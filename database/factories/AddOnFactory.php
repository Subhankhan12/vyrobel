<?php

namespace Database\Factories;

use App\Models\AddOn;
use App\Models\Organization;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddOn>
 */
class AddOnFactory extends Factory
{
    protected $model = AddOn::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'property_id' => Property::factory(),
            'name' => 'Breakfast',
            'price_minor' => 1500,
            'currency' => 'EUR',
            'taxable' => true,
            'is_active' => true,
        ];
    }
}
