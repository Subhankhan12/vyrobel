<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Amenity;
use App\Models\Owner;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\UnitRequest;
use App\Models\User;
use App\Notifications\Owner\UnitRequestReviewedNotification;
use App\Support\Events\AuditEventRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Owner ADD-UNIT request path (Phase 2 · Sub-gate 6). ⚠️ An owner does NOT create billable inventory directly —
 * they submit a REQUEST the operator reviews. This service captures + lists those requests, SELF-ONLY: the owner
 * is resolved by user_id (never a request id), so a request can only ever be filed against — and listed for —
 * the authenticated owner's OWN account.
 */
class OwnerUnitRequestService
{
    /** The requestable kinds — the REAL `units.unit_kind` enum values, so a request maps to what can be created. */
    public const KINDS = [
        'apartment' => 'Apartment',
        'villa' => 'Villa',
        'room' => 'Room',
        'dorm_bed' => 'Dorm bed',
    ];

    /** Upper bounds for the wizard's stepper fields — server-authoritative, so a tampered POST can't overflow. */
    public const MAX_BEDROOMS = 20;

    public const MAX_BATHROOMS = 20;

    public const MAX_SLEEPS = 50;

    public const MAX_MIN_NIGHTS = 30;

    /** Photos accepted per request (the wizard offers a cover + a couple more). */
    public const MAX_PHOTOS = 6;

    public function __construct(private AuditEventRecorder $audit) {}

    /** The authenticated user's OWN owner record (never a request id); non-linked → 404 (fail closed). */
    public function owner(User $user): Owner
    {
        return Owner::query()->where('user_id', $user->getKey())->firstOrFail();
    }

    /**
     * The REAL global amenity catalog (a shared reference lookup, {@see Amenity}) — the toggle chips render from
     * this, never a fabricated list (#39). Ordered by category then name so the chips group naturally; an empty
     * catalog renders no chips (honest), never invented ones.
     *
     * @return Collection<int, Amenity>
     */
    public function amenityCatalog(): Collection
    {
        return Amenity::query()->orderBy('category')->orderBy('name')->get();
    }

    /**
     * File a pending unit request for the authenticated owner. It creates NO unit — the operator turns it into
     * real inventory as their own action. The wizard fields are the owner's PROPOSED values on a pending request
     * (never a live unit / rate write, #39). Amenities attach through the shared polymorphic pivot; photos are
     * attached by the caller (it holds the uploaded files). Money is pre-parsed integer minor units (#17).
     *
     * @param  array{
     *     requested_name: string, requested_kind: string, location?: ?string, street_address?: ?string,
     *     bedrooms?: int, bathrooms?: int, sleeps?: int, requested_rate_minor?: ?int, cleaning_fee_minor?: ?int,
     *     min_nights?: ?int, currency?: ?string, notes?: ?string, amenity_ids?: array<int, string>
     * }  $data
     */
    public function submit(User $user, array $data, ?string $draftId = null): UnitRequest
    {
        $owner = $this->owner($user);
        $request = $this->persist($owner, $data, 'pending', $draftId);

        $this->audit->record('owner.unit_requested', $owner, null, ['kind' => $request->requested_kind]);

        return $request;
    }

    /**
     * DO9-2 — save the wizard's progress as an owner-private DRAFT. Same owner, same table, same persist path as
     * {@see submit()} (§N.8f); only the status differs. Creates the draft, or updates the one being resumed.
     *
     * ⚠️ A DRAFT IS NOT A REQUEST. Nothing is filed: it never enters {@see UnitRequest::scopeFiled()}, so it
     * cannot appear on the operator's desk or in their counts, and {@see approve()}/{@see decline()} refuse it
     * because they require `pending`. It is the owner's own unfinished work until they submit it.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveDraft(User $user, array $data, ?string $draftId = null): UnitRequest
    {
        $owner = $this->owner($user);
        $draft = $this->persist($owner, $data, 'draft', $draftId);

        $this->audit->record('owner.unit_request_drafted', $owner, null, ['kind' => $draft->requested_kind]);

        return $draft;
    }

    /**
     * Resolve one of THIS owner's drafts for resuming or submitting — owner-scoped by {@see BelongsToOwner} AND
     * pinned to `draft`.
     *
     * ⚠️ THE STATUS CHECK IS A GUARD, NOT A CONVENIENCE. Without it a resumed-draft submit could be pointed at
     * an ALREADY-FILED request id and would overwrite a request the operator has seen — or one they have already
     * approved — from the owner's side. A non-draft id resolves to null and is treated as "no draft".
     */
    public function findDraft(?string $draftId): ?UnitRequest
    {
        if ($draftId === null || $draftId === '') {
            return null;
        }

        return UnitRequest::query()->whereKey($draftId)->where('status', 'draft')->first();
    }

    /**
     * The shared writer behind {@see submit()} and {@see saveDraft()} — ONE definition of how a request's fields
     * reach the table, so a draft and a submission can never drift into storing different things (§N.8f).
     *
     * `$draftId` resumes: the SAME row is updated and promoted rather than a second one created, which is what
     * keeps "draft → submitted" a state transition instead of a duplicate request. It is resolved through
     * {@see findDraft()}, so only the owner's own, still-draft row can ever be written.
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(Owner $owner, array $data, string $status, ?string $draftId): UnitRequest
    {
        $attributes = [
            'owner_id' => $owner->id,
            'requested_name' => $data['requested_name'] ?? null,
            'requested_kind' => $data['requested_kind'] ?? array_key_first(self::KINDS),
            'location' => $data['location'] ?? null,
            'street_address' => $data['street_address'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? 0,
            'bathrooms' => $data['bathrooms'] ?? 0,
            'sleeps' => $data['sleeps'] ?? 0,
            'requested_rate_minor' => $data['requested_rate_minor'] ?? null,
            'cleaning_fee_minor' => $data['cleaning_fee_minor'] ?? null,
            'min_nights' => $data['min_nights'] ?? null,
            'currency' => $data['currency'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $status,
        ];

        $existing = $this->findDraft($draftId);

        if ($existing instanceof UnitRequest) {
            $existing->update($attributes);
            $request = $existing->refresh();
        } else {
            $request = UnitRequest::create($attributes);
        }

        // Amenities are real catalog rows — attach only ids that genuinely exist (a tampered id is silently
        // dropped by sync against the validated set the caller passes). Shared pivot, never a parallel table.
        // ⚠️ sync() on a resumed draft also REMOVES chips the owner has since unticked, which is what makes the
        // saved draft match the wizard rather than accumulate every amenity ever selected.
        $request->amenities()->sync($data['amenity_ids'] ?? []);

        return $request;
    }

    /**
     * The owner's OWN requests, newest first — owner-scoped by BelongsToOwner. ⚠️ Deliberately INCLUDES drafts:
     * this is the owner's own list, and a draft they cannot see is a draft they cannot resume. The operator's
     * equivalent read is {@see UnitRequest::scopeFiled()}, which excludes them.
     *
     * @return Collection<int, UnitRequest>
     */
    public function requests(): Collection
    {
        return UnitRequest::query()->orderByDesc('created_at')->get();
    }

    /**
     * #104 GATE 1 — the operator APPROVES a pending request by creating the REAL unit from the owner's proposed
     * values. This is the app's first unit-creation writer, so it is deliberately narrow: the operator chooses an
     * existing property + one of that property's existing room types (the operational decisions the owner can't
     * make), and the unit is created under them, ASSIGNED to the requesting owner, and made bookable by recomputing
     * availability. The proposed nightly rate is NOT auto-applied — rates live on the shared room-type rate plan
     * (applying one here would reprice the type's OTHER units), so the operator prices it on the Rates screen; the
     * proposed figure is carried into the audit for reference. The request is stamped `fulfilled` and linked to the
     * unit it became (clean provenance), and the owner is notified. Everything runs in ONE transaction.
     *
     * @throws RuntimeException when the request is not pending, or the property/room type is not in this tenant
     */
    public function approve(UnitRequest $request, User $reviewer, string $propertyId, string $roomTypeId, ?string $note = null): Unit
    {
        if ($request->status !== 'pending') {
            throw new RuntimeException('Only a pending request can be approved.');
        }

        // Tenant-scoped resolution — a foreign property/room-type id 404s via the global scope, and the room type
        // must belong to the chosen property (no cross-property graft).
        $property = Property::query()->findOrFail($propertyId);
        $roomType = RoomType::query()->where('property_id', $property->id)->findOrFail($roomTypeId);

        $currency = (string) ($request->currency ?? $property->currency ?? 'EUR');

        return DB::transaction(function () use ($request, $reviewer, $property, $roomType, $currency, $note): Unit {
            $unit = Unit::create([
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'code' => $this->uniqueCode($property->id, (string) $request->requested_name),
                'name' => (string) $request->requested_name,
                'unit_kind' => (string) $request->requested_kind,
                'max_occupancy' => $request->sleeps > 0 ? $request->sleeps : 2,
                'owner_id' => $request->owner_id,
                'is_active' => true,
            ]);

            // The new unit adds to the room type's sellable inventory — recompute a forward year so it is bookable
            // immediately (the certified availability spine, never a direct availability write).
            $today = CarbonImmutable::today();
            app(AvailabilityService::class)->recompute($roomType->id, $today->toDateString(), $today->addYear()->toDateString());

            $request->forceFill([
                'status' => 'approved',
                'created_unit_id' => $unit->id,
                'review_note' => $note,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $this->audit->record('owner.unit_request_approved', $request, null, [
                'unit_id' => $unit->id,
                'kind' => (string) $request->requested_kind,
                'proposed_rate_minor' => $request->requested_rate_minor,
                'currency' => $currency,
            ]);

            $request->owner?->user?->notify(new UnitRequestReviewedNotification($request->refresh(), 'approved'));

            return $unit;
        });
    }

    /**
     * #104 GATE 1 — the operator DECLINES a pending request. No inventory is created; the request is stamped
     * `declined` with the operator's reason, audited, and the owner is notified so they learn the outcome (the
     * feedback loop the audit found missing). Reversible only by the owner re-requesting — the record is retained.
     *
     * @throws RuntimeException when the request is not pending
     */
    public function decline(UnitRequest $request, User $reviewer, ?string $note = null): UnitRequest
    {
        if ($request->status !== 'pending') {
            throw new RuntimeException('Only a pending request can be declined.');
        }

        $request->forceFill([
            'status' => 'declined',
            'review_note' => $note,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ])->save();

        $this->audit->record('owner.unit_request_declined', $request, null, ['kind' => (string) $request->requested_kind]);

        $request->owner?->user?->notify(new UnitRequestReviewedNotification($request->refresh(), 'declined'));

        return $request;
    }

    /**
     * A unique unit `code` within the property (the table enforces UNIQUE(property_id, code)). Derived from the
     * requested name so it reads sensibly (e.g. "Sea View 2B" → "SEA-VIEW-2B"), with a numeric suffix only if that
     * base already exists — deterministic, no reliance on random ids.
     */
    private function uniqueCode(string $propertyId, string $name): string
    {
        $base = Str::upper(Str::slug($name, '-'));
        if ($base === '') {
            $base = 'UNIT';
        }
        $base = Str::limit($base, 24, '');

        $code = $base;
        $n = 2;
        while (Unit::query()->where('property_id', $propertyId)->where('code', $code)->exists()) {
            $code = $base.'-'.$n;
            $n++;
        }

        return $code;
    }
}
