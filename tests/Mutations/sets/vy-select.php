<?php

declare(strict_types=1);

// Mutation set vy-select — the rich select (the select component) and the timezone list behind it (2026-10-09 on /app/profile;
// 2026-10-10 rebuilt on the one engine for the app-wide rollout). A REAL select carries the value, regions are <optgroup>s,
// every zone the server accepts is offered, and a person elsewhere never sees (or saves) Lisbon. The engine itself is the
// select-engine set. Run with `php tests/Mutations/run.php --set=vy-select`.

$test = 'tests/Feature/VySelectTest.php';
$select = 'resources/views/components/select.blade.php';
$zones = 'app/Support/Time/TimezoneOptions.php';

return [
    [
        'id' => 'VS-M1',
        'name' => 'the timezone list shrinks back to a few zones (Baku shows — and saves — as something else)',
        'file' => 'app/Livewire/Operator/OperatorProfile.php',
        'old' => "'timezoneOptions' => TimezoneOptions::choices(",
        'new' => "'timezoneOptions' => ['Europe/Lisbon' => 'Europe/Lisbon', 'UTC' => 'UTC'] ?: TimezoneOptions::choices(",
        'tests' => [['file' => $test, 'filter' => 'offers every zone']],
    ],
    [
        'id' => 'VS-M2',
        'name' => 'the saved value is not chosen (the first option shows — and saves)',
        'file' => $select,
        'old' => "@selected(\$item['value'] === \$current)>",
        'new' => '>',
        'tests' => [['file' => $test, 'filter' => 'offers every zone']],
    ],
    [
        'id' => 'VS-M3',
        'name' => 'the select loses its id, so the page\'s label[for] names nothing',
        'file' => $select,
        'old' => '<select name="{{ $name }}" id="{{ $id }}"',
        'new' => '<select name="{{ $name }}"',
        'tests' => [['file' => $test, 'filter' => 'renders Language and Timezone as the rich select']],
    ],
    [
        'id' => 'VS-M4',
        'name' => 'a long list is not searchable',
        'file' => $select,
        'old' => '($searchable === null && count($items) > 8)) data-search',
        'new' => '($searchable === null && count($items) > 8000)) data-search',
        'tests' => [['file' => $test, 'filter' => 'renders Language and Timezone as the rich select']],
    ],
    [
        'id' => 'VS-M5',
        'name' => 'regions lose their headings (no optgroup)',
        'file' => $select,
        'old' => "@if (\$group !== '')<optgroup label=\"{{ \$group }}\">@endif",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'renders Language and Timezone as the rich select']],
    ],
    [
        'id' => 'VS-M6',
        'name' => 'the hint is not drawn on the field until a script runs',
        'file' => $select,
        'old' => "<span class=\"vys-deco-hint\">{{ \$chosen['hint'] ?? '' }}</span>",
        'new' => '<span class="vys-deco-hint"></span>',
        'tests' => [['file' => $test, 'filter' => 'renders Language and Timezone as the rich select']],
    ],
    [
        'id' => 'VS-M7',
        'name' => 'the device\'s own zone is no longer offered',
        'file' => $select,
        'old' => '@if ($deviceTimezone) data-suggest-device-zone @endif',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'renders Language and Timezone as the rich select']],
    ],
    [
        'id' => 'VS-M8',
        'name' => 'a saved zone the list lacks is dropped (the field would show — and save — another)',
        'file' => $zones,
        'old' => "if (\$keep !== null && \$keep !== '' && ! in_array(\$keep, \$ids, true)) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'labels every zone']],
    ],
    [
        'id' => 'VS-M9',
        'name' => 'a negative offset written with a hyphen, not a minus',
        'file' => $zones,
        'old' => "return 'UTC'.(\$seconds < 0 ? '−' : '+')",
        'new' => "return 'UTC'.(\$seconds < 0 ? '-' : '+')",
        'tests' => [['file' => $test, 'filter' => 'labels every zone']],
    ],
    [
        'id' => 'VS-M10',
        'name' => 'typing "-3" no longer finds a UTC−3 zone',
        'file' => $zones,
        'old' => ".' '.str_replace('−', '-', \$hint)),",
        'new' => '),',
        'tests' => [['file' => $test, 'filter' => 'labels every zone']],
    ],
    [
        'id' => 'VS-M11',
        'name' => 'a city loses its region ("Buenos Aires" for America/Argentina/Buenos_Aires)',
        'file' => $zones,
        'old' => "\$label = count(\$parts) > 2 ? \$city.', '.str_replace('_', ' ', \$parts[1]) : \$city;",
        'new' => '$label = $city;',
        'tests' => [['file' => $test, 'filter' => 'labels every zone']],
    ],
];
