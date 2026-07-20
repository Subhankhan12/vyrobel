<?php

declare(strict_types=1);

namespace App\Http\Requests\Guest;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Guest login attempt (Phase 4a · Sub-gate 3) — the GUEST realm's front door. Mirrors the operator/owner
 * {@see LoginRequest} posture (rate-limited per email+IP, generic failure, no
 * enumeration) but authenticates on the 'guest' guard — NEVER `web`. Only a CLAIMED + VERIFIED account resolves
 * (the guest provider requires both), so an unclaimed/unverified email fails with the same generic message.
 * Guests have no 2FA, so this is a straight `attempt` (no pending-challenge split).
 */
class GuestLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** Authenticate on the GUEST guard or throw a generic error; rate-limited per (email, IP). */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::guard('guest')->attempt(['email' => $this->email(), 'password' => (string) $this->string('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** Fail closed with a throttle message once too many attempts have been made for this (email, IP). */
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
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /** Namespaced with `|guest|` so the guest realm's rate limit never shares a bucket with the operator login. */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email()).'|guest|'.$this->ip());
    }

    private function email(): string
    {
        return trim((string) $this->string('email'));
    }
}
