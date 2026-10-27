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
use RuntimeException;

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

    /**
     * DO9-2 — save the wizard's progress as an owner-private DRAFT.
     *
     * ⚠️ THE RELAXATION IS PRECISE, AND IT IS ONLY ABOUT REQUIREDNESS. `requested_name` and `requested_kind`
     * become nullable so a half-filled wizard can be kept; every FORMAT and BOUND rule is the identical rule
     * {@see store()} applies (same shared {@see self::rules()}), because a draft that quietly accepted a
     * 5,000-character note or an unknown amenity id would just move the failure to submit-time, after the
     * owner believed their work was safe.
     *
     * ⚠️ NOTHING IS FILED. The row lands with status `draft`, outside {@see UnitRequest::scopeFiled()}, so the
     * operator never sees it and cannot action it. Submitting is the separate, fully-validated step.
     */
    public function draft(Request $request, OwnerUnitRequestService $units): RedirectResponse
    {
        $validated = $request->validate(self::rules(draft: true));

        $rateMinor = $this->money($validated['nightly_rate'] ?? null);
        $cleaningMinor = $this->money($validated['cleaning_fee'] ?? null);
        if ($rateMinor === false || $cleaningMinor === false) {
            // withInput() keeps everything they typed, so a bad rate costs them the click, never the draft.
            return back()->withInput()->withErrors([
                'nightly_rate' => __('php/http/controllers/owner/owner_unit_request_controller.invalid_rate'),
            ]);
        }

        try {
            $draft = $units->saveDraft(
                $request->user(),
                $this->payload($validated, $rateMinor, $cleaningMinor),
                $this->draftId($request),
            );
        } catch (ModelNotFoundException) {
            abort(404);
        }

        $this->storePhotos($draft, $request->file('photos', []));

        return redirect()->to(route('owner.add-unit', ['draft' => $draft->id]))
            ->with('unit_request_draft_saved', __('php/http/controllers/owner/owner_unit_request_controller.draft_saved'));
    }

    /**
     * DO9-3 — the owner retracts a request they have already filed. PRG on a controller route (#20), gated the
     * same way as filing one ($capActions, co-owner only).
     *
     * 🔒 OWNER-SCOPED: resolved through {@see UnitRequest}'s owner scope, so another owner's request id simply
     * does not exist here (404) — the id in the URL can never reach a request this owner does not hold.
     *
     * ⚠️ THE STATE DECISION IS NOT MADE HERE. This controller does not read `status` at all: the service
     * re-reads it under a row lock, because anything read at this point is already stale (§N.21.6's cousin —
     * a status on a rendered page is a snapshot). A refusal comes back as an honest, specific message.
     */
    public function withdraw(Request $request, OwnerUnitRequestService $units, string $unitRequest): RedirectResponse
    {
        try {
            /** @var UnitRequest $row */
            $row = UnitRequest::query()->findOrFail($unitRequest);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        try {
            $units->withdraw($row);
        } catch (RuntimeException $e) {
            return back()->with('unit_request_withdraw_error', $e->getMessage());
        }

        return back()->with('unit_request_withdrawn', __('php/http/controllers/owner/owner_unit_request_controller.request_withdrawn'));
    }

    public function store(Request $request, OwnerUnitRequestService $units): RedirectResponse
    {
        // ⚠️ THE FULL RULE SET, ALWAYS. A resumed draft submits through exactly this path, so the draft
        // relaxation can never reach a filed request: an incomplete draft is rejected HERE, at submit.
        $validated = $request->validate(self::rules());

        // Parse the two proposed money fields — a present-but-unparseable amount is a validation error, not a
        // silent drop, so the owner sees it rather than submitting a request with a lost rate.
        $rateMinor = $this->money($validated['nightly_rate'] ?? null);
        $cleaningMinor = $this->money($validated['cleaning_fee'] ?? null);
        if ($rateMinor === false || $cleaningMinor === false) {
            return back()->withInput()->withErrors([
                'nightly_rate' => __('php/http/controllers/owner/owner_unit_request_controller.invalid_rate'),
            ]);
        }

        try {
            // A `draft_id` promotes THAT draft row to pending (a state transition), rather than filing a second
            // request and orphaning the draft. It resolves owner-scoped + draft-only inside the service.
            $created = $units->submit(
                $request->user(),
                $this->payload($validated, $rateMinor, $cleaningMinor),
                $this->draftId($request),
            );
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
     * DO9-2 — the ONE rule set behind both writes (§N.8f). ⚠️ `$draft` changes EXACTLY two rules: the two
     * required fields become nullable. Everything else — the kind whitelist, the numeric bounds, the amenity
     * existence check, the 2,000-character note cap, the photo mime/size limits — is byte-identical in both
     * contexts, so a draft can be INCOMPLETE but never MALFORMED, and a draft that saves cleanly cannot fail
     * submit-time validation for a reason the owner was never shown.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function rules(bool $draft = false): array
    {
        $required = $draft ? ['nullable'] : ['required'];

        return [
            'requested_name' => [...$required, 'string', 'max:120'],
            'requested_kind' => [...$required, Rule::in(array_keys(OwnerUnitRequestService::KINDS))],
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
            'draft_id' => ['nullable', 'string', 'max:26'],
        ];
    }

    /**
     * The service payload — built once so a draft stores precisely what a submission stores (§N.8f).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, ?int $rateMinor, ?int $cleaningMinor): array
    {
        return [
            'requested_name' => $validated['requested_name'] ?? null,
            'requested_kind' => $validated['requested_kind'] ?? null,
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
        ];
    }

    /**
     * The draft being resumed, if any. Only ever a hint: the service re-resolves it owner-scoped and draft-only,
     * so a forged or foreign id simply yields a new row rather than touching someone else's request.
     */
    private function draftId(Request $request): ?string
    {
        $id = $request->input('draft_id');

        return is_string($id) && $id !== '' ? $id : null;
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
