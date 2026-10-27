<?php

// app/Notifications/Operator/StatementFinalizedNotification.php

return [
    'title' => 'Statement finalized · :owner',
    // when the owner has no name
    'title_no_owner' => 'Statement finalized · owner',
    'context' => 'Net payable :amount',
];
