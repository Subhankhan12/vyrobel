<?php

// app/Http/Controllers/Operator/PropertyController.php — the Portfolio's property writes (add, edit, remove, keep).

return [
    'paused_adding' => 'Workspace paused — adding a property is read-only until :resume.',
    'property_added' => ':name added to your portfolio. Next: add a room type and a unit, then a rate plan with prices — then it can take bookings.',
    'paused_editing' => 'Workspace paused — editing a property is read-only until :resume.',
    'property_updated' => ':name updated.',
    'name_mismatch' => 'The name you typed didn’t match “:name” — nothing was removed.',
    'not_removed' => ':name was not removed. :reason',
    'removed' => ':name was removed. Its bookings, invoices and payments are kept.',
    'removal_scheduled' => ':name will be removed after the last checkout on :date. It stays open until then — you can keep it any time before.',
    'removal_cancelled' => ':name stays — its removal is cancelled.',
    'read_only' => 'Your workspace is read-only right now — nothing was changed.',
];
