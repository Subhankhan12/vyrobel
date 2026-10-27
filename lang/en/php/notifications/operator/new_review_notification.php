<?php

// app/Notifications/Operator/NewReviewNotification.php — :stars is the rating drawn in ★/☆.

return [
    'title' => 'New review · :stars',
    'context_private' => 'Private feedback to the manager',
    'context_awaiting' => 'Awaiting moderation',
];
