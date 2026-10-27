<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A money movement. Token-only (never raw card). Correct-by-reversal: a refund/void is a NEW
 * offsetting Payment; this row is never mutated to "undo" it.
 *
 * @property bool|null $livemode ADMIN 06 — Stripe's own, stored when written; null = not a Stripe record
 */
class Payment extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'folio_id',
        'reservation_id',
        'gateway',
        'method',
        'gateway_ref',
        'stripe_account_id',
        'livemode',
        'idempotency_key',
        'type',
        'amount',
        'amount_minor',
        'currency',
        'status',
        'card_token',
        'metadata',
        'refund_borne_by',
        'processed_at',
    ];

    /**
     * ⚠️ WHO BEARS A REFUND — the CLOSED set. NULL is not a member: NULL means OWNER-BORNE, which is both
     * the product ruling and the fail-safe direction (a caller that forgets debits the owner exactly as
     * before). Held here so the boundary rule and the ledger listener read ONE definition (§N.8f).
     */
    public const BORNE_BY_OWNER = 'owner';

    public const BORNE_BY_OPERATOR = 'operator';

    /** @var list<string> */
    public const BORNE_BY = [self::BORNE_BY_OWNER, self::BORNE_BY_OPERATOR];

    /** ⚠️ TRUE only for an EXPLICIT operator-borne refund. NULL and 'owner' both mean the owner bears it. */
    public function isOperatorBorne(): bool
    {
        return (string) $this->refund_borne_by === self::BORNE_BY_OPERATOR;
    }

    /**
     * @var list<string>
     */
    protected $hidden = [
        'card_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // ⚠️ ADMIN 06 — Stripe's own `livemode`, stored when the record was written (null: not a Stripe record)
            'livemode' => 'boolean',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * A human display label for the payment method (QA D2-OBS-1) — the REAL cash / bank-transfer / card choice,
     * from the distinct `method` column (backfilled for pre-column rows from the choice already in metadata).
     * Null when no explicit method was recorded (the `gateway` still identifies the rail). Label only.
     */
    public function methodLabel(): ?string
    {
        $method = $this->method;
        if (! is_string($method) || $method === '') {
            return null;
        }

        return match ($method) {
            'cash' => (string) __('php/models/payment.method_cash'),
            'bank_transfer', 'bank' => (string) __('php/models/payment.method_bank_transfer'),
            'card' => (string) __('php/models/payment.method_card'),
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }

    /**
     * @return BelongsTo<Folio, $this>
     */
    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return HasOne<PaymentAuthorization, $this>
     */
    public function authorization(): HasOne
    {
        return $this->hasOne(PaymentAuthorization::class);
    }
}
