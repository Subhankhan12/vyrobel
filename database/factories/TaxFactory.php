<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Property;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'property_id' => Property::factory(),
            'name' => 'VAT',
            'kind' => 'vat',
            'mode' => 'percentage',
            'rate' => 20.0,
            'inclusive' => false,
        ];
    }
}
