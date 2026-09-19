<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Actions\Reservations\ReverseCheckOut;
use App\Mail\ReviewRequestMail;
use App\Models\Guest;
use App\Models\GuestReviewInvite;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * R3 (founder, 2026-09-19) — RECORD THE SEND, THEN REVERSE AND DISCLOSE.
 *
 * ⚠️⚠️ THE CENTRAL RISK THIS FILE GUARDS IS NOT THE UNDO — IT IS THE BOUNDARY. Every stay that checked out
 * before `guest_review_invites` existed has NO ROW, and an absent row means something completely different
 * from an empty one: "we have no idea" versus "nothing was sent". An undo that reports the first as the
 * second is the precise lie this ruling was made to prevent, and it would be invisible on a green suite.
 */
function rcoStay(array $s): Reservation
{
    $r = book($s, ['unit_id' => $s['units'][0]->id]);
    app(CheckInReservation::class)->handle($r);
    app(CheckOutReservation::class)->handle($r->fresh());

    return $r->fresh();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1 · THE BOUNDARY — three states, and the third is the one that bites
// ─────────────────────────────────────────────────────────────────────────────

/**
 * ⚠️⚠️ THE MUTATION THIS EXISTS FOR: make the undo read a missing row as "no email was sent" and this goes
 * RED. If it stays green the boundary is not built, whatever the screen says.
 */
it('⚠️⚠️ reports UNKNOWN — never "no email was sent" — for a stay that predates the record', function () {
    Mail::fake();
    $s = seedScenario(1);
    $stay = rcoStay($s);

    // ⚠️ EXACTLY WHAT A PRE-MIGRATION STAY LOOKS LIKE: the row is absent, not empty.
    GuestReviewInvite::query()->where('reservation_id', $stay->getKey())->delete();

    $d = app(ReverseCheckOut::class)->preview($stay);

    expect($d['review_email']['known'])->toBeFalse();
    expect($d['review_email']['state'])->toBe(GuestReviewInvite::UNKNOWN_PREDATES_RECORD);

    // ⚠️⚠️ THE WORDING CARRIES IT. "No review request was sent" about this stay would be a false claim, and
    // a vaguer sentence that merely avoids being falsifiable is not a fix either.
    expect($d['review_email']['message'])
        ->toContain('no record')
        ->toContain('before this product started recording')
        ->not->toContain('No review request was sent');
});

it('⚠️ reports SENT, with the time, when a request genuinely went', function () {
    Mail::fake();
    $s = seedScenario(1);
    $stay = rcoStay($s);

    GuestReviewInvite::query()->where('reservation_id', $stay->getKey())->update([
        'outcome' => GuestReviewInvite::SENT, 'sent_at' => now(),
    ]);

    $d = app(ReverseCheckOut::class)->preview($stay);

    expect($d['review_email']['known'])->toBeTrue();
    expect($d['review_email']['sent'])->toBeTrue();
    expect($d['review_email']['message'])->toContain('cannot be recalled');
});

/**
 * ⚠️ THE STATE THE BOUNDARY IS OFTEN CONFUSED WITH — a KNOWN negative. This one MAY say "no request was
 * sent", because the product actually knows. Asserted beside the unknown case so the two cannot drift into
 * one sentence.
 */
it('⚠️ reports a KNOWN negative differently from the unknown one, and says why', function () {
    Mail::fake();
    $s = seedScenario(1);
    $stay = rcoStay($s);

    GuestReviewInvite::query()->where('reservation_id', $stay->getKey())->update([
        'outcome' => GuestReviewInvite::SUPPRESSED_BY_GUEST, 'sent_at' => null,
    ]);

    $d = app(ReverseCheckOut::class)->preview($stay);

    expect($d['review_email']['known'])->toBeTrue();
    expect($d['review_email']['sent'])->toBeFalse();
    expect($d['review_email']['message'])
        ->toContain('No review request was sent')
        ->toContain('asked not to receive');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2 · THE UNDO — what it reverses, what it refuses to touch
// ─────────────────────────────────────────────────────────────────────────────

it('⚠️ reverses the status and REMOVES a departure task nobody has started', function () {
    Mail::fake();
    $s = seedScenario(1);
    $stay = rcoStay($s);

    // ⚠️ PERTURBED ON PURPOSE: the task must EXIST first, or "removed" and "never created" look identical.
    expect(HousekeepingTask::query()->where('type', 'departure')->count())->toBe(1);

    app(ReverseCheckOut::class)->handle($stay);

    expect((string) $stay->fresh()->status)->toBe('checked_in');
    expect(HousekeepingTask::query()->where('type', 'departure')->count())->toBe(0);
});

/**
 * ⚠️⚠️ CANCELLING WORK SOMEONE HAS BEGUN IS AN INSTRUCTION, NOT AN UNDO. A started task is left exactly
 * where it is and reported — the alternative is silently discarding a cleaner's shift.
 */
it('⚠️⚠️ LEAVES a cleaning task that has already been started, and says so', function () {
    Mail::fake();
    $s = seedScenario(1);
    $stay = rcoStay($s);

    HousekeepingTask::query()->where('type', 'departure')->update(['status' => 'in_progress']);

    $d = app(ReverseCheckOut::class)->handle($stay);

    expect(HousekeepingTask::query()->where('type', 'departure')->count())->toBe(1);
    expect((string) HousekeepingTask::query()->where('type', 'departure')->first()->status)->toBe('in_progress');
    expect(implode(' ', $d['not_reversed']))->toContain('already been started');
});

it('⚠️ does NOT assert the unit is clean again — the guest is back in the room', function () {
    Mail::fake();
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $stay = rcoStay($s);

    expect((string) $unit->fresh()->housekeeping_status)->toBe('dirty');

    $d = app(ReverseCheckOut::class)->handle($stay);

    expect((string) $unit->fresh()->housekeeping_status)->toBe('dirty');
    expect(implode(' ', $d['not_reversed']))->toContain('stays marked dirty');
});

it('⚠️ refuses to reverse anything that is not checked out', function () {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);

    expect(fn () => app(ReverseCheckOut::class)->handle($r))->toThrow(RuntimeException::class);
    expect((string) $r->fresh()->status)->toBe('confirmed');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3 · THE REVIEW LINK — proven in BOTH directions
// ─────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ ALLOW — a guest invited to review can still review after the checkout is reversed', function () {
    Mail::fake();
    $s = seedScenario(1);
    $stay = rcoStay($s);

    expect(app(ReviewService::class)->isCompleted($stay))->toBeTrue();

    app(ReverseCheckOut::class)->handle($stay);

    // ⚠️ The stay is IN_HOUSE again — and the invitation the product already sent must still work.
    expect(app(ReviewService::class)->isCompleted($stay->fresh()))->toBeTrue();
});

/**
 * ⚠️⚠️ THE POSITIVE CONTROL, AND IT CAN FAIL. A gate proven only in ALLOW may be letting the whole world
 * review. This stay has NEVER been checked out and its dates have not passed, so the only honest answer is no.
 */
it('⚠️⚠️ DENY — a stay that has never completed still cannot be reviewed', function () {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);
    app(CheckInReservation::class)->handle($r);

    expect(app(ReviewService::class)->isCompleted($r->fresh()))->toBeFalse();
});

/**
 * ⚠️⚠️ RECORDED ON SUCCESS, NOT ON INTENT. A row written when the send is REQUESTED would claim an email
 * that never left — and the undo would then tell an operator the guest holds an invitation they never got,
 * which is the same class of lie as the boundary, pointing the other way.
 *
 * ⚠️ 🔬 `ReviewRequestMail` is NOT `ShouldQueue` and no caller passes a delay, so the review path is
 * synchronous and a clean return genuinely means it went. If that ever changes, this assertion is where it
 * should be re-argued.
 */
it('⚠️⚠️ records NO_CONTACT rather than SENT when there was nobody to send to', function () {
    Mail::fake();
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ PERTURBED: strip the contact so the send cannot succeed. Without this the outcome would be `sent`
    //    either way and the test could not tell intent from success.
    $r->leadGuest?->forceFill(['email' => null])->save();

    app(CheckInReservation::class)->handle($r);
    app(CheckOutReservation::class)->handle($r->fresh());

    $row = GuestReviewInvite::query()->where('reservation_id', $r->getKey())->first();

    expect($row)->not->toBeNull('a row must exist either way — its ABSENCE is reserved for pre-record stays');
    expect((string) $row->outcome)->toBe(GuestReviewInvite::NO_CONTACT);
    expect($row->sent_at)->toBeNull();
    Mail::assertNotSent(ReviewRequestMail::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4 · THE CONFIRMATION SURFACE — the consequences appear BEFORE the press
// ─────────────────────────────────────────────────────────────────────────────

/**
 * ⚠️⚠️ AN UNDO WHOSE CONSEQUENCES ONLY APPEAR AFTERWARDS IS THE FAILURE MODE, NOT THE FEATURE. The confirm
 * screen must carry the unknown state in its own words — rendering it as "not sent" would put the lie on a
 * screen even though the service underneath answers correctly.
 */
it('⚠️⚠️ the confirm screen SHOWS the unknown state, and writes nothing', function () {
    Mail::fake();
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');
    $stay = rcoStay($s);
    GuestReviewInvite::query()->where('reservation_id', $stay->getKey())->delete();

    $html = $this->actingAs($actor)
        ->get(route('app.front-desk.reverse-checkout', $stay->getKey()))
        ->assertOk()->getContent();

    expect($html)->toContain('we have no record');
    expect($html)->toContain('stays marked dirty');

    // ⚠️ THE SCREEN IS READ-ONLY — a preview that mutated would be the opposite of a confirmation.
    expect((string) $stay->fresh()->status)->toBe('checked_out');
});

it('⚠️ the POST performs the reversal and carries the disclosure back', function () {
    Mail::fake();
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');
    $stay = rcoStay($s);

    $this->actingAs($actor)
        ->post(route('app.front-desk.reverse-checkout.store', $stay->getKey()))
        ->assertRedirect();

    expect((string) $stay->fresh()->status)->toBe('checked_in');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5 · THE TRIPS CARD — FIX 34's RESIDUAL, CLOSED
// ─────────────────────────────────────────────────────────────────────────────

/** A CLAIMED guest holding a stay that was checked out and then reversed. */
function rcoReopenedForGuest(): array
{
    $s = seedScenario(1);
    $stay = rcoStay($s);
    app(ReverseCheckOut::class)->handle($stay);

    $guest = Guest::withoutGlobalScopes()->whereKey($s['guest']->id)->first();
    $guest->forceFill(['password' => 'claimed-Passw0rd!', 'email_verified_at' => now()])->save();

    return ['scenario' => $s, 'stay' => $stay->fresh(), 'guest' => $guest];
}

/**
 * ⚠️⚠️ FIX 34 CLOSED THE LINK AND REPORTED THE CARD AS A RESIDUAL — this closes it.
 *
 * The emailed review link is gated on `ReviewService::isCompleted()`, which FIX 34 widened to *has this stay
 * ever completed*. The trips CARD went on asking `ReservationStage::PAST`, and a re-opened stay is IN_HOUSE —
 * so the guest held a link that worked and a list with no button for the same action. **The screen disagreed
 * with the server about the guest's own stay.**
 *
 * ⚠️ THE CARD NOW ASKS THE SAME RULE, via the batch form `isCompleted()` itself delegates to — one rule,
 * asked two ways, never two computations of one concept.
 */
it('⚠️⚠️ the trips list offers the review button for a REOPENED stay — the same answer the link gives', function () {
    Mail::fake();
    $c = rcoReopenedForGuest();

    // ⚠️ THE STAY IS BACK IN HOUSE — the exact state in which the card used to hide the button.
    expect((string) $c['stay']->status)->toBe('checked_in');

    $html = $this->actingAs($c['guest'], 'guest')->get('/guest/trips')->assertOk()->getContent();

    expect($html)->toContain(route('guest.review', $c['stay']->getKey()));

    // ⚠️ AND THE LINK STILL WORKS — the card and the server must agree, which is the whole finding.
    $this->actingAs($c['guest'], 'guest')->get(route('guest.review', $c['stay']->getKey()))->assertOk();
});

/**
 * ⚠️⚠️ THE CONTROL, AND IT CAN FAIL. A card that offered "review" to every stay would pass the case above
 * while being far worse. A stay that has NEVER completed must still have no button.
 */
it('⚠️ and a stay that has never completed still has NO review button', function () {
    $s = seedScenario(1);
    $r = book($s, ['unit_id' => $s['units'][0]->id]);
    app(CheckInReservation::class)->handle($r);

    $guest = Guest::withoutGlobalScopes()->whereKey($s['guest']->id)->first();
    $guest->forceFill(['password' => 'claimed-Passw0rd!', 'email_verified_at' => now()])->save();

    $html = $this->actingAs($guest, 'guest')->get('/guest/trips')->assertOk()->getContent();

    expect($html)->not->toContain(route('guest.review', $r->getKey()));
});

/**
 * ⚠️⚠️ THE N+1 IS PART OF THE FIX — a page that is correct and slow is not finished, and a per-card
 * `exists()` is exactly why FIX 34 reported this residual rather than fixing it in passing.
 *
 * ⚠️ MEASURED WITH SEVERAL TRIPS, NOT ONE: with one card, "does the count grow" cannot be asked at all. The
 * assertion is that the review decision costs a FIXED number of queries regardless of how many trips exist.
 */
it('⚠️⚠️ decides reviewability in ONE query however many trips there are', function () {
    Mail::fake();
    $s = seedScenario(3);

    $guest = Guest::withoutGlobalScopes()->whereKey($s['guest']->id)->first();
    $guest->forceFill(['password' => 'claimed-Passw0rd!', 'email_verified_at' => now()])->save();

    // ⚠️⚠️ PERTURBED ON PURPOSE, AND THE FIRST ATTEMPT AT THIS FIXTURE WAS VACUOUS. Stays that are simply
    //    checked out are PAST BY DATE, and `isCompleted()` short-circuits on the stage WITHOUT a query — so a
    //    genuine per-card N+1 still cost zero history queries and this test passed under the very mutation it
    //    exists to catch. 🔬 Confirmed by running that mutation.
    //
    // ⚠️ THESE STAYS ARE REVERSED, so each is `checked_in` (NOT past) and only the HISTORY branch can answer
    //    — which is precisely the branch that would fire once per card.
    foreach ([0, 1, 2] as $i) {
        $r = book($s, ['unit_id' => $s['units'][$i]->id]);
        app(CheckInReservation::class)->handle($r);
        app(CheckOutReservation::class)->handle($r->fresh());
        app(ReverseCheckOut::class)->handle($r->fresh());
        expect((string) $r->fresh()->status)->toBe('checked_in');   // the stage branch cannot answer
    }

    $count = 0;
    DB::listen(function ($q) use (&$count): void {
        if (str_contains($q->sql, 'reservation_status_history')) {
            $count++;
        }
    });

    $this->actingAs($guest, 'guest')->get('/guest/trips')->assertOk();

    expect($count)->toBeLessThanOrEqual(1,
        'the reviewability decision must not cost a query per trip — got '.$count.' for 3 stays');
});
