<?php

// app/Http/Controllers/Operator/InventoryActionController.php — add, edit, pause and delete room types, units and rate plans; arrival info.

return [
    'room_type_added' => ':name added. Next: add its units — the real rooms guests stay in — then give it prices in Rates.',
    // :codes is the list of unit codes, e.g. "101–110, 120"
    'room_type_added_with_units' => '{1} :name added with :count unit: :codes. Next: give it prices in Rates, and it can be booked.|[0,*] :name added with :count units: :codes. Next: give it prices in Rates, and it can be booked.',
    'unit_added_bookable' => ':code added to :room_type. It can be booked now.',
    'unit_added_needs_prices' => ':code added to :room_type. It can be booked once :room_type has prices — set them in Rates.',
    'units_added_bookable' => ':count units added to :room_type: :codes. They can be booked now.',
    'units_added_needs_prices' => ':count units added to :room_type: :codes. They can be booked once :room_type has prices — set them in Rates.',
    // :blockers is a list such as "2 bookings and 1 cleaning task"
    'unit_delete_blocked_active' => 'Unit :code can’t be deleted — it has :blockers on record. Pause it instead: it stops taking new bookings and keeps its history.',
    'unit_delete_blocked_paused' => 'Unit :code can’t be deleted — it has :blockers on record. It is already paused, so it takes no new bookings, and its history stays.',
    'unit_deleted' => 'Unit :code deleted.',
    // :kind_plural is the kind of its units, e.g. "rooms", "dorm beds"
    'kind_locked' => 'A room type’s kind can’t change while it has units — they were added as :kind_plural.',
    'room_type_updated' => 'Room type “:name” updated.',
    'room_type_delete_blocked_has_units' => ':name can’t be deleted — it has :blockers on record. A room type can be deleted once it has no units.',
    'room_type_delete_blocked' => ':name can’t be deleted — it has :blockers on record. Deleting it would erase that or leave it pointing at nothing.',
    'room_type_deleted' => ':name deleted.',
    'rate_plan_created' => ':plan created for :room_type. Use Set prices on its row to give it nightly prices — guests can be offered it once it has them.',
    // a unit code is unique within its property (the database's own rule)
    'duplicate_code' => 'That unit code is already used in this property — choose another.',
    'unit_updated' => 'Unit :code updated.',
    'unit_resumed' => 'Unit :code is taking bookings again.',
    'unit_paused' => 'Unit :code is paused. Bookings already taken are kept; no new ones can be made.',
    'bulk_resumed_some_already' => '{1} Resumed :count unit · :skipped already taking bookings.|[0,*] Resumed :count units · :skipped already taking bookings.',
    'bulk_resumed' => '{1} Resumed :count unit.|[0,*] Resumed :count units.',
    'bulk_paused_some_already' => '{1} Paused :count unit · :skipped already paused. Bookings already taken are kept.|[0,*] Paused :count units · :skipped already paused. Bookings already taken are kept.',
    'bulk_paused' => '{1} Paused :count unit. Bookings already taken are kept.|[0,*] Paused :count units. Bookings already taken are kept.',
    'rate_plan_updated' => 'Rate plan “:name” updated. No prices changed.',
    'last_plan_on_sale' => '“:name” is the only rate plan still on sale for this room type. Taking it off sale would leave nothing to quote, so the room type could not be booked at all. Add or reactivate another plan first.',
    'rate_plan_already_on_sale' => 'Rate plan “:name” was already on sale.',
    'rate_plan_already_off_sale' => 'Rate plan “:name” was already off sale.',
    'rate_plan_back_on_sale' => 'Rate plan “:name” is back on sale.',
    'rate_plan_off_sale' => 'Rate plan “:name” is off sale. Existing bookings keep the price they were sold at; no nightly prices were changed.',
    'choose_room_type_property' => 'Choose the property this room type belongs to.',
    'choose_property' => 'Choose which property this belongs to — your account has more than one.',
    'arrival_nothing_changed' => 'Nothing changed — a blank field leaves the stored value alone.',
    'arrival_updated' => 'Arrival info updated for :unit.',
    'unit_code_taken' => 'Unit code :code is already used in this property — choose another.',
    'unit_codes_taken' => 'Already used in this property: :codes — choose other codes.',
    'code_taken_ignoring_accents' => 'One of these codes is already used in this property — codes that differ only by accents or capitals count as the same. Choose another.',
    // :date is the day the workspace resumes, e.g. "12 Nov"
    'paused_rates_until' => 'Workspace paused — rate changes are read-only until :date.',
    'paused_rates' => 'Workspace paused — rate changes are read-only for now.',
    'paused_units_until' => 'Workspace paused — changes to units and room types are read-only until :date.',
    'paused_units' => 'Workspace paused — changes to units and room types are read-only for now.',
];
