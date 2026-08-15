<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\UnitRequest;
use App\Services\OwnerUnitRequestService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Unit Requests (Phase 2 · Sub-gate 6) — the operator-visibility side of the owner add-unit request.
 * Read-only: the operator SEES an owner's pending requests here and creates the real unit as their own action
 * (that creation is deliberately NOT built — it stays the operator's manual step). Tenant-scoped: on /app the
 * owner scope is inactive, so {@see UnitRequest} lists every owner's request in the org (never cross-tenant).
 */
#[Layout('components.layouts.operator')]
class OperatorUnitRequests extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        // ⚠️ DO9-2 — `filed()` EXCLUDES the owner's still-private DRAFTS. This query had no status filter at
        // all, so the moment a `draft` state existed every half-finished wizard would have appeared on the
        // operator's desk — an owner's unfinished, deliberately-unsent work shown to staff. The KPI counts
        // below derive from this same collection, so they inherit the exclusion rather than repeating it.
        $requests = UnitRequest::query()->filed()->with('owner', 'amenities', 'photos', 'createdUnit')->orderByDesc('created_at')->get();

        // #104 GATE 1 — the approve form needs the tenant's real properties + each property's room types (the
        // operational choices the owner can't make). Tenant-scoped; only offered to a manager (`units.manage`).
        $canManage = (bool) auth()->user()?->can('units.manage');
        $properties = [];
        if ($canManage) {
            foreach (Property::query()->with('roomTypes:id,property_id,name')->orderBy('name')->get() as $p) {
                $properties[] = [
                    'id' => (string) $p->getKey(),
                    'name' => (string) $p->name,
                    'room_types' => $p->roomTypes
                        ->map(fn (RoomType $rt): array => ['id' => (string) $rt->getKey(), 'name' => (string) $rt->name])
                        ->values()->all(),
                ];
            }
        }

        return view('livewire.operator.unit-requests', [
            'viewer' => $this->viewer(),
            'canManage' => $canManage,
            'properties' => $properties,
            'pending' => $requests->where('status', 'pending')->count(),
            'requests' => $requests->map(fn (UnitRequest $r): array => [
                'id' => (string) $r->id,
                'owner' => (string) ($r->owner->name ?? '—'),
                'name' => (string) $r->requested_name,
                'kind' => OwnerUnitRequestService::KINDS[$r->requested_kind],
                'details' => $this->detailSummary($r),
                'status' => (string) $r->status,
                'review_note' => (string) ($r->review_note ?? ''),
                'created_unit' => $r->createdUnit !== null ? (string) $r->createdUnit->name : null,
                'when' => $r->created_at?->format('j M Y'),
            ])->all(),
        ]);
    }

    /**
     * A compact, read-only summary of what the owner submitted through the Add-Unit wizard — location · capacity ·
     * the PROPOSED rate · amenity + photo counts · any note. Every part is a REAL captured value (#39), rendered
     * only when present (honest — an old 3-field request just shows what it has), so the operator can review the
     * request before creating the real unit. Money is the stored integer minor units, formatted (#17).
     */
    private function detailSummary(UnitRequest $r): string
    {
        $parts = [];

        if (($r->location ?? '') !== '') {
            $parts[] = (string) $r->location;
        }

        $capacity = [];
        if ($r->bedrooms > 0) {
            $capacity[] = $r->bedrooms.' bd';
        }
        if ($r->bathrooms > 0) {
            $capacity[] = $r->bathrooms.' ba';
        }
        if ($r->sleeps > 0) {
            $capacity[] = 'sleeps '.$r->sleeps;
        }
        if ($capacity !== []) {
            $parts[] = implode(' · ', $capacity);
        }

        if ($r->requested_rate_minor !== null) {
            $parts[] = $this->money($r->requested_rate_minor, (string) ($r->currency ?? 'EUR')).'/night';
        }

        $amenities = $r->amenities->count();
        if ($amenities > 0) {
            $parts[] = $amenities.' '.($amenities === 1 ? 'amenity' : 'amenities');
        }

        $photos = $r->photos->count();
        if ($photos > 0) {
            $parts[] = $photos.' '.($photos === 1 ? 'photo' : 'photos');
        }

        if (($r->notes ?? '') !== '') {
            $parts[] = (string) $r->notes;
        }

        return $parts === [] ? '—' : implode(' · ', $parts);
    }
}
