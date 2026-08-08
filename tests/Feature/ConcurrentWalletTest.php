<?php

use App\Exceptions\Guest\WalletOverdrawException;
use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\GuestCreditEntry;
use App\Models\Organization;
use App\Models\PromoCode;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\FolioService;
use App\Services\GuestWalletService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
 * THE GUEST WALLET UNDER TRUE CONCURRENCY (wireframe-parity sweep · WAVE 2 · S1).
 *
 * THE BUG: GuestWalletService did check-then-act with NO transaction and NO lock. applyToBooking() read the
 * folio balance owed and the derived spendable balance BEFORE opening its transaction; redeemCode() counted
 * redemptions with no transaction at all. Two concurrent requests each saw the pre-spend state, both passed,
 * and both posted — spending the same credit twice, driving the derived balance NEGATIVE, over-crediting the
 * folio, and blowing past a promo's max_redemptions. The table's UNIQUE idempotency_key existed and no debit
 * path ever set it.
 *
 * This is the SAME defect class as the €369 double-refund (audit H1, fixed in e4775ec) and for the same
 * reason: an append-only ledger bounds EDITS, never the COUNT of appends. WalletGuardTest already locked the
 * SEQUENTIAL bound and stayed green throughout, because a sequential second call always sees the first one's
 * committed row. Only real parallelism exposes it.
 *
 * THE FIX mirrors FolioService::refundPayment(): a transaction that ROW-LOCKS (the guest row for the wallet,
 * the promo row for the redemption cap), RE-READS the bound inside the lock, and writes a deterministic
 * idempotency key so the UNIQUE index is the backstop.
 *
 * NOTE: this file intentionally does NOT use RefreshDatabase — a row lock is only observable across SEPARATE
 * connections with COMMITTED data. It seeds real committed rows, races two independent OS processes, and
 * cleans up after itself. Same discipline as ConcurrentRefundTest / ConcurrentBookingTest.
 */

/**
 * A guest holding $creditMinor of wallet credit, with a real unpaid booking of $owedMinor. Committed.
 *
 * @return array{0: Organization, 1: Guest, 2: Reservation}
 */
function seedWalletRaceScenario(int $creditMinor, int $owedMinor): array
{
    $tenant = app(TenantContext::class);

    $org = $tenant->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'Wallet Race Org', 'slug' => 'wallet-race-'.Str::random(10), 'default_currency' => 'EUR',
    ]));

    return $tenant->forOrganization($org->id, function () use ($org, $creditMinor, $owedMinor): array {
        $property = Property::create(['name' => 'Race Hotel', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
        $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Double', 'kind' => 'room', 'std_occupancy' => 2]);
        Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'R1']);
        RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);
        $guest = Guest::create(['first_name' => 'Wallet', 'last_name' => 'Racer']);

        app(AvailabilityService::class)->recompute($roomType->id, '2026-09-01', '2026-09-03');

        $reservation = Reservation::create([
            'reference' => 'W-'.Str::upper(Str::random(8)),
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
        $folios->postCharge($folio, 'room', 'Room', new Money($owedMinor, 'EUR'));

        // Real committed wallet credit through the certified path.
        app(GuestWalletService::class)->credit($guest, GuestCreditEntry::TYPE_ADJUSTMENT, 'Race seed', new Money($creditMinor, 'EUR'));

        return [$org, $guest->refresh(), $reservation->refresh()];
    });
}

function cleanupWalletRaceScenario(Organization $org): void
{
    app(TenantContext::class)->runAsSuperAdmin(function () use ($org): void {
        DomainEvent::query()->where('organization_id', $org->id)->delete();
        AuditEvent::query()->where('organization_id', $org->id)->delete();
        Organization::query()->whereKey($org->id)->forceDelete();
    });
}

/**
 * Race two independent OS processes through the worker and return their decoded results.
 *
 * @param  array<string, string>  $vars
 * @return array{0: mixed, 1: mixed, 2: string}
 */
function raceWalletWorkers(array $vars): array
{
    $env = array_merge(getenv(), $vars, [
        'RACE_START_AT' => (string) (microtime(true) + 2.0),   // both boot, then fire together
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'vyrobel_test',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
    ]);

    $worker = base_path('tests/Support/wallet_race_worker.php');
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

    return [
        json_decode(trim((string) $out1), true),
        json_decode(trim((string) $out2), true),
        "worker1 out={$out1} err={$err1}\nworker2 out={$out2} err={$err2}",
    ];
}

it('permits exactly one of two truly-concurrent wallet applies, and never spends the same credit twice', function () {
    Artisan::call('migrate', ['--force' => true]);

    $creditMinor = 5000;    // €50.00 of credit
    $owedMinor = 20000;     // €200.00 owed, so the FOLIO bound is not what refuses the loser
    [$org, $guest, $reservation] = seedWalletRaceScenario($creditMinor, $owedMinor);

    try {
        [$r1, $r2, $context] = raceWalletWorkers([
            'RACE_MODE' => 'apply',
            'RACE_ORG_ID' => $org->id,
            'RACE_GUEST_ID' => $guest->id,
            'RACE_RESERVATION_ID' => $reservation->id,
            'RACE_AMOUNT_MINOR' => (string) $creditMinor,   // BOTH try to spend the FULL balance
            'RACE_CURRENCY' => 'EUR',
        ]);

        expect($r1)->toBeArray($context);
        expect($r2)->toBeArray($context);

        $statuses = [$r1['status'] ?? 'parse_error', $r2['status'] ?? 'parse_error'];
        sort($statuses);

        // THE MAKE-OR-BREAK ASSERTION: exactly one apply succeeds, the other is cleanly REFUSED by the bound.
        //
        // Measured against the pre-fix code, this run produced ['error', 'success'] — the loser hit a MySQL
        // DEADLOCK (SQLSTATE 40001) racing the folio update, i.e. a 500 to the guest rather than an honest
        // refusal. The outcome there is timing-dependent (a deadlock aborted the second spend this time;
        // nothing in the code prevented it landing), which is exactly why it must not be left to chance.
        // Taking the guest lock FIRST gives a single, consistent lock order, so the loser now waits and is
        // then refused by the re-read bound. See the redemption race below for the unambiguous
        // double-write proof: pre-fix, BOTH workers returned 'success'.
        expect($statuses)->toBe(['rejected', 'success'], $context);

        app(TenantContext::class)->forOrganization($org->id, function () use ($guest, $reservation, $creditMinor, $owedMinor, $context): void {
            $wallet = app(GuestWalletService::class);

            // The refused attempt wrote NOTHING — the throw rolls its whole transaction back.
            $debits = GuestCreditEntry::query()->where('guest_id', $guest->getKey())
                ->where('direction', GuestCreditEntry::DIRECTION_DEBIT)->get();
            expect($debits)->toHaveCount(1, $context);

            // The derived balance lands at exactly 0 and NEVER goes negative — the signature of a double
            // spend would be −5000 here.
            $spendable = $wallet->spendableMinor($guest->fresh());
            expect($spendable)->toBe(0, $context);
            expect($spendable)->toBeGreaterThanOrEqual(0, $context);

            // #17: the folio was credited ONCE. Two applies would have left €100 of credit against €50 held.
            $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->firstOrFail();
            expect(FolioTotals::for($folio)['balance_minor'])->toBe($owedMinor - $creditMinor, $context);
        });
    } finally {
        cleanupWalletRaceScenario($org);
    }
});

it('permits exactly one of two truly-concurrent redemptions of a single-use promo code', function () {
    Artisan::call('migrate', ['--force' => true]);

    [$org, $guest, $reservation] = seedWalletRaceScenario(0, 20000);

    try {
        $code = 'RACE'.Str::upper(Str::random(6));

        app(TenantContext::class)->forOrganization($org->id, fn () => PromoCode::create([
            'code' => $code, 'amount_minor' => 2500, 'currency' => 'EUR',
            'max_redemptions' => 1, 'is_active' => true,
        ]));

        [$r1, $r2, $context] = raceWalletWorkers([
            'RACE_MODE' => 'redeem',
            'RACE_ORG_ID' => $org->id,
            'RACE_GUEST_ID' => $guest->id,
            'RACE_PROMO_CODE' => $code,
        ]);

        expect($r1)->toBeArray($context);
        expect($r2)->toBeArray($context);

        $statuses = [$r1['status'] ?? 'parse_error', $r2['status'] ?? 'parse_error'];
        sort($statuses);

        // Both succeeded against the pre-fix code: free money, twice, and max_redemptions blown past.
        expect($statuses)->toBe(['rejected', 'success'], $context);

        app(TenantContext::class)->forOrganization($org->id, function () use ($guest, $context): void {
            $credits = GuestCreditEntry::query()->where('guest_id', $guest->getKey())
                ->where('type', GuestCreditEntry::TYPE_PROMO)->get();

            expect($credits)->toHaveCount(1, $context);
            expect(app(GuestWalletService::class)->spendableMinor($guest->fresh()))->toBe(2500, $context);
        });
    } finally {
        cleanupWalletRaceScenario($org);
    }
});

it('collapses a replayed wallet apply to one debit when the caller supplies an idempotency key', function () {
    Artisan::call('migrate', ['--force' => true]);

    [$org, $guest, $reservation] = seedWalletRaceScenario(10000, 20000);   // €100 credit, €200 owed

    try {
        app(TenantContext::class)->forOrganization($org->id, function () use ($guest, $reservation): void {
            $wallet = app(GuestWalletService::class);
            $key = 'wallet-test-'.Str::random(12);

            $first = $wallet->applyToBooking($guest, $reservation, new Money(2500, 'EUR'), $key);
            $second = $wallet->applyToBooking($guest, $reservation, new Money(2500, 'EUR'), $key);

            // Same logical apply replayed → the ORIGINAL debit is returned, no second spend.
            expect($second->id)->toBe($first->id);
            expect(GuestCreditEntry::query()->where('guest_id', $guest->getKey())
                ->where('direction', GuestCreditEntry::DIRECTION_DEBIT)->count())->toBe(1);

            // …but a genuine SECOND apply (no key) is still allowed, because the BOUND — not the key — is what
            // protects the balance. Two €25 applies against €100 of credit are legitimate.
            $wallet->applyToBooking($guest->fresh(), $reservation, new Money(2500, 'EUR'));
            expect(GuestCreditEntry::query()->where('guest_id', $guest->getKey())
                ->where('direction', GuestCreditEntry::DIRECTION_DEBIT)->count())->toBe(2);
            expect($wallet->spendableMinor($guest->fresh()))->toBe(5000);
        });
    } finally {
        cleanupWalletRaceScenario($org);
    }
});

it('reverses a credit ONCE even when the balance guard cannot catch the second reversal', function () {
    Artisan::call('migrate', ['--force' => true]);

    // ⚠️ TWO credits, deliberately. With only one, reversing it drops the balance to 0 and the OVERDRAW guard
    // refuses a second reversal for the wrong reason — so the test would pass against the unfixed code and
    // guard nothing (the same non-discriminating trap that WAVE 1's HIGH-3 test fell into).
    // With a second credit still live, the balance guard CANNOT mask it: reversing A twice leaves
    // assertSpendable() satisfied both times, and the guest loses 8000 for a single 4000 credit.
    [$org, $guest] = seedWalletRaceScenario(4000, 20000);

    try {
        app(TenantContext::class)->forOrganization($org->id, function () use ($guest): void {
            $wallet = app(GuestWalletService::class);

            $creditA = GuestCreditEntry::query()->where('guest_id', $guest->getKey())
                ->where('direction', GuestCreditEntry::DIRECTION_CREDIT)->firstOrFail();
            $wallet->credit($guest, GuestCreditEntry::TYPE_ADJUSTMENT, 'Second credit', new Money(4000, 'EUR'));

            expect($wallet->spendableMinor($guest->fresh()))->toBe(8000);

            $wallet->reverse($creditA, 'Posted in error');
            expect($wallet->spendableMinor($guest->fresh()))->toBe(4000);

            // The re-reversal bound refuses it. Unfixed, assertSpendable(4000) passes against the remaining
            // 4000 and a SECOND debit posts, taking the balance to 0 — one credit reversed twice.
            expect(fn () => $wallet->reverse($creditA, 'Posted in error'))
                ->toThrow(WalletOverdrawException::class);

            expect($wallet->spendableMinor($guest->fresh()))->toBe(4000);
            expect(GuestCreditEntry::query()->where('reverses_id', $creditA->getKey())->count())->toBe(1);
        });
    } finally {
        cleanupWalletRaceScenario($org);
    }
});
