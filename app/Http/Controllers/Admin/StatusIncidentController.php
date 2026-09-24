<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StatusIncident;
use App\Support\Platform\AdminReason;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ⚠️ ADMIN 01 · PHASE 5 — THE PUBLIC STATUS PAGE'S INCIDENTS, written by hand. ⚠️ Everything posted here is PUBLIC;
 * the form says so. The page shows only what is posted — never a release, a queue depth, a config value or a check.
 */
class StatusIncidentController extends Controller
{
    public function __construct(private readonly PlatformQuery $platform) {}

    public function index(): View
    {
        return view('admin.status', ['incidents' => StatusIncident::query()->latest('started_at')->limit(50)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'impact' => ['required', Rule::in(array_keys(StatusIncident::IMPACTS))],
            'status' => ['required', Rule::in(['investigating', 'identified', 'monitoring'])],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $reason = AdminReason::from($request);

        $this->platform->mutate('platform.status.incident_open', $reason, fn () => StatusIncident::create($data + [
            'started_at' => now(), 'created_by' => (string) auth('admin')->id(),
        ]), ['impact' => $data['impact']], 'status_incident', null);

        return redirect()->route('admin.status.index')->with('admin_notice', 'Posted to the public status page.');
    }

    public function update(Request $request, string $incident): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(StatusIncident::STATUSES))],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $reason = AdminReason::from($request);
        $row = StatusIncident::query()->findOrFail($incident);
        if (! $row->isOpen()) {
            return redirect()->route('admin.status.index')->withErrors(['status' => 'That incident is already resolved.']);
        }

        $this->platform->mutate('platform.status.incident_'.$data['status'], $reason, fn () => $row->update([
            'status' => $data['status'],
            'message' => $data['message'],
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]), ['from' => $row->status], 'status_incident', (string) $row->id);

        return redirect()->route('admin.status.index')->with('admin_notice', 'Updated on the public status page.');
    }
}
