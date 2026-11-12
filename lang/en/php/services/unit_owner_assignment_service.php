<?php

// app/Services/UnitOwnerAssignmentService.php — DEC-p: giving an existing owner a unit that has no owner. Every line is a refusal
// the operator reads on the owner's page; nothing is written when one is shown.

return [
    'owner_not_active' => ':owner can’t be given a unit — they haven’t accepted their invitation yet, or they have been removed. Only an active owner can be given a unit.',
    'unit_on_pending_invitation' => '“:unit” is listed on an owner invitation that has not been accepted yet. It becomes that owner’s when they accept — or cancel the invitation first to give the unit to someone else.',
    'unit_already_owned' => '“:unit” already belongs to :owner, so it can’t be given to someone else here. Only a unit with no owner can be given.',
    'unit_owned_by_another' => 'another owner',
    'date_invalid' => 'Pick a real date to give the unit from.',
    'date_in_past' => 'Pick today or a later day. A day that has already passed would change earnings that are already worked out.',
    'date_too_far' => 'Pick a day within the next :days days.',
    'no_agreement' => ':owner has no agreement that covers “:unit” on :date. Give :owner an agreement that covers this unit from :date first, then give them the unit.',
    'stay_across_date' => 'A guest is staying in “:unit” from :from to :to, which is across :date. Pick :to or a later day, so that no stay is shared between you and :owner.',
    'future_stays_with_another_owner' => '“:unit” has stays on or after :date that are already worked out for another owner (:owners). Those stay with them, so the unit can’t be given from that day.',
];
