<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Issued proforma/tax invoice. PDF referenced via `media_id` (shared media infra). Correct-by-reversal.
 */
class Invoice extends Model
{
    use BelongsToOrganization, HasDocuments, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'folio_id',
        'property_id',
        'number',
        'type',
        'issued_at',
        'tax_breakdown',
        'media_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'tax_breakdown' => 'array',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
