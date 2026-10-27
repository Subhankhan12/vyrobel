<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Organization;
use App\Models\RateProposal;
use App\Models\Review;
use App\Models\Scopes\OwnerScope;
use App\Services\OwnerPortalService;
use App\Support\Reviews\OwnerReviewSummary;
use App\Support\Reviews\ReviewList;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Reviews (Phase 5 · SG4 → exact-match rebuild) — the owner READS the published reviews for THEIR OWN units
 * + the REAL reputation aggregates (overall rating, response rate, last-30, by-villa, rating breakdown), plus the
 * operator's public RESPONSES. Read-only by design: the operator owns the response (SG3, `reviews.respond`,
 * `can:reviews.manage`, audited #20) — the owner sees but does not reply.
 *
 * 🔒 OWNER ISOLATION (#28): the units come from the owner-scoped {@see OwnerPortalService::units()} (the
 * {@see OwnerScope}), so every aggregate + review is filtered to the owner's OWN unit ids —
 * another owner's unit reviews (a different unit_id) can never appear. Guest byline is first-name + initial only.
 * Every figure is DERIVED from real published+public reviews ({@see OwnerReviewSummary}/{@see ReviewList}) — never
 * a stored/fabricated number (#39). The Value observation links the REAL Wave-C rate-proposals (`owner.rates`).
 */
#[Layout('components.layouts.owner')]
class OwnerReviews extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $unitIds = app(OwnerPortalService::class)->units()->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $summary = OwnerReviewSummary::forUnits($unitIds);
        $softest = collect($summary['categories'])->firstWhere('softest', true);

        return view('livewire.owner.reviews', [
            'viewer' => $this->viewer(),
            'summary' => $summary,
            // The category labels the owner reads, keyed by the category CODE (Review::CATEGORIES' keys).
            'categoryLabels' => $this->categoryLabels(),
            'softestKey' => is_array($softest) ? (string) $softest['key'] : null,
            'reviews' => ReviewList::forUnits($unitIds),
            // The managing operator/agency name (the real org) — "{Name} replies for you". Honest fallback.
            'agency' => (string) (Organization::query()->value('name') ?: __('php/livewire/owner/owner_reviews.agency_fallback')),
            // Real Wave-C rate-proposals awaiting the owner (the Value → rate-changes link is REAL, not a refusal).
            'pendingProposals' => RateProposal::query()->where('state', 'pending')->count(),
        ]);
    }

    /**
     * Review category code → its label in the reader's language. Keyed by the code, so a label that translates
     * the same as another can never merge two categories.
     *
     * @return array<string, string>
     */
    private function categoryLabels(): array
    {
        return Review::categoryLabels();   // the ONE definition, on the model
    }
}
