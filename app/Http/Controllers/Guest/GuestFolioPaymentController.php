<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Folio;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
use App\Services\FolioService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Guest folio card charge (Phase 8 · SG2 — LIVE Stripe, test-mode-first). The guest pays their OWN booking's
 * balance via a hosted Stripe Checkout — the card NEVER touches our server (PCI SAQ A). 🔒 The folio records
 * the money ONLY on the `payment_intent.succeeded` WEBHOOK: here we pre-create a PENDING Payment against the
 * session's PaymentIntent and redirect; the existing webhook settles it ({@see FolioService}).
 *
 * 🔒 GUEST ISOLATION (#28): the booking is resolved THROUGH the authenticated guest's own reservations
 * ({@see ResolvesGuest}), never a request id — a foreign booking id simply doesn't resolve (404). A double-click
 * can't double-charge — the deterministic Idempotency-Key returns the SAME Checkout session, and the pending
 * Payment is `firstOrCreate` on the intent id.
 */
class GuestFolioPaymentController extends Controller
{
    use ResolvesGuest;

    public function pay(Request $request, FolioService $folios, string $reservation): RedirectResponse
    {
        // 🔒 The guest's OWN booking only — resolved through their reservations relation.
        $booking = $this->guest()->reservations()->whereKey($reservation)->firstOrFail();

        $folio = Folio::query()->where('reservation_id', $booking->getKey())->first();
        $totals = $folio !== null ? FolioTotals::for($folio) : ['balance_minor' => 0];
        $balanceMinor = (int) $totals['balance_minor'];

        if ($folio === null || $balanceMinor <= 0) {
            return redirect()->to(route('guest.payments'))->with('pay_notice', 'That booking has nothing due.');
        }

        $amount = Money::of($balanceMinor, (string) $booking->currency);
        $orgId = (string) $booking->organization_id;

        try {
            $session = app(StripeClientInterface::class)->createPaymentCheckout(
                amountMinor: $amount->amountMinor(),
                currency: $amount->currency(),
                description: 'Booking '.$booking->reference,
                successUrl: route('guest.payments').'?paid=1',
                cancelUrl: route('guest.payments').'?canceled=1',
                metadata: ['organization_id' => $orgId, 'reservation_id' => (string) $booking->getKey(), 'folio_id' => (string) $folio->getKey()],
                idempotencyKey: StripeIdempotency::keyFor('folio_checkout', (string) $folio->getKey()),
            );

            if ($session->paymentIntentId === null) {
                throw new \RuntimeException('Stripe Checkout returned no PaymentIntent.');
            }

            // The pending Payment is org-scoped — create it in the booking's org context. It settles on the webhook.
            app(TenantContext::class)->forOrganization($orgId, fn () => $folios->recordCheckoutIntent(
                $folio,
                $amount,
                $session->paymentIntentId,
                ['reservation_id' => (string) $booking->getKey(), 'source' => 'guest_checkout'],
            ));
        } catch (Throwable $e) {
            report($e);

            return redirect()->to(route('guest.payments'))->with('pay_error', 'We couldn’t start the payment — please try again.');
        }

        // UX only — the folio is settled on the webhook. Off-site to Stripe's hosted, PCI-scoped card page.
        return redirect()->away($session->url);
    }
}
