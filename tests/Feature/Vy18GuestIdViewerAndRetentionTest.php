<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\GuestDocument;
use App\Models\Media;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\Guest\GuestIdDocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ⚠️⚠️ VY-18 (audit of 2026-11-24; his ruling the same day: "Build the viewer") — AN ID IMAGE CAN BE OPENED BY THE STAFF WHO NEED IT,
// EVERY OPENING IS RECORDED, AND AN IMAGE DOES NOT OUTLIVE THE BOOKING IT WAS TAKEN FOR.
//
// ID images were collected (encrypted, on the private disk) and no screen or route could open one — so every document stayed
// "pending" for ever. And the 30-day delete counted from the checkout date: a CANCELLED booking months ahead kept its image until
// 30 days after a stay that never happened, and an image whose booking had been deleted was never matched at all.
//
//   · staff with `guest_documents.manage` open the image from the reservation's page — decrypted for that response only, never cached;
//   · each opening writes an audit row (who, which document — no guest data);
//   · they mark it checked or rejected (it was "pending" for ever);
//   · cancelling or no-showing a booking deletes its ID image at once; the nightly job also deletes the image of any booking that is
//     cancelled, a no-show or gone.

uses(DatabaseTransactions::class);

/** @return array{0: Reservation, 1: GuestDocument, 2: string} the booking, its ID record and the ORIGINAL image bytes */
function vy18WithId(array $s, string $in = '2026-09-05', string $out = '2026-09-08'): array
{
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
    );
    $image = UploadedFile::fake()->image('passport.jpg', 320, 200);
    $bytes = (string) file_get_contents($image->getRealPath());
    $document = app(GuestIdDocumentService::class)->store($s['guest'], $reservation, $image, 'passport');

    return [$reservation, $document, $bytes];
}

function vy18ViewUrl(Reservation $reservation, GuestDocument $document): string
{
    return route('app.reservations.id-documents.show', [$reservation->id, $document->id]);
}

// ── the viewer ──────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ VY-18: staff with the permission open the ID image — the ORIGINAL bytes, never cached — and the opening is recorded', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    [$reservation, $document, $bytes] = vy18WithId($s);
    $user = operatorUser($s, [], 'Front Desk');   // holds guest_documents.manage

    $response = test()->actingAs($user)->get(vy18ViewUrl($reservation, $document))->assertOk();

    expect($response->getContent())->toBe($bytes)
        ->and($response->headers->get('Content-Type'))->toBe('image/jpeg')
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and((string) $response->headers->get('Content-Disposition'))->toStartWith('inline');

    $audit = AuditEvent::query()->where('action', 'guest_document.viewed')->where('subject_id', $document->id)->get();
    expect($audit)->toHaveCount(1)
        ->and((string) $audit[0]->actor_id)->toBe((string) $user->id)
        // the row says WHICH document and which booking — never the guest's name or anything read from the image
        ->and(json_encode([$audit[0]->before, $audit[0]->after]))->not->toContain((string) $s['guest']->last_name);

    // a second look is a second row: every opening is recorded, not the first only
    test()->actingAs($user)->get(vy18ViewUrl($reservation, $document))->assertOk();
    expect(AuditEvent::query()->where('action', 'guest_document.viewed')->where('subject_id', $document->id)->count())->toBe(2);
});

it('⚠️⚠️ VY-18: a role WITHOUT the permission cannot open the image, and nothing is recorded as viewed', function (string $role) {
    Storage::fake('local');
    $s = seedScenario(1);
    [$reservation, $document, $bytes] = vy18WithId($s);

    $response = test()->actingAs(operatorUser($s, [], $role))->get(vy18ViewUrl($reservation, $document))->assertForbidden();

    expect($response->getContent())->not->toBe($bytes)
        ->and(AuditEvent::query()->where('action', 'guest_document.viewed')->where('subject_id', $document->id)->count())->toBe(0);
})->with(['Housekeeping', 'Accountant', 'Reservation Agent', 'Guest Support Agent']);

it('⚠️⚠️ VY-18: another ORGANISATION\'S document is not found — and a document of another BOOKING is not served under this one', function () {
    Storage::fake('local');
    $a = seedScenario(2);
    [$reservationA, $documentA] = vy18WithId($a);
    [$otherBooking] = vy18WithId($a, '2026-09-08', '2026-09-09');   // a second booking of the same organisation, its own document

    // the right document under the wrong booking of the SAME organisation → 404
    test()->actingAs(operatorUser($a, [], 'Front Desk'))
        ->get(route('app.reservations.id-documents.show', [$otherBooking->id, $documentA->id]))->assertNotFound();

    // another organisation's member, permitted in their own house → 404 here
    $b = seedScenario(1);
    test()->actingAs(operatorUser($b, [], 'Front Desk'))->get(vy18ViewUrl($reservationA, $documentA))->assertNotFound();

    expect(AuditEvent::query()->where('action', 'guest_document.viewed')->where('subject_id', $documentA->id)->count())->toBe(0);
});

it('⚠️ VY-18: staff mark the document CHECKED or REJECTED — it is no longer "pending" for ever — and the decision is recorded', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    [$reservation, $document] = vy18WithId($s);
    $user = operatorUser($s, [], 'Front Desk');
    $url = route('app.reservations.id-documents.review', [$reservation->id, $document->id]);

    test()->actingAs($user)->post($url, ['decision' => 'verified'])->assertRedirect(route('app.reservations.show', $reservation->id));
    expect($document->refresh()->verification_status)->toBe('verified')
        ->and((string) $document->verified_by)->toBe((string) $user->id)
        ->and(AuditEvent::query()->where('action', 'guest_document.verified')->where('subject_id', $document->id)->count())->toBe(1);

    test()->actingAs($user)->post($url, ['decision' => 'rejected'])->assertRedirect();
    expect($document->refresh()->verification_status)->toBe('rejected')
        ->and(AuditEvent::query()->where('action', 'guest_document.rejected')->where('subject_id', $document->id)->count())->toBe(1);

    // anything else is not a decision; a role without the permission decides nothing
    test()->actingAs($user)->post($url, ['decision' => 'pending'])->assertSessionHasErrors('decision');
    test()->actingAs(operatorUser($s, [], 'Housekeeping'))->post($url, ['decision' => 'verified'])->assertForbidden();
    expect($document->refresh()->verification_status)->toBe('rejected');
});

it('⚠️ VY-18: the reservation\'s page shows the ID card to a permitted role only — and never when there is no document', function () {
    Storage::fake('local');
    $s = seedScenario(2);
    [$reservation, $document] = vy18WithId($s);
    $bare = book($s, ['unit_id' => $s['units'][1]->id]);   // a booking with no ID

    $frontDesk = operatorUser($s, [], 'Front Desk');
    test()->actingAs($frontDesk)->get(route('app.reservations.show', $reservation->id))->assertOk()
        ->assertSee(__('livewire/reservation-detail.id_title'))
        ->assertSee(vy18ViewUrl($reservation, $document), false);
    test()->actingAs($frontDesk)->get(route('app.reservations.show', $bare->id))->assertOk()
        ->assertDontSee(__('livewire/reservation-detail.id_title'));

    // the Reservation Agent reads the reservation but holds no document permission: no card, no link
    test()->actingAs(operatorUser($s, [], 'Reservation Agent'))->get(route('app.reservations.show', $reservation->id))->assertOk()
        ->assertDontSee(__('livewire/reservation-detail.id_title'))
        ->assertDontSee(vy18ViewUrl($reservation, $document), false);
});

// ── how long an image is kept ───────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ VY-18: CANCELLING a booking deletes its ID image at once — the file and all three rows', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    [$reservation, $document] = vy18WithId($s);   // arrives 5 September — five weeks ahead of the suite's clock; checkout + 30 days is in October
    $path = (string) $document->document->media->path;
    $mediaId = (string) $document->document->media->id;
    $docId = (string) $document->document_id;
    expect(Storage::disk('local')->exists($path))->toBeTrue();

    app(AssignmentService::class)->cancelReservation($reservation, 'guest changed plans');

    expect(Storage::disk('local')->exists($path))->toBeFalse()
        ->and(GuestDocument::withTrashed()->whereKey($document->id)->exists())->toBeFalse()
        ->and(Document::withTrashed()->whereKey($docId)->exists())->toBeFalse()
        ->and(Media::withTrashed()->whereKey($mediaId)->exists())->toBeFalse();
});

it('⚠️ VY-18: a NO-SHOW deletes the image the same way', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    [$reservation, $document] = vy18WithId($s);
    $path = (string) $document->document->media->path;

    app(AssignmentService::class)->markNoShow($reservation);

    expect(Storage::disk('local')->exists($path))->toBeFalse()
        ->and(GuestDocument::withTrashed()->whereKey($document->id)->exists())->toBeFalse();
});

it('⚠️⚠️ VY-18: the nightly job deletes the image of a booking that is cancelled or GONE, whatever its dates — and keeps a live one', function () {
    Storage::fake('local');
    $s = seedScenario(3);
    $ids = app(GuestIdDocumentService::class);
    $make = function (int $unit, string $in, string $out) use ($s, $ids): array {
        $reservation = app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][$unit]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
        );

        return [$reservation, $ids->store($s['guest'], $reservation, UploadedFile::fake()->image('id.jpg', 100, 80), 'id_card')];
    };

    [$live, $liveDoc] = $make(0, '2026-09-01', '2026-09-03');
    [$cancelled, $cancelledDoc] = $make(1, '2026-09-01', '2026-09-03');
    [$gone, $goneDoc] = $make(2, '2026-09-01', '2026-09-03');

    // the states as an OLDER release left them: the status changed, the booking was removed, and nothing purged the image then
    $cancelled->forceFill(['status' => 'cancelled'])->saveQuietly();
    $gone->delete();

    test()->artisan('guest-documents:purge-expired')->assertSuccessful();

    expect(GuestDocument::withTrashed()->whereKey($cancelledDoc->id)->exists())->toBeFalse()
        ->and(GuestDocument::withTrashed()->whereKey($goneDoc->id)->exists())->toBeFalse()
        ->and(GuestDocument::query()->whereKey($liveDoc->id)->exists())->toBeTrue();   // a live, future stay keeps its image
});

it('VY-18 CONTROL: the 30-days-after-checkout rule still holds for a stay that happened', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    [, $document] = vy18WithId($s, '2026-09-01', '2026-09-03');

    test()->travelTo('2026-09-20');
    test()->artisan('guest-documents:purge-expired')->assertSuccessful();
    expect(GuestDocument::query()->whereKey($document->id)->exists())->toBeTrue();    // 17 days after checkout: kept

    test()->travelTo('2026-10-05');
    test()->artisan('guest-documents:purge-expired')->assertSuccessful();
    expect(GuestDocument::withTrashed()->whereKey($document->id)->exists())->toBeFalse();   // 32 days: gone
});
