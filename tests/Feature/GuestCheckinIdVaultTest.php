<?php

use App\Models\Guest;
use App\Models\GuestDocument;
use App\Models\Media;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\Guest\GuestIdDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Guest Portal batch 2 · GATE 2 — the sensitive ID vault + arrival window (founder decision D2). Proven:
//  • the ID is REALLY encrypted at rest (the blob on disk is ciphertext that decrypts to the original),
//    stored on the PRIVATE disk, register-only, verification_status='pending' — NEVER a fake "verified";
//  • a GDPR erase (anonymize) purges the ID file + rows AND the guest's arrival note/window;
//  • the 30-day retention command hard-deletes IDs 30 days after checkout, keeps fresher ones;
//  • guest isolation holds (a foreign booking → 404, nothing written).

function vaultGuest(Guest $g, string $email): Guest
{
    $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'phone' => '+351900000001']);
    $g->password = 'guest-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

function vaultBook(array $s, Guest $guest, Unit $unit, string $in = '2026-09-05', string $out = '2026-09-08'): Reservation
{
    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
    );
}

// ── the submit stores a REAL encrypted, register-only, pending ID (never "verified") ────────────────
it('encrypts the guest ID at rest on the private disk as a pending register record', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    $a = vaultGuest($s['guest'], 'ada@example.test');
    $reservation = vaultBook($s, $a, $s['units'][0]);

    $image = UploadedFile::fake()->image('passport.jpg', 600, 400);
    $original = (string) file_get_contents($image->getRealPath());

    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $reservation->getKey()), [
        'id_type' => 'passport',
        'id_photo' => $image,
        'arrival_window' => '13-15',
    ])->assertRedirect();

    $doc = GuestDocument::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    expect($doc->guest_id)->toBe($a->getKey())
        ->and($doc->type)->toBe('passport')
        ->and($doc->verification_status)->toBe('pending');   // captured, NEVER a fabricated "verified"

    $media = $doc->document->media;
    expect($media->disk)->toBe('local');                     // the PRIVATE disk (never `public`)

    // 🔒 the blob on disk is CIPHERTEXT — not the raw image — and it decrypts back to the original bytes.
    $stored = Storage::disk('local')->get($media->path);
    expect($stored)->not->toBe($original);
    expect(Crypt::decryptString($stored))->toBe($original);

    // the arrival window landed on the reservation (operator-visible), intent stamped, status untouched.
    $fresh = $reservation->fresh();
    expect($fresh->arrival_window)->toBe('13-15')
        ->and($fresh->guest_checked_in_at)->not->toBeNull()
        ->and($fresh->status)->toBe('confirmed');
});

// ── the arrival note is stored (as the guest's own reservation note) ────────────────────────────────
it('stores the arrival note and window from the wizard', function () {
    $s = seedScenario(1);
    $a = vaultGuest($s['guest'], 'ada@example.test');
    $reservation = vaultBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $reservation->getKey()), [
        'arrival_window' => '18+',
        'arrival_note' => 'Travelling with a cot, late arrival.',
    ])->assertRedirect();

    $fresh = $reservation->fresh();
    expect($fresh->arrival_window)->toBe('18+')
        ->and($fresh->notes_guest)->toBe('Travelling with a cot, late arrival.');
});

// ── a re-upload SUPERSEDES (one register record, the old encrypted blob is gone) ────────────────────
it('supersedes a prior ID on re-upload without stacking duplicates', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    $a = vaultGuest($s['guest'], 'ada@example.test');
    $reservation = vaultBook($s, $a, $s['units'][0]);

    app(GuestIdDocumentService::class)->store($a, $reservation, UploadedFile::fake()->image('a.jpg'), 'passport');
    $firstPath = (string) Media::query()->where('collection', 'guest-id')->value('path');

    app(GuestIdDocumentService::class)->store($a, $reservation, UploadedFile::fake()->image('b.jpg'), 'id_card');

    expect(GuestDocument::query()->where('reservation_id', $reservation->getKey())->count())->toBe(1);
    expect(Storage::disk('local')->exists($firstPath))->toBeFalse();   // the old blob is purged
    expect(GuestDocument::query()->where('reservation_id', $reservation->getKey())->value('type'))->toBe('id_card');
});

// ── GDPR erase purges the ID (file + rows) AND the guest's arrival note/window ──────────────────────
it('purges the ID file and clears the arrival note on GDPR erase', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    $a = vaultGuest($s['guest'], 'ada@example.test');
    $reservation = vaultBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $reservation->getKey()), [
        'id_photo' => UploadedFile::fake()->image('passport.jpg'),
        'arrival_window' => '11-13',
        'arrival_note' => 'Peanut allergy.',
    ])->assertRedirect();

    $path = (string) Media::query()->where('collection', 'guest-id')->value('path');
    expect(Storage::disk('local')->exists($path))->toBeTrue();

    $a->anonymize();

    expect(Storage::disk('local')->exists($path))->toBeFalse();                        // the encrypted bytes are gone
    expect(GuestDocument::query()->where('reservation_id', $reservation->getKey())->count())->toBe(0);
    expect($reservation->fresh()->notes_guest)->toBeNull();                            // the guest's note is scrubbed
    expect($reservation->fresh()->arrival_window)->toBeNull();
});

// ── the 30-day retention command hard-deletes expired IDs, keeps fresher ones ───────────────────────
it('purges guest IDs 30 days after checkout but keeps fresher ones', function () {
    Storage::fake('local');
    $s = seedScenario(2);
    $a = vaultGuest($s['guest'], 'ada@example.test');

    // Two bookings + IDs; then backdate ONE checkout well past the 30-day window.
    $old = vaultBook($s, $a, $s['units'][0]);
    $recent = vaultBook($s, $a, $s['units'][1]);
    app(GuestIdDocumentService::class)->store($a, $old, UploadedFile::fake()->image('old.jpg'), 'passport');
    app(GuestIdDocumentService::class)->store($a, $recent, UploadedFile::fake()->image('new.jpg'), 'passport');

    $old->forceFill(['check_out' => '2026-06-01'])->save();     // >30d before "today" (2026-08-01)
    $recent->forceFill(['check_out' => '2026-07-25'])->save();  // within the 30-day window

    $oldDoc = GuestDocument::query()->where('reservation_id', $old->getKey())->with('document.media')->first();
    $oldPath = (string) ($oldDoc?->document?->media?->path ?? '');

    $this->artisan('guest-documents:purge-expired')->assertSuccessful();

    expect(GuestDocument::query()->where('reservation_id', $old->getKey())->count())->toBe(0);      // purged
    expect(GuestDocument::query()->where('reservation_id', $recent->getKey())->count())->toBe(1);   // kept
    if ($oldPath !== '') {
        expect(Storage::disk('local')->exists($oldPath))->toBeFalse();
    }
});

// ── isolation: a guest cannot attach an ID to another guest's booking (404, nothing written) ────────
it('404s an ID upload to another guest’s booking', function () {
    Storage::fake('local');
    $s = seedScenario(2);
    $a = vaultGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $bRes = vaultBook($s, $b, $s['units'][1]);

    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $bRes->getKey()), [
        'id_photo' => UploadedFile::fake()->image('sneaky.jpg'),
    ])->assertNotFound();

    expect(GuestDocument::query()->where('reservation_id', $bRes->getKey())->count())->toBe(0);
});
