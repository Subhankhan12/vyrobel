<?php

// app/Livewire/Operator/GuestProfile.php — the operator back office's Guest profile component (Guests CRM detail).

return [
    // the Latest review card's status pill, shown when the review is not published
    'review_pending' => 'Pending',
    'review_rejected' => 'Rejected',
    // the page title when the guest has no name
    'anonymized_guest' => 'Anonymized guest',
    'unnamed_guest' => 'Unnamed guest',
    // one part of the source mix, e.g. "2 Direct" (the parts are joined with " · ")
    'source_mix_item' => ':number :source',
    // a note whose author's account no longer exists
    'former_staff_member' => 'Former staff member',
];
