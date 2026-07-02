<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\Organization;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelMapping>
 */
class ChannelMappingFactory extends Factory
{
    protected $model = ChannelMapping::class;

    public function definition(): array
    {
        return [
            // Respect the active tenant context (see ChannelFactory) so an in-context mapping is
            // visible to the tenant-scoped ChannelMappingGuard.
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'channel_id' => Channel::factory(),
            'kind' => ChannelMapping::KIND_ROOM_TYPE,
            'room_type_id' => RoomType::factory(),
            'rate_plan_id' => null,
            'external_id' => 'ext-'.$this->faker->bothify('####??'),
            'status' => ChannelMapping::STATUS_ACTIVE,
        ];
    }

    public function roomType(RoomType $roomType): static
    {
        return $this->state(fn () => [
            'kind' => ChannelMapping::KIND_ROOM_TYPE,
            'room_type_id' => $roomType->getKey(),
            'rate_plan_id' => null,
        ]);
    }

    public function ratePlan(RatePlan $ratePlan): static
    {
        return $this->state(fn () => [
            'kind' => ChannelMapping::KIND_RATE_PLAN,
            'rate_plan_id' => $ratePlan->getKey(),
            'room_type_id' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => 'disabled']);
    }
}
