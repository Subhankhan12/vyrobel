<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Platform\BreakGlass;
use Illuminate\Console\Command;

/**
 * ADMIN 01 · PHASE 3 — end (and announce to the organisation) every break-glass session whose 30 minutes are up.
 * ⚠️ The EXPIRY itself never depends on this: every request checks the clock ({@see BreakGlass::current()}), so a
 * session is refused the moment it is due. This sweep only makes sure a session nobody touched again still gets
 * its end recorded and its in-app notice sent.
 */
class ExpireBreakGlassSessions extends Command
{
    protected $signature = 'break-glass:expire';

    protected $description = 'End and announce break-glass sessions whose time is up';

    public function handle(BreakGlass $breakGlass): int
    {
        $n = $breakGlass->expireOverdue();
        $this->info("Ended {$n} expired break-glass session(s).");

        return self::SUCCESS;
    }
}
