<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Notifications\NewDeviceSignInNotification;
use App\Support\Log\LogSafe;
use App\Support\Platform\DeviceLabel;
use App\Support\Security\NewDeviceSignIn;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * LOGIN ALERTS (2026-10-24) — on a staff or owner sign-in (the `web` guard: password, passkey, Google/X, after two-factor or
 * an approval), tell the person when it came from a device their account has not used ({@see NewDeviceSignIn}).
 *
 * ⚠️ The sign-in happening now is never its own history: NewDeviceSignIn reads only sign-ins before this second (the log stores
 * whole seconds), whichever listener runs first — it is also registered before the log's own, but that order is not relied on. ⚠️ An alert must never break a sign-in: any failure here is logged and
 * swallowed, as the sign-in log's own writer does.
 */
class NotifyNewDeviceSignIn
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        try {
            $user = $event->user;
            $request = request();
            $now = CarbonImmutable::now();

            if (! NewDeviceSignIn::isNew($user, $request->userAgent(), $now)) {
                return;
            }

            $key = NewDeviceSignIn::eventKeyFor($user);
            $zone = is_string($user->timezone) && $user->timezone !== '' ? $user->timezone : (string) config('app.timezone');

            $user->notify(new NewDeviceSignInNotification(
                eventKey: $key,
                device: DeviceLabel::from($request->userAgent()),
                ip: $request->ip(),
                when: $now->setTimezone($zone)->format('j M Y, H:i T'),
                securityUrl: route($key === NewDeviceSignIn::OWNER_KEY ? 'owner.security' : 'app.security', [], false),
            ));
        } catch (\Throwable $e) {
            Log::warning('NotifyNewDeviceSignIn could not send a login alert (the sign-in is unaffected).', ['error' => LogSafe::message($e)]);
        }
    }
}
