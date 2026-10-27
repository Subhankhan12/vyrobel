<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Exceptions\Guest\ReviewNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\ReviewService;
use App\Support\Branding\PropertyBrand;
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
    // ⚠️ I18N-01 — these English values are the stored CODES (validated with Rule::in, saved on the review); the words a
    //    guest reads come from tagLabel(), so a translated label can never fail the validation.
    private const POSITIVE_TAGS = ['Spotless villa', 'Easy check-in', 'Great location', 'Fast replies', 'Comfy beds'];

    private const NEGATIVE_TAGS = ['Cleanliness', 'Check-in hassle', 'Noise', 'Slow replies'];

    public function __construct(private readonly ReviewService $reviews) {}

    public function show(Request $request, string $reservation): View
    {
        $model = $this->owned($reservation);
        abort_unless($this->reviews->isCompleted($model), 404);   // only a completed stay is reviewable

        $existing = $this->reviews->reviewFor($model);

        // Star deep-link from the review-request email: ?rating=N pre-selects that star ("already halfway
        // done"). Bounded 1–5; ignored when a review already exists (their saved rating wins).
        $prefill = (int) $request->query('rating', '0');
        $prefillRating = ($existing === null && $prefill >= 1 && $prefill <= 5) ? $prefill : null;

        return view('guest.review', [
            'reservation' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? __('php/http/controllers/guest/guest_review_controller.your_stay')),
            // #98 — the property's guest-facing brand accent flows to the review page (fail-open to Vyrobel).
            'brandAccent' => PropertyBrand::for($model->property)->accent,
            'stay' => __('php/http/controllers/guest/guest_review_controller.stay_dates', ['from' => CarbonImmutable::parse($model->check_in)->translatedFormat('j M'), 'to' => CarbonImmutable::parse($model->check_out)->translatedFormat('j M Y')]),
            'positiveTags' => self::POSITIVE_TAGS,
            'negativeTags' => self::NEGATIVE_TAGS,
            // code => the label the guest reads (the chip's text; its value stays the code)
            'tagLabels' => array_combine([...self::POSITIVE_TAGS, ...self::NEGATIVE_TAGS], array_map(fn (string $code): string => $this->tagLabel($code), [...self::POSITIVE_TAGS, ...self::NEGATIVE_TAGS])),
            'prefillRating' => $prefillRating,
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
            'rating_cleanliness' => ['nullable', 'integer', 'between:1,5'],
            'rating_communication' => ['nullable', 'integer', 'between:1,5'],
            'rating_location' => ['nullable', 'integer', 'between:1,5'],
            'rating_value' => ['nullable', 'integer', 'between:1,5'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', Rule::in($allTags)],
            'body' => ['nullable', 'string', 'max:5000'],
            'private' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->reviews->submit($this->guest(), $model, [
                'rating' => (int) $data['rating'],
                'categories' => array_filter([
                    'rating_cleanliness' => $data['rating_cleanliness'] ?? null,
                    'rating_communication' => $data['rating_communication'] ?? null,
                    'rating_location' => $data['rating_location'] ?? null,
                    'rating_value' => $data['rating_value'] ?? null,
                ], fn ($v): bool => $v !== null),
                'tags' => $data['tags'] ?? [],
                'body' => $data['body'] ?? null,
                'private' => $request->boolean('private'),
            ]);
        } catch (ReviewNotAllowedException $e) {
            return redirect()->route('guest.review', $model->getKey())->with('error', $e->getMessage());
        }

        return redirect()->route('guest.trips')->with('status', __('php/http/controllers/guest/guest_review_controller.thanks'));
    }

    /** The words for a review-tag code. */
    private function tagLabel(string $code): string
    {
        return match ($code) {
            'Spotless villa' => __('php/http/controllers/guest/guest_review_controller.tag_spotless_villa'),
            'Easy check-in' => __('php/http/controllers/guest/guest_review_controller.tag_easy_check_in'),
            'Great location' => __('php/http/controllers/guest/guest_review_controller.tag_great_location'),
            'Fast replies' => __('php/http/controllers/guest/guest_review_controller.tag_fast_replies'),
            'Comfy beds' => __('php/http/controllers/guest/guest_review_controller.tag_comfy_beds'),
            'Cleanliness' => __('php/http/controllers/guest/guest_review_controller.tag_cleanliness'),
            'Check-in hassle' => __('php/http/controllers/guest/guest_review_controller.tag_check_in_hassle'),
            'Noise' => __('php/http/controllers/guest/guest_review_controller.tag_noise'),
            'Slow replies' => __('php/http/controllers/guest/guest_review_controller.tag_slow_replies'),
            default => $code,
        };
    }

    /** The authed guest's OWN reservation, or 404 — resolved through their own relation (never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->with(['property', 'reservationUnits.unit'])->findOrFail($reservation);
    }
}
