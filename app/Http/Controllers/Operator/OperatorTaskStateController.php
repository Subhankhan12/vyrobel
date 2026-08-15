<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Models\OperatorTaskState;
use App\Services\OwnerUnitRequestService;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * D1-1 — a staff member ticks (or un-ticks) one of their dashboard's derived task rows.
 *
 * ⚠️ THIS MARKS NOTHING COMPLETE. The rows are live summaries of outstanding work and clear themselves when
 * their real count reaches zero; this records only that THIS person has been through the row today. No count
 * changes, no downstream state moves, nothing becomes "ready" — so there is deliberately no lock and no
 * transaction here. That machinery belongs to transitions with side-effects
 * ({@see OwnerUnitRequestService::withdraw()}); adding it to a personal note would be cargo.
 *
 * 🔒 SELF-ONLY BY CONSTRUCTION, the same shape as {@see NotificationController}: the
 * row is written with `$request->user()`'s id, never a user id from the request, so a tick can only ever be
 * this person's own and there is no cross-user id to forge.
 *
 * 🔒 THE SCOPE COMES FROM {@see PropertyContext}, NOT THE REQUEST — the same property the dashboard actually
 * read. A request-supplied property could otherwise record a tick against a property this user cannot see.
 *
 * 🔒 THE TASK KEY IS A CLOSED SET ({@see OperatorTaskState::KEYS}). Free text here would turn the table into
 * an open junk store and let a tick exist for a task that does not.
 *
 * ⚠️ IDEMPOTENT TWICE OVER: `firstOrCreate` on the natural key, behind a UNIQUE index that enforces the same
 * thing at the database. A double-submit writes one row; un-ticking twice deletes nothing the second time.
 */
class OperatorTaskStateController extends Controller
{
    public function toggle(Request $request, PropertyContext $context): RedirectResponse
    {
        $validated = $request->validate([
            'task_key' => ['required', 'string', Rule::in(OperatorTaskState::KEYS)],
            'ticked' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $key = [
            'user_id' => $user->getKey(),
            // 'all' when the dashboard is aggregating every accessible property — never null, so the unique
            // index actually applies (a NULL would be distinct from every other NULL in MySQL).
            'scope_key' => $context->isAll() ? OperatorTaskState::SCOPE_ALL : (string) ($context->id() ?? OperatorTaskState::SCOPE_ALL),
            'task_key' => $validated['task_key'],
            // The tick belongs to the day being viewed, so it cannot silently suppress tomorrow's recurrence.
            'task_date' => $this->viewedDate($request),
        ];

        if ($request->boolean('ticked')) {
            // organization_id is auto-filled by BelongsToOrganization from the active tenant.
            OperatorTaskState::query()->firstOrCreate($key);
        } else {
            OperatorTaskState::query()->where($key)->delete();
        }

        return back();
    }

    /**
     * The date the dashboard was showing — validated as a real date, defaulting to today. It only ever keys a
     * personal note, but an unvalidated string would still land in a date column.
     */
    private function viewedDate(Request $request): string
    {
        $date = $request->input('task_date');

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        return now()->toDateString();
    }
}
