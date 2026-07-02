<?php

namespace Database\Factories;

use App\Models\Fee;
use App\Models\Organization;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fee>
 */
class FeeFactory extends Factory
{
    protected $model = Fee::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'property_id' => Property::factory(),
            'name' => 'Cleaning',
            'mode' => 'flat',
            'amount_minor' => 5000,
            'currency' => 'EUR',
            'taxable' => false,
        ];
    }
}
