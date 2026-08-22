<?php

declare(strict_types=1);

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Notifications\Operator\StatementFinalizedNotification;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ⚠️⚠️ THE CROSS-BRANCH INVARIANT — and merged main is the FIRST place it can be checked end to end.
//
// OP-FIX-3 narrowed the finance-notification audience to `reports.view` BECAUSE OP-FIX-2 gated `app.owners`
// — the URL those notifications carry — on `reports.view`. On either branch alone that reasoning is sound but
// only half-present: OP-FIX-3's branch had the narrowing without the gate it was derived from.
//
// ⚠️ THE PROPERTY: a finance notification is delivered to EXACTLY the roles that can OPEN the page it links
// to. A recipient who would 403 on their own notification's link is the dead-affordance defect this audit
// fixed at the control layer (OP-FIX-1) and the route layer (OP-FIX-2) — it must not survive at the
// addressing layer. This test makes the two sides fail together if either drifts.

/** Every back-office role, so the invariant is checked across the whole actor set, not a sample. */
const FAA_ROLES = [
    'Property Owner', 'Group Admin', 'Hotel Manager', 'Revenue Manager', 'Front Desk',
    'Reservation Agent', 'Housekeeping', 'Maintenance', 'Accountant', 'Marketing Manager',
    'Guest Support Agent',
];

it('delivers a finance notification to exactly the roles that can open the page it links to', function () {
    $s = seedScenario(1);

    // One user per back-office role — including the seven the demo has no account for, which is the whole
    // reason the audit could not settle this by rendering.
    $users = [];
    foreach (FAA_ROLES as $role) {
        $users[$role] = operatorUser($s, ['name' => $role.' User'], $role);
    }

    // A real owner + a real finalized statement → the REAL `owner_statement.finalized` domain event.
    $owner = Owner::factory()->create(['name' => 'Amara Okafor', 'payout_currency' => 'EUR', 'status' => 'active']);
    $unit = $s['units'][0];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00,
        'effective_from' => '2026-01-01', 'effective_to' => null,
    ]);
    $ownerUser = User::factory()->create(['name' => 'Amara Okafor', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $ownerUser->id, 'status' => 'active']);
    $owner->update(['user_id' => $ownerUser->id]);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    $received = [];
    $canOpen = [];

    foreach (FAA_ROLES as $role) {
        $user = $users[$role]->fresh();

        $received[$role] = $user->notifications()
            ->where('type', StatementFinalizedNotification::class)->count() > 0;

        // The page the notification's own `url` points at.
        $canOpen[$role] = $this->actingAs($user)->get(route('app.owners'))->getStatusCode() === 200;
    }

    // ⚠️ THE PROBE — both sides must be non-trivial. An all-false `received` (dispatch broken) or an all-true
    // `canOpen` (gate missing) would make the equality below pass while proving nothing.
    expect(array_filter($received))->not->toBeEmpty()
        ->and(array_filter($received))->not->toHaveCount(count(FAA_ROLES))
        ->and(array_filter($canOpen))->not->toBeEmpty()
        ->and(array_filter($canOpen))->not->toHaveCount(count(FAA_ROLES));

    // ⚠️⚠️ THE INVARIANT: addressed == entitled, role by role.
    expect($received)->toBe($canOpen);

    // …and named explicitly, so a drift reads as a fact rather than a diff of two booleans.
    expect($received['Hotel Manager'])->toBeTrue()
        ->and($received['Accountant'])->toBeTrue()
        ->and($received['Housekeeping'])->toBeFalse()
        ->and($received['Front Desk'])->toBeFalse()
        ->and($received['Maintenance'])->toBeFalse();
});
