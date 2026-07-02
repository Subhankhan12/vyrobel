<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Organization;
use App\Models\Property;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        return [
            // Respect the active tenant context so a channel created in-context lands in the
            // current org; fall back to a fresh org for standalone factory use.
            'organization_id' => app(TenantContext::class)->id() ?? Organization::factory(),
            'property_id' => Property::factory(),
            'provider' => 'channex',
            'name' => 'Channex — '.$this->faker->company(),
            'external_property_id' => (string) $this->faker->uuid(),
            'credentials' => 'test-api-key-'.$this->faker->bothify('??????##'),
            'status' => 'connected',
        ];
    }

    public function disconnected(): static
    {
        return $this->state(fn () => ['status' => 'disconnected']);
    }
}
