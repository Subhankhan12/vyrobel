<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Owner;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Owner>
 */
class OwnerFactory extends Factory
{
    protected $model = Owner::class;

    public function definition(): array
    {
        return [
            // Respect the active tenant context (fall back to a fresh org for standalone use) —
            // same discipline as ChannelFactory so context-scoped tests see the row.
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'type' => 'individual',
            'name' => $this->faker->name(),
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
            'email' => Str::before($this->faker->safeEmail(), '@').'-'.Str::lower((string) Str::ulid()).'@example.net',
            'phone' => $this->faker->phoneNumber(),
            // A tax ID on file by default → the owner is "tax complete", so payouts are not held. A test that
            // needs the HELD state (Tax Center) creates the owner with tax_id null explicitly.
            'tax_id' => (string) $this->faker->numerify('#########'),
            'payout_currency' => 'EUR',
            'status' => 'active',
        ];
    }

    public function company(): static
    {
        return $this->state(fn () => [
            'type' => 'company',
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' Ltd',
        ]);
    }
}
