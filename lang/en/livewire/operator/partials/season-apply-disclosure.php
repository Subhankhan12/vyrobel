<?php

// resources/views/livewire/operator/partials/season-apply-disclosure.blade.php — what applying a season writes, what cannot be
// undone and what it replaces that somebody negotiated (ruling R-SEASON / R-RATE: it tells; it does not ask). Translate the
// meaning exactly: never soften "cannot be undone" or "the owner is not told". The :placeholders are filled in on the page.

return [
    // :units is the "units" phrase below; :to is shown in bold. The English always said "nights", even for 1.
    'will_overwrite' => ':season will overwrite :changing of :nights nights across :units, from :from to :to a night.',
    'units' => '{1} :count unit|[0,*] :count units',
    'cannot_undo' => 'The prices it replaces are not stored anywhere. <strong>This cannot be undone.</strong>',
    // :agreed is the number of nights (in bold), :carry the phrase below, :owners the phrase after it
    'agreed_callout' => ':agreed :carry a rate negotiated with :owners (:from to :to). Applying replaces those prices, and the owner is not told.',
    'agreed_carry' => '{1} of those nights carries|[0,*] of those nights carry',
    'agreed_owners' => '{1} an owner|[0,*] :count owners',
    'losing_nights' => ':nights of those nights are governed by :season, which wins under the rule and will be written last.',
    'rewritten_nights' => 'Applying also re-writes :nights nights from overlapping seasons that are already applied.',
    'count_scope' => 'Owner-agreed nights are counted only when agreed through a proposal, and only while the price still matches what was agreed. A rate an owner set themselves, or one that has since moved, is not counted here.',
    'booked_keep_price' => 'Stays already booked keep the price they were booked at — this changes what is offered from now on.',
];
