<?php

declare(strict_types=1);

// Mutation set finish-01-c — FINISH 01 · Part C (the small backlog). Run with `php tests/Mutations/run.php --set=finish-01-c`.

return [
    [
        'id' => 'C-M1',
        'name' => 'the silent-discard switch removed from the test environment',
        'file' => 'tests/TestCase.php',
        'old' => '        Model::preventSilentlyDiscardingAttributes();
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/SilentDiscardIsLoudInTestsTest.php']],
    ],
    [
        'id' => 'C-M2',
        'name' => 'the prototype\'s "48h" put back into the visible copy',
        'file' => 'lang/en/guest/reservation.php',
        'old' => '\'Your door code & Wi-Fi unlock on :date.\'',
        'new' => '\'Your door code & Wi-Fi unlock 48h before arrival, on :date.\'',
        'tests' => [['file' => 'tests/Feature/GuestReservationTest.php', 'filter' => 'derives the unlock date']],
    ],
    [
        'id' => 'C-M3',
        'name' => 'the demo seeder runs GUARDED again when called directly',
        'file' => 'database/seeders/DemoSeeder.php',
        'old' => '        Model::unguarded(fn () => $this->seedDemo());',
        'new' => '        $this->seedDemo();',
        'tests' => [['file' => 'tests/Feature/DemoSeedDateRobustnessTest.php']],
    ],
];
