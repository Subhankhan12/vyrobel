<?php

/**
 * Concurrency worker for the GUEST WALLET no-double-spend test (wireframe-parity WAVE 2 · S1).
 *
 * A standalone process (its own MySQL connection) that attempts ONE wallet action and prints a single
 * JSON line. Two are launched simultaneously against the SAME guest to prove the derived-balance bound
 * holds under real OS-level parallelism.
 *
 * RACE_MODE selects which path is raced:
 *   apply  — GuestWalletService::applyToBooking() (spend against a booking)
 *   redeem — GuestWalletService::redeemCode()     (once-per-guest / max_redemptions)
 *
 * WHY A SEPARATE PROCESS: the fix is a MySQL row lock (SELECT … FOR UPDATE) plus a UNIQUE
 * idempotency_key. A lock is only observable across SEPARATE connections with COMMITTED data — two
 * sequential in-process calls would pass even against the ORIGINAL unguarded code. Mirrors
 * tests/Support/refund_race_worker.php and booking_race_worker.php.
 */

use App\Exceptions\Guest\WalletOverdrawException;
use App\Models\Guest;
use App\Models\Reservation;
use App\Services\GuestWalletService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$orgId = (string) getenv('RACE_ORG_ID');
$startAt = (float) getenv('RACE_START_AT');

app(TenantContext::class)->set($orgId);

// Barrier: align both processes to fire at the same wall-clock instant.
$now = microtime(true);
if ($startAt > $now) {
    usleep((int) (($startAt - $now) * 1_000_000));
}

try {
    $guest = Guest::query()->findOrFail((string) getenv('RACE_GUEST_ID'));
    $wallet = app(GuestWalletService::class);

    if ((string) getenv('RACE_MODE') === 'redeem') {
        $result = $wallet->redeemCode($guest, (string) getenv('RACE_PROMO_CODE'));

        echo json_encode([
            'status' => $result['ok'] ? 'success' : 'rejected',
            'reason' => $result['message'],
            'entry_id' => $result['entry']?->id,
            'pid' => getmypid(),
        ]);
        exit;
    }

    $reservation = Reservation::query()->findOrFail((string) getenv('RACE_RESERVATION_ID'));
    $amountMinor = (int) getenv('RACE_AMOUNT_MINOR');
    $currency = (string) (getenv('RACE_CURRENCY') ?: 'EUR');

    $entry = $wallet->applyToBooking($guest, $reservation, new Money($amountMinor, $currency));

    echo json_encode([
        'status' => 'success',
        'entry_id' => $entry->id,
        'amount_minor' => (int) $entry->amount_minor,
        'pid' => getmypid(),
    ]);
} catch (WalletOverdrawException $e) {
    // The bound refused it — the ONLY acceptable outcome for the loser of the race.
    echo json_encode(['status' => 'rejected', 'reason' => $e->getMessage(), 'pid' => getmypid()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'pid' => getmypid()]);
}
