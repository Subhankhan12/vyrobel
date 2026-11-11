<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\ReverseCheckIn;
use App\Exceptions\Booking\ReservationInProgressException;
use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Services\AssignmentService;
use App\Support\Reservations\ReservationStatusLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ R9 (founder, 2026-09-22) — REVERSE A MIS-CLICKED CHECK-IN.
 *
 * ⚠️⚠️ WHY IT EXISTS, IN ONE SENTENCE: R7 blocked `checked_in → cancelled` and left `checked_in` with a
 * single exit, so a clerk who checked in the wrong row had no way back. **This is the door R7 needed**, and
 * the proof that matters is the one the previous pass could not run: reverse, then cancel, and watch the
 * room come back.
 *
 * ⚠️ WHAT A CHECK-IN WRITES — 🔬 measured by diffing EVERY table across a real call, not by reading:
 * `reservations.status` (the only column that moves) + one row each in `reservation_status_history`,
 * `domain_events` and `audit_events`. **Nothing else** — no folio item, no availability row, no housekeeping
 * task, no email. That zero is why reversing is safe, and it is why these tests assert it directly.
 */

/** A booking driven to `checked_in` through the REAL action — never a status flip, so the fixture is honest. */
function rciCheckedIn(array $s, int $unitIndex = 0): Reservation
{
    $r = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);

    return app(CheckInReservation::class)->handle($r->fresh());
}

// ── (a) THE REVERSAL ITSELF — every side effect confirmed by reading state back ──────────────────────
it('⚠️ R9(a) — reverses checked_in → confirmed, appending the trail rather than editing it', function () {
    $s = seedScenario(1);
    $r = rciCheckedIn($s);

    $historyBefore = ReservationStatusHistory::query()->where('reservation_id', $r->id)->count();
    $soldBefore = DB::table('availability')->sum('sold');

    $disclosure = app(ReverseCheckIn::class)->handle($r->fresh());

    $after = $r->fresh();
    expect((string) $after->status)->toBe('confirmed');

    // ⚠️ APPEND, NEVER EDIT (#7). The check-in really happened; deleting its row would be a second lie.
    expect(ReservationStatusHistory::query()->where('reservation_id', $r->id)->count())->toBe($historyBefore + 1)
        ->and(ReservationStatusHistory::query()->where('reservation_id', $r->id)
            ->where('from_status', 'checked_in')->where('to_status', 'confirmed')
            ->where('reason', 'check-in reversed')->exists())->toBeTrue()
        // …and the ORIGINAL check-in row is still there.
        ->and(ReservationStatusHistory::query()->where('reservation_id', $r->id)
            ->where('to_status', 'checked_in')->exists())->toBeTrue();

    // ⚠️⚠️ INVENTORY IS UNTOUCHED, AND THAT IS THE DESIGN. The nights were occupied at BOOKING time and
    //    `checked_in` never changed them, so `confirmed` holds exactly the same lines. A status operation
    //    reaching into the single inventory write path is the F-18 shape.
    expect(DB::table('availability')->sum('sold'))->toBe($soldBefore)
        ->and($r->reservationUnits()->where('status', 'active')->count())->toBe(1);

    // Both event streams record the reversal as its own event — never as a second `checked_in`.
    expect(DomainEvent::query()->withoutGlobalScopes()
        ->where('subject_id', $r->id)->where('event_type', 'reservation.checkin_reversed')->count())->toBe(1);

    $audit = AuditEvent::query()->withoutGlobalScopes()
        ->where('action', 'reservation.checkin_reversed')->where('subject_id', $r->id)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->before['status'])->toBe('checked_in')
        ->and($audit->after['status'])->toBe('confirmed');

    // The disclosure is RETURNED, like R3's — an undo whose consequences surface afterwards is the failure
    // mode this shape exists to avoid.
    expect($disclosure['reversible'])->not->toBeEmpty()
        ->and($disclosure['not_reversed'])->not->toBeEmpty();
});

// ── (b) ⚠️⚠️ THE STEP THAT COULD NOT RUN LAST PASS ───────────────────────────────────────────────────
it('⚠️⚠️ R9(b) — reverse → cancel → ALLOWED, and the unit is released. This closes the loop R7 opened', function () {
    $s = seedScenario(1);
    $r = rciCheckedIn($s);

    $soldWhileInHouse = DB::table('availability')->sum('sold');
    expect($soldWhileInHouse)->toBeGreaterThan(0, 'the fixture must actually hold inventory, or this proves nothing');

    // ⚠️ THE CONTROL FIRST: while checked in, cancelling is still refused. Without this the test could pass
    //    on a build where R7 had been removed, and would then be asserting nothing about the sequence.
    expect(fn () => app(AssignmentService::class)->cancelReservation($r->fresh(), 'too early'))
        ->toThrow(ReservationInProgressException::class);
    expect((string) $r->fresh()->status)->toBe('checked_in')
        ->and(DB::table('availability')->sum('sold'))->toBe($soldWhileInHouse);

    // …reverse…
    app(ReverseCheckIn::class)->handle($r->fresh());
    expect((string) $r->fresh()->status)->toBe('confirmed');

    // …and NOW the cancel goes through.
    $cancelled = app(AssignmentService::class)->cancelReservation($r->fresh(), 'wrong booking — checked in by mistake');

    expect((string) $cancelled->status)->toBe('cancelled')
        ->and((string) $cancelled->cancellation_reason)->toBe('wrong booking — checked in by mistake')
        // ⚠️⚠️ THE ROOM COMES BACK. This is the whole point of the ruling pair: R7 stopped the room being
        //    freed under a guest who was in it; R9 makes it freeable once someone says the arrival was wrong.
        ->and(DB::table('availability')->sum('sold'))->toBeLessThan($soldWhileInHouse)
        ->and($r->reservationUnits()->where('status', 'active')->count())->toBe(0);
});

// ── (c) REFUSED FROM EVERY OTHER STATUS, with the row unmoved ────────────────────────────────────────
it('⚠️ R9(c) — refuses from any status but checked_in, and moves nothing', function (string $status) {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);
    Reservation::query()->whereKey($r->getKey())->update(['status' => $status]);

    $historyBefore = ReservationStatusHistory::query()->where('reservation_id', $r->id)->count();

    // ⚠️ preview() must refuse too — the confirmation screen calls it, and a screen that renders for an
    //    impossible reversal invites a press that will fail.
    expect(fn () => app(ReverseCheckIn::class)->preview($r->fresh()))->toThrow(RuntimeException::class);
    expect(fn () => app(ReverseCheckIn::class)->handle($r->fresh()))->toThrow(RuntimeException::class);

    expect((string) $r->fresh()->status)->toBe($status)
        ->and(ReservationStatusHistory::query()->where('reservation_id', $r->id)->count())->toBe($historyBefore)
        ->and(DomainEvent::query()->withoutGlobalScopes()
            ->where('subject_id', $r->id)->where('event_type', 'reservation.checkin_reversed')->count())->toBe(0);
})->with(['confirmed', 'cancelled', 'checked_out', 'no_show', 'tentative']);

// ── (d) ⚠️ DOUBLE-SUBMIT — one state change, one event, no duplicate rows ────────────────────────────
it('⚠️ R9(d) — a second reversal changes nothing and appends nothing', function () {
    $s = seedScenario(1);
    $r = rciCheckedIn($s);

    app(ReverseCheckIn::class)->handle($r->fresh());

    $historyAfterFirst = ReservationStatusHistory::query()->where('reservation_id', $r->id)->count();

    // ⚠️ THE SECOND PRESS REFUSES RATHER THAN SILENTLY SUCCEEDING. Both outcomes leave one state change; the
    //    difference is whether the second clerk is told. Reporting "reversed" to someone whose press did
    //    nothing is the same class of lie as a warning that never fired.
    expect(fn () => app(ReverseCheckIn::class)->handle($r->fresh()))->toThrow(RuntimeException::class);

    expect((string) $r->fresh()->status)->toBe('confirmed')
        ->and(ReservationStatusHistory::query()->where('reservation_id', $r->id)->count())->toBe($historyAfterFirst)
        ->and(ReservationStatusHistory::query()->where('reservation_id', $r->id)
            ->where('to_status', 'confirmed')->where('reason', 'check-in reversed')->count())->toBe(1)
        ->and(DomainEvent::query()->withoutGlobalScopes()
            ->where('subject_id', $r->id)->where('event_type', 'reservation.checkin_reversed')->count())->toBe(1)
        ->and(AuditEvent::query()->withoutGlobalScopes()
            ->where('subject_id', $r->id)->where('action', 'reservation.checkin_reversed')->count())->toBe(1);
});

// ── ⚠️ THE REFUSAL MESSAGE NOW NAMES THE REAL REMEDY, AND A TEST HOLDS IT THERE ─────────────────────
it('⚠️ R9 — R7’s refusal names the reversal, not a dead end, and cannot drift back', function () {
    // ⚠️⚠️ THIS IS A COPY PIN, AND IT IS LOAD-BEARING. Until R9 shipped, this message honestly said reversing
    //    "is not built yet" — correct then, false now. A refusal that names a control which does not exist
    //    and a refusal that fails to name one that does are the same defect pointed in opposite directions.
    foreach (['cancelled', 'no_show'] as $to) {
        $msg = ReservationInProgressException::messageFor($to);
        expect($msg)->toContain('Reverse the check-in first')
            ->and($msg)->toContain(ReservationStatusLabel::for($to))   // D-REFUSAL-STATUS-CODE: the word, never the code
            ->and($msg)->not->toContain($to === 'no_show' ? 'no_show' : '~~none~~')
            ->and($msg)->not->toContain('not built yet');
    }

    // ⚠️ AND THE ROUTE IT NAMES IS REAL — a message can be true in English and wrong about the product.
    expect(route('app.front-desk.reverse-checkin', 'x'))->toContain('reverse-checkin');
});

// ── ⚠️ THE TWO REFUSAL SETS STAY DISJOINT — R9 adds a transition, which is when someone merges them ──
it('⚠️ R9 — TERMINAL_STATUSES and IN_PROGRESS_STATUSES are still disjoint', function () {
    // ⚠️ R9's own premise depends on it: reversing something the rest of the system calls FINAL would be
    //    incoherent, which is why the action asserts this at runtime too.
    expect(array_intersect(AssignmentService::TERMINAL_STATUSES, AssignmentService::IN_PROGRESS_STATUSES))
        ->toBe([], 'R7 and #29 have been merged — R9 cannot be correct while that is true');
    expect(AssignmentService::IN_PROGRESS_STATUSES)->toBe(['checked_in']);
});
