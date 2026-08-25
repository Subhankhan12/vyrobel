<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\Organization;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\OperatorConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * CONNECT PHASE 1 — operator Stripe Connect onboarding.
 *
 * ⚠️⚠️ THE FIRST TEST IS THE MOST IMPORTANT ONE: an organisation WITHOUT a connected account must behave
 * exactly as it did before this phase existed. Everything here is additive or it is wrong.
 */
function connectOrg(array $s): Organization
{
    return Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
}

it('⚠️⚠️ ADDITIVE — an org with no connected account is unchanged and reads NOT READY', function () {
    $s = seedScenario(1);
    $org = connectOrg($s);

    expect($org->stripe_connect_account_id)->toBeNull();

    $readiness = app(OperatorConnectService::class)->readiness($org);

    expect($readiness['connected'])->toBeFalse()
        ->and($readiness['ready'])->toBeFalse()
        ->and($readiness['charges_enabled'])->toBeFalse();

    // ⚠️ And the guest payment path is the platform's, exactly as before — no connected account reaches it.
    expect(config('services.stripe.webhook_secret'))->not->toBe(config('services.stripe.connect_webhook_secret'));
});

it('⚠️⚠️ `details_submitted` IS NOT READINESS — a submitted account that Stripe has not enabled reads NOT READY', function () {
    $s = seedScenario(1);
    $org = connectOrg($s);
    $fake = app(StripeClientInterface::class);
    expect($fake)->toBeInstanceOf(FakeStripeClient::class);

    $url = app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    expect($url)->toContain('connect.stripe.test');

    $org->refresh();
    $accountId = (string) $org->stripe_connect_account_id;
    expect($accountId)->not->toBe('');

    // ⚠️ THE OPERATOR FINISHED THE FORM. Stripe has NOT enabled charges — review, or more information.
    $fake->connectDetailsSubmitted[$accountId] = true;
    $fake->connectChargesEnabled[$accountId] = false;

    $readiness = app(OperatorConnectService::class)->readiness($org);

    expect($readiness['connected'])->toBeTrue()
        ->and($readiness['details_submitted'])->toBeTrue()
        // ⚠️⚠️ THE WHOLE POINT: submitted is not ready. A storefront trusting the first would offer a card
        //    it cannot take.
        ->and($readiness['charges_enabled'])->toBeFalse()
        ->and($readiness['ready'])->toBeFalse();

    // …and once Stripe actually enables charges, and only then, it reads ready.
    $fake->connectChargesEnabled[$accountId] = true;
    expect(app(OperatorConnectService::class)->readiness($org)['ready'])->toBeTrue();
});

it('⚠️ READINESS COMES FROM STRIPE, NOT FROM US — it changes with no local write', function () {
    $s = seedScenario(1);
    $org = connectOrg($s);
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    $accountId = (string) $org->stripe_connect_account_id;

    $fake = app(StripeClientInterface::class);
    $fake->connectChargesEnabled[$accountId] = true;
    expect(app(OperatorConnectService::class)->readiness($org)['ready'])->toBeTrue();

    // ⚠️ Stripe suspends the account. NOTHING is written locally — and readiness must follow Stripe.
    $fake->connectChargesEnabled[$accountId] = false;
    expect(app(OperatorConnectService::class)->readiness($org)['ready'])->toBeFalse();

    // ⚠️ The org row is untouched throughout: it holds an IDENTIFIER, never a readiness flag.
    $org->refresh();
    expect((string) $org->stripe_connect_account_id)->toBe($accountId);
});

it('⚠️ starting twice re-links the SAME account and never creates a second one', function () {
    $s = seedScenario(1);
    $org = connectOrg($s);
    $svc = app(OperatorConnectService::class);

    $svc->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    $first = (string) $org->stripe_connect_account_id;

    $svc->startOnboarding($org->refresh(), 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();

    expect((string) $org->stripe_connect_account_id)->toBe($first);
});

it('⚠️ the act is AUDITED — who started it and when', function () {
    $s = seedScenario(1);
    $org = connectOrg($s);

    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');

    $event = AuditEvent::withoutGlobalScopes()
        ->where('action', 'organization.connect_onboarding_started')->first();

    expect($event)->not->toBeNull()
        ->and($event->created_at)->not->toBeNull();
});

it('⚠️ the route refuses a role without billing.manage, and admits one with it', function () {
    $s = seedScenario(1);

    // Front Desk can take a booking but must not connect the organisation's payment account.
    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.billing.connect.start'))->assertForbidden();

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.billing.connect.start'))->assertRedirect();
});

it('⚠️⚠️ NOTHING CHARGES ON IT — no direct-charge path was pre-built', function () {
    // ⚠️ The guest checkout must not name a connected account anywhere. An unused money path is the
    //    "built with no front door" shape, and on a charge call it invites a later wiring with no context.
    $client = file_get_contents(base_path('app/Payments/Stripe/StripeClient.php'));
    $checkout = substr($client, strpos($client, 'function createPaymentCheckout'));
    $checkout = substr($checkout, 0, strpos($checkout, 'function constructWebhookEvent'));

    expect($checkout)->not->toContain('stripe_account')
        ->and($checkout)->not->toContain('on_behalf_of')
        ->and($checkout)->not->toContain('transfer_data')
        ->and($checkout)->not->toContain('application_fee');

    // And no caller passes a connected account into the guest charge.
    foreach (['app/Http/Controllers/BookingEngineController.php', 'app/Http/Controllers/Guest/GuestFolioPaymentController.php'] as $file) {
        expect(file_get_contents(base_path($file)))->not->toContain('stripe_connect_account_id');
    }
});

// ── ⚠️⚠️ STRIPE'S ACCOUNT LINKS EXPIRE AND ARE SINGLE-USE ────────────────────────────────────────────

it('⚠️⚠️ mints a FRESH link every time and stores NONE — a reused link fails mid-signup', function () {
    $s = seedScenario(1);
    $org = connectOrg($s);
    $svc = app(OperatorConnectService::class);
    $fake = app(StripeClientInterface::class);

    $first = $svc->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $second = $svc->startOnboarding(connectOrg($s), 'ops@example.test', 'https://r', 'https://x');
    $third = $svc->startOnboarding(connectOrg($s), 'ops@example.test', 'https://r', 'https://x');

    // ⚠️ A link is minted on EVERY call — never fetched from storage.
    expect($fake->accountLinksCreated)->toHaveCount(3)
        ->and($first)->not->toBe($second)
        ->and($second)->not->toBe($third);

    // ⚠️⚠️ AND NOTHING PERSISTS A LINK. A stored link expires and is single-use: it would fail days later,
    //    for someone in the middle of signing up, with an error they cannot act on.
    $row = (array) connectOrg($s)->getAttributes();
    foreach ($row as $column => $value) {
        expect((string) $value)->not->toContain('connect.stripe.test');
    }
    expect(implode(' ', array_keys($row)))->not->toContain('account_link');

    // The source file must not hold one either.
    expect(file_get_contents(base_path('app/Services/OperatorConnectService.php')))
        ->not->toContain('account_link')
        ->and(file_get_contents(base_path('app/Services/OperatorConnectService.php')))->not->toContain('Cache::');
});

it('⚠️ BOTH Stripe return paths are wired — completion AND refresh/expiry', function () {
    $s = seedScenario(1);
    app(OperatorConnectService::class)->startOnboarding(connectOrg($s), 'ops@example.test', 'https://refresh-me', 'https://done');

    $link = app(StripeClientInterface::class)->accountLinksCreated[0];

    // Stripe's accountLinks.create takes BOTH: `refresh_url` (the link expired or was already used) and
    // `return_url` (the operator finished). Neither may be omitted.
    expect($link['refresh_url'])->toBe('https://refresh-me')
        ->and($link['return_url'])->toBe('https://done');
});

it('⚠️⚠️ AN ABANDONED ONBOARDING RESUMES — same account, brand new link', function () {
    $s = seedScenario(1);
    $svc = app(OperatorConnectService::class);
    $fake = app(StripeClientInterface::class);

    $svc->startOnboarding(connectOrg($s), 'ops@example.test', 'https://r', 'https://x');
    $accountId = (string) connectOrg($s)->stripe_connect_account_id;

    // The operator walks away halfway. Stripe has the form partly filled: submitted false, charges false.
    expect(app(OperatorConnectService::class)->readiness(connectOrg($s))['ready'])->toBeFalse();

    // They come back days later — the expired link is useless, so a new one is minted for the SAME account.
    $resumed = $svc->startOnboarding(connectOrg($s), 'ops@example.test', 'https://r', 'https://x');

    expect((string) connectOrg($s)->stripe_connect_account_id)->toBe($accountId)
        ->and($resumed)->toContain($accountId)
        ->and($fake->accountLinksCreated)->toHaveCount(2);
});
