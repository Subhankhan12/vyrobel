<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Services\OwnerPortalService;
use App\Support\Reviews\ReviewList;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Reviews (Phase 5 · Sub-gate 4) — the owner READS the published reviews for THEIR OWN units (guest byline,
 * rating, body, and the operator's response). Read-only by design: the operator owns the response (SG3), so the
 * owner sees but does not reply.
 *
 * 🔒 OWNER ISOLATION: the units come from the owner-scoped {@see OwnerPortalService::units()} (the {@see
 * \App\Models\Scopes\OwnerScope} global scope, set by SetOwnerScopeForPortal from the authed user's owner links) —
 * so the reviews are filtered to the owner's OWN unit ids ({@see ReviewList::forUnits()}). Another owner's unit
 * reviews (a different unit_id) can never appear. Only PUBLISHED reviews (a pending/private one is never shown).
 */
#[Layout('components.layouts.owner')]
class OwnerReviews extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $unitIds = app(OwnerPortalService::class)->units()->pluck('id')->map(fn ($id): string => (string) $id)->all();

        return view('livewire.owner.reviews', [
            'viewer' => $this->viewer(),
            'reviews' => ReviewList::forUnits($unitIds),
        ]);
    }
}
