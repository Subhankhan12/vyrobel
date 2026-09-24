<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\PlatformAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ADMIN 01 · PHASE 1 — inviting a platform admin WITHOUT anybody else ever knowing their password.
 *
 * The Owner enters a name, an email and a role. An `invited` admin row is created with a random password nobody
 * is told (it can never be used — the login only admits `active` admins), and a one-time link is shown ONCE to
 * the Owner, who sends it to the new admin themselves. The new admin opens it, chooses their own password (the
 * platform-admin bar), and must enrol 2FA at their first sign-in like everyone else.
 *
 * ⚠️ WHY THE LINK IS SHOWN, NOT EMAILED: production mail is `log` — an emailed invite would be discarded and the
 *    screen would still say "sent". The page says plainly that nothing was emailed.
 * ⚠️ Only the SHA-256 of the token is stored, so a database read cannot produce a working link. It expires, and
 *    is spent on first use.
 */
final class PlatformAdminInvites
{
    public const EXPIRES_HOURS = 72;

    /** @return array{0: PlatformAdmin, 1: string} the invited admin and the raw token (shown once, never stored) */
    public function invite(string $name, string $email, PlatformAdminRole $role, PlatformAdmin $by): array
    {
        $token = Str::random(64);

        $admin = new PlatformAdmin;
        $admin->forceFill([
            'name' => $name,
            'email' => Str::lower(trim($email)),
            // ⚠️ Random and discarded: nobody — including the inviting Owner — ever knows this password, and an
            //    `invited` admin cannot sign in with any password at all.
            'password' => Hash::make(Str::random(64)),
            'status' => PlatformAdmin::STATUS_INVITED,
            'role' => $role->value,
            'invite_token_hash' => hash('sha256', $token),
            'invited_at' => now(),
            'invite_expires_at' => now()->addHours(self::EXPIRES_HOURS),
            'invited_by' => $by->getKey(),
        ])->save();

        return [$admin, $token];
    }

    /** A fresh link for an invite that expired or was lost. The old link stops working. */
    public function reissue(PlatformAdmin $admin): string
    {
        $token = Str::random(64);
        $admin->forceFill([
            'invite_token_hash' => hash('sha256', $token),
            'invite_expires_at' => now()->addHours(self::EXPIRES_HOURS),
        ])->save();

        return $token;
    }

    /** The invited admin a raw token belongs to — only while it is unexpired and unspent. */
    public function find(string $token): ?PlatformAdmin
    {
        if (strlen($token) !== 64) {
            return null;
        }

        return PlatformAdmin::query()
            ->where('status', PlatformAdmin::STATUS_INVITED)
            ->where('invite_token_hash', hash('sha256', $token))
            ->where('invite_expires_at', '>', now())
            ->first();
    }

    /** Spend the token: set the admin's OWN password and make them active. 2FA is enrolled at first sign-in. */
    public function accept(PlatformAdmin $admin, string $password): void
    {
        $admin->forceFill([
            'password' => $password,   // the 'hashed' cast hashes exactly once
            'status' => PlatformAdmin::STATUS_ACTIVE,
            'invite_token_hash' => null,
            'invite_expires_at' => null,
            'remember_token' => Str::random(60),
        ])->save();
    }
}
