<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformAdminInvites;
use App\Support\Platform\PlatformAdminPasswordPolicy;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ADMIN 01 · PHASE 1 — accepting a platform-admin invitation: the new admin chooses their OWN password.
 *
 * PUBLIC (the invitee has no session yet) but rate-limited, and useless without a 64-character one-time token
 * whose hash matches an unexpired, unspent invitation. It does NOT sign the person in: they go to the normal
 * admin login and are forced to enrol 2FA there — the same door as every other admin, no side entrance.
 *
 * ⚠️ An unknown, expired or spent link gets the same neutral page, so the link cannot be probed for validity.
 */
class AdminInviteController extends Controller
{
    public function __construct(
        private readonly PlatformAdminInvites $invites,
        private readonly PlatformAuditLogger $audit,
    ) {}

    public function show(string $token): View
    {
        if (Auth::guard('admin')->check()) {
            // Someone already signed into the console opening an invite — do not mix the two.
            return view('admin.auth.invite-invalid', ['message' => 'Sign out of the console first, then open the invitation link again.']);
        }

        $admin = $this->invites->find($token);

        return $admin === null
            ? view('admin.auth.invite-invalid', ['message' => 'This invitation link is not valid. It may have expired or already been used — ask an Owner for a new one.'])
            : view('admin.auth.invite-accept', ['admin' => $admin, 'token' => $token, 'hint' => PlatformAdminPasswordPolicy::hint()]);
    }

    public function store(Request $request, string $token): RedirectResponse|View
    {
        $admin = $this->invites->find($token);

        if ($admin === null) {
            return view('admin.auth.invite-invalid', ['message' => 'This invitation link is not valid. It may have expired or already been used — ask an Owner for a new one.']);
        }

        $request->validate(['password' => ['required', 'confirmed', PlatformAdminPasswordPolicy::rule()]]);

        $this->invites->accept($admin, (string) $request->string('password'));
        $this->audit->record('platform.admin.invite_accepted', ['role' => $admin->role], 'platform_admin', $admin->id, actor: $admin);

        return redirect()->route('admin.login')
            ->with('status', 'Your password is set. Sign in now — you will be asked to set up two-factor authentication before the console opens.');
    }
}
