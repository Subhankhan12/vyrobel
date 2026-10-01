<?php

declare(strict_types=1);

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the wireframe-parity sweep's HIGH-3.
 *
 * `OperatorOwners::payStatus()` tested "does this owner have ANY paid payout" against the owner's whole payout
 * history. One historical paid payout therefore short-circuited every later branch: a finalized-UNPAID statement
 * rendered "Paid", `needs_attention` fell to 0, the net-payable KPI read €0.00 and the payout run reported
 * "0 owners scheduled" — while Owner Detail showed the real pending figure on the SAME rows. An operator would
 * have concluded nothing was owed.
 *
 * The blind spot: every existing test gave the owner exactly ONE statement and ONE payout, so the short-circuit
 * could never fire. These give the owner a PAID history AND a pending current statement — the ordinary state of
 * any owner past their first payout.
 */
function payableStateStaff(array $s): User
{
    $user = User::factory()->create(['name' => 'Payable Staff', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $s['org']);
}

/** An owner with a real agreement, a payout method, and two finalized statements (July + September). */
function payableStateOwner(array $s): array
{
    $owner = Owner::factory()->create([
        'name' => 'Amara Okafor', 'payout_currency' => 'EUR', 'status' => 'active', 'tax_id' => 'IE1234567X',
    ]);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 18.0,
        'payout_schedule' => 'monthly', 'effective_from' => '2026-01-01', 'effective_to' => null,
    ]);
    PayoutMethod::create([
        'owner_id' => $owner->id, 'label' => 'Main', 'account_holder' => 'Amara Okafor',
        'iban' => 'IE29AIBK93115212344821', 'iban_last4' => '4821', 'iban_country' => 'IE',
        'currency' => 'EUR', 'is_default' => true,
    ]);

    $statements = app(OwnerStatementService::class);

    // An EARLIER period, closed and genuinely PAID — the history that used to mask everything after it.
    // (The test helper books only in September, so July carries no activity; its payout row is what matters.)
    $julyStatement = $statements->finalize($statements->generateDraft($owner->id, '2026-07-01', '2026-07-31'));
    Payout::create([
        'organization_id' => $s['org']->id,
        'owner_id' => $owner->id,
        'owner_statement_id' => $julyStatement->id,
        'amount_minor' => 50000,
        'currency' => 'EUR',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    // SEPTEMBER — earned and statemented, payout NOT yet made.
    $sept = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($sept, 20000);
    app(OwnerAttributionService::class)->attributeReservation($sept);
    collectFolio($sept);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    $septStatement = $statements->finalize($statements->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return ['owner' => $owner, 'july' => $julyStatement, 'september' => $septStatement];
}

it('still shows the real pending payable when the owner has an older PAID payout', function () {
    $s = seedScenario(1);
    $ctx = payableStateOwner($s);
    $net = (int) $ctx['september']->net_payable_minor;

    expect($net)->toBeGreaterThan(0);   // the September statement genuinely owes money

    // ⚠️ Assert the STATUS, not the row's money: the row prints the statement net either way, so it does not
    // discriminate. With the bug, the JULY payout made this read "Paid" and dropped Needs-attention to 0.
    // With the fix the September statement is judged on its own payout (there is none yet) and correctly
    // surfaces for review.
    $this->actingAs(payableStateStaff($s))->get(route('app.owners'))
        ->assertOk()
        ->assertSee('Needs review')     // the real state of an unpaid, unverified statement
        // and it counts toward Needs review. ⚠️ 2026-10-01: the figure's label read "1 draft" — the statement is FINALIZED,
        // with lines the booking channel has not confirmed, so the label now says that; the count it pins is the same.
        ->assertSee('1 with lines the booking channel hasn’t confirmed')
        ->assertSee(MoneyFormatter::display($net, 'EUR'));
});

it('reads Paid only when THIS statement’s payout is paid', function () {
    $s = seedScenario(1);
    $ctx = payableStateOwner($s);

    // Pay September too — now "Paid" is the truthful state.
    $payout = app(OwnerPayoutService::class)->createFromStatement($ctx['september']);
    $payout?->update(['status' => 'paid', 'paid_at' => now()]);

    // Now "Paid" is the truthful state, and it no longer needs review. ⚠️ Asserted as the ZERO, not as the absence of
    // the old "1 draft" label — once the label changed, that absence held whatever the count was.
    $this->actingAs(payableStateStaff($s))->get(route('app.owners'))
        ->assertOk()
        ->assertSee('Paid')
        ->assertSee('0 with lines the booking channel hasn’t confirmed');
});

it('keeps the operator list agreeing with the owner’s own statement figure', function () {
    $s = seedScenario(1);
    $ctx = payableStateOwner($s);

    // An older paid payout must not suppress the attention signal for a later, unpaid statement.
    $this->actingAs(payableStateStaff($s))->get(route('app.owners'))
        ->assertOk()
        ->assertSee(MoneyFormatter::display((int) $ctx['september']->net_payable_minor, 'EUR'))
        ->assertSee('1 with lines the booking channel hasn’t confirmed');
});
