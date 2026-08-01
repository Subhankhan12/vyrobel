<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GuestDocument;
use App\Services\Guest\GuestIdDocumentService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * DATA MINIMISATION — hard-delete guest ID documents 30 days after the stay's checkout (Guest Portal batch 2 ·
 * GATE 2 · founder decision D2). Makes the online-check-in promise "deleted 30 days after checkout" REAL: the
 * encrypted file bytes AND the Media/Document/GuestDocument rows are destroyed via the single teardown
 * ({@see GuestIdDocumentService::purge}). A genuine retention job, not a claim.
 *
 * Runs across ALL tenants (super-admin context — this is a platform maintenance job, not a tenant request); the
 * GuestDocument still resolves its own org via its columns. Idempotent — a document with no due reservation is
 * skipped, and a re-run after a purge finds nothing.
 */
class PurgeExpiredGuestDocuments extends Command
{
    protected $signature = 'guest-documents:purge-expired {--days=30 : Days after checkout to retain} {--dry-run : Report without deleting}';

    protected $description = 'Hard-delete guest ID documents (encrypted file + rows) 30 days after the stay checks out (data minimisation).';

    public function handle(GuestIdDocumentService $ids, TenantContext $tenant): int
    {
        $days = max(0, (int) $this->option('days'));
        $cutoff = CarbonImmutable::today()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        // Platform maintenance across every tenant — the per-doc org is still carried on its own rows.
        $purged = $tenant->runAsSuperAdmin(function () use ($cutoff, $dryRun, $ids): int {
            $count = 0;

            GuestDocument::query()
                ->whereHas('reservation', fn ($q) => $q->whereDate('check_out', '<', $cutoff->toDateString()))
                ->with('document.media')
                ->chunkById(200, function ($documents) use (&$count, $dryRun, $ids): void {
                    foreach ($documents as $guestDocument) {
                        if (! $dryRun) {
                            $ids->purge($guestDocument);
                        }
                        $count++;
                    }
                });

            return $count;
        });

        $verb = $dryRun ? 'Would purge' : 'Purged';
        $this->info("{$verb} {$purged} expired guest ID document(s) (checkout older than {$days} days).");

        return self::SUCCESS;
    }
}
