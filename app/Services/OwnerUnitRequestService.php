<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Amenity;
use App\Models\Owner;
use App\Models\UnitRequest;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Collection;

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
}
