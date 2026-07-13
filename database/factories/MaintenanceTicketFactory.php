<?php

namespace Database\Factories;

use App\Models\MaintenanceTicket;
use App\Models\Organization;
use App\Models\Unit;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceTicket>
 */
class MaintenanceTicketFactory extends Factory
{
    protected $model = MaintenanceTicket::class;

    public function definition(): array
    {
        return [
            // Respect the active tenant context (fall back to a fresh org) — same discipline as the
            // Channel/Owner/Unit factories.
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'unit_id' => Unit::factory(),
            'title' => $this->faker->randomElement(['Leaking tap', 'Faulty AC', 'Broken lock', 'Cracked tile']),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['plumbing', 'electrical', 'appliance', 'structural', 'other']),
            'priority' => 'normal',
            'status' => 'open',
            'takes_out_of_service' => false,
            'opened_at' => now(),
        ];
    }

    public function outOfService(): static
    {
        return $this->state(fn () => ['takes_out_of_service' => true]);
    }
}
