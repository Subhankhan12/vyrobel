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
    public function submit(User $user, array $data): UnitRequest
    {
        $owner = $this->owner($user);

        $request = UnitRequest::create([
            'owner_id' => $owner->id,
            'requested_name' => $data['requested_name'],
            'requested_kind' => $data['requested_kind'],
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
            'status' => 'pending',
        ]);

        // Amenities are real catalog rows — attach only ids that genuinely exist (a tampered id is silently
        // dropped by sync against the validated set the caller passes). Shared pivot, never a parallel table.
        if (! empty($data['amenity_ids'])) {
            $request->amenities()->sync($data['amenity_ids']);
        }

        $this->audit->record('owner.unit_requested', $owner, null, ['kind' => $request->requested_kind]);

        return $request;
    }

    /**
     * The owner's OWN requests, newest first — owner-scoped by BelongsToOwner.
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
