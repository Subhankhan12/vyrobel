<?php

/**
 * Cross-system concurrency worker (Phase 2 Step 5). A standalone process (its own MySQL
 * connection) that fires ONE booking attempt at a shared future timestamp, so two of these race
 * for real over committed data — the only way to exercise the InnoDB row-lock guarantee across
 * the direct and OTA write paths. Prints one JSON line.
 *
 * RACE_MODE:
 *   - direct       → AssignmentService::createBooking (targets RACE_UNIT_ID) → success | rejected
 *   - ota          → AssignmentService::importBooking (type-assigned)        → seated | overbooked
 *   - import_dup   → BookingImportService::import (fixed ota_reservation_id)  → imported (dedup-safe)
 *
 * QUEUE_CONNECTION=sync is forced by the test env so the post-commit ARI-push enqueue runs inline
 * and self-contained (no dependency on a running queue worker); it never affects the booking that
 * already committed.
 */

use App\Exceptions\Booking\BookingException;
use App\Models\Channel;
use App\Services\AssignmentService;
use App\Services\BookingImportService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$orgId = (string) getenv('RACE_ORG_ID');
app(TenantContext::class)->set($orgId);

$startAt = (float) getenv('RACE_START_AT');
$now = microtime(true);
if ($startAt > $now) {
    usleep((int) (($startAt - $now) * 1_000_000));
}

$mode = (string) getenv('RACE_MODE');
$checkIn = (string) getenv('RACE_CHECK_IN');
$checkOut = (string) getenv('RACE_CHECK_OUT');
$roomTypeId = (string) getenv('RACE_ROOM_TYPE_ID');
$currency = 'EUR';

try {
    if ($mode === 'direct') {
        $reservation = app(AssignmentService::class)->createBooking(
            [
                'property_id' => (string) getenv('RACE_PROPERTY_ID'),
                'currency' => $currency,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'lead_guest_id' => getenv('RACE_GUEST_ID') ?: null,
                'source' => 'direct',
            ],
            [[
                'room_type_id' => $roomTypeId,
                'unit_id' => getenv('RACE_UNIT_ID') ?: null,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'price_minor' => 10000,
                'currency' => $currency,
            ]],
        );

        echo json_encode(['status' => 'success', 'reservation_id' => $reservation->id, 'pid' => getmypid()]);
    } elseif ($mode === 'ota') {
        $reservation = app(AssignmentService::class)->importBooking(
            [
                'property_id' => (string) getenv('RACE_PROPERTY_ID'),
                'channel_id' => (string) getenv('RACE_CHANNEL_ID'),
                'ota_reservation_id' => (string) getenv('RACE_OTA_ID'),
                'source' => 'ota',
                'currency' => $currency,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'lead_guest_id' => getenv('RACE_GUEST_ID') ?: null,
            ],
            [[
                'room_type_id' => $roomTypeId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'price_minor' => 10000,
                'currency' => $currency,
            ]],
        );

        $line = $reservation->reservationUnits()->first();
        echo json_encode([
            'status' => $line !== null && $line->status === 'overbooked' ? 'overbooked' : 'seated',
            'reservation_id' => $reservation->id,
            'pid' => getmypid(),
        ]);
    } elseif ($mode === 'import_dup') {
        $channel = Channel::query()->findOrFail((string) getenv('RACE_CHANNEL_ID'));
        $reservation = app(BookingImportService::class)->import($channel, [
            'ota_reservation_id' => (string) getenv('RACE_OTA_ID'),
            'currency' => $currency,
            'total_minor' => 10000,
            'customer' => ['ota_guest_id' => 'race-guest', 'first_name' => 'Race', 'last_name' => 'Guest'],
            'rooms' => [[
                'external_room_type_id' => (string) getenv('RACE_EXT_ROOM_TYPE'),
                'external_rate_plan_id' => (string) getenv('RACE_EXT_RATE_PLAN'),
                'checkin_date' => $checkIn,
                'checkout_date' => $checkOut,
                'occupancy' => ['adults' => 2, 'children' => 0],
                'amount_minor' => 10000,
            ]],
        ]);

        echo json_encode(['status' => 'imported', 'reservation_id' => $reservation->id, 'pid' => getmypid()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "unknown RACE_MODE [{$mode}]", 'pid' => getmypid()]);
    }
} catch (BookingException $e) {
    echo json_encode(['status' => 'rejected', 'reason' => $e->getMessage(), 'pid' => getmypid()]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'pid' => getmypid()]);
}
