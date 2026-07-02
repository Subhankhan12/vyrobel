<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Organization;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'property_id' => Property::factory(),
            'name' => fake()->words(2, true),
            'block_code' => Str::upper(Str::random(5)),
        ];
    }
}
