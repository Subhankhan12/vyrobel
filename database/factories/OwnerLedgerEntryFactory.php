<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OwnerLedgerEntry>
 */
class OwnerLedgerEntryFactory extends Factory
{
    protected $model = OwnerLedgerEntry::class;

    public function definition(): array
    {
        return [
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'owner_id' => Owner::factory(),
            'entry_type' => 'room_revenue',
            'direction' => 'credit',
            'amount_minor' => 10000,
            'currency' => 'EUR',
            'description' => 'Room revenue',
            'service_date' => '2026-09-01',
            'attribution_version' => 1,
            'posted_at' => now(),
        ];
    }
}
