<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Guest\ReviewNotAllowedException;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
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
    /**
     * ⚠️⚠️ R3 (founder, 2026-09-19) — "HAS EVER COMPLETED A STAY", NOT "IS PAST RIGHT NOW".
     *
     * A guest who was emailed a review request must not meet a 404 because staff corrected a mis-clicked
     * checkout. `ReservationStage` puts a re-opened stay in IN_HOUSE — correctly, the guest IS in the room —
     * so a gate reading the stage alone revokes an invitation the product already sent.
     *
     * ⚠️⚠️ THE CHANGE IS SCOPED HERE, NOT IN THE ENUM, AND THAT IS DELIBERATE. `ReservationStage::PAST` is
     * read by the owner bookings tabs, the guest trips tabs and trip labels, and the guest reservation
     * controller; widening the enum would widen all of them and start calling occupied rooms "past".
     * `isCompleted()` has three callers and all three are the review path.
     *
     * ⚠️ IT STILL REFUSES A STAY THAT NEVER COMPLETED — the history is the evidence, and a stay that has
     * never reached `checked_out` has no such row.
     */
    public function isCompleted(Reservation $reservation, ?CarbonInterface $today = null): bool
    {
        if (ReservationStage::for($reservation, $today) === ReservationStage::PAST) {
            return true;
        }

        // ⚠️ THE REOPENED CASE. `reservation_status_history` already records everything a stay has been
        //    through, so the invitation survives the correction without a new column to keep in step.
        return $this->everCheckedOut([$reservation->getKey()]) !== [];
    }

    /**
     * ⚠️⚠️ THE SAME QUESTION, ASKED FOR MANY — AND IT EXISTS SO A LIST SURFACE NEED NOT ASK IT PER ROW.
     *
     * FIX 34 widened "can this be reviewed" to *has this stay ever completed* and scoped it to this service,
     * because widening {@see ReservationStage::PAST} would have started calling occupied rooms "past". The
     * emailed review LINK asked this service; the guest trips CARD went on asking the stage, so a stay whose
     * checkout had been reversed offered a working link and no button (FIX 34 reported that residual).
     *
     * ⚠️ ONE DEFINITION (§N.8f): {@see self::isCompleted()} now DELEGATES here, so the singular and the
     * batch cannot drift — there is one rule, asked two ways, not two computations of one concept.
     *
     * ⚠️ AND IT IS ONE QUERY FOR THE WHOLE SET. A per-card `exists()` would have made the trips list N+1,
     * which is exactly why FIX 34 reported the residual instead of fixing it in passing.
     *
     * @param  list<string>  $reservationIds
     * @return list<string> those that have EVER reached `checked_out`
     */
    public function everCheckedOut(array $reservationIds): array
    {
        if ($reservationIds === []) {
            return [];
        }

        return ReservationStatusHistory::query()
            ->whereIn('reservation_id', $reservationIds)
            ->where('to_status', 'checked_out')
            ->distinct()
            ->pluck('reservation_id')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }

    /**
     * Submit a guest's review of their OWN completed stay. Guards: the stay is completed AND not already reviewed.
     *
     * @param  array{rating: int, categories?: array<string, int|null>, tags?: array<int, string>, body?: ?string, private?: bool}  $data
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

        // Optional per-category sub-ratings (Cleanliness/Communication/Location/Value), each clamped 1–5 or null.
        $categories = [];
        foreach (array_keys(Review::CATEGORIES) as $key) {
            $raw = $data['categories'][$key] ?? null;
            $categories[$key] = $raw !== null ? max(1, min(5, (int) $raw)) : null;
        }

        $review = Review::create([
            'guest_id' => $guest->getKey(),
            'reservation_id' => $reservation->getKey(),
            'unit_id' => $unit?->getKey(),
            'room_type_id' => $unit?->room_type_id,
            'rating' => max(1, min(5, (int) $data['rating'])),
            'tags' => array_values(array_map('strval', $data['tags'] ?? [])),
            ...$categories,
            'body' => isset($data['body']) && trim((string) $data['body']) !== '' ? trim((string) $data['body']) : null,
            'visibility' => ($data['private'] ?? false) ? Review::VISIBILITY_PRIVATE : Review::VISIBILITY_PUBLIC,
            'status' => Review::STATUS_PENDING,
        ]);

        // No PII in the payload — rating + visibility only (never the body or the guest's name).
        $this->domainEvents->record('review.submitted', $review, ['rating' => $review->rating, 'visibility' => $review->visibility]);
        $this->audit->record('review.submitted', $review, null, ['rating' => $review->rating, 'visibility' => $review->visibility]);

        return $review;
    }

    /**
     * MODERATION — publish a PUBLIC review so it becomes publicly visible (Phase 5 · SG2). A `private` review can
     * NEVER be published (the guest's choice is respected). Idempotent. A meaningful state change → events.
     */
    public function publish(Review $review): Review
    {
        if ($review->visibility !== Review::VISIBILITY_PUBLIC) {
            throw new ReviewNotAllowedException('A private review can’t be published.');
        }
        if ($review->status === Review::STATUS_PUBLISHED) {
            return $review;
        }

        $before = (string) $review->status;
        $review->forceFill(['status' => Review::STATUS_PUBLISHED, 'published_at' => now()])->save();

        $this->domainEvents->record('review.published', $review, ['rating' => $review->rating]);
        $this->audit->record('review.published', $review, ['status' => $before], ['status' => Review::STATUS_PUBLISHED]);

        return $review;
    }

    /** The operator's public response to a review (Phase 5 · SG3). Shown with the review where it is published. */
    public function respond(Review $review, string $response): Review
    {
        $review->forceFill(['operator_response' => trim($response), 'responded_at' => now()])->save();

        $this->audit->record('review.responded', $review, null, ['has_response' => true]);

        return $review;
    }

    /** MODERATION — reject a review so it is never publicly visible (unpublishes a published one too). Idempotent. */
    public function reject(Review $review): Review
    {
        if ($review->status === Review::STATUS_REJECTED) {
            return $review;
        }

        $before = (string) $review->status;
        $review->forceFill(['status' => Review::STATUS_REJECTED, 'published_at' => null])->save();

        $this->domainEvents->record('review.rejected', $review, []);
        $this->audit->record('review.rejected', $review, ['status' => $before], ['status' => Review::STATUS_REJECTED]);

        return $review;
    }
}
