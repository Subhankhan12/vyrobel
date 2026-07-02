<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RatePlan>
 */
class RatePlanFactory extends Factory
{
    protected $model = RatePlan::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'property_id' => Property::factory(),
            'room_type_id' => RoomType::factory(),
            'name' => 'Flexible',
            'currency' => 'EUR',
            'is_refundable' => true,
            'is_active' => true,
        ];
    }
}
