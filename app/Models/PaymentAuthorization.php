<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deposit / incidental hold — authorize vs capture lifecycle.
 *
 * @property bool|null $livemode ADMIN 06 — Stripe's own, stored when the hold was placed
 */
class PaymentAuthorization extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'payment_id',
        'authorized',
        'authorized_minor',
        'captured',
        'captured_minor',
        'currency',
        'expires_at',
        'status',
        'livemode',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // ⚠️ ADMIN 06 — Stripe's own `livemode`, stored when the record was written (null: not a Stripe record)
            'livemode' => 'boolean',
            'authorized' => MoneyCast::class.':authorized_minor,currency',
            'captured' => MoneyCast::class.':captured_minor,currency',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
