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
