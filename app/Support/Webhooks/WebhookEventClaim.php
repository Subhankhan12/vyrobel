<?php

declare(strict_types=1);

namespace App\Support\Webhooks;

use App\Jobs\Sync\ProcessChannexWebhookJob;
use App\Models\WebhookEvent;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * ⚠️⚠️ RECEIVE-ONCE FOR AN INLINE WEBHOOK — AND "CLAIMED" IS NOT "PROCESSED".
 *
 * ⚠️ THE DEFECT THIS REPLACES. Both Stripe receivers claimed the `webhook_events` row and only then ran the
 * work, and their duplicate check read the row's EXISTENCE rather than its STATE:
 *
 *     $row = WebhookEvent::createOrFirst([...]);
 *     if (! $row->wasRecentlyCreated) { return 'duplicate'; }   // ← ANY existing row, however it got there
 *     $apply();                                                 // ← a throw here leaves the row behind
 *
 * So anything that threw inside the closure CONSUMED the event: Stripe retried, the row already existed, the
 * retry was answered "duplicate", and the delivery was **lost permanently**. The idempotency guard and the
 * loss mechanism were the same line.
 *
 * ⚠️⚠️ AND IT IS WORSE THAN THE ORDERING DEFECTS THIS SEQUENCE FIXED. Wrong state can be corrected by a later
 * event; NO state has no second chance. A lost `payment_intent.succeeded` is a guest who paid and a folio
 * that never learned.
 *
 * ⚠️ THE INNER WRITES WERE ALREADY SAFE, WHICH IS WHY ONLY THE CLAIM NEEDED FIXING. `FolioService::settlePayment`
 * / `settleRefund` and `OwnerPayoutService::confirmTransfer` each wrap their own writes in `DB::transaction`,
 * so a throw rolls THEM back. What did not roll back was the claim row — created outside those transactions.
 * A retry was therefore always clean; it was simply never admitted.
 *
 * ⚠️ THE STATES ALREADY EXISTED — no migration. `WebhookEvent` has had `received`/`processing`/`processed`/
 * `failed` and an `error` column all along, and {@see ProcessChannexWebhookJob} already drives
 * exactly this state machine on the QUEUED Channex path. This brings the two INLINE Stripe receivers to the
 * behaviour a third receiver in the same codebase already had.
 *
 * ⚠️⚠️ WHY ONE CLASS AND NOT TWO COPIES. The receivers' own docblock pre-committed to merging on the FIRST of
 * two triggers — "a THIRD receiver needing this guard" and "ANY change to the rule's shape (a new field, a
 * different key, a TTL, **a status**)". Both have now fired: `ChannexWebhookController` is a third writer of
 * the same table, and this change is precisely a change of status shape. Merging is the documented
 * consequence, not scope creep.
 */
final class WebhookEventClaim
{
    /**
     * How long a claim may sit in `processing` before another delivery may take it over.
     *
     * ⚠️⚠️ THIS COVERS THE CASE AN EXCEPTION HANDLER CANNOT: a process that DIES mid-handler — OOM, a killed
     * container, a lost connection — leaves no throw to catch and no completion, so the row would sit
     * `processing` forever and every retry would be refused. That is the same permanent loss by another
     * route, and a fix that only handled exceptions would not cover it.
     *
     * ⚠️ Chosen to sit well above any realistic inline handler (these complete in well under a second, even
     * with a Stripe read-back) and well below Stripe's retry window, so a genuinely dead claim is retryable
     * long before Stripe gives up.
     */
    public const STALE_CLAIM_MINUTES = 15;

    public const RESULT_PROCESSED = 'processed';

    public const RESULT_DUPLICATE = 'duplicate';

    /** Another delivery of this same event holds the claim right now. */
    public const RESULT_IN_FLIGHT = 'in_flight';

    /**
     * Run `$apply` at most once for this event, and let a delivery that never COMPLETED be retried.
     *
     * ⚠️ Rethrows whatever `$apply` throws, after recording it — the caller still fails, so Stripe still
     * retries. The difference is that the retry is now admitted.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $extra  additional columns to set when the row is first created
     */
    public static function once(
        string $source,
        string $externalEventId,
        string $eventType,
        array $payload,
        Closure $apply,
        array $extra = [],
    ): string {
        // `createOrFirst` (not `firstOrCreate`): the unique(source, external_event_id) index turns a losing
        // concurrent INSERT into a fetch rather than a 500.
        $row = WebhookEvent::createOrFirst(
            ['source' => $source, 'external_event_id' => $externalEventId],
            $extra + [
                'event_type' => $eventType,
                'signature_verified' => true,
                'payload' => $payload,
                'status' => WebhookEvent::STATUS_PROCESSING,
                'received_at' => now(),
            ],
        );

        if (! $row->wasRecentlyCreated && ! self::reclaim($row)) {
            // ⚠️ THE STATE, NOT THE ROW. A `processed` row is a true duplicate; anything else is either a
            //    claim another delivery is working right now, or one this call has just taken over.
            return $row->status === WebhookEvent::STATUS_PROCESSED
                ? self::RESULT_DUPLICATE
                : self::RESULT_IN_FLIGHT;
        }

        try {
            $apply();
        } catch (Throwable $e) {
            // ⚠️ RECORD THE FAILURE AND RELEASE THE CLAIM. `failed` lets Stripe's very next retry re-claim
            //    immediately rather than waiting out the stale window — and the row is the ONLY trace that
            //    this event was ever seen, since nothing in this product reconciles against Stripe.
            WebhookEvent::query()->whereKey($row->getKey())->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        WebhookEvent::query()->whereKey($row->getKey())->update([
            'status' => WebhookEvent::STATUS_PROCESSED,
            'processed_at' => now(),
            'error' => null,
        ]);

        return self::RESULT_PROCESSED;
    }

    /**
     * Record an event that arrived, verified, and could NOT be placed — without claiming or applying it.
     *
     * ⚠️⚠️ "IGNORED" IS NOT A RESULT. Before this, an event whose organisation could not be resolved was
     * answered `202 ignored` and left NO TRACE WHATSOEVER: no log line, no row. It was indistinguishable
     * from an event handled perfectly, and that indistinguishability is precisely what hid two permanently
     * inert invoice handlers for this product's entire life.
     *
     * ⚠️ IT MUST NOT READ AS `processed`. The row is written `failed` with the reason in `error`, which is
     * both honest and re-claimable: if a redelivery arrives once the organisation does exist, {@see once()}
     * can take it over (`failed` is in the re-claimable set) and apply it for real.
     *
     * ⚠️ IT DOES NOT THROW AND THE CALLER STILL ANSWERS 202. Retrying an event that names an organisation
     * this deployment does not have would retry forever. Acknowledged-and-written-down is the honest pair.
     *
     * ⚠️ ONE DEFINITION. These rows are written here, beside {@see once()}, rather than in a receiver —
     * the same reason the two receivers' idempotency rule was merged into this class.
     *
     * The founder can see every one of them with:
     *   select event_type, error, received_at from webhook_events where status = 'failed' order by id desc;
     */
    public static function recordUnresolved(
        string $source,
        string $externalEventId,
        string $eventType,
        array $payload,
        string $reason,
        array $extra = [],
    ): void {
        $row = WebhookEvent::createOrFirst(
            ['source' => $source, 'external_event_id' => $externalEventId],
            $extra + [
                'event_type' => $eventType,
                'signature_verified' => true,
                'payload' => $payload,
                'status' => WebhookEvent::STATUS_FAILED,
                'received_at' => now(),
                'error' => $reason,
            ],
        );

        // A redelivery of an event we already placed must not be downgraded — only an unplaced one is
        // marked unresolved, and a row already `processed` is left exactly as it is.
        if (! $row->wasRecentlyCreated && $row->status !== WebhookEvent::STATUS_PROCESSED) {
            WebhookEvent::query()->whereKey($row->getKey())->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'error' => $reason,
            ]);
        }
    }

    /**
     * Atomically take over an existing claim that is not currently being worked. True when this call won it.
     *
     * ⚠️⚠️ ONE UPDATE, NOT A READ-THEN-WRITE. Two simultaneous deliveries of the same event must not both
     * proceed, so the eligibility test and the claim are the SAME statement: the database decides, and the
     * loser's `update()` affects zero rows. A `SELECT` followed by an `UPDATE` would let both through.
     */
    private static function reclaim(WebhookEvent $row): bool
    {
        return WebhookEvent::query()
            ->whereKey($row->getKey())
            ->where(function (Builder $q): void {
                // Never worked, or a previous delivery recorded a failure — retryable immediately.
                $q->whereIn('status', [WebhookEvent::STATUS_RECEIVED, WebhookEvent::STATUS_FAILED])
                    // …or a claim old enough that whoever held it is gone. `updated_at` is the claim clock:
                    // it is refreshed by the claim itself, and nothing else writes these rows.
                    ->orWhere(fn (Builder $stale): Builder => $stale
                        ->where('status', WebhookEvent::STATUS_PROCESSING)
                        ->where('updated_at', '<', now()->subMinutes(self::STALE_CLAIM_MINUTES)));
            })
            ->update(['status' => WebhookEvent::STATUS_PROCESSING]) > 0;
    }
}
