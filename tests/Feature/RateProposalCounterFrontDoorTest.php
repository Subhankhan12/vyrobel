<?php

use App\Models\Membership;
use App\Models\RateProposal;
use App\Models\RoomType;
use App\Models\User;
use App\Services\RateProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ F1 · THE COUNTER-OFFER — TWO DEFECTS, AND WHY EITHER ALONE WOULD BE WORSE ═════════════════════
//
// ⚠️⚠️ THE ROUTE ALWAYS WORKED. `app.rate-proposals.accept-counter` was registered, gated
// `can:rates.manage`, and backed by a complete `RateProposalService::acceptCounter()`. A test that POSTs
// to it would have passed before this pass and proves NOTHING — that is the entire H-2 lesson. So every
// assertion below reads the RENDERED PAGE and the mutations remove the CONTROL, never the route.
//
// ⚠️ THE SECOND DEFECT WAS WORSE THAN THE MISSING BUTTON. `proposalRows()` built its summary from
// `proposed_rate_minor` in every state, so a COUNTERED proposal displayed the operator's OWN asking price
// with "Status: Countered" beneath it. Not a blank — a STALE NUMBER RENDERED CONFIDENTLY. Adding a button
// without fixing that would have handed an operator a money decision showing the wrong figure.

/** An owner with an exclusively-owned room type, a pending proposal, and the owner's counter on it. */
function f1Scenario(int $proposedMinor = 20000, int $counterMinor = 19000): array
{
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    $operator = operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner');
    $owner = ownUnit($s['units'][0]);

    // ⚠️ `ownUnit()` builds no linked login; `counter()` stamps `countered_by`, so one is needed.
    $ownerUser = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $org, 'user_id' => $ownerUser->id, 'status' => 'active']);
    $owner->forceFill(['user_id' => $ownerUser->id])->save();

    $roomType = RoomType::query()->findOrFail($s['units'][0]->room_type_id);

    $svc = app(RateProposalService::class);
    $proposal = $svc->propose(
        $operator, (string) $owner->id, (string) $roomType->id, $proposedMinor,
        null, null, '2026-09-01', '2026-09-30', null
    );

    return compact('s', 'org', 'operator', 'owner', 'ownerUser', 'roomType', 'proposal', 'counterMinor');
}

function f1Page(array $c): string
{
    return test()->actingAs($c['operator'])
        ->get(route('app.owners.show', $c['owner']->id))
        ->assertOk()
        ->getContent();
}

// ── ⚠️⚠️ THE NUMBER — the owner's counter must be the one on screen ──────────────────────────────
it('⚠️ shows the owner’s counter, not the operator’s original ask', function () {
    $c = f1Scenario(proposedMinor: 20000, counterMinor: 19000);

    // ⚠️ Before the counter, the operator's own figure is correct and must still show.
    expect(f1Page($c))->toContain('200.00');

    app(RateProposalService::class)->counter(
        $c['proposal']->fresh(), $c['ownerUser'], $c['counterMinor'], null
    );

    $html = f1Page($c);

    // ⚠️⚠️ THE HEADLINE SPECIFICALLY, NOT ANYWHERE ON THE PAGE. A bare `toContain('190.00')` passed even
    //    with the headline reverted to the stale ask, because the counter also appears in the sub-line —
    //    the assertion could not tell the two apart, so the mutation came back falsely clean. This reads
    //    the `vy-um-name` row title, which is the figure an operator's eye lands on.
    // ⚠️ SCOPED TO THE RATE-PROPOSALS CARD: `vy-um-name` is shared with the unit rows above, so an
    //    unscoped match read "U1" and asserted nothing about a proposal at all.
    $card = substr($html, (int) strpos($html, 'Rate proposals'));
    preg_match('/<div class="vy-um-name">(.*?)<\/div>/s', $card, $headline);
    expect($headline)->not->toBeEmpty();
    expect($headline[1])->toContain('190.00');
    expect($headline[1])->not->toContain('200.00');   // the stale ask must not be the headline

    // ⚠️ And the original is kept BESIDE it rather than replaced — an operator needs both to decide.
    expect($html)->toContain('owner countered at');
    expect($html)->toContain('you proposed');
});

// ── ⚠️⚠️ THE CONTROL — proven from the rendered page, named unambiguously ────────────────────────
//
// ⚠️ The assertion names the ACCEPT-COUNTER ROUTE URL, not a word like "Accept" that an empty state or an
// unrelated button could also satisfy. H-2's first mutation was a false green for exactly that reason.
it('⚠️ puts an accept-counter control on the page, addressed to the accept-counter route', function () {
    $c = f1Scenario();
    $proposal = $c['proposal']->fresh();

    // Not offered while the proposal is merely pending — there is nothing to accept yet.
    expect(f1Page($c))->not->toContain(route('app.rate-proposals.accept-counter', $proposal->id));

    app(RateProposalService::class)->counter($proposal, $c['ownerUser'], $c['counterMinor'], null);

    $html = f1Page($c);
    expect($html)->toContain(route('app.rate-proposals.accept-counter', $c['proposal']->id));
    // ⚠️ The amount is ON the button, so the decision names its own number.
    expect($html)->toContain('Accept €190.00');
});

// ── ⚠️ AND THE DOOR REALLY OPENS — the control's target applies the OWNER'S rate ─────────────────
//
// ⚠️ MONEY PATH. This asserts the effect of the button's own route, so a control that pointed somewhere
// harmless would not satisfy it.
it('⚠️ applies the owner’s counter — not the operator’s ask — when the control is used', function () {
    $c = f1Scenario(proposedMinor: 20000, counterMinor: 19000);
    app(RateProposalService::class)->counter($c['proposal']->fresh(), $c['ownerUser'], $c['counterMinor'], null);

    test()->actingAs($c['operator'])
        ->post(route('app.rate-proposals.accept-counter', $c['proposal']->id))
        ->assertRedirect();

    $after = $c['proposal']->fresh();
    expect($after->state)->toBe(RateProposal::STATE_APPROVED);
    expect((int) $after->countered_rate_minor)->toBe(19000);
});
