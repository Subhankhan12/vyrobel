<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\GuestWalletService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One APPEND-ONLY entry in a guest's credit wallet (Phase 4c · Sub-gate 2). Same discipline as {@see OwnerLedger}:
 * a `direction` (credit/debit) + a positive `amount_minor`; the balance is DERIVED in {@see GuestWalletService},
 * never stored. Correct-by-reversal — a reversal is a new debit with `reverses_id` set, never an edit. Rows are
 * written once and never mutated.
 *
 * @property string $direction
 * @property string $type
 * @property int $amount_minor
 * @property string $currency
 * @property Carbon|null $expires_at
 */
class GuestCreditEntry extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $table = 'guest_credit_ledger';

    public const DIRECTION_CREDIT = 'credit';

    public const DIRECTION_DEBIT = 'debit';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADD_ON_CANCELLED = 'add_on_cancelled';

    public const TYPE_REFERRAL = 'referral';

    public const TYPE_LOYALTY = 'loyalty';

    public const TYPE_BIRTHDAY = 'birthday';

    public const TYPE_PROMO = 'promo';

    public const TYPE_APPLIED = 'applied';

    public const TYPE_REVERSAL = 'reversal';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'organization_id',
        'guest_id',
        'direction',
        'type',
        'amount_minor',
        'currency',
        'description',
        'reverses_id',
        'source_type',
        'source_id',
        'expires_at',
        'idempotency_key',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'expires_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<GuestCreditEntry, $this>
     */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }
}
