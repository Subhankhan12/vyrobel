<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Owner;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'email' => $this->faker->unique()->safeEmail(),
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
