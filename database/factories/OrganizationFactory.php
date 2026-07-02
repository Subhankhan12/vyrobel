<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'mode' => 'single_property',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'country' => 'PT',
            'status' => 'active',
            'billing_email' => fake()->safeEmail(),
        ];
    }
}
