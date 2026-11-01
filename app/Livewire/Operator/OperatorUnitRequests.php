<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\UnitRequestActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Media;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\UnitRequest;
use App\Models\User;
use App\Services\OwnerUnitRequestService;
use App\Support\I18n\LocalDate;
use App\Support\Property\PropertyContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator UNIT REQUESTS — rebuilt 2026-10-03. An owner asks for a unit through the Add-Unit wizard; the operator reviews it
 * here and approves it (creating the real unit through {@see OwnerUnitRequestService}) or declines it, each in its own dialog
 * (the writes are `can:units.manage` controller POSTs — {@see UnitRequestActionController}).
 * Tenant-scoped: on /app the owner scope is inactive, so every owner's request in the organisation is listed, never another
 * organisation's. This component only renders — there is no wire action.
 *
 * ⚠️ WHAT IS SHOWN IS WHAT WAS FILED — {@see UnitRequest::scopeFiled()} keeps an owner's unsent drafts off the desk (DO9-2).
 * ⚠️ DEC-k (2026-10-03): the approve dialog offers only the properties the person works in — `PropertyContext::readIds()`,
 *    the same set the write accepts — so the dialog never offers a property the approve would refuse.
 */
#[Layout('components.layouts.operator')]
class OperatorUnitRequests extends Component
{
    use FormatsOperatorData;

    /** The views the chips offer, in order; `withdrawn` appears only when a request is in it. */
    public const VIEWS = ['pending', 'approved', 'declined', 'withdrawn', 'all'];

    public function render(): View
    {
        // ⚠️ DO9-2 — `filed()` EXCLUDES the owner's still-private DRAFTS: an owner's unfinished, deliberately-unsent work is
        // never shown to staff. The counts derive from this same collection, so they inherit the exclusion.
        // the owner WITH a removed one (archived), so the page can say so rather than show "—"
        $requests = UnitRequest::query()->filed()
            ->with(['owner' => fn ($q) => $q->withTrashed(), 'owner.user', 'amenities', 'photos', 'createdUnit'])
            ->orderByDesc('created_at')->get();

        $counts = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'withdrawn' => 0];
        foreach ($requests as $r) {
            $counts[(string) $r->status] = ($counts[(string) $r->status] ?? 0) + 1;
        }
        $counts['all'] = $requests->count();

        // The view: the one asked for, else the waiting ones while there are any, else everything.
        $asked = request()->query('status');
        $view = is_string($asked) && in_array($asked, self::VIEWS, true) ? $asked : ($counts['pending'] > 0 ? 'pending' : 'all');
        $shown = $view === 'all' ? $requests : $requests->where('status', $view)->values();

        // The approve dialog's choices — only the person's properties (DEC-k), each with its room types.
        $canManage = (bool) auth()->user()?->can('units.manage');
        $properties = [];
        if ($canManage) {
            $ids = app(PropertyContext::class)->readIds();
            foreach (Property::query()->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))->with('roomTypes:id,property_id,name')->orderBy('name')->get() as $p) {
                $properties[] = [
                    'id' => (string) $p->getKey(),
                    'name' => (string) $p->name,
                    'room_types' => $p->roomTypes
                        ->map(fn (RoomType $rt): array => ['id' => (string) $rt->getKey(), 'name' => (string) $rt->name])
                        ->values()->all(),
                ];
            }
        }

        $chips = [];
        foreach (self::VIEWS as $key) {
            if ($key === 'withdrawn' && $counts['withdrawn'] === 0) {
                continue;
            }
            $label = match ($key) {
                'pending' => __('php/livewire/operator/operator_unit_requests.chip_pending'),
                'approved' => __('php/livewire/operator/operator_unit_requests.chip_approved'),
                'declined' => __('php/livewire/operator/operator_unit_requests.chip_declined'),
                'withdrawn' => __('php/livewire/operator/operator_unit_requests.chip_withdrawn'),
                default => __('common.all'),
            };
            $chips[] = ['key' => $key, 'label' => $label, 'count' => $counts[$key],
                'url' => route('app.unit-requests', ['status' => $key])];
        }

        return view('livewire.operator.unit-requests', [
            'viewer' => $this->viewer(),
            'canManage' => $canManage,
            'properties' => $properties,
            'pending' => $counts['pending'],
            'view' => $view,
            'chips' => $chips,
            'requests' => $shown->map(fn (UnitRequest $r): array => [
                'id' => (string) $r->id,
                'owner' => (string) ($r->owner->name ?? '—'),
                // whether the owner can be told — the service notifies only an owner with a portal login
                'notifiable' => $r->owner?->user instanceof User && ! $r->owner->trashed(),
                // removed after asking: the request can only be declined (OwnerUnitRequestService::approve refuses)
                'owner_removed' => $r->owner?->trashed() ?? false,
                'name' => (string) $r->requested_name,
                'kind' => OwnerUnitRequestService::kindLabel((string) $r->requested_kind),
                'details' => $this->detailSummary($r),
                'rate' => $r->requested_rate_minor !== null ? $this->money((int) $r->requested_rate_minor, (string) ($r->currency ?? 'EUR')) : null,
                'photos' => $r->photos->take(4)->map(fn (Media $m): string => (string) $m->url)->values()->all(),
                'more_photos' => max(0, $r->photos->count() - 4),
                'status' => (string) $r->status,
                'review_note' => (string) ($r->review_note ?? ''),
                'created_unit' => $r->createdUnit !== null ? (string) ($r->createdUnit->name ?: $r->createdUnit->code) : null,
                'when' => $r->created_at?->translatedFormat(LocalDate::pattern('j M Y')),
                'reviewed' => $r->reviewed_at !== null ? CarbonImmutable::parse($r->reviewed_at)->translatedFormat(LocalDate::pattern('j M Y')) : null,
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
            $capacity[] = trans_choice('php/livewire/operator/operator_unit_requests.bedrooms', $r->bedrooms, ['count' => $r->bedrooms]);
        }
        if ($r->bathrooms > 0) {
            $capacity[] = trans_choice('php/livewire/operator/operator_unit_requests.bathrooms', $r->bathrooms, ['count' => $r->bathrooms]);
        }
        if ($r->sleeps > 0) {
            $capacity[] = trans_choice('php/livewire/operator/operator_unit_requests.sleeps', $r->sleeps, ['count' => $r->sleeps]);
        }
        if ($capacity !== []) {
            $parts[] = implode(' · ', $capacity);
        }

        if ($r->requested_rate_minor !== null) {
            $parts[] = __('php/livewire/operator/operator_unit_requests.rate_per_night', ['amount' => $this->money($r->requested_rate_minor, (string) ($r->currency ?? 'EUR'))]);
        }

        $amenities = $r->amenities->count();
        if ($amenities > 0) {
            $parts[] = trans_choice('php/livewire/operator/operator_unit_requests.amenities', $amenities, ['count' => $amenities]);
        }

        $photos = $r->photos->count();
        if ($photos > 0) {
            $parts[] = trans_choice('php/livewire/operator/operator_unit_requests.photos', $photos, ['count' => $photos]);
        }

        if (($r->notes ?? '') !== '') {
            $parts[] = (string) $r->notes;
        }

        return $parts === [] ? '—' : implode(' · ', $parts);
    }
}
