<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\Reservation;
use App\Services\BookingEngineService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-17 (audit of 2026-11-24) — ONE GUEST PER EMAIL IN AN ORGANISATION: A SECOND DIRECT BOOKING JOINS THE FIRST GUEST.
//
// Every booking from the public storefront created a NEW guest record, so a guest who booked twice with the same email had two
// records — and their portal account, which is one of those records, showed only its own booking.
//
// A public booking now attaches to the guest that already holds that email IN THIS ORGANISATION (found by the email's blind index,
// never the plaintext): the one with a portal account first, else the oldest. The existing record is never changed from the public
// form — a visitor who types someone's email cannot rename them. Another organisation's guest is never matched.

uses(DatabaseTransactions::class);

function vy17Book(array $s, string $email, string $checkIn, string $checkOut, array $guest = []): array
{
    $engine = app(BookingEngineService::class);
    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, $checkIn, $checkOut, 2, 0, $email);

    return $engine->book($quote, 'pm_card_visa', array_merge(['email' => $email, 'first_name' => 'Isolde', 'last_name' => 'Marchbanks'], $guest));
}

function vy17Guests(array $s): int
{
    return Guest::query()->where('organization_id', $s['org']->id)->count();
}

it('⚠️⚠️ VY-17: a second booking with the same email joins the SAME guest — one record, two bookings', function () {
    $s = publicPropertyScenario(units: 2);
    onboardOrgForCards($s);

    $first = vy17Book($s, 'isolde.vy17@guestmail.dev', '2026-09-01', '2026-09-03');
    $second = vy17Book($s, '  Isolde.VY17@guestmail.dev ', '2026-09-05', '2026-09-07');   // typed differently — the same address

    expect($first['status'])->toBe('confirmed')->and($second['status'])->toBe('confirmed')
        ->and($second['reservation']->lead_guest_id)->toBe($first['reservation']->lead_guest_id)
        ->and(vy17Guests($s))->toBe(1)
        ->and(Reservation::query()->where('lead_guest_id', $first['reservation']->lead_guest_id)->count())->toBe(2);
});

it('⚠️ VY-17: the guest with a PORTAL ACCOUNT is the one a new booking joins — so the booking shows in their account', function () {
    $s = publicPropertyScenario(units: 2);
    onboardOrgForCards($s);

    // two records already hold the email (the duplicates that exist today); the SECOND one is the claimed account
    $plain = Guest::create(['first_name' => 'Older', 'last_name' => 'Record', 'email' => 'twice.vy17@guestmail.dev']);
    $account = Guest::create(['first_name' => 'Claimed', 'last_name' => 'Account', 'email' => 'twice.vy17@guestmail.dev']);
    $account->forceFill(['password' => 'a-long-guest-password', 'email_verified_at' => now()])->save();

    $result = vy17Book($s, 'twice.vy17@guestmail.dev', '2026-09-01', '2026-09-03');

    expect($result['reservation']->lead_guest_id)->toBe($account->id)->not->toBe($plain->id)
        ->and(vy17Guests($s))->toBe(2);   // nobody new
});

it('⚠️ VY-17: the existing guest is NOT rewritten from the public form — a visitor cannot rename the person whose email they typed', function () {
    $s = publicPropertyScenario(units: 2);
    onboardOrgForCards($s);
    $existing = Guest::create(['first_name' => 'Rosalind', 'last_name' => 'Featherstone', 'email' => 'rosalind.vy17@guestmail.dev', 'language' => 'de']);

    app()->setLocale('en');
    $result = vy17Book($s, 'rosalind.vy17@guestmail.dev', '2026-09-01', '2026-09-03', ['first_name' => 'Mallory', 'last_name' => 'Impostor']);

    $after = Guest::query()->findOrFail($existing->id);
    expect($result['reservation']->lead_guest_id)->toBe($existing->id)
        ->and($after->first_name)->toBe('Rosalind')
        ->and($after->last_name)->toBe('Featherstone')
        ->and($after->language)->toBe('de');
});

it('⚠️ VY-17: an ERASED guest, and one whose erasure is scheduled, is never joined — a new record is made', function () {
    $s = publicPropertyScenario(units: 2);
    onboardOrgForCards($s);
    $scheduled = Guest::create(['first_name' => 'Going', 'last_name' => 'Away', 'email' => 'going.vy17@guestmail.dev']);
    $scheduled->forceFill(['scheduled_deletion_at' => now()->addDays(3)])->save();

    $result = vy17Book($s, 'going.vy17@guestmail.dev', '2026-09-01', '2026-09-03');

    expect($result['reservation']->lead_guest_id)->not->toBe($scheduled->id)->and(vy17Guests($s))->toBe(2);
});

it('⚠️⚠️ VY-17: another ORGANISATION\'S guest with the same email is never joined', function () {
    $other = publicPropertyScenario(units: 1);
    $foreign = Guest::create(['first_name' => 'Other', 'last_name' => 'Hotel', 'email' => 'shared.vy17@guestmail.dev']);

    $s = publicPropertyScenario(units: 1);   // a second organisation; the tenant context is now this one
    onboardOrgForCards($s);
    $result = vy17Book($s, 'shared.vy17@guestmail.dev', '2026-09-01', '2026-09-03');

    $lead = app(TenantContext::class)->runAsSuperAdmin(fn () => Guest::query()->findOrFail($result['reservation']->lead_guest_id));
    expect($lead->id)->not->toBe($foreign->id)
        ->and((string) $lead->organization_id)->toBe((string) $s['org']->id)
        ->and((string) $foreign->organization_id)->toBe((string) $other['org']->id);
});

it('VY-17 CONTROL: a different email still makes its own guest, and a refused booking never deletes the guest it joined', function () {
    $s = publicPropertyScenario(units: 1);   // ONE unit
    onboardOrgForCards($s);

    $first = vy17Book($s, 'keeper.vy17@guestmail.dev', '2026-09-01', '2026-09-03');
    $other = vy17Book($s, 'someone.else.vy17@guestmail.dev', '2026-09-05', '2026-09-07');
    expect($other['reservation']->lead_guest_id)->not->toBe($first['reservation']->lead_guest_id)->and(vy17Guests($s))->toBe(2);

    // the same guest tries the nights that are already taken: refused — and their record (which holds a real booking) is still there
    $refused = vy17Book($s, 'keeper.vy17@guestmail.dev', '2026-09-01', '2026-09-03');
    expect($refused['status'])->toBe('unavailable')
        ->and(Guest::query()->whereKey($first['reservation']->lead_guest_id)->exists())->toBeTrue()
        ->and(vy17Guests($s))->toBe(2);
});
