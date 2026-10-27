<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Middleware\EnsurePrimaryOwner;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\ExportDownload;
use App\Models\OwnerTeamInvite;
use App\Models\OwnerUser;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * OWNER TEAM (Phase 7c · SG3) — the PRIMARY OWNER's view of their team: themselves (the implicit co-owner),
 * their sub-user members ({@see OwnerUser}), and any pending {@see OwnerTeamInvite}s, plus the invite /
 * change-role / remove controls. READ-ONLY component: every mutation is a real controller route behind
 * {@see EnsurePrimaryOwner} (so the owner middleware — which does NOT run on
 * /livewire/update — gates every write), never a Livewire action.
 *
 * 🔒 OWNER SCOPING: members + invites are read for the owner ids the acting primary owner holds
 * ({@see OwnerContext::ownerIds()}) — never another owner's. The route gate guarantees the viewer is the
 * primary owner; this screen is unreachable by a sub-user (403).
 */
#[Layout('components.layouts.owner')]
class OwnerTeam extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $context = app(OwnerContext::class);
        // 🔒 P1-1: the team screen manages ONLY the teams of owners this user is the PRIMARY of — never an owner
        // they are merely a sub-user of (the union). Matches OwnerTeamController's per-owner write scope.
        $ownerIds = $context->primaryOwnerIds();

        $members = OwnerUser::query()
            ->whereIn('owner_id', $ownerIds)
            ->with('user:id,name,email,status,last_login_at')
            ->get()
            ->map(fn (OwnerUser $m): array => [
                'id' => (string) $m->getKey(),
                'name' => (string) $m->user->name,
                'email' => (string) $m->user->email,
                'role' => $m->role,
                'role_label' => OwnerTeamRole::label($m->role),
                'role_summary' => OwnerTeamRole::summary($m->role),
                'status' => $m->status,
                'active' => $m->status === 'active',
                'last_active' => $this->lastActive($m->user->last_login_at, (string) $m->user->status),
            ])
            ->all();

        $invites = OwnerTeamInvite::query()
            ->whereIn('owner_id', $ownerIds)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OwnerTeamInvite $i): array => [
                'id' => (string) $i->getKey(),
                'email' => (string) $i->email,
                'role_label' => OwnerTeamRole::label((string) $i->role),
                'expires' => $i->expires_at?->isFuture() ? __('php/livewire/owner/owner_team.expires', ['when' => $i->expires_at->diffForHumans()]) : __('php/livewire/owner/owner_team.expired'),
            ])
            ->all();

        $viewerUser = auth()->user();

        return view('livewire.owner.team', [
            'viewer' => $this->viewer(),
            'you' => [
                'name' => (string) ($viewerUser->name ?? __('php/livewire/owner/owner_team.you')),
                'email' => (string) ($viewerUser->email ?? ''),
            ],
            'members' => $members,
            'invites' => $invites,
            'roles' => array_map(fn (string $r): array => [
                'value' => $r,
                'label' => OwnerTeamRole::label($r),
                'summary' => OwnerTeamRole::summary($r),
            ], OwnerTeamRole::ALL),
            // The REAL "what each role can see" matrix — derived verbatim from OwnerTeamRole::CAPABILITIES (the
            // SAME grid the route gates + @ownercan read), so it can never disagree with what a role grants (#39).
            'matrix' => $this->matrix(),
            // A TRUTHFUL recent-activity list from real signals only (member sign-ins + invites sent) — never a
            // fabricated event log (owner-team actions are not audited): no invented "who did what when".
            'activity' => $this->activity($ownerIds, $members),
            // Wave B · GATE 5 — the REAL financial-export download log (who downloaded what, when), owner-scoped.
            'downloads' => $this->downloadLog($ownerIds),
        ]);
    }

    /**
     * The REAL export DOWNLOAD LOG (Wave B · GATE 5) — recent financial exports downloaded by this owner's team,
     * owner-scoped (the acting primary owner's owner ids). The SAME record whose identity is watermarked onto the
     * file; nothing invented (an empty list is honest). Newest first, capped.
     *
     * @param  list<string>  $ownerIds
     * @return list<array{who: string, what: string, when: string}>
     */
    private function downloadLog(array $ownerIds): array
    {
        if ($ownerIds === []) {
            return [];
        }

        return ExportDownload::query()
            ->whereIn('owner_id', $ownerIds)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn (ExportDownload $d): array => [
                'who' => (string) ($d->downloader_name ?? __('php/livewire/owner/owner_team.unknown_user')),
                'what' => (string) $d->label,
                'when' => $d->created_at instanceof Carbon ? $d->created_at->diffForHumans() : '—',
            ])
            ->all();
    }

    /**
     * The capability matrix, row per area × the three roles — read straight from {@see OwnerTeamRole::allows}
     * so it reflects the ACTUAL capabilities (anti-drift with the route gates), never a hand-kept table.
     *
     * @return list<array{label: string, co_owner: bool, accountant: bool, view_only: bool}>
     */
    private function matrix(): array
    {
        $rows = [
            [__('php/livewire/owner/owner_team.area_financials'), OwnerTeamRole::CAP_FINANCIALS],
            [__('php/livewire/owner/owner_team.area_actions'), OwnerTeamRole::CAP_ACTIONS],
            [__('php/livewire/owner/owner_team.area_documents'), OwnerTeamRole::CAP_DOCUMENTS],
            [__('php/livewire/owner/owner_team.area_calendar'), OwnerTeamRole::CAP_CALENDAR],
            [__('php/livewire/owner/owner_team.area_exports'), OwnerTeamRole::CAP_EXPORTS],
            [__('php/livewire/owner/owner_team.area_payout'), OwnerTeamRole::CAP_PAYOUT],
        ];

        return array_map(fn (array $r): array => [
            'label' => $r[0],
            'co_owner' => OwnerTeamRole::allows(OwnerTeamRole::CO_OWNER, $r[1]),
            'accountant' => OwnerTeamRole::allows(OwnerTeamRole::ACCOUNTANT, $r[1]),
            'view_only' => OwnerTeamRole::allows(OwnerTeamRole::VIEW_ONLY, $r[1]),
        ], $rows);
    }

    /**
     * TRUTHFUL recent activity — the newest real signals only: each member's real last sign-in and each real
     * pending invite (when it was sent). No audited team-event log exists, so nothing here is invented; a team
     * with no sign-ins/invites yet gets an empty list (the caller shows an honest empty state).
     *
     * @param  list<string>  $ownerIds
     * @param  list<array<string, mixed>>  $members
     * @return list<array{who: string, what: string, when: string}>
     */
    private function activity(array $ownerIds, array $members): array
    {
        $events = [];

        // Real sign-ins (only members who have actually signed in — never a fabricated "active now").
        $logins = OwnerUser::query()
            ->whereIn('owner_id', $ownerIds)
            ->with('user:id,name,last_login_at')
            ->get();
        foreach ($logins as $m) {
            $login = $m->user->last_login_at;
            if ($login !== null) {
                $events[] = ['who' => (string) $m->user->name, 'what' => __('php/livewire/owner/owner_team.signed_in'), 'when' => $login->diffForHumans(), 'ts' => $login];
            }
        }

        // Real invites sent (pending) — a truthful "invited X as Role".
        $sent = OwnerTeamInvite::query()
            ->whereIn('owner_id', $ownerIds)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
        foreach ($sent as $i) {
            $events[] = [
                'who' => __('php/livewire/owner/owner_team.you'),
                'what' => __('php/livewire/owner/owner_team.invited_as', ['email' => $i->email, 'role' => OwnerTeamRole::label((string) $i->role)]),
                'when' => $i->created_at?->diffForHumans() ?? '',
                'ts' => $i->created_at ?? now(),
            ];
        }

        usort($events, fn (array $a, array $b): int => $b['ts'] <=> $a['ts']);

        return array_map(fn (array $e): array => [
            'who' => $e['who'],
            'initials' => $this->initials($e['who']),
            'what' => $e['what'],
            'when' => $e['when'],
        ], array_slice($events, 0, 6));
    }

    /** A TRUTHFUL last-active label from the real `last_login_at` — never a fabricated relative time. */
    private function lastActive(?Carbon $lastLogin, string $status): string
    {
        if ($status === 'invited') {
            return __('php/livewire/owner/owner_team.invite_pending');
        }
        if ($status === 'disabled') {
            return __('php/livewire/owner/owner_team.access_removed');
        }
        if ($lastLogin === null) {
            return __('php/livewire/owner/owner_team.never_signed_in');
        }

        return $lastLogin->isToday()
            ? __('php/livewire/owner/owner_team.active_today')
            : __('php/livewire/owner/owner_team.last_active', ['when' => $lastLogin->diffForHumans()]);
    }
}
