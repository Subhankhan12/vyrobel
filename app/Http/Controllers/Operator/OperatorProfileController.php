<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Operator SELF-PROFILE write (Phase 1 · Sub-gate 5) — the logged-in operator edits their OWN name + work
 * email, via the operator-mutation pattern (POST → controller → PRG). A REAL persist on the `users` row, not a
 * toast-only save.
 *
 * ⚠️ SELF-ONLY, and the guard is the resolution itself: the user is `$request->user()` — the authenticated
 * login — and there is NO user id anywhere in the request. So an operator can only ever write their OWN row;
 * there is no id to tamper with to reach someone else's (the change-password / owner-self-edit discipline).
 * `email` is the login identifier (plaintext, unlike guest PII) and is kept UNIQUE (ignoring self) so a change
 * can't collide with another account. The audit records WHICH fields changed, never their values.
 */
class OperatorProfileController extends Controller
{
    public function update(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();   // SELF — the authenticated login, never a request-supplied id

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
        ]);

        $user->forceFill($data)->save();

        $audit->record('user.profile_updated', $user, null, ['fields' => array_keys($data)]);

        return redirect()->to(route('app.profile'))->with('profile_notice', 'Your profile is saved.');
    }
}
