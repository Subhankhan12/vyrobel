<?php

use App\Models\BookingQuote;
use App\Models\Guest;
use App\Models\GuestChangeRequest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Note;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Services\GuestService;
use App\Services\InboxService;
use App\Support\Guest\GuestSpend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// GDPR ERASURE — THE UI TRIGGER (register item 4, closed 2026-07-17).
//
// ⚠️ THE REFRAME THAT MATTERS: this is not "wire an enforcement check onto an existing feature". Before this
// gate, `Guest::anonymize()` was called from **NOWHERE** — no route, no controller, no Livewire class. Every
// hit outside the model itself was a COMMENT or a test. The erase was correct, thorough and tested… and
// unreachable. A data-subject erasure request could not be served by an operator at all. The register logged
// it honestly rather than pretending the GDPR story was complete.
//
// WHAT THIS FILE DEFENDS:
//   • The trigger REUSES the real `Guest::anonymize()` — it does not re-implement redaction. That method
//     already erases the columns, hard-deletes ID documents + their stored bytes, redacts inbox message
//     bodies + the thread's participant identity, and (since Wave 2) redacts NOTE bodies. Re-implementing
//     any of it would guarantee the copy drifts and misses a store.
//   • It is PERMISSION-GATED on `guests.erase` (invariant #30) — Property Owner / Group Admin only. A Hotel
//     Manager runs the house; they do not get to erase a person.
//   • It is NOT ONE-CLICK. Erasure is irreversible, so intent is confirmed by a TYPED confirmation that the
//     SERVER verifies — a client-side confirm() alone is theatre, bypassable by anyone who can POST.
//   • The financial history SURVIVES. That is the whole design of anonymize(): strip the PII, KEEP the row,
//     so the folio/ledger that references this guest stays referentially intact.

function erasableGuest(array $s): array
{
    $guest = Guest::create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada.lovelace@example.test',
        'phone' => '+353860000001',
    ]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    Reservation::query()->whereKey($reservation->getKey())->update(['lead_guest_id' => $guest->id]);
    postRoom($reservation->fresh(), 30000);

    // A note (staff PII about the guest) and a real inbox conversation (the guest's own words).
    app(GuestService::class)->addNote($guest, 'Travelling with her partner Charles — allergic to shellfish.',
        User::factory()->create(['name' => 'Marco Staff']));

    $thread = app(InboxService::class)->startThread([
        'channel' => 'manual',
        'subject' => 'Late arrival',
        'guest_id' => $guest->id,
        'reservation_id' => $reservation->id,
    ]);
    app(InboxService::class)->postInbound([
        'channel' => 'manual',
        'body' => 'Hi, this is Ada — my number is +353860000001, please call.',
        'external_thread_ref' => (string) $thread->external_thread_ref,
        'guest_id' => $guest->id,
    ]);

    return [$guest, $reservation];
}

// ── 1. THE FEATURE — an authorised operator can actually erase a guest, WITH confirmation ────────
it('erases a guest from the profile when an authorised operator confirms', function () {
    $s = seedScenario(1);
    [$guest, $reservation] = erasableGuest($s);

    // Property Owner is the ORG ADMIN — one of the two roles the catalog trusts with guests.erase.
    $admin = operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner');

    $response = $this->actingAs($admin)->post('/app/guests/'.$guest->id.'/anonymize', ['confirm' => 'ERASE']);

    $response->assertRedirect(route('app.guests.show', $guest->id))->assertSessionHas('guest_notice');

    $fresh = Guest::query()->findOrFail($guest->id);

    // It called the REAL anonymize(): every PII store it owns is cleared…
    expect($fresh->isAnonymized())->toBeTrue()
        ->and($fresh->first_name)->toBeNull()
        ->and($fresh->last_name)->toBeNull()
        ->and($fresh->email)->toBeNull()
        ->and($fresh->phone)->toBeNull();

    // …including the note body (a staff note ABOUT an identified person is that person's data)…
    expect(Note::query()->where('notable_id', $guest->id)->first()->body)->toBe('');

    // …and the guest's own words in the inbox, plus the thread's encrypted participant identity.
    $threadIds = MessageThread::query()->where('guest_id', $guest->id)->pluck('id');
    expect(Message::query()->whereIn('thread_id', $threadIds)->pluck('body')->unique()->all())->toBe([''])
        ->and(MessageThread::query()->whereKey($threadIds)->first()->participant_identity)->toBeNull();

    // Nothing identifying survives anywhere — asserted against the raw rows, not the casts.
    $rawGuest = json_encode((array) DB::table('guests')->where('id', $guest->id)->first());
    foreach (['Ada', 'Lovelace', '+353860000001'] as $pii) {
        expect($rawGuest)->not->toContain($pii);
    }
});

// ── 2. THE FINANCIAL HISTORY SURVIVES — the entire reason anonymize() keeps the row ──────────────
it('keeps the guest row and their financial history intact after erasure', function () {
    $s = seedScenario(1);
    [$guest, $reservation] = erasableGuest($s);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post('/app/guests/'.$guest->id.'/anonymize', ['confirm' => 'ERASE']);

    // The row is KEPT — never soft-deleted-and-retained, and never hard-deleted: the folio references it.
    expect(Guest::query()->find($guest->id))->not->toBeNull()
        ->and(Guest::query()->withTrashed()->find($guest->id)->deleted_at)->toBeNull();

    // The money is untouched: €300 billed still reconciles through the shared definition.
    $buckets = GuestSpend::for(Guest::query()->findOrFail($guest->id));
    expect($buckets['EUR']['billed_minor'])->toBe(30000)
        ->and($reservation->fresh())->not->toBeNull();

    // And the profile renders as an ERASED profile whose history is still readable (the established
    // behaviour the Wave 2 screens already handle).
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/guests/'.$guest->id)
        ->assertOk()
        ->assertSee('Anonymized guest')
        ->assertSee('€300.00')
        ->assertDontSee('Ada Lovelace');
});

// ── 3. 🔑 PERMISSION-GATED — erasing a person is not a privilege every back-office role gets ─────
it('refuses erasure to a role without guests.erase, and erases nothing', function () {
    $s = seedScenario(1);
    [$guest] = erasableGuest($s);

    // Hotel Manager runs the house and holds guests.manage — but NOT guests.erase.
    $manager = operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager');

    $this->actingAs($manager)->post('/app/guests/'.$guest->id.'/anonymize', ['confirm' => 'ERASE'])
        ->assertForbidden();

    // A 403 that still erased would be catastrophic and irreversible.
    expect(Guest::query()->findOrFail($guest->id)->isAnonymized())->toBeFalse()
        ->and(Guest::query()->findOrFail($guest->id)->email)->toBe('ada.lovelace@example.test');
});

it('hides the erase control from a role that may not erase, and shows it to one that may', function () {
    $s = seedScenario(1);
    [$guest] = erasableGuest($s);

    $this->actingAs(operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager'))
        ->get('/app/guests/'.$guest->id)->assertOk()->assertDontSee('Erase this guest');

    $this->actingAs(operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner'))
        ->get('/app/guests/'.$guest->id)->assertOk()->assertSee('Erase this guest');
});

// ── 4. NOT ONE-CLICK — the confirmation is verified by the SERVER, not just the browser ──────────
it('refuses to erase without the typed confirmation', function () {
    $s = seedScenario(1);
    [$guest] = erasableGuest($s);
    $admin = operatorUser($s, [], 'Property Owner');

    // A bare POST — exactly what bypassing the UI's confirm dialog produces. An irreversible action must
    // not depend on client-side JavaScript for its intent check.
    $this->actingAs($admin)->post('/app/guests/'.$guest->id.'/anonymize', [])
        ->assertSessionHasErrors('confirm');
    expect(Guest::query()->findOrFail($guest->id)->isAnonymized())->toBeFalse();

    // The wrong word is refused too.
    $this->actingAs($admin)->post('/app/guests/'.$guest->id.'/anonymize', ['confirm' => 'yes'])
        ->assertSessionHasErrors('confirm');
    expect(Guest::query()->findOrFail($guest->id)->isAnonymized())->toBeFalse();
});

it('renders a typed confirmation on the profile, not a bare one-click button', function () {
    $s = seedScenario(1);
    [$guest] = erasableGuest($s);

    $response = $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/guests/'.$guest->id);

    $response->assertOk()
        ->assertSee('Erase this guest')
        // The UI must SAY it is permanent, and require the word the server checks for.
        ->assertSee('permanent', false)
        ->assertSee('name="confirm"', false);

    assertNoRawBlade($response);
});

// ── 5. TENANT-SCOPED — a cross-org guest 404s, and is not erased ─────────────────────────────────
it('404s rather than erasing another organisation’s guest', function () {
    $foreign = seedScenario(1);
    [$foreignGuest] = erasableGuest($foreign);

    $s = seedScenario(1);
    // The actor HOLDS guests.erase — so a 404 here can only come from the tenant scope, which is what this
    // test exists to prove (the same 403-would-mask-it trap the Gate 2 fixtures had to avoid).
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->post('/app/guests/'.$foreignGuest->id.'/anonymize', ['confirm' => 'ERASE'])
        ->assertNotFound();

    expect(Guest::query()->withoutGlobalScopes()->findOrFail($foreignGuest->id)->isAnonymized())->toBeFalse();
});

// ── 6. #26 STILL RUNS FIRST — an owner never reaches the permission layer ────────────────────────
it('bounces an owner before any permission is consulted', function () {
    $s = seedScenario(1);
    [$guest] = erasableGuest($s);

    $this->actingAs(ownerRoleUser($s, 0))
        ->post('/app/guests/'.$guest->id.'/anonymize', ['confirm' => 'ERASE'])
        ->assertRedirect(route('owner.dashboard'));

    expect(Guest::query()->findOrFail($guest->id)->isAnonymized())->toBeFalse();
});

// ── 7. Erasing an already-erased guest is refused cleanly, never a 500 ───────────────────────────
it('refuses to re-erase an already anonymized guest', function () {
    $s = seedScenario(1);
    [$guest] = erasableGuest($s);
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->post('/app/guests/'.$guest->id.'/anonymize', ['confirm' => 'ERASE'])
        ->assertSessionHas('guest_notice');

    $this->actingAs($admin)->post('/app/guests/'.$guest->id.'/anonymize', ['confirm' => 'ERASE'])
        ->assertSessionHas('guest_error');
});

// ══════════════════════════════════════════════════════════════════════════════════════════════════
// AUDIT 2026-08-07 · H4 + H5 — THE ERASURE WAS INCOMPLETE IN THREE STORES
//
// anonymize() cleared eight stores and MISSED:
//   • `reviews.operator_response` (H4/F-2b-02) — the operator's PUBLIC reply kept the guest's first name on
//     an indexable storefront page tied to a specific stay. The byline said "A guest" while the reply below
//     said "Thank you Sofia": visibly incomplete to anyone reading the page.
//   • `reservations.notes_internal` + `expected_arrival` (H5/F-2b-03) — the STAFF-authored half of a row
//     whose guest half was already cleared. Full name, phone, allergies, companions survived verbatim,
//     defeating the encrypted `guests.phone` column by plaintext copy.
//
// An adversarial schema sweep for the same class of miss then found two more (change-request payloads and
// converted booking quotes), which this test also pins.
//
// THE SHAPE OF THE TEST IS THE POINT: plant a value in EVERY store, erase once, assert ALL are gone —
// verified against the RAW DB rows, not the model (a cast could hide a survivor). Adding a store later
// without adding it to anonymize() fails here.
// ══════════════════════════════════════════════════════════════════════════════════════════════════

it('clears EVERY free-text store about the guest — raw-DB verified', function () {
    $s = seedScenario(1);
    [$guest, $reservation] = erasableGuest($s);

    $reservation->update([
        'notes_guest' => 'Please leave a cot — travelling with my daughter Mia',
        'arrival_window' => '18:00-20:00',
        'notes_internal' => 'VIP. Ada Lovelace, +351 910 000 001. Nut allergy. Partner Charles joining.',
        'expected_arrival' => '19:30',
    ]);

    $review = Review::create([
        'organization_id' => $s['org']->id,
        'guest_id' => $guest->id,
        'reservation_id' => $reservation->id,
        'unit_id' => $s['units'][0]->id,
        'room_type_id' => $s['units'][0]->room_type_id,
        'rating' => 5,
        'body' => 'Lovely stay.',
        'operator_response' => 'Thank you Ada — a pleasure hosting you and Charles!',
        'responded_at' => now(),
        'status' => 'published',
    ]);

    $guest->anonymize();

    // RAW rows — bypass every cast/accessor, so nothing can be hidden by presentation.
    $rawRes = DB::table('reservations')->where('id', $reservation->id)->first();
    $rawRev = DB::table('reviews')->where('id', $review->id)->first();

    expect($rawRes->notes_guest)->toBeNull()
        ->and($rawRes->arrival_window)->toBeNull()
        ->and($rawRes->notes_internal)->toBeNull()        // H5
        ->and($rawRes->expected_arrival)->toBeNull();     // H5

    expect($rawRev->operator_response)->toBeNull()        // H4 — the public leak
        ->and($rawRev->responded_at)->toBeNull();

    // The rating survives: erasure must not move the derived #76 aggregates.
    expect((int) $rawRev->rating)->toBe(5);

    // Nothing anywhere in those rows still spells the erased identity.
    foreach ([$rawRes, $rawRev] as $row) {
        $blob = strtolower(json_encode((array) $row, JSON_THROW_ON_ERROR));
        expect($blob)->not->toContain('ada')
            ->and($blob)->not->toContain('lovelace')
            ->and($blob)->not->toContain('910 000 001')
            ->and($blob)->not->toContain('charles');
    }
});

it('clears a converted booking quote and a change-request payload on erasure', function () {
    $s = seedScenario(1);
    [$guest, $reservation] = erasableGuest($s);

    $quote = BookingQuote::create([
        'organization_id' => $s['org']->id,
        'property_id' => $s['property']->id,
        'reference' => 'Q-'.Str::upper(Str::random(6)),
        'session_token' => Str::random(40),
        'check_in' => '2026-09-01', 'check_out' => '2026-09-03',
        'adults' => 2, 'currency' => 'EUR', 'total_minor' => 20000,
        'requested' => ['room_type_id' => null],
        'email' => 'ada@example.test', 'phone' => '+351910000001',
        'guest_snapshot' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
        'status' => 'converted',
        'converted_reservation_id' => $reservation->id,
    ]);

    $request = GuestChangeRequest::create([
        'organization_id' => $s['org']->id,
        'guest_id' => $guest->id,
        'reservation_id' => $reservation->id,
        'type' => 'date_change',
        'status' => 'pending',
        'payload' => ['note' => 'Arriving late with my daughter Mia'],
    ]);

    $guest->anonymize();

    $rawQuote = DB::table('booking_quotes')->where('id', $quote->id)->first();
    $rawReq = DB::table('guest_change_requests')->where('id', $request->id)->first();

    expect($rawQuote->email)->toBeNull()
        ->and($rawQuote->phone)->toBeNull()
        ->and($rawQuote->guest_snapshot)->toBeNull();
    // payload is NOT NULL at the schema level, so erasure EMPTIES it (redact-don't-delete).
    expect(json_decode((string) $rawReq->payload, true))->toBe([]);
    expect((string) $rawReq->payload)->not->toContain('Mia');

    // The money/audit skeleton survives — erasure strips PII, it does not delete history.
    expect((int) $rawQuote->total_minor)->toBe(20000);
    expect($rawReq->status)->toBe('pending');
});
