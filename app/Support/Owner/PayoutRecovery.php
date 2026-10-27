<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\Payout;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\OwnerPayoutService;

/**
 * DO3-3 — the ONE definition of "can this failed payout be retried, and if not, what must the owner FIX
 * first?" (§N.8f). Read by BOTH the view gate (whether the owner sees a Retry control) and the write gate
 * (whether the POST is honoured), so the button and the guard cannot drift apart — a button offered where
 * the write refuses is a broken promise on a money surface; a write honoured where the button is hidden is
 * an unguarded route.
 *
 * ⚠️ NOTHING HERE MOVES MONEY. A retry re-QUEUES ({@see OwnerPayoutService::retry()} writes a `pending`
 * row); only {@see OwnerPayoutService::pay()} — operator-permissioned and rail-gated — ever sends. The
 * owner asks for another attempt; they never touch the rail. That is why an owner-initiated retry is safe
 * at all, and it is the reason the design register's refusal ("there is no owner-initiated retry RAIL")
 * still holds: this gives them none.
 *
 * ⚠️ RETRYABILITY IS DERIVED FROM THE OWNER'S REAL CURRENT STATE, NEVER FROM THE STORED FAILURE STRING.
 * `failure_reason` is a historical message about an attempt that is over; what decides whether the NEXT
 * attempt can succeed is how the account stands NOW. So a blocker the owner has since fixed disappears on
 * its own, and one they have not yet fixed keeps the doomed re-send off the page (fix-then-retry).
 *
 * ⚠️ NO ETA IS PRODUCED. The wireframe's "retrying · est. {time}" has no source in the product and stays
 * refused (#39) — this reports state, never a promise about when money will land.
 */
final class PayoutRecovery
{
    /**
     * What must be true before another attempt could succeed, derived from the ACTIVE rail's OWN refusal
     * conditions rather than from a guessed list. Empty ⇒ nothing stands in the way.
     *
     * ⚠️ The `stripe_connect` entries mirror {@see StripeConnectPayoutRail::send()} exactly, and
     * `PayoutRecoveryDriftTest` pins them to the rail's real behaviour so the two cannot separate.
     * The `manual` rail records an offline transfer and cannot refuse, so it contributes no blocker —
     * that is a fact about the code, not an omission.
     *
     * ⚠️ Missing TAX details are deliberately NOT a blocker here: they do not fail a payout, they HOLD it
     * ({@see OwnerPayoutService::pay()} raises before the rail), which is a real, visible, auto-releasing
     * state that this page already banners with its own CTA. Duplicating it here would put two competing
     * calls-to-action on one screen.
     *
     * @return list<array{key: string, title: string, detail: string, cta_label: string, cta_route: string}>
     */
    public static function blockers(Owner $owner): array
    {
        if ((string) config('services.payouts.rail') !== 'stripe_connect') {
            return [];
        }

        $blockers = [];

        $account = $owner->stripe_connect_account_id;
        if (! is_string($account) || $account === '') {
            $blockers[] = [
                'key' => 'no_connected_account',
                'title' => __('php/support/owner/payout_recovery.no_account_title'),
                'detail' => __('php/support/owner/payout_recovery.no_account_detail'),
                'cta_label' => __('php/support/owner/payout_recovery.no_account_cta'),
                'cta_route' => route('owner.payout-method'),
            ];

            return $blockers;   // the enabled check below is meaningless without an account
        }

        if (! $owner->stripe_connect_payouts_enabled) {
            $blockers[] = [
                'key' => 'payouts_not_enabled',
                'title' => __('php/support/owner/payout_recovery.not_enabled_title'),
                'detail' => __('php/support/owner/payout_recovery.not_enabled_detail'),
                'cta_label' => __('php/support/owner/payout_recovery.not_enabled_cta'),
                'cta_route' => route('owner.payout-method'),
            ];
        }

        return $blockers;
    }

    /**
     * The payout that already claims this one's statement, if any — a replacement the owner (or the engine)
     * has already queued, one in flight, or one that has since succeeded. Its existence is precisely what
     * makes a further retry a DOUBLE PAYMENT, so it is both the reason the control hides and the reason
     * {@see OwnerPayoutService::retry()} refuses.
     */
    public static function liveReplacement(Payout $payout): ?Payout
    {
        return Payout::query()
            ->where('owner_statement_id', $payout->owner_statement_id)
            ->whereIn('status', Payout::LIVE_STATUSES)
            ->first();
    }

    /**
     * Whether the owner may ask for another attempt at THIS payout. All four conditions are real state:
     * the payout genuinely failed, nothing live already claims its statement, nothing in the account
     * would make the next attempt fail again, and the owner's payouts are not paused (DEC-n, 2026-10-02:
     * a pause stops every payout, a retry included — the service refuses it, so the button must not offer it).
     *
     * ⚠️ This is the DISPLAY/PRE-FLIGHT answer, and it is deliberately not the last word — the service
     * re-decides under a row lock, because everything read here is a snapshot.
     */
    public static function isRetryable(Payout $payout, Owner $owner): bool
    {
        return (string) $payout->status === 'failed'
            && self::liveReplacement($payout) === null
            && self::blockers($owner) === []
            && ! OwnerPayoutPreference::isPaused((string) $owner->getKey());
    }
}
