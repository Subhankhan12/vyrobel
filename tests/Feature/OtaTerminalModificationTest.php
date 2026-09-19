<?php

use App\Exceptions\Booking\ReservationTerminalModificationException;
use App\Http\Middleware\ResolvePublicProperty;
use App\Models\AuditEvent;
use App\Models\ChannelMapping;
use App\Models\Organization;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\BookingImportService;

/**
 * F-29 — THE OTA WRITER THAT REVIVED A DEAD BOOKING (fix programme, 2026-09-13).
 *
 * `BookingImportService::build()` sets `'status' => 'confirmed'` unconditionally and `modify()` handed it to
 * `AssignmentService::reimportBooking()`, whose raw `$reservation->update($attributes)` rewrote a terminal
 * booking back to `confirmed` — re-taking inventory and overwriting `total_minor` on the way.
 *
 * ⚠️ PROVEN BY ROW STATE, NEVER BY A RETURN VALUE. Every probe re-reads the row from the database and
 * asserts the status, the money and the inventory did not move. A method that returns the reservation
 * unchanged and a method that silently rewrote it look identical from the outside.
 *
 * ⚠️ TWO LAYERS, BECAUSE A GUARD ON ONE ENTRANCE IS NOT A GUARD — that is precisely how F-18 and F-29 both
 * hid. `modify()` (the retrying webhook path) refuses QUIETLY; `reimportBooking()` (the raw writer, which
 * covers every caller present and future) THROWS. The split is the author's own, stated in `cancel()`:
 * "Idempotent here; authoritative there."
 */
beforeEach(function () {
    config(['queue.default' => 'database']);
    flushQueue();
});

afterEach(fn () => cleanupCommittedTenantData());

/**
 * A channel with active room-type + rate-plan mappings, so imports reverse-map cleanly.
 * Defined locally: BookingImportTest's namesake is private to that file and is not touched here.
 */
function otaMappedScenario(int $units = 2): array
{
    $ctx = channelScenario();
    for ($i = 2; $i <= $units; $i++) {
        Unit::create([
            'property_id' => $ctx['scenario']['property']->id,
            'room_type_id' => $ctx['scenario']['roomType']->id,
            'code' => 'U'.$i,
        ]);
    }
    app(AvailabilityService::class)->recompute($ctx['scenario']['roomType']->id, '2026-09-01', '2026-09-10');

    ChannelMapping::factory()->roomType($ctx['scenario']['roomType'])
        ->create(['channel_id' => $ctx['channel']->id, 'external_id' => 'chx-rt-1']);
    ChannelMapping::factory()->ratePlan($ctx['scenario']['ratePlan'])
        ->create(['channel_id' => $ctx['channel']->id, 'external_id' => 'chx-rp-1']);

    return $ctx;
}

/** Import a real OTA booking, then force it to $status without going through a guarded path. */
function otaBookingIn(string $status): array
{
    ['scenario' => $s, 'channel' => $channel] = otaMappedScenario(units: 2);
    $payload = channexBookingPayload();

    $reservation = app(BookingImportService::class)->import($channel, $payload['booking']);

    // Put the row into the terminal state directly: the point is to test the MODIFY guard, not to
    // re-test the termination guard that already bounds how a row legitimately gets here.
    Reservation::withoutEvents(fn () => $reservation->forceFill(['status' => $status])->saveQuietly());

    return [$channel, $reservation->refresh(), $payload['booking']];
}

dataset('terminal statuses', AssignmentService::TERMINAL_STATUSES);

it('refuses an OTA modification of a terminal booking, and the ROW does not move', function (string $status) {
    [$channel, $reservation, $booking] = otaBookingIn($status);

    $before = Reservation::query()->withoutGlobalScopes()->find($reservation->id)->getAttributes();
    $soldBefore = DB::table('availability')->sum('sold');
    $linesBefore = DB::table('reservation_units')->where('reservation_id', $reservation->id)
        ->orderBy('id')->get()->map(fn ($l) => (array) $l)->toArray();

    // A REAL modification payload: a changed price and changed dates, exactly what an OTA sends.
    $booking['total_minor'] = 99999;
    $booking['rooms'][0]['amount_minor'] = 99999;
    $booking['rooms'][0]['checkin_date'] = '2026-09-05';
    $booking['rooms'][0]['checkout_date'] = '2026-09-07';

    $returned = app(BookingImportService::class)->modify($channel, $booking);

    // ⚠️ NO EXCEPTION on the webhook path — a throw here becomes retry → dead_letter (a poison message).
    expect($returned->id)->toBe($reservation->id);

    $after = Reservation::query()->withoutGlobalScopes()->find($reservation->id)->getAttributes();

    // THE ROW, FIELD BY FIELD — status, money, dates, guest, everything.
    expect($after['status'])->toBe($status)
        ->and((int) $after['total_minor'])->toBe((int) $before['total_minor'])
        ->and((string) $after['check_in'])->toBe((string) $before['check_in'])
        ->and((string) $after['check_out'])->toBe((string) $before['check_out'])
        ->and($after['lead_guest_id'])->toBe($before['lead_guest_id'])
        ->and($after)->toEqual($before);   // nothing at all moved, including updated_at

    // INVENTORY — reimportBooking() releases then re-occupies, so a revival re-takes rooms.
    expect(DB::table('availability')->sum('sold'))->toBe($soldBefore);
    expect(DB::table('reservation_units')->where('reservation_id', $reservation->id)
        ->orderBy('id')->get()->map(fn ($l) => (array) $l)->toArray())->toEqual($linesBefore);
})->with('terminal statuses');

it('records the refusal with an honest actor and names the channel, without leaking guest PII', function () {
    [$channel, $reservation, $booking] = otaBookingIn('cancelled');

    app(BookingImportService::class)->modify($channel, $booking);

    $row = AuditEvent::query()->withoutGlobalScopes()
        ->where('action', 'reservation.ota_modification_refused')
        ->where('subject_id', $reservation->id)->latest('created_at')->first();

    expect($row)->not->toBeNull()
        ->and($row->subject_type)->toBe('reservation')      // the morph alias resolves — no throw
        ->and($row->actor_type)->toBe('system')             // a sync has no user; the enum is user/agent/system
        ->and($row->after['channel_id'])->toBe((string) $channel->id)
        ->and($row->after['status'])->toBe('cancelled')
        ->and($row->after['reason'])->toBe('terminal');

    // PII-SAFE (#28): operators read audit rows on four surfaces. Identifiers and states only.
    $json = json_encode($row->after);
    expect($json)->not->toContain('jane@example.com')
        ->not->toContain('Jane')->not->toContain('Doe');
});

it('THE POSITIVE CONTROL — a legitimate modification of a LIVE booking still applies', function () {
    ['scenario' => $s, 'channel' => $channel] = otaMappedScenario(units: 2);
    $payload = channexBookingPayload();

    $reservation = app(BookingImportService::class)->import($channel, $payload['booking']);
    expect($reservation->status)->toBe('confirmed');

    $booking = $payload['booking'];
    $booking['total_minor'] = 31500;
    $booking['rooms'][0]['amount_minor'] = 31500;

    $modified = app(BookingImportService::class)->modify($channel, $booking);

    // ⚠️ THIS COULD HAVE FAILED: an over-broad guard would refuse here too, and "everything refused" is
    // indistinguishable from "the method is broken" without this case.
    $row = Reservation::query()->withoutGlobalScopes()->find($reservation->id);
    expect($row->status)->toBe('confirmed')
        ->and((int) $row->total_minor)->toBe(31500)
        ->and($modified->id)->toBe($reservation->id);
});

it('THE SECOND ENTRANCE — the raw writer refuses too, and throws rather than returning', function () {
    [$channel, $reservation] = otaBookingIn('checked_out');

    $before = Reservation::query()->withoutGlobalScopes()->find($reservation->id)->getAttributes();

    // Call the raw writer DIRECTLY, bypassing modify() entirely — the path a future caller would take.
    expect(fn () => app(AssignmentService::class)->reimportBooking(
        $reservation,
        ['status' => 'confirmed', 'total_minor' => 99999],
        [],
    ))->toThrow(ReservationTerminalModificationException::class);

    expect(Reservation::query()->withoutGlobalScopes()->find($reservation->id)->getAttributes())
        ->toEqual($before);
});

/**
 * ⚠️⚠️ RULED BY THE FOUNDER, 2026-09-19 — R6: THE CHANNEL MANAGER KEEPS THE LAST WORD.
 *
 * ⚠️⚠️ THIS PINS A DELIBERATE PERMISSION, NOT A DEFECT. `checked_in` is NOT in `TERMINAL_STATUSES`, so an
 * inbound OTA modification may rewrite a stay THE GUEST IS PHYSICALLY IN — status, dates, price and unit
 * lines. That reads like the bug F-29 closed, and it is not: a real OTA can legitimately extend a stay
 * mid-visit, and refusing would drop a genuine booking change on the floor. **The founder ruled the channel
 * authoritative.**
 *
 * ⚠️ A FUTURE PASS WILL WANT TO ADD `checked_in` TO `TERMINAL_STATUSES`. That is the change this test exists
 * to make deliberate: it reddens here, with this docblock attached, rather than passing quietly.
 *
 * ⚠️ THE MUTATION: add 'checked_in' to `AssignmentService::TERMINAL_STATUSES` → the modify() path no-ops, the
 * status stays `checked_in` and the price stays put → RED on both assertions.
 */
it('⚠️⚠️ RULED 2026-09-19 (R6) — an inbound channel MAY still rewrite a stay the guest is physically in', function () {
    [$channel, $reservation, $booking] = otaBookingIn('checked_in');

    // A REAL modification: the OTA extends the stay and reprices it, mid-visit.
    $booking['total_minor'] = 99999;
    $booking['rooms'][0]['amount_minor'] = 99999;

    $returned = app(BookingImportService::class)->modify($channel, $booking);
    expect($returned->id)->toBe($reservation->id);

    $after = Reservation::query()->withoutGlobalScopes()->find($reservation->id);

    // ⚠️ THE RULING, IN TWO ROWS: the stay is re-confirmed and the MONEY moves. Both are permitted.
    expect((string) $after->status)->toBe('confirmed', 'R6: the channel re-confirms a checked-in stay');
    expect((int) $after->total_minor)->toBe(99999, 'R6: the channel may reprice a stay in progress');
});

/**
 * ⚠️⚠️ F-52 — RULED BY THE FOUNDER, 2026-09-19: THE CHANNEL MANAGER KEEPS ITS AUTHORITY OVER A SUSPENDED
 * ORGANISATION. THIS IS A DECISION, NOT AN OVERSIGHT.
 *
 * ⚠️⚠️ IT LOOKS EXACTLY LIKE THE DEFECT F-48 WAS, WHICH IS WHY IT IS PINNED. A suspended tenant whose public
 * storefront is closed ({@see ResolvePublicProperty}) but whose OTA bookings still land
 * reads, at a glance, like the guard was simply missed on one path. It was not: the founder ruled this path
 * stays open, and a future pass that "closes the gap" would be building past a decision.
 *
 * ⚠️⚠️ THE REASONING — refusing the sync DOES NOT PREVENT THE SALE. The OTA has already sold the room; the
 * guest holds a real reservation whatever this product does. Dropping it here closes no door — it only
 * BLINDS THE PMS to a booking that exists, turning a billing dispute into a DOUBLE-BOOKING and a guest
 * arriving at a room already given away. A closed storefront stops NEW sales; a refused sync loses the
 * record of one that already happened.
 *
 * ⚠️ AND IT COULD NOT BE A QUIET REFUSAL ANYWAY: this is the retrying webhook path, where a throw becomes
 * FAILED → retry → dead letter (FIX 14). The full reasoning also lives in `BookingImportService`'s docblock,
 * which is where somebody tempted to add the check would actually be standing.
 *
 * ⚠️ THE MUTATION THAT PROVES THIS PIN: add an organisation-status check to the import → this goes RED.
 */
it('⚠️⚠️ RULED 2026-09-19 (F-52) — an OTA booking STILL LANDS for a SUSPENDED organisation, deliberately', function (string $status) {
    ['scenario' => $s, 'channel' => $channel] = otaMappedScenario(units: 2);
    $payload = channexBookingPayload();

    // ⚠️ THE PUBLIC DOOR IS SHUT — the same organisation, the same moment. That contrast is the finding.
    Organization::query()->whereKey($s['org']->id)->update(['status' => $status]);
    expect((string) Organization::query()->whereKey($s['org']->id)->value('status'))->toBe($status);

    $reservation = app(BookingImportService::class)->import($channel, $payload['booking']);

    // ⚠️ THE ROW, NOT THE RETURN VALUE — a booking that exists in the world now exists in the PMS too.
    expect($reservation)->not->toBeNull();
    $row = Reservation::query()->withoutGlobalScopes()->find($reservation->id);
    expect($row)->not->toBeNull('F-52: the channel keeps its authority — the booking must be recorded');
    expect((string) $row->status)->toBe('confirmed');
    expect((string) $row->source)->not->toBe('');
})->with(['suspended', 'cancelled']);
