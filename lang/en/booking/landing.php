<?php

// resources/views/booking/landing.blade.php — the multi-property storefront landing for one organisation.

return [
    'page_title' => 'Stays',
    'book_direct' => 'Book direct',
    'nav_stays' => 'Stays',
    'my_account' => 'My account',
    'sign_in' => 'Sign in',
    'rating_across' => '{1} :average across :count review|[0,*] :average across :count reviews',
    // printed raw (it carries <br>); :org is escaped where it is passed in
    'heading' => 'Book direct with<br>:org.',
    'subheading' => 'Hand-kept stays, booked straight with the host — no fees, our best rate, and a real person on the other end.',
    'where' => 'Where',
    'check_in' => 'Check in',
    'check_out' => 'Check out',
    'adults' => 'Adults',
    'children' => 'Children',
    'where_to_stay' => 'Where to stay',
    // one town reads "Our home base."; any other number reads ":count towns, …"
    'towns_lede' => '{1} Our home base.|[0,*] :count towns, each with its own pace.',
    'from_price' => 'from :price',
    'featured_stays' => 'Featured stays',
    'featured_lede' => 'Guest favourites across the collection.',
    'per_night' => '/ night',
    'see_dates_for_pricing' => 'See dates for pricing',
    'browse_by_what_matters' => 'Browse by what matters',
    'why_book_direct' => 'Why book direct',
    'recent_guests' => 'Recent guests',
    'rating_short' => '{1} :average · :count review|[0,*] :average · :count reviews',
    'stars_aria' => ':rating out of 5 stars',
    'review_quote' => '“:body”',
    'no_reviews_yet' => 'No reviews yet — be the first to stay and share your experience.',
    'cta_heading' => 'Find your place',
    'cta_sub' => 'Real dates, live prices, instant confirmation.',
    'browse_all_stays' => 'Browse all stays',
];
