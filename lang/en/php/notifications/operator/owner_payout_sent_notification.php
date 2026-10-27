<?php

// app/Notifications/Operator/OwnerPayoutSentNotification.php

return [
    'title' => 'Owner payout sent',
    'context' => ':amount disbursed to :owner',
    // stands in for the owner's name when there is none
    'context_no_owner' => ':amount disbursed to owner',
];
