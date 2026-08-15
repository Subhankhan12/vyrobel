<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\GuestWaitlistEntry;
use App\Services\GuestDeletionService;
use App\Services\GuestWalletService;
use App\Services\GuestWishlistService;
use App\Support\Guest\GuestName;
use App\Support\Money\MoneyFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Guest Account (Guest Portal · GATE 3) — the authenticated guest's OWN profile, preferences, and account
 * deletion. Behind the guest guard (`auth:guest`).
 *
 * 🔒 SELF-ONLY / GUEST ISOLATION — the guard IS the resolution: the guest is ALWAYS {@see Guest()} = the
 * authenticated guest-guard user, NEVER a request-supplied id. There is no `{guest}` route param to tamper with,
 * so a guest can only ever read/edit their OWN record.
 *
 * DELETION (founder decision D2): "Delete my account" is a REVERSIBLE, 14-day SCHEDULED erase via
 * {@see GuestDeletionService} — never an immediate destroy. The account stays usable and the guest can UNDO
 * before the daily sweep runs the certified {@see Guest::anonymize()} + per-policy reservation cancellation.
 * EMAIL change stays deferred (it is the login identifier + drives the blind index — read-only here).
 */
class GuestAccountController extends Controller
{
    use ResolvesGuest;

    public function show(GuestWalletService $wallet, GuestWishlistService $saved): View
    {
        $guest = $this->guest();
        $waitlist = $saved->waitlist($guest);

        return view('guest.account', [
            'hero' => [
                'initials' => $this->initials((string) $guest->first_name, (string) $guest->last_name),
                'member_since' => $guest->created_at?->format('Y') ?? '—',
                'name' => GuestName::display($guest),
                'email' => (string) $guest->email,
            ],
            'kpi' => [
                'credit' => MoneyFormatter::display($wallet->spendableMinor($guest), 'EUR'),
                'stays' => (int) $guest->reservations()->count(),
                'cards' => 0,   // D3 — no saved-card vault (no raw PAN); honest 0
                'waitlists' => $waitlist->count(),
            ],
            'profile' => [
                'first_name' => (string) $guest->first_name,
                'last_name' => (string) $guest->last_name,
                'email' => (string) $guest->email,   // read-only — the login identifier
                'phone' => (string) $guest->phone,
                'nationality' => (string) $guest->nationality,
                'language' => (string) $guest->language,
            ],
            'waitlist' => $waitlist->map(fn (GuestWaitlistEntry $entry): array => [
                'id' => (string) $entry->getKey(),
                'room' => (string) ($entry->roomType->name ?? 'Room'),
                'dates' => $this->waitlistDates($entry),
            ])->all(),
            'marketingConsent' => (bool) $guest->marketing_consent,
            'deletion' => [
                'scheduled' => $guest->scheduled_deletion_at !== null,
                'on' => $guest->scheduled_deletion_at?->format('j M Y'),
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

    /** Preferences — the REAL #87 marketing-consent gate + language. SMS is honest-gated (no provider), never set. */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $guest = $this->guest();

        if ($request->has('marketing_consent')) {
            $guest->update(['marketing_consent' => $request->boolean('marketing_consent')]);
        }
        if ($request->filled('language')) {
            $guest->update(['language' => (string) $request->string('language')->substr(0, 10)]);
        }

        return redirect()->route('guest.account')->with('status', 'Your preferences have been saved.');
    }

    /** Change password — the CURRENT password is verified (against the guest guard) before the shared hash path. */
    public function changePassword(Request $request): RedirectResponse
    {
        $guest = $this->guest();

        $request->validate([
            'current_password' => ['required', 'current_password:guest'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $guest->changePasswordTo((string) $request->string('password'));   // the one shared hash + rotate path

        return redirect()->route('guest.account')->with('status', 'Your password has been changed.');
    }

    /**
     * SCHEDULE a reversible 14-day deletion (D2) — the guest must type DELETE. NEVER an immediate destroy: the
     * daily sweep runs the certified erase after the grace window; the guest stays logged in and can undo.
     */
    public function destroy(Request $request, GuestDeletionService $deletions): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:DELETE']], [
            'confirm.in' => 'Type DELETE to confirm.',
        ]);

        $effective = $deletions->schedule($this->guest());

        return redirect()->route('guest.account')->with('status',
            'Account deletion scheduled for '.$effective->format('j M Y').'. You can undo any time before then.');
    }

    /** Undo a scheduled deletion — the guest keeps their account. */
    public function undoDeletion(GuestDeletionService $deletions): RedirectResponse
    {
        $deletions->cancel($this->guest());

        return redirect()->route('guest.account')->with('status', 'Your account deletion has been cancelled — welcome back.');
    }

    private function initials(string $first, string $last): string
    {
        $initials = mb_strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));

        return $initials !== '' ? $initials : 'G';
    }

    private function waitlistDates(GuestWaitlistEntry $entry): string
    {
        if ($entry->check_in !== null && $entry->check_out !== null) {
            return $entry->check_in->format('j M').' → '.$entry->check_out->format('j M Y');
        }

        return $entry->flexible_month !== null ? 'Flexible · '.(string) $entry->flexible_month : 'Flexible dates';
    }
}
