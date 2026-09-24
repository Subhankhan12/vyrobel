<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ADMIN 01 · PHASE 5 — one incident on the PUBLIC status page. ⚠️ Every word is written by an admin for the public;
 * nothing here is ever derived from an internal (a release, a queue, a config value, a health check).
 *
 * @property string $title
 * @property string $impact
 * @property string $status
 * @property string $message
 * @property Carbon $started_at
 * @property Carbon|null $resolved_at
 */
class StatusIncident extends Model
{
    use HasUlids;

    public const IMPACTS = ['minor' => 'Minor', 'major' => 'Major'];

    public const STATUSES = ['investigating' => 'Investigating', 'identified' => 'Identified', 'monitoring' => 'Monitoring', 'resolved' => 'Resolved'];

    /** Resolved incidents stay on the public page this long. */
    public const SHOW_RESOLVED_DAYS = 14;

    protected $fillable = ['title', 'impact', 'status', 'message', 'started_at', 'resolved_at', 'created_by'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function isOpen(): bool
    {
        return $this->status !== 'resolved';
    }
}
