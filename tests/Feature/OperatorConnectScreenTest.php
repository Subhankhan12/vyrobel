<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\OperatorConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ THE SCREEN THAT MAKES THE DIRECT-CHARGE SWITCH USABLE. All three payment paths charge on the
 * operator's own connected account, so until this exists nobody can complete onboarding and nobody can take
 * a card at all.
 */
function connectScreen(array $s, string $role = 'Property Owner')
{
    return test()->actingAs(operatorUser($s, [], $role))
        ->withSession(['current_property_id' => $s['property']->id])
        ->get(route('app.billing'));
}

it('⚠️ is gated on billing.manage — Front Desk cannot reach it, the billing owner can', function () {
    $s = seedScenario(1);

    connectScreen($s, 'Front Desk')->assertForbidden();
    connectScreen($s, 'Property Owner')->assertOk();
});

it('⚠️⚠️ THE COUNTRY WARNING IS SHOWN *BEFORE* THE ACCOUNT EXISTS — it cannot be changed after', function () {
    $s = seedScenario(1);

    $response = connectScreen($s);

    // ⚠️ An irreversible choice needs its warning at the point of choosing, not in a document and not
    //    afterwards, when a customer discovers it while expanding into a second country.
    $response->assertSee('the country cannot be changed later')
        ->assertSee('Stripe fixes the country when the account is created', false);
});

it('⚠️ the country warning DISAPPEARS once the account exists — the choice is already made', function () {
    $s = seedScenario(1);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');

    connectScreen($s)->assertDontSee('the country cannot be changed later');
});

it('⚠️⚠️ `details_submitted` RENDERS AS "REVIEWING", NEVER AS READY', function () {
    $s = seedScenario(1);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    $account = (string) $org->stripe_connect_account_id;

    // The operator finished the form; Stripe has NOT enabled charges.
    $fake = app(StripeClientInterface::class);
    $fake->connectDetailsSubmitted[$account] = true;
    $fake->connectChargesEnabled[$account] = false;

    $response = connectScreen($s);

    // ⚠️ Collapsing "submitted" into "done" is how a card refused later reads as a broken product.
    $response->assertSee('Submitted — Stripe is reviewing')
        ->assertDontSee('Verified — cards can be taken')
        // …and the screen still says what to do meanwhile.
        ->assertSee('Take payment another way and record it on the', false);
});

it('⚠️ readiness FOLLOWS STRIPE with no local write — enable, then suspend', function () {
    $s = seedScenario(1);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    $account = (string) $org->stripe_connect_account_id;
    $fake = app(StripeClientInterface::class);

    $fake->connectChargesEnabled[$account] = true;
    connectScreen($s)->assertSee('Verified — cards can be taken');

    // ⚠️ Stripe suspends it. Nothing is written locally, and the screen must follow.
    $fake->connectChargesEnabled[$account] = false;
    connectScreen($s)->assertDontSee('Verified — cards can be taken');
});

it('⚠️⚠️ THE SCREEN MINTS NO LINK — links expire and are single-use', function () {
    $s = seedScenario(1);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');

    $fake = app(StripeClientInterface::class);
    $before = count($fake->accountLinksCreated);

    $response = connectScreen($s);

    // ⚠️ Rendering must not mint one: a link held on a page left open fails days later, mid-signup.
    expect(count($fake->accountLinksCreated))->toBe($before);
    $response->assertDontSee('connect.stripe.test');
});

it('⚠️⚠️ AN ABANDONED SETUP RESUMES FROM THE SCREEN — same account, new link', function () {
    $s = seedScenario(1);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    $account = (string) $org->stripe_connect_account_id;
    $fake = app(StripeClientInterface::class);
    $before = count($fake->accountLinksCreated);

    // The screen offers to continue, and acting on it mints a FRESH link for the SAME account.
    connectScreen($s)->assertSee('Continue setup with Stripe');

    test()->actingAs(operatorUser($s, [], 'Property Owner'))
        ->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.billing.connect.start'))->assertRedirect();

    expect(count($fake->accountLinksCreated))->toBe($before + 1)
        ->and(Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey())->stripe_connect_account_id)
        ->toBe($account);
});

it('⚠️⚠️ NO STATEMENT-DESCRIPTOR FIELD EXISTS — Stripe would ignore it', function () {
    $s = seedScenario(1);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');

    $response = connectScreen($s);

    // ⚠️ On a Standard account with direct charges the descriptor comes from the CONNECTED ACCOUNT's own
    //    settings, which the operator controls. A field here would write somewhere they can silently
    //    override — telling them they configured something they had not.
    $html = $response->getContent();
    expect($html)->not->toContain('name="statement_descriptor"')
        ->and($html)->not->toContain('statement_descriptor_prefix');

    // ⚠️ …and no code path writes one either.
    foreach (['app/Services/OperatorConnectService.php', 'app/Livewire/Operator/OperatorBilling.php'] as $file) {
        expect(file_get_contents(base_path($file)))->not->toContain('statement_descriptor');
    }

    // The guidance names the risk and a destination that genuinely exists.
    $response->assertSee('likely to dispute the', false)
        ->assertSee('Vyrobel cannot set it for you', false)
        ->assertSee('https://dashboard.stripe.com/settings/public', false);
});

it('⚠️ the screen PROMISES NOTHING — no date, no future capability', function () {
    $s = seedScenario(1);
    $response = connectScreen($s);
    $html = $response->getContent();

    foreach (["you'll be able", 'you will be able', 'coming soon', 'Coming soon', 'shortly', 'in a future'] as $promise) {
        expect($html)->not->toContain($promise);
    }
});

it('⚠️ no raw Blade ships — a directive after a word character is not compiled', function () {
    $s = seedScenario(1);
    assertNoRawBlade(connectScreen($s));
});
