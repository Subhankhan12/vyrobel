<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\GuestWaitlistEntry;
use App\Services\GuestDeletionService;
use App\Services\GuestWalletService;
use App\Services\GuestWishlistService;
use App\Support\Email\GuestEmailPreferences;
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
use App\Support\I18n\Locales;
use App\Support\I18n\LocalText;
use App\Support\Money\MoneyFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function show(GuestWalletService $wallet, GuestWishlistService $saved, GuestDeletionService $deletions): View
    {
        $guest = $this->guest();
        $waitlist = $saved->waitlist($guest);

        return view('guest.account', [
            // ⚠️ GUEST EMAIL PREFERENCES — the guest's own opt-outs for the OPTIONAL transactional emails.
            // Separate from `marketingConsent` below on purpose: different consent basis, different store,
            // different gate. Collapsing them would let a transactional opt-out revoke marketing consent.
            'emailPrefs' => GuestEmailPreferences::forGuest($guest),
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
                'room' => (string) ($entry->roomType->name ?? __('php/http/controllers/guest/guest_account_controller.room_fallback')),
                'dates' => $this->waitlistDates($entry),
            ])->all(),
            'marketingConsent' => (bool) $guest->marketing_consent,
            'deletion' => [
                'scheduled' => $guest->scheduled_deletion_at !== null,
                'on' => $guest->scheduled_deletion_at?->translatedFormat(LocalDate::pattern('j M Y')),
                // ⚠️ The count the screen WARNS with, read from the same set the erase cancels
                // ({@see GuestDeletionService::reservationsToCancel()}) so the two can never disagree.
                'stays' => $deletions->reservationsToCancel($guest)->count(),
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
            'language' => ['nullable', 'string', Rule::in(Locales::planned())],
        ]);

        $guest->update($data);   // email is not in $data → untouched; the saving() hook keeps email_hash in step

        return redirect()->route('guest.account')->with('status', __('php/http/controllers/guest/guest_account_controller.details_saved'));
    }

    /** Preferences — the REAL #87 marketing-consent gate + language. SMS is honest-gated (no provider), never set. */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $guest = $this->guest();

        if ($request->has('marketing_consent')) {
            $guest->update(['marketing_consent' => $request->boolean('marketing_consent')]);
        }
        // I18N-01 — only a language Vyrobel plans to speak is stored (it was any 10 characters); one not finished yet is
        //    kept and shows English until it is, like every other saved choice.
        if ($request->filled('language') && Locales::isPlanned((string) $request->string('language'))) {
            $guest->update(['language' => (string) $request->string('language')]);
        }

        // ⚠️ THE GUEST'S OWN EMAIL OPT-OUTS. Only the OPTIONAL categories are storable — the helper refuses
        // anything else, so posting `email_pref=txn` or `pre_arrival` cannot switch off a transactional
        // record or the door-code nudge. Self-scoped by construction: it writes through $guest, which came
        // from the GUARD (ResolvesGuest), never from the request.
        //
        // ⚠️ NOT ROUTED THROUGH `marketing_consent` — that flag and its unsubscribe flow are a separate
        // control on a separate legal basis, and are untouched by this write.
        // ⚠️ ONE CHOKEPOINT, NOT TWO. This used to re-check `array_key_exists(..., OPTIONAL)` before calling
        // the helper — a second copy of "which categories are storable". Mutation-testing showed the cost:
        // deleting THIS check left the suite green (the helper still refused), so the layer was redundant AND
        // unprovable, and a second copy of one rule is how the two drift (§N.8f, §N.13 — defence-in-depth
        // needs per-layer tests, and this layer could not have one without mocking the chokepoint).
        // The helper refuses a non-optional category itself, and THAT refusal is mutation-proven.
        $category = (string) $request->string('email_pref');
        if ($category !== '') {
            GuestEmailPreferences::set($guest, $category, $request->boolean('receiving'));
        }

        return redirect()->route('guest.account')->with('status', __('php/http/controllers/guest/guest_account_controller.preferences_saved'));
    }

    /** Change password — the CURRENT password is verified (against the guest guard) before the shared hash path. */
    public function changePassword(Request $request): RedirectResponse
    {
        $guest = $this->guest();

        $request->validate([
            'current_password' => ['required', 'current_password:guest'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $guest->changePasswordTo((string) $request->string('password'), $request->session()->getId());   // the one shared hash + rotate path; this session stays

        return redirect()->route('guest.account')->with('status', __('php/http/controllers/guest/guest_account_controller.password_changed'));
    }

    /**
     * SCHEDULE a reversible 14-day deletion (D2) — the guest must type DELETE. NEVER an immediate destroy: the
     * daily sweep runs the certified erase after the grace window; the guest stays logged in and can undo.
     */
    public function destroy(Request $request, GuestDeletionService $deletions): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:DELETE']], [
            'confirm.in' => __('php/http/controllers/guest/guest_account_controller.type_delete_to_confirm'),
        ]);

        $effective = $deletions->schedule($this->guest());

        return redirect()->route('guest.account')->with('status',
            __('php/http/controllers/guest/guest_account_controller.deletion_scheduled', ['date' => $effective->translatedFormat(LocalDate::pattern('j M Y'))]));
    }

    /** Undo a scheduled deletion — the guest keeps their account. */
    public function undoDeletion(GuestDeletionService $deletions): RedirectResponse
    {
        $deletions->cancel($this->guest());

        return redirect()->route('guest.account')->with('status', __('php/http/controllers/guest/guest_account_controller.deletion_cancelled'));
    }

    private function initials(string $first, string $last): string
    {
        $initials = LocalText::upper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));

        return $initials !== '' ? $initials : 'G';
    }

    private function waitlistDates(GuestWaitlistEntry $entry): string
    {
        if ($entry->check_in !== null && $entry->check_out !== null) {
            return __('php/http/controllers/guest/guest_account_controller.waitlist_date_range', ['from' => $entry->check_in->translatedFormat(LocalDate::pattern('j M')), 'to' => $entry->check_out->translatedFormat(LocalDate::pattern('j M Y'))]);
        }

        return $entry->flexible_month !== null ? __('php/http/controllers/guest/guest_account_controller.flexible_month', ['month' => (string) $entry->flexible_month]) : __('php/http/controllers/guest/guest_account_controller.flexible_dates');
    }
}
