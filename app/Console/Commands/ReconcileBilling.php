<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ReconciliationRun;
use App\Support\Reconciliation\BillingReconciler;
use App\Support\Reconciliation\CheckResult;
use Illuminate\Console\Command;
use Throwable;

/**
 * ⚠️⚠️ THE RECONCILIATION — it REPORTS, it never REPAIRS.
 *
 * ⚠️ CONSOLE ONLY, ON PURPOSE. There is no route and there will not be one: a reconciliation endpoint is an
 * information-disclosure surface nobody asked for, listing every subscription and owner that disagrees with
 * Stripe. There is also no mail and no alerting — Postmark is a go-live item and alerting is its own
 * decision, so the output has to be findable with a command and a query and nothing else.
 *
 * ⚠️⚠️ AND IT MUST NOT BE SILENT ITSELF. The scheduler's own failure is one log line with nothing watching
 * it; a reconciliation that failed that way would be the defect it exists to solve. So every run writes a
 * row BEFORE it starts, and the header states when the last successful run was — "the report was empty" and
 * "the report never ran" are different sentences here.
 */
class ReconcileBilling extends Command
{
    protected $signature = 'billing:reconcile {--json : Emit the report as JSON instead of a table}';

    protected $description = 'Compare this product against Stripe and against itself, and REPORT what disagrees. Never repairs.';

    /** A run older than this is called out in the header rather than left for someone to notice. */
    public const STALE_AFTER_HOURS = 36;

    public function handle(BillingReconciler $reconciler): int
    {
        $this->reportLastRun();

        // ⚠️ WRITTEN BEFORE THE WORK. A run that dies midway leaves a `running` row with a start time,
        //    which is the difference between "it broke" and "it was never invoked".
        $run = ReconciliationRun::create([
            'status' => ReconciliationRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $results = $reconciler->run();
        } catch (Throwable $e) {
            $run->update([
                'status' => ReconciliationRun::STATUS_FAILED,
                'finished_at' => now(),
                'error' => $e->getMessage(),
            ]);

            $this->error('Reconciliation FAILED: '.$e->getMessage());
            $this->line('  The failure is recorded — run id '.$run->getKey().'.');

            return self::FAILURE;
        }

        $examined = array_sum(array_map(fn (CheckResult $r): int => $r->examined, $results));
        $disagreed = array_sum(array_map(fn (CheckResult $r): int => count($r->disagreements), $results));
        $unchecked = array_sum(array_map(fn (CheckResult $r): int => count($r->unchecked), $results));
        $agreed = array_sum(array_map(fn (CheckResult $r): int => $r->agreed(), $results));

        $run->update([
            'status' => ReconciliationRun::STATUS_COMPLETED,
            'finished_at' => now(),
            'examined' => $examined,
            'agreed' => $agreed,
            'disagreed' => $disagreed,
            'unchecked' => $unchecked,
            'report' => array_map(fn (CheckResult $r): array => $r->toArray(), $results),
        ]);

        if ($this->option('json')) {
            $this->line((string) json_encode(array_map(fn (CheckResult $r): array => $r->toArray(), $results), JSON_PRETTY_PRINT));

            return $disagreed > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->renderTable($results);

        // ⚠️⚠️ THE SUMMARY NAMES THE UNCHECKED COUNT. "0 discrepancies across 40" and "0 across 3, 37
        //    unchecked" must not read the same — collapsing the third outcome into the first is the exact
        //    shape this product has already refused twice.
        $this->newLine();
        $this->line(sprintf(
            '  Examined %d object(s): %d agreed, %d DISAGREED, %d UNCHECKED.',
            $examined, $agreed, $disagreed, $unchecked
        ));

        if ($examined === 0) {
            $this->warn('  ⚠️ Nothing was examined. That is not "no drift" — it is an empty world.');
        }

        if ($unchecked > 0) {
            $this->warn('  ⚠️ '.$unchecked.' object(s) could NOT be checked. They are not known to agree.');
        }

        $this->line('  Run id '.$run->getKey().' — history: select * from reconciliation_runs order by started_at desc;');

        return $disagreed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** ⚠️ A job whose last success was three weeks ago must SAY SO. */
    private function reportLastRun(): void
    {
        $last = ReconciliationRun::query()
            ->where('status', ReconciliationRun::STATUS_COMPLETED)
            ->latest('finished_at')->first();

        if ($last === null) {
            $this->warn('  ⚠️ No reconciliation has ever completed. This is the first.');

            return;
        }

        $hours = $last->finished_at?->diffInHours(now()) ?? 0;

        $message = '  Last successful run: '.$last->finished_at?->toDateTimeString().' ('.$hours.'h ago).';

        $hours > self::STALE_AFTER_HOURS
            ? $this->warn($message.' ⚠️ STALE — nothing has reconciled in over '.self::STALE_AFTER_HOURS.'h.')
            : $this->line($message);
    }

    /** @param  list<CheckResult>  $results */
    private function renderTable(array $results): void
    {
        $this->newLine();
        $this->table(
            ['Check', 'Needs Stripe', 'Examined', 'Agreed', 'Disagreed', 'Unchecked'],
            array_map(fn (CheckResult $r): array => [
                $r->title,
                $r->needsStripe ? 'yes' : 'no',
                (string) $r->examined,
                (string) $r->agreed(),
                count($r->disagreements) > 0 ? '⚠️ '.count($r->disagreements) : '0',
                count($r->unchecked) > 0 ? '⚠️ '.count($r->unchecked) : '0',
            ], $results),
        );

        foreach ($results as $result) {
            if ($result->isClear()) {
                continue;
            }

            $this->newLine();
            $this->line('  '.$result->title.'  ['.$result->source.']');

            foreach ($result->disagreements as $row) {
                $this->line('    DISAGREES  '.$row['subject'].' — '.$row['detail']);
            }

            foreach ($result->unchecked as $row) {
                $this->line('    UNCHECKED  '.$row['subject'].' — '.$row['detail']);
            }
        }
    }
}
