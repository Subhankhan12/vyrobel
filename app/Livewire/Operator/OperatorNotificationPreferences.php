<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\NotificationPreferenceController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\User;
use App\Notifications\FeedNotification;
use App\Services\NotificationPreferences;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator NOTIFICATION PREFERENCES (#90 exact-match; the operator side of #79) — the AUTHENTICATED user's own
 * per-event toggles, rebuilt to `Operator Notification Preferences.html` on the SHARED {@see NotificationPreferences}
 * engine + {@see NotificationPreferenceController} (audience 'operator'), the same engine the owner page uses. The
 * self-only write is a POST; the EMAIL column genuinely gates delivery (via {@see NotificationPreferences::allows()}
 * → {@see FeedNotification::via()}, mutation-proven), quiet hours are real, always-on events lock.
 *
 * ⚠️ HONESTY (#39): the engine's real channels are `in_app` + `email`. PUSH + SMS have NO infrastructure anywhere
 * (no VAPID/web-push/device-token/provider) — so both are HONEST-GATED (display-only, disabled, NEVER a submitted
 * field, never a fake send). The real `in_app` channel (not a wireframe column) is preserved via hidden inputs so
 * the feed keeps working. The daily digest stores the real `daily_summary` pref but is not dispatched yet (no job).
 */
#[Layout('components.layouts.operator')]
class OperatorNotificationPreferences extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $groups = app(NotificationPreferences::class)->groupedFor($user, 'operator');

        // Pull the daily summary OUT of the grid → it drives the wireframe's separate "Daily digest email" card.
        $digest = null;
        foreach ($groups as $groupName => $events) {
            $groups[$groupName] = array_values(array_filter($events, function (array $e) use (&$digest): bool {
                if ($e['key'] === 'daily_summary') {
                    $digest = $e;

                    return false;
                }

                return true;
            }));
        }
        $groups = array_filter($groups, fn (array $events): bool => $events !== []);

        return view('livewire.operator.notification-preferences', [
            'viewer' => $this->viewer(),
            'groups' => $groups,
            'digest' => $digest,
            'email' => (string) ($user->email ?? ''),
            'quietStart' => $user->quiet_hours_start !== null ? substr((string) $user->quiet_hours_start, 0, 5) : '22:00',
            'quietEnd' => $user->quiet_hours_end !== null ? substr((string) $user->quiet_hours_end, 0, 5) : '07:00',
            'quietOn' => $user->quiet_hours_start !== null && $user->quiet_hours_end !== null,
        ]);
    }
}
