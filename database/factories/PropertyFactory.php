<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 *
 * Tenant-scoped: `organization_id` defaults to a fresh Organization. For a coherent graph,
 * pass an explicit `organization_id` or use `->recycle($org)`.
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company().' Hotel',
            'type' => 'hotel',
            'currency' => 'EUR',
            'timezone' => 'Europe/Lisbon',
            'check_in_time' => '15:00:00',
            'check_out_time' => '11:00:00',
            'status' => 'active',
        ];
    }

    public function mixed(): static
    {
        return $this->state(fn () => ['type' => 'mixed']);
    }
}
