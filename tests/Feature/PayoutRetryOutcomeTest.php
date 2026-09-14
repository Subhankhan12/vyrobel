<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Payout;
use App\Models\User;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\PayoutResult;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ══ THE LAST REFUSAL-AS-SUCCESS — AND IT IS THE WORST-PLACED OF THE THREE ═════════════════════════
//
// ⚠️⚠️ `payout_retry_notice` rendered EVERY message through `class="vy-flash is-ok"` — a skin literally
// named for success, with a green tick. The messages themselves were already careful (the view's own
// comment says they report what actually happened rather than a blanket success). THE SKIN CONTRADICTED
// THEM, and this is a money surface: an owner told their retry succeeded stops chasing it.
//
// ⚠️⚠️ THREE OUTCOMES, NOT TWO — AND THE BRIEF'S COUNT DOES NOT HOLD. It called four of five messages
// refusals. Read against `OwnerPayoutRetryController`, they are not:
//   · "We've queued this payout…"                      → SUCCESS.
//   · "…no longer in a failed state, nothing re-queued" → NO-OP. The payout moved on; nothing is lost.
//   · "…a replacement is already on its way"            → NO-OP, and it is GOOD NEWS: money IS coming.
//   · "Please finish setting up your payout account"    → ⚠️ THE ONE TRUE REFUSAL.
// Painting the two no-ops red would be a NEW honesty defect — telling an owner whose money is on its way
// that something failed. So the fix is three-way, and these tests pin all three.
//
// ⚠️ NO MESSAGE TEXT AND NO REFUSAL LOGIC CHANGED IN THIS PASS. Presentation over a working guard.

/** A rail that always fails — the only way to reach `failed`; the manual rail cannot refuse. */
class OutcomeFailingRail implements PayoutRail
{
    public function name(): string
    {
        return 'manual';
    }

    public function send(Payout $payout): PayoutResult
    {
        return new PayoutResult(successful: false, failureReason: 'bank rejected the transfer');
    }
}

function outcomeFailedPayout(): array
{
    $s = seedScenario(1);
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][0];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
    ]);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    $payouts = app(OwnerPayoutService::class);
    $payout = $payouts->pay($payouts->createFromStatement($statement), new OutcomeFailingRail);

    expect($payout->status)->toBe('failed');   // ⚠️ the fixture CONSTRUCTED its case, not assumed it

    return compact('s', 'user', 'owner', 'payout');
}

function outcomeRetry(array $c): string
{
    // ⚠️ `->from()` IS REQUIRED: the controller answers with `back()`, and with no referer a test follows
    //    the fallback to `/` — the marketing home page — where none of these assertions mean anything.
    return test()->actingAs($c['user'])->from(route('owner.payouts'))->followingRedirects()
        ->post(route('owner.payouts.retry', ['payout' => $c['payout']->id]))
        ->assertOk()
        ->getContent();
}

// ── ⚠️⚠️ THE ONE TRUE REFUSAL — an INSTRUCTION that was rendered as a COMPLETION ──────────────────
//
// ⚠️ `PayoutRecovery::blockers()` returns actionable items with a CTA ("Connect your payout account" →
// owner.payout-method). So the retry did not happen AND WILL NEVER HAPPEN until the owner acts. Under a
// green tick they do nothing — and the thing they are not doing is the only thing that releases the money.
// ⚠️ LATENT TODAY, LIVE AT CUTOVER: blockers() returns [] unless PAYOUTS_RAIL=stripe_connect, which
// defaults to `manual`. This branch becomes reachable exactly when real money starts moving.
it('⚠️ renders "finish setting up your payout account" as an ACTION, never a completion', function () {
    config()->set('services.payouts.rail', 'stripe_connect');   // makes blockers() reachable
    $c = outcomeFailedPayout();
    $c['owner']->forceFill(['stripe_connect_account_id' => null])->save();

    $html = outcomeRetry($c);

    expect($html)->toContain('Please finish setting up your payout account first');

    // ⚠️ THE SKIN — `is-err`, not the success skin it used to wear.
    expect($html)->toContain('class="vy-flash is-err"');
    expect($html)->not->toContain('class="vy-flash is-ok"');

    // ⚠️ NOT COLOUR ALONE — an accessible prefix and an assertive role, and focus taken.
    expect($html)->toContain('Action needed: ');
    expect($html)->toContain('role="alert"');
});

// ── ⚠️ THE SUCCESS HALF — the direction that costs, and the one that caught a vacuous needle last pass ──
it('⚠️ still renders a genuine re-queue as a success', function () {
    $c = outcomeFailedPayout();

    $html = outcomeRetry($c);

    expect($html)->toContain('We’ve queued this payout for another attempt');
    // ⚠️ A component that rendered EVERYTHING as refused would satisfy the refusal test above.
    expect($html)->toContain('class="vy-flash is-ok"');
    expect($html)->not->toContain('class="vy-flash is-err"');
    expect($html)->not->toContain('Action needed: ');
});

// ── ⚠️⚠️ THE NO-OP — must be NEITHER a tick NOR an alarm ──────────────────────────────────────────
it('⚠️ renders "no longer in a failed state" as neither a success nor a failure', function () {
    $c = outcomeFailedPayout();
    // The payout moves on between the read and the click — the shape the pre-flight exists to report.
    $c['payout']->forceFill(['status' => 'paid'])->save();

    $html = outcomeRetry($c);

    expect($html)->toContain('no longer in a failed state');

    // ⚠️ NOT a success: nothing was queued, so no tick.
    expect($html)->not->toContain('class="vy-flash is-ok"');
    // ⚠️ NOT a refusal either: nothing is wrong and nothing is owed. Painting this red would invent a
    //    problem — the honesty defect one layer over from the one this pass fixes.
    expect($html)->not->toContain('class="vy-flash is-err"');
    expect($html)->toContain('No change: ');
});

// ── ⚠️ THE TWO SKINS ARE ONE CONTRACT — the anti-drift device (§N.8f) ─────────────────────────────
//
// ⚠️ The owner realm CANNOT use <x-operator.flash-notice>: the operator shell styles `.vy-toast`, the owner
// shell styles `.vy-flash`, so the operator component would render UNSTYLED inside <x-owner.shell>. Two
// skins are therefore justified — but "which icon means what" must not drift, so it is TESTED, not trusted.
it('⚠️ keeps the operator and owner refusal skins answering identically', function () {
    $operator = file_get_contents(resource_path('views/components/operator/flash-notice.blade.php'));
    $owner = file_get_contents(resource_path('views/components/owner/flash-notice.blade.php'));

    foreach ([$operator, $owner] as $component) {
        expect($component)->toContain('role="alert"');          // assertive, because the action failed
        expect($component)->toContain('tabindex="-1"');         // a live region at page load never announces
        expect($component)->toContain('$el.focus()');           // …so focus is what actually reaches the user
        expect($component)->toContain('vy-sr-only');            // an accessible prefix, not colour alone
        expect($component)->toContain('M10.29 3.86');           // the SAME alert triangle, not a recoloured tick
        expect($component)->toContain('M20 6 9 17l-5-5');       // and the same tick for a real success
    }
});
