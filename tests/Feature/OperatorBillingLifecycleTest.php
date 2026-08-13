<?php

use App\Models\Plan;
use App\Models\Reservation;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\RateAndRestrictionService;
use App\Support\Billing\SubscriptionPricing;
use App\Support\Money\Money;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Operator BILLING exact-match (#85) — the real per-unit subscription FIGURE (reconciling) + the REAL
// pause/cancel state machine that GENUINELY gates the workspace read-only (the shared gate the
// Dashboard/Calendar/certified-write consume). No real charge; no fabricated card/invoice (#39).

/** An org on a Pro subscription (channels + management view) with a real nightly rate. */
function billScenario(): array
{
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    $plan = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
    Subscription::create(['organization_id' => $s['org']->id, 'plan_id' => $plan->id, 'status' => 'active', 'current_period_end' => now()->addMonth()]);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of(12000, 'EUR'));

    return $s;
}

/** The read-only gate evaluated inside the org's tenant context. */
function billIsReadOnly(array $s): bool
{
    return app(TenantContext::class)->forOrganization((string) $s['org']->id, fn () => app(OperatorWorkspace::class)->isReadOnly());
}

// ── 1. The subscription figure is REAL + reconciles (units × per-unit × VAT; net + VAT == gross) ───
it('computes the per-unit subscription figure from the real unit count, reconciling', function () {
    $s = seedScenario(3);   // 3 active units
    app(TenantContext::class)->forOrganization((string) $s['org']->id, function () {
        $p = SubscriptionPricing::current('monthly');

        expect($p['units'])->toBe(3)
            ->and($p['net_minor'])->toBe(3 * (int) config('billing.per_unit_minor'))   // units × per-unit
            ->and($p['vat_minor'])->toBe((int) round($p['net_minor'] * $p['vat_pct'] / 100))
            ->and($p['gross_minor'])->toBe($p['net_minor'] + $p['vat_minor']);          // #17 reconciles
    });
});

// ── 2. 🔒 PAUSE genuinely gates the workspace read-only (mutation-proven) ──────────────────────────
it('pauses the workspace read-only and refuses new bookings, then resumes', function () {
    $s = billScenario();
    $owner = operatorUser($s, ['name' => 'Marco Reyes'], 'Property Owner');   // billing.manage + reservations.create

    expect(billIsReadOnly($s))->toBeFalse();

    // PAUSE → a real future paused_until → read-only.
    $this->actingAs($owner)->post('/app/billing/pause', ['months' => 3])->assertRedirect();
    expect($s['org']->fresh()->paused_until)->not->toBeNull()
        ->and(billIsReadOnly($s))->toBeTrue();

    // A new booking is REFUSED while paused (the certified write path guard) — nothing created.
    $before = Reservation::query()->count();
    $this->actingAs($owner)->post('/app/reservations/create', [
        'check_in' => '2026-09-02', 'check_out' => '2026-09-05',
        'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id,
        'guest_first_name' => 'Grace', 'guest_last_name' => 'Hopper', 'adults' => '2', 'source' => 'direct',
    ])->assertRedirect();
    expect(Reservation::query()->count())->toBe($before);   // 🔒 read-only enforced

    // RESUME → clears the pause → live again.
    $this->actingAs($owner)->post('/app/billing/resume')->assertRedirect();
    expect($s['org']->fresh()->paused_until)->toBeNull()
        ->and(billIsReadOnly($s))->toBeFalse();
});

// ── 3. CANCEL schedules read-only from the paid-through date (mutation-proven both sides) ──────────
it('cancels to read-only from the paid-through date — full access until then, read-only after', function () {
    $s = billScenario();
    $owner = operatorUser($s, ['name' => 'Marco Reyes'], 'Property Owner');

    // CANCEL → readonly_from = current_period_end (future) → still FULL access now.
    $this->actingAs($owner)->post('/app/billing/cancel')->assertRedirect();
    $org = $s['org']->fresh();
    expect($org->readonly_from)->not->toBeNull()
        ->and($org->readonly_from->toDateString())->toBe($org->currentSubscription()->current_period_end->toDateString())
        ->and(billIsReadOnly($s))->toBeFalse();   // paid-through date is future → not yet read-only

    // Once the paid-through date has passed → read-only (mutation-proven).
    $org->update(['readonly_from' => now()->subDay()]);
    expect(billIsReadOnly($s))->toBeTrue();
});

// ── 4. The management view is REAL + HONEST — real figure, no fabricated card/paid-invoice ────────
it('renders the management view with a real figure and honest (never fabricated) card + invoices', function () {
    $s = billScenario();
    $owner = operatorUser($s, ['name' => 'Marco Reyes'], 'Property Owner');

    $this->actingAs($owner)->get('/app/billing')
        ->assertOk()
        ->assertSee('What you’re paying for', false)
        // ⚠️ AMENDED at BILLING PASS 3. This asserted 'incl. 23% VAT' on every org. That line belongs to the
        // PER-UNIT view (net + VAT), and this fixture's org is on a FLAT tier — so the assertion was pinning
        // part of the §N.8d display the pass exists to remove: a flat tier price is quoted AS the price, and
        // there is no net/VAT split behind it to disclose.
        //
        // ⚠️ G8 was considered before changing it: does it encode a real invariant? The invariant it was
        // reaching for — "the figure is real and its VAT treatment is disclosed, never silently flipped" — is
        // preserved below and in BillingPageShapeTest, which asserts the VAT line appears for a per-unit plan
        // and its absence for a flat one. What is dropped is only the claim that EVERY org sees a VAT split.
        ->assertSee('/mo')                     // a real figure still renders
        ->assertDontSee('incl. 23% VAT')       // …but not the per-unit VAT split, on a FLAT tier
        ->assertSee('No card on file')         // HONEST — card is account-gated
        ->assertSee('Free')                    // the real trial invoice (€0.00)
        // 🔒 #39 — never a fabricated card or paid invoice:
        ->assertDontSee('····4242')
        ->assertDontSee('Visa ');
});
