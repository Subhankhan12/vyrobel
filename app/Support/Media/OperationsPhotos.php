<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Http\Controllers\Operator\OperationsPhotoController;
use App\Http\Controllers\PublicMediaController;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\Media;
use App\Support\Property\PropertyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ⚠️⚠️ VY-34 (audit of 2026-11-24, read in code) — HOUSEKEEPING AND MAINTENANCE PHOTOS ARE PRIVATE.
 *
 * They were stored on the PUBLIC disk and served by {@see PublicMediaController} at `/media/…` to anyone with the
 * address — no sign-in, only an address that is hard to guess. They are pictures of the inside of guests' rooms, of damage and of
 * whatever a cleaner found, so they now live on the PRIVATE disk (`local`) and open only through {@see OperationsPhotoController},
 * for a signed-in member of the back office who can see that task or work order (the tenant scope, then the person's properties —
 * the same {@see PropertyScope} the board and the work order use).
 *
 * ONE place for the three things every caller needs: where a photo is stored, its address, and the response that serves it. A photo
 * uploaded before this change (still on the public disk) is moved by `media:privatise-operations-photos`; until then it opens through
 * the same signed-in route — never again at `/media`.
 */
final class OperationsPhotos
{
    /** @var array<string, class-string<Model>> the collection → the record it belongs to */
    public const COLLECTIONS = [
        'hk_proof' => HousekeepingTask::class,
        'mt_photo' => MaintenanceTicket::class,
    ];

    /** The private disk: its root is outside anything a web server answers. */
    public const DISK = 'local';

    /** The upload directories, as they were on the public disk — kept, so a moved file keeps its path. */
    public const DIRECTORIES = ['housekeeping', 'maintenance'];

    /** The image types served; the file's own bytes must say one of them. */
    private const TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public static function isOperations(Media $media): bool
    {
        return array_key_exists((string) $media->collection, self::COLLECTIONS);
    }

    /** Store an uploaded (already validated) photo for a task or work order on the private disk, and record it. */
    public static function store(UploadedFile $file, Model $owner, string $collection, string $directory, string $alt): Media
    {
        $ext = $file->extension() ?: 'jpg';                      // from the VALIDATED file, never the client name
        $path = $file->storeAs($directory.'/'.$owner->getKey(), (string) Str::ulid().'.'.$ext, self::DISK);

        return Media::create([
            'mediable_type' => $owner->getMorphClass(),
            'mediable_id' => $owner->getKey(),
            'collection' => $collection,
            'disk' => self::DISK,
            'path' => (string) $path,
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'alt' => $alt,
        ]);
    }

    /** The signed-in address of a photo — the only one it has. */
    public static function url(Media $media): string
    {
        return route('app.photos.show', ['media' => (string) $media->getKey()]);
    }

    /**
     * The photo, for this viewer — or a 404. Tenant-scoped by the Media model; then the task or work order it belongs to must be one the
     * person can see (their properties), exactly as on the board. Anything that is not one of these two collections is not served here.
     */
    public static function find(string $id): Media
    {
        $media = Media::query()->whereIn('collection', array_keys(self::COLLECTIONS))->findOrFail($id);
        $scope = app(PropertyScope::class);

        match ((string) $media->collection) {
            'hk_proof' => $scope->task((string) $media->mediable_id),
            'mt_photo' => $scope->ticket((string) $media->mediable_id),
            default => abort(404),
        };

        return $media;
    }

    public static function response(Media $media): BinaryFileResponse
    {
        $disk = Storage::disk((string) $media->disk);
        $path = (string) $media->path;
        abort_unless($disk->fileExists($path), 404);

        $absolute = $disk->path($path);
        $type = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($absolute);
        abort_unless(in_array($type, self::TYPES, true), 404);   // the file's own bytes decide, never its name

        $response = response()->file($absolute, ['Content-Type' => $type, 'X-Content-Type-Options' => 'nosniff']);
        // ⚠️ never at the shared edge, never kept by the browser: a picture of a guest's room. Set on the response object — a
        //    BinaryFileResponse marks itself `public` and a Cache-Control passed in the headers array does not undo that.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
