<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Security\ActiveSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Operator SELF-PROFILE writes (#96) — the logged-in operator edits their OWN personal details + UI preferences,
 * and can sign out their other devices. Operator-mutation pattern (POST → controller → PRG). REAL persists on the
 * `users` row, never toast-only.
 *
 * ⚠️ SELF-ONLY, and the guard is the resolution itself: the user is `$request->user()` — the authenticated login,
 * with NO user id anywhere in the request. `email` is the login identifier (kept UNIQUE ignoring self). The audit
 * records WHICH fields changed, never their values.
 */
class OperatorProfileController extends Controller
{
    private const LOCALES = ['en', 'pt', 'es', 'fr'];

    private const DATE_FORMATS = ['DD/MM/YYYY', 'MM/DD/YYYY'];

    /** Personal details — name / preferred name / email / phone / language (locale) / timezone. */
    public function update(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();   // SELF — never a request-supplied id

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:40'],
            'locale' => ['nullable', Rule::in(self::LOCALES)],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $user->forceFill($data)->save();
        $audit->record('user.profile_updated', $user, null, ['fields' => array_keys($data)]);

        return redirect()->route('app.profile')->with('profile_notice', 'Your profile is saved.');
    }

    /** UI preferences — compact density / onboarding tips / interface motion / date format (stored + applied). */
    public function preferences(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'date_format' => ['nullable', Rule::in(self::DATE_FORMATS)],
        ]);

        $user->forceFill([
            'compact_density' => $request->boolean('compact_density'),
            'onboarding_tips' => $request->boolean('onboarding_tips'),
            'reduce_motion' => $request->boolean('reduce_motion'),
            'date_format' => $validated['date_format'] ?? $user->date_format ?? 'DD/MM/YYYY',
        ])->save();
        $audit->record('user.preferences_updated', $user, null, ['fields' => ['compact_density', 'onboarding_tips', 'reduce_motion', 'date_format']]);

        return redirect()->route('app.profile')->with('profile_notice', 'Preferences saved.');
    }

    /**
     * Sign out of all OTHER devices — REAL session revocation (#66, mirrors the owner path). Deletes the user's
     * other `sessions` rows (keeps the current one), rotates the remember-token so any "remember me" cookie dies.
     * Current-password confirmed (self-only, a sensitive action).
     */
    public function signOutOtherSessions(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $request->validate(['current_password' => ['required', 'current_password']]);

        // ⚠️ SESSIONS 01 — the ONE shared implementation (this used to carry its own copy of the delete query):
        // rows deleted, password re-hashed so every other session fails its hash check, remember-token rotated.
        $deleted = ActiveSessions::signOutOthers($request, $user, (string) $request->input('current_password'));
        $audit->record('user.other_sessions_signed_out', $user, null, ['count' => $deleted]);

        return redirect()->route('app.profile')->with('profile_notice', $deleted > 0
            ? 'Signed out of '.$deleted.' other '.Str::plural('session', $deleted).'.'
            : 'You have no other active sessions.');
    }
}
