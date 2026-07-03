<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Owner;
use App\Models\OwnerExpense;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OwnerExpense>
 */
class OwnerExpenseFactory extends Factory
{
    protected $model = OwnerExpense::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'owner_id' => Owner::factory(),
            'category' => 'cleaning',
            'description' => 'Turnover clean',
            'amount_minor' => 5000,
            'currency' => 'EUR',
            'expense_date' => '2026-09-03',
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['category' => 'maintenance', 'description' => 'Boiler repair', 'amount_minor' => 12000]);
    }
}
