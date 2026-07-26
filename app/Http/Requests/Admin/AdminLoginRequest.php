<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * SUPER ADMIN · GATE 0 — the platform-admin login attempt. Mirrors the hardened tenant {@see
 * \App\Http\Requests\Auth\LoginRequest} exactly, but validates against the `admin` guard:
 *   - password checked against the `hashed` column via `Auth::guard('admin')->validate()` — NO session
 *     established (so the caller can branch to the mandatory 2FA challenge);
 *   - `status = active` folded into the credential check → a disabled admin fails with the SAME generic
 *     message (no enumeration);
 *   - rate-limited to 5 attempts per (email + IP) per minute on a DEDICATED `admin|` throttle namespace, so
 *     the admin login shares no bucket with the tenant login; a lockout fires the framework `Lockout` event.
 */
class AdminLoginRequest extends FormRequest
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

    /** VERIFY the credentials WITHOUT logging in, or throw a GENERIC error (no enumeration) — rate-limited. */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $ok = Auth::guard('admin')->validate([
            'email' => (string) $this->string('email'),
            'password' => (string) $this->string('password'),
            'status' => 'active',
        ]);

        if (! $ok) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

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

    /** Per (email + IP), on a dedicated `admin|` namespace so it never collides with the tenant login bucket. */
    public function throttleKey(): string
    {
        return 'admin|'.Str::transliterate(Str::lower((string) $this->string('email')).'|'.$this->ip());
    }
}
