<?php

// app/Http/Controllers/Operator/OwnerUnitAssignController.php — DEC-p: giving an existing owner a unit (the owner's page, "Give a unit").

return [
    'workspace_paused_until' => 'Workspace paused — owner changes are read-only until :date.',
    'workspace_paused_for_now' => 'Workspace paused — owner changes are read-only for now.',
    'unit_required' => 'Choose which unit to give.',
    'date_invalid' => 'Pick a real date to give the unit from.',
    'password_required' => 'Enter your password to give this unit.',
    'password_wrong' => 'That password isn’t right — enter the one you sign in with.',
    'given' => '“:unit” now belongs to :owner from :date. Stays that start before then stay with you.',
    'stays_worked_out' => '{1} One stay from then on was worked out for :owner.|[2,*] :count stays from then on were worked out for :owner.',
    'stays_waiting' => '{1} One more will be worked out when its room charge is posted.|[2,*] :count more will be worked out when their room charges are posted.',
];
