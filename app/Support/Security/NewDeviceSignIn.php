<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Models\LoginActivity;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Platform\DeviceLabel;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * LOGIN ALERTS — "is this sign-in from a device this account has not used?" (2026-10-24, the founder's design).
 *
 * A device is its browser and system as {@see DeviceLabel} names them ("Chrome on Windows"); the memory is the account's own
 * successful sign-ins in the sign-in log for the 90 days it keeps them. A sign-in is NEW when the account signed in before
 * in that window and never from this device — the very first sign-in of an account is not news to its owner, so it alerts
 * nobody. No place, no fingerprint: only what the log already holds.
 *
 * Which preference governs it: an owner's sign-in alert is part of `account_security` — "Sign-ins and bank changes — always
 * on" in the owner's notification settings, a promise made before this existed — so for an owner it cannot be switched off;
 * for staff it is `new_device_login`, a switch on both the Security page and Notification preferences (one stored setting).
 */
final class NewDeviceSignIn
{
    public const OPERATOR_KEY = 'new_device_login';

    public const OWNER_KEY = 'account_security';

    /**
     * @param  CarbonImmutable  $before  only sign-ins strictly before this moment count (the one happening now is not history)
     */
    public static function isNew(User $user, ?string $userAgent, CarbonImmutable $before): bool
    {
        $prior = LoginActivity::query()
            ->where('guard', 'web')
            ->where('event', 'login')
            ->where('subject_type', 'user')
            ->where('subject_id', (string) $user->getKey())
            ->where('occurred_at', '>=', $before->subDays(LoginActivity::RETENTION_DAYS))
            // ⚠️ the sign-in log stores whole seconds; a row written for THIS sign-in in this same second is not history
            ->where('occurred_at', '<', $before->startOfSecond())
            ->pluck('user_agent');

        if ($prior->isEmpty()) {
            return false;
        }

        $device = DeviceLabel::from($userAgent);

        return ! $prior->contains(fn (?string $ua): bool => DeviceLabel::from($ua) === $device);
    }

    /**
     * The preference that governs this person's sign-in alerts. The same test the sign-in itself uses to send someone to the
     * owner portal (RedirectsByRole::homeFor) — an owner, or an owner's team member — reads the owner rule (always on);
     * everyone else the staff switch. Read across organisations (there is none at sign-in), names and ids only.
     */
    public static function eventKeyFor(User $user): string
    {
        $isOwner = app(TenantContext::class)->runAsSuperAdmin(
            fn (): bool => Owner::query()->where('user_id', $user->getKey())->exists()
                || OwnerUser::query()->where('user_id', $user->getKey())->exists(),
        );

        return $isOwner ? self::OWNER_KEY : self::OPERATOR_KEY;
    }
}
