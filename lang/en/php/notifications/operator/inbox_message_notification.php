<?php

// app/Notifications/Operator/InboxMessageNotification.php — :count is the conversation's unread messages (1 or more)

return [
    'title' => '{1} New message from :name|[2,*] :count new messages from :name',
    'context_owner' => 'Owner conversation',
    'context_guest' => 'Guest conversation',
];
