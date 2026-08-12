<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Review;
use App\Notifications\FeedNotification;

/**
 * Operator: a guest left a review (Phase 5) — dispatched on the REAL `review.submitted` domain event to the org's
 * operator staff. Preference-gated on the `new_review` catalog key (built in Phase 3, its trigger deferred until
 * now). Reputation category; links to the operator Reviews screen. No guest PII — a rating + a neutral line only.
 */
class NewReviewNotification extends FeedNotification
{
    public function __construct(private Review $review) {}

    public function eventKey(): string
    {
        return 'new_review';
    }

    public function category(): string
    {
        return 'reputation';
    }

    public function data(object $notifiable): array
    {
        // DO10-4 capstone: this printed the filled half ONLY, so a 3-star review read "New review · ★★★"
        // with no denominator — ambiguous between 3/5 and 3/3. Its sibling LowRatingNotification already
        // built both halves; matched to it, so the feed states the rating unambiguously and by GLYPH.
        $rating = (int) $this->review->rating;
        $stars = str_repeat('★', $rating).str_repeat('☆', max(0, 5 - $rating));
        $context = $this->review->visibility === Review::VISIBILITY_PRIVATE
            ? 'Private feedback to the manager'
            : 'Awaiting moderation';

        return [
            'title' => 'New review · '.$stars,
            'context' => $context,
            'url' => route('app.reviews', [], false),
            'category' => 'reputation',
        ];
    }
}
