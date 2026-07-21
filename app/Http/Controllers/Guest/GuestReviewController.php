<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Exceptions\Guest\ReviewNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\ReviewService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guest Review (Phase 5 · Sub-gate 1) — a guest reviews a REAL completed stay.
 *
 * 🔒 TIED TO A REAL COMPLETED STAY: the {reservation} resolves THROUGH `$this->guest()->reservations()` (the guest's
 * own — a foreign id → 404), and the review action is available ONLY for a completed (PAST) stay (else 404). One
 * review per stay ({@see ReviewService}). A guest can never review a future/cancelled/foreign/not-their stay.
 *
 * A public review is submitted `pending` (moderation is SG2 — never auto-public); "send privately to the manager"
 * sets `visibility = private` (operator feedback, never published).
 */
class GuestReviewController extends Controller
{
    use ResolvesGuest;

    /** The design's tag chips. */
    private const POSITIVE_TAGS = ['Spotless villa', 'Easy check-in', 'Great location', 'Fast replies', 'Comfy beds'];

    private const NEGATIVE_TAGS = ['Cleanliness', 'Check-in hassle', 'Noise', 'Slow replies'];

    public function __construct(private readonly ReviewService $reviews) {}

    public function show(string $reservation): View
    {
        $model = $this->owned($reservation);
        abort_unless($this->reviews->isCompleted($model), 404);   // only a completed stay is reviewable

        $existing = $this->reviews->reviewFor($model);

        return view('guest.review', [
            'reservation' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? 'Your stay'),
            'stay' => CarbonImmutable::parse($model->check_in)->format('j M').' – '.CarbonImmutable::parse($model->check_out)->format('j M Y'),
            'positiveTags' => self::POSITIVE_TAGS,
            'negativeTags' => self::NEGATIVE_TAGS,
            'existing' => $existing === null ? null : [
                'rating' => (int) $existing->rating,
                'tags' => $existing->tags ?? [],
                'body' => (string) $existing->body,
                'private' => $existing->visibility === Review::VISIBILITY_PRIVATE,
                'status' => (string) $existing->status,
            ],
        ]);
    }

    public function submit(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);
        abort_unless($this->reviews->isCompleted($model), 404);   // the tie-to-completed-stay guard (matches show())
        $allTags = [...self::POSITIVE_TAGS, ...self::NEGATIVE_TAGS];
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', Rule::in($allTags)],
            'body' => ['nullable', 'string', 'max:5000'],
            'private' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->reviews->submit($this->guest(), $model, [
                'rating' => (int) $data['rating'],
                'tags' => $data['tags'] ?? [],
                'body' => $data['body'] ?? null,
                'private' => $request->boolean('private'),
            ]);
        } catch (ReviewNotAllowedException $e) {
            return redirect()->route('guest.review', $model->getKey())->with('error', $e->getMessage());
        }

        return redirect()->route('guest.trips')->with('status', 'Thanks for your review — we appreciate it.');
    }

    /** The authed guest's OWN reservation, or 404 — resolved through their own relation (never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->with(['property', 'reservationUnits.unit'])->findOrFail($reservation);
    }
}
