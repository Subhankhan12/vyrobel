<?php

/**
 * Concurrency worker for the CUMULATIVE-REFUND-BOUND test (invariant #27).
 *
 * A standalone process (its own MySQL connection) that attempts ONE refund through
 * FolioService::refundPayment() and prints a single JSON line. Two of these are launched
 * simultaneously against the SAME payment for the SAME full amount, to prove the bound holds
 * under real OS-level parallelism.
 *
 * WHY A SEPARATE PROCESS: the fix is a MySQL row lock (SELECT … FOR UPDATE). A lock is only
 * observable across SEPARATE connections with COMMITTED data — two sequential in-process calls,
 * or an injected barrier on one connection, would pass even against the ORIGINAL unguarded code.
 * This mirrors tests/Support/booking_race_worker.php, the harness that proves no-double-booking.
 *
 * DB is overridden to vyrobel_test via real env vars (RACE_* + DB_DATABASE), which Laravel's
 * immutable .env loader will not overwrite; APP_KEY etc. still come from .env.
 */

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\Payment;
use App\Services\FolioService;
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
    $payment = Payment::query()->findOrFail((string) getenv('RACE_PAYMENT_ID'));
    $amountMinor = (int) getenv('RACE_REFUND_MINOR');
    $currency = (string) (getenv('RACE_CURRENCY') ?: 'EUR');

    $refund = app(FolioService::class)->refundPayment($payment, new Money($amountMinor, $currency));

    echo json_encode([
        'status' => 'success',
        'refund_id' => $refund->id,
        'amount_minor' => (int) $refund->amount_minor,
        'pid' => getmypid(),
    ]);
} catch (RefundExceedsPaymentException $e) {
    // The bound refused it — the ONLY acceptable outcome for the loser of the race.
    echo json_encode(['status' => 'rejected', 'reason' => $e->getMessage(), 'pid' => getmypid()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'pid' => getmypid()]);
}
