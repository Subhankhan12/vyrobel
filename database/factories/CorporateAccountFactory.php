<?php

namespace Database\Factories;

use App\Models\CorporateAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporateAccount>
 */
class CorporateAccountFactory extends Factory
{
    protected $model = CorporateAccount::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company(),
            'type' => 'corporate',
            'commission_pct' => 10.0,
        ];
    }
}
