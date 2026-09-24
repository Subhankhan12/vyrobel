<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformFunnel;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** ADMIN 01 · PHASE 4 — the signup funnel by source, from recorded facts only ({@see PlatformFunnel}). */
class FunnelController extends Controller
{
    public function index(Request $request, PlatformQuery $platform, PlatformFunnel $funnel): View
    {
        $includeInternal = $request->query('internal') === 'include';

        return view('admin.funnel', [
            'f' => $platform->read('platform.funnel', fn (): array => $funnel->compute($includeInternal), ['include_internal' => $includeInternal]),
        ]);
    }
}
