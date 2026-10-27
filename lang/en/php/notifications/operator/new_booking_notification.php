<?php

// app/Notifications/Operator/NewBookingNotification.php — :source is the booking's source code, capitalised.

return [
    'title' => 'New booking · :reference',
    'context' => ':source · :check_in – :check_out',
];
