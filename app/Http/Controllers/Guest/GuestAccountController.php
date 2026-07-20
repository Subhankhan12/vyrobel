<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Support\Guest\GuestName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Guest Account (Phase 4a · Sub-gate 4) — the authenticated guest's OWN profile, change-password, and account
 * removal. Behind the guest guard (`auth:guest`).
 *
 * 🔒 SELF-ONLY / GUEST ISOLATION — the guard IS the resolution: the guest is ALWAYS {@see Guest()} = the
 * authenticated guest-guard user, NEVER a request-supplied id. There is no `{guest}` route param to tamper with,
 * so a guest can only ever read/edit their OWN record — the isolation the whole 4b/4c portal relies on.
 *
 * DEFERRED (stated): EMAIL change — email is the login identifier AND drives the blind index, so changing it
 * needs re-verification of the new address + re-indexing; deferred honestly (the field is read-only here).
 * DELETION CHOICE (stated): "remove portal access" revokes the login (nulls the auth fields) but KEEPS the guest
 * row + PII + bookings — the property needs them for upcoming stays and its legal/financial records. This is
 * SAFER than an instant self-anonymize (which would strip a guest's name off an upcoming booking); full GDPR
 * erasure of personal data stays an operator-mediated action ({@see Guest::anonymize()}).
 */
class GuestAccountController extends Controller
{
    use ResolvesGuest;

    public function show(): View
    {
        $guest = $this->guest();

        return view('guest.account', [
            'name' => GuestName::display($guest),
            'profile' => [
                'first_name' => (string) $guest->first_name,
                'last_name' => (string) $guest->last_name,
                'email' => (string) $guest->email,   // read-only — the login identifier
                'phone' => (string) $guest->phone,
                'nationality' => (string) $guest->nationality,
                'language' => (string) $guest->language,
            ],
        ]);
    }

    /** Self-edit name / phone / country / language — NEVER the email (deferred). */
    public function updateProfile(Request $request): RedirectResponse
    {
        $guest = $this->guest();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'size:2'],
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        $guest->update($data);   // email is not in $data → untouched; the saving() hook keeps email_hash in step

        return redirect()->route('guest.account')->with('status', 'Your details have been saved.');
    }

    /** Change password — the CURRENT password is verified (against the guest guard) before the shared hash path. */
    public function changePassword(Request $request): RedirectResponse
    {
        $guest = $this->guest();

        $request->validate([
            'current_password' => ['required', 'current_password:guest'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $guest->changePasswordTo((string) $request->string('password'));   // the one shared hash + rotate path

        return redirect()->route('guest.account')->with('status', 'Your password has been changed.');
    }

    /** Remove PORTAL ACCESS (revoke the login) — keeps the guest row + PII + bookings; see the class docblock. */
    public function destroy(Request $request): RedirectResponse
    {
        $guest = $this->guest();

        $guest->forceFill([
            'password' => null,
            'email_verified_at' => null,
            'remember_token' => null,
        ])->save();

        Auth::guard('guest')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guest.login')->with('status', 'Your portal access has been removed.');
    }
}
