<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformHotelStats;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** ADMIN 01 · PHASE 5 — hotel-platform statistics, module adoption and cohorts. Aggregates only ({@see PlatformHotelStats}). */
class HotelStatsController extends Controller
{
    public function index(Request $request, PlatformQuery $platform, PlatformHotelStats $stats): View
    {
        $includeInternal = $request->query('internal') === 'include';

        return view('admin.hotels', [
            's' => $platform->read('platform.hotel_stats', fn (): array => $stats->compute($includeInternal), ['include_internal' => $includeInternal]),
        ]);
    }
}
