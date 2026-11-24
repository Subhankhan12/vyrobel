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

    protected $description = 'Hard-delete guest ID documents (encrypted file + rows) 30 days after the stay checks out, and at once for a booking that is cancelled, a no-show or gone (data minimisation).';

    /** Reservation states after which the stay will not happen — the image has no register entry to serve. */
    private const STAY_WILL_NOT_HAPPEN = ['cancelled', 'no_show'];

    public function handle(GuestIdDocumentService $ids, TenantContext $tenant): int
    {
        $days = max(0, (int) $this->option('days'));
        $cutoff = CarbonImmutable::today()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        // Platform maintenance across every tenant — the per-doc org is still carried on its own rows.
        $purged = $tenant->runAsSuperAdmin(function () use ($cutoff, $dryRun, $ids): int {
            $count = 0;

            // ⚠️⚠️ VY-18 (audit of 2026-11-24) — THREE REASONS AN IMAGE IS DUE, NOT ONE. The only rule was "the booking's checkout is
            //    more than N days ago", so (1) a CANCELLED or no-show booking kept its image until N days after dates that never
            //    happened — months, for a booking made far ahead — and (2) an image whose booking was DELETED matched nothing and was
            //    never purged at all. Both are due now, whatever their dates. A cancellation purges at once
            //    (PurgeGuestIdDocumentsOnTermination); this is the net under it, and what clears images left by older releases.
            GuestDocument::query()
                ->where(function ($due) use ($cutoff): void {
                    $due->whereHas('reservation', fn ($q) => $q->whereDate('check_out', '<', $cutoff->toDateString()))
                        ->orWhereHas('reservation', fn ($q) => $q->whereIn('status', self::STAY_WILL_NOT_HAPPEN))
                        ->orWhereDoesntHave('reservation');   // deleted (the relation hides a removed booking), or never tied to one
                })
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
        $this->info("{$verb} {$purged} guest ID document(s) (checkout older than {$days} days, or a booking that is cancelled, a no-show or gone).");

        return self::SUCCESS;
    }
}
