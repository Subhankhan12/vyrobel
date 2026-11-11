<?php

// app/Models/FolioItem.php — the sentences the system writes into a folio line's description (D-I18N-STORED-RECORDS, 2026-11-11).
// The values ARE the English the code writes, with `:name` where something was filled in. A line the system did NOT write (an add-on, a tax, a fee
// or a charge as an operator named it, a demo seeder's) matches none of them and shows exactly as stored.
return [
    // a booking made on the desk or the Calendar: "Room · Double Room"
    'room_type' => 'Room · :name',
    // one bill for a group: "Room · Double Room · R-26-0042" — the room and the booking it is for
    'room_type_reference' => 'Room · :name · :reference',
    // a booking made on the public booking page: "Room (R-26-0042)"
    'room_reference' => 'Room (:reference)',
    // a group's discount: "Group discount (10%)"
    'group_discount' => 'Group discount (:percent%)',
    // a guest's wallet credit used against the bill
    'wallet_credit' => 'Wallet credit applied',
];
