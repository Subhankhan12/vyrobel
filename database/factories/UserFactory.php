<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
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
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
