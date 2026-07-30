<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 1 (Owner Agreement) — a NEW owner-scoped, READ-ONLY page over the certified
// management_agreements table. Locks its PRESENTATION guarantees, and above all its ISOLATION:
// `management_agreements` carries BelongsToOrganization ONLY, so OwnerScope does NOT protect it — the
// component's EXPLICIT whereIn(owner_id) is the single thing standing between one owner and every other
// owner's commercial terms inside the same tenant. Also locked: nullable columns render honestly (never
// "0%"), only the real status enum is used, and the non-existent e-signature/amendment flow is absent.

/** A user linked to a fresh owner (with a unit) + that owner's governing agreement. */
function agreementOwner(array $s, int $unitIndex, array $overrides = []): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);

    $agreement = ManagementAgreement::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'unit_id' => null,           // portfolio-wide
        'commission_pct' => 15.00,
        'effective_from' => '2026-01-01',
        'effective_to' => null,      // open-ended → in effect today
        'status' => 'active',
    ], $overrides));

    return compact('user', 'owner', 'unit', 'agreement');
}

beforeEach(function () {
    // "In effect today" is date-dependent — freeze the clock so the fixtures below are deterministic.
    // Laravel's TestCase::tearDown() resets Carbon's test-now after each test.
    Carbon::setTestNow(Carbon::parse('2026-07-16 12:00:00'));
});

// ── 1. The REAL current agreement + no invented signing flow ─────────────────────────────────────
it('renders the real agreement in effect and never invents a signing flow', function () {
    $s = seedScenario(1);
    $a = agreementOwner($s, 0, [
        'commission_pct' => 15.00,
        'management_fee_minor' => 10000,
        'management_fee_currency' => 'EUR',
        'management_fee_interval' => 'monthly',
        'payout_currency' => 'EUR',
        'payout_schedule' => 'quarterly',
        'effective_from' => '2026-01-01',
        'effective_to' => null,
    ]);

    $this->actingAs($a['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('Active agreement')
        ->assertSee('15.00%')          // REAL commission_pct
        ->assertSee('€100.00')         // REAL management fee via money() — integer minor, never a float
        ->assertSee('Monthly')         // REAL management_fee_interval
        ->assertSee('Quarterly')       // REAL payout_schedule (distinct from the fee interval)
        ->assertSee('Portfolio-wide')  // unit_id null = the whole portfolio
        ->assertSee('1 Jan 2026')      // REAL effective_from
        ->assertSee('EUR')             // REAL payout currency
        ->assertSee('Active')          // the REAL status enum
        // ⚠️ the wireframe's FAKE e-signature copy is never used — the built flow says "acknowledge", and
        // with no amendment on file no amendment card renders at all (#82).
        ->assertDontSee('Review & sign')
        ->assertDontSee('ready to acknowledge');

    // The rendered terms are the stored row, to the cent / to the digit.
    $stored = ManagementAgreement::query()->find($a['agreement']->id);
    expect((int) $stored->management_fee_minor)->toBe(10000)
        ->and((string) $stored->commission_pct)->toBe('15.00')
        ->and((string) $stored->payout_schedule)->toBe('quarterly');
});

// ── 2. ⚠️ THE LEAK TEST — OwnerScope does NOT cover management_agreements ────────────────────────
it('scopes agreements explicitly, hides another owner in the same tenant, and fails closed', function () {
    $s = seedScenario(2);
    $a = agreementOwner($s, 0, ['commission_pct' => 15.00]);
    $b = agreementOwner($s, 1, ['commission_pct' => 22.75]);   // distinctive — must never reach A

    // Both rows live in ONE tenant and NEITHER is owner-scoped: a tenant-scoped query sees them all.
    // Only the component's explicit whereIn(owner_id) keeps B's commercial terms off A's portal.
    expect(ManagementAgreement::query()->count())->toBe(2);

    $this->actingAs($a['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('15.00%')
        ->assertDontSee('22.75');

    $this->actingAs($b['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('22.75%')
        ->assertDontSee('15.00');

    // A non-linked authenticated member (Membership, no Owner) resolves to an EMPTY owner scope →
    // the empty state, never every owner's terms.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/agreement')
        ->assertOk()
        ->assertSee('No active agreement on file')
        ->assertDontSee('15.00')
        ->assertDontSee('22.75');
});

// ── 3. Nullable columns render honestly — an unset commission is NOT "0%" ────────────────────────
it('renders unset terms as "Not set" and never as a zero figure', function () {
    $s = seedScenario(1);
    $a = agreementOwner($s, 0, [
        'commission_pct' => null,
        'management_fee_minor' => null,
        'management_fee_currency' => null,
        'management_fee_interval' => null,
    ]);

    $this->actingAs($a['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('Active agreement')
        ->assertSee('Not set')
        ->assertSee('no commission rate is set')
        // ⚠️ a null commission/fee must never be presented as an agreed zero.
        ->assertDontSee('0.00%')
        ->assertDontSee('€0.00');
});

// ── 4. History — real past/ended terms, with the real status enum only ───────────────────────────
it('renders earlier and upcoming agreements in history using the real status enum only', function () {
    $s = seedScenario(1);
    $a = agreementOwner($s, 0, ['commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    // A REAL ended agreement — a closed past period, status='ended'.
    ManagementAgreement::factory()->create([
        'owner_id' => $a['owner']->id,
        'unit_id' => null,
        'commission_pct' => 9.50,
        'effective_from' => '2024-01-01',
        'effective_to' => '2024-12-31',
        'status' => 'ended',
    ]);

    // A REAL future-dated agreement — status is still 'active', but it is NOT in effect today.
    ManagementAgreement::factory()->create([
        'owner_id' => $a['owner']->id,
        'unit_id' => null,
        'commission_pct' => 11.25,
        'effective_from' => '2027-01-01',
        'effective_to' => null,
        'status' => 'active',
    ]);

    $this->actingAs($a['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('Agreement history')
        ->assertSee('9.50%')            // the REAL ended agreement
        ->assertSee('Ended')            // the REAL status enum
        ->assertSee('1 Jan 2024')
        ->assertSee('31 Dec 2024')
        ->assertSee('Ran to 31 Dec 2024')  // factual DATE context, not an invented phase
        ->assertSee('11.25%')           // the REAL future agreement
        ->assertSee('Starts 1 Jan 2027')
        ->assertSee('15.00%')           // the in-effect agreement still headlines
        // the design's non-existent lifecycle vocabulary must not be invented as a status
        ->assertDontSee('Expired')
        ->assertDontSee('Renewed')
        ->assertDontSee('Pending signature');
});

// ── 5. Scope + attribution overrides — REAL unit + REAL stored override keys only ────────────────
it('renders a per-unit agreement against its real unit and lists only the stored attribution overrides', function () {
    $s = seedScenario(1);
    $a = agreementOwner($s, 0, [
        'commission_pct' => 15.00,
        // attribution_rules is OVERRIDES ONLY — a missing key means the engine default applies.
        'attribution_rules' => ['ota_cost_bearer' => 'owner', 'cleaning_fee_bearer' => 'operator'],
    ]);

    // A REAL per-unit agreement on top of the portfolio-wide one (most-specific-wins is the certified
    // attribution engine's job — here the row is only DISPLAYED, never resolved).
    ManagementAgreement::factory()->forUnit($a['unit']->id)->create([
        'owner_id' => $a['owner']->id,
        'commission_pct' => 12.25,
        'effective_from' => '2026-02-01',
        'effective_to' => null,
        'status' => 'active',
    ]);

    $this->actingAs($a['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('Portfolio-wide')          // the portfolio-wide agreement headlines
        ->assertSee('Terms by villa')          // the wireframe's per-villa surface (#82)
        ->assertSee($a['unit']->code)          // the per-villa row names its REAL owner-scoped unit
        ->assertSee('12.25%')                  // the REAL per-unit commission (resolveFor — the same the engine uses)
        // the REAL stored override keys/values — and nothing else
        ->assertSee('OTA Cost Bearer')
        ->assertSee('Cleaning Fee Bearer')
        ->assertSee('Operator')
        ->assertDontSee('Standard attribution')   // this agreement DOES carry overrides
        ->assertDontSee('Addon Bearer');          // never stored → never listed as an agreed term
});

// ── 6. No overrides stored → the honest "standard attribution", not an invented rule list ────────
it('says standard attribution when the agreement stores no overrides', function () {
    $s = seedScenario(1);
    $a = agreementOwner($s, 0, ['attribution_rules' => null]);

    $this->actingAs($a['user'])->get('/owner/agreement')
        ->assertOk()
        ->assertSee('Standard attribution')
        ->assertDontSee('OTA Cost Bearer')
        ->assertDontSee('Cleaning Fee Bearer');
});

// ── 7. No unrendered Blade leaks to the owner ────────────────────────────────────────────────────
// See assertNoRawBlade(): a directive whose `@` follows a word character ships raw source to the page
// as ordinary text, which assertSee/assertDontSee cannot catch. Real bug found in the browser on the
// Expenses screen (Wave 1 · Sub-gate 1); guarded here too.
it('renders no raw Blade directives', function () {
    $s = seedScenario(1);
    $a = agreementOwner($s, 0);

    assertNoRawBlade($this->actingAs($a['user'])->get('/owner/agreement')->assertOk());
});
