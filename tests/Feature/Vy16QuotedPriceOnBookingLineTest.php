<?php

declare(strict_types=1);

use App\Models\FolioItem;
use App\Models\ReservationUnit;
use App\Services\BookingEngineService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-16 (audit of 2026-11-24, reproduced) — THE BOOKING LINE CARRIES THE QUOTED PRICE, THE SAME FIGURE THE FOLIO CHARGES.
//
// A rate changed between the quote and the booking. The folio charged the quoted 240.00 (the price the guest was shown), but the
// booking LINE stored the new 400.00 — it was re-priced at booking time — and the occupancy and revenue reports read the line. One
// booking, two prices.
//
// The quote now stores its line's price, and booking writes THAT on the line. A quote made before this existed has one line, so its
// line is the quote's own total. Nothing is re-priced at booking time any more.

uses(DatabaseTransactions::class);

function vy16Line(array $result): ReservationUnit
{
    return ReservationUnit::query()->where('reservation_id', $result['reservation']->id)->firstOrFail();
}

function vy16RoomCharge(array $result): int
{
    return (int) FolioItem::query()->where('folio_id', $result['folio']->id)->where('type', 'room')->sum('amount_minor');
}

it('⚠️⚠️ VY-16 (as reproduced): a rate change between quote and booking leaves the line and the folio on the SAME quoted price', function () {
    $s = publicPropertyScenario(units: 1, nightlyMinor: 12000);
    onboardOrgForCards($s);
    $engine = app(BookingEngineService::class);

    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'vy16@guestmail.dev');
    expect((int) $quote->total_minor)->toBe(24000);

    // the operator raises the rate after the guest was shown 240.00
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of(20000, 'EUR'));

    $result = $engine->book($quote->refresh(), 'pm_card_visa', ['email' => 'vy16@guestmail.dev']);

    expect($result['status'])->toBe('confirmed')
        ->and(vy16RoomCharge($result))->toBe(24000)              // what the guest was shown and is charged
        ->and((int) vy16Line($result)->price_minor)->toBe(24000); // and what every report reads — it was 40000
});

it('VY-16: a quote made BEFORE the line carried a price (no key) books at the quote\'s own total, not a re-price', function () {
    $s = publicPropertyScenario(units: 1, nightlyMinor: 12000);
    onboardOrgForCards($s);
    $engine = app(BookingEngineService::class);

    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'vy16.old@guestmail.dev');
    $requested = $quote->requested;
    unset($requested[0]['price_minor']);                       // an older quote's shape
    $quote->forceFill(['requested' => $requested])->save();

    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of(20000, 'EUR'));
    $result = $engine->book($quote->refresh(), 'pm_card_visa', ['email' => 'vy16.old@guestmail.dev']);

    expect((int) vy16Line($result)->price_minor)->toBe(24000)->and(vy16RoomCharge($result))->toBe(24000);
});

it('VY-16 CONTROL: with no rate change the line, the folio and the quote all read the published price', function () {
    $s = publicPropertyScenario(units: 1, nightlyMinor: 12000);
    onboardOrgForCards($s);
    $engine = app(BookingEngineService::class);

    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-04', 2, 0, 'vy16.c@guestmail.dev');
    $result = $engine->book($quote, 'pm_card_visa', ['email' => 'vy16.c@guestmail.dev']);

    expect((int) $quote->total_minor)->toBe(36000)
        ->and((int) vy16Line($result)->price_minor)->toBe(36000)
        ->and(vy16RoomCharge($result))->toBe(36000)
        ->and($quote->requested[0]['price_minor'])->toBe(36000);
});
