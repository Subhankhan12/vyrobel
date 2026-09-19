<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Mail\ReviewRequestMail;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Services\FrontDeskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ── F-18 · THE RESERVATION LADDER IS BOUNDED AT THE LAYER THAT WRITES ─────────────────────────────
// Measured before the fix (Phase 9, in rolled-back transactions): a CANCELLED reservation was driven
// to `checked_in`, and a `confirmed` one that was never checked in was driven to `checked_out`. Both
// controllers guarded correctly; the ACTIONS did not, and the actions are what perform the write.
//
// ⚠️ EVERY ASSERTION READS THE ROW. Phase 9 recorded that three of four probes returned 302 PRG and
// told nothing whatsoever — the status code is not evidence here.

/** Every column an illegal transition could move. */
function rlRow(Reservation $r): array
{
    $f = $r->fresh();

    // ⚠️ THESE ARE THE REAL COLUMNS. A first version of this helper read `checked_in_at`/`checked_out_at`,
    // which DO NOT EXIST on `reservations` — so those two assertions compared null to null and proved
    // nothing. Caught by dumping an actual row rather than trusting the model. The real names are below.
    return [
        'status' => (string) $f->status,
        'guest_checked_in_at' => $f->guest_checked_in_at?->toDateTimeString(),
        'cancelled_at' => $f->cancelled_at?->toDateTimeString(),
        'cancellation_reason' => $f->cancellation_reason,
        'payment_status' => (string) $f->payment_status,
        'balance_minor' => (int) $f->balance_minor,
    ];
}

/** Force a fixture into a state by CREATION, never by driving the ladder. */
function rlAt(array $s, string $status): Reservation
{
    $r = book($s, ['unit_id' => $s['units'][0]->id]);
    Reservation::query()->whereKey($r->getKey())->update(['status' => $status]);

    return $r->fresh();
}

it('⚠️ F-18 — refuses check-in from every TERMINAL state, proven by row state', function (string $from) {
    $s = seedScenario(1);
    $r = rlAt($s, $from);
    $before = rlRow($r);

    expect(fn () => app(CheckInReservation::class)->handle($r))
        ->toThrow(RuntimeException::class);

    expect(rlRow($r))->toBe($before)
        ->and(rlRow($r)['status'])->toBe($from);

    // ⚠️⚠️ R1 (founder, 2026-09-19) — THE REFUSAL MUST NAME THE WAY OUT. A front desk with a guest standing
    // in front of them needs the next move, not only a closed door. A bare "cannot check in a cancelled
    // reservation" is what this replaced.
    //
    // ⚠️⚠️ THIS ASSERTION WAS WIDENED BY A RULING, NOT BY A FAILURE (FIX 49, F-60). It read one ternary over
    // a SHARED message; the founder extended R1 to `no_show` and split the two arms, so `no_show` now names
    // the route a receptionist should actually take. ⚠️ The route was DRIVEN before it was named: 🔬
    // reschedule REFUSES a released line, and a new reservation SUCCEEDS and re-takes the nights. Asserting
    // the old shared phrase here would now pass over copy that points nowhere.
    try {
        app(CheckInReservation::class)->handle($r);
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain(match ($from) {
            'checked_out' => 'departure has already been recorded',
            'no_show' => 'take a New reservation on the front desk',
            default => 'needs a new booking',
        });
    }

    // ⚠️ AND THE `no_show` COPY MUST STAY TRUE TO WHAT WAS MEASURED, not merely mention a control.
    if ($from === 'no_show') {
        try {
            app(CheckInReservation::class)->handle($r);
        } catch (RuntimeException $e) {
            expect($e->getMessage())
                ->toContain('released back to sale')      // 🔬 availability.sold 2 → 0
                ->toContain('re-checks availability')     // 🔬 why a status write is not enough
                ->toContain('stay on the guest record')   // 🔬 the folio and the no-show survive
                ->not->toContain('reschedule');           // 🔬 DRIVEN: reschedule refuses a released line
        }
    }
})->with(['cancelled', 'no_show', 'checked_out']);

it('⚠️ F-18 — refuses check-out from a booking that was never checked in, proven by row state', function (string $from) {
    $s = seedScenario(1);
    $r = rlAt($s, $from);
    $before = rlRow($r);

    expect(fn () => app(CheckOutReservation::class)->handle($r))
        ->toThrow(RuntimeException::class);

    expect(rlRow($r))->toBe($before);

    try {
        app(CheckOutReservation::class)->handle($r);
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('needs a new booking');
    }
})->with(['cancelled', 'no_show']);

/**
 * ⚠️⚠️ THIS CASE REPLACES A PINNED ANSWER. Until 2026-09-19 it read "confirmed → checked_out IS PERMITTED"
 * and asserted the opposite of what it asserts now. That pin was doing its job: **the founder's ruling R4
 * turned it red, and this edit is the record of the decision** — not a quiet fix to a failing test.
 *
 * ⚠️ WHAT THE OLD PIN PROTECTED, AND WHY IT IS NO LONGER NEEDED. `DemoSeeder` performed this transition at
 * three sites (:605 the past review stay, :700 the walk-in, and five times inside `seedOwnerReviews`), so an
 * earlier map that refused it reddened 41 tests and broke `migrate:fresh --seed`. **The seeder now walks the
 * real ladder** — it checks in first — so the product no longer performs the transition at all.
 *
 * ⚠️ THE GUARD IS IN THE ACTION, NOT THE CONTROLLER. Driving the action directly is the whole point: F-18
 * existed because the guard sat one layer above the write, and an HTTP-only test stayed green over it.
 */
it('⚠️⚠️ RULED 2026-09-19 (R4) — a stay CANNOT be checked out without being checked in', function () {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);
    $before = rlRow($r);

    expect(fn () => app(CheckOutReservation::class)->handle($r))->toThrow(RuntimeException::class);

    // ⚠️ THE ROW, NOT THE EXCEPTION — a refusal that still wrote would be the defect in a new coat.
    expect(rlRow($r))->toBe($before)
        ->and(rlRow($r)['status'])->toBe('confirmed');

    // ⚠️ AND THE MESSAGE NAMES BOTH REAL CASES, or the desk is left with no next move.
    try {
        app(CheckOutReservation::class)->handle($r);
    } catch (RuntimeException $e) {
        expect($e->getMessage())
            ->toContain('checked in before it can be checked out')
            ->toContain('leaving early')
            ->toContain('no-show');
    }
});

it('⚠️⚠️ AN ILLEGAL CHECK-OUT FIRES NO SIDE EFFECT — no task, no dirty unit, no review email to a real guest', function () {
    Mail::fake();
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $r = rlAt($s, 'cancelled');

    expect(HousekeepingTask::count())->toBe(0);

    expect(fn () => app(CheckOutReservation::class)->handle($r))->toThrow(RuntimeException::class);

    // `reservation.checked_out` drives THREE listeners. The guard runs before the event is recorded,
    // so none of them may fire — above all the one that emails a guest about a stay that never happened.
    expect(HousekeepingTask::count())->toBe(0)
        ->and((string) $unit->fresh()->housekeeping_status)->toBe('clean');
    Mail::assertNotQueued(ReviewRequestMail::class);
    Mail::assertNotSent(ReviewRequestMail::class);
});

it('✅ POSITIVE CONTROL — the legal ladder still runs end to end and its side effects still fire', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $r = book($s, ['unit_id' => $unit->id]);

    app(FrontDeskService::class)->checkIn($r);
    expect((string) $r->fresh()->status)->toBe('checked_in');

    app(FrontDeskService::class)->checkOut($r);
    expect((string) $r->fresh()->status)->toBe('checked_out');

    // ⚠️ Without this, "everything refused" and "the path is broken" are the same observation — and it
    // also proves the guard did not silently suppress the legitimate check-out's side effects.
    expect((string) $unit->fresh()->housekeeping_status)->toBe('dirty')
        ->and(HousekeepingTask::query()->where('type', 'departure')->count())->toBe(1);
});

it('⚠️ idempotency is UNCHANGED — a repeated press still returns quietly, never a refusal', function () {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);

    app(FrontDeskService::class)->checkIn($r);
    app(CheckInReservation::class)->handle($r->fresh());          // second press
    expect((string) $r->fresh()->status)->toBe('checked_in');

    app(FrontDeskService::class)->checkOut($r->fresh());
    app(CheckOutReservation::class)->handle($r->fresh());         // second press
    expect((string) $r->fresh()->status)->toBe('checked_out');
});

it('⚠️ EVERY ENTRANCE — the guard is in the ACTION, so a caller that bypasses the controller still meets it', function () {
    $s = seedScenario(1);
    $r = rlAt($s, 'cancelled');

    // F-18's exact shape: the controller bounds the HTTP surface, the action performs the write, and a
    // job/console/future caller reaches the action directly. THIS is the test that reddens if the guard
    // is moved up into the controller.
    expect(fn () => app(CheckInReservation::class)->handle($r))->toThrow(RuntimeException::class);
    expect(fn () => app(CheckOutReservation::class)->handle($r))->toThrow(RuntimeException::class);

    expect((string) $r->fresh()->status)->toBe('cancelled');
});

it('⚠️ THE HTTP SURFACE WAS ALWAYS GREEN — this test passes with the action guard REMOVED, which is why F-18 hid', function () {
    $s = seedScenario(1);
    $r = rlAt($s, 'cancelled');
    $actor = operatorUser($s, [], 'Front Desk');

    $this->actingAs($actor)->post(route('app.front-desk.check-in', $r->id));

    expect((string) $r->fresh()->status)->toBe('cancelled');

    // ⚠️ THIS IS THE CONTROL FOR THE WRONG-FIX MUTATION. The controller's own pre-check refuses here and
    // always did — so this assertion cannot distinguish a guarded action from an unguarded one. Only the
    // service-level probes above can. Kept deliberately: it documents the blind spot rather than closing it.
});
