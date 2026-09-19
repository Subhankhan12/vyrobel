<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * F-46 — AN OWNER WITH NO STATEMENTS SEES THEIR OWN CURRENCY.
 *
 * ⚠️⚠️ F-44 WAS RECORDED AS LATENT AND WAS LIVE. FIX 22 proved 45 `?? 'EUR'` fallbacks unreachable by reading
 * the schema — every `currency` column is NOT NULL, so the branch could not fire. That evidence was correct
 * and IRRELEVANT to this one: `portfolioCurrency()` falls back on an **EMPTY COLLECTION**, not a null column,
 * and the schema cannot see an empty set.
 *
 * ⚠️ SO IT FIRES ON AN OWNER'S FIRST DAY — before any statement or payout exists — on the four screens that
 * report what they have earned. 🖥 MEASURED in tenant two: "EARNED YTD €0.00" and "NEXT PAYOUT €0.00" for an
 * owner paid in GBP.
 *
 * ⚠️ THE AMOUNT IS RIGHT AND THE SYMBOL IS WRONG — the dangerous direction, because zero looks the same in
 * every currency and gives the reader no arithmetic cue that anything is off.
 *
 * ⚠️ THE FIX DERIVES FROM THE OBJECT, NOT A DEFAULT: `owners.payout_currency` is `char(3)` NOT NULL — the
 * currency this owner is actually paid in. A dash would be the honest answer only if nothing could derive it.
 *
 * ⚠️⚠️ THE WRONG-FIX MUTATION IS WHY THIS CLASS SURVIVED: assert the EUR tenant only and it passes green
 * while the GBP tenant renders euros. Both tenants are asserted below, and the EUR case is a genuine control
 * — a fix that hardcoded GBP would be the same defect reversed, and would turn it RED.
 */
function ocwsOwner(string $payoutCurrency): array
{
    $s = seedScenario(1);
    $user = User::factory()->create(['email' => 'ocws-'.$payoutCurrency.'@example.test', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    $owner = Owner::factory()->create([
        'organization_id' => $s['org']->id,
        'user_id' => $user->id,
        'status' => 'active',
        'payout_currency' => $payoutCurrency,
    ]);

    // ⚠️ THE FIXTURE IS THE FINDING: no statements, no payouts, no ledger — the owner's first day.
    expect(DB::table('owner_statements')->where('organization_id', $s['org']->id)->count())->toBe(0);
    expect(DB::table('payouts')->where('organization_id', $s['org']->id)->count())->toBe(0);

    return ['scenario' => $s, 'user' => $user, 'owner' => $owner];
}

it('shows a GBP owner pounds on their first day, not euros', function () {
    $c = ocwsOwner('GBP');

    $html = $this->actingAs($c['user'])->get('/owner')->assertOk()->getContent();

    // ⚠️ THE RENDERED FIGURE, not the presence of a symbol somewhere on the page.
    expect($html)->toContain('£0.00');
    expect($html)->not->toContain('€0.00');
});

/**
 * ⚠️ THE CONTROL, AND IT COULD GENUINELY FAIL. If the fix hardcoded GBP — the same defect reversed — this
 * goes RED. If the page simply rendered nothing, the assertion above would pass vacuously and this one
 * would catch that too.
 */
it('still shows a EUR owner euros', function () {
    $c = ocwsOwner('EUR');

    $html = $this->actingAs($c['user'])->get('/owner')->assertOk()->getContent();

    expect($html)->toContain('€0.00');
    expect($html)->not->toContain('£0.00');
});
