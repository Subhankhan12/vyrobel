<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformQuery;
use App\Support\Platform\Revenue\PlatformRevenue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * ADMIN 01 · PHASE 4 — REVENUE, traced to the charge ({@see PlatformRevenue}). Read-only with respect to money: this
 * console never charges, refunds or transfers, and nothing here writes a money row.
 */
class RevenueController extends Controller
{
    public function index(Request $request, PlatformQuery $platform, PlatformRevenue $revenue): View
    {
        $includeInternal = $request->query('internal') === 'include';

        return view('admin.revenue', [
            'r' => $platform->read('platform.revenue', fn (): array => $revenue->compute($includeInternal), ['include_internal' => $includeInternal]),
        ]);
    }
}
