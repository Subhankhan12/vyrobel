<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GuestDeletionService;
use Illuminate\Console\Command;

/**
 * Runs the guest-account deletions whose 14-day grace window has elapsed (Guest Portal · GATE 3, D2). Each due
 * guest's upcoming reservations are cancelled per policy + their PII is GDPR-erased ({@see Guest::anonymize}).
 * Scheduled daily (routes/console.php). Mirrors {@see ProcessPropertyRemovals}.
 */
class ProcessGuestDeletions extends Command
{
    protected $signature = 'guests:process-deletions';

    protected $description = 'Run scheduled guest-account deletions (grace elapsed): cancel upcoming reservations per policy + GDPR-anonymize.';

    public function handle(GuestDeletionService $deletions): int
    {
        $count = $deletions->processDue();
        $this->info("Guest accounts erased: {$count}");

        return self::SUCCESS;
    }
}
