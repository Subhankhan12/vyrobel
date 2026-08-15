<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * In-app CHANGE password — a logged-in operator or owner changes their OWN password. Same security posture
 * as the rest of the auth chapter (login / reset / invite):
 *   - The user is resolved from `auth()` ONLY — never a request id — so a session can only ever change its
 *     OWN account's password (the self-edit guard, as in the owner self-profile write). An operator cannot
 *     change an owner's password and vice-versa; there is no user id anywhere in the request.
 *   - 🔒 The CURRENT password is verified (the `current_password` rule → Hash::check against the real hash)
 *     BEFORE anything changes: a logged-in but hijacked / left-open session must prove it knows the current
 *     password. Non-negotiable — this is the anti-hijack guard.
 *   - The new password meets the SAME rules as reset (`required, confirmed, min:8`).
 *   - The hash + remember-token rotation is the ONE shared path {@see User::changePasswordTo()} — identical
 *     to `PasswordResetController::reset`, so the two can't drift. Rotating the remember token invalidates
 *     any "remember me" cookie held by OTHER sessions; THIS session stays signed in (no re-login here).
 *   - CSRF + rate-limit ride the route middleware; the response is a PRG.
 *
 * This is change-password ONLY. The design's Security screens also draw 2FA, session lists, connected apps
 * and IP rules — all unbacked subsystems (see docs/AUTH-INVENTORY.md) — which are deliberately NOT built
 * here and are shown as an honest "Soon", never faked.
 */
class ChangePasswordController extends Controller
{
    /** Operator side → back to the operator Security page. */
    public function operator(Request $request): RedirectResponse
    {
        return $this->change($request, route('app.security'));
    }

    /** Owner side → back to the owner Security page, where the change-password card lives. */
    public function owner(Request $request): RedirectResponse
    {
        return $this->change($request, route('owner.security'));
    }

    private function change(Request $request, string $back): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();   // SELF only — never a request-supplied id.

        $request->validate([
            // 🔒 verified against the authenticated user's real hash BEFORE any change (anti-hijack guard).
            'current_password' => ['required', 'current_password'],
            // The same rule set as PasswordResetController::reset — length + confirmation.
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // The ONE shared hash + remember-token-rotate path: invalidates other remembered sessions; this
        // session stays valid (the user is NOT logged out on the current device).
        // Keep the browser doing the change; every OTHER session is terminated.
        $user->changePasswordTo((string) $request->string('password'), $request->session()->getId());

        return redirect()->to($back)->with('password_status', 'Your password has been changed.');
    }
}
