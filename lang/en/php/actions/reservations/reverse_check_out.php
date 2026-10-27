<?php

// app/Actions/Reservations/ReverseCheckOut.php — the R3 disclosure the confirmation screen prints, and its refusal.

return [
    'reversible_status' => 'The stay returns to checked in.',
    'pending_tasks_removed' => '{1} :count departure cleaning task will be removed — not started yet.|[0,*] :count departure cleaning tasks will be removed — not started yet.',
    'started_tasks_kept' => '{1} :count cleaning task has already been started and will be left alone — cancelling work someone has begun is a decision for housekeeping, not an undo.|[0,*] :count cleaning tasks have already been started and will be left alone — cancelling work someone has begun is a decision for housekeeping, not an undo.',
    'unit_stays_dirty' => 'The unit stays marked dirty. The guest is back in the room, so "clean" may not be true.',
    'notifications_stay_delivered' => 'Notifications already delivered to staff and the guest stay delivered.',
    'review_unknown' => 'We have no record of whether a review request was sent for this stay, because it checked out before this product started recording them. It may or may not have been sent.',
    'review_sent_on' => 'A review request was emailed to the guest on :date. It cannot be recalled.',
    'review_sent' => 'A review request was emailed to the guest. It cannot be recalled.',
    'review_suppressed_by_operator' => 'No review request was sent — this property has the review-request email switched off.',
    'review_suppressed_by_guest' => 'No review request was sent — the guest has asked not to receive review emails.',
    'review_not_sent' => 'No review request was sent — there was no contact address, or the send failed.',
    // :status is the raw status code, as before
    'refused_not_checked_out' => 'Only a checked-out stay can have its checkout reversed; this one is :status.',
];
