<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Platform\Health\HealthBoard;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\View\View;

/**
 * ADMIN 01 · PHASE 1 — `/admin`, the first screen after sign-in, is the "NOTHING IS SILENT" health board.
 *
 * It reads only platform-owned facts (the queue tables, the heartbeat cache, the log files, the release layout,
 * the resolved config tree, the switch table) — no tenant row — so it needs no scope lift and does not go
 * through the one door. The view is still recorded on the audit ledger, like every console page.
 *
 * The business numbers that used to live here moved to {@see OverviewController} — with internal organisations
 * excluded and the trial "plan value" removed.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly HealthBoard $board,
        private readonly PlatformAuditLogger $audit,
    ) {}

    public function index(): View
    {
        $cards = $this->board->cards();
        $this->audit->record('platform.health.view');

        return view('admin.dashboard', ['cards' => $cards]);
    }
}
