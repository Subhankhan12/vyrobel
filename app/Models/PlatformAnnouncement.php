<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ADMIN 01 · PHASE 5 — an in-app announcement from Vyrobel to its tenants. ⚠️ A PLATFORM record (no tenant scope);
 * written only by the console's audited actions; its text is PLAIN and always rendered escaped.
 *
 * @property string $title
 * @property string $body
 * @property string $level
 * @property string $audience
 * @property list<string>|null $organization_ids
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $withdrawn_at
 */
class PlatformAnnouncement extends Model
{
    use HasUlids;

    public const LEVELS = ['info' => 'Information', 'warning' => 'Warning'];

    protected $fillable = ['title', 'body', 'level', 'audience', 'organization_ids', 'starts_at', 'ends_at', 'withdrawn_at', 'created_by'];

    protected function casts(): array
    {
        return ['organization_ids' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    /**
     * Showing NOW: started, not ended, not withdrawn.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLive($query): void
    {
        $query->whereNull('withdrawn_at')->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function reaches(string $organizationId): bool
    {
        return $this->audience === 'all' || in_array($organizationId, (array) $this->organization_ids, true);
    }

    public function isLive(): bool
    {
        return $this->withdrawn_at === null && $this->starts_at->lessThanOrEqualTo(now())
            && ($this->ends_at === null || $this->ends_at->greaterThan(now()));
    }
}
