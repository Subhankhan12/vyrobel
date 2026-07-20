<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NotificationPreferences;
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

        $route = $audience === 'operator' ? 'app.notification-preferences' : 'owner.notification-preferences';

        return redirect()->to(route($route))->with('prefs_notice', 'Notification preferences saved.');
    }
}
