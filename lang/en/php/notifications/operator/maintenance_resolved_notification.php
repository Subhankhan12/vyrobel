<?php

// app/Notifications/Operator/MaintenanceResolvedNotification.php — :title is the work order's title, :unit the unit.

return [
    'title' => 'Work order resolved · :title',
    'context_back_online' => 'Unit :unit — fixed and back online',
    'context_still_out' => 'Unit :unit — resolved (still out of order)',
];
