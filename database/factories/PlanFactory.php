<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 *
 * Global product catalog (not tenant-scoped).
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => 'plan-'.Str::lower(Str::random(6)),
            'name' => fake()->randomElement(['Starter', 'Growth', 'Pro']),
            'price_minor' => 4900,
            'currency' => 'EUR',
            'interval' => 'monthly',
            'is_active' => true,
        ];
    }
}
