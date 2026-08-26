<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\OperatorConnectService;

/**
 * Give a scenario's organisation a Stripe connected account that CAN take a card.
 *
 * ⚠️⚠️ WHY SO MANY SUITES SUDDENLY NEED THIS. All three charge paths — the storefront, the guest portal and
 * the desk (`recordPayment`) — now charge on the operator's OWN connected account. An organisation that has
 * not completed Stripe verification cannot take a card at all, by design and with no platform fallback. So
 * any fixture that takes a card must now describe an operator who has onboarded.
 *
 * ⚠️ THIS IS A FIXTURE, NOT A LICENCE TO WEAKEN A TEST. It moves the world the test describes; it never
 * touches what the test asserts.
 *
 * ⚠️ ONE DEFINITION (§N.8f): the snippet had been written inline four times before it was extracted, which
 * is exactly how a test double drifts from the thing it stands in for.
 *
 * ⚠️ A `manual`-gateway test must NOT call this — the offline rail works with no Connect account at all,
 * and proving that is its own assertion.
 */
function onboardOrgForCards(array $s): string
{
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());

    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();

    $accountId = (string) $org->stripe_connect_account_id;
    app(StripeClientInterface::class)->connectChargesEnabled[$accountId] = true;

    return $accountId;
}
