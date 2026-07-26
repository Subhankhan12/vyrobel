<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformMetrics;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;

/**
 * SUPER ADMIN · BUILD 1 · GATE 1 — the platform Overview dashboard: REAL metrics across ALL organizations.
 *
 * The ENTIRE aggregate is computed inside ONE {@see PlatformQuery::read()} call, so every cross-tenant query
 * runs under the audited, admin-guard-gated, scope-restored `runAsSuperAdmin` context — a single audited read
 * per dashboard load, never an ad-hoc bypass. Figures are real (#39); zero states are honest.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly PlatformQuery $platform,
        private readonly PlatformMetrics $metrics,
    ) {}

    public function index(): View
    {
        $metrics = $this->platform->read(
            'platform.dashboard',
            fn (): array => $this->metrics->compute(),
        );

        return view('admin.dashboard', ['m' => $metrics]);
    }
}
