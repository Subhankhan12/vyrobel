<?php

// app/Notifications/Operator/MaintenanceReassignedNotification.php — :title is the work order's title, :unit the unit.

return [
    'title_assigned' => 'Work order assigned to you · :title',
    'title_reassigned' => 'Work order reassigned · :title',
    'context_assigned' => 'Unit :unit — it is now yours',
    'context_reassigned' => 'Unit :unit — you are off this one',
];
