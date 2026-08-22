<?php

/*
 * DO-CORRECTNESS-1 · FIX 1 — THE OWNER'S PAYOUT BADGE TELLS THE TRUTH ABOUT MONEY IN FLIGHT (2026-08-15).
 *
 * ⚠️ THE DEFECT. `FormatsOwnerData::payoutStatusMeta()`'s `default` arm swallowed TWO real enum members:
 * `processing` (a Connect transfer INITIATED and awaiting its webhook) and `held` (the tax-hold blocking a
 * payout). Both badged as **"Payout pending"** — so an owner whose money was already moving, or was blocked
 * on an action only they can take, read "not started yet". The amounts were right; the sentence about them
 * was wrong, which is the harder kind to notice.
 *
 * ⚠️ WHY NO PASS EVER SAW IT — THE UNRENDERED SIBLING BRANCH (§N.13.1). A `match` renders exactly one arm per
 * page load, and the demo has never held a `processing` or `held` payout (the register logs that separately as
 * DO17-3). Every browser pass over the Payouts, Documents, Dashboard and Statement pages was therefore looking
 * at the `paid`/`pending` arms and could not have seen this — "the page looks fine" was true and meaningless.
 * ⚠️ **So this file must reach the two states off the demo path, and it does so in BOTH available ways:**
 * by calling the map directly for every enum member, and by driving the real engine until a payout is
 * genuinely `held` and then reading the rendered page.
 *
 * ⚠️ THE LABELS ARE NOT INVENTED. `OwnerBalance::rowState()` has always called these two states "In transit"
 * and "On hold". The clearing model was right; the badge map lagged. The fix CONVERGES the lagging copy on
 * the existing definition — a third vocabulary for one concept is how drift starts (§N.8f).
 *
 * ⚠️ WHAT THIS FIX DID NOT TOUCH: when the states are SET. `OwnerPayoutService::pay()` (`processing`) and
 * `hold()` (`held`) are byte-identical to before. A label fix must not become a money fix.
 */

use App\Livewire\Operator\OperatorOwnerDetail;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Services\PayoutRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The map under test, reached the way the real components reach it (the trait), not re-implemented here.
 * ⚠️ Prefixed helper name — a top-level function in a Pest file is GLOBAL and has collided before.
 */
function fix1Meta(string $status): array
{
    $host = new class
    {
        use FormatsOwnerData {
            payoutStatusMeta as public pub;
        }
    };

    return $host->pub($status);
}

it('⚠️ FIX 1 — labels EVERY payout enum member accurately; processing and held are no longer "pending"', function () {
    // ⚠️ NON-VACUITY FIRST, AND IT READS THE SOURCE (not a pinned constant — DO-RESTORE-2's trap). The two
    // states must be EXPLICIT arms; a guard that only checked the output would still pass if someone widened
    // `default` to return "In transit" for everything.
    $source = (string) file_get_contents(app_path('Livewire/Owner/Concerns/FormatsOwnerData.php'));
    expect($source)->toContain("'processing' => ['label' => 'In transit'")
        ->and($source)->toContain("'held' => ['label' => 'On hold'");

    // ⚠️ THE TWO BRANCHES THE DEMO NEVER RENDERS.
    expect(fix1Meta('processing')['label'])->toBe('In transit')
        ->and(fix1Meta('held')['label'])->toBe('On hold');

    // ⚠️ AND NEITHER MAY READ AS NOT-STARTED. This is the actual defect, asserted as itself rather than as a
    // string equality that a future rename could satisfy while re-introducing the meaning.
    foreach (['processing', 'held'] as $inFlight) {
        expect(strtolower(fix1Meta($inFlight)['label']))->not->toContain('pending');
    }

    // The arms that were already right stay right — a fix that quietly moved `paid` would be a regression.
    expect(fix1Meta('paid')['label'])->toBe('Paid')
        ->and(fix1Meta('failed')['label'])->toBe('Payout failed')
        ->and(fix1Meta('reversed')['label'])->toBe('Reversed')
        ->and(fix1Meta('pending')['label'])->toBe('Payout pending');

    // ⚠️ EVERY declared enum member now resolves to a NAMED arm. Computed from the model's own docblock rather
    // than a list typed here, so adding a seventh status to the enum and forgetting the map fails HERE.
    $declared = [];
    preg_match('/`status` spans the full lifecycle enum \(([^)]+)\)/', (string) file_get_contents(app_path('Models/Payout.php')), $m);
    preg_match_all('/`(\w+)`/', $m[1] ?? '', $found);
    $declared = $found[1] ?? [];
    expect($declared)->not->toBeEmpty('the Payout enum docblock changed shape — this guard reads it');

    $unnamed = array_values(array_filter($declared, fn (string $s): bool => ! str_contains($source, "'".$s."' => [")));
    // `pending` is legitimately served by `default` (it IS the pending state), so it is the only allowed miss.
    expect($unnamed)->toBe(['pending'], 'a real payout status has no explicit label arm: '.implode(', ', $unnamed));
});

it('⚠️ FIX 1 — the OWNER PAGE renders "On hold" for a genuinely held payout (off the demo path)', function () {
    // ⚠️ THE STATE IS PRODUCED BY THE PRODUCTION PATH, NOT BY `update(['status' => 'held'])`. Hand-stamping a
    // status would prove the map maps; driving the real engine proves the state a real owner can actually be
    // in reaches the real page with the right word on it.
    $s = seedScenario(1);

    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    // ⚠️ No tax id → the engine's REAL tax-hold fires. That is what makes this `held` and not `paid`.
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR', 'tax_id' => null]);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL);

    // ⚠️ NON-VACUITY: the condition really was reached. Without this the assertions below could pass on a page
    // that simply has no payout row at all — the shape of vacuous guard §N.4p exists to catch.
    expect(Payout::query()->where('owner_id', $owner->id)->where('status', 'held')->count())->toBe(1);

    $this->actingAs($user)->get('/owner/payouts')
        ->assertOk()
        ->assertSee('On hold')
        // ⚠️ THE DEFECT, ASSERTED AS ABSENT. Before the fix this page said "Payout pending" over held money.
        ->assertDontSee('Payout pending');
});

it('⚠️ the two OPERATOR copies are NOT collapsed into the owner trait — the tone vocabularies do not overlap', function () {
    /*
     * ⚠️ CONFIRM-BEFORE-COLLAPSING, AND THE ANSWER WAS "DON'T". The audit found three definitions of
     * "payout status → badge" and the obvious §N.8f move is to unify them. Reading all three first showed
     * that would BREAK rendering: the two realms' badge vocabularies are disjoint.
     *
     * The owner shell defines exactly three tone classes; the operator shell defines fifteen `tone-*` classes
     * and defines NEITHER `paid` NOR `draft`. Feeding one realm's tone to the other's CSS yields an unstyled
     * badge. Same concept, two design systems — a legitimate divergence, not drift.
     *
     * This test pins that reasoning so a later "consistency" pass cannot collapse them without first
     * disproving it. ⚠️ It is a guard on a DECISION, not on behaviour (the pattern the DO-RESTORE guards use).
     */
    $ownerShell = (string) file_get_contents(resource_path('views/components/layouts/owner.blade.php'));
    $operatorShell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // The owner realm's tones exist as `.vy-status--x`; the operator's as `.vy-tag.tone-x`.
    expect($ownerShell)->toContain('.vy-status--paid')->toContain('.vy-status--draft')
        ->and($operatorShell)->toContain('.vy-tag.tone-confirmed')->toContain('.vy-tag.tone-no-show');

    // ⚠️ THE LOAD-BEARING FACT: the operator shell has no rule for the owner's two distinctive tones, so the
    // owner map's output cannot be rendered there.
    expect($operatorShell)->not->toContain('.vy-tag.tone-paid')
        ->and($operatorShell)->not->toContain('.vy-tag.tone-draft');

    // ⚠️ UPDATED 2026-08-16 — THE OPERATOR SIDE IS NOW ONE DEFINITION, WHICH IS WHAT THIS FILE ASKED FOR.
    // This block used to assert a `private function payoutStatusMeta` inside EACH operator component, because
    // that is how the map existed when the decision above was recorded. The two copies were byte-identical
    // and both of their docblocks called the remaining duplication "a real, narrower de-dup, logged rather
    // than done" — it has now been done, into the `FormatsOperatorData` trait both components already used.
    //
    // ⚠️ THE DECISION THIS TEST GUARDS IS UNCHANGED AND STILL GUARDED ABOVE: the operator realm did NOT
    // collapse into the OWNER trait, and cannot, for the tone-vocabulary reason proved a few lines up. What
    // changed is that the operator realm stopped keeping two copies of its own map — so the assertion now
    // follows the code to its single home rather than pinning a shape that no longer exists.
    $operatorMap = (new ReflectionClass(App\Livewire\Operator\PayoutRun::class))->getMethod('payoutStatusMeta');
    $otherMap = (new ReflectionClass(OperatorOwnerDetail::class))->getMethod('payoutStatusMeta');

    expect(basename((string) $operatorMap->getFileName()))->toBe('FormatsOperatorData.php')
        // ⚠️ Same file AND same line — a trait is flattened into the using class, so the declaring class name
        // alone cannot distinguish one shared definition from two identical copies.
        ->and($operatorMap->getFileName())->toBe($otherMap->getFileName())
        ->and($operatorMap->getStartLine())->toBe($otherMap->getStartLine());

    // The behaviour this block always cared about: an unknown status is ECHOED, never re-badged.
    $operatorSrc = (string) file_get_contents(app_path('Livewire/Operator/Concerns/FormatsOperatorData.php'));
    expect($operatorSrc)->toContain("default => ['label' => ucfirst(\$status)");

    // ⚠️ And no component may quietly grow its own copy back.
    foreach (['Operator/OperatorOwnerDetail.php', 'Operator/PayoutRun.php'] as $file) {
        $src = (string) file_get_contents(app_path('Livewire/'.$file));
        expect($src)->not->toContain('function payoutStatusMeta(string $status): array')
            // The stale docblock claim stays gone (the §N.8d class, in a comment).
            ->and($src)->not->toContain('the enum is EXACTLY pending|paid|failed|reversed');
    }
});
