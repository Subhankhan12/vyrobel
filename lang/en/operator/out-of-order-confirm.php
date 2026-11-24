<?php

// resources/views/operator/out-of-order-confirm.blade.php — shown BEFORE a work order is saved, when taking its unit out of order would
// leave bookings with no room (VY-03). Nothing has been saved when this page shows.

return [
    'page_title' => 'Take the room out of order?',
    'heading' => 'Bookings need this room',
    'peak_line' => '{1} :count booking would be left without a room|[0,*] :count bookings would be left without a room on the same night',
    // the room type and the date stay inside the sentence so each language places them where its grammar needs them
    'nights_line' => '{1} On :count night, “:room_type” would have more bookings than rooms: <strong>:date</strong>.|[0,*] On :count nights, “:room_type” would have more bookings than rooms. The first is <strong>:date</strong>.',
    'nothing_saved' => 'Nothing has been saved. Move or cancel those bookings first — or take the room out of order now and move them after. The calendar marks every overbooked night.',
    'confirm' => 'Take it out of order anyway',
];
