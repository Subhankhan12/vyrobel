<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Guest\ReviewNotAllowedException;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
use App\Support\Reviews\ReviewRating;
use App\Support\Reviews\ReviewTag;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Operator Reviews (Phase 5 · Sub-gate 3) — the operator sees their org's reviews (public + private-to-manager
 * feedback), RESPONDS, and MODERATES (publish/reject, the SG2 state machine). Tenant-scoped: the {review} binding
 * is a STRING id resolved IN the action via the org-scoped `Review::findOrFail` (the established operator pattern,
 * never route-model binding — cross-org → 404). Behind the back-office gate + `can:reviews.view` /
 * `can:reviews.manage` (a reputation task, NOT Front Desk). Guest NAME only (#28).
 *
 * ⚠️ Response-AUTOMATION and review-GATING (steering only happy guests to public) are DEFERRED honestly — the
 * screen offers the real MANUAL response + moderation. Deceptive review-gating is deliberately NOT built; the
 * honest equivalent is the guest's own "send privately to the manager" choice from SG1.
 */
class OperatorReviewsController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function index(Request $request): Response
    {
        // #104 GATE 2 — a real status filter (All / Published / Pending / Rejected), driven by the real
        // `status` column. An unknown value falls through to "all" (no silent empty).
        $filter = (string) $request->query('filter', 'all');
        $statusFor = [
            'published' => Review::STATUS_PUBLISHED,
            'pending' => Review::STATUS_PENDING,
            'rejected' => Review::STATUS_REJECTED,
        ][$filter] ?? null;

        $reviews = Review::query()
            ->with(['guest', 'roomType'])
            ->when($statusFor !== null, fn ($q) => $q->where('status', $statusFor))
            ->latest()
            ->get()
            ->map(fn (Review $r): array => [
                'id' => (string) $r->getKey(),
                'guest' => GuestName::display($r->guest),   // NAME only — no contact PII (#28)
                'rating' => (int) $r->rating,
                'stars' => str_repeat('★', (int) $r->rating).str_repeat('☆', 5 - (int) $r->rating),
                'tags' => ReviewTag::labels($r->tags ?? []),
                'body' => (string) $r->body,
                'room' => (string) ($r->roomType->name ?? ''),
                'private' => $r->visibility === Review::VISIBILITY_PRIVATE,
                'status' => (string) $r->status,
                'response' => (string) $r->operator_response,
                'at' => CarbonImmutable::parse($r->created_at)->translatedFormat(LocalDate::pattern('j M Y')),
            ]);

        return response()->view('operator.reviews', [
            'reviews' => $reviews->all(),
            'summary' => ReviewRating::operatorSummary(),
            'filter' => $filter,
        ]);
    }

    public function respond(Request $request, string $review): RedirectResponse
    {
        $data = $request->validate(['response' => ['required', 'string', 'max:2000']]);
        $this->reviews->respond($this->owned($review), (string) $data['response']);

        return back()->with('status', __('php/http/controllers/operator/operator_reviews_controller.response_saved'));
    }

    public function publish(string $review): RedirectResponse
    {
        try {
            $this->reviews->publish($this->owned($review));
        } catch (ReviewNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('php/http/controllers/operator/operator_reviews_controller.review_published'));
    }

    public function reject(string $review): RedirectResponse
    {
        $this->reviews->reject($this->owned($review));

        return back()->with('status', __('php/http/controllers/operator/operator_reviews_controller.review_rejected'));
    }

    /** This org's review, or 404 — resolved in-action so the tenant scope is set (cross-org → not found). */
    private function owned(string $review): Review
    {
        return Review::query()->findOrFail($review);
    }
}
