<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAdmin;
use App\Support\Platform\AdminReason;
use App\Support\Platform\ConsoleFields;
use App\Support\Platform\PlatformAdminInvites;
use App\Support\Platform\PlatformAdminRole;
use App\Support\Platform\PlatformAdminSessions;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * ADMIN 01 · PHASE 1 — the platform ADMINS themselves: invite, assign a role, revoke, restore, see live sessions,
 * force sign-out. Owner-only to change (`admins.manage`); Analyst may look (`admins.view`).
 *
 * ⚠️⚠️ THE FLOORS, ENFORCED HERE (not just by hiding buttons):
 *   · the LAST ACTIVE OWNER cannot be demoted or revoked — the console must always keep someone who can manage it;
 *   · you cannot revoke YOURSELF (use sign-out; a self-revoke is an unrecoverable foot-gun);
 *   · every change needs the acting admin's CURRENT PASSWORD and a WRITTEN REASON, and is audited.
 * ⚠️ 2FA is mandatory for every admin, with no toggle anywhere — an invited admin enrols at first sign-in.
 */
class AdminUserController extends Controller
{
    public function __construct(
        private readonly PlatformAuditLogger $audit,
        private readonly PlatformAdminSessions $sessions,
        private readonly PlatformAdminInvites $invites,
    ) {}

    public function index(Request $request): View
    {
        $admins = PlatformAdmin::query()->orderByRaw("FIELD(status, 'active', 'invited', 'disabled')")->orderBy('name')->get();

        $active = [];
        foreach ($admins as $admin) {
            $active[$admin->id] = $this->sessions->activeFor($admin);
        }

        $this->audit->record('platform.admins.view');

        return view('admin.admins.index', [
            'admins' => $admins,
            'activeSessions' => $active,
            'currentHash' => $this->sessions->currentHash($request),
            'roles' => PlatformAdminRole::cases(),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('platform_admins', 'email')],
            'role' => ['required', Rule::enum(PlatformAdminRole::class)],
            'current_password' => ['required', 'current_password:admin'],
        ], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);

        [$admin, $token] = $this->invites->invite(
            (string) $request->string('name'),
            (string) $request->string('email'),
            PlatformAdminRole::from((string) $request->string('role')),
            $this->actor(),
        );

        $this->audit->record('platform.admin.invited', ['role' => $admin->role], 'platform_admin', $admin->id, reason: $reason);

        return redirect()->route('admin.admins.index')
            ->with('invite_link', route('admin.invite.show', $token))
            ->with('invite_for', $admin->name);
    }

    public function reissueInvite(Request $request, string $admin): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $target = $this->find($admin);
        abort_unless($target->status === PlatformAdmin::STATUS_INVITED, 409, 'Only a pending invitation can be re-issued.');

        $token = $this->invites->reissue($target);
        $this->audit->record('platform.admin.invite_reissued', [], 'platform_admin', $target->id, reason: $reason);

        return redirect()->route('admin.admins.index')
            ->with('invite_link', route('admin.invite.show', $token))
            ->with('invite_for', $target->name);
    }

    public function changeRole(Request $request, string $admin): RedirectResponse
    {
        $request->validate([
            'role' => ['required', Rule::enum(PlatformAdminRole::class)],
            'current_password' => ['required', 'current_password:admin'],
        ], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);
        $target = $this->find($admin);
        $to = PlatformAdminRole::from((string) $request->string('role'));
        $from = (string) $target->role;

        if ($from === PlatformAdminRole::Owner->value && $to !== PlatformAdminRole::Owner && $this->isLastActiveOwner($target)) {
            throw ValidationException::withMessages(['role' => 'This is the last active Owner. Make someone else an Owner first.']);
        }

        $target->forceFill(['role' => $to->value])->save();
        $this->audit->record('platform.admin.role_changed', ['from' => $from, 'to' => $to->value], 'platform_admin', $target->id, reason: $reason);

        return redirect()->route('admin.admins.index')->with('admin_notice', "{$target->name} is now {$to->label()}.");
    }

    public function revoke(Request $request, string $admin): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:admin']], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);
        $target = $this->find($admin);

        if ($target->is($this->actor())) {
            throw ValidationException::withMessages(['reason' => 'You cannot revoke your own access. Ask another Owner.']);
        }

        if ($target->role === PlatformAdminRole::Owner->value && $this->isLastActiveOwner($target)) {
            throw ValidationException::withMessages(['reason' => 'This is the last active Owner and cannot be revoked.']);
        }

        $target->forceFill(['status' => PlatformAdmin::STATUS_DISABLED, 'invite_token_hash' => null])->save();
        $ended = $this->sessions->endAllFor($target, 'revoked', $this->actor());
        $this->audit->record('platform.admin.revoked', ['sessions_ended' => $ended], 'platform_admin', $target->id, reason: $reason);

        return redirect()->route('admin.admins.index')->with('admin_notice', "{$target->name}'s access is revoked; {$ended} ".Str::plural('session', $ended).' ended.');
    }

    public function restore(Request $request, string $admin): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:admin']], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);
        $target = $this->find($admin);
        abort_unless($target->status === PlatformAdmin::STATUS_DISABLED, 409, 'Only a revoked admin can be restored.');

        $target->forceFill(['status' => PlatformAdmin::STATUS_ACTIVE])->save();
        $this->audit->record('platform.admin.restored', [], 'platform_admin', $target->id, reason: $reason);

        return redirect()->route('admin.admins.index')->with('admin_notice', "{$target->name}'s access is restored.");
    }

    /** End every live session of an admin — enforced on their next request, on any session driver. */
    public function signOut(Request $request, string $admin): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $target = $this->find($admin);
        $actor = $this->actor();

        // ⚠️⚠️ SESSIONS 01 — force sign-out can NEVER end the caller's own session. Pointed at yourself it used to end
        //    EVERY session including the one pressing the button — the last Owner signing themselves out of the
        //    console mid-incident, the same trap as a kill switch locking out the admin. On yourself it now ends your
        //    OTHER sessions only (a lost laptop), and this one stays.
        if ($target->is($actor)) {
            $ended = $this->sessions->endOthersFor($target, $request);
            $this->audit->record('platform.admin.signed_out', ['sessions_ended' => $ended, 'own_other_sessions_only' => true], 'platform_admin', $target->id, reason: $reason);

            return redirect()->route('admin.admins.index')->with('admin_notice', "Ended {$ended} of your other ".Str::plural('session', $ended).' — this one stays signed in.');
        }

        $ended = $this->sessions->endAllFor($target, 'forced', $actor);
        $this->audit->record('platform.admin.signed_out', ['sessions_ended' => $ended], 'platform_admin', $target->id, reason: $reason);

        return redirect()->route('admin.admins.index')->with('admin_notice', "Ended {$ended} ".Str::plural('session', $ended)." for {$target->name}.");
    }

    private function find(string $id): PlatformAdmin
    {
        return PlatformAdmin::query()->findOrFail($id);
    }

    private function actor(): PlatformAdmin
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof PlatformAdmin, 403);

        return $admin;
    }

    private function isLastActiveOwner(PlatformAdmin $target): bool
    {
        return PlatformAdmin::query()
            ->where('status', PlatformAdmin::STATUS_ACTIVE)
            ->where('role', PlatformAdminRole::Owner->value)
            ->whereKeyNot($target->getKey())
            ->doesntExist();
    }
}
