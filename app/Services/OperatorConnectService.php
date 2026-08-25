<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Payments\Stripe\StripeClientInterface;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;

/**
 * CONNECT PHASE 1 — the OPERATOR's Stripe connected account (Standard), and nothing more.
 *
 * ⚠️⚠️ NOTHING CHARGES ON THIS ACCOUNT. This service creates the account and reads its readiness. The guest
 * payment path is untouched: `createPaymentCheckout()` still charges on the platform exactly as before, and
 * an organization whose `stripe_connect_account_id` is NULL behaves identically to today.
 * ⚠️ THE DIRECT-CHARGE CALL IS DELIBERATELY NOT PRE-BUILT. An unused payment path is the "built with no
 * front door" shape this project has hit four times, and on a money path it is worse — it invites a later
 * pass to wire it up without re-reading why it was written.
 *
 * ⚠️ WHY STANDARD, from Stripe's own account-type table:
 *   · Supported charge types — Standard: "Direct only". It is the type built for direct charges, and
 *     "Use Standard connected accounts when you … want to use direct charges".
 *   · Fraud and dispute liability — "Connected account for direct charges" on Standard, but "Platform" on
 *     BOTH Express and Custom. ⚠️ Express would leave dispute liability with US, which is the opposite of
 *     the reason for moving.
 *   · The operator gets a FULL Stripe Dashboard, and Stripe (not Vyrobel) handles their verification and
 *     contacts them for more information. Integration effort is "Lowest", and Express/Custom carry an
 *     additional Stripe cost that Standard does not.
 *
 * ⚠️ WHAT THE DOCS DID NOT CONFIRM: Stripe now marks Standard/Express/Custom as LEGACY account types and
 * points new platforms at the Accounts v2 API / controller properties. This integration already uses v1
 * `type: 'standard'` for owner accounts, so it stays consistent — but whether a NEW platform should start on
 * v2 was NOT established here and should be checked before go-live.
 */
class OperatorConnectService
{
    public function __construct(
        private StripeClientInterface $stripe,
        private AuditEventRecorder $audit,
        private TenantContext $tenant,
    ) {}

    /**
     * Create the connected account if the org has none, and return a fresh Stripe-hosted onboarding link.
     * Idempotent on the stored id: a second call re-links the SAME account, never creates a second one.
     */
    public function startOnboarding(Organization $organization, string $email, string $refreshUrl, string $returnUrl): string
    {
        $accountId = $organization->stripe_connect_account_id;

        if (! is_string($accountId) || $accountId === '') {
            $account = $this->stripe->createConnectAccount(
                email: $email,
                metadata: ['organization_id' => (string) $organization->getKey()],
            );
            $accountId = $account->id;

            $this->tenant->forOrganization(
                (string) $organization->getKey(),
                fn () => Organization::query()->whereKey($organization->getKey())
                    ->update(['stripe_connect_account_id' => $accountId]),
            );

            // ⚠️ WHO started it and WHEN. Connecting an organisation's payment account is a high-trust act.
            $this->audit->record(
                'organization.connect_onboarding_started',
                $organization,
                ['stripe_connect_account_id' => null],
                ['stripe_connect_account_id' => $accountId],
                organizationId: (string) $organization->getKey(),
            );
        }

        return $this->stripe->createConnectAccountLink($accountId, $refreshUrl, $returnUrl)->url;
    }

    /**
     * The account's readiness, READ FROM STRIPE every time.
     *
     * ⚠️⚠️ THERE IS NO LOCAL READINESS FLAG AND THERE MUST NOT BE ONE. `charges_enabled` is a fact Stripe
     * holds and changes without telling us in advance — on suspension, on a new information request, on a
     * compliance change. A stored boolean is an assertion about a moment that has passed.
     * ⚠️ THE ONLY THING STORED LOCALLY IS THE ACCOUNT ID. Nothing is cached; if caching is ever added, the
     * cache must be refreshed from the `account.updated` Connect webhook, not on a timer.
     *
     * @return array{connected: bool, details_submitted: bool, charges_enabled: bool, payouts_enabled: bool, ready: bool}
     */
    public function readiness(Organization $organization): array
    {
        $accountId = $organization->stripe_connect_account_id;

        if (! is_string($accountId) || $accountId === '') {
            return ['connected' => false, 'details_submitted' => false, 'charges_enabled' => false, 'payouts_enabled' => false, 'ready' => false];
        }

        $account = $this->stripe->retrieveConnectAccount($accountId);

        return [
            'connected' => true,
            'details_submitted' => $account->detailsSubmitted,
            'charges_enabled' => $account->chargesEnabled,
            'payouts_enabled' => $account->payoutsEnabled,
            // ⚠️ READY IS `charges_enabled` ALONE. A submitted form is not permission to take money.
            'ready' => $account->chargesEnabled,
        ];
    }
}
