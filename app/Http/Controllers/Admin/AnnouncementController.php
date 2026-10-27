<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformAnnouncement;
use App\Support\Platform\AdminReason;
use App\Support\Platform\ConsoleFields;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ⚠️ ADMIN 01 · PHASE 5 — IN-APP ANNOUNCEMENTS to every tenant or chosen ones, with a start and an optional end,
 * dismissible by each person. Publishing and withdrawing are audited with a reason. ⚠️ Plain text only — the tenant
 * page escapes it. Nothing is emailed (mail is dark); the announcement IS the channel.
 */
class AnnouncementController extends Controller
{
    public function __construct(private readonly PlatformQuery $platform) {}

    public function index(): View
    {
        [$announcements, $organizations] = $this->platform->read('platform.announcements.view', fn (): array => [
            PlatformAnnouncement::query()->latest('starts_at')->limit(50)->get(),
            Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
        ], []);

        return view('admin.announcements', ['announcements' => $announcements, 'organizations' => $organizations]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
            'level' => ['required', Rule::in(array_keys(PlatformAnnouncement::LEVELS))],
            'audience' => ['required', Rule::in(['all', 'chosen'])],
            'organization_ids' => ['required_if:audience,chosen', 'array'],
            'organization_ids.*' => ['string', 'exists:organizations,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ], ConsoleFields::messages(), ConsoleFields::names());
        // ⚠️ ADMIN 05 — "Every organisation" with organisations ticked used to publish to EVERY tenant and drop the ticks
        //    without a word (and a failed submit reset the audience to "Every organisation"). Contradictory input is
        //    refused, never guessed at.
        if ($data['audience'] === 'all' && ($data['organization_ids'] ?? []) !== []) {
            return back()->withInput()->withErrors(['audience' => __('php/http/controllers/admin/announcement_controller.audience_mismatch')]);
        }
        $reason = AdminReason::from($request);

        // ⚠️ ADMIN 03 · F13 — the ledger row is written BEFORE the row exists, so it used to carry no target and a
        //    "publish" could never be tied to its later "withdraw". The id is minted first and both rows name it.
        $id = (new PlatformAnnouncement)->newUniqueId();

        $announcement = $this->platform->mutate('platform.announcement.publish', $reason, fn () => tap(new PlatformAnnouncement([
            'title' => $data['title'],
            'body' => $data['body'],
            'level' => $data['level'],
            'audience' => $data['audience'],
            'organization_ids' => $data['audience'] === 'chosen' ? array_values($data['organization_ids'] ?? []) : null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'created_by' => (string) auth('admin')->id(),
        ]), fn (PlatformAnnouncement $a) => $a->forceFill(['id' => $id])->save()), ['audience' => $data['audience'], 'organizations' => count($data['organization_ids'] ?? [])], 'announcement', $id);

        $chosen = count($data['organization_ids'] ?? []);
        $notice = match (true) {
            $data['audience'] === 'all' && $announcement->isLive() => __('php/http/controllers/admin/announcement_controller.published_all_live'),
            $data['audience'] === 'all' => __('php/http/controllers/admin/announcement_controller.published_all_scheduled'),
            $announcement->isLive() => trans_choice('php/http/controllers/admin/announcement_controller.published_chosen_live', $chosen, ['count' => $chosen]),
            default => trans_choice('php/http/controllers/admin/announcement_controller.published_chosen_scheduled', $chosen, ['count' => $chosen]),
        };

        return redirect()->route('admin.announcements.index')->with('admin_notice', $notice);
    }

    public function withdraw(Request $request, string $announcement): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $row = PlatformAnnouncement::query()->findOrFail($announcement);
        if ($row->withdrawn_at !== null) {
            return redirect()->route('admin.announcements.index')->withErrors(['announcement' => __('php/http/controllers/admin/announcement_controller.already_withdrawn')]);
        }

        $this->platform->mutate('platform.announcement.withdraw', $reason, fn () => $row->forceFill(['withdrawn_at' => now()])->save(), [], 'announcement', (string) $row->id);

        return redirect()->route('admin.announcements.index')->with('admin_notice', __('php/http/controllers/admin/announcement_controller.withdrawn'));
    }
}
