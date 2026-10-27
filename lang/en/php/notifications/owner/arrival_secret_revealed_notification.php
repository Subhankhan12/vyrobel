<?php

// App\Notifications\Owner\ArrivalSecretRevealedNotification — tells the owner THAT arrival details were viewed, never what they are.

return [
    'context_door_code' => ':actor viewed the door code for :unit at :property. If this wasn’t expected, contact your manager.',
    'context_wifi_password' => ':actor viewed the wifi password for :unit at :property. If this wasn’t expected, contact your manager.',
    'context_door_code_wifi' => ':actor viewed the door code and wifi password for :unit at :property. If this wasn’t expected, contact your manager.',
    'context_wifi_door_code' => ':actor viewed the wifi password and door code for :unit at :property. If this wasn’t expected, contact your manager.',
    'context_arrival_details' => ':actor viewed the arrival details for :unit at :property. If this wasn’t expected, contact your manager.',
    'title' => 'Arrival details viewed',
];
