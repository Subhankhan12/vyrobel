<?php

declare(strict_types=1);

use App\Models\AddOn;
use App\Models\GuestChangeRequest;
use App\Services\FolioService;
use App\Services\GuestChangeRequestService;
use App\Support\Guest\GuestPayments;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 3 (minor gaps), guest realm.
 */

/**
 * The pending add-on row rendered BLANK.
 *
 * GuestChangeRequestService writes `payload['add_on_ids']` and reads it back, but GuestManageController's
 * summary read `payload['add_ons']` — a key nothing in the codebase ever writes. So every pending add-on
 * request showed an empty description. Reading the right key alone would print raw ULIDs, so the names are
 * resolved (tenant-scoped, so a foreign id simply does not resolve).
 */
it('shows the real add-on names on a pending request instead of a blank row', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $breakfast = AddOn::create([
        'property_id' => $s['property']->id, 'name' => 'Breakfast',
        'price_minor' => 1800, 'currency' => 'EUR', 'is_active' => true,
    ]);

    app(GuestChangeRequestService::class)->requestAddOns($reservation, $s['guest'], [(string) $breakfast->id]);

    $request = GuestChangeRequest::query()->where('reservation_id', $reservation->id)->firstOrFail();

    // The service writes `add_on_ids` — the key the summary must read.
    expect($request->payload)->toHaveKey('add_on_ids')
        ->and($request->payload)->not->toHaveKey('add_ons');

    $html = $this->actingAs($s['guest'], 'guest')
        ->get(route('guest.manage', $reservation->id))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Breakfast')                       // the real name, not a blank cell
        ->and($html)->not->toContain((string) $breakfast->id);   // and never the raw ULID
});

/**
 * The Payments overview hid refunds.
 *
 * "Paid to date" is NET of refunds, so a refunded guest saw a smaller Paid figure with nothing explaining
 * it. FolioTotals already returned `refunded_minor` (added for the Statement of Account) — it simply was
 * never surfaced. Same single pass as `paid`, so the two cannot drift (#17).
 */
it('surfaces a real refunded total on the payments overview', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', new Money(20000, 'EUR'));
    $payment = $folios->recordPayment($folio, new Money(20000, 'EUR'));
    $folios->refundPayment($payment, new Money(5000, 'EUR'));

    $overview = GuestPayments::overview($s['guest']->fresh(), CarbonImmutable::today());

    // The refund is reported, and Paid is still the net figure it has always been.
    expect($overview['total_refunded_minor'])->toBe(5000)
        ->and($overview['total_refunded'])->toContain('50.00')
        ->and($overview)->toHaveKey('total_paid');
});

it('shows no refunded figure when nothing was refunded, rather than a noisy zero', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', new Money(20000, 'EUR'));
    $folios->recordPayment($folio, new Money(20000, 'EUR'));

    expect(GuestPayments::overview($s['guest']->fresh(), CarbonImmutable::today())['total_refunded_minor'])->toBe(0);
});

/**
 * The reservation page invented an arrival hour.
 *
 * The tiles fell back to "From 15:00" / "By 11:00" when the property had published no time — telling a guest
 * an hour the operator never set, while the Arrival page beside it correctly showed nothing (#39).
 */
it('never invents a check-in or check-out hour the property has not set', function () {
    $blade = file_get_contents(resource_path('views/guest/reservation.blade.php'));

    // The fabricated fallbacks are gone from the markup (the explanatory comment may still name them).
    expect($blade)->not->toContain("?? 'From 15:00'")
        ->and($blade)->not->toContain("?? 'By 11:00'")
        ->and($blade)->toContain("\$arrival['check_in_time'] !== null");
});
