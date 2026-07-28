<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Owner;
use App\Models\OwnerAttentionState;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OwnerAttentionState>
 */
class OwnerAttentionStateFactory extends Factory
{
    protected $model = OwnerAttentionState::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'owner_id' => Owner::factory(),
            'attention_key' => 'tax_hold',
            'snoozed_until' => null,
            'dismissed_at' => null,
            'read_at' => null,
        ];
    }
}
