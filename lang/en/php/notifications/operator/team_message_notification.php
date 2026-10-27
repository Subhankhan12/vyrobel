<?php

// app/Notifications/Operator/TeamMessageNotification.php — :count is the conversation's unread messages (1 or more)

return [
    'title_direct' => '{1} :name sent you a message|[2,*] :name sent you :count messages',
    'title_group' => '{1} :name wrote in :group|[2,*] :count new messages in :group',
    'context' => 'Team chat',
];
