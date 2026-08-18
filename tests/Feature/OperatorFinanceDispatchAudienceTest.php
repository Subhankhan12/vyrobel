<?php

declare(strict_types=1);

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\User;
use App\Notifications\Operator\StatementFinalizedNotification;
use App\Notifications\Owner\StatementReadyNotification;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR FIX 3 (A) — the finance-notification DISPATCH AUDIENCE (operator deep audit, run 21 + run 36).
//
// ⚠️⚠️ THE LEAK WAS NEVER A SCOPE BUG, AND THAT IS WHY THE FIX IS HERE AND NOT ON THE ROUTE. Run 21 proved
// every user genuinely owns their own notification rows (self-scoped by `notifiable_id`). A housekeeper saw an
// owner's net payable because the Finance-group notifications were ADDRESSED to all eleven back-office roles —
// `operatorsForOrg()` is one bucket. Gating `/app/notifications` would have blocked the operational
// notifications that same housekeeper legitimately owns, while leaving the finance rows sitting in their feed.
//
// ⚠️ The permission is not a guess: both finance notifications link to `/app/owners`, which OP-FIX-2 gated on
// `reports.view`. A recipient must be able to open the page their notification points at.

/** An owner with a unit, an agreement and a portal user — the fixture a real statement needs. */
function fdaOwner(array $s, int $unitIndex, string $name): array
{
    $owner = Owner::factory()->create(['name' => $name, 'payout_currency' => 'EUR', 'status' => 'active']);
    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00,
        'effective_from' => '2026-01-01', 'effective_to' => null,
    ]);

    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    return ['owner' => $owner, 'user' => $user, 'unit' => $unit];
}

/** Book → charge → attribute → finalize: fires the REAL `owner_statement.finalized` domain event. */
function fdaFinalize(array $s, array $o): OwnerStatement
{
    $reservation = book($s, ['unit_id' => $o['unit']->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    $svc = app(OwnerStatementService::class);

    return $svc->finalize($svc->generateDraft($o['owner']->id, '2026-09-01', '2026-09-30'));
}

function fdaCount(User $user, string $type): int
{
    return $user->fresh()->notifications()->where('type', $type)->count();
}

// ── ⚠️⚠️ THE AUDIENCE, BOTH DIRECTIONS, THROUGH THE REAL EVENT ────────────────────────────────────────
it('addresses a finalized statement only to back-office roles that may read owner finance', function () {
    $s = seedScenario(1);

    // Roles WITH `reports.view` — they must still be told. Over-narrowing would be a worse defect than the
    // leak: a finance notification nobody receives is a silently broken workflow.
    $manager = operatorUser($s, ['name' => 'Entitled Manager'], 'Hotel Manager');
    $accountant = operatorUser($s, ['name' => 'Entitled Accountant'], 'Accountant');

    // Roles WITHOUT it — three of them, deliberately. Finding 6 was invisible because a role that is never
    // rendered is where a defect hides, and Housekeeping alone would not have shown that Front Desk and
    // Maintenance are excluded for the same reason.
    $housekeeper = operatorUser($s, ['name' => 'Excluded Housekeeper'], 'Housekeeping');
    $desk = operatorUser($s, ['name' => 'Excluded Front Desk'], 'Front Desk');
    $maintenance = operatorUser($s, ['name' => 'Excluded Maintenance'], 'Maintenance');

    $owner = fdaOwner($s, 0, 'Amara Okafor');
    fdaFinalize($s, $owner);

    // ⚠️ POSITIVE HALF — the notification really fired and really reached the entitled roles. Without this a
    // dispatch that broke entirely would pass the negative half perfectly.
    expect(fdaCount($manager, StatementFinalizedNotification::class))->toBe(1)
        ->and(fdaCount($accountant, StatementFinalizedNotification::class))->toBe(1);

    // ⚠️ NEGATIVE HALF — the money never reaches a role that may not read it, because it is never SENT.
    expect(fdaCount($housekeeper, StatementFinalizedNotification::class))->toBe(0)
        ->and(fdaCount($desk, StatementFinalizedNotification::class))->toBe(0)
        ->and(fdaCount($maintenance, StatementFinalizedNotification::class))->toBe(0);

    // ⚠️ AND THE OWNER'S OWN NOTIFICATION IS UNTOUCHED — narrowing the OPERATOR audience must not disturb the
    // owner realm, which is addressed by a different path entirely.
    expect(fdaCount($owner['user'], StatementReadyNotification::class))->toBe(1);
});

// ── ⚠️ #28 — narrowing WHO receives it did not change WHAT it says ─────────────────────────────────────
it('leaves the finance notification body free of guest contact', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, ['name' => 'Reader'], 'Hotel Manager');

    $s['guest']->forceFill([
        'email' => 'fda.guest@example.test',
        'phone' => '+351 912 999 888',
        'first_name' => 'Zephyrine',
        'last_name' => 'Quillfeather',
    ])->save();

    $owner = fdaOwner($s, 0, 'Amara Okafor');
    fdaFinalize($s, $owner);

    $row = $manager->fresh()->notifications()->where('type', StatementFinalizedNotification::class)->first();
    expect($row)->not->toBeNull();                       // the premise — there IS a body to inspect

    $blob = json_encode($row->data) ?: '';
    expect($blob)->not->toContain('fda.guest@example.test')
        ->and($blob)->not->toContain('+351 912 999 888')
        ->and($blob)->not->toContain('Zephyrine')
        ->and($blob)->not->toContain('Quillfeather')
        // …while still carrying the operator-useful content it always did
        ->and($blob)->toContain('Statement finalized');
});
