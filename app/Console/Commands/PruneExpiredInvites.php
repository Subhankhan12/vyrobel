<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OwnerInviteService;
use Illuminate\Console\Command;

/**
 * Scheduled cleanup (Tier 1 sweep · Step 3): mark genuinely EXPIRED, NEVER-ACCEPTED invites as expired
 * and safely clean up their never-activated artifacts (the invited login + the pending inactive owner),
 * so they don't linger as usable/confusing accounts. All the conservative guards live in
 * {@see OwnerInviteService::pruneExpired()} — this command is a thin, idempotent entrypoint.
 */
class PruneExpiredInvites extends Command
{
    protected $signature = 'invites:prune-expired';

    protected $description = 'Expire genuinely expired, never-accepted invites and clean up their never-activated login/owner artifacts (conservative, idempotent).';

    public function handle(OwnerInviteService $invites): int
    {
        $result = $invites->pruneExpired();

        $this->info(sprintf(
            'Expired %d invite(s); soft-removed %d never-activated owner(s); marked %d never-activated login(s) expired.',
            $result['invites'],
            $result['owners'],
            $result['logins'],
        ));

        return self::SUCCESS;
    }
}
