<?php

namespace Database\Factories;

use App\Models\HousekeepingTask;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HousekeepingTask>
 */
class HousekeepingTaskFactory extends Factory
{
    protected $model = HousekeepingTask::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'property_id' => Property::factory(),
            'unit_id' => Unit::factory(),
            'date' => now()->toDateString(),
            'type' => 'departure',
            'status' => 'pending',
            'priority' => 'normal',
        ];
    }
}
