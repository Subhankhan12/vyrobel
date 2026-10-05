<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\RoomType;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Operator room-type PHOTO management (Demo-readiness · Part A · Step A2) — the app's FIRST user
 * file-upload path, treated as a security surface. The proven operator-mutation pattern:
 * POST → controller → store on the `public` disk + write `media` rows → PRG. TENANT-scoped: the
 * room-type (and each photo) is resolved via a string id under the operator's org, so a cross-org id
 * 404s and nothing is written.
 *
 * SECURITY:
 *   - accepts ONLY real images — the `image` rule validates actual image CONTENT (getimagesize), and
 *     `mimes:jpeg,png,webp` checks the content-guessed type, so a disguised `.jpg` (really a script)
 *     is rejected; size is capped;
 *   - the STORED filename is generated (`Str::ulid().ext`) — the user's filename is never used, so
 *     there is no path traversal; the extension comes from the validated file, not the client string;
 *   - files land on the `public` disk deliberately (served read-only from /storage) — never an
 *     arbitrary path.
 *
 * ⚠️ 2026-10-05 — three gaps closed with the Units page's photo dialog: a paused workspace could still add, reorder and
 * remove photos (every other inventory write is refused); the room type was found through the TENANT scope only, so staff
 * limited to one property could change another's photos by posting its id (DEC-k — now the same rule as every unit
 * write); and each change landed the page with the photos closed. Every answer now says which room type's photos to
 * reopen (`units_photos`).
 */
class RoomTypePhotoController extends Controller
{
    private const MAX_KB = 5120;                  // 5 MB

    private const DISK = 'public';

    private const COLLECTION = 'photos';

    /** Upload one image → a media row in the room-type's photo collection (first upload becomes cover). */
    public function store(Request $request, string $roomType): RedirectResponse
    {
        $rt = $this->roomType($roomType);
        if ($guard = $this->readOnly($rt)) {
            return $guard;
        }

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_KB],
        ]);

        $file = $request->file('photo');
        // Extension from the VALIDATED file (content-guessed), never the client-supplied name.
        $ext = $file->extension() ?: 'jpg';
        $name = (string) Str::ulid().'.'.$ext;
        $path = $file->storeAs('room-photos/'.$rt->getKey(), $name, self::DISK);

        $isFirst = $rt->photos()->count() === 0;
        $nextOrder = (int) ($rt->photos()->max('sort_order') ?? -1) + 1;

        Media::create([
            'mediable_type' => $rt->getMorphClass(),
            'mediable_id' => $rt->getKey(),
            'collection' => self::COLLECTION,
            'disk' => self::DISK,
            'path' => $path,
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'sort_order' => $nextOrder,
            'is_cover' => $isFirst,   // the first photo is the cover by default
            'alt' => (string) $rt->name.' photo',
        ]);

        return $this->back($rt)->with('units_notice', 'Photo added to '.$rt->name.'.');
    }

    /** Make a photo the cover (exactly one cover per room-type). */
    public function setCover(Request $request, string $roomType, string $photo): RedirectResponse
    {
        $rt = $this->roomType($roomType);
        if ($guard = $this->readOnly($rt)) {
            return $guard;
        }
        $target = $this->photo($rt, $photo);

        $rt->photos()->update(['is_cover' => false]);
        $target->update(['is_cover' => true]);

        return $this->back($rt)->with('units_notice', 'Cover photo updated.');
    }

    /** Reorder a photo up/down by swapping sort_order with its neighbour. */
    public function move(Request $request, string $roomType, string $photo): RedirectResponse
    {
        $rt = $this->roomType($roomType);
        if ($guard = $this->readOnly($rt)) {
            return $guard;
        }
        $target = $this->photo($rt, $photo);
        $direction = $request->string('direction')->value() === 'up' ? 'up' : 'down';

        $neighbour = $rt->photos()
            ->when($direction === 'up',
                fn ($q) => $q->where('sort_order', '<', $target->sort_order)->orderByDesc('sort_order'),
                fn ($q) => $q->where('sort_order', '>', $target->sort_order)->orderBy('sort_order'),
            )
            ->first();

        if ($neighbour !== null) {
            $targetOrder = $target->sort_order;
            $target->update(['sort_order' => $neighbour->sort_order]);
            $neighbour->update(['sort_order' => $targetOrder]);
        }

        return $this->back($rt)->with('units_notice', 'Photo order updated.');
    }

    /** Remove a photo (delete the file + soft-delete the row); promote a new cover if needed. */
    public function destroy(Request $request, string $roomType, string $photo): RedirectResponse
    {
        $rt = $this->roomType($roomType);
        if ($guard = $this->readOnly($rt)) {
            return $guard;
        }
        $target = $this->photo($rt, $photo);
        $wasCover = $target->is_cover;

        Storage::disk($target->disk)->delete($target->path);
        $target->delete();

        if ($wasCover) {
            $next = $rt->photos()->first();   // ordered — the new first
            $next?->update(['is_cover' => true]);
        }

        return $this->back($rt)->with('units_notice', 'Photo removed.');
    }

    /**
     * The operator's OWN room-type (tenant-scoped string id → cross-org 404), inside the properties the person works in
     * (DEC-k — another property's id is not found, never merely hidden).
     */
    private function roomType(string $id): RoomType
    {
        $ids = app(PropertyContext::class)->readIds();

        return RoomType::query()
            ->when($ids !== [], fn ($q) => $q->whereIn('property_id', $ids))
            ->findOrFail($id);
    }

    /** Back where the person was, with this room type's photos open again. */
    private function back(RoomType $rt): RedirectResponse
    {
        return back()->with('units_photos', (string) $rt->getKey());
    }

    /** A paused workspace changes no photo — the shell's banner says why; this names what was refused. */
    private function readOnly(RoomType $rt): ?RedirectResponse
    {
        $workspace = app(OperatorWorkspace::class);
        if (! $workspace->isReadOnly()) {
            return null;
        }
        $until = $workspace->resumeLabel();

        return $this->back($rt)->with('units_error', 'Workspace paused — photos can’t be changed '
            .($until !== null && $until !== '' ? 'until '.$until : 'for now').'.');
    }

    /** A photo that belongs to THIS room-type's photo collection (never another entity's media). */
    private function photo(RoomType $rt, string $id): Media
    {
        return Media::query()
            ->where('mediable_type', $rt->getMorphClass())
            ->where('mediable_id', $rt->getKey())
            ->where('collection', self::COLLECTION)
            ->findOrFail($id);
    }
}
