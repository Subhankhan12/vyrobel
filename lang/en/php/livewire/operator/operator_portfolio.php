<?php

// app/Livewire/Operator/OperatorPortfolio.php — the operator Portfolio component: the type, status and channel labels on each property card.

return [
    // the property's channel status, read from its real channels
    'sync_no_channels' => 'No channels connected',
    'sync_needs_attention' => 'Sync needs attention',
    'sync_connected' => 'Channels connected',
    'sync_disconnected' => 'Channels disconnected',
    // the property's status
    'status_inactive' => 'Inactive',
    'status_finish_setup' => 'Finish setup to go live',
    'status_active' => 'Active',
    // the property types (also the type tiles in the Add and Edit dialogs)
    'type_hotel' => 'Hotel',
    'type_aparthotel' => 'Aparthotel',
    'type_serviced_apartment' => 'Serviced apartment',
    'type_apartment_building' => 'Apartment building',
    'type_apartment' => 'Apartment',
    'type_villa' => 'Villas & apartments',
    'type_hostel' => 'Hostel',
    'type_guest_house' => 'Guesthouse / lodge',
    'type_resort' => 'Resort',
    'type_mixed' => 'Mixed',
    'type_portfolio' => 'Portfolio',
];
