<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Models\Review;
use App\Notifications\FeedNotification;

/**
 * OWNER FIX 2 — a guest review was published on one of THIS owner's units.
 *
 * ⚠️ #28 — THE RATING AND THE UNIT, AND NO REVIEWER. The owner Reviews page shows an abbreviated name
 * ("Sofia M."), but a page is VISITED behind an owner-capability check while a notification is PUSHED and may
 * be mirrored into an inbox by the email channel. Nothing here identifies the guest, and the review BODY is
 * deliberately not echoed either — free text about a stay can name people, and the owner can read it on the
 * page it belongs to.
 */
class OwnerReviewPostedNotification extends FeedNotification
{
    public function __construct(private Review $review) {}

    public function eventKey(): string
    {
        return 'owner_review_posted';
    }

    public function category(): string
    {
        return 'performance';
    }

    public function data(object $notifiable): array
    {
        $unit = $this->review->unit;
        $label = $unit !== null ? (string) ($unit->code ?: $unit->name ?: 'your unit') : 'your unit';
        $rating = (int) $this->review->rating;

        return [
            'title' => $rating.'★ review on '.$label,
            'context' => 'A guest published a '.$rating.'-star review of '.$label.'. Read it on your Reviews page.',
            'url' => route('owner.reviews', [], false),
            'category' => 'performance',
        ];
    }
}
