<?php

/**
 * R9 concurrency worker. A standalone process — its OWN MySQL connection, which is the only way to
 * exercise the InnoDB row-lock for real — that fires ONE reverse-check-in at a shared future timestamp.
 * Prints one JSON line.
 *
 * ⚠️ WHY A SEPARATE PROCESS AND NOT TWO CALLS IN ONE TEST: a sequential double-call proves the guard reads
 * committed state; it does NOT prove two transactions racing inside the lock window resolve to one winner.
 * Those are different claims and only this one is about concurrency.
 */

use App\Actions\Reservations\ReverseCheckIn;
use App\Models\Reservation;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

app(TenantContext::class)->set((string) getenv('RACE_ORG_ID'));

$startAt = (float) getenv('RACE_START_AT');
$now = microtime(true);
if ($startAt > $now) {
    usleep((int) (($startAt - $now) * 1_000_000));
}

try {
    $reservation = Reservation::query()->findOrFail((string) getenv('RACE_RESERVATION_ID'));
    app(ReverseCheckIn::class)->handle($reservation);
    echo json_encode(['status' => 'reversed'])."\n";
} catch (Throwable $e) {
    echo json_encode(['status' => 'refused', 'message' => substr($e->getMessage(), 0, 120)])."\n";
}
