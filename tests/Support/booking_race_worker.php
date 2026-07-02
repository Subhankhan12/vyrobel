<?php

/**
 * Concurrency worker for the double-booking test. A standalone process (its own MySQL
 * connection) that attempts ONE booking through AssignmentService and prints a single JSON
 * line. Two of these are launched simultaneously against the same unit/nights to prove the
 * no-double-booking guarantee under real OS-level parallelism.
 *
 * DB is overridden to vyrobel_test via a real env var (RACE_* + DB_DATABASE), which Laravel's
 * immutable .env loader will not overwrite; APP_KEY etc. still come from .env.
 */

use App\Exceptions\Booking\BookingException;
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

// Barrier: align both processes to fire at the same wall-clock instant.
$now = microtime(true);
if ($startAt > $now) {
    usleep((int) (($startAt - $now) * 1_000_000));
}

try {
    $checkIn = (string) getenv('RACE_CHECK_IN');
    $checkOut = (string) getenv('RACE_CHECK_OUT');
    $currency = (string) (getenv('RACE_CURRENCY') ?: 'EUR');

    $reservation = app(AssignmentService::class)->createBooking(
        [
            'property_id' => (string) getenv('RACE_PROPERTY_ID'),
            'currency' => $currency,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'lead_guest_id' => getenv('RACE_GUEST_ID') ?: null,
            'source' => 'direct',
            'status' => 'confirmed',
        ],
        [[
            'room_type_id' => (string) getenv('RACE_ROOM_TYPE_ID'),
            'unit_id' => (string) getenv('RACE_UNIT_ID'),
            'rate_plan_id' => getenv('RACE_RATE_PLAN_ID') ?: null,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'price_minor' => 10000,
            'currency' => $currency,
        ]],
    );

    echo json_encode(['status' => 'success', 'reservation_id' => $reservation->id, 'pid' => getmypid()]);
} catch (BookingException $e) {
    echo json_encode(['status' => 'rejected', 'reason' => $e->getMessage(), 'pid' => getmypid()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'pid' => getmypid()]);
}
