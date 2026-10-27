<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * D1-1 — one staff member's PERSONAL tick against a derived dashboard task, for one day, in one property
 * scope. See the migration for why this is a personal note and not a completion record.
 *
 * ⚠️ THE KEY SET IS CLOSED. `task_key` may only ever hold a value the dashboard itself produces
 * ({@see self::KEYS}); the write path validates against this list. Without that the table is a free-text
 * junk store any POST could fill, and a tick could be recorded against a "task" that does not exist.
 */
class OperatorTaskState extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    /**
     * Every derived task row the dashboard can render, and the ONLY values `task_key` accepts.
     * ⚠️ Keys are STABLE STRINGS, not positions: the rendered list changes shape as counts come and go, so a
     * tick keyed by index would silently attach itself to a different task the moment one cleared.
     *
     * @var list<string>
     */
    public const KEYS = [
        'work_orders.urgent',
        'work_orders.open',
        'housekeeping.to_clean',
        'arrivals.to_check_in',
    ];

    /** The scope value for the "All properties" dashboard view — never null (see the migration). */
    public const SCOPE_ALL = 'all';

    /**
     * D1-2 — the CATEGORY each task belongs to, derived from the key rather than stored a second time
     * (§N.8f). The category is already implicit in the key's own namespace, so reading it here means a new
     * task type cannot arrive with a key but no category — the two cannot drift apart.
     *
     * @var array<string, string>
     */
    public const CATEGORY_LABELS = [
        'work_orders' => 'Work orders',
        'housekeeping' => 'Housekeeping',
        'arrivals' => 'Arrivals',
    ];

    /** The category namespace of a task key (`work_orders.urgent` → `work_orders`). */
    public static function categoryFor(string $taskKey): string
    {
        return (string) str($taskKey)->before('.');
    }

    /**
     * The order the category sections are shown in when the dashboard groups by category: the order the English
     * labels sorted into before the labels were translated (Arrivals · Housekeeping · Work orders), fixed by CODE
     * so a translation can never reorder the sections (I18N-01).
     *
     * @var list<string>
     */
    public const CATEGORY_ORDER = ['arrivals', 'housekeeping', 'work_orders'];

    /** The human label for a task key's category; falls back to the namespace so nothing renders blank. */
    public static function categoryLabelFor(string $taskKey): string
    {
        return self::categoryLabel(self::categoryFor($taskKey));
    }

    /** A category CODE → its label in the reader's language (translated at call time, keyed by the code). */
    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'work_orders' => (string) __('php/models/operator_task_state.category_work_orders'),
            'housekeeping' => (string) __('php/models/operator_task_state.category_housekeeping'),
            'arrivals' => (string) __('php/models/operator_task_state.category_arrivals'),
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }

    /** A category CODE's position in {@see CATEGORY_ORDER}; an unknown category sorts after the known ones. */
    public static function categorySortIndex(string $category): int
    {
        $i = array_search($category, self::CATEGORY_ORDER, true);

        return $i === false ? count(self::CATEGORY_ORDER) : $i;
    }

    protected $fillable = [
        'organization_id',
        'user_id',
        'scope_key',
        'task_key',
        'task_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'task_date' => 'date',
        ];
    }
}
