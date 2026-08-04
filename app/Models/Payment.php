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
        'idempotency_key',
        'type',
        'amount',
        'amount_minor',
        'currency',
        'status',
        'card_token',
        'metadata',
        'processed_at',
    ];

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
            'cash' => 'Cash',
            'bank_transfer', 'bank' => 'Bank transfer',
            'card' => 'Card',
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
