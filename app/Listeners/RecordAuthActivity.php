<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Guest;
use App\Models\LoginActivity;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Support\Log\LogSafe;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SUPER ADMIN · BUILD 2 · GATE 1 — captures auth SECURITY SIGNALS (successful login / failed attempt / lockout)
 * into {@see LoginActivity}, for the platform security & activity view.
 *
 * 🔒 ADDITIVE — registered onto the FRAMEWORK auth events (see AppServiceProvider). The tenant login/auth flow
 * is NOT modified: operator/owner login uses `Auth::validate` (fires no per-attempt `Failed`), so their
 * repeated-failure signal is the `Lockout` event; guest login uses `attempt` (fires `Failed`); the admin realm
 * additionally dispatches `Failed` from its own request. Every successful login (all realms) fires `Login`.
 *
 * 🔒 A security-log write must NEVER break authentication — every path is wrapped so a logging failure is
 * swallowed (the "a mail failure never rolls back the domain txn" discipline).
 *
 * 🔒 #28 — a GUEST's email is encrypted PII; it is NEVER persisted here in plaintext (identifier stays null for
 * the guest realm; the guest is referenced by id on a successful login). Passwords are never stored.
 */
class RecordAuthActivity
{
    public function login(Login $event): void
    {
        $this->record('login', $event->guard, $event->user);
    }

    public function failed(Failed $event): void
    {
        $email = is_string($event->credentials['email'] ?? null) ? $event->credentials['email'] : null;
        $this->record('failed', $event->guard, null, $email);
    }

    public function lockout(Lockout $event): void
    {
        $request = $event->request;
        $guard = $this->guardFromPath($request);
        $email = is_string($request->input('email')) ? $request->input('email') : null;
        $this->record('lockout', $guard, null, $email, $request);
    }

    private function record(string $event, ?string $guard, ?Authenticatable $user, ?string $identifier = null, ?Request $request = null): void
    {
        try {
            $request ??= request();
            [$type, $id] = $this->subject($user);

            LoginActivity::create([
                'event' => $event,
                'guard' => $guard,
                'subject_type' => $type,
                'subject_id' => $id,
                'identifier' => $user !== null ? $this->emailFor($user, $guard) : $this->maskForGuard($identifier, $guard),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('RecordAuthActivity failed to persist a security-signal row (auth is unaffected).', ['error' => LogSafe::message($e)]);
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function subject(?Authenticatable $user): array
    {
        if ($user === null) {
            return [null, null];
        }

        $type = match (true) {
            $user instanceof User => 'user',
            $user instanceof Guest => 'guest',
            $user instanceof PlatformAdmin => 'platform_admin',
            default => 'unknown',
        };

        return [$type, (string) $user->getAuthIdentifier()];
    }

    /** The login email for a resolved actor — NEVER a guest's (encrypted PII, #28). */
    private function emailFor(Authenticatable $user, ?string $guard): ?string
    {
        if ($guard === 'guest' || $user instanceof Guest) {
            return null;
        }

        $email = $user->getAttribute('email');

        return is_string($email) ? $email : null;
    }

    /** A raw typed email from a failed/lockout attempt — dropped for the guest realm (never persist guest PII). */
    private function maskForGuard(?string $email, ?string $guard): ?string
    {
        if ($guard === 'guest' || $email === null || $email === '') {
            return null;
        }

        return $email;
    }

    private function guardFromPath(Request $request): string
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return 'admin';
        }

        return $request->is('guest/*') ? 'guest' : 'web';
    }
}
