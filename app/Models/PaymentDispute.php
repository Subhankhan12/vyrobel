<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ⚠️ ADMIN 06 — a card DISPUTE on one of the organisation's payments, as Stripe reports it (`charge.dispute.*`), keyed by
 * Stripe's own dispute id. It exists so a permanent deletion can refuse while any dispute is open.
 *
 * 📖 Stripe (disputes/how-disputes-work, read 2026-09-27): a payment can — rarely — carry MORE THAN ONE dispute; a
 * `lost` dispute can later become `won` (a "late win"); an inquiry that sits 120 days unescalated closes as
 * `warning_closed`. And Stripe does not promise to deliver events in order. So one column per payment was wrong both
 * ways: a late "still open" event re-opened a finished dispute (refused forever), and one dispute closing could hide
 * another still open. Each dispute is its own row, and each event is applied only if it is not older than the last.
 *
 * @property string $payment_id
 * @property string $stripe_dispute_id
 * @property string $status
 * @property bool|null $livemode
 * @property Carbon|null $event_created_at
 */
class PaymentDispute extends Model
{
    use BelongsToOrganization, HasUlids;

    /**
     * Stripe's dispute statuses that are FINISHED. Every other status is OPEN — the four Stripe names today
     * (warning_needs_response, warning_under_review, needs_response, under_review) and any it adds later.
     * ⚠️ REVIEW 2 (D-R2-7): this used to list the OPEN ones, so a status the code did not know counted as finished and
     * the deletion's refusal failed OPEN. Listing the finished ones makes an unknown status refuse.
     * THE ONE DEFINITION: the webhook that records a dispute and the deletion that refuses on one both read it.
     *
     * @var list<string>
     */
    public const CLOSED_STATUSES = ['won', 'lost', 'warning_closed', 'prevented'];

    protected $fillable = ['organization_id', 'payment_id', 'stripe_dispute_id', 'status', 'livemode', 'event_created_at'];

    protected function casts(): array
    {
        return [
            'livemode' => 'boolean',
            'event_created_at' => 'datetime',
        ];
    }

    /**
     * Apply one `charge.dispute.*` event. Under the payment's organisation context. Idempotent per event (the caller's
     * `once()`), and ordered per dispute: an event OLDER than the last one applied changes nothing, and at the same
     * second a finished dispute is never turned back to open. A newer event always applies — Stripe's late win
     * (`lost` → `won`) included.
     */
    public static function record(Payment $payment, string $disputeId, string $status, ?int $eventCreated, ?bool $livemode): void
    {
        $at = $eventCreated !== null ? Carbon::createFromTimestamp($eventCreated) : null;
        $row = self::query()->createOrFirst(
            ['stripe_dispute_id' => $disputeId],
            ['payment_id' => $payment->getKey(), 'status' => $status, 'livemode' => $livemode, 'event_created_at' => $at],
        );
        if ($row->wasRecentlyCreated) {
            return;
        }

        $reopens = ! in_array($status, self::CLOSED_STATUSES, true);
        // ONE conditional UPDATE — the database decides, so two deliveries racing cannot both pass a read-then-write
        self::query()->whereKey($row->getKey())
            ->when($at !== null, fn ($q) => $q->where(fn ($w) => $w->whereNull('event_created_at')
                ->orWhere('event_created_at', '<', $at)
                ->orWhere(fn ($same) => $same->where('event_created_at', $at)
                    ->when($reopens, fn ($open) => $open->whereNotIn('status', self::CLOSED_STATUSES)))))
            ->when($at === null && $reopens, fn ($q) => $q->whereNotIn('status', self::CLOSED_STATUSES))
            ->update(['status' => $status, 'event_created_at' => $at ?? $row->event_created_at]
                + ($livemode !== null && $row->livemode === null ? ['livemode' => $livemode] : []));
    }
}
