<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One immutable line in the append-only `owner_ledger` (Phase 3 · Module 1). Correct-by-reversal:
 * never edited/deleted — a reversal is a NEW row (`reverses_id` set, `direction` flipped). `amount`
 * is always positive; `direction` carries the sign for netting. No SoftDeletes by design.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon $service_date
 */
class OwnerLedgerEntry extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    protected $table = 'owner_ledger';

    protected $fillable = [
        'organization_id',
        'owner_id',
        'unit_id',
        'reservation_id',
        'entry_type',
        'direction',
        'amount_minor',
        'currency',
        'description',
        'service_date',
        'attribution_version',
        'reverses_id',
        'statement_id',
        'unverified',
        'prior_period_adjustment',
        'note',
        'meta',
        'idempotency_key',
        'source_type',
        'source_id',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class.':amount_minor,currency',
            'service_date' => 'date',
            'attribution_version' => 'integer',
            'unverified' => 'boolean',
            'prior_period_adjustment' => 'boolean',
            'meta' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Signed minor amount for netting: credit = +, debit = −.
     */
    public function signedMinor(): int
    {
        return $this->direction === 'credit' ? (int) $this->amount_minor : -(int) $this->amount_minor;
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<OwnerLedgerEntry, $this>
     */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(OwnerLedgerEntry::class, 'reverses_id');
    }
}
