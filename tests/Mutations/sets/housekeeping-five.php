<?php

declare(strict_types=1);

// Mutation set housekeeping-five — the Housekeeping page in the five-page pass (2026-10-05, the founder: "Text is not visible
// completely. Serach box design is not good and its symbol is not well-aligned. Board and list buttons design is not good"). 🔬 Its
// search was its own pill whose icon lost a cascade fight; Board and List were bare buttons; a 720 px list ran past narrower cards;
// "In progress" had no tone; "Today's cleaning" over every day's rooms. Run with `php tests/Mutations/run.php --set=housekeeping-five`.
// ⚠️ How it looks is what a browser draws: these prove the markup, the words and the rules exist; the 🖥 walk proved the look.

$test = 'tests/Feature/HousekeepingPassFiveTest.php';

return [
    [
        'id' => 'HK-M1',
        'name' => 'the search is the page\'s own pill again — its icon at the top of the row (his report)',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '<form class="vy-gl-search" method="GET" action="{{ route(\'app.housekeeping\') }}" role="search">',
        'new' => '<form class="vy-input-group vy-input-group--pill" method="GET" action="{{ route(\'app.housekeeping\') }}" role="search">',
        'tests' => [['file' => $test, 'filter' => 'searches with the shared search box']],
    ],
    [
        'id' => 'HK-M2',
        'name' => 'the search hint says less than the search does',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => 'placeholder="Search room, type or housekeeper"',
        'new' => 'placeholder="Search rooms…"',
        'tests' => [['file' => $test, 'filter' => 'searches with the shared search box']],
    ],
    [
        'id' => 'HK-M3',
        'name' => 'Board and List are loose buttons again (his report)',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '<div class="vy-hk-views" role="group" aria-label="View">',
        'new' => '<div class="vy-seg">',
        'tests' => [['file' => $test, 'filter' => 'switches Board and List with one real segmented control']],
    ],
    [
        'id' => 'HK-M4',
        'name' => 'the List button does not say it is pressed',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => ':aria-pressed="view === \'list\' ? \'true\' : \'false\'"',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'switches Board and List with one real segmented control']],
    ],
    [
        'id' => 'HK-M5',
        'name' => 'the browser draws its own button again',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '.vy-hk-view { -webkit-appearance: none; appearance: none; display: inline-flex;',
        'new' => '.vy-hk-view { display: inline-flex;',
        'tests' => [['file' => $test, 'filter' => 'switches Board and List with one real segmented control']],
    ],
    [
        'id' => 'HK-M6',
        'name' => 'no focus ring on Board / List',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-hk-view:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'switches Board and List with one real segmented control']],
    ],
    [
        'id' => 'HK-M7',
        'name' => 'the list is the 720 px table again',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '<table class="vy-gl-table vy-hk-table">',
        'new' => '<table class="vy-utable vy-utable--stick-action">',
        'tests' => [['file' => $test, 'filter' => 'fits the list inside its card at every width']],
    ],
    [
        'id' => 'HK-M8',
        'name' => 'the list card is not a size container — a narrow card cuts Ready and the action (his report)',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-hk .vy-gl-card { container: vy-hk-list / inline-size; }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'fits the list inside its card at every width']],
    ],
    [
        'id' => 'HK-M9',
        'name' => 'the shared table\'s 760 px floor comes back',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-hk .vy-gl-table { min-width: 0; }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'fits the list inside its card at every width']],
    ],
    [
        'id' => 'HK-M10',
        'name' => '"In progress" is a blank tag again',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-tag.tone-in-progress .vy-dot { background: var(--vy-hk-progress); }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'gives "In progress" its colour']],
    ],
    [
        'id' => 'HK-M11',
        'name' => 'the page claims "Today’s cleaning" over every day\'s rooms',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => 'subtitle="Cleaning across your rooms, by day"',
        'new' => 'subtitle="Today’s cleaning across your rooms"',
        'tests' => [['file' => $test, 'filter' => 'says which day each room is for']],
    ],
    [
        'id' => 'HK-M12',
        'name' => 'an overdue room reads like any other day',
        'file' => 'app/Livewire/HousekeepingBoard.php',
        'old' => '            $d->lessThan($today) && ! $done => [\'day\' => \'Overdue · \'.$d->format(\'j M\'), \'day_tone\' => \'overdue\'],
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'says which day each room is for']],
    ],
    [
        'id' => 'HK-M13',
        'name' => 'tomorrow\'s room carries a bare weekday',
        'file' => 'app/Livewire/HousekeepingBoard.php',
        'old' => '            $d->isSameDay($today->addDay()) => [\'day\' => \'Tomorrow\', \'day_tone\' => \'later\'],
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'says which day each room is for']],
    ],
    [
        'id' => 'HK-M14',
        'name' => 'the Assign dialog\'s developer note comes back',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '<p class="vy-hk-hint">It shows under their name on this board. Nothing is sent to their phone.</p>',
        'new' => '<p class="vy-hk-hint">The room appears in their task list. (Device push isn’t wired — no fabricated notification.)</p>',
        'tests' => [['file' => $test, 'filter' => 'says only what the Assign dialogs really do']],
    ],
    [
        'id' => 'HK-M15',
        'name' => 'a housekeeper\'s open rooms called "today"',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '<span class="vy-hk-option-load">{{ $member[\'load\'] }} open</span>',
        'new' => '<span class="vy-hk-option-load">{{ $member[\'load\'] }} today</span>',
        'tests' => [['file' => $test, 'filter' => 'says only what the Assign dialogs really do']],
    ],
    [
        'id' => 'HK-M16',
        'name' => 'the detail\'s first stage is "Dirty" again',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => 'in [\'Pending\', \'In progress\', \'Clean\', \'Inspected\']',
        'new' => 'in [\'Dirty\', \'In progress\', \'Clean\', \'Inspected\']',
        'tests' => [['file' => $test, 'filter' => 'says "Pending" in the detail’s stages']],
    ],
    [
        'id' => 'HK-M17',
        'name' => 'the empty photo row offers to "flag an issue" again',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '<span class="vy-hk-none">Add a photo of the finished room.</span>',
        'new' => '<span class="vy-hk-none">Attach proof of the clean, or flag an issue.</span>',
        'tests' => [['file' => $test, 'filter' => 'says "Pending" in the detail’s stages']],
    ],
    [
        'id' => 'HK-M18',
        'name' => 'Assign is a glass card with no shared dialog class (A8 family)',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '<div class="vy-modal vy-modal-card vy-hk-dlg" role="dialog" aria-modal="true" aria-labelledby="vy-hk-assign-t"',
        'new' => '<div class="vy-card vy-modal-card vy-hk-dlg" role="dialog" aria-modal="true" aria-labelledby="vy-hk-assign-t"',
        'tests' => [['file' => $test, 'filter' => 'opens every dialog centred on the shared solid card']],
    ],
    [
        'id' => 'HK-M19',
        'name' => 'the lane arrow turns with a string :style again (A10)',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => ':class="laneOpen(\'{{ $column[\'key\'] }}\') && \'is-open\'"',
        'new' => ':style="laneOpen(\'{{ $column[\'key\'] }}\') ? \'transform: rotate(180deg)\' : \'\'"',
        'tests' => [['file' => $test, 'filter' => 'turns the lane’s arrow with a class']],
    ],
    [
        'id' => 'HK-M20',
        'name' => 'the detail does not reopen after a photo',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '        detail: @js($detail), assign: null, assignAllOpen: false,',
        'new' => '        detail: null, assign: null, assignAllOpen: false,',
        'tests' => [['file' => $test, 'filter' => 'reopens a room’s detail after a photo']],
    ],
    [
        'id' => 'HK-M21',
        'name' => 'a housekeeper\'s chip counts only their open rooms — not what it shows',
        'file' => 'app/Livewire/HousekeepingBoard.php',
        'old' => '            \'count\' => (int) ($shown[$hk[\'id\']] ?? 0),',
        'new' => '            \'count\' => (int) ($load[$hk[\'id\']] ?? 0),',
        'tests' => [['file' => $test, 'filter' => 'counts on each housekeeper chip exactly the rooms clicking it shows']],
    ],
    [
        'id' => 'HK-M22',
        'name' => 'Assign offered with nobody on the roster (🖥 the walk)',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '        @php $canAssign = $canManage && $crew !== []; @endphp',
        'new' => '        @php $canAssign = $canManage; @endphp',
        'tests' => [['file' => $test, 'filter' => 'offers no Assign while nobody is on the roster']],
    ],
    [
        'id' => 'HK-M23',
        'name' => 'the hint links Roles & access for someone it would refuse (#30)',
        'file' => 'resources/views/livewire/housekeeping-board.blade.php',
        'old' => '                @if ($canStaff)
',
        'new' => '                @if (true)
',
        'tests' => [['file' => $test, 'filter' => 'offers no Assign while nobody is on the roster']],
    ],
    [
        'id' => 'HK-M24',
        'name' => 'the card\'s day goes',
        'file' => 'resources/views/livewire/partials/hk-card.blade.php',
        'old' => '        <span class="vy-hk-day is-{{ $card[\'day_tone\'] }}">{{ $card[\'day\'] }}</span>
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'says which day each room is for']],
    ],
];
