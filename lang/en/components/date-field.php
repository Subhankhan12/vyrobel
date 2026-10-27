<?php

// resources/views/components/date-field.blade.php — the themed date picker. Month and weekday names come from Carbon.

return [
    'placeholder' => 'Pick a date',
    // the trigger's accessible name once a date is chosen: the field's label, then the full date
    'label_value' => ':label: :value',
    'calendar' => ':label calendar',
    'previous_month' => 'Previous month',
    'next_month' => 'Next month',
];
