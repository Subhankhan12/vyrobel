<?php

// app/Exceptions/Booking/RestrictionsViolationException.php — shown on the new-reservation wizard when an override is resubmitted.

return [
    // only reached with two or more breaches; :list is the breaches' own sentences, joined
    'breaches' => 'This booking breaches :count restrictions: :list',
    'changed_since' => 'The restrictions on these dates changed while you were reviewing them — check the list again before overriding.',
];
