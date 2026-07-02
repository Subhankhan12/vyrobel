<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'property_id' => Property::factory(),
            'name' => fake()->randomElement(['Double Room', 'Twin Room', 'Suite']),
            'kind' => 'room',
            'std_occupancy' => 2,
            'max_adults' => 2,
            'max_children' => 1,
        ];
    }

    public function apartment(): static
    {
        return $this->state(fn () => [
            'name' => 'One-Bedroom Apartment',
            'kind' => 'apartment',
            'bedrooms' => 1,
            'std_occupancy' => 3,
            'max_adults' => 3,
            'max_children' => 2,
        ]);
    }
}
