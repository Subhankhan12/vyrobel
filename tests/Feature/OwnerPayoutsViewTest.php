<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** An owner with a PENDING payout + NO tax details (→ the real tax-hold) — for the on-hold banner. */
function heldPayoutOwner(array $s, int $unitIndex, int $roomMinor): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR', 'tax_id' => null]);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    app(OwnerPayoutService::class)->createFromStatement($statement);   // PENDING (not paid → no tax details)

    return compact('user', 'owner', 'unit', 'statement');
}

// Design pass · Screen 3 (Owner Payouts) — a NEW owner-scoped page. Locks its PRESENTATION guarantees:
// live payout data renders; paid-this-year reconciles to Σ real paid payouts; the lifecycle shows the
// REAL states only (no invented "Scheduled"/"On hold"); no fake IBAN/date; owner isolation holds
// (another owner's payouts invisible, non-linked user fails closed).

/** A linked owner with a finalized statement + payout (optionally paid through the manual rail). */
function payoutOwner(array $s, int $unitIndex, int $roomMinor, bool $pay = false): array
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
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    $payoutSvc = app(OwnerPayoutService::class);
    $payout = $payoutSvc->createFromStatement($statement);
    if ($pay && $payout !== null) {
        $payout = $payoutSvc->pay($payout);   // manual rail (default)
    }

    return compact('user', 'owner', 'unit', 'statement', 'payout');
}

// ── 1. Real payout data + paid-year reconciliation + NO invented statuses + no fake IBAN ─────────
it('renders real payout data, reconciles paid-this-year, and invents no Scheduled/On-hold statuses', function () {
    $s = seedScenario(1);
    $a = payoutOwner($s, 0, 20000, pay: true);   // net €170.00, payout PAID via the manual rail

    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('Payout history')
        ->assertSee('Paid this year')
        ->assertSee('€170.00')                       // the real payout amount
        ->assertSee('Bank transfer')                 // rail=manual → "Bank transfer"
        ->assertSee('Finalized')                     // REAL lifecycle labels only
        ->assertSee('Paid')
        ->assertSee('data-countup="17000"', false)   // count-up on the REAL amount
        // ⚠️ the design's non-existent lifecycle states must NOT be invented
        ->assertDontSee('Scheduled')
        ->assertDontSee('On hold')
        ->assertDontSee('On-hold')
        ->assertDontSee('Verification pending')
        // no fake payout-account details
        ->assertDontSee('IBAN')
        ->assertDontSee('4821');

    // paid-this-year reconciles to Σ the owner's REAL paid payouts.
    expect((int) Payout::query()->where('owner_id', $a['owner']->id)->where('status', 'paid')->sum('amount_minor'))->toBe(17000);
});

// ── 2. Owner isolation — A sees only A's payouts; a non-linked user fails closed ─────────────────
it('scopes payouts to the owner, hides other owners, and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = payoutOwner($s, 0, 20000, pay: true);   // €170.00
    $b = payoutOwner($s, 1, 50000, pay: true);   // €425.00 (distinctive)

    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('€170.00')
        ->assertDontSee('€425.00');

    // A non-linked authenticated user resolves to an empty owner scope → nothing, never all owners.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/payouts')
        ->assertOk()
        ->assertSee('No payouts')
        ->assertDontSee('€170.00')
        ->assertDontSee('€425.00');
});

// ── 3. The on-hold banner shows the REAL tax-hold only (never a fabricated "verification pending") ──
it('shows the real tax-hold banner for a held payout and never a fake verification hold', function () {
    $s = seedScenario(1);
    $a = heldPayoutOwner($s, 0, 20000);   // pending €170.00, no tax details → the real tax-hold

    $response = $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('on hold')                       // the banner (exact design)
        ->assertSee('tax details are on file')       // the REAL reason
        ->assertSee(route('owner.tax'))              // the real CTA → Tax Center
        ->assertDontSee('Verification pending')      // never the fabricated bank-verification hold
        ->assertDontSee('micro-deposit');

    assertNoRawBlade($response);
});

// ── 4. The masked payout IBAN shows ONLY to a payout-capable role (CAP_PAYOUT), never the full number ──
it('shows the masked payout IBAN only to a payout-capable role', function () {
    $s = seedScenario(2);
    $a = payoutOwner($s, 0, 20000, pay: true);

    PayoutMethod::create([
        'organization_id' => $s['org']->id, 'owner_id' => $a['owner']->id,
        'label' => 'Revolut Business', 'account_holder' => 'Amara Okafor',
        'iban' => 'LT121000011101001000', 'iban_last4' => '1000', 'iban_country' => 'LT',
        'currency' => 'EUR', 'is_default' => true,
    ]);

    // The PRIMARY owner (co-owner, CAP_PAYOUT) sees the masked IBAN + account — never the full number.
    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('···· 1000')
        ->assertSee('Revolut Business')
        ->assertDontSee('LT121000011101001000');

    // An accountant may VIEW the page (it's not payout-gated) but must NOT see the account number (#28).
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->get('/owner/payouts')
        ->assertOk()
        ->assertDontSee('···· 1000')
        ->assertDontSee('LT121000011101001000');
});

// ── 5. Payout preferences — a REAL store; CO-OWNER writes (CAP_PAYOUT), others are refused ─────────
it('saves payout preferences for a co-owner and refuses a non-payout role', function () {
    $s = seedScenario(1);
    $a = payoutOwner($s, 0, 20000);

    // Co-owner (primary) saves — the row persists (mutation-proven).
    $this->actingAs($a['user'])->post(route('owner.payouts.preferences'), [
        'frequency' => 'twice_monthly', 'payout_day' => 2, 'minimum' => '500', 'paused' => '1',
    ])->assertRedirect();

    $pref = OwnerPayoutPreference::query()->where('owner_id', $a['owner']->id)->firstOrFail();
    expect($pref->frequency)->toBe('twice_monthly')
        ->and($pref->payout_day)->toBe(2)
        ->and($pref->minimum_minor)->toBe(50000)   // €500 → 50000 minor (#17)
        ->and($pref->paused)->toBeTrue();

    // An accountant / view-only sub-user is refused the write (CAP_PAYOUT = co-owner only, #28/#42).
    foreach (['accountant', 'view_only'] as $role) {
        $this->actingAs(ownerSubUser($s, $a['owner']->id, $role))
            ->post(route('owner.payouts.preferences'), ['frequency' => 'weekly', 'payout_day' => 1, 'minimum' => '0'])
            ->assertForbidden();
    }

    // The write never widened the set — still exactly one preference row, still the co-owner's values.
    expect(OwnerPayoutPreference::query()->count())->toBe(1)
        ->and(OwnerPayoutPreference::query()->first()->frequency)->toBe('twice_monthly');
});

// ── 6. Payout-history CSV — owner-scoped, EXPORTS-gated, never another owner's payouts ─────────────
it('exports the payout-history CSV owner-scoped and EXPORTS-gated', function () {
    $s = seedScenario(2);
    $a = payoutOwner($s, 0, 20000, pay: true);   // €170.00
    payoutOwner($s, 1, 50000, pay: true);        // €425.00 (distinctive)

    $csv = $this->actingAs($a['user'])->get(route('owner.payouts.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->streamedContent();

    expect($csv)->toContain('Amount')          // header
        ->toContain('€170.00')                 // A's real payout
        ->not->toContain('€425.00');           // never B's

    // EXPORTS capability: accountant may download; view-only may NOT.
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->get(route('owner.payouts.export'))->assertOk();
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'view_only'))->get(route('owner.payouts.export'))->assertForbidden();
});
