<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An owner's per-signal ATTENTION STATE (Wave A · GATE 1) — owner-scoped via {@see BelongsToOwner} (a portal
 * read only ever sees the authenticated owner's own rows). Records what the owner DID with a real attention
 * signal: snoozed it until a date, dismissed it, or read it. It never fabricates a signal — a row only exists
 * for a signal {@see App\Support\Owner\OwnerAttention} genuinely raised. Shared by My Units + the Overview feed.
 *
 * @property string $attention_key
 * @property ?Carbon $snoozed_until
 * @property ?Carbon $dismissed_at
 * @property ?Carbon $read_at
 */
class OwnerAttentionState extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'attention_key',
        'snoozed_until',
        'dismissed_at',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snoozed_until' => 'datetime',
            'dismissed_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /** Hidden now = dismissed, or snoozed to a moment still in the future. */
    public function isHidden(): bool
    {
        if ($this->dismissed_at !== null) {
            return true;
        }

        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }
}
