<?php

use App\Support\Money\Money;

// HARD INVARIANT (Phase 3 · Module 1 Step 2): Money::allocate() distributes an amount into parts
// that ALWAYS sum back to the original — no minor unit created or lost — for every awkward
// amount/weight combination. Owner commission/payout splits depend on this holding exactly.

it('allocates parts that sum back to the original exactly', function (int $amount, array $weights) {
    $parts = Money::of($amount, 'EUR')->allocate($weights);

    $sum = array_reduce($parts, fn (int $c, Money $m): int => $c + $m->amountMinor(), 0);

    expect($sum)->toBe($amount)
        ->and(count($parts))->toBe(count($weights));
})->with([
    'classic 100 / 3' => [100, [1, 1, 1]],
    '10000 / 3' => [10000, [1, 1, 1]],
    '1 cent / 3' => [1, [1, 1, 1]],
    '2 cents / 3' => [2, [1, 1, 1]],
    '15% commission split' => [12345, [1500, 8500]],   // basis points
    'odd commission 17.5%' => [99999, [1750, 8250]],
    'seven uneven parts' => [1000, [3, 1, 4, 1, 5, 9, 2]],
    'zero amount' => [0, [1, 1, 1]],
    'single part' => [777, [1]],
    'lopsided weights' => [101, [1, 1000000]],
    'many equal nights' => [5000, [1, 1, 1, 1, 1, 1, 1]],
]);

it('preserves sign for negative totals (reversals)', function () {
    $parts = Money::of(-100, 'EUR')->allocate([1, 1, 1]);
    $sum = array_reduce($parts, fn (int $c, Money $m): int => $c + $m->amountMinor(), 0);

    expect($sum)->toBe(-100)
        ->and($parts[0]->amountMinor())->toBeLessThanOrEqual(0);
});

it('fuzz: allocate always sums to the whole across random amounts and weights', function () {
    mt_srand(20260703); // deterministic

    for ($t = 0; $t < 2000; $t++) {
        $amount = mt_rand(-500000, 500000);
        $n = mt_rand(1, 8);
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $weights[] = mt_rand(0, 50);
        }
        if (array_sum($weights) === 0) {
            $weights[0] = 1; // allocate requires at least one positive weight
        }

        $parts = Money::of($amount, 'EUR')->allocate($weights);
        $sum = array_reduce($parts, fn (int $c, Money $m): int => $c + $m->amountMinor(), 0);

        expect($sum)->toBe($amount);
    }
});

it('percentage rounds half-up to the nearest minor unit', function () {
    expect(Money::of(10000, 'EUR')->percentage(15)->amountMinor())->toBe(1500)
        ->and(Money::of(12345, 'EUR')->percentage(15)->amountMinor())->toBe(1852)   // 1851.75 -> 1852
        ->and(Money::of(100, 'EUR')->percentage(0)->amountMinor())->toBe(0);
});

it('rejects negative or all-zero weights', function () {
    expect(fn () => Money::of(100, 'EUR')->allocate([1, -1]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::of(100, 'EUR')->allocate([0, 0]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Money::of(100, 'EUR')->allocate([]))->toThrow(InvalidArgumentException::class);
});
