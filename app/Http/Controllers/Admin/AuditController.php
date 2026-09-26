<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * ADMIN 01 · PHASE 1 — the platform-admin AUDIT LOG viewer: every console action, who, when, what, target, why.
 *
 * ⚠️ READ-ONLY, BY CONSTRUCTION: there is no edit or delete route, and the ledger itself refuses UPDATE and
 * DELETE at the model and at the connection (PlatformWriteGuard) — so there is nothing here to protect by a
 * missing button alone.
 *
 * ⚠️ DEFAULTS TO WRITES + SIGN-INS. Every console page view also writes a row (reads are audited too), so an
 * unfiltered list would bury the actions that matter under page views. "All" and "Reads" are one click away.
 */
class AuditController extends Controller
{
    /** Audit labels that record a READ (a page view / cross-tenant read), not a change. */
    /**
     * ⚠️ ADMIN 03 · F1 — EVERY READ ACTION, OR THE DEFAULT "CHANGES & SIGN-INS" VIEW FILLS WITH PAGE VIEWS. 🔬 Found
     * live: nine read actions added in Phases 2–5 were never listed here, so looking at the triage queue, revenue or
     * the privacy desk appeared as a CHANGE. ⚠️ A hand-kept list drifts silently — `AuditReadActionsAreCompleteTest`
     * now fails when any `PlatformQuery::read()` action, or any recorded `.view` / `.page` action, is missing here.
     */
    public const READ_ACTIONS = [
        'platform.dashboard', 'platform.health.view', 'platform.overview', 'platform.customers.list',
        'platform.customers.view', 'platform.activity.audit_slice', 'platform.switches.view', 'platform.audit.view',
        'platform.admins.view',
        // ADMIN 01 · Phases 2–5
        'platform.customers.triage', 'platform.customer.impact', 'platform.support.lookup', 'platform.break_glass.view',
        'platform.break_glass.page', 'platform.revenue', 'platform.funnel', 'platform.privacy.view', 'platform.hotel_stats',
        'platform.announcements.view', 'platform.customer.current_plan', 'platform.customer.delete_check',
    ];

    /** Audit labels that record a sign-in event rather than an action. */
    public const SIGNIN_ACTIONS = ['platform.login', 'platform.2fa_enrolled', 'platform.admin.invite_accepted'];

    public function __construct(private readonly PlatformAuditLogger $audit) {}

    public function index(Request $request): View
    {
        $filters = [
            'kind' => in_array($request->query('kind'), ['changes', 'reads', 'all'], true) ? (string) $request->query('kind') : 'changes',
            'admin' => (string) $request->query('admin', ''),
            'action' => (string) $request->query('action', ''),
            'tenant' => (string) $request->query('tenant', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $query = PlatformAdminAction::query()->orderByDesc('created_at')->orderByDesc('id');

        match ($filters['kind']) {
            'changes' => $query->whereNotIn('action', self::READ_ACTIONS),
            'reads' => $query->whereIn('action', self::READ_ACTIONS),
            default => null,
        };

        $query->when($filters['admin'] !== '', fn (Builder $q) => $q->where('platform_admin_id', $filters['admin']))
            ->when($filters['action'] !== '', fn (Builder $q) => $q->where('action', $filters['action']))
            ->when($filters['tenant'] !== '', fn (Builder $q) => $q->where('target_type', 'organization')->where('target_id', $filters['tenant']))
            ->when($this->date($filters['from']), fn (Builder $q, Carbon $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when($this->date($filters['to']), fn (Builder $q, Carbon $d) => $q->where('created_at', '<=', $d->endOfDay()));

        $rows = $query->paginate(50)->withQueryString();

        // Names for the page's rows only — organisations are the platform table, not tenant data.
        $admins = PlatformAdmin::query()->orderBy('name')->get(['id', 'name', 'email']);
        $orgIds = collect($rows->items())->where('target_type', 'organization')->pluck('target_id')->filter()->unique()->all();
        $orgNames = Organization::withTrashed()->whereIn('id', $orgIds)->pluck('name', 'id');

        $this->audit->record('platform.audit.view', array_filter($filters, fn ($v) => $v !== '' && $v !== 'changes'));

        return view('admin.audit.index', [
            'rows' => $rows,
            'filters' => $filters,
            'admins' => $admins,
            'adminNames' => $admins->pluck('name', 'id'),
            'orgNames' => $orgNames,
            'actions' => PlatformAdminAction::query()->distinct()->orderBy('action')->pluck('action'),
            'organizations' => Organization::withTrashed()->orderBy('name')->get(['id', 'name', 'is_internal']),
        ]);
    }

    private function date(string $value): ?Carbon
    {
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value) ?: null;
    }
}
