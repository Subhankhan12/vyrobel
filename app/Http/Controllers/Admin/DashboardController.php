<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;

/**
 * SUPER ADMIN · GATE 0 — the authenticated landing. Deliberately NOT a real dashboard yet (features arrive in
 * later gates). It exists to (a) prove the hardened realm renders end-to-end behind mandatory 2FA, and (b)
 * EXERCISE the safe cross-tenant read foundation: a single, audited, admin-guard-gated "how many organizations
 * exist" count through {@see PlatformQuery} (which wraps the EXISTING, tested `runAsSuperAdmin` closure). This
 * is the isolation exercise the gate calls for — established + proven, not a feature.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly PlatformQuery $platform) {}

    public function index(): View
    {
        // The ONE cross-tenant read in Gate 0 — audited, admin-guard-gated, scope restored after (see PlatformQuery).
        $organizationCount = $this->platform->read(
            'platform.count_organizations',
            fn (): int => Organization::query()->count(),
        );

        return view('admin.dashboard', [
            'organizationCount' => $organizationCount,
        ]);
    }
}
