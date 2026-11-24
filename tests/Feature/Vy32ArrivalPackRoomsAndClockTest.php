<?php

declare(strict_types=1);

use App\Mail\PreArrivalMail;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Support\Guest\ArrivalPack;
use App\Support\Pdf\ArrivalPackDocument;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-32 (audit of 2026-11-24, read in code) — TWO OF ITS FIVE SMALL DEFECTS ARE THE DOOR CODE'S.
//
//   · EVERY ROOM — a stay of two rooms showed only the first room's door code: ArrivalPack read `reservationUnits->first()`. Each room's
//     code (and Wi-Fi and notes) now reaches all four surfaces — the Arrival page, the reservation page, the printable guide, the email.
//   · THE PROPERTY'S DAY — the screens asked ArrivalPack with the SERVER's date, so the release day followed UTC: a property ahead of UTC
//     released the code a calendar day late by its own clock, one behind UTC a day early. "Today" is now the property's own date
//     (ArrivalPack::today → BoardDate, the Calendar's and the Front Desk's rule). Both directions are pinned below.

uses(DatabaseTransactions::class);

/** A claimed guest with a 2026-09-05 → 09-08 stay. Two rooms (`$rooms = 2`) each with their own code. Nothing owed. */
function vy32Stay(int $rooms = 1, string $timezone = 'UTC'): array
{
    $s = seedScenario(2);
    $s['property']->update(['timezone' => $timezone]);
    $s['units'][0]->update(['name' => 'Room 101', 'door_code' => '1111#', 'wifi_ssid' => 'Net-101', 'wifi_password' => 'pass-101', 'arrival_notes' => 'First floor.']);
    $s['units'][1]->update(['name' => 'Room 102', 'door_code' => '2222#', 'wifi_ssid' => 'Net-102', 'wifi_password' => 'pass-102', 'arrival_notes' => 'Second floor.']);

    /** @var Guest $guest */
    $guest = $s['guest'];
    $guest->update(['email' => 'grace.vy32.'.uniqid().'@example.test', 'first_name' => 'Grace', 'last_name' => 'Hopper']);
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    $line = fn (int $i): array => ['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][$i]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 20000, 'currency' => 'EUR'];
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct', 'adults' => 2],
        $rooms === 2 ? [$line(0), $line(1)] : [$line(0)],
    );

    return $s + ['reservation' => $reservation, 'guestUser' => $guest->fresh()];
}

function vy32Loaded(Reservation $r): Reservation
{
    return Reservation::query()->with(['property', 'reservationUnits.unit', 'leadGuest'])->findOrFail($r->getKey());
}

it('⚠️⚠️ VY-32 (rooms, as read): a two-room stay\'s pack carries BOTH rooms\' codes, Wi-Fi and notes — not the first room\'s alone', function () {
    $c = vy32Stay(rooms: 2);

    $pack = ArrivalPack::for(vy32Loaded($c['reservation']), CarbonImmutable::parse('2026-09-04'));

    expect($pack['released'])->toBeTrue()
        ->and(array_column($pack['rooms'], 'label'))->toBe(['Room 101', 'Room 102'])
        ->and(array_column($pack['rooms'], 'door_code'))->toBe(['1111#', '2222#'])
        ->and(array_column($pack['rooms'], 'wifi_ssid'))->toBe(['Net-101', 'Net-102'])
        ->and(array_column($pack['rooms'], 'arrival_notes'))->toBe(['First floor.', 'Second floor.'])
        ->and($pack['door_code'])->toBe('1111#');   // the first room's, unchanged, for the one-room screens
});

it('⚠️ VY-32 (rooms): withheld means withheld for EVERY room — a second room\'s code never leaks before the first one\'s', function () {
    $c = vy32Stay(rooms: 2);

    $pack = ArrivalPack::for(vy32Loaded($c['reservation']), CarbonImmutable::parse('2026-08-20'));   // before the window

    expect($pack['released'])->toBeFalse()
        ->and(array_filter(array_column($pack['rooms'], 'door_code')))->toBe([])
        ->and(array_filter(array_column($pack['rooms'], 'wifi_password')))->toBe([]);
});

it('⚠️ VY-32 (rooms): all four surfaces show both codes — the Arrival page, the reservation page, the printable guide, the email', function () {
    $c = vy32Stay(rooms: 2);
    $this->travelTo('2026-09-04 10:00:00');

    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.arrival', $c['reservation']->getKey()))
        ->assertOk()->assertSee('1111#')->assertSee('2222#')->assertSee('Room 102');
    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.reservation', $c['reservation']->getKey()))
        ->assertOk()->assertSee('1111#')->assertSee('2222#');

    $pdf = ArrivalPackDocument::data(vy32Loaded($c['reservation']), CarbonImmutable::parse('2026-09-04'));
    $html = view('pdfs.arrival', $pdf)->render();
    expect($html)->toContain('1111#')->toContain('2222#')->toContain('Net-102');

    $mail = (new PreArrivalMail(vy32Loaded($c['reservation']), CarbonImmutable::parse('2026-09-03')))->render();
    expect($mail)->toContain('1111#')->toContain('2222#');
});

it('VY-32 (rooms) CONTROL: a one-room stay reads exactly as before — no room name heading the code', function () {
    $c = vy32Stay(rooms: 1);
    $this->travelTo('2026-09-04 10:00:00');

    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.arrival', $c['reservation']->getKey()))
        ->assertOk()->assertSee('1111#')->assertDontSee('2222#')->assertDontSee('Room 101');
});

it('⚠️⚠️ VY-32 (clock): a property AHEAD of UTC releases on ITS day — 01:00 in Auckland on 3 Sep is the window, though the server still reads 2 Sep', function () {
    $c = vy32Stay(timezone: 'Pacific/Auckland');
    $this->travelTo(CarbonImmutable::parse('2026-09-02 13:00:00', 'UTC'));   // Auckland: 3 Sep, 01:00 — two days before the 5th

    expect(ArrivalPack::today(Property::query()->findOrFail($c['property']->id))->toDateString())->toBe('2026-09-03');
    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.arrival', $c['reservation']->getKey()))
        ->assertOk()->assertSee('1111#');
});

it('⚠️⚠️ VY-32 (clock): a property BEHIND UTC does not release early — 20:00 in Los Angeles on 2 Sep is not the window, though the server reads 3 Sep', function () {
    $c = vy32Stay(timezone: 'America/Los_Angeles');
    $this->travelTo(CarbonImmutable::parse('2026-09-03 03:00:00', 'UTC'));   // Los Angeles: 2 Sep, 20:00

    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.arrival', $c['reservation']->getKey()))
        ->assertOk()->assertDontSee('1111#');
});

it('⚠️ VY-32 (HEIC): the ID photo accepts only what it lists — a HEIC is refused and "heic" is never named as accepted; the picker asks for the same three', function () {
    $c = vy32Stay();
    $this->travelTo('2026-09-03 10:00:00');
    $heic = \Illuminate\Http\UploadedFile::fake()->create('photo.heic', 40, 'image/heic');

    $this->actingAs($c['guestUser'], 'guest')->post(route('guest.checkin.submit', $c['reservation']->getKey()), ['id_type' => 'passport', 'id_photo' => $heic])
        ->assertSessionHasErrors('id_photo');
    $messages = implode(' ', session('errors')->get('id_photo'));
    expect(strtolower($messages))->not->toContain('heic');
    expect(\App\Http\Controllers\Guest\GuestCheckinController::ID_PHOTO_TYPES)->toBe(['jpeg', 'jpg', 'png', 'webp']);

    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.checkin', $c['reservation']->getKey()))
        ->assertOk()->assertSee('accept="image/jpeg,image/png,image/webp"', false);
});
