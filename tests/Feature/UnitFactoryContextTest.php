<?php

use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Tier 1 finishing sweep · Step 4 — UnitFactory test-infra debt. The factory now respects the active
// tenant context (like ChannelFactory/OwnerFactory) instead of always minting a fresh org, while still
// honoring an explicitly passed organization_id (backward compatible).

it('builds a unit in the active tenant context, not a mismatched fresh org', function () {
    $s = seedScenario(1);   // sets TenantContext to a fresh org

    $unit = Unit::factory()->create();

    expect($unit->organization_id)->toBe($s['org']->id);   // context org, consistent with Channel/Owner factories
});

it('still honors an explicitly passed organization_id (backward compatible)', function () {
    seedScenario(1);                                   // context = org A
    $other = Organization::factory()->create();        // a different org

    $unit = Unit::factory()->create(['organization_id' => $other->id]);

    expect($unit->organization_id)->toBe($other->id);  // explicit value wins over the context fallback
});
