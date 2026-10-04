<?php

declare(strict_types=1);

// Mutation set calendar-new-reservation — the Calendar's "+ New reservation" pass (2026-10-04, the founder: "the design is
// messed, guest first name, last name, and their boxes are not well aligned … also do additional works"): the dialog rebuilt
// on the shared solid card with each name beside its own box, the guest's name kept out of the address (and out of the next
// booking), rooms with no price, no rate plan or no rooms said rather than skipped, the unit's own price, dates that never
// start in the past, one "today", and a free night on a unit's row that opens the dialog for it.
// Run with `php tests/Mutations/run.php --set=calendar-new-reservation`.
// ⚠️ The dialog's own script (the name kept across its Check rack and only for the person who typed it, the name check before
// Confirm, the dates-changed line, the back-button reload) is JavaScript: browser-verified on the walk, not proven here.

$test = 'tests/Feature/CalendarNewReservationTest.php';
$gate = 'tests/Feature/OperatorControlGateTest.php';

$dialog = 'resources/views/livewire/operator/partials/rack-new-reservation.blade.php';
$rack = 'app/Livewire/ReservationRack.php';
$view = 'resources/views/livewire/reservation-rack.blade.php';

return [
    // ── The founder's report — the names and their boxes, on the shared dialog ────────────────────────
    [
        'id' => 'CN-M1',
        'name' => 'the name fields lose their pairing (each label beside its own box)',
        'file' => $dialog,
        'old' => '                        <div class="vyo-pair">',
        'new' => '                        <div class="vy-rnr-names">',
        'tests' => [['file' => $test, 'filter' => 'puts each name with its own box']],
    ],
    [
        'id' => 'CN-M2',
        'name' => 'the dialog is the see-through glass again',
        'file' => $dialog,
        'old' => '        <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg vy-rnr"',
        'new' => '        <div class="vy-modal vy-modal--wide vyo-dlg vy-rnr"',
        'tests' => [['file' => $test, 'filter' => 'is the shared solid dialog']],
    ],
    [
        'id' => 'CN-M3',
        'name' => 'Escape leaves new=1 in the address again',
        'file' => $dialog,
        'old' => 'x-on:keydown.escape.window="close()"',
        'new' => 'x-on:keydown.escape.window="open = false"',
        'tests' => [['file' => $test, 'filter' => 'is the shared solid dialog']],
    ],
    [
        'id' => 'CN-M4',
        'name' => 'a quote in a comment cuts the dialog\'s script short again',
        'file' => $dialog,
        'old' => "            // ⚠️ The name comes back ONLY across this dialog's own Check rack",
        'new' => "            // ⚠️ The name comes back ONLY across this dialog's own \"Check rack\"",
        'tests' => [['file' => $test, 'filter' => 'keeps the dialog’s whole script inside its attribute']],
    ],
    // ── The guest's name — out of the address, out of the next booking ────────────────────────────────
    [
        'id' => 'CN-M5',
        'name' => 'the first name rides the "Check rack" GET again',
        'file' => $dialog,
        'old' => '<input id="vy-rnr-gf" class="vy-input" type="text" x-model="gf"',
        'new' => '<input id="vy-rnr-gf" class="vy-input" type="text" name="gf" x-model="gf"',
        'tests' => [['file' => $test, 'filter' => 'never puts the guest’s name in the address']],
    ],
    [
        'id' => 'CN-M6',
        'name' => 'the dialog restores a name without its own marker',
        'file' => $dialog,
        'old' => '                        <input type="hidden" name="kept" value="1">',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'keeps the guest’s name only across its own']],
    ],
    [
        'id' => 'CN-M7',
        'name' => 'the dialog claims to match earlier guests again',
        'file' => $dialog,
        'old' => '                    <p class="vy-rnr-fine">Books it straight away, as a new guest',
        'new' => '                    <p class="vy-rnr-fine">Matches an existing profile if the guest has stayed before. Books it straight away, as a new guest',
        'tests' => [['file' => $test, 'filter' => 'says nothing it does not do']],
    ],
    // ── No dead ends ──────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'CN-M8',
        'name' => 'a room type with no price is skipped again ("no units are free")',
        'file' => $rack,
        'old' => "                \$waiting[] = ['name' => (string) \$rt['room_type_name'], 'units' => count(\$rt['units']), 'has_plan' => \$rt['rate_plan_id'] !== null];",
        'new' => '                // skipped',
        'tests' => [['file' => $test, 'filter' => 'says a room type has no price yet']],
    ],
    [
        'id' => 'CN-M9',
        'name' => 'someone who cannot set prices is sent to set one',
        'file' => $rack,
        'old' => "            'can_set_price' => (bool) auth()->user()?->can('rates.manage'),",
        'new' => "            'can_set_price' => true,",
        'tests' => [['file' => $test, 'filter' => 'says a room type has no price yet']],
    ],
    [
        'id' => 'CN-M10',
        'name' => 'a room type with no rate plan is sent to the full form, which cannot price it',
        'file' => $dialog,
        'old' => "                                    @if (\$w['has_plan'])",
        'new' => '                                    @if (true)',
        'tests' => [['file' => $test, 'filter' => 'says a room type has no rate plan yet']],
    ],
    [
        'id' => 'CN-M11',
        'name' => 'a property with no rooms gets the form, not the reason',
        'file' => $dialog,
        'old' => "            @if (\$__rnr['rooms'] === 0)",
        'new' => '            @if (false)',
        'tests' => [['file' => $test, 'filter' => 'says when the property has no rooms']],
    ],
    [
        'id' => 'CN-M12',
        'name' => 'a property whose rooms are closed is said to have none',
        'file' => $dialog,
        'old' => "                        @if (\$__rnr['rooms_all'] === 0)",
        'new' => '                        @if (true)',
        'tests' => [['file' => $test, 'filter' => 'says a property’s rooms are all closed']],
    ],
    // ── Money ─────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'CN-M13',
        'name' => 'a unit\'s own price is hidden behind the room type\'s again',
        'file' => $rack,
        'old' => "                \$totalMinor = \$ownPrices[\$planId][(string) \$u['id']] ?? (int) \$rt['price_minor'];",
        'new' => "                \$totalMinor = (int) \$rt['price_minor'];",
        'tests' => [['file' => $test, 'filter' => 'shows a unit’s own price']],
    ],
    [
        'id' => 'CN-M14',
        'name' => 'the room total is shown as if no tax were added to it',
        'file' => $rack,
        'old' => '                || $taxes->contains(fn (Tax $t): bool => StayEstimator::taxApplies($t, $currency))',
        'new' => '                || false',
        'tests' => [['file' => $test, 'filter' => 'says when taxes or fees will be added']],
    ],
    // ── Dates and "today" ─────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'CN-M15',
        'name' => 'check-in defaults to the calendar week\'s first day, even in the past',
        'file' => $rack,
        'old' => "        \$checkIn = \$this->validDate(\$q->query('ci')) ?? max(\$windowStart->toDateString(), \$bookFrom);",
        'new' => "        \$checkIn = \$this->validDate(\$q->query('ci')) ?? \$windowStart->toDateString();",
        'tests' => [['file' => $test, 'filter' => 'never starts a booking in the past']],
    ],
    [
        'id' => 'CN-M16',
        'name' => 'the check-in box accepts a past day again',
        'file' => $dialog,
        'old' => "name=\"ci\" value=\"{{ \$__rnr['form']['check_in'] }}\" min=\"{{ \$__rnr['today'] }}\"",
        'new' => "name=\"ci\" value=\"{{ \$__rnr['form']['check_in'] }}\"",
        'tests' => [['file' => $test, 'filter' => 'never starts a booking in the past']],
    ],
    [
        'id' => 'CN-M17',
        'name' => 'a past check-in can be confirmed again',
        'file' => $rack,
        'old' => "            \$form['check_in'] < \$bookFrom =>",
        'new' => '            false =>',
        'tests' => [['file' => $test, 'filter' => 'says a check-in in the past cannot be booked']],
    ],
    [
        'id' => 'CN-M18',
        'name' => 'a check-out before check-in says nothing',
        'file' => $rack,
        'old' => "            \$nights === 0 => 'Check-out must be after check-in.',",
        'new' => "            false => 'Check-out must be after check-in.',",
        'tests' => [['file' => $test, 'filter' => 'names a check-out before check-in']],
    ],
    [
        'id' => 'CN-M19',
        'name' => 'a stay over the limit says "nothing is free"',
        'file' => $rack,
        'old' => '            $nights > UnitAvailabilityService::MAX_NIGHTS =>',
        'new' => '            false =>',
        'tests' => [['file' => $test, 'filter' => 'names a check-out before check-in']],
    ],
    [
        'id' => 'CN-M20',
        'name' => 'today is the app\'s date again, not the property\'s',
        'file' => $rack,
        'old' => "                'today' => \$bookFrom,",
        'new' => "                'today' => CarbonImmutable::today()->toDateString(),",
        'tests' => [['file' => $test, 'filter' => 'marks today in the property’s own timezone']],
    ],
    // ── Click an empty night ──────────────────────────────────────────────────────────────────────────
    [
        'id' => 'CN-M21',
        'name' => 'a booked night offers a booking',
        'file' => $rack,
        'old' => "            if (! isset(\$covered[\$i]) && \$d['date'] >= \$bookFrom && (\$available[\$d['date']] ?? 0) >= 1) {",
        'new' => "            if (\$d['date'] >= \$bookFrom && (\$available[\$d['date']] ?? 0) >= 1) {",
        'tests' => [['file' => $test, 'filter' => 'lets an empty night start a booking']],
    ],
    [
        'id' => 'CN-M22',
        'name' => 'a past night offers a booking',
        'file' => $rack,
        'old' => "            if (! isset(\$covered[\$i]) && \$d['date'] >= \$bookFrom && (\$available[\$d['date']] ?? 0) >= 1) {",
        'new' => "            if (! isset(\$covered[\$i]) && (\$available[\$d['date']] ?? 0) >= 1) {",
        'tests' => [['file' => $test, 'filter' => 'lets an empty night start a booking']],
    ],
    [
        'id' => 'CN-M23',
        'name' => 'a night with no room to sell offers a booking',
        'file' => $rack,
        'old' => "            if (! isset(\$covered[\$i]) && \$d['date'] >= \$bookFrom && (\$available[\$d['date']] ?? 0) >= 1) {",
        'new' => "            if (! isset(\$covered[\$i]) && \$d['date'] >= \$bookFrom) {",
        'tests' => [['file' => $test, 'filter' => 'offers a night only where the room type has a room to sell']],
    ],
    [
        'id' => 'CN-M24',
        'name' => 'the night links show to a role that cannot book',
        'file' => $view,
        'old' => "                                            @if (\$canBook && \$unit['state'] === 'active')",
        'new' => "                                            @if (\$unit['state'] === 'active')",
        'tests' => [['file' => $test, 'filter' => 'lets an empty night start a booking']],
    ],
    [
        'id' => 'CN-M25',
        'name' => 'a clicked unit that is not free is picked anyway',
        'file' => $rack,
        'old' => "            'preselect' => collect(\$units)->contains('unit_id', \$preselect) ? \$preselect : '',",
        'new' => "            'preselect' => \$preselect,",
        'tests' => [['file' => $test, 'filter' => 'picks the clicked unit when it is free']],
    ],
    // ── The gate and bad input ────────────────────────────────────────────────────────────────────────
    [
        'id' => 'CN-M26',
        'name' => 'a typed-in new=1 opens the dialog for a role that cannot book, or in a paused workspace',
        'file' => $rack,
        'old' => "        if (\$q->query('new') !== '1' || ! \$canBook || \$property === null) {",
        'new' => "        if (\$q->query('new') !== '1' || \$property === null) {",
        'tests' => [
            ['file' => $test, 'filter' => 'offers no booking link and no dialog while the workspace is paused'],
            ['file' => $test, 'filter' => 'lets an empty night start a booking'],
        ],
    ],
    [
        'id' => 'CN-M33',
        'name' => 'the dialog never opens, even for someone who may book',
        'file' => $rack,
        'old' => "        if (\$q->query('new') !== '1' || ! \$canBook || \$property === null) {",
        'new' => '        if (true) {',
        'tests' => [['file' => $gate, 'filter' => 'offers New reservation on the calendar only to a role that may create one']],
    ],
    [
        'id' => 'CN-M27',
        'name' => 'a paused workspace offers night links and the dialog',
        'file' => $rack,
        'old' => "        \$canBook = ! app(OperatorWorkspace::class)->isReadOnly() && (bool) auth()->user()?->can('reservations.create');",
        'new' => "        \$canBook = (bool) auth()->user()?->can('reservations.create');",
        'tests' => [['file' => $test, 'filter' => 'offers no booking link and no dialog while the workspace is paused']],
    ],
    [
        'id' => 'CN-M28',
        'name' => 'a hand-made unit[] in the address is a 500 again',
        'file' => $rack,
        'old' => "        \$preselect = is_string(\$unitParam) ? \$unitParam : '';",
        'new' => "        \$preselect = (string) \$q->query('unit', '');",
        'tests' => [['file' => $test, 'filter' => 'turns a hand-made array in the address into nothing']],
    ],
    [
        'id' => 'CN-M29',
        'name' => 'a hand-made rt[] in the address is a 500 again',
        'file' => $rack,
        'old' => "        \$filterRt = is_string(\$rtParam) ? trim(\$rtParam) : '';",
        'new' => '        $filterRt = trim((string) $rtParam);',
        'tests' => [['file' => $test, 'filter' => 'turns a hand-made array in the address into nothing']],
    ],
    // ── Small things ──────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'CN-M30',
        'name' => 'the agent source is named apart from the full form',
        'file' => $rack,
        'old' => "'agent' => 'Travel agent'",
        'new' => "'agent' => 'Agent'",
        'tests' => [['file' => $test, 'filter' => 'names the travel agent source']],
    ],
    [
        'id' => 'CN-M31',
        'name' => 'an inline style comes back to the dialog',
        'file' => $dialog,
        'old' => '            <div class="vy-modal-head">',
        'new' => '            <div class="vy-modal-head" style="padding: 22px;">',
        'tests' => [['file' => $test, 'filter' => 'uses no inline style in the dialog']],
    ],
    [
        'id' => 'CN-M32',
        'name' => 'the drag hint splits into two columns again',
        'file' => $view,
        'old' => '<p class="vy-rack-hint"><strong>Drag a booking</strong> to a new date or unit',
        'new' => '<p class="vy-rack-hint"><span class="vy-deferred-note"><strong>Drag a booking</strong></span> to a new date or unit',
        'tests' => [['file' => $test, 'filter' => 'keeps the drag hint in one sentence']],
    ],
];
