<?php

use App\Mail\OperatorGuestMessageMail;
use App\Models\AuditEvent;
use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// OPERATOR GUESTS-LIST BULK ACTIONS (#87) — Message · Add-tag · Export · Delete(GDPR erasure) + Add guest.
// The load-bearing facts: the bulk DELETE erases PII but RETAINS the (now-anonymized) reservations; it is
// gated (guests.erase) + typed-ERASE server-confirmed; marketing messages honour consent; export is audited;
// every write respects read-only. Ids are re-resolved tenant-scoped, so a tampered id can't reach another org.

/** A guest with real CRM columns in the current tenant. */
function bulkGuest(string $first, string $last, array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => $first, 'last_name' => $last], $extra));
}

/** A booking whose lead guest is $guest (its own unit — book() reuses the same window). */
function bulkStay(array $s, int $unitIndex, Guest $guest): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    Reservation::query()->whereKey($reservation->getKey())->update(['lead_guest_id' => $guest->id]);

    return $reservation->fresh();
}

// ── THE CORE — bulk DELETE erases PII but RETAINS the anonymized reservations ────────────────────
it('bulk-erases guest PII while keeping the reservations (anonymized, not destroyed)', function () {
    $s = seedScenario(2);
    $a = bulkGuest('Ada', 'Lovelace', ['email' => 'ada@example.test', 'phone' => '+3530000001', 'city' => 'Dublin']);
    $b = bulkGuest('Bo', 'Booker', ['email' => 'bo@example.test']);
    $stayA = bulkStay($s, 0, $a);
    $stayB = bulkStay($s, 1, $b);

    // Property Owner holds guests.erase (a Hotel Manager does not — proven below).
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)
        ->post(route('app.guests.bulk.anonymize'), ['ids' => [$a->id, $b->id], 'confirm' => 'ERASE'])
        ->assertRedirect(route('app.guests'));

    // PII is GONE on both guests…
    foreach ([$a, $b] as $guest) {
        $fresh = $guest->fresh();
        expect($fresh)->not->toBeNull()
            ->and($fresh->first_name)->toBeNull()
            ->and($fresh->email)->toBeNull()
            ->and($fresh->city)->toBeNull()
            ->and($fresh->isAnonymized())->toBeTrue();
    }

    // …but the reservations SURVIVE, still pointing at the (now anonymized) guest rows — financials intact.
    expect(Reservation::find($stayA->id))->not->toBeNull()
        ->and((string) Reservation::find($stayA->id)->lead_guest_id)->toBe((string) $a->id)
        ->and(Reservation::find($stayB->id))->not->toBeNull();
});

// ── The bulk erase is GATED (guests.erase — Property Owner / Group Admin only) ───────────────────
it('refuses a bulk erase from a user without guests.erase', function () {
    $s = seedScenario(1);
    $a = bulkGuest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    // Hotel Manager holds guests.manage but NOT guests.erase.
    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post(route('app.guests.bulk.anonymize'), ['ids' => [$a->id], 'confirm' => 'ERASE'])
        ->assertForbidden();

    expect($a->fresh()->isAnonymized())->toBeFalse();   // untouched
});

// ── The bulk erase requires the typed ERASE word (server-checked) ────────────────────────────────
it('rejects a bulk erase without the typed ERASE confirmation', function () {
    $s = seedScenario(1);
    $a = bulkGuest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post(route('app.guests.bulk.anonymize'), ['ids' => [$a->id], 'confirm' => 'delete'])
        ->assertSessionHasErrors('confirm');

    expect($a->fresh()->isAnonymized())->toBeFalse();
});

// ── Bulk MESSAGE emails the selected guests + records an inbox thread ────────────────────────────
it('bulk-messages selected guests by email and records a manual inbox thread', function () {
    Mail::fake();
    $s = seedScenario(1);
    $a = bulkGuest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    $this->actingAs(operatorUser($s))
        ->post(route('app.guests.bulk.message'), ['ids' => [$a->id], 'body' => 'Your room is ready.'])
        ->assertRedirect(route('app.guests'));

    Mail::assertSent(OperatorGuestMessageMail::class, fn (OperatorGuestMessageMail $m): bool => $m->hasTo('ada@example.test'));
    expect(MessageThread::query()->where('guest_id', $a->id)->where('channel', 'manual')->exists())->toBeTrue();
});

// ── Marketing mode genuinely gates on consent — a non-consented guest is skipped ─────────────────
it('sends a marketing bulk message only to guests who consented', function () {
    Mail::fake();
    $s = seedScenario(1);
    $yes = bulkGuest('Yes', 'Consent', ['email' => 'yes@example.test', 'marketing_consent' => true]);
    $no = bulkGuest('No', 'Consent', ['email' => 'no@example.test', 'marketing_consent' => false]);

    $this->actingAs(operatorUser($s))
        ->post(route('app.guests.bulk.message'), ['ids' => [$yes->id, $no->id], 'body' => 'Autumn offer', 'marketing' => '1'])
        ->assertRedirect();

    Mail::assertSent(OperatorGuestMessageMail::class, fn (OperatorGuestMessageMail $m): bool => $m->hasTo('yes@example.test'));
    Mail::assertNotSent(OperatorGuestMessageMail::class, fn (OperatorGuestMessageMail $m): bool => $m->hasTo('no@example.test'));
});

// ── Export streams a CSV and is AUDITED (count only, never the values) ───────────────────────────
it('exports selected guests to an audited CSV', function () {
    $s = seedScenario(1);
    $a = bulkGuest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    $response = $this->actingAs(operatorUser($s))
        ->post(route('app.guests.bulk.export'), ['ids' => [$a->id]]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect(AuditEvent::query()->where('action', 'guest.exported')->exists())->toBeTrue();
});

// ── Add guest creates a real, audited profile ────────────────────────────────────────────────────
it('creates a new guest through the add-guest form, audited', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s))
        ->post(route('app.guests.store'), ['last_name' => 'Newman', 'first_name' => 'Nadia', 'email' => 'nadia@example.test', 'city' => 'Lisbon'])
        ->assertRedirect();

    $guest = Guest::query()->where('last_name', 'Newman')->first();
    expect($guest)->not->toBeNull()
        ->and((string) $guest->email)->toBe('nadia@example.test')
        ->and((string) $guest->city)->toBe('Lisbon');
    expect(AuditEvent::query()->where('action', 'guest.created')->exists())->toBeTrue();
});

// ── Read-only (#84) refuses bulk writes ──────────────────────────────────────────────────────────
it('refuses bulk message and erase while the workspace is paused', function () {
    Mail::fake();
    $s = seedScenario(1);
    $s['org']->forceFill(['paused_until' => now()->addMonth()])->save();
    $a = bulkGuest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post(route('app.guests.bulk.message'), ['ids' => [$a->id], 'body' => 'hi'])
        ->assertRedirect();
    Mail::assertNothingSent();

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post(route('app.guests.bulk.anonymize'), ['ids' => [$a->id], 'confirm' => 'ERASE'])
        ->assertRedirect();
    expect($a->fresh()->isAnonymized())->toBeFalse();
});
