<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\OwnerInviteMail;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Owner-invite flow (Auth Phase B). The operator invites an owner into THEIR OWN organization (this
 * service always runs inside the caller's tenant context — every record is created org-scoped, so an
 * operator can never target another org). Only the `owner` role is wired in Phase B.
 *
 * SECURITY: the token is high-entropy random; only its sha256 hash is stored; it is single-use (the
 * invite `status` advances to accepted) and expiring. Re-inviting refreshes the pending invite's token
 * instead of creating duplicates. An email that already belongs to an ACTIVE user is rejected.
 */
class OwnerInviteService
{
    public const EXPIRY_DAYS = 7;

    private const SUPPORTED_ROLES = ['owner'];

    /**
     * Create (or refresh) a pending owner invite: an inactive `invited` user + a pending owner + an
     * org membership + a fresh hashed token, then email the tokened set-password link.
     *
     * @param  list<string>  $unitIds  units to assign to the owner on acceptance
     * @return array{invite: OwnerInvite, token: string} the token plaintext is returned once (for the
     *                                                   email + tests); it is never persisted.
     */
    public function invite(string $name, string $email, array $unitIds = [], ?float $commissionPct = null, string $role = 'owner'): array
    {
        if (! in_array($role, self::SUPPORTED_ROLES, true)) {
            throw new RuntimeException("Role [{$role}] invites are not available yet.");
        }

        $email = Str::lower(trim($email));
        $orgId = (string) app(TenantContext::class)->id();

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null && $existing->status === 'active') {
            // Don't hijack a live account through the invite path.
            throw new RuntimeException('A user with this email already exists.');
        }

        [$invite, $token] = DB::transaction(function () use ($name, $email, $orgId, $unitIds, $commissionPct, $role, $existing): array {
            // Inactive login (status=invited → cannot pass the active-only login) with an UNUSABLE
            // random placeholder password until the owner sets their own on acceptance.
            $user = $existing ?? User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'status' => 'invited',
            ]);

            // Pending owner party (inactive → active on acceptance), org-scoped, linked to the login.
            $owner = Owner::query()->where('user_id', $user->getKey())->first() ?? Owner::create([
                'type' => 'individual',
                'name' => $name,
                'email' => $email,
                'payout_currency' => 'EUR',
                'user_id' => $user->getKey(),
                'status' => 'inactive',
            ]);

            // Membership (active so the tenant context resolves once the login is activated).
            Membership::firstOrCreate(
                ['organization_id' => $orgId, 'user_id' => $user->getKey()],
                ['status' => 'active', 'invited_at' => now()],
            );

            $plain = Str::random(64);

            // Refresh a still-pending invite for this (org, email) instead of duplicating.
            $invite = OwnerInvite::query()->where('email', $email)->where('status', 'pending')->first()
                ?? new OwnerInvite;

            $invite->fill([
                'organization_id' => $orgId,
                'owner_id' => $owner->getKey(),
                'invited_by' => Auth::id(),
                'email' => $email,
                'token_hash' => hash('sha256', $plain),
                'role' => $role,
                'assigned_unit_ids' => $unitIds === [] ? null : $unitIds,
                'commission_pct' => $commissionPct,
                'status' => 'pending',
                'expires_at' => now()->addDays(self::EXPIRY_DAYS),
                'accepted_at' => null,
            ])->save();

            return [$invite, $plain];
        });

        Mail::to($email)->send(new OwnerInviteMail($invite, $token, $name));

        return ['invite' => $invite, 'token' => $token];
    }

    /** Revoke a pending invite (a spent token can never be redeemed). */
    public function revoke(OwnerInvite $invite): void
    {
        if ($invite->status === 'pending') {
            $invite->update(['status' => 'revoked']);
        }
    }
}
