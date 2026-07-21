<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Middleware\EnsurePrimaryOwner;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
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
        $ownerIds = $context->ownerIds();

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
                'expires' => $i->expires_at?->isFuture() ? 'Expires '.$i->expires_at->diffForHumans() : 'Expired',
            ])
            ->all();

        $viewerUser = auth()->user();

        return view('livewire.owner.team', [
            'viewer' => $this->viewer(),
            'you' => [
                'name' => (string) ($viewerUser->name ?? 'You'),
                'email' => (string) ($viewerUser->email ?? ''),
            ],
            'members' => $members,
            'invites' => $invites,
            'roles' => array_map(fn (string $r): array => [
                'value' => $r,
                'label' => OwnerTeamRole::label($r),
                'summary' => OwnerTeamRole::summary($r),
            ], OwnerTeamRole::ALL),
        ]);
    }

    /** A TRUTHFUL last-active label from the real `last_login_at` — never a fabricated relative time. */
    private function lastActive(?Carbon $lastLogin, string $status): string
    {
        if ($status === 'invited') {
            return 'Invite pending';
        }
        if ($status === 'disabled') {
            return 'Access removed';
        }
        if ($lastLogin === null) {
            return 'Hasn’t signed in yet';
        }

        return $lastLogin->isToday() ? 'Active today' : 'Last active '.$lastLogin->diffForHumans();
    }
}
