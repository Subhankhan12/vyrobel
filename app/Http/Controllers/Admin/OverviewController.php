<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformMetrics;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The platform's BUSINESS overview — computed in ONE {@see PlatformQuery::read()} (read-only, both scopes
 * lifted, audited).
 *
 * ⚠️⚠️ ADMIN 01 · PHASE 1: internal organisations are EXCLUDED by default (`?internal=include` puts them back,
 * visibly), and there is no revenue figure until Phase 4 computes one from charges. With no customers the page
 * says "No customers yet" rather than laying out a row of zeroes that reads like a dead business.
 */
class OverviewController extends Controller
{
    public function __construct(
        private readonly PlatformQuery $platform,
        private readonly PlatformMetrics $metrics,
    ) {}

    public function index(Request $request): View
    {
        $includeInternal = $request->query('internal') === 'include';

        $m = $this->platform->read(
            'platform.overview',
            fn (): array => $this->metrics->compute($includeInternal),
            ['include_internal' => $includeInternal],
        );

        return view('admin.overview', ['m' => $m]);
    }
}
