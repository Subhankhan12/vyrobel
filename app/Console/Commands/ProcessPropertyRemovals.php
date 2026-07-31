<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ChannelConnectionService;
use App\Services\PropertyRemovalService;
use Illuminate\Console\Command;

/**
 * Archive properties whose scheduled removal date has passed (task #98 GATE 2). Runs daily; each due property is
 * SOFT-DELETED (records retained) and its channels disconnected via the certified {@see PropertyRemovalService}.
 * A property with no scheduled removal (or a future effective date) is untouched.
 */
class ProcessPropertyRemovals extends Command
{
    protected $signature = 'properties:process-removals';

    protected $description = 'Archive (soft-delete) properties whose scheduled removal date has passed — records retained.';

    public function handle(PropertyRemovalService $removals, ChannelConnectionService $channels): int
    {
        $count = $removals->processDue($channels);

        $this->info($count === 0 ? 'No properties due for removal.' : "Archived {$count} property(ies).");

        return self::SUCCESS;
    }
}
