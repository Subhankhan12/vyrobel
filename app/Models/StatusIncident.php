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

    /** An incident status CODE ({@see STATUSES}' key) → its label in the reader's language, translated at call time (I18N-01). */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'investigating' => (string) __('php/models/status_incident.status_investigating'),
            'identified' => (string) __('php/models/status_incident.status_identified'),
            'monitoring' => (string) __('php/models/status_incident.status_monitoring'),
            'resolved' => (string) __('php/models/status_incident.status_resolved'),
            default => $status,
        };
    }

    /** An incident impact CODE ({@see IMPACTS}' key) → its label in the reader's language. */
    public static function impactLabel(string $impact): string
    {
        return match ($impact) {
            'minor' => (string) __('php/models/status_incident.impact_minor'),
            'major' => (string) __('php/models/status_incident.impact_major'),
            default => $impact,
        };
    }

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
