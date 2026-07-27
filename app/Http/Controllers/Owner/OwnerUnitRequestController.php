<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\UnitRequest;
use App\Services\OwnerUnitRequestService;
use App\Support\Money\MoneyInput;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Owner add-unit REQUEST write (Phase 2 · Sub-gate 6; the WIZARD write, My Units · GATE 3). POST → validate
 * server-side → {@see OwnerUnitRequestService} → attach photos → PRG to the "done" state. SELF-ONLY: the request
 * is filed against the owner resolved by user_id (never a request id). It creates NO inventory — the operator
 * reviews the request and creates the real unit as their own action.
 *
 * The wizard's steps are a client-side convenience; the WRITE is ONE ordinary form POST (never `/livewire/update`
 * — the owner-scope bypass boundary, #20), so EVERY field is (re)validated here regardless of what the UI allowed.
 * Money is parsed with {@see MoneyInput} (string arithmetic, #17); the PROPOSED rate is stored on the request, it
 * is NOT a live `rate_calendar` write (#39). Photo handling reuses the hardened room-type upload discipline:
 * content-validated images only, a GENERATED ULID filename (no client name → no path traversal), on the `public`
 * disk, capped in size and count.
 */
class OwnerUnitRequestController extends Controller
{
    private const PHOTO_MAX_KB = 5120;   // 5 MB per photo — mirrors RoomTypePhotoController

    private const DISK = 'public';

    public function store(Request $request, OwnerUnitRequestService $units): RedirectResponse
    {
        $validated = $request->validate([
            'requested_name' => ['required', 'string', 'max:120'],
            'requested_kind' => ['required', Rule::in(array_keys(OwnerUnitRequestService::KINDS))],
            'location' => ['nullable', 'string', 'max:120'],
            'street_address' => ['nullable', 'string', 'max:200'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:'.OwnerUnitRequestService::MAX_BEDROOMS],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:'.OwnerUnitRequestService::MAX_BATHROOMS],
            'sleeps' => ['nullable', 'integer', 'min:0', 'max:'.OwnerUnitRequestService::MAX_SLEEPS],
            'min_nights' => ['nullable', 'integer', 'min:1', 'max:'.OwnerUnitRequestService::MAX_MIN_NIGHTS],
            // Money arrives as hand-typed text; MoneyInput turns it into minor units (rejecting junk / non-positive).
            'nightly_rate' => ['nullable', 'string', 'max:12'],
            'cleaning_fee' => ['nullable', 'string', 'max:12'],
            // Amenities must be REAL catalog ids — an unknown id is rejected outright (never silently "created").
            'amenities' => ['nullable', 'array', 'max:40'],
            'amenities.*' => ['string', Rule::exists('amenities', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:'.OwnerUnitRequestService::MAX_PHOTOS],
            'photos.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::PHOTO_MAX_KB],
        ]);

        // Parse the two proposed money fields — a present-but-unparseable amount is a validation error, not a
        // silent drop, so the owner sees it rather than submitting a request with a lost rate.
        $rateMinor = $this->money($validated['nightly_rate'] ?? null);
        $cleaningMinor = $this->money($validated['cleaning_fee'] ?? null);
        if ($rateMinor === false || $cleaningMinor === false) {
            return back()->withInput()->withErrors([
                'nightly_rate' => 'Enter the rate as a plain amount, e.g. 180 or 180.00.',
            ]);
        }

        try {
            $created = $units->submit($request->user(), [
                'requested_name' => $validated['requested_name'],
                'requested_kind' => $validated['requested_kind'],
                'location' => $validated['location'] ?? null,
                'street_address' => $validated['street_address'] ?? null,
                'bedrooms' => (int) ($validated['bedrooms'] ?? 0),
                'bathrooms' => (int) ($validated['bathrooms'] ?? 0),
                'sleeps' => (int) ($validated['sleeps'] ?? 0),
                'requested_rate_minor' => $rateMinor,
                'cleaning_fee_minor' => $cleaningMinor,
                'min_nights' => isset($validated['min_nights']) ? (int) $validated['min_nights'] : null,
                'currency' => ($rateMinor !== null || $cleaningMinor !== null) ? 'EUR' : null,
                'notes' => $validated['notes'] ?? null,
                'amenity_ids' => $validated['amenities'] ?? [],
            ]);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        $this->storePhotos($created, $request->file('photos', []));

        // Carry the just-created request to the "done" screen (id + a couple of fields for the confirmation).
        return redirect()->to(route('owner.add-unit'))->with('unit_request_done', [
            'id' => $created->id,
            'name' => (string) $created->requested_name,
            'location' => (string) ($created->location ?? ''),
        ]);
    }

    /**
     * null → no amount given; false → given but unparseable; int → minor units. The two-state result lets the
     * caller distinguish "left blank" (fine) from "typed garbage" (an error the owner must see).
     */
    private function money(?string $input): int|false|null
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        return MoneyInput::toMinor($input) ?? false;
    }

    /**
     * Store each uploaded image as a `media` row on the request (first = cover). Reuses the room-type upload
     * security posture: a GENERATED ULID filename (never the client's), the extension from the validated file.
     *
     * @param  array<int, UploadedFile|null>  $files
     */
    private function storePhotos(UnitRequest $request, array $files): void
    {
        $order = 0;
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $ext = $file->extension() ?: 'jpg';
            $name = (string) Str::ulid().'.'.$ext;
            $path = $file->storeAs('unit-request-photos/'.$request->getKey(), $name, self::DISK);

            Media::create([
                'mediable_type' => $request->getMorphClass(),
                'mediable_id' => $request->getKey(),
                'collection' => 'photos',
                'disk' => self::DISK,
                'path' => $path,
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'sort_order' => $order,
                'is_cover' => $order === 0,   // the first upload is the cover
                'alt' => (string) $request->requested_name.' photo',
            ]);
            $order++;
        }
    }
}
