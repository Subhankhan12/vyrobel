<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login attempt for the shared Vyrobel account (operator/owner). Encapsulates validation, brute-force
 * rate limiting, and the credential check — the Laravel Breeze pattern, hardened for this app.
 *
 * SECURITY:
 *   - Passwords are verified via Laravel Auth against the `hashed` column (bcrypt) — never compared in
 *     plaintext, never logged.
 *   - `status = active` is folded into the credential check, so a suspended account fails with the SAME
 *     generic message — no way to tell "wrong password" from "inactive" from "no such user" (no user
 *     enumeration).
 *   - Rate limited to 5 attempts per (email + IP) per minute; a lockout fires the framework `Lockout`
 *     event and returns a throttle message with the retry delay.
 *   - No remember-me (deliberately omitted — no long-lived credential cookie this pass).
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Verify the credentials, or throw a GENERIC validation error (no enumeration) — rate-limited.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // `status` is a plain (non-password) attribute → Auth adds it as a WHERE, so only an ACTIVE
        // user with the right password authenticates. Failure is indistinguishable across causes.
        $ok = Auth::attempt([
            'email' => (string) $this->string('email'),
            'password' => (string) $this->string('password'),
            'status' => 'active',
        ], false);

        if (! $ok) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Fail closed with a throttle message once too many attempts have been made for this (email, IP).
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /** Per-account AND per-IP so one IP can't lock every account and one account can't be brute-forced. */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->string('email')).'|'.$this->ip());
    }
}
