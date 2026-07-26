<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Support\Platform\PlatformActivity;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;

/**
 * SUPER ADMIN · BUILD 2 · GATE 1 — the Security & Activity view. Login-activity is a platform table (read
 * directly, admin-realm-gated); the cross-tenant AUDIT slice (account/business events across all orgs) runs
 * inside {@see PlatformQuery::read()}, so that access is audited + scope-restored.
 */
class ActivityController extends Controller
{
    public function __construct(
        private readonly PlatformQuery $platform,
        private readonly PlatformActivity $activity,
    ) {}

    public function index(): View
    {
        $logins = $this->activity->recentLogins();
        $failures24h = $this->activity->recentFailureCount();

        // The account/business events are tenant audit rows — read them via the audited cross-tenant path.
        $accountEvents = $this->platform->read(
            'platform.activity.audit_slice',
            fn (): array => $this->activity->recentAccountEvents(),
        );

        return view('admin.activity.index', [
            'logins' => $logins,
            'failures24h' => $failures24h,
            'accountEvents' => $accountEvents,
            'retentionDays' => LoginActivity::RETENTION_DAYS,
        ]);
    }
}
