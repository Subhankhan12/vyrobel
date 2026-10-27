<?php

// app/Mail/AccountDeletionScheduledMail.php — "Your account deletion is scheduled" (to the guest).

return [
    'subject' => 'Your account deletion is scheduled',
    'heading' => 'Your account deletion is scheduled',
    // :count is the grace period in days
    'intro' => 'We’ve scheduled your Vyrobel guest account for deletion. You have :count days to change your mind — nothing is removed until then.',
    // added after the intro only when the erase will cancel bookings (:count ≥ 1)
    'cancel_note' => '{1} When it runs we will also cancel the :count booking you still have with us, including a stay already under way. Whether any money comes back follows each booking’s cancellation policy, so some or all of it may not be refunded.|[2,*] When it runs we will also cancel the :count bookings you still have with us, including a stay already under way. Whether any money comes back follows each booking’s cancellation policy, so some or all of it may not be refunded.',
];
