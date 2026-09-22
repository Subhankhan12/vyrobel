<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
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
            'phone' => fake()->e164PhoneNumber(),
            'nationality' => 'GB',
            'marketing_consent' => fake()->boolean(),
        ];
    }
}
