<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\Payout;
use App\Models\User;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\PayoutResult;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Owner\PayoutRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// DO3-3 — the owner-facing FAILED-PAYOUT alert + retry (a RE-QUEUE, never a send).
//
// ⚠️ THE CATASTROPHIC FAILURE THIS FILE EXISTS TO PREVENT IS A DOUBLE PAYMENT. `PayoutRunService`
// disburses EVERY pending payout an owner has, so two live payouts for one statement = the owner is paid
// twice. Tests 4/5/6 force the three routes to that outcome (a double-submit · retrying a row whose sibling
// already SUCCEEDED · retrying onto one already in flight) and assert ONE effect each, by RAW query.
//
// ⚠️ Every count below is a raw `DB::table` read — never the scoped model — so a scoping bug can never make
// a row invisible to the assertion that is supposed to catch it (§N.18.1: a check must be independent of
// the mechanism it checks).

/** A rail that always fails — the only way to reach `failed`, since the manual rail cannot refuse. */
class RetryTestFailingRail implements PayoutRail
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

/** A linked owner (co-owner/primary → CAP_PAYOUT) whose September payout has genuinely FAILED. */
function failedPayoutOwner(array $s, int $unitIndex = 0, int $roomMinor = 20000): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only collected revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    $payouts = app(OwnerPayoutService::class);
    $payout = $payouts->pay($payouts->createFromStatement($statement), new RetryTestFailingRail);

    expect($payout->status)->toBe('failed');   // the fixture CONSTRUCTED its case (§N.27.6)

    return compact('user', 'owner', 'unit', 'statement', 'payout');
}

/** Raw count of payouts against a statement, by status — independent of every model scope. */
function rawPayoutCount(string $statementId, ?string $status = null): int
{
    $q = DB::table('payouts')->where('owner_statement_id', $statementId);

    return (int) ($status === null ? $q->count() : $q->where('status', $status)->count());
}

// ── 1. The alert renders for a REAL failed payout, with the rail's own reason + a retry control ──
it('shows the failed-payout alert with the real failure reason and a retry control', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);

    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('payout couldn’t be sent', false)
        ->assertSee('bank rejected the transfer')          // the rail's OWN text, verbatim — never rewritten
        ->assertSee('€170.00')                             // the real failed amount
        ->assertSee('September 2026')                      // the real statement period
        ->assertSee('attempted '.now()->format('j M Y'))   // the real failed_at, not a re-rendered "now"
        ->assertSee('Retry payout')
        ->assertSee(route('owner.payouts.retry', ['payout' => $a['payout']->id]), false);
});

// ── 2. ⚠️ STATE-GATED — every NON-failed state renders NO alert and NO retry control ─────────────
// The unrendered-sibling discipline: each state is FORCED, not assumed absent.
it('renders no failure alert or retry control for a pending, held, processing or paid payout', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);

    foreach (['pending', 'held', 'processing', 'paid'] as $status) {
        DB::table('payouts')->where('id', $a['payout']->id)->update(['status' => $status]);

        $this->actingAs($a['user'])->get('/owner/payouts')
            ->assertOk()
            ->assertDontSee('payout couldn’t be sent', false)
            ->assertDontSee('Retry payout');
    }
});

// ── 3. A retry queues EXACTLY ONE new pending payout, and keeps the failed row ───────────────────
it('queues exactly one new pending payout and preserves the failed attempt', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);

    $this->actingAs($a['user'])
        ->post(route('owner.payouts.retry', ['payout' => $a['payout']->id]))
        ->assertRedirect();

    expect(rawPayoutCount($a['statement']->id))->toBe(2)
        ->and(rawPayoutCount($a['statement']->id, 'failed'))->toBe(1)      // original NOT edited away
        ->and(rawPayoutCount($a['statement']->id, 'pending'))->toBe(1);

    $retry = DB::table('payouts')->where('owner_statement_id', $a['statement']->id)->where('status', 'pending')->first();
    expect($retry->supersedes_id)->toBe($a['payout']->id)                  // the history chain
        ->and((int) $retry->amount_minor)->toBe((int) $a['payout']->amount_minor);
});

// ── 4. ⚠️⚠️ DOUBLE-SUBMIT → ONE EFFECT (the double-click double-pay route) ───────────────────────
it('creates only ONE payout when the retry is submitted twice', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);
    $route = route('owner.payouts.retry', ['payout' => $a['payout']->id]);

    $this->actingAs($a['user'])->post($route)->assertRedirect();
    $this->actingAs($a['user'])->post($route)->assertRedirect();   // the double-click

    // ⚠️ TWO rows total: the failed original + exactly ONE re-queue. Three would be a double payment.
    expect(rawPayoutCount($a['statement']->id))->toBe(2)
        ->and(rawPayoutCount($a['statement']->id, 'pending'))->toBe(1);
});

// ── 4b. ⚠️ PER-LAYER — the SERVICE refuses the second re-queue on its own ────────────────────────
// Without this, test 4 passes on the controller's pre-flight alone: delete the service guard and the
// double-submit test stays green while every OTHER caller (the operator side, a job, a command) can
// double-pay. Defence in depth needs a test per layer (§N.4p), and this is the layer that must not fail.
it('refuses a second re-queue at the service, not merely at the controller', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);
    $svc = app(OwnerPayoutService::class);

    $first = $svc->retry($a['payout']->fresh());
    $second = $svc->retry($a['payout']->fresh());   // the same failed row, retried again

    expect($first->wasRecentlyCreated)->toBeTrue()
        ->and($second->wasRecentlyCreated)->toBeFalse()
        ->and($second->getKey())->toBe($first->getKey())        // the SAME row is handed back
        ->and(rawPayoutCount($a['statement']->id))->toBe(2)
        ->and(rawPayoutCount($a['statement']->id, 'pending'))->toBe(1);
});

// ── 5. ⚠️⚠️ SHOWN "FAILED" BUT ACTUALLY SUCCEEDED → REFUSED (the webhook-race double-pay route) ──
it('refuses a retry when a payout for the same statement has already been paid', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);

    // A sibling payout for this statement really succeeded — the page the owner is looking at predates it.
    Payout::create([
        'organization_id' => $a['owner']->organization_id,
        'owner_id' => $a['owner']->id,
        'owner_statement_id' => $a['statement']->id,
        'amount_minor' => $a['payout']->amount_minor,
        'currency' => $a['payout']->currency,
        'rail' => 'manual',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($a['user'])
        ->post(route('owner.payouts.retry', ['payout' => $a['payout']->id]))
        ->assertRedirect();

    // NOTHING new — the money already left; a re-queue here would send it a second time.
    expect(rawPayoutCount($a['statement']->id))->toBe(2)
        ->and(rawPayoutCount($a['statement']->id, 'pending'))->toBe(0);

    // …and the service refuses on its own, not merely the controller's pre-flight.
    expect(app(OwnerPayoutService::class)->retry($a['payout']->fresh())->wasRecentlyCreated)->toBeFalse();
    expect(rawPayoutCount($a['statement']->id))->toBe(2);
});

// ── 6. ⚠️⚠️ AN IN-FLIGHT RETRY DOES NOT STACK ───────────────────────────────────────────────────
it('refuses a retry while a payout for the same statement is already in flight', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);

    Payout::create([
        'organization_id' => $a['owner']->organization_id,
        'owner_id' => $a['owner']->id,
        'owner_statement_id' => $a['statement']->id,
        'amount_minor' => $a['payout']->amount_minor,
        'currency' => $a['payout']->currency,
        'rail' => 'stripe_connect',
        'status' => 'processing',
    ]);

    $this->actingAs($a['user'])
        ->post(route('owner.payouts.retry', ['payout' => $a['payout']->id]))
        ->assertRedirect();

    expect(rawPayoutCount($a['statement']->id))->toBe(2)
        ->and(rawPayoutCount($a['statement']->id, 'pending'))->toBe(0);

    // The page tells the owner the truth rather than offering a control that would be refused.
    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('already on its way')
        ->assertDontSee('Retry payout');

    // ⚠️ PER-LAYER — the service refuses it too, for every caller that never passes the controller.
    expect(app(OwnerPayoutService::class)->retry($a['payout']->fresh())->wasRecentlyCreated)->toBeFalse();
    expect(rawPayoutCount($a['statement']->id))->toBe(2)
        ->and(rawPayoutCount($a['statement']->id, 'pending'))->toBe(0);
});

// ── 7. 🔒 RBAC — an owner cannot retry ANOTHER owner's payout ────────────────────────────────────
it('404s a cross-owner retry and writes nothing', function () {
    $s = seedScenario(2);
    $a = failedPayoutOwner($s, 0);
    $b = failedPayoutOwner($s, 1);

    $this->actingAs($a['user'])
        ->post(route('owner.payouts.retry', ['payout' => $b['payout']->id]))
        ->assertNotFound();

    // Raw-query: owner B's statement gained nothing (a scoped read could not prove this).
    expect(rawPayoutCount($b['statement']->id))->toBe(1)
        ->and(rawPayoutCount($b['statement']->id, 'pending'))->toBe(0);
});

// ── 8. 🔒 CAP_PAYOUT — a non-co-owner sub-user sees the alert but cannot act ─────────────────────
it('refuses the retry for a sub-user without the payout capability', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);

    $accountant = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $accountant->id, 'status' => 'active']);
    OwnerUser::create([
        'organization_id' => $s['org']->id, 'owner_id' => $a['owner']->id,
        'user_id' => $accountant->id, 'role' => 'accountant', 'status' => 'active',
    ]);

    // They SHOULD know the payout failed — the alert renders; the control does not.
    $this->actingAs($accountant)->get('/owner/payouts')
        ->assertOk()
        ->assertSee('payout couldn’t be sent', false)
        ->assertDontSee('Retry payout');

    $this->actingAs($accountant)
        ->post(route('owner.payouts.retry', ['payout' => $a['payout']->id]))
        ->assertForbidden();

    expect(rawPayoutCount($a['statement']->id))->toBe(1);
});

// ── 9. The service refuses every non-failed status outright ──────────────────────────────────────
it('refuses to retry a payout that is not failed', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);
    $svc = app(OwnerPayoutService::class);

    foreach (['pending', 'processing', 'held', 'paid', 'reversed'] as $status) {
        DB::table('payouts')->where('id', $a['payout']->id)->update(['status' => $status]);

        expect(fn () => $svc->retry($a['payout']->fresh()))->toThrow(RuntimeException::class);
    }

    expect(rawPayoutCount($a['statement']->id))->toBe(1);
});

// ── 10. ⚠️ FIX-THEN-RETRY — a blocked account gets guidance, not a doomed re-send ────────────────
it('offers recovery guidance instead of a retry when the payout account would fail again', function () {
    config()->set('services.payouts.rail', 'stripe_connect');

    $s = seedScenario(1);
    $a = failedPayoutOwner($s);
    $a['owner']->update(['stripe_connect_account_id' => null, 'stripe_connect_payouts_enabled' => false]);

    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('payout couldn’t be sent', false)
        ->assertSee('Connect your payout account')
        ->assertSee(route('owner.payout-method'), false)
        ->assertDontSee('Retry payout');

    $this->actingAs($a['user'])
        ->post(route('owner.payouts.retry', ['payout' => $a['payout']->id]))
        ->assertRedirect();

    expect(rawPayoutCount($a['statement']->id))->toBe(1);   // nothing queued
});

// ── 11. ⚠️ §N.8f DRIFT PIN — the blockers ARE the rail's own refusal conditions ──────────────────
// Reads the REAL rail rather than a copied list, so the guidance cannot drift from what actually fails.
it('keeps the recovery blockers in step with the Stripe Connect rail’s real refusals', function () {
    config()->set('services.payouts.rail', 'stripe_connect');

    $s = seedScenario(1);
    $a = failedPayoutOwner($s);
    $owner = $a['owner'];
    $rail = app(StripeConnectPayoutRail::class);

    // (a) No connected account → the rail refuses AND a blocker is reported.
    $owner->update(['stripe_connect_account_id' => null, 'stripe_connect_payouts_enabled' => false]);
    expect($rail->send($a['payout']->fresh())->successful)->toBeFalse()
        ->and(PayoutRecovery::blockers($owner->fresh()))->not->toBeEmpty();

    // (b) Connected but payouts not enabled → still refuses, still blocked.
    $owner->update(['stripe_connect_account_id' => 'acct_test_123', 'stripe_connect_payouts_enabled' => false]);
    expect($rail->send($a['payout']->fresh())->successful)->toBeFalse()
        ->and(PayoutRecovery::blockers($owner->fresh()))->not->toBeEmpty();

    // (c) Ready → no blocker (the rail's own guards are satisfied).
    $owner->update(['stripe_connect_account_id' => 'acct_test_123', 'stripe_connect_payouts_enabled' => true]);
    expect(PayoutRecovery::blockers($owner->fresh()))->toBe([]);

    // The MANUAL rail records an offline transfer and cannot refuse → it contributes no blocker.
    config()->set('services.payouts.rail', 'manual');
    $owner->update(['stripe_connect_account_id' => null, 'stripe_connect_payouts_enabled' => false]);
    expect(PayoutRecovery::blockers($owner->fresh()))->toBe([]);
});

// ── 12. The one definition of "live" is shared by BOTH writers (§N.8f) ───────────────────────────
it('pins the live-payout status set that both payout writers guard against', function () {
    expect(Payout::LIVE_STATUSES)->toBe(['pending', 'processing', 'held', 'paid']);

    // `failed`/`reversed` are absent on purpose — that absence is what makes a failed payout retryable.
    expect(Payout::LIVE_STATUSES)->not->toContain('failed')
        ->and(Payout::LIVE_STATUSES)->not->toContain('reversed');

    // Both guards read the constant rather than their own copy of the list.
    $source = file_get_contents(app_path('Services/OwnerPayoutService.php'));
    expect(substr_count($source, 'Payout::LIVE_STATUSES'))->toBe(2)
        ->and($source)->not->toContain("['pending', 'processing', 'held', 'paid']");
});

// ── 13. The lifecycle note promises no automatic retry (§N.8d) ───────────────────────────────────
it('does not tell the owner a failed payout will be retried automatically', function () {
    $s = seedScenario(1);
    $a = failedPayoutOwner($s);

    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertDontSee('it will be retried')          // the old, false promise
        ->assertSee('Retrying queues a new payout');   // what is actually true
});
