<?php

/**
 * Concurrency worker for the drag-to-reschedule test. A standalone process (its own MySQL
 * connection) that attempts ONE {@see AssignmentService::reschedule()} of a given
 * line onto a TARGET unit + nights, then prints a single JSON line. Two of these race onto the
 * SAME target unit/nights to prove the reschedule path keeps the no-double-booking guarantee.
 *
 * DB is overridden to vyrobel_test via real env vars (RACE_* + DB_DATABASE), which Laravel's
 * immutable .env loader will not overwrite; APP_KEY etc. still come from .env.
 */

use App\Exceptions\Booking\BookingException;
use App\Models\ReservationUnit;
use App\Services\AssignmentService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$orgId = (string) getenv('RACE_ORG_ID');
$startAt = (float) getenv('RACE_START_AT');

app(TenantContext::class)->set($orgId);

$line = ReservationUnit::query()->findOrFail((string) getenv('RACE_LINE_ID'));

// Barrier: align both processes to fire at the same wall-clock instant.
$now = microtime(true);
if ($startAt > $now) {
    usleep((int) (($startAt - $now) * 1_000_000));
}

try {
    $reservationUnit = app(AssignmentService::class)->reschedule(
        $line,
        (string) getenv('RACE_NEW_CHECK_IN'),
        (string) getenv('RACE_NEW_CHECK_OUT'),
        (string) getenv('RACE_TARGET_UNIT_ID'),
    );

    echo json_encode(['status' => 'success', 'line_id' => $reservationUnit->id, 'pid' => getmypid()]);
} catch (BookingException $e) {
    echo json_encode(['status' => 'rejected', 'reason' => $e->getMessage(), 'pid' => getmypid()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'pid' => getmypid()]);
}
