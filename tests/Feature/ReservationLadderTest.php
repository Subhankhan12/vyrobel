<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Mail\ReviewRequestMail;
use App\Models\AuditEvent;
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

// ── ⚠️⚠️ R5 — THE `tentative` ARM, THE `no_show` ARM'S SIBLING (FIX 55) ───────────────────────────
//
// ⚠️⚠️ R5 RULED: REFUSE, AND NAME THE CONFIRM-THEN-CHECK-IN ROUTE. ⚠️ It could not be written when it was
// first ruled, and the reason is the whole lesson: 🔬 THERE WAS NO CONFIRM ROUTE. Sixteen operator POST
// routes touched a reservation and not one promoted it to `confirmed`. Copy naming it would have sent a
// receptionist to a control that did not exist — the exact dead end the `no_show` measurement caught a pass
// earlier. FIX 54 BUILT and DROVE the route; only now is the sentence true, so only now is it written.
//
// ⚠️⚠️ AND THE TWO ARMS ARE NOT THE SAME SHAPE, which is why R5 gets its own copy rather than R1's:
// 🔬 a no-show's nights were RELEASED, so the way out is a NEW booking that re-checks availability and may
// find the room gone. 🔬 A tentative hold still OCCUPIES its nights (`availability.sold` 0 → 2 at create,
// unchanged at confirm), so confirming converts what is already held and cannot fail for availability.
// Copy that blurred the two would send a receptionist down the wrong path in one of the two cases.
it('⚠️⚠️ R5 — the tentative refusal names the SHIPPED confirm control, and the two cannot drift', function () {
    $s = seedScenario(2);
    $reservation = tentativeHold($s);   // ⚠️ THE REAL PRODUCER — not a status written onto a row
    $before = rlRow($reservation);

    expect((string) $reservation->status)->toBe('tentative');

    $message = null;
    try {
        app(CheckInReservation::class)->handle($reservation);
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
    }

    expect($message)->not->toBeNull('a tentative booking was CHECKED IN — R5 rules that it must be refused');

    // ⚠️ The generic fallthrough is precisely what this replaced: a closed door with no way out.
    expect($message)->not->toBe('Cannot check in a tentative reservation.',
        'R5 still falls through to the generic message — the refusal must name the route');

    expect($message)
        ->toContain('held but not confirmed')        // 🔬 WHY: the room is taken, the promise is not made
        ->toContain('cannot fail for availability'); // 🔬 the hold already occupies the nights

    // ⚠️⚠️ THE CONTROL IS NAMED BY READING THE SURFACE, NEVER BY TYPING THE LABEL TWICE. Two `match`es for one
    // concept drift; so do a button and the sentence that points at it. If the button is renamed, the copy
    // becomes a lie about the product's own UI — and this fails, naming both halves.
    $blade = i18nEnglishSource(resource_path('views/livewire/reservation-detail.blade.php'));
    preg_match('/app\\.reservations\\.confirm.*?<button[^>]*>([^<]+)<\\/button>/s', (string) $blade, $m);
    $shippedLabel = trim($m[1] ?? '');

    expect($shippedLabel)->toBe('Confirm hold',
        'the confirm control was renamed — R5\'s refusal copy names the OLD label and now misdirects');
    // ⚠️ `toContain()` takes VARIADIC NEEDLES, not a message — a second argument there is silently asserted
    //    as another needle. Stated explicitly because this exact slip cost two red runs in the pass that
    //    wrote this file.
    expect(str_contains((string) $message, $shippedLabel))->toBeTrue(
        'R5\'s refusal does not name the control a receptionist must actually press');

    // ⚠️ AND A REFUSAL MOVES NOTHING. The row is the evidence, never the exception type.
    expect(rlRow($reservation))->toBe($before, 'a refused check-in must not move the reservation row');
});

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

// ── ⚠️ APPLY 01 #2 (founder, 2026-09-22) — THE EARLY CHECK-IN CONFIRMATION ────────────────────────────
//
// ⚠️⚠️ IT IS A WARNING, NOT A CONTROL, AND THESE TESTS ARE WRITTEN TO PIN THAT DISTINCTION RATHER THAN THE
// INTERRUPTION. The interesting assertions are the ones proving it REFUSES NOTHING: a second press always
// completes, no permission is attached, and the same actor who saw the warning passes it. A future pass that
// "hardens" this into a gate — a permission, a signed token, a role check — is changing a ruling.
//
// ⚠️ THE ROW IS THE EVIDENCE, not the status code. A 200 that rendered the confirmation and a 200 that
// silently checked the guest in are indistinguishable from the response alone.

it('⚠️ RULED 2026-09-22 — warns on a 13-day-early check-in, and writes NOTHING until it is confirmed', function () {
    $this->travelTo('2026-08-19');           // 13 days before the fixture's 2026-09-01 arrival
    $s = seedScenario(1);
    $r = rlAt($s, 'confirmed');
    $actor = operatorUser($s, [], 'Front Desk');

    // The shared definition, asserted directly — the screen and the audit row both read this number.
    expect(CheckInReservation::daysEarly($r))->toBe(13);

    $response = $this->actingAs($actor)->post(route('app.front-desk.check-in', $r->id));

    $response->assertOk()                    // the confirmation renders; it is NOT the PRG redirect
        ->assertSee('13 days away')
        ->assertSee('Check in anyway');

    // ⚠️ AND NOTHING MOVED. A warning that had already written would be worse than no warning at all.
    expect((string) $r->fresh()->status)->toBe('confirmed')
        ->and($r->fresh()->guest_checked_in_at)->toBeNull();
});

it('⚠️ completes on the second press — the SAME actor, no extra permission, nothing to earn', function () {
    $this->travelTo('2026-08-19');
    $s = seedScenario(1);
    $r = rlAt($s, 'confirmed');
    $actor = operatorUser($s, [], 'Front Desk');   // the LOWEST back-office role, deliberately

    $this->actingAs($actor)->post(route('app.front-desk.check-in', $r->id))->assertOk();   // warned…

    $this->actingAs($actor)
        ->post(route('app.front-desk.check-in', $r->id), ['confirm_early' => 1, 'date' => '2026-08-19'])
        ->assertRedirect();

    // ⚠️ THE RULING, IN ONE ROW: it warns and then gets out of the way.
    expect((string) $r->fresh()->status)->toBe('checked_in');

    // ⚠️⚠️ AND THE EARLINESS IS RECORDED ON THE PATH THAT WENT THROUGH THE WARNING — not only on the direct
    // service call. This is the half that makes the feature survive being ignored: a UI confirmation is
    // bypassed by a direct POST, a double-submit, or anyone who clicks straight through, so the audit row is
    // what makes the mistake findable afterwards. Asserting it on the HTTP path is the point; asserting it
    // only on handle() would prove the recorder works and say nothing about the route people actually use.
    $row = AuditEvent::query()->withoutGlobalScopes()
        ->where('action', 'reservation.checked_in')
        ->where('subject_id', $r->id)->latest('created_at')->first();

    expect($row)->not->toBeNull()
        ->and($row->after['days_early'])->toBe(13);
});

it('⚠️ says nothing at all on the arrival day — the common case is uninterrupted', function () {
    $this->travelTo('2026-09-01');           // the arrival date itself
    $s = seedScenario(1);
    $r = rlAt($s, 'confirmed');
    $actor = operatorUser($s, [], 'Front Desk');

    expect(CheckInReservation::daysEarly($r))->toBe(0);

    // ⚠️ A REDIRECT, NOT A 200 — no confirmation was rendered, and the check-in happened on the first press.
    $this->actingAs($actor)
        ->post(route('app.front-desk.check-in', $r->id), ['date' => '2026-09-01'])
        ->assertRedirect();

    expect((string) $r->fresh()->status)->toBe('checked_in');
});

it('⚠️ records the earliness on the audit row — on the early one AND on the zero', function () {
    // ⚠️ THE ZERO MATTERS AS MUCH AS THE THIRTEEN. A field written only on the unusual case cannot be read
    // as "this one was on time": its absence would be ambiguous between that and "written before the field
    // existed". Both halves are asserted so a later optimisation cannot drop the boring one.
    $this->travelTo('2026-08-19');
    $s = seedScenario(1);
    $early = rlAt($s, 'confirmed');

    app(CheckInReservation::class)->handle($early);

    $row = AuditEvent::query()->withoutGlobalScopes()
        ->where('action', 'reservation.checked_in')
        ->where('subject_id', $early->id)->latest('created_at')->first();

    expect($row)->not->toBeNull()
        ->and($row->after['days_early'])->toBe(13)
        ->and($row->after['status'])->toBe('checked_in');

    $this->travelTo('2026-09-01');
    $s2 = seedScenario(1);
    $onTime = rlAt($s2, 'confirmed');

    app(CheckInReservation::class)->handle($onTime);

    $row2 = AuditEvent::query()->withoutGlobalScopes()
        ->where('action', 'reservation.checked_in')
        ->where('subject_id', $onTime->id)->latest('created_at')->first();

    expect($row2->after)->toHaveKey('days_early')
        ->and($row2->after['days_early'])->toBe(0);
});

it('⚠️ the threshold is CONFIG, and the warning follows it', function () {
    // ⚠️ PROVES THE NUMBER IS NOT HARD-CODED — the ruling put it in config precisely so it is one knob and
    // not a literal scattered across a controller, a view and an action. Raising it past the earliness
    // silences the warning; the check-in then completes on the FIRST press.
    $this->travelTo('2026-08-19');
    $s = seedScenario(1);
    $r = rlAt($s, 'confirmed');
    $actor = operatorUser($s, [], 'Front Desk');

    expect(CheckInReservation::isUnusuallyEarly($r))->toBeTrue();

    config(['reservations.early_check_in_warning_days' => 30]);
    expect(CheckInReservation::isUnusuallyEarly($r))->toBeFalse();

    $this->actingAs($actor)
        ->post(route('app.front-desk.check-in', $r->id), ['date' => '2026-08-19'])
        ->assertRedirect();

    expect((string) $r->fresh()->status)->toBe('checked_in');
});
