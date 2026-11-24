<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Services\OwnerStatementService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * ⚠️⚠️ VY-26 (audit of 2026-11-24, read in code) — OWNER STATEMENTS, CLOSED ON THE DAY THE OPERATOR CHOSE.
 *
 * Statements were made only by hand, one owner at a time, from each owner's page ("Close period"). An organisation that sets a day
 * (`organizations.statement_close_day`, 1–28, on the Payout run page; NULL = off, every organisation's default) now has LAST MONTH closed
 * for it that day: for every owner with an unclaimed ledger line up to the period's end, through EXACTLY the two calls the hand path makes
 * ({@see OwnerStatementService::generateDraft()} then {@see OwnerStatementService::finalize()}) and the same audit row — the collection
 * gate, the withholding, the per-owner lock and the numbering are the service's, untouched.
 *
 * ⚠️ IT NEVER CLOSES A PERIOD TWICE (an owner whose month is already finalized is skipped) and NEVER GUESSES AN EXCHANGE RATE: an owner paid
 *    in another currency needs a rate the hand path asks a person for, so the service refuses, that owner is reported and left for a person,
 *    and the run goes on. The day is the organisation's day of the month in UTC (the run is at 05:00 UTC).
 */
class CloseMonthlyStatements extends Command
{
    protected $signature = 'statements:close-month {--organization= : Run for this organisation now, whatever its day}';

    protected $description = 'Close last month\'s owner statements for organisations that chose a day for it (VY-26).';

    public function handle(TenantContext $tenant, OwnerStatementService $statements, AuditEventRecorder $audit): int
    {
        $today = CarbonImmutable::now('UTC');
        $periodStart = $today->subMonthNoOverflow()->startOfMonth()->toDateString();
        $periodEnd = $today->subMonthNoOverflow()->endOfMonth()->toDateString();
        $only = $this->option('organization');

        $organizations = Organization::query()
            ->whereNotNull('statement_close_day')
            ->whereNotIn('status', ['suspended', 'cancelled'])
            ->when(is_string($only), fn ($q) => $q->whereKey($only), fn ($q) => $q->where('statement_close_day', $today->day))
            ->get();

        $closed = $skipped = 0;
        foreach ($organizations as $organization) {
            [$c, $s] = $tenant->forOrganization((string) $organization->getKey(), function () use ($statements, $audit, $periodStart, $periodEnd): array {
                $c = $s = 0;
                foreach (Owner::query()->get() as $owner) {
                    $done = OwnerStatement::query()->where('owner_id', $owner->getKey())
                        ->whereDate('period_start', $periodStart)->whereDate('period_end', $periodEnd)->where('locked', true)->exists();
                    $owed = OwnerLedgerEntry::query()->where('owner_id', $owner->getKey())->whereNull('statement_id')
                        ->whereDate('service_date', '<=', $periodEnd)->exists();
                    if ($done || ! $owed) {
                        continue;
                    }

                    try {
                        $statement = $statements->finalize($statements->generateDraft((string) $owner->getKey(), $periodStart, $periodEnd));
                    } catch (RuntimeException $e) {
                        // a sealed period, or an owner paid in another currency (a rate is a person's decision): left for a person
                        Log::warning('statements:close-month — owner '.$owner->getKey().' not closed: '.$e::class);
                        $s++;

                        continue;
                    }

                    // the same row the hand path writes — a field list, never the figures
                    $audit->record('owner.statement_finalized', $owner, null, [
                        'fields' => ['statement_id', 'period_start', 'period_end'],
                        'statement_id' => (string) $statement->getKey(),
                        'automatic' => true,
                    ], actorType: 'system');
                    $c++;
                }

                return [$c, $s];
            });
            $closed += $c;
            $skipped += $s;
        }

        $this->info(sprintf('Statements closed: %d · left for a person: %d · period %s → %s', $closed, $skipped, $periodStart, $periodEnd));

        return self::SUCCESS;
    }
}
