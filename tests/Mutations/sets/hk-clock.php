<?php

declare(strict_types=1);

// Mutation set hk-clock — D-HK-UTC-CLOCK (2026-11-12): the Housekeeping card's "started" / "done" clock is the property's own.
// Run with `php tests/Mutations/run.php --set=hk-clock`.

$test = 'tests/Feature/HousekeepingClockAtPropertyTest.php';

return [
    [
        'id' => 'HKC-M1',
        'name' => 'the clock ignores the timezone (the stored UTC time is printed again)',
        'file' => 'app/Support/Property/PropertyDay.php',
        'old' => 'return ($timezone !== null ? $at->setTimezone($timezone) : $at)->format(\'H:i\');',
        'new' => 'return $at->format(\'H:i\');',
        'tests' => [['file' => $test, 'filter' => 'started at the PROPERTY']],
    ],
    [
        'id' => 'HKC-M2',
        'name' => 'the board never hands a card its property\'s timezone (every card reads UTC)',
        'file' => 'app/Livewire/HousekeepingBoard.php',
        'old' => '$zones[(string) $t->property_id] ?? null))->all();',
        'new' => 'null))->all();',
        'tests' => [['file' => $test, 'filter' => 'was done at the PROPERTY']],
    ],
    [
        'id' => 'HKC-M3',
        'name' => 'a finished room\'s clock is taken from its START (the done time is the start time)',
        'file' => 'app/Livewire/HousekeepingBoard.php',
        'old' => "'completed_label' => PropertyDay::clock(\$task->completed_at, \$timezone),",
        'new' => "'completed_label' => PropertyDay::clock(\$task->started_at, \$timezone),",
        'tests' => [['file' => $test, 'filter' => 'was done at the PROPERTY']],
    ],
];
