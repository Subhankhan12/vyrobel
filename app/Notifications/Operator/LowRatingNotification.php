<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Review;
use App\Notifications\FeedNotification;

/**
 * Operator: a guest left a LOW-rated review — under 4★ (#90) — dispatched on the REAL `review.submitted` domain
 * event, but ONLY when the real rating is below 4 (the dispatcher branches on the actual rating; this is never a
 * fabricated signal). Preference-gated on the `low_rating` catalog key. A separate, more-urgent alert than
 * `new_review` so a manager can react to a poor stay quickly. No guest PII — a rating + a neutral line only.
 */
class LowRatingNotification extends FeedNotification
{
    public function __construct(private Review $review) {}

    public function eventKey(): string
    {
        return 'low_rating';
    }

    public function category(): string
    {
        return 'reputation';
    }

    public function data(object $notifiable): array
    {
        $stars = str_repeat('★', (int) $this->review->rating).str_repeat('☆', max(0, 5 - (int) $this->review->rating));

        return [
            'title' => 'Low rating · '.$stars,
            'context' => 'A guest rated their stay under 4 stars',
            'url' => route('app.reviews', [], false),
            'category' => 'reputation',
        ];
    }
}
