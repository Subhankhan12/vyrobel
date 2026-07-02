<?php

use App\Models\Channel;

if (! function_exists('channelScenario')) {
    /**
     * A seedScenario() plus one connected Channel on that property (no mapping yet).
     *
     * @return array{scenario: array<string, mixed>, channel: Channel}
     */
    function channelScenario(): array
    {
        $s = seedScenario(1);
        $channel = Channel::factory()->create([
            'property_id' => $s['property']->id,
            'name' => 'Channex Main',
        ]);

        return ['scenario' => $s, 'channel' => $channel];
    }
}
