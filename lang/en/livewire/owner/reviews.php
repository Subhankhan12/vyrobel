<?php

// resources/views/livewire/owner/reviews.blade.php — the owner Reviews & reputation page (read-only).

return [
    'title' => 'Reviews & reputation',
    'subtitle' => 'What guests are saying across your units — :agency replies for you.',
    'empty_state' => 'No reviews yet for your units. As guests review their completed stays, your rating and your team’s replies appear here.',
    // hero KPIs
    'overall_rating' => 'Overall rating',
    'reviews_all_villas' => '{1} :count review · all units|[0,*] :count reviews · all units',
    'response_rate' => 'Response rate',
    'replies_within' => '{1} replies within ~:count hour|[0,*] replies within ~:count hours',
    'replied_of' => ':responded of :total replied',
    'last_30_days' => 'Last 30 days',
    'last_30_avg' => 'avg :rating',
    'new_reviews' => '{1} new review|[0,*] new reviews',
    // by villa
    'by_villa' => 'By unit',
    'villa_reviews' => '{1} :count review|[0,*] :count reviews',
    'trend_steady' => '— steady',
    // recent reviews
    'recent_reviews' => 'Recent reviews',
    // the Alpine counter stays inside the sentence so each language places it where its grammar needs it
    'shown_newest_first' => '<span x-text="shown"></span> shown · newest first',
    'all_villas' => 'All units',
    'stars_aria' => ':rating out of 5 stars',
    'agency_replied' => ':agency replied',
    'awaiting_reply' => 'Awaiting a reply from your team',
    // right rail
    'rating_breakdown' => 'Rating breakdown',
    'by_category' => 'By category',
    'softest_score' => ':category is your softest score. Your rates are yours to set — <a href=":url" style="color:var(--vy-accent);text-decoration:none;font-weight:500">open your Rates</a> if you want to act on it.',
    'handled_for_you' => 'Handled for you',
    'handled_body' => ':agency replies to your published reviews — :response_rate% responded. Direct-stay reviews appear here automatically; Airbnb & Booking.com review sync activates once those channels are connected.',
    'handled_body_hours' => '{1} :agency replies to your published reviews — :response_rate% responded, on average within ~:count hour. Direct-stay reviews appear here automatically; Airbnb & Booking.com review sync activates once those channels are connected.|[0,*] :agency replies to your published reviews — :response_rate% responded, on average within ~:count hours. Direct-stay reviews appear here automatically; Airbnb & Booking.com review sync activates once those channels are connected.',
];
