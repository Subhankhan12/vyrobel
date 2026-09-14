<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Retention\RetentionPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * ⚠️ RETENTION SWEEP (audit 2026-08-29 · M-6) — delete rows past their declared window from the log-like
 * stores in {@see RetentionPolicy::WINDOWS}. The windows and the reasoning live THERE, in one declaration.
 *
 * ⚠️ RUNS IN THE SCHEDULER'S SHAPE: no tenant context, no owner context, no request. That is why the sweep is
 * wrapped in an EXPLICIT super-admin context — the project's sanctioned cross-tenant mechanism, the same one
 * {@see PurgeExpiredGuestDocuments} uses. It is NOT `withoutGlobalScope`: nothing here
 * reads tenant data back to a caller, and a retention sweep spans tenants BY DESIGN (five of the seven stores
 * have no `organization_id` at all, so "iterate tenants" would be meaningless for them).
 * ⚠️ Covered by ScheduledCommandsRunHeadlessTest, which runs every scheduled command with both contexts cleared.
 *
 * ⚠️ BATCHED. The FIRST real run is the biggest this will ever do, and one unbounded DELETE on a large table
 * locks it. Each store deletes in bounded chunks until the window is clear.
 *
 * ⚠️ PER-STORE ISOLATION. One store failing must not stop the rest — a single wholesale failure is how a
 * retention obligation quietly stops running.
 */
class PruneRetainedRecords extends Command
{
    /** Rows per DELETE. Small enough not to hold a long lock, large enough to drain a backlog. */
    private const BATCH = 1000;

    /** A stop so a pathological table cannot spin forever in one nightly run; the rest drains tomorrow. */
    private const MAX_BATCHES = 500;

    protected $signature = 'retention:prune {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Delete rows past their declared retention window (see RetentionPolicy).';

    public function handle(TenantContext $tenant): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $failures = [];

        foreach (RetentionPolicy::windows() as $table => $rule) {
            try {
                $deleted = $tenant->runAsSuperAdmin(fn (): int => $this->prune(
                    $table,
                    $rule['column'],
                    $rule['days'],
                    $dryRun,
                ));

                $this->info(sprintf(
                    '%s: %s %d row(s) older than %d days.',
                    $table,
                    $dryRun ? 'would prune' : 'pruned',
                    $deleted,
                    $rule['days'],
                ));
            } catch (Throwable $e) {
                // Isolated on purpose — the next store still runs.
                $failures[] = $table.': '.$e->getMessage();
                $this->warn(sprintf('%s: FAILED — %s', $table, $e->getMessage()));
            }
        }

        if ($failures !== []) {
            $this->error('Retention sweep incomplete for: '.implode(' | ', $failures));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Delete in bounded batches. `sessions.last_activity` is a UNIX INTEGER, not a datetime — comparing it as
     * a string would silently match nothing, which reads exactly like a clean run.
     */
    private function prune(string $table, string $column, int $days, bool $dryRun): int
    {
        $cutoff = now()->subDays($days);
        $bound = $table === 'sessions' ? $cutoff->getTimestamp() : $cutoff->toDateTimeString();

        if ($dryRun) {
            return (int) DB::table($table)->where($column, '<', $bound)->count();
        }

        $total = 0;

        for ($i = 0; $i < self::MAX_BATCHES; $i++) {
            $deleted = DB::table($table)->where($column, '<', $bound)->limit(self::BATCH)->delete();
            $total += $deleted;

            if ($deleted < self::BATCH) {
                break;
            }
        }

        return $total;
    }
}
