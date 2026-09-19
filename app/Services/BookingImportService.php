<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Middleware\ResolvePublicProperty;
use App\Models\Channel;
use App\Models\Guest;
use App\Models\Reservation;
use App\Support\Events\AuditEventRecorder;
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
 *
 * ⚠️⚠️ THIS SERVICE DELIBERATELY DOES NOT READ `organizations.status` — F-52, RULED BY THE FOUNDER
 * 2026-09-19. IF YOU CAME HERE TO ADD THAT CHECK, STOP AND READ THIS FIRST.
 *
 * ⚠️ IT LOOKS LIKE A GAP AND IT IS NOT. A suspended or cancelled organisation has its PUBLIC booking path
 * closed ({@see ResolvePublicProperty}, F-48/F-51) — so "a suspended tenant is still
 * taking bookings" reads exactly like the defect that guard was built to fix. The channel path is different
 * in kind, and the founder ruled it stays open.
 *
 * ⚠️⚠️ THE REASONING, BECAUSE IT IS THE WHOLE POINT: refusing this sync DOES NOT PREVENT THE SALE. The OTA
 * has already sold the room and the guest already holds a real reservation, out in the world, whatever this
 * service does. Dropping it here does not close a door — it only BLINDS THE PMS to a booking that exists,
 * which turns a billing dispute into a DOUBLE-BOOKING and a guest arriving at a room given to someone else.
 * A closed storefront stops new sales; a refused sync loses the record of one that already happened.
 *
 * ⚠️ AND A REFUSAL HERE COULD NOT BE A QUIET ONE: this runs on the retrying webhook path, where a throw
 * becomes `webhook_events` FAILED → retry → dead letter (FIX 14's ruling). The only safe shape would be a
 * recorded NO-OP — which would still lose the booking.
 *
 * ⚠️ Pinned by a test named for the decision, so removing this is a deliberate act rather than a tidy-up.
 */
class BookingImportService
{
    public function __construct(
        private AssignmentService $assignment,
        private ChannelMappingGuard $guard,
        private GuestMatcher $guests,
        private AuditEventRecorder $audit,
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
     * Apply an OTA modification. Unknown booking → treat it as an import; already-terminal booking →
     * safe no-op (recorded). Otherwise re-sync the reservation's lines (free/re-occupy correctly).
     * Redelivery idempotency comes from `webhook_events` (each external event processed once).
     *
     * @param  array<string, mixed>  $booking
     */
    public function modify(Channel $channel, array $booking): Reservation
    {
        $reservation = $this->findExisting($channel, $this->otaReservationId($booking));

        if ($reservation === null) {
            return $this->import($channel, $booking);
        }

        // ⚠️ THE SAME RULE AS cancel(), THREE METHODS DOWN, AND FOR THE SAME REASON (F-29, 2026-09-13).
        // build() sets `'status' => 'confirmed'` UNCONDITIONALLY, and reimportBooking() does a raw
        // `$reservation->update($attributes)` — so an OTA modification of a terminal booking REVIVED it:
        // status back to `confirmed`, the occupying lines released and re-taken (INVENTORY), and
        // `total_minor` overwritten (MONEY — it is read on the confirmation email, the owner booking
        // detail, the guests CRM and the reservations list's SUM(total_minor)).
        //
        // ⚠️ A NO-OP, NOT A THROW — cancel()'s reasoning applies here unchanged and is the author's own:
        // this runs on the inbound webhook path, where ProcessChannexWebhookJob catches, marks the
        // webhook_event FAILED and RE-THROWS into the retry backbone (retry → backoff → dead_letter). A
        // terminal booking is an ordinary, expected redelivery, not an error, and turning it into one would
        // stop the channel's bookings arriving with nothing on any screen to explain why.
        // "Idempotent here; authoritative there" — reimportBooking() throws; this returns.
        //
        // ⚠️ SCOPE: TERMINAL_STATUSES only (cancelled / no_show / checked_out) — the shared definition, the
        // sibling's exact rule. `checked_in` is NOT terminal and is NOT refused here: whether a channel may
        // rewrite a stay the guest is physically in is a FOUNDER RULING, not this method's to make
        // (docs/STATUS-LADDERS.md, R6). That case remains open and is recorded, not silently decided.
        if (in_array((string) $reservation->status, AssignmentService::TERMINAL_STATUSES, true)) {
            // ⚠️ THE REFUSAL LEAVES A TRAIL, WHICH cancel()'s SILENT NO-OP DOES NOT. Nobody is watching this
            // path when it happens — there is no user, no screen and no request — so an unrecorded refusal
            // is unrecoverable after the fact. actor_type `system` is the honest value (the enum is
            // user/agent/system and a sync has no user); the CHANNEL is named so support can tell which
            // integration sent it. PII-SAFE: identifiers and states only, never a value from the OTA
            // payload — audit rows are rendered to operators (#28).
            //
            // ⚠️⚠️ BUT THIS ROW SURFACES ON NO SCREEN TODAY, AND THAT IS AN OPEN GAP, NOT A DESIGN. It is
            // queryable for support and forensics and nothing more: `ReservationDetail`'s timeline reads
            // `reservation_status_history` plus audit rows whose subject is a **Payment** (three payment
            // actions), so a reservation-subject row is filtered out; and no operator surface reads
            // `webhook_events` or `sync_jobs` at all. **A silent refusal is the F-13 shape** — reported by
            // FIX 14, deliberately not fixed here, because building a channel-activity surface is a
            // feature, not this guard.
            $this->audit->record('reservation.ota_modification_refused', $reservation, null, [
                'status' => (string) $reservation->status,
                'reason' => 'terminal',
                'channel_id' => (string) $channel->getKey(),
                'ota_reservation_id' => $this->otaReservationId($booking),
            ], actorType: 'system');

            return $reservation; // already terminal → idempotent no-op
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

        // The SHARED definition (invariant #29), not this path's own copy. It used to check only
        // ['cancelled','no_show'] — so an OTA cancelling a stay the guest had already COMPLETED
        // (`checked_out`) went straight through to the service and stamped `cancelled_at` onto it, while the
        // operator controller refused exactly that transition. Two rules, one concept, silently disagreeing.
        //
        // ⚠️ AND IT MUST STAY A NO-OP, NOT A THROW. This runs on the inbound webhook path: a failed job is
        // RETRIED, so letting a terminal booking reach the now-guarded service would turn an ordinary
        // duplicate delivery into a poison message. Idempotent here; authoritative there.
        if (in_array($reservation->status, AssignmentService::TERMINAL_STATUSES, true)) {
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
