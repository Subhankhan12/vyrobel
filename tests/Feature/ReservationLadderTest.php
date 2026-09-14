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
        ->toThrow(RuntimeException::class, "Cannot check in a {$from} reservation.");

    expect(rlRow($r))->toBe($before)
        ->and(rlRow($r)['status'])->toBe($from);
})->with(['cancelled', 'no_show', 'checked_out']);

it('⚠️ F-18 — refuses check-out from a booking that was never checked in, proven by row state', function (string $from) {
    $s = seedScenario(1);
    $r = rlAt($s, $from);
    $before = rlRow($r);

    expect(fn () => app(CheckOutReservation::class)->handle($r))
        ->toThrow(RuntimeException::class, "Cannot check out a {$from} reservation.");

    expect(rlRow($r))->toBe($before);
})->with(['cancelled', 'no_show']);

it('⚠️⚠️ confirmed → checked_out IS PERMITTED — the map was re-derived, not widened to green a test', function () {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);

    // 🔬 DemoSeeder:605 and :700 perform exactly this through this action, never checking in first. A map
    // that refused it reddened 41 tests across five certified suites AND broke `migrate:fresh --seed`.
    // ⚠️ Asserted so that TIGHTENING it later is a deliberate act that breaks a test — R4 in STATUS-LADDERS.md.
    app(CheckOutReservation::class)->handle($r);

    expect((string) $r->fresh()->status)->toBe('checked_out');
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
