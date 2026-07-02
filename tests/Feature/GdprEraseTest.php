<?php

use App\Models\Guest;
use App\Models\GuestDocument;
use App\Models\Media;
use App\Models\Property;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Obligation 9 — anonymize() nulls PII, sets anonymized_at, PRESERVES the row + id + financial
// history, AND purges the actual stored file bytes of ID documents/media.

it('erases guest PII and stored file bytes while preserving the row and financial history', function () {
    Storage::fake('local');
    newDemoOrg();

    $property = Property::create(['name' => 'H', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $guest = Guest::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.test', 'phone' => '+15551230000']);
    $guestId = $guest->id;

    // Financial history that must survive: a reservation referencing the guest.
    $reservation = Reservation::create([
        'property_id' => $property->id, 'reference' => 'R-GDPR-1', 'source' => 'direct', 'status' => 'confirmed',
        'check_in' => '2026-09-01', 'check_out' => '2026-09-02', 'currency' => 'EUR', 'lead_guest_id' => $guest->id,
    ]);

    // A sensitive ID document with real bytes on the (fake) disk.
    $path = 'guests/'.$guestId.'/passport.jpg';
    Storage::disk('local')->put($path, 'FAKE-PASSPORT-BYTES');
    $media = Media::create(['mediable_type' => 'guest', 'mediable_id' => $guestId, 'disk' => 'local', 'path' => $path, 'mime' => 'image/jpeg', 'size' => 18]);
    $document = $guest->documents()->create(['media_id' => $media->id, 'category' => 'passport', 'signed_url_required' => true]);
    $guestDocument = GuestDocument::create(['guest_id' => $guestId, 'type' => 'passport', 'document_id' => $document->id]);

    expect(Storage::disk('local')->exists($path))->toBeTrue();

    $guest->anonymize();
    $guest->refresh();

    expect($guest->getKey())->toBe($guestId)                        // id preserved
        ->and(Guest::whereKey($guestId)->exists())->toBeTrue()      // row preserved (not deleted)
        ->and($guest->first_name)->toBeNull()
        ->and($guest->last_name)->toBeNull()
        ->and($guest->email)->toBeNull()
        ->and($guest->phone)->toBeNull()
        ->and($guest->anonymized_at)->not->toBeNull()
        ->and($reservation->refresh()->lead_guest_id)->toBe($guestId) // financial history intact
        ->and(Storage::disk('local')->exists($path))->toBeFalse()     // stored BYTES purged
        ->and(GuestDocument::withTrashed()->whereKey($guestDocument->id)->exists())->toBeFalse()
        ->and(Media::withTrashed()->whereKey($media->id)->exists())->toBeFalse();
});
