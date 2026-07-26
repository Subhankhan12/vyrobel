<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A charge line. Posted lines are correct-by-reversal — never deleted/edited by services.
 */
class FolioItem extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'folio_id',
        'type',
        'description',
        'unit_amount',
        'unit_amount_minor',
        'quantity',
        'amount',
        'amount_minor',
        'tax',
        'tax_minor',
        'currency',
        'reference_type',
        'reference_id',
        'service_date',
        'posted_by',
        'posted_at',
        'idempotency_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_amount' => MoneyCast::class.':unit_amount_minor,currency',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'tax' => MoneyCast::class.':tax_minor,currency',
            'service_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
