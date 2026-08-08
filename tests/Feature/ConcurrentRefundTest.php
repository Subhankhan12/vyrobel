<?php

use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
 * THE CUMULATIVE-REFUND BOUND UNDER TRUE CONCURRENCY (invariant #27).
 *
 * THE BUG (audit 2026-08-07 · H1 / L1-01 / F4-2): FolioService::refundPayment() enforced the bound with an
 * UNLOCKED check-then-act — it read the cumulative refunded total, checked it, called the gateway, and only
 * then opened a transaction to insert the row. Two concurrent refunds both read alreadyRefunded = 0, both
 * passed the bound, and both issued a gateway refund: a €369.00 payment could be refunded €738.00 — exactly
 * the over-refund #27 exists to forbid, re-opened through the concurrency door.
 *
 * RefundGuardTest already locks the SEQUENTIAL bound and stayed green throughout the bug, because a sequential
 * second call always sees the first one's committed row. Only real parallelism exposes it.
 *
 * THE FIX mirrors OwnerPayoutService::pay() — the sibling money-OUT path that already had the right shape:
 * a DB transaction that ROW-LOCKS the original payment and re-reads the cumulative total INSIDE the lock.
 *
 * NOTE: this file intentionally does NOT use RefreshDatabase — a row lock is only observable across SEPARATE
 * connections with COMMITTED data. It seeds real committed rows, races two independent OS processes, and
 * cleans up after itself. Same discipline as ConcurrentBookingTest.
 */

/**
 * A settled payment of $paidMinor on a real folio, created through the certified path and COMMITTED.
 *
 * @return array{0: Organization, 1: Payment}
 */
function seedRefundRaceScenario(int $paidMinor): array
{
    $tenant = app(TenantContext::class);

    $org = $tenant->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'Refund Race Org', 'slug' => 'refund-race-'.Str::random(10), 'default_currency' => 'EUR',
    ]));

    return $tenant->forOrganization($org->id, function () use ($org, $paidMinor): array {
        $property = Property::create(['name' => 'Race Hotel', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
        $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Double', 'kind' => 'room', 'std_occupancy' => 2]);
        $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'R1']);
        RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);
        $guest = Guest::create(['first_name' => 'Refund', 'last_name' => 'Racer']);

        app(AvailabilityService::class)->recompute($roomType->id, '2026-09-01', '2026-09-03');

        $reservation = Reservation::create([
            'reference' => 'R-'.Str::upper(Str::random(8)),
            'property_id' => $property->id,
            'lead_guest_id' => $guest->id,
            'currency' => 'EUR',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'source' => 'direct',
            'status' => 'confirmed',
        ]);

        $folios = app(FolioService::class);
        $folio = $folios->openFolio($reservation);
        $folios->postCharge($folio, 'room', 'Room', new Money($paidMinor, 'EUR'));
        $payment = $folios->recordPayment($folio, new Money($paidMinor, 'EUR'));

        return [$org, $payment];
    });
}

function cleanupRefundRaceScenario(Organization $org): void
{
    app(TenantContext::class)->runAsSuperAdmin(function () use ($org): void {
        DomainEvent::query()->where('organization_id', $org->id)->delete();
        AuditEvent::query()->where('organization_id', $org->id)->delete();
        Organization::query()->whereKey($org->id)->forceDelete();
    });
}

it('permits exactly one of two truly-concurrent FULL refunds of the same payment, and never exceeds the original', function () {
    Artisan::call('migrate', ['--force' => true]); // ensure schema regardless of test order

    $paidMinor = 36900; // €369.00 — the amount from the original live over-refund bug
    [$org, $payment] = seedRefundRaceScenario($paidMinor);

    try {
        $startAt = microtime(true) + 2.0; // both processes boot, then fire together

        $env = array_merge(getenv(), [
            'RACE_ORG_ID' => $org->id,
            'RACE_PAYMENT_ID' => $payment->id,
            'RACE_REFUND_MINOR' => (string) $paidMinor, // BOTH try to refund the FULL amount
            'RACE_CURRENCY' => 'EUR',
            'RACE_START_AT' => (string) $startAt,
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'vyrobel_test',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
        ]);

        $worker = base_path('tests/Support/refund_race_worker.php');
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $p1 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes1, base_path(), $env);
        $p2 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes2, base_path(), $env);

        expect(is_resource($p1) && is_resource($p2))->toBeTrue();

        $out1 = stream_get_contents($pipes1[1]);
        $err1 = stream_get_contents($pipes1[2]);
        $out2 = stream_get_contents($pipes2[1]);
        $err2 = stream_get_contents($pipes2[2]);

        foreach ([$pipes1, $pipes2] as $pipes) {
            fclose($pipes[1]);
            fclose($pipes[2]);
        }
        proc_close($p1);
        proc_close($p2);

        $r1 = json_decode(trim((string) $out1), true);
        $r2 = json_decode(trim((string) $out2), true);

        $context = "worker1 out={$out1} err={$err1}\nworker2 out={$out2} err={$err2}";
        expect($r1)->toBeArray($context);
        expect($r2)->toBeArray($context);

        $statuses = [$r1['status'] ?? 'parse_error', $r2['status'] ?? 'parse_error'];
        sort($statuses);

        // THE MAKE-OR-BREAK ASSERTION: exactly one refund succeeds, the other is cleanly REFUSED by the
        // bound. Against the pre-fix code both returned 'success'.
        expect($statuses)->toBe(['rejected', 'success'], $context);

        app(TenantContext::class)->forOrganization($org->id, function () use ($payment, $paidMinor, $context): void {
            $refunds = Payment::withTrashed()
                ->where('type', 'refund')
                ->where('metadata->refunds_payment_id', $payment->getKey())
                ->get();

            // The refused attempt wrote NOTHING — no partial row, not even a 'failed' one (the throw rolls
            // its whole transaction back). Append-only stays clean.
            expect($refunds)->toHaveCount(1, $context);

            $succeededTotal = (int) $refunds->where('status', 'succeeded')->sum('amount_minor');

            // #27: the cumulative total refunded may EQUAL but never EXCEED the original.
            expect($succeededTotal)->toBe($paidMinor, $context);
            expect($succeededTotal)->toBeLessThanOrEqual((int) $payment->amount_minor, $context);

            // #17: the folio still reconciles. recompute() does paid = received − refunded, so refunding the
            // full amount ONCE lands paid_minor at exactly 0 and balance back to the full charge. Had both
            // refunds landed, paid_minor would be NEGATIVE (−36900) and balance 73800 — the "negative-of-truth"
            // signature of the double refund. Asserting paid >= 0 pins that directly.
            $folio = $payment->fresh()->folio->fresh();
            expect((int) $folio->paid_minor)->toBe(0, $context);
            expect((int) $folio->paid_minor)->toBeGreaterThanOrEqual(0, $context);
            expect((int) $folio->balance_minor)->toBe((int) $folio->total_minor, $context);
        });
    } finally {
        cleanupRefundRaceScenario($org);
    }
});

it('collapses a replayed refund to one row when the caller supplies an idempotency key', function () {
    Artisan::call('migrate', ['--force' => true]);

    [$org, $payment] = seedRefundRaceScenario(10000); // €100.00

    try {
        app(TenantContext::class)->forOrganization($org->id, function () use ($payment): void {
            $folios = app(FolioService::class);
            $key = 'refund-test-'.Str::random(12);

            $first = $folios->refundPayment($payment->fresh(), new Money(2500, 'EUR'), null, $key);
            $second = $folios->refundPayment($payment->fresh(), new Money(2500, 'EUR'), null, $key);

            // Same logical refund replayed → the ORIGINAL row is returned, no second refund.
            expect($second->id)->toBe($first->id);
            expect(Payment::query()->where('type', 'refund')
                ->where('metadata->refunds_payment_id', $payment->getKey())->count())->toBe(1);

            // …but a genuine SECOND partial refund (no key) is still allowed, because the BOUND — not the
            // key — is what protects the total. Two €25 refunds against €100 are legitimate.
            $folios->refundPayment($payment->fresh(), new Money(2500, 'EUR'));
            expect(Payment::query()->where('type', 'refund')
                ->where('metadata->refunds_payment_id', $payment->getKey())->count())->toBe(2);
        });
    } finally {
        cleanupRefundRaceScenario($org);
    }
});
