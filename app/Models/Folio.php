<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Financial ledger header for a reservation. total/paid/balance are DERIVED from the append-only
 * ledger (items + payments) by FolioService — never edited ad hoc.
 */
class Folio extends Model
{
    use BelongsToOrganization, HasFactory, HasNotes, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'reservation_id',
        'currency',
        'status',
        'total',
        'total_minor',
        'paid',
        'paid_minor',
        'balance',
        'balance_minor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => MoneyCast::class.':total_minor,currency',
            'paid' => MoneyCast::class.':paid_minor,currency',
            'balance' => MoneyCast::class.':balance_minor,currency',
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return HasMany<FolioItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(FolioItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
