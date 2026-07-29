<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PayoutRun;
use App\Payouts\PayoutEngineMode;
use App\Services\PayoutRunService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * The scheduled automated payout run (payout engine · Phase 2). Selects the owners DUE today per their payout
 * preferences and disburses through the certified {@see PayoutRunService}. Registered daily in
 * routes/console.php but INERT unless `services.payouts.engine_enabled` (PAYOUT_ENGINE_ENABLED) is on — a fresh
 * deploy never auto-pays.
 *
 * ⚠️ TEST-MODE ONLY in this build: {@see PayoutEngineMode} is `test` unless a LIVE Stripe key runs in production,
 * so no real money moves. The bold banner below makes the mode unmistakable in logs/output. Enabling live money
 * is the founder's separate go-live step (docs/STRIPE-PRODUCTION.md).
 */
class RunScheduledPayouts extends Command
{
    protected $signature = 'payouts:run
        {--date= : Run as if today were this date (YYYY-MM-DD); defaults to today}
        {--force : Run even when the engine is disabled (PAYOUT_ENGINE_ENABLED off) — still TEST-MODE-gated}';

    protected $description = 'Run the automated owner payout engine for owners due today (TEST MODE unless live keys + production).';

    public function handle(PayoutRunService $engine): int
    {
        $enabled = (bool) config('services.payouts.engine_enabled', false);

        if (! $enabled && ! $this->option('force')) {
            $this->warn('Payout engine is disabled (PAYOUT_ENGINE_ENABLED=false). Skipping. Use --force to run anyway.');

            return self::SUCCESS;
        }

        $mode = PayoutEngineMode::current();
        $today = $this->option('date') !== null
            ? CarbonImmutable::parse((string) $this->option('date'))
            : CarbonImmutable::today();

        $banner = $mode === 'test'
            ? '⚠️  TEST MODE — no real funds move.'
            : '‼️  LIVE MODE — real money will move.';
        $this->line("Payout engine run [{$banner}] for {$today->toDateString()}");

        $run = $engine->runScheduled($today);

        $this->info(sprintf(
            'Run %s (%s): %d owners, %d created, %d paid, %d held, %d failed.',
            $run->run_key, $run->mode, $run->owners_selected, $run->payouts_created,
            $run->payouts_paid, $run->payouts_held, $run->payouts_failed,
        ));

        return $run->status === PayoutRun::STATUS_FAILED ? self::FAILURE : self::SUCCESS;
    }
}
