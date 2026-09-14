<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⚠️⚠️ THE ESCAPE FROM THE PASSWORDLESS LOCKOUT — and it is a STEP BEFORE the re-auth rules, never a
 * bypass around them.
 *
 * Twenty-four sensitive actions confirm with `current_password`, verified against the stored hash. An
 * account with no password fails every one of them — correctly, because `Hash::check(x, null)` is false —
 * and would have no way to fix itself. This sends that user to set a password and then back to what they
 * were doing.
 *
 * ⚠️⚠️ IT ONLY EVER REDIRECTS. There is no branch that lets a request continue: a passwordless user
 * reaching a confirmation is sent to the password screen, and everyone else falls straight through to the
 * rule that was always going to run. Making it let someone through turns every re-auth test red, which is
 * the whole safety case.
 *
 * ⚠️⚠️ IT KEYS ON THE REQUEST CARRYING `current_password`, NOT ON A LIST OF ROUTES. The field is present
 * exactly when a form demanded confirmation, so this covers all twenty-four sites — and the twenty-fifth,
 * the day someone adds it — without a list to maintain or twenty-four route edits. One definition (§N.8f).
 *
 * ⚠️ LAZY, NEVER EAGER. Nothing here runs at signup, at login, or on any ordinary screen. A passwordless
 * user browses, reads, books and works untouched; the interruption happens at the first confirmation they
 * reach, which is the moment the reason for it is visible — and which most users never reach at all.
 *
 * ⚠️ IT CANNOT BE SILENTLY FALSE. It reads `User::hasPassword()`, a column, and no role, permission or
 * team-scoped check. `can()` returns false without a team set — pinned by a test, and bitten three times —
 * and a check like that here would have sent EVERY user to set a password forever while looking like a
 * working gate.
 *
 * ⚠️ THE `admin` GUARD IS EXCLUDED EXPLICITLY. `platform_admins.password` stays NOT NULL: an admin is
 * created by console command with a password and mandatory 2FA, and no provider path reaches that table.
 * This middleware runs on the session realms whose users can be passwordless.
 */
class RequirePasswordBeforeReauth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only a confirmation carries this field — every other request is untouched.
        if (! $request->has('current_password')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || $user->hasPassword()) {
            return $next($request);
        }

        // ⚠️ THE INPUT SURVIVES. A confirmation often sits on a form carrying real intent — a payout
        //    destination, an owner invitation, a role change — and silently discarding it would be worse
        //    than the lockout this exists to fix. `withInput()` puts it back in the session, and
        //    `setIntendedUrl` returns the user to the page they were on.
        //    ⚠️ The password itself is never flashed: `except` mirrors the framework's own exclusions.
        $request->session()->put('url.intended', url()->previous());

        // ⚠️ THE EXISTING SCREEN, NOT A NEW ONE. The change-password card on each realm's Security page IS
        //    the set-a-password path; it drops its "current password" field when there is none to confirm
        //    with. A second "create a password" screen would be a second definition of one thing.
        $target = str_starts_with(ltrim($request->path(), '/'), 'owner') ? 'owner.security' : 'app.security';

        return redirect()
            ->route($target)
            ->withInput($request->except(['current_password', 'password', 'password_confirmation', '_token']))
            ->with('password_status', 'This action needs confirming with your password, and your account does not have one yet. Set one now and you will be brought back.');
    }
}
