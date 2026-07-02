<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only reservation lifecycle audit (only `created_at`).
 */
class ReservationStatusHistory extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $table = 'reservation_status_history';

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'reservation_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
