<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NotificationPreferences;
use App\Support\Notifications\QuietHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notification-preference writes (Phase 3 · Sub-gate 2). SELF-ONLY: the preferences are saved for
 * `$request->user()` (auth-resolved, never a request id), and the AUDIENCE comes from the route default (the
 * operator route → 'operator', the owner route → 'owner') — not from the request — so a user can only ever set
 * their OWN toggles, for their own audience's events. Always-on events are forced enabled in the service.
 */
class NotificationPreferenceController extends Controller
{
    public function update(Request $request, NotificationPreferences $prefs, string $audience): RedirectResponse
    {
        abort_unless(in_array($audience, ['operator', 'owner'], true), 404);

        /** @var array<string, array<string, mixed>> $submitted */
        $submitted = (array) $request->input('prefs', []);

        $enabled = [];
        foreach (array_keys(NotificationPreferences::catalogFor($audience)) as $key) {
            foreach (NotificationPreferences::CHANNELS as $channel) {
                $enabled[$key][$channel] = (bool) ($submitted[$key][$channel] ?? false);
            }
        }

        $prefs->save($request->user(), $audience, $enabled);

        // QUIET HOURS (Wave B · GATE 6) — the per-user held-EMAIL window, saved from the same form. Both endpoints
        // must be valid "HH:MM" and the toggle on; anything else clears it (off). Self-only ($request->user()).
        $user = $request->user();
        $start = (string) $request->input('quiet_start', '');
        $end = (string) $request->input('quiet_end', '');
        $on = $request->boolean('quiet_hours_enabled') && QuietHours::valid($start) && QuietHours::valid($end);
        $user?->forceFill([
            'quiet_hours_start' => $on ? $start : null,
            'quiet_hours_end' => $on ? $end : null,
        ])->save();

        $route = $audience === 'operator' ? 'app.notification-preferences' : 'owner.notification-preferences';

        return redirect()->to(route($route))->with('prefs_notice', 'Notification preferences saved.');
    }
}
