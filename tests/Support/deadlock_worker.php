<?php

/**
 * Deadlock-avoidance worker. Books a TWO-room reservation (one unit of room_type X + one unit of
 * room_type Y) in an order given by RACE_LINE_ORDER ('xy' or 'yx'). Two of these run concurrently
 * in OPPOSITE order: without AssignmentService's deterministic lock ordering they could deadlock
 * on the availability rows; with it, both serialize cleanly and succeed (distinct units).
 */

use App\Exceptions\Booking\BookingException;
use App\Services\AssignmentService;
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

$checkIn = (string) getenv('RACE_CHECK_IN');
$checkOut = (string) getenv('RACE_CHECK_OUT');

$lineX = [
    'room_type_id' => (string) getenv('RACE_ROOM_TYPE_X'),
    'unit_id' => (string) getenv('RACE_UNIT_X'),
    'check_in' => $checkIn, 'check_out' => $checkOut, 'price_minor' => 10000, 'currency' => 'EUR',
];
$lineY = [
    'room_type_id' => (string) getenv('RACE_ROOM_TYPE_Y'),
    'unit_id' => (string) getenv('RACE_UNIT_Y'),
    'check_in' => $checkIn, 'check_out' => $checkOut, 'price_minor' => 10000, 'currency' => 'EUR',
];

$lines = getenv('RACE_LINE_ORDER') === 'yx' ? [$lineY, $lineX] : [$lineX, $lineY];

try {
    $reservation = app(AssignmentService::class)->createBooking(
        [
            'property_id' => (string) getenv('RACE_PROPERTY_ID'), 'currency' => 'EUR',
            'check_in' => $checkIn, 'check_out' => $checkOut,
            'lead_guest_id' => getenv('RACE_GUEST_ID') ?: null, 'source' => 'direct',
        ],
        $lines,
    );

    echo json_encode(['status' => 'success', 'reservation_id' => $reservation->id, 'pid' => getmypid()]);
} catch (BookingException $e) {
    echo json_encode(['status' => 'rejected', 'reason' => $e->getMessage(), 'pid' => getmypid()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'pid' => getmypid()]);
}
