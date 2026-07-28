<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\LoginApproval;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * LOGIN APPROVAL (Wave B · GATE 4) — the "approve this new device by email" challenge. When a user has opted in
 * (`require_login_approval`) and signs in from a device that is not already trusted, a pending approval is created
 * and its token is emailed; the sign-in completes only after the emailed link is clicked.
 *
 * SECURITY: the token is a random bearer secret, stored ONLY as a SHA-256 hash; short-lived ({@see TTL_MINUTES});
 * SINGLE-USE (consumed when the pending login completes); self-only (bound to a user_id). A stale/forged/expired
 * token approves nothing.
 */
class LoginApprovalService
{
    /** How long an approval request stays actionable. */
    private const TTL_MINUTES = 30;

    /**
     * Open a pending approval for this login attempt. Returns [the approval, the PLAINTEXT token] — the caller
     * emails the token in a signed link and stashes the approval id in the pending session.
     *
     * @return array{0: LoginApproval, 1: string}
     */
    public function open(User $user, Request $request): array
    {
        $token = Str::random(48);

        $approval = LoginApproval::create([
            'user_id' => (string) $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'expires_at' => Carbon::now()->addMinutes(self::TTL_MINUTES),
        ]);

        return [$approval, $token];
    }

    /**
     * Approve a request by its plaintext token (the emailed link) — marks `approved_at` on a still-pending row for
     * this user. Returns true when a genuine pending request was approved; a stale/forged/expired token → false.
     */
    public function approve(User $user, string $token): bool
    {
        $approval = LoginApproval::query()
            ->where('user_id', (string) $user->getKey())
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($approval === null) {
            return false;
        }

        if ($approval->approved_at === null) {
            $approval->forceFill(['approved_at' => Carbon::now()])->save();
        }

        return true;
    }

    /** The pending approval for a user by id (still pending), or null. */
    public function pending(User $user, string $approvalId): ?LoginApproval
    {
        $approval = LoginApproval::query()
            ->where('user_id', (string) $user->getKey())
            ->find($approvalId);

        return $approval !== null && $approval->isPending() ? $approval : null;
    }

    /** Consume an approved request (single-use) once the pending login completes. */
    public function consume(LoginApproval $approval): void
    {
        $approval->forceFill(['consumed_at' => Carbon::now()])->save();
    }
}
