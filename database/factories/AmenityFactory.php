<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amenity>
 *
 * Global shared catalog (no organization_id).
 */
class AmenityFactory extends Factory
{
    protected $model = Amenity::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['WiFi', 'Parking', 'Pool', 'Air Conditioning', 'Kitchen', 'Sea View']),
            'icon' => 'star',
            'category' => 'general',
        ];
    }
}
