<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Reservation;
use App\Services\OwnerAttributionService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * Attribute owner revenue for bookings that were never attributed (wireframe-parity sweep · HIGH-2).
 *
 * `attributeReservation()` had NO production caller — only the seeder and tests — so every booking made
 * through a real entry point left the owner ledger empty. Owner earnings, statements, payouts and the annual
 * tax summary would have stayed at zero indefinitely while occupancy and ADR climbed. The entry points now
 * attribute at booking time; this command closes the gap for bookings that predate that fix.
 *
 * SAFE TO RE-RUN. `attributeReservation()` is idempotent (a booking already attributed is skipped by version)
 * and refuses to attribute a booking whose folio carries no room revenue, so a partially-written booking is
 * left attributable rather than latched at zero. Nothing is reversed or edited — attribution only appends.
 *
 * Use --dry-run to see what WOULD be attributed without writing anything.
 */
class BackfillOwnerAttribution extends Command
{
    protected $signature = 'owners:backfill-attribution
        {--organization= : limit to one organization id}
        {--dry-run : report what would be attributed, write nothing}';

    protected $description = 'Attribute owner revenue for bookings that were never attributed (idempotent).';

    public function handle(OwnerAttributionService $attribution, TenantContext $tenant): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $only = $this->option('organization');

        $organizations = Organization::query()
            ->when(is_string($only) && $only !== '', fn ($q) => $q->whereKey($only))
            ->get();

        if ($organizations->isEmpty()) {
            $this->warn('No organizations matched.');

            return self::SUCCESS;
        }

        $totalCandidates = 0;
        $totalAttributed = 0;

        foreach ($organizations as $organization) {
            $tenant->forOrganization((string) $organization->getKey(), function () use (
                $attribution, $organization, $dryRun, &$totalCandidates, &$totalAttributed
            ): void {
                $candidates = 0;
                $attributed = 0;

                Reservation::query()
                    ->with('reservationUnits')
                    ->orderBy('id')
                    ->chunkById(200, function ($reservations) use (
                        $attribution, $dryRun, &$candidates, &$attributed
                    ): void {
                        foreach ($reservations as $reservation) {
                            if (! $attribution->needsAttribution($reservation)) {
                                continue;
                            }

                            $candidates++;

                            if ($dryRun) {
                                continue;
                            }

                            $attribution->attributeReservation($reservation);

                            // Confirm it actually landed rather than assuming it did.
                            if (! $attribution->needsAttribution($reservation->refresh())) {
                                $attributed++;
                            }
                        }
                    });

                $this->line(sprintf(
                    '  %-38s candidates: %-5d attributed: %s',
                    (string) $organization->name,
                    $candidates,
                    $dryRun ? '(dry run)' : (string) $attributed,
                ));

                $totalCandidates += $candidates;
                $totalAttributed += $attributed;
            });
        }

        $this->newLine();
        $this->info($dryRun
            ? sprintf('%d booking(s) would be attributed. Re-run without --dry-run to write.', $totalCandidates)
            : sprintf('%d of %d booking(s) attributed.', $totalAttributed, $totalCandidates));

        return self::SUCCESS;
    }
}
