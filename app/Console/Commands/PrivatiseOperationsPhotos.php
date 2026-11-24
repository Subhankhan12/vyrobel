<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Scopes\OrganizationScope;
use App\Support\Media\OperationsPhotos;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * ⚠️ VY-34 (audit of 2026-11-24) — MOVE THE HOUSEKEEPING AND MAINTENANCE PHOTOS UPLOADED BEFORE THE FIX OFF THE PUBLIC DISK.
 *
 * New photos go to the private disk ({@see OperationsPhotos::store()}); a photo uploaded earlier still sits on the public disk. Its
 * address is already the signed-in route (Media::url) and `/media` no longer serves those directories — this removes the file from the
 * public folder itself: copy to the private disk at the SAME path, verify the bytes are equal, point the record at the private disk,
 * and only then delete the public copy. Idempotent (a photo already private is skipped); `--dry-run` only counts.
 *
 * ⚠️ It never deletes a public copy it could not verify — a mismatch or a missing source is reported and left exactly as it was.
 */
class PrivatiseOperationsPhotos extends Command
{
    protected $signature = 'media:privatise-operations-photos {--dry-run : Count what would move; change nothing}';

    protected $description = 'Move housekeeping and maintenance photos from the public disk to the private disk (VY-34).';

    public function handle(TenantContext $tenant): int
    {
        $dry = (bool) $this->option('dry-run');

        [$moved, $failed] = $tenant->runAsSuperAdmin(function () use ($dry): array {
            $moved = $failed = 0;
            $rows = Media::query()->withoutGlobalScopes([OrganizationScope::class])
                ->whereIn('collection', array_keys(OperationsPhotos::COLLECTIONS))
                ->where('disk', '!=', OperationsPhotos::DISK)
                ->get();

            foreach ($rows as $media) {
                $from = Storage::disk((string) $media->disk);
                $to = Storage::disk(OperationsPhotos::DISK);
                $path = (string) $media->path;

                if (! $from->fileExists($path)) {
                    $failed++;
                    $this->warn("missing on the {$media->disk} disk: media {$media->getKey()} — left as it is");

                    continue;
                }
                if ($dry) {
                    $moved++;

                    continue;
                }

                $bytes = (string) $from->get($path);
                if (! $to->fileExists($path)) {
                    $to->put($path, $bytes);
                }
                if (! hash_equals(hash('sha256', $bytes), hash('sha256', (string) $to->get($path)))) {
                    $failed++;
                    $this->warn("the private copy of media {$media->getKey()} does not match — the public file is kept");

                    continue;
                }

                $media->forceFill(['disk' => OperationsPhotos::DISK])->saveQuietly();
                $from->delete($path);
                $moved++;
            }

            return [$moved, $failed];
        });

        $this->info(sprintf('%s: %d · not moved (see above): %d', $dry ? 'Would move' : 'Moved', $moved, $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
