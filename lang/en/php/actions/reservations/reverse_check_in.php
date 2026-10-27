<?php

// app/Actions/Reservations/ReverseCheckIn.php — the R9 disclosure the confirmation screen prints, and its refusals.

return [
    'reversible_status' => 'The stay returns to confirmed, and the room stays held for the same nights.',
    'history_kept' => 'The check-in itself stays in the booking history. It happened, and the record says so — the reversal is added alongside it.',
    'charges_kept' => '{1} :count charge posted since check-in, totalling :amount, stay on the folio. Reversing an arrival does not un-spend money — remove them on the folio if they were a mistake too.|[0,*] :count charges posted since check-in, totalling :amount, stay on the folio. Reversing an arrival does not un-spend money — remove them on the folio if they were a mistake too.',
    'online_check_in_kept' => 'The guest\'s own online check-in stays completed. They really did fill it in, and this does not ask them to do it again.',
    'already_reversed' => 'That check-in was already reversed a moment ago — the stay is back to confirmed.',
    // :status is the raw status code, as before
    'refused_not_checked_in' => 'Only a checked-in stay can have its check-in reversed; this one is :status.',
];
