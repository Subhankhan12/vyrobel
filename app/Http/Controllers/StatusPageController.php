<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StatusIncident;
use Illuminate\Contracts\View\View;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 5 — THE PUBLIC STATUS PAGE. MANUAL INCIDENTS ONLY.
 *
 * It shows what an admin has POSTED — open incidents, and ones resolved in the last
 * {@see StatusIncident::SHOW_RESOLVED_DAYS} days — and NOTHING ELSE: no release hash, no queue depth, no config state,
 * no health check, no uptime figure. ⚠️ And with nothing posted it says exactly that — "no incidents have been posted"
 * — never "all systems operational": that would be a claim about internals this page does not measure.
 */
class StatusPageController extends Controller
{
    public function __invoke(): View
    {
        $incidents = StatusIncident::query()
            ->where(fn ($q) => $q->where('status', '!=', 'resolved')->orWhere('resolved_at', '>=', now()->subDays(StatusIncident::SHOW_RESOLVED_DAYS)))
            ->orderByRaw("status = 'resolved'")
            ->latest('started_at')
            ->get(['title', 'impact', 'status', 'message', 'started_at', 'resolved_at', 'updated_at']);

        return view('status', ['incidents' => $incidents]);
    }
}
