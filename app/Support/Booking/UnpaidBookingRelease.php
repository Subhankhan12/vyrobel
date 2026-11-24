<?php

declare(strict_types=1);

namespace App\Support\Booking;

use App\Exceptions\Booking\ReservationAlreadyTerminalException;
use App\Exceptions\Booking\ReservationInProgressException;
use App\Models\CheckoutSession;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Scopes\OrganizationScope;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Reservations\CancellationReason;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️⚠️ VY-23 (audit of 2026-11-24, read in code) — A STOREFRONT BOOKING WHOSE CARD PAYMENT IS NEVER COMPLETED GIVES ITS ROOM BACK.
 *
 * The storefront books FIRST (the room is taken under the lock, as it must be) and then sends the guest to Stripe's page. A guest who
 * closed that page left a CONFIRMED booking holding the room for ever: nothing cancelled it, so anyone could fill a calendar with
 * abandoned checkouts and only the rate limit in the way. Now:
 *
 *   · the page itself expires — {@see HOLD_MINUTES} after it was opened (Stripe's minimum is 30) — so after that nobody can pay it;
 *   · when Stripe says it expired (`checkout.session.expired`), the booking is released at once ({@see afterCheckoutExpired()});
 *   · and a job every five minutes releases any whose page expired more than {@see GRACE_MINUTES} ago without that word arriving
 *     ({@see sweep()}) — a webhook that never comes must not leave the room held.
 *
 * "Released" = the pending payment is marked failed and the booking is CANCELLED through the certified {@see AssignmentService} (which
 * frees the room and writes the audit and domain events), with the reason {@see CancellationReason::PAYMENT_NOT_COMPLETED}.
 *
 * ⚠️ WHAT IS NEVER RELEASED: a booking that has ANY settled payment (a paid deposit is a real booking); one whose page was COMPLETED (a
 *    payment still processing — a bank debit — is in flight, never abandoned); one a person has already changed (checked in, cancelled,
 *    a no-show); one that is not from the storefront. The pending payment row is LOCKED and re-read first, so a payment that settles at
 *    the same moment wins and nothing is released.
 */
final class UnpaidBookingRelease
{
    /** How long the storefront's Stripe page stays open (Stripe accepts 30 minutes at the least). */
    public const HOLD_MINUTES = 31;

    /** How long after the page expired the job waits for Stripe's own word before releasing on its own. */
    public const GRACE_MINUTES = 10;

    /** A page opened before the storefront set an expiry closes after Stripe's default: 24 hours. */
    private const DEFAULT_PAGE_HOURS = 24;

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly AssignmentService $assignment,
        private readonly FolioService $folios,
    ) {}

    /** The page's closing time the storefront asks Stripe for (a Unix time). */
    public static function pageExpiresAt(): int
    {
        return CarbonImmutable::now()->addMinutes(self::HOLD_MINUTES)->getTimestamp();
    }

    /**
     * Stripe said the page expired. Called inside the webhook's own idempotency and the organisation's tenant context; never throws —
     * a webhook must not be turned into a poison message by a booking somebody already changed.
     */
    public function afterCheckoutExpired(?string $sessionId, ?string $reservationId): void
    {
        if ($sessionId === null || $reservationId === null) {
            return;
        }

        $payment = Payment::query()->where('reservation_id', $reservationId)->where('status', 'pending')
            ->where('metadata->checkout_session_id', $sessionId)->first();
        if ($payment instanceof Payment) {
            $this->release($payment);
        }
    }

    /**
     * Release every storefront booking whose card page closed unpaid more than {@see GRACE_MINUTES} ago. Across all organisations, each in
     * its own tenant context. Returns how many were released.
     */
    public function sweep(): int
    {
        $cutoff = CarbonImmutable::now()->subMinutes(self::HOLD_MINUTES + self::GRACE_MINUTES);

        /** @var list<array{0: string, 1: string}> $candidates */
        $candidates = $this->tenant->runAsSuperAdmin(fn (): array => Payment::query()->withoutGlobalScopes([OrganizationScope::class])
            ->where('gateway', 'stripe')->where('type', 'payment')->where('status', 'pending')
            ->where('metadata->source', 'storefront')
            ->where('processed_at', '<', $cutoff)
            ->get(['id', 'organization_id'])
            ->map(fn (Payment $p): array => [(string) $p->organization_id, (string) $p->getKey()])
            ->all());

        $released = 0;
        foreach ($candidates as [$organizationId, $paymentId]) {
            $released += $this->tenant->forOrganization($organizationId, function () use ($paymentId): int {
                $payment = Payment::query()->find($paymentId);

                return $payment instanceof Payment && $this->pageIsOver($payment) && $this->release($payment) ? 1 : 0;
            });
        }

        return $released;
    }

    /** Has this payment's Stripe page closed without being completed, long enough ago? */
    private function pageIsOver(Payment $payment): bool
    {
        /** @var array<string, mixed> $meta */
        $meta = is_array($payment->metadata) ? $payment->metadata : [];
        $sessionId = is_string($meta['checkout_session_id'] ?? null) ? $meta['checkout_session_id'] : null;
        $graceAgo = CarbonImmutable::now()->subMinutes(self::GRACE_MINUTES);

        if ($sessionId === null) {
            // a page opened before the storefront recorded its id: Stripe's default lifetime
            return $payment->processed_at !== null
                && CarbonImmutable::parse($payment->processed_at)->addHours(self::DEFAULT_PAGE_HOURS)->lessThan($graceAgo);
        }

        $page = CheckoutSession::query()->where('stripe_session_id', $sessionId)->first();
        if (! $page instanceof CheckoutSession || $page->completed_at !== null) {
            return false;   // unknown here, or completed — a payment in flight is never released
        }

        return $page->expired_at !== null || ($page->expires_at !== null && $page->expires_at->lessThan($graceAgo));
    }

    /** Release the booking this pending storefront payment was for — or nothing, if anything says it is not abandoned. */
    private function release(Payment $pending): bool
    {
        return DB::transaction(function () use ($pending): bool {
            $payment = Payment::query()->lockForUpdate()->find($pending->getKey());
            if (! $payment instanceof Payment || $payment->status !== 'pending') {
                return false;   // it settled (or failed) meanwhile — the payment's own word wins
            }

            $reservation = Reservation::query()->lockForUpdate()->find($payment->reservation_id);
            if (! $reservation instanceof Reservation || $reservation->status !== 'confirmed' || $reservation->source !== 'direct') {
                return false;
            }

            $settled = Payment::query()->where('reservation_id', $reservation->getKey())
                ->whereIn('type', ['payment', 'capture'])->where('status', 'succeeded')->exists();
            if ($settled) {
                return false;   // money is in: a deposit or a payment by another route — a real booking
            }

            $this->folios->settlePayment($payment, 'failed');

            try {
                $this->assignment->cancelReservation($reservation, CancellationReason::PAYMENT_NOT_COMPLETED);
            } catch (ReservationAlreadyTerminalException|ReservationInProgressException) {
                return false;   // a person got there first; the payment is still marked failed, which it is
            }

            return true;
        });
    }
}
