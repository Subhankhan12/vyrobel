<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * #104b GATE 3 — a per-(property, week) "published" stamp for the roster. Edits stay live; publishing finalizes
 * + notifies. Tenant-scoped ({@see BelongsToOrganization}).
 *
 * @property Carbon $week_starts_on
 * @property Carbon $published_at
 */
class SchedulePublication extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = ['organization_id', 'property_id', 'week_starts_on', 'published_at', 'published_by'];

    protected function casts(): array
    {
        return ['week_starts_on' => 'date', 'published_at' => 'datetime'];
    }
}
