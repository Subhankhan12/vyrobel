<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * INBOUND provider-webhook record + idempotency anchor (Channex now, Stripe in Step 6). One row
 * per delivered event; `unique(source, external_event_id)` makes at-least-once delivery
 * exactly-once. Tenant-scoped: for Channex the org is resolved from the channel before insert.
 */
class WebhookEvent extends Model
{
    use BelongsToOrganization, HasUlids;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    /**
     * ⚠️ ADMIN 06 — recorded with its reason, but NOT a failure: a correctly signed event this deployment has nothing to
     * do with (a subscription invoice's PaymentIntent on the platform plane, an operator account's update, a type the
     * app does not act on). Kept apart from `failed` so the daily billing reconciliation stays a list of real problems.
     */
    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'organization_id',
        'source',
        'channel_id',
        'external_event_id',
        'event_type',
        'signature_verified',
        'payload',
        'status',
        'received_at',
        'processed_at',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
