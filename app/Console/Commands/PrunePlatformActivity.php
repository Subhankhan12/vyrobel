<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LoginActivity;
use Illuminate\Console\Command;

/**
 * SUPER ADMIN · BUILD 2 · GATE 1 — the PRIVACY RETENTION prune (GDPR, built in from the first commit).
 *
 * Login-activity rows are a SECURITY SIGNAL (login/failed/lockout + IP), kept only as long as they are useful
 * for security monitoring. This scheduled command PURGES rows older than {@see LoginActivity::RETENTION_DAYS}
 * (90 days) — the IP and all. Idempotent; runs daily (see routes/console.php). Purpose: security monitoring
 * only; lawful basis: legitimate interest; access: the /admin realm only.
 */
class PrunePlatformActivity extends Command
{
    protected $signature = 'platform:prune-activity';

    protected $description = 'Purge login-activity security-signal rows older than the retention window (GDPR — 90 days).';

    public function handle(): int
    {
        $cutoff = now()->subDays(LoginActivity::RETENTION_DAYS);

        $deleted = LoginActivity::query()->where('occurred_at', '<', $cutoff)->delete();

        $this->info(sprintf('Purged %d login-activity row(s) older than %d days.', $deleted, LoginActivity::RETENTION_DAYS));

        return self::SUCCESS;
    }
}
