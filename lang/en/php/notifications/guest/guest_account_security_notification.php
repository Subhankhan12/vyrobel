<?php

// app/Notifications/Guest/GuestAccountSecurityNotification.php — the guest's account-deletion row in the bell.

return [
    'title_scheduled' => 'Account deletion scheduled',
    'title_cancelled' => 'Account deletion cancelled',
    'context_scheduled' => 'Your account is scheduled for deletion — it will also cancel any bookings you still have with us. Open your account to see the date or undo it',
    'context_cancelled' => 'Your account will no longer be deleted',
];
