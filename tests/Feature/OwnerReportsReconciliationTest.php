<?php

use App\Livewire\Owner\OwnerReports;
use App\Models\Membership;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Services\TaxRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ⛔ P2-2 (owner-realm audit) — the Reports "Gross to net" must RECONCILE (#17) and AGREE with the annual
// tax summary once WITHHOLDING (#73) / payment_fee (#70) are configured. The breakdown omitted both buckets,
// so its `net` line silently failed to add up the moment either posted (and the computed reconciles flag was
// never read by the view). This asserts reconciliation UNDER the breaking condition (a configured withholding
// rate) — so it FAILS on the pre-fix code and HOLDS after.

it('reconciles the Reports gross-to-net when a withholding rate is configured', function () {
    $s = seedScenario(1);

    $owner = ownUnit($s['units'][0]);            // owner + 15% agreement
    $owner->update(['tax_country' => 'PT']);
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    // A real 25% withholding rate → the finalize posts a −4250 withholding line (pre-withholding net 17000 → 12750).
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_WITHHOLDING, 25.0, '2026-01-01');
    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    expect((int) $statement->withholding_minor)->toBe(-4250);   // the breaking condition really exists

    $g2n = Livewire::actingAs($user)->test(OwnerReports::class)->viewData('grossToNet');

    // 🔒 #17 — every entry_type (incl. withholding) is now bucketed, so the breakdown sums to net…
    expect($g2n['reconciles'])->toBeTrue()
        // …and the withholding is surfaced as its own real line (agreeing with the Tax Center / annual summary).
        ->and($g2n['has_withholding'])->toBeTrue()
        ->and($g2n['withholding'])->toContain('42.50');   // −€42.50 withheld, the real figure
});
