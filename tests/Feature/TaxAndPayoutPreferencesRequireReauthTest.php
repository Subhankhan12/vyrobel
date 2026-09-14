<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ══ THE LAST TWO OWNER ACTIONS IN THE RE-AUTH FAMILY — 2026-08-31 ═══════════════════════════════════
//
// ⚠️ REPRODUCED, both with no password: `legal_name` overwritten with "ATTACKER PAYEE LTD" (the payee
// printed on the remittance document), and payouts PAUSED with a €1,000,000 minimum in one request.
//
// ⚠️ FIVE FORMS, not two. `owner.tax.save` is posted from THREE views (Settings · Tax Center · Payout
// Method) and `owner.payouts.preferences` from TWO (Payout Method · Payouts). A rule with no field is a
// feature that always fails, so every one of the five carries the input — and the two that share the
// Payout Method page carry DISTINCT ids.
//
// ⚠️ THE BARE RULE, RE-VERIFIED HERE not inherited: the guards are web/guest/admin/api. There is no
// `owner` guard, so `current_password:owner` would silently validate nothing.

function taxReauthActor(): array
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->changePasswordTo('secret-Passw0rd!');
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    return ['user' => $user->fresh(), 'ownerId' => $ownerId, 'scenario' => $s];
}

function taxPayload(array $extra = []): array
{
    return array_merge([
        'legal_name' => 'ATTACKER PAYEE LTD', 'type' => 'company', 'tax_country' => 'PT',
        'tax_id' => '999999999', 'vat_number' => 'PT999999999', 'vat_registered' => '1',
    ], $extra);
}

function prefPayload(array $extra = []): array
{
    return array_merge(['frequency' => 'monthly', 'payout_day' => 1, 'minimum' => '1000000', 'paused' => '1'], $extra);
}

function taxOwner(array $a): Owner
{
    return Owner::withoutGlobalScopes()->findOrFail($a['ownerId']);
}

// ── 1. ⛔ TAX DETAILS — "what is disclosed" ─────────────────────────────────────────────────────────
it('refuses a tax-details save without the current password', function () {
    $a = taxReauthActor();
    $before = taxOwner($a)->legal_name;

    $this->actingAs($a['user'])->from(route('owner.tax'))
        ->post(route('owner.tax.save'), taxPayload())
        ->assertSessionHasErrors('current_password');

    expect(taxOwner($a)->legal_name)->toBe($before);   // ⚠️ the payee name did not move
});

it('saves tax details with the correct password', function () {
    $a = taxReauthActor();

    $this->actingAs($a['user'])->from(route('owner.tax'))
        ->post(route('owner.tax.save'), taxPayload([
            'current_password' => 'secret-Passw0rd!', 'legal_name' => 'Real Owner Ltd',
        ]))->assertRedirect(route('owner.tax'));

    expect(taxOwner($a)->legal_name)->toBe('Real Owner Ltd');
});

// ── 2. ⛔ PAYOUT PREFERENCES — pause + minimum ──────────────────────────────────────────────────────
it('refuses a payout-preference save without the current password', function () {
    $a = taxReauthActor();

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), prefPayload())
        ->assertSessionHasErrors('current_password');

    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->exists())->toBeFalse();
});

it('saves payout preferences with the correct password', function () {
    $a = taxReauthActor();

    $this->actingAs($a['user'])->from(route('owner.payout-method'))
        ->post(route('owner.payouts.preferences'), prefPayload([
            'current_password' => 'secret-Passw0rd!', 'paused' => '0', 'minimum' => '500',
        ]))->assertRedirect();

    $pref = OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->firstOrFail();
    expect($pref->paused)->toBeFalse()->and($pref->minimum_minor)->toBe(50000);
});

// ── 3. ⚠️⚠️ THE ORDER — capability answers BEFORE identity, so the error is no existence oracle ─────
// An accountant sub-user has no CAP_PAYOUT. They must get the SAME answer whether or not they supply a
// password: if a missing password answered differently, the field would report whether a payout owner
// exists to someone who may not act on it.
it('answers an actor without the payout capability identically with and without a password', function () {
    $a = taxReauthActor();
    $sub = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $a['scenario']['org']->id, 'user_id' => $sub->id, 'status' => 'active']);
    $sub->changePasswordTo('secret-Passw0rd!');
    OwnerUser::create([
        'owner_id' => $a['ownerId'], 'user_id' => $sub->id,
        'role' => OwnerTeamRole::ACCOUNTANT, 'status' => 'active',
    ]);

    $without = $this->actingAs($sub->fresh())->post(route('owner.payouts.preferences'), prefPayload());
    $with = $this->actingAs($sub->fresh())->post(route('owner.payouts.preferences'),
        prefPayload(['current_password' => 'secret-Passw0rd!']));

    expect($without->getStatusCode())->toBe($with->getStatusCode());
    // ⚠️ 403, not 404, and NOT from the controller: `EnsureOwnerCapability` refuses at the ROUTE, before any
    //    of this controller runs. The `abort(404)` inside save() is a second line that a wrong-role sub-user
    //    never reaches. Pinned, because "the order is correct" is a claim about WHICH layer answers first.
    expect($without->getStatusCode())->toBe(403);
    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $a['ownerId'])->exists())->toBeFalse();
});

// ── 3b. ⚠️⚠️ THE ORDER, WHERE IT IS ACTUALLY OBSERVABLE — a user with NO owner link ─────────────────
// ⚠️ MEASURED, not assumed: `EnsureOwnerCapability` only gates a user who ALREADY has owner access
// (`ownerIds() !== []`), so a user with none falls straight through to the controller — and `/owner` has no
// positive "is an owner" gate (audit M-10). For that actor the controller's own `abort(404)` is the ONLY
// refusal, so whether it runs before or after validation is genuinely visible. It must answer 404 either
// way; a 422 naming `current_password` would tell an unrelated account that the route exists and what it
// wants.
it('answers a user with no owner link identically with and without a password', function () {
    $s = seedScenario(1);
    $stranger = operatorUser($s, ['name' => 'Nora NoOwner'], 'Front Desk');   // a real user, no Owner record

    $without = $this->actingAs($stranger->fresh())->post(route('owner.payouts.preferences'), prefPayload());
    $with = $this->actingAs($stranger->fresh())->post(route('owner.payouts.preferences'),
        prefPayload(['current_password' => 'password']));

    expect($without->getStatusCode())->toBe($with->getStatusCode());
    expect($without->getStatusCode())->toBe(404);
    // ⚠️ A 404 body, not a validation redirect: the refusal says nothing about what the route wanted.
    $without->assertNotFound();
    $with->assertNotFound();
});

// ── 4. 🖥️ THE FIELD EXISTS ON EVERY FORM THAT POSTS TO EITHER ROUTE ────────────────────────────────
// ⚠️ "Static checks cannot see a rendered page." A rule with no field is a control that bounces the owner
// every time. Each page is FETCHED and the input located INSIDE the form that posts to the gated route.

/** Every <form> on the page posting to $action, each cut at its own </form>. */
function taxFormsPosting(string $html, string $action): array
{
    $out = [];
    foreach (array_slice(explode('<form', $html), 1) as $chunk) {
        $body = explode('</form>', $chunk)[0];
        if (str_contains($body, $action)) {
            $out[] = $body;
        }
    }

    return $out;
}

it('renders the password field inside all five affected forms', function () {
    $a = taxReauthActor();
    $tax = route('owner.tax.save');
    $prefs = route('owner.payouts.preferences');

    $pages = [
        '/owner/settings' => [[$tax, 1]],
        '/owner/tax' => [[$tax, 1]],
        '/owner/payout-method' => [[$tax, 1], [$prefs, 1]],
        '/owner/payouts' => [[$prefs, 1]],
    ];

    $seen = 0;
    foreach ($pages as $url => $expectations) {
        $html = $this->actingAs($a['user'])->get($url)->assertOk()->getContent();
        foreach ($expectations as [$action, $count]) {
            $forms = taxFormsPosting($html, $action);
            expect(count($forms))->toBe($count, "{$url} should carry {$count} form(s) posting to {$action}");
            foreach ($forms as $form) {
                expect(str_contains($form, 'name="current_password"'))
                    ->toBeTrue("a form on {$url} posts to {$action} without the re-auth field the route requires");
                $seen++;
            }
        }
    }

    expect($seen)->toBe(5);   // ⚠️ all five, not "at least one"
});

// ── 5. ⚠️ TWO OF THEM SHARE A PAGE — distinct ids, or one label focuses the other's box ─────────────
it('gives the two password fields on the payout-method page distinct ids', function () {
    $a = taxReauthActor();

    $html = $this->actingAs($a['user'])->get('/owner/payout-method')->assertOk()->getContent();

    preg_match_all('/<input id="([^"]*)"[^>]*name="current_password"/', $html, $inputs);
    expect(count($inputs[1]))->toBe(2)
        ->and(array_unique($inputs[1]))->toHaveCount(2);

    // ⚠️ AND EACH LABEL MUST POINT AT ITS OWN INPUT. Distinct input ids are not enough: a mutation that
    //    pointed BOTH labels at the same id left the ids distinct and this test green — clicking either
    //    caption then focuses one box. That is exactly the defect shape the team-scope editor shipped.
    preg_match_all('/<label[^>]*for="([^"]*)">Confirm with your password<\/label>/', $html, $labels);
    expect(count($labels[1]))->toBe(2)
        ->and(array_unique($labels[1]))->toHaveCount(2);
    foreach ($labels[1] as $for) {
        expect(in_array($for, $inputs[1], true))->toBeTrue("label for=\"{$for}\" points at no password input");
    }
});
