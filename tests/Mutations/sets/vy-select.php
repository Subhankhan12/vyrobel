<?php

declare(strict_types=1);

// Mutation set vy-select — the Vyrobel select, first on /app/profile (2026-10-09, his request: "make me very cool drop-downs …
// first work on this page"). Language and Timezone as `<x-select>`; every zone the server accepts, so nobody sees (or saves)
// Lisbon in place of their own; the list floats above the cards and opens centred on the choice. Run with
// `php tests/Mutations/run.php --set=vy-select`. ⚠️ These prove the markup, options and rules; the behaviour is a browser fact.

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
        'name' => 'the form value is empty until Alpine boots (a save before then blanks the field)',
        'file' => $select,
        'old' => '<input type="hidden" name="{{ $name }}" value="{{ $current }}"',
        'new' => '<input type="hidden" name="{{ $name }}" value=""',
        'tests' => [['file' => $test, 'filter' => 'no native select left']],
    ],
    [
        'id' => 'VS-M3',
        'name' => 'the field is no longer a combobox for a screen reader',
        'file' => $select,
        'old' => 'role="combobox" aria-haspopup="listbox"',
        'new' => 'aria-haspopup="listbox"',
        'tests' => [['file' => $test, 'filter' => 'no native select left']],
    ],
    [
        'id' => 'VS-M4',
        'name' => 'the field loses its label for a screen reader',
        'file' => $select,
        'old' => '@if ($labelledby) aria-labelledby="{{ $labelledby }} {{ $id }}" @endif',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'no native select left']],
    ],
    [
        'id' => 'VS-M5',
        'name' => 'the list stays inside its card (painted under the next card down)',
        'file' => $select,
        'old' => '<template x-teleport="body">',
        'new' => '<template x-if="true">',
        'tests' => [['file' => $test, 'filter' => 'floats the list above every card']],
    ],
    [
        'id' => 'VS-M6',
        'name' => 'the choice is centred from screen rects again (195px short mid-animation)',
        'file' => $select,
        'old' => 'list.scrollTop = (row.offsetTop - list.offsetTop) - (list.clientHeight - row.offsetHeight) / 2;',
        'new' => 'const r = row.getBoundingClientRect(), l = list.getBoundingClientRect(); list.scrollTop += (r.top - l.top) - (list.clientHeight - r.height) / 2;',
        'tests' => [['file' => $test, 'filter' => 'floats the list above every card']],
    ],
    [
        'id' => 'VS-M7',
        'name' => 'the list scrolls before it is on screen (opens at the top, not at the choice)',
        'file' => $select,
        'old' => 'this.$nextTick(() => requestAnimationFrame(() => {',
        'new' => 'this.$nextTick(() => (() => {',
        'tests' => [['file' => $test, 'filter' => 'floats the list above every card']],
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
    [
        'id' => 'VS-M12',
        'name' => 'Enter no longer picks the active option (the keyboard path the allow-list entry rests on)',
        'file' => $select,
        'old' => "case 'Enter': e.preventDefault(); this.choose(this.filtered[this.active]); break;",
        'new' => "case 'Enter': e.preventDefault(); break;",
        'tests' => [['file' => 'tests/Feature/KeyboardReachabilityContractTest.php', 'filter' => 'has no click handler a keyboard cannot reach']],
    ],
];
