<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;

/**
 * Orchestrates inbound OTA booking sync (Step 4): normalizes a Channex booking payload, reverse-
 * maps external room/rate ids to our entities (via the mapping guard — an unmapped external id
 * fails loudly, never a silent drop), matches-or-creates the guest, and hands off to
 * {@see AssignmentService} — the ONE inventory write path. This service NEVER writes
 * `reservation_units`/`availability` itself; it only builds attributes and delegates.
 *
 * Idempotency:
 *   - webhook level: `webhook_events` dedupes at-least-once redelivery (controller/job).
 *   - booking level: {@see import()} dedupes on (channel, ota_reservation_id) — the same booking
 *     imported twice yields ONE reservation.
 *
 * Guest matching is by external ref only (per channel); the blind-index match for encrypted
 * email/phone stays deferred to Phase 3.
 */
class BookingImportService
{
    public function __construct(
        private AssignmentService $assignment,
        private ChannelMappingGuard $guard,
        private GuestMatcher $guests,
    ) {}

    /**
     * Import a new OTA booking. Idempotent on (channel, ota_reservation_id).
     *
     * @param  array<string, mixed>  $booking
     */
    public function import(Channel $channel, array $booking): Reservation
    {
        $otaReservationId = $this->otaReservationId($booking);

        $existing = $this->findExisting($channel, $otaReservationId);
        if ($existing !== null) {
            return $existing; // already imported — exactly one reservation
        }

        [$attributes, $lines] = $this->build($channel, $booking);

        try {
            return $this->assignment->importBooking($attributes, $lines);
        } catch (UniqueConstraintViolationException) {
            // Two concurrent deliveries of the same booking both passed findExisting() and raced
            // the insert; the DB unique(channel_id, ota_reservation_id) caught the loser. Resolve
            // to the winner's row — still exactly one reservation, no error surfaced.
            return $this->findExisting($channel, $otaReservationId)
                ?? throw new InvalidArgumentException("Import race left no reservation for {$otaReservationId}.");
        }
    }

    /**
     * Apply an OTA modification. If we've never seen the booking, treat it as an import. Otherwise
     * re-sync the reservation's lines (free/re-occupy correctly). Redelivery idempotency comes
     * from `webhook_events` (each external event processed once).
     *
     * @param  array<string, mixed>  $booking
     */
    public function modify(Channel $channel, array $booking): Reservation
    {
        $reservation = $this->findExisting($channel, $this->otaReservationId($booking));

        if ($reservation === null) {
            return $this->import($channel, $booking);
        }

        [$attributes, $lines] = $this->build($channel, $booking);
        unset($attributes['reference']); // never rewrite the human reference on modify

        return $this->assignment->reimportBooking($reservation, $attributes, $lines);
    }

    /**
     * Apply an OTA cancellation. Unknown or already-terminal booking → safe no-op.
     *
     * @param  array<string, mixed>  $booking
     */
    public function cancel(Channel $channel, array $booking): ?Reservation
    {
        $reservation = $this->findExisting($channel, $this->otaReservationId($booking));

        if ($reservation === null) {
            return null; // never imported → nothing to cancel
        }

        if (in_array($reservation->status, ['cancelled', 'no_show'], true)) {
            return $reservation; // already terminal → idempotent no-op
        }

        return $this->assignment->cancelReservation($reservation, 'OTA cancellation');
    }

    private function findExisting(Channel $channel, string $otaReservationId): ?Reservation
    {
        return Reservation::query()
            ->where('channel_id', $channel->getKey())
            ->where('ota_reservation_id', $otaReservationId)
            ->first();
    }

    /**
     * Normalize a booking payload into [reservationAttributes, lines] for AssignmentService.
     *
     * @param  array<string, mixed>  $booking
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function build(Channel $channel, array $booking): array
    {
        $rooms = $booking['rooms'] ?? [];
        if (! is_array($rooms) || $rooms === []) {
            throw new InvalidArgumentException('OTA booking payload has no rooms.');
        }

        $currency = is_string($booking['currency'] ?? null) ? $booking['currency'] : 'EUR';
        $guest = $this->resolveGuest($channel, is_array($booking['customer'] ?? null) ? $booking['customer'] : []);

        $lines = [];
        $checkIns = [];
        $checkOuts = [];
        $totalAdults = 0;
        $totalChildren = 0;

        foreach ($rooms as $room) {
            $roomTypeMapping = $this->guard->resolveInboundRoomTypeMapping(
                $channel,
                (string) ($room['external_room_type_id'] ?? ''),
            );
            $ratePlanMapping = $this->guard->resolveInboundRatePlanMapping(
                $channel,
                isset($room['external_rate_plan_id']) ? (string) $room['external_rate_plan_id'] : null,
            );

            $checkIn = (string) $room['checkin_date'];
            $checkOut = (string) $room['checkout_date'];
            $adults = (int) ($room['occupancy']['adults'] ?? 1);
            $children = (int) ($room['occupancy']['children'] ?? 0);

            $checkIns[] = $checkIn;
            $checkOuts[] = $checkOut;
            $totalAdults += $adults;
            $totalChildren += $children;

            $lines[] = [
                'room_type_id' => $roomTypeMapping->room_type_id,
                'rate_plan_id' => $ratePlanMapping?->rate_plan_id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
                'children' => $children,
                'price_minor' => (int) ($room['amount_minor'] ?? 0),
                'currency' => $currency,
            ];
        }

        $attributes = [
            'property_id' => $channel->property_id,
            'channel_id' => $channel->getKey(),
            'ota_reservation_id' => $this->otaReservationId($booking),
            'source' => 'ota',
            'status' => 'confirmed',
            'currency' => $currency,
            'check_in' => min($checkIns),
            'check_out' => max($checkOuts),
            'adults' => max(1, $totalAdults),
            'children' => $totalChildren,
            'lead_guest_id' => $guest->getKey(),
            'total_minor' => (int) ($booking['total_minor'] ?? 0),
        ];

        return [$attributes, $lines];
    }

    /**
     * Match a guest by this channel's external ref, else create one. External refs are stored
     * per-channel: `external_refs = { "<channel_id>": "<ota_guest_id>" }`.
     *
     * @param  array<string, mixed>  $customer
     */
    private function resolveGuest(Channel $channel, array $customer): Guest
    {
        // The OTA guest id (opaque, non-PII) is the external ref key/value; the shared GuestMatcher
        // does the match-or-create (the SAME matcher the inbox reuses — no parallel matcher).
        $otaGuestId = isset($customer['ota_guest_id']) ? (string) $customer['ota_guest_id'] : null;

        return $this->guests->matchOrCreate($channel->getKey(), $otaGuestId, [
            'first_name' => is_string($customer['first_name'] ?? null) ? $customer['first_name'] : 'OTA',
            'last_name' => is_string($customer['last_name'] ?? null) ? $customer['last_name'] : 'Guest',
            'email' => is_string($customer['email'] ?? null) ? $customer['email'] : null,
            'phone' => is_string($customer['phone'] ?? null) ? $customer['phone'] : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $booking
     */
    private function otaReservationId(array $booking): string
    {
        $id = $booking['ota_reservation_id'] ?? null;

        if (! is_string($id) || $id === '') {
            throw new InvalidArgumentException('OTA booking payload is missing ota_reservation_id.');
        }

        return $id;
    }
}
