<?php

declare(strict_types=1);

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;

if (! function_exists('purWorld')) {
    /**
     * A real owner with a real finalized statement and a real PAID payout — the world both payout-reversal
     * suites measure against.
     *
     * ⚠️ **MOVED HERE 2026-09-23 (REPAIR 01).** It lived in `PayoutReversalIsUnguardedTest` until a SECOND file
     * needed it — `PayoutReversalRepairedTest`, which proves the two defects GONE rather than contained. ⚠️ This
     * project's own rule, already written on `ownerRoleUser`: **a `function` declared in one Pest file is only
     * loaded when THAT file loads, so running a sibling in isolation fatals on an undefined function.** One
     * definition, in Support, is the fix — and it keeps the two files agreeing about what a paid payout IS.
     *
     * @return array{statement: OwnerStatement, paid: Payout, payouts: OwnerPayoutService}
     */
    function purWorld(): array
    {
        $s = seedScenario(1);
        $owner = Owner::factory()->create(['name' => 'Reversal Probe', 'payout_currency' => 'EUR', 'status' => 'active']);
        $unit = $s['units'][0];
        $unit->update(['owner_id' => $owner->id]);
        ManagementAgreement::factory()->create([
            'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
        ]);

        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, 20000);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);

        $statements = app(OwnerStatementService::class);
        $statement = $statements->finalize($statements->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

        $payouts = app(OwnerPayoutService::class);
        $paid = $payouts->pay($payouts->createFromStatement($statement));

        return ['statement' => $statement, 'paid' => $paid, 'payouts' => $payouts];
    }
}
