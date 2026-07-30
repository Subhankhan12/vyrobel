<?php

namespace Database\Factories;

use App\Models\AgreementAmendment;
use App\Models\Organization;
use App\Models\Owner;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgreementAmendment>
 */
class AgreementAmendmentFactory extends Factory
{
    protected $model = AgreementAmendment::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'owner_id' => Owner::factory(),
            'version' => '2026 rate-card',
            'title' => '2026 rate-card & fee schedule',
            'summary' => 'Updated management fee for the 2026 season.',
            'effective_date' => now()->addMonths(2)->toDateString(),
            'new_terms' => ['commission_pct' => 17.00],
            'status' => AgreementAmendment::STATUS_PUBLISHED,
        ];
    }
}
