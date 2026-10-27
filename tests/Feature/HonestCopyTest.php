<?php

declare(strict_types=1);

use App\Livewire\Operator\OperatorBilling;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 2, the copy that asserted behaviour the code does not implement
 * (#2, #6, #10, #13, #20, #22). Each is now scoped to what is genuinely true.
 *
 * ⚠️ TWO OF THESE ARE DELIBERATELY COPY-ONLY, by the founder's decision:
 *   #6  — binding the OPERATOR's direct rate write to the owner's floor/ceiling shifts control between the
 *         two parties. Deferred as a product decision; the copy now promises only the proposal path, which
 *         RateProposalService really does enforce.
 *   #10 — whether the Essential team limit should be ENFORCED belongs with the open billing decision. The
 *         limit is now displayed truthfully; nothing enforces it, and nothing here pretends otherwise.
 */

/** #10 — the billing page printed "· unlimited" for every plan, including Essential (real limit: 5). */
it('prints the plan’s REAL team limit instead of always claiming unlimited', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();

    Subscription::create([
        'organization_id' => $s['org']->id,
        'plan_id' => Plan::query()->where('code', 'essential_monthly')->firstOrFail()->id,
        'status' => 'active',
    ]);

    $counts = Livewire::actingAs(operatorUser($s, [], 'Property Owner'))
        ->test(OperatorBilling::class)
        ->viewData('counts');

    // Essential genuinely caps team at 5 — the page must say so, not "unlimited".
    expect($counts['team_limit'])->toBe(5);

    Livewire::actingAs(operatorUser($s, [], 'Property Owner'))
        ->test(OperatorBilling::class)
        ->assertSee('of 5')
        ->assertDontSee('· unlimited', false);
});

it('still says unlimited when the plan genuinely sets no limit', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();

    Subscription::create([
        'organization_id' => $s['org']->id,
        'plan_id' => Plan::query()->where('code', 'pro_monthly')->firstOrFail()->id,
        'status' => 'active',
    ]);

    // Pro's limit is null — "unlimited" is TRUE here, so it must survive. An honesty fix that made every
    // plan look capped would be its own false claim.
    expect(Livewire::actingAs(operatorUser($s, [], 'Property Owner'))
        ->test(OperatorBilling::class)
        ->viewData('counts')['team_limit'])->toBeNull();
});

/**
 * The remaining four are pure copy on Blade surfaces, asserted at the source. A rendered-page test for each
 * would need four different authenticated realms and states; the claim being fixed is the presence or
 * absence of a sentence, so that is what is pinned. All four were browser-checked in the same pass.
 */
it('no longer asserts unenforced or unqualified behaviour in owner copy', function () {
    $rates = i18nEnglishSource(resource_path('views/livewire/owner/rates.blade.php'));
    $payoutMethod = i18nEnglishSource(resource_path('views/livewire/owner/payout-method.blade.php'));
    $reports = i18nEnglishSource(resource_path('views/livewire/owner/reports.blade.php'));
    $welcome = i18nEnglishSource(resource_path('views/livewire/owner/welcome.blade.php'));

    // #6 — the guardrail governs PROPOSALS; the blanket claim is gone.
    expect($rates)->not->toContain('Every change needs your sign-off')
        ->and($rates)->toContain('proposes');

    // #2 — the 48h hold carries the same account-gated qualifier as the bullet above it.
    expect($payoutMethod)->toContain('48h as a security check — with your live banking connection');

    // #20 — the KPI is statements PLUS unstamped ledger, and says so. Asserted case-INSENSITIVELY: the
    // first pass missed a second, lower-cased copy of the same claim in the hero sub-label, which the
    // browser caught. One claim, zero survivors.
    expect(stripos($reports, 'reconciles to your statements'))->toBeFalse()
        ->and($reports)->toContain('not yet statemented');

    // #22 — the footnote cannot claim "configured" while the badge says EXAMPLE.
    // (I18N-01: the footnote branches between two KEYS on the same flag; each one's English says which rates it used)
    expect($reports)->toContain("\$vat['is_example'] ? 'livewire/owner/reports.vat_computed_example' : 'livewire/owner/reports.vat_computed_configured'")
        ->and(__('livewire/owner/reports.vat_computed_example', [], 'en'))->toContain('EXAMPLE')
        ->and(__('livewire/owner/reports.vat_computed_configured', [], 'en'))->toContain('configured')->not->toContain('EXAMPLE');

    // #13 — the heading branches like the rest of the card.
    expect($welcome)->toContain("\$hasPayout ? 'Your payout account' : 'Add your payout account'");
});
