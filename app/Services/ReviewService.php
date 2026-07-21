<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Guest\ReviewNotAllowedException;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Review;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonInterface;

/**
 * The single write path for reviews (Phase 5). A review is only ever created for a REAL completed stay, exactly
 * once. The caller resolves the reservation through the authed guest (isolation); this service re-guards the two
 * invariants — completed + not-already-reviewed — BEFORE any write, so the rule holds no matter who calls.
 *
 * A submitted review is `pending` (never auto-public); moderation (publish/reject) is SG2. The domain event
 * `review.submitted` carries NO guest PII (rating + visibility only) and is what SG3's new-review notification
 * consumes.
 */
class ReviewService
{
    public function __construct(private DomainEventRecorder $domainEvents, private AuditEventRecorder $audit) {}

    /** The existing review for a stay, or null (one review per stay). */
    public function reviewFor(Reservation $reservation): ?Review
    {
        return Review::query()->where('reservation_id', $reservation->getKey())->first();
    }

    /** A stay is reviewable once it is a completed (PAST) stay — checked out, or a confirmed stay now past. */
    public function isCompleted(Reservation $reservation, ?CarbonInterface $today = null): bool
    {
        return ReservationStage::for($reservation, $today) === ReservationStage::PAST;
    }

    /**
     * Submit a guest's review of their OWN completed stay. Guards: the stay is completed AND not already reviewed.
     *
     * @param  array{rating: int, tags?: array<int, string>, body?: ?string, private?: bool}  $data
     */
    public function submit(Guest $guest, Reservation $reservation, array $data): Review
    {
        if (! $this->isCompleted($reservation)) {
            throw new ReviewNotAllowedException('Only a completed stay can be reviewed.');
        }
        if ($this->reviewFor($reservation) !== null) {
            throw new ReviewNotAllowedException('This stay has already been reviewed.');
        }

        $unit = $reservation->reservationUnits->first()?->unit;

        $review = Review::create([
            'guest_id' => $guest->getKey(),
            'reservation_id' => $reservation->getKey(),
            'unit_id' => $unit?->getKey(),
            'room_type_id' => $unit?->room_type_id,
            'rating' => max(1, min(5, (int) $data['rating'])),
            'tags' => array_values(array_map('strval', $data['tags'] ?? [])),
            'body' => isset($data['body']) && trim((string) $data['body']) !== '' ? trim((string) $data['body']) : null,
            'visibility' => ($data['private'] ?? false) ? Review::VISIBILITY_PRIVATE : Review::VISIBILITY_PUBLIC,
            'status' => Review::STATUS_PENDING,
        ]);

        // No PII in the payload — rating + visibility only (never the body or the guest's name).
        $this->domainEvents->record('review.submitted', $review, ['rating' => $review->rating, 'visibility' => $review->visibility]);
        $this->audit->record('review.submitted', $review, null, ['rating' => $review->rating, 'visibility' => $review->visibility]);

        return $review;
    }
}
