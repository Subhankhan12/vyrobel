<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\NotificationPreferenceController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Models\User;
use App\Services\NotificationPreferences;
use App\Support\Notifications\QuietHours;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Notification Preferences (Phase 3 · Sub-gate 2 → exact-match rebuild #79) — the owner's own per-event,
 * per-channel toggles (Statement ready, Payout sent, Performance digest, Terms changed, and the always-on
 * Account & security), matched to the decoded "Owner Notifications" wireframe. Read side; the self-only write
 * goes POST → {@see NotificationPreferenceController}.
 *
 * The wireframe's grid is EMAIL + SMS (the two external channels) — IN-APP is dropped from the UI because the
 * portal feed always records ("statements are always kept in your portal regardless"). So the view manages the
 * EMAIL column (REAL — {@see NotificationPreferences::allows()} genuinely gates the owner Mailables) and shows
 * the SMS column HONESTLY GATED (no provider — a Wave-D hard-defer; never a toggle that implies a send, #39).
 * The IN-APP preference is PRESERVED via hidden inputs, so dropping the column never silently turns off the feed.
 *
 * The owner's OWN email/phone are shown (NOT a guest #28 concern): email = where notifications route (the auth
 * User's email); phone = the owner's own {@see Owner::$phone} (honest "no number yet" when unset). The
 * urgent-bypass guarantee is REAL — `account_security` is `always_on`, so it can never be suppressed.
 */
#[Layout('components.layouts.owner')]
class OwnerNotificationPreferences extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        // The owner's OWN phone (their contact — not guest #28). Encrypted at rest; honest empty when unset.
        $phone = Owner::query()->where('user_id', $user->getKey())->value('phone');

        // A FLAT ordered event list (the wireframe matrix has no group headers) with each event's current
        // email + in-app state — email drives the real toggle; in_app is preserved (see the class docblock).
        $svc = app(NotificationPreferences::class);
        $states = $svc->statesFor($user, 'owner');
        $events = [];
        foreach (NotificationPreferences::catalogFor('owner') as $key => $entry) {
            $events[] = [
                'key' => $key,
                'label' => $entry['label'],
                'description' => $entry['description'],
                'always_on' => $entry['always_on'],
                'email' => (bool) ($states[$key]['email'] ?? false),
                'in_app' => (bool) ($states[$key]['in_app'] ?? true),
            ];
        }

        return view('livewire.owner.notification-preferences', [
            'viewer' => $this->viewer(),
            'events' => $events,
            // Where we reach you — the owner's own contact. Email = the auth user's (the notification target).
            'email' => (string) $user->email,
            'phone' => is_string($phone) && trim($phone) !== '' ? trim($phone) : null,
            // Quiet hours (Wave B · GATE 6) — the real held-EMAIL window, for the form to prefill.
            'quiet' => [
                'on' => QuietHours::configured($user),
                'start' => (string) ($user->quiet_hours_start ?? '22:00'),
                'end' => (string) ($user->quiet_hours_end ?? '07:00'),
            ],
        ]);
    }
}
