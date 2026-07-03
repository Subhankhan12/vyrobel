<?php

namespace Database\Factories;

use App\Models\ManagementAgreement;
use App\Models\Organization;
use App\Models\Owner;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManagementAgreement>
 */
class ManagementAgreementFactory extends Factory
{
    protected $model = ManagementAgreement::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'owner_id' => Owner::factory(),
            'unit_id' => null, // owner-wide by default
            'commission_pct' => 15.00,
            'management_fee_minor' => null,
            'payout_currency' => 'EUR',
            'payout_schedule' => 'monthly',
            'effective_from' => now()->subYear()->toDateString(),
            'effective_to' => null, // open-ended (current)
            'status' => 'active',
        ];
    }

    public function withManagementFee(int $minor = 10000, string $currency = 'EUR', string $interval = 'monthly'): static
    {
        return $this->state(fn () => [
            'management_fee_minor' => $minor,
            'management_fee_currency' => $currency,
            'management_fee_interval' => $interval,
        ]);
    }

    /**
     * A per-unit override agreement (most-specific-wins in resolution).
     */
    public function forUnit(string $unitId): static
    {
        return $this->state(fn () => ['unit_id' => $unitId]);
    }

    public function effective(string $from, ?string $to = null): static
    {
        return $this->state(fn () => ['effective_from' => $from, 'effective_to' => $to]);
    }
}
