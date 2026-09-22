<?php

/**
 * F-53 concurrency worker. A standalone process — its OWN MySQL connection, which is the only way to
 * exercise the InnoDB row-lock for real — that fires ONE payout reversal at a shared future timestamp.
 * Prints one JSON line.
 *
 * ⚠️ WHY A SEPARATE PROCESS AND NOT TWO CALLS IN ONE TEST: a sequential double-call proves the re-check
 * reads committed state; it does NOT prove two transactions racing inside the lock window resolve to one
 * winner. 🔬 Measured 2026-09-23: removing `lockForUpdate` from the repaired reversal left the sequential
 * tests GREEN — the lock was not under test at all until this worker existed.
 *
 * ⚠️ It drives the PROTECTED `performReversal()` through the same test-only subclass the repair suite uses,
 * because `reverse()` is quarantined (R-REVERSE, open). The quarantine is not lifted here or anywhere.
 */

use App\Models\Payout;
use App\Services\OwnerPayoutService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

app(TenantContext::class)->set((string) getenv('RACE_ORG_ID'));

/** ⚠️ TEST-ONLY, and resolved THROUGH THE CONTAINER so the real constructor dependencies are injected. */
class RaceReversalDriver extends OwnerPayoutService
{
    public function drive(Payout $paid, string $reason): Payout
    {
        return $this->performReversal($paid, $reason);
    }
}

$driver = app()->make(RaceReversalDriver::class);

$startAt = (float) getenv('RACE_START_AT');
$now = microtime(true);
if ($startAt > $now) {
    usleep((int) (($startAt - $now) * 1_000_000));
}

try {
    $paid = Payout::query()->withoutGlobalScopes()->findOrFail((string) getenv('RACE_PAYOUT_ID'));
    $driver->drive($paid, 'race probe');
    echo json_encode(['status' => 'reversed'])."\n";
} catch (Throwable $e) {
    echo json_encode(['status' => 'refused', 'message' => substr($e->getMessage(), 0, 140)])."\n";
}
