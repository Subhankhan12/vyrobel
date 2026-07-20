<?php

declare(strict_types=1);

namespace App\Services;

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

    public function __construct(private AuditEventRecorder $audit) {}

    /** The authenticated user's OWN owner record (never a request id); non-linked → 404 (fail closed). */
    public function owner(User $user): Owner
    {
        return Owner::query()->where('user_id', $user->getKey())->firstOrFail();
    }

    /**
     * File a pending unit request for the authenticated owner. It creates NO unit — the operator turns it into
     * real inventory as their own action.
     *
     * @param  array{requested_name: string, requested_kind: string, notes?: ?string}  $data
     */
    public function submit(User $user, array $data): UnitRequest
    {
        $owner = $this->owner($user);

        $request = UnitRequest::create([
            'owner_id' => $owner->id,
            'requested_name' => $data['requested_name'],
            'requested_kind' => $data['requested_kind'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

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
