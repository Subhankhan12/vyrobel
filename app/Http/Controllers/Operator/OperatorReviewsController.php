<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Guest\ReviewNotAllowedException;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use App\Support\Guest\GuestName;
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

    public function index(): Response
    {
        $reviews = Review::query()
            ->with(['guest', 'roomType'])
            ->latest()
            ->get()
            ->map(fn (Review $r): array => [
                'id' => (string) $r->getKey(),
                'guest' => GuestName::display($r->guest),   // NAME only — no contact PII (#28)
                'rating' => (int) $r->rating,
                'stars' => str_repeat('★', (int) $r->rating).str_repeat('☆', 5 - (int) $r->rating),
                'tags' => $r->tags ?? [],
                'body' => (string) $r->body,
                'room' => (string) ($r->roomType->name ?? ''),
                'private' => $r->visibility === Review::VISIBILITY_PRIVATE,
                'status' => (string) $r->status,
                'response' => (string) $r->operator_response,
                'at' => CarbonImmutable::parse($r->created_at)->format('j M Y'),
            ]);

        return response()->view('operator.reviews', ['reviews' => $reviews->all()]);
    }

    public function respond(Request $request, string $review): RedirectResponse
    {
        $data = $request->validate(['response' => ['required', 'string', 'max:2000']]);
        $this->reviews->respond($this->owned($review), (string) $data['response']);

        return back()->with('status', 'Your response has been saved.');
    }

    public function publish(string $review): RedirectResponse
    {
        try {
            $this->reviews->publish($this->owned($review));
        } catch (ReviewNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Review published.');
    }

    public function reject(string $review): RedirectResponse
    {
        $this->reviews->reject($this->owned($review));

        return back()->with('status', 'Review rejected — it won’t be shown publicly.');
    }

    /** This org's review, or 404 — resolved in-action so the tenant scope is set (cross-org → not found). */
    private function owned(string $review): Review
    {
        return Review::query()->findOrFail($review);
    }
}
