<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlatformAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends Factory<PlatformAdmin>
 */
class PlatformAdminFactory extends Factory
{
    protected $model = PlatformAdmin::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            // ⚠️⚠️ F-66's REAL MECHANISM, FIXED 2026-09-23 — NOT `fake()->unique()`.
            //    🔬 Measured: `unique()` tracks uniqueness only within ONE generator's memory, which does NOT
            //    span tests, while `safeEmail()` draws from a small pool. `RefreshDatabase` is off globally,
            //    so a test that does not roll back leaves a row behind; a later test's reset faker reissues
            //    the same value; the unique index refuses it — and the suite goes red for a reason that has
            //    nothing to do with the code. 🔬 It bit `OAuthConnectedAppsViewTest` with
            //    `Duplicate entry 'onader@example.net'` while the test DB held 0 rows afterwards, which is
            //    why "stale accumulation" was the wrong diagnosis.
            //    ⚠️ A ULID cannot collide regardless of generator state, and this fix touches NO test's
            //    isolation level — so it cannot break the cross-process concurrency suite, which is exactly
            //    what a global `RefreshDatabase` would have done.
            'email' => Str::before(fake()->safeEmail(), '@').'-'.Str::lower((string) Str::ulid()).'@example.net',
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * A fully-enrolled admin: a confirmed TOTP secret so the mandatory-2FA gate lets them through. The secret is
     * returned via `->twoFactorSecret` on the state closure's model for tests that need to generate a live code.
     */
    public function withTwoFactor(): static
    {
        $secret = (new Google2FA)->generateSecretKey();

        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [['code' => 'AAAAA-BBBBB', 'used_at' => null]],
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'disabled']);
    }
}
