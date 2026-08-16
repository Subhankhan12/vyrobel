<?php

declare(strict_types=1);

namespace App\Models;

use App\Livewire\Operator\OperatorDashboard;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * D1-2 — how one staff member likes their dashboard arranged. Personal by construction (keyed on `user_id`),
 * so a colleague's choice never changes your view — the same ruling D1-1 made for ticks, and for the same
 * reason: a display preference must never reach across people.
 *
 * ⚠️ THIS CHANGES ARRANGEMENT ONLY. It never filters, hides or reorders by importance — see
 * {@see OperatorDashboard::tasks()}, where the rows and their real counts are derived
 * before any preference is read.
 */
class OperatorDashboardPreference extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    /** How the task list may be grouped — the design's two-button segmented control, and nothing else. */
    public const GROUPINGS = ['priority', 'category'];

    /** The design's default: priority first, so the most urgent work leads the list unless asked otherwise. */
    public const GROUPING_DEFAULT = 'priority';

    protected $fillable = [
        'organization_id',
        'user_id',
        'task_grouping',
    ];

    /**
     * This user's chosen grouping, or the default. ⚠️ Falls back to the default for an unknown stored value
     * as well as a missing row — a preference read must never be able to produce a grouping the view cannot
     * render.
     */
    public static function groupingFor(?string $userId): string
    {
        if ($userId === null) {
            return self::GROUPING_DEFAULT;
        }

        $stored = (string) (self::query()->where('user_id', $userId)->value('task_grouping') ?? '');

        return in_array($stored, self::GROUPINGS, true) ? $stored : self::GROUPING_DEFAULT;
    }
}
