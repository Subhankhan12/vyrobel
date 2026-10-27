<?php

// resources/views/operator/reviews.blade.php — operator reviews: see, respond, publish or reject (review text and guest names are people's content).

return [
    'title' => 'Reviews',
    'intro' => 'Respond to your guests and choose what’s published. Auto-responses and review-gating are coming soon — for now, every response and publish decision is yours.',
    'overall_rating' => 'Overall rating',
    'published_reviews' => '{1} :count published review|[0,*] :count published reviews',
    'reply_rate' => 'Reply rate',
    'answered' => ':responded of :total answered',
    'answered_with_pending' => ':responded of :total answered · :pending awaiting review',
    'rating_breakdown' => 'Rating breakdown',
    // a review's state: the filter tabs and the badge on each review
    'status_published' => 'Published',
    'status_pending' => 'Pending',
    'status_rejected' => 'Rejected',
    'status_private' => 'Private',
    'rating_of_five' => ':rating/5',
    'your_response' => 'Your response:',
    'response_placeholder' => 'Write a response…',
    'respond' => 'Respond',
    'publish' => 'Publish',
    'reject' => 'Reject',
    'empty_state' => 'No reviews yet.',
];
