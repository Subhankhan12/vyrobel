<?php

use App\Livewire\Owner\OwnerBalance;
use App\Models\Membership;
use App\Models\Payout;
use App\Models\Reservation;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** A linked owner-portal user with a PAID September statement (17000 net after 15% commission on 20000). */
function reconOwner(array $s): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = ownUnit($s['units'][0]);            // owner + 15% agreement + tax details (factory default)
    $owner->update(['user_id' => $user->id]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);

    $svc = app(OwnerStatementService::class);
    $sept = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    $pay = app(OwnerPayoutService::class);
    $payout = $pay->createFromStatement($sept);
    if ($payout !== null) {
        $pay->pay($payout);   // manual rail — the owner has tax details on file
    }

    return compact('user', 'owner');
}

// ⛔ P2-1 (owner-realm audit) — the Balance CLEARING IDENTITY must hold to the cent (#17) EVEN after a post-lock
// refund produces a NEGATIVE statement (carried forward). The page states: Available + On hold + In transit +
// Paid == Net position. A negative statement's stamped rows were in Net but in no tile bucket, so the identity
// broke by exactly the carried amount. These assert the FULL identity (incl. the new Carried-forward bucket) —
// so the negative case FAILS on the pre-fix code (carriedForward absent → 0) and HOLDS after the fix.

/** The clearing-identity check for a rendered Balance component: Available+OnHold+InTransit+Paid+CarriedFwd == Net. */
function balanceIdentityHolds($c): bool
{
    $minor = fn (string $key): int => (int) ($c->viewData($key)['minor'] ?? 0);

    $sum = $minor('available') + $minor('onHold') + $minor('inTransit') + $minor('paid') + $minor('carriedForward');

    return $sum === $minor('net');
}

// ── 1. The identity holds for a normal paid statement (the common case is undisturbed) ─────────────
it('reconciles the Balance clearing identity for a normal paid statement', function () {
    $s = seedScenario(1);
    $c = reconOwner($s);

    $component = Livewire::actingAs($c['user'])->test(OwnerBalance::class);

    expect(balanceIdentityHolds($component))->toBeTrue()
        ->and((int) ($component->viewData('carriedForward')['minor'] ?? 0))->toBe(0);   // nothing carried
});

// ── 2. ⚠️ The identity holds after a post-lock refund → a NEGATIVE (carried-forward) statement ──────
it('reconciles the Balance clearing identity after a negative (carried-forward) statement', function () {
    $s = seedScenario(1);

    // Sept: a real positive statement, PAID (17000 net after 15% commission on 20000).
    $c = reconOwner($s);

    // Post-lock refund reverses the reservation → October nets NEGATIVE → carried forward (no payout).
    app(OwnerAttributionService::class)->reverseReservation(Reservation::first());
    $svc = app(OwnerStatementService::class);
    $oct = $svc->finalize($svc->generateDraft($c['owner']->id, '2026-10-01', '2026-10-31'));
    app(OwnerPayoutService::class)->createFromStatement($oct);   // negative → carryForward, no payout

    expect($oct->net_payable_minor)->toBeLessThan(0);   // the breaking condition really exists

    $component = Livewire::actingAs($c['user'])->test(OwnerBalance::class);

    // 🔒 #17 — the four tiles + the Carried-forward bucket reconstruct Net to the cent, even now.
    expect(balanceIdentityHolds($component))->toBeTrue()
        ->and((int) $component->viewData('carriedForward')['minor'])->toBeLessThan(0);   // the real carried figure
});

// ── 3. ⚠️ D16-7: the `processing` and `held` BUCKETS themselves — unguarded until now ──────────────
// Balance is the ONE owner surface that treats the engine's persisted `processing` and `held` statuses as
// first-class (In transit = processing + released pending; On hold = held + paused pending), while the shared
// `payoutStatusMeta` collapses both into "Payout pending" (O14/D16-7). Nothing guarded that: dropping either
// bucket from the component left every Balance test green, AND the engine-surface test green — because its
// identity is computed from the DB rather than from the component, and its `assertSee('On hold')` passes on
// the tile's static label. This asserts the COMPONENT'S OWN buckets, so a regression in either is caught.
it('puts a processing payout in In transit and a held payout in On hold', function () {
    $s = seedScenario(1);
    $c = reconOwner($s);   // September is finalized, with a PAID payout

    $payout = Payout::query()->where('owner_id', $c['owner']->id)->firstOrFail();
    expect((string) $payout->status)->toBe('paid');   // the starting state is real

    // PROCESSING — an initiated transfer awaiting the webhook → In transit, never On hold.
    $payout->forceFill(['status' => 'processing'])->save();
    $c1 = Livewire::actingAs($c['user'])->test(OwnerBalance::class);
    expect((int) $c1->viewData('inTransit')['minor'])->toBe((int) $payout->amount_minor)
        ->and((int) $c1->viewData('onHold')['minor'])->toBe(0)
        ->and(balanceIdentityHolds($c1))->toBeTrue();

    // HELD — the engine's persisted tax-hold → On hold, never In transit.
    $payout->forceFill(['status' => 'held'])->save();
    $c2 = Livewire::actingAs($c['user'])->test(OwnerBalance::class);
    expect((int) $c2->viewData('onHold')['minor'])->toBe((int) $payout->amount_minor)
        ->and((int) $c2->viewData('inTransit')['minor'])->toBe(0)
        ->and(balanceIdentityHolds($c2))->toBeTrue();
});
