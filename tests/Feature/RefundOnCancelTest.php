<?php

use App\Models\Payment;
use App\Models\PropertyPolicy;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Services\GuestDeletionService;
use App\Services\ReservationCancellationService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Reservations\CancellationPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// REFUND-ON-CANCEL (founder decision — "full auto-refund in-window only"). A cancellation WITHIN the property's
// free-cancel window fully refunds everything paid, through the certified bounded FolioService::refundPayment
// (#27, same-currency, TEST-MODE gateway); AFTER the window, NO auto-refund (the policy encodes no partial %, so
// it stays a manual operator decision — never fabricated, #39). Reconciles #17. Reused by the operator cancel +
// the guest account-delete sweep.

/** A property with a flexible free-until policy + a PAID reservation on 8–10 Sep (freeUntil = 6 Sep). */
function cancelRefundScenario(int $freeUntilDays = 2, int $paidMinor = 30000, int $chargeMinor = 30000): array
{
    config(['services.payments.gateway' => 'manual']);   // manual gateway → a real succeeded payment/refund
    $s = seedScenario(1);
    PropertyPolicy::create(['property_id' => $s['property']->id, 'cancellation' => ['type' => 'flexible', 'free_until_days' => $freeUntilDays]]);

    $res = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-08', 'check_out' => '2026-09-10', 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-08', 'check_out' => '2026-09-10', 'price_minor' => $chargeMinor, 'currency' => 'EUR']],
    );
    $folio = app(FolioService::class)->openFolio($res);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of($chargeMinor, 'EUR'));
    if ($paidMinor > 0) {
        app(FolioService::class)->recordPayment($folio, Money::of($paidMinor, 'EUR'), 'offline');
    }

    return ['s' => $s, 'res' => $res, 'folio' => $folio];
}

function cancelRefundTotal(string $folioId): int
{
    return (int) Payment::query()->where('folio_id', $folioId)->where('type', 'refund')->where('status', 'succeeded')->sum('amount_minor');
}

// ── The policy helper: within the window is refundable, after is not (the deadline is inclusive) ────
it('reads the free-cancel window from the real policy', function () {
    ['res' => $res] = cancelRefundScenario();
    expect(CancellationPolicy::freeUntil($res)->toDateString())->toBe('2026-09-06');   // check-in 09-08 − 2 days
    expect(CancellationPolicy::isRefundable($res, CarbonImmutable::parse('2026-09-06')))->toBeTrue();    // on the deadline
    expect(CancellationPolicy::isRefundable($res, CarbonImmutable::parse('2026-09-07')))->toBeFalse();   // after it
});

// ── IN-WINDOW → a FULL, real, test-mode refund; reconciles (#17) ────────────────────────────────────
it('fully refunds a cancellation inside the free-cancel window', function () {
    ['res' => $res, 'folio' => $folio] = cancelRefundScenario();   // €300 paid

    $result = app(ReservationCancellationService::class)->cancelWithPolicyRefund($res, 'Changed plans', CarbonImmutable::parse('2026-09-05'));

    expect($result['cancelled'])->toBeTrue()
        ->and($result['refunded_minor'])->toBe(30000)
        ->and((string) $res->fresh()->status)->toBe('cancelled')
        ->and(cancelRefundTotal($folio->id))->toBe(30000);   // a real succeeded refund on the folio

    // #17 — the refund undoes the payment: net paid → 0, balance back to the charges.
    $fresh = $folio->fresh()->load(['items', 'payments']);
    expect((int) FolioTotals::for($fresh)['balance_minor'])->toBe(30000);
});

// ── AFTER-WINDOW → NO auto-refund (a manual operator decision; nothing fabricated) ──────────────────
it('does not auto-refund a cancellation after the free-cancel window', function () {
    ['res' => $res, 'folio' => $folio] = cancelRefundScenario();

    $result = app(ReservationCancellationService::class)->cancelWithPolicyRefund($res, 'Changed plans', CarbonImmutable::parse('2026-09-07'));

    expect($result['refunded_minor'])->toBe(0)
        ->and((string) $res->fresh()->status)->toBe('cancelled')   // still cancelled…
        ->and(cancelRefundTotal($folio->id))->toBe(0);                  // …but nothing auto-refunded
});

// ── BOUNDED (#27) — never refunds more than was actually paid ───────────────────────────────────────
it('refunds only what was actually paid, never the full charge', function () {
    ['res' => $res, 'folio' => $folio] = cancelRefundScenario(paidMinor: 10000, chargeMinor: 30000);   // €300 charged, only €100 paid

    $result = app(ReservationCancellationService::class)->cancelWithPolicyRefund($res, null, CarbonImmutable::parse('2026-09-05'));

    expect($result['refunded_minor'])->toBe(10000)   // only the €100 paid, never the €300 charge
        ->and(cancelRefundTotal($folio->id))->toBe(10000);
});

// ── The OPERATOR cancel route auto-refunds in-window + flashes the amount ───────────────────────────
it('auto-refunds through the operator cancel route within the window', function () {
    ['s' => $s, 'res' => $res, 'folio' => $folio] = cancelRefundScenario();
    $operator = operatorUser($s, [], 'Hotel Manager');

    $this->travelTo(CarbonImmutable::parse('2026-09-05'));   // inside the free window
    $this->actingAs($operator)->post(route('app.reservations.cancel', $res->id), ['reason' => 'Changed plans'])->assertRedirect();
    $this->travelBack();

    expect((string) $res->fresh()->status)->toBe('cancelled')
        ->and(cancelRefundTotal($folio->id))->toBe(30000);
});

// ── The guest account-delete sweep refunds an in-window cancellation too ────────────────────────────
it('refunds an in-window reservation when the guest account-delete sweep runs', function () {
    ['s' => $s, 'res' => $res, 'folio' => $folio] = cancelRefundScenario();
    $guest = $s['guest'];
    $guest->forceFill(['scheduled_deletion_at' => now()])->save();

    $this->travelTo(CarbonImmutable::parse('2026-09-05'));   // inside the free window
    app(GuestDeletionService::class)->processDue();
    $this->travelBack();

    expect((string) $res->fresh()->status)->toBe('cancelled')
        ->and(cancelRefundTotal($folio->id))->toBe(30000)
        ->and($guest->fresh()->anonymized_at)->not->toBeNull();   // the erase still ran
});
