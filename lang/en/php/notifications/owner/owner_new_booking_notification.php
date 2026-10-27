<?php

// App\Notifications\Owner\OwnerNewBookingNotification — a booking confirmed on one of the owner's units (no guest identity).

return [
    'context_nights' => '{1} :unit · :check_in – :check_out · :count night · :reference|[0,*] :unit · :check_in – :check_out · :count nights · :reference',
    'context' => ':unit · :check_in – :check_out · :reference',
    'context_your_unit_nights' => '{1} Your unit · :check_in – :check_out · :count night · :reference|[0,*] Your unit · :check_in – :check_out · :count nights · :reference',
    'context_your_unit' => 'Your unit · :check_in – :check_out · :reference',
    'title' => 'New booking on :unit',
    'title_your_unit' => 'New booking on Your unit',
];
