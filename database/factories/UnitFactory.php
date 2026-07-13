<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            // Respect the active tenant context (fall back to a fresh org for standalone use) —
            // same discipline as ChannelFactory/OwnerFactory, so a unit built in-context lands in the
            // current org instead of a mismatched fresh one. An explicit `organization_id` still wins.
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'property_id' => Property::factory(),
            'room_type_id' => RoomType::factory(),
            'code' => (string) fake()->unique()->numberBetween(100, 999),
            'unit_kind' => 'room',
            'max_occupancy' => 2,
            'housekeeping_status' => 'clean',
            'condition_status' => 'in_service',
            'is_active' => true,
        ];
    }

    public function outOfOrder(): static
    {
        return $this->state(fn () => ['condition_status' => 'out_of_order']);
    }
}
