<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\CheckoutSession;
use App\Payments\Stripe\StripeCheckoutSession;
use App\Payments\Stripe\StripeEvent;
use App\Services\BillingService;
use App\Services\FolioService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;

/**
 * ⚠️ ADMIN 06 — THE ONE PLACE a Stripe Checkout page is recorded, completed and expired (KNOWN-LIMITS L19, GO-LIVE
 * B19). Both flows that open one — a subscription ({@see BillingService::startCheckout()}) and a guest's
 * card payment ({@see FolioService::recordCheckoutIntent()}) — record it here, and both webhook
 * endpoints close it here, so a permanent deletion can refuse while any page is still open.
 */
final class CheckoutSessions
{
    /** Record a page the app just opened — under the organisation's tenant context. Idempotent on the Stripe id. */
    public static function record(StripeCheckoutSession $session, string $mode): CheckoutSession
    {
        return CheckoutSession::query()->firstOrCreate(
            ['stripe_session_id' => $session->id],
            [
                'mode' => $mode,
                'livemode' => $session->livemode,
                'expires_at' => $session->expiresAt !== null ? Carbon::createFromTimestamp($session->expiresAt) : null,
            ],
        );
    }

    /** The organisation a recorded page belongs to — read with no tenant context (a webhook has none). */
    public static function organizationFor(StripeEvent $event, TenantContext $tenant): ?string
    {
        $id = $event->objectField('id');
        if ($id === null) {
            return null;
        }

        $org = $tenant->runAsSuperAdmin(fn () => CheckoutSession::query()->where('stripe_session_id', $id)->value('organization_id'));

        return $org !== null ? (string) $org : null;
    }

    /**
     * `checkout.session.completed` / `checkout.session.expired` → the page is closed. Under the organisation's
     * tenant context. A no-op for a page this app did not record, and never re-opens a closed one.
     */
    public static function closeFromEvent(StripeEvent $event): void
    {
        $id = $event->objectField('id');
        if ($id === null) {
            return;
        }

        $column = $event->type === 'checkout.session.expired' ? 'expired_at' : 'completed_at';
        CheckoutSession::query()->where('stripe_session_id', $id)->whereNull('completed_at')->whereNull('expired_at')
            ->update([$column => now()] + ($event->livemode() !== null ? ['livemode' => $event->livemode()] : []));
    }
}
