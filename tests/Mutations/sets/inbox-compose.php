<?php

declare(strict_types=1);

// Mutation set inbox-compose — the Inbox in the five-page pass (2026-10-05, the founder: "on ~ New message page design is not good. 2
// text is repeated. Fix it. It must have better design"). 📖 The dialog printed its hint as a label AND the placeholder under a second
// label, said who it lists twice, shared an id with the reply box, and a Direct message read "Delivered" though Direct sends nothing.
// Run with `php tests/Mutations/run.php --set=inbox-compose`. ⚠️ How the dialog looks is what a browser draws: these prove the markup,
// the words and the rules exist; the 🖥 walk at 1,440 and 390 px proved how they look. IB-M18…M24: the filter tabs, cut to "Clos" at
// 390 px on production (seen on the live look of rel_AY) — the 🖥 walk at 320 to 1,280 px proved every word shows.

$test = 'tests/Feature/InboxComposeRedesignTest.php';

return [
    [
        'id' => 'IB-M1',
        'name' => 'the hint is a label again, under "Message" (his "2 text is repeated")',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<label class="vy-field-label" for="vy-ibx-compose-body">Message</label>',
        'new' => '<span class="vy-field-label">Message</span><label class="vy-field-label" for="vy-ibx-compose-body">Write a message, or pick a template above to start from one — you can edit it before sending.</label>',
        'tests' => [['file' => $test, 'filter' => 'says each thing once in New message']],
    ],
    [
        'id' => 'IB-M2',
        'name' => 'the placeholder repeats the hint',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => 'placeholder="Write your message…"',
        'new' => 'placeholder="Write a message, or pick a template above to start from one — you can edit it before sending."',
        'tests' => [['file' => $test, 'filter' => 'says each thing once in New message']],
    ],
    [
        'id' => 'IB-M3',
        'name' => 'the sub-text claims only current or upcoming stays',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<p class="vy-modal-sub">Guests staying now, arriving soon, or who left in the last 14 days.</p>',
        'new' => '<p class="vy-modal-sub">Guests with a current or upcoming stay.</p>',
        'tests' => [['file' => $test, 'filter' => 'says who it lists']],
    ],
    [
        'id' => 'IB-M4',
        'name' => 'a dead form when nobody can be messaged',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '                @if ($composeGuests !== [])
',
        'new' => '                @if (true)
',
        'tests' => [['file' => $test, 'filter' => 'offers the way on when there is nobody to message']],
    ],
    [
        'id' => 'IB-M5',
        'name' => 'the dialog\'s box shares its id with the reply box again',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<textarea id="vy-ibx-compose-body"',
        'new' => '<textarea id="vy-lbl-operator-inbox-body"',
        'tests' => [['file' => $test, 'filter' => 'gives the dialog’s message box its own id']],
    ],
    [
        'id' => 'IB-M6',
        'name' => 'no way to find a guest by name',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => 'placeholder="Find a guest"',
        'new' => 'placeholder=""',
        'tests' => [['file' => $test, 'filter' => 'lets a guest be found by name in the dialog']],
    ],
    [
        'id' => 'IB-M7',
        'name' => 'Start is clickable with no guest',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<button type="submit" class="vy-btn vy-btn--accent" :disabled="! composeGuest">Start conversation</button>',
        'new' => '<button type="submit" class="vy-btn vy-btn--accent">Start conversation</button>',
        'tests' => [['file' => $test, 'filter' => 'lets a guest be found by name in the dialog']],
    ],
    [
        'id' => 'IB-M8',
        'name' => 'the footer promises "the guest replies wherever they booked" again',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<span x-show="composeGuest && composeAccount[composeGuest]" x-cloak><span x-text="composeName()"></span> can read it in their guest account.</span>',
        'new' => '<span>Starts a thread in this inbox · the guest replies wherever they booked.</span>',
        'tests' => [['file' => $test, 'filter' => 'says what happens to the message']],
    ],
    [
        'id' => 'IB-M9',
        'name' => 'the Assign dialog is the see-through glass again (A9)',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<div class="vy-modal vy-modal-card vy-ibx-dlg" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="assignOpen"',
        'new' => '<div class="vy-modal vy-ibx-dlg" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="assignOpen"',
        'tests' => [['file' => $test, 'filter' => 'puts every Inbox dialog on the shared solid card']],
    ],
    [
        'id' => 'IB-M10',
        'name' => 'the page brings back its own see-through dialog rule',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '        .vy-ibx-dlg { max-width: 540px; }
',
        'new' => '        .vy-modal { background: var(--vy-surface, #FFFCF8); }
',
        'tests' => [['file' => $test, 'filter' => 'puts every Inbox dialog on the shared solid card']],
    ],
    [
        'id' => 'IB-M11',
        'name' => 'the Assign note\'s hint is its label AND its placeholder again',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<label class="vy-field-label" for="vy-ibx-note">Note for the team · optional</label>',
        'new' => '<label class="vy-field-label" for="vy-ibx-note">Add a note for the team (optional) — e.g. why you\'re handing this over</label>',
        'tests' => [['file' => $test, 'filter' => 'says the Assign dialog’s things once']],
    ],
    [
        'id' => 'IB-M12',
        'name' => 'a Direct message reads "Delivered" again',
        'file' => 'app/Livewire/Operator/OperatorInbox.php',
        'old' => '\'delivered\' => [\'label\' => $channel === \'manual\' ? \'Saved\' : \'Delivered\', \'failed\' => false],',
        'new' => '\'delivered\' => [\'label\' => \'Delivered\', \'failed\' => false],',
        'tests' => [['file' => $test, 'filter' => 'calls a Direct message "Saved"']],
    ],
    [
        'id' => 'IB-M13',
        'name' => 'a Direct conversation is treated as a booking channel',
        'file' => 'app/Livewire/Operator/OperatorInbox.php',
        'old' => '            \'is_direct\' => (string) $thread->channel === \'manual\',',
        'new' => '            \'is_direct\' => false,',
        'tests' => [['file' => $test, 'filter' => 'calls a Direct message "Saved"']],
    ],
    [
        'id' => 'IB-M14',
        'name' => 'every guest counts as having a guest account',
        'file' => 'app/Livewire/Operator/OperatorInbox.php',
        'old' => '            \'has_account\' => $guest !== null && $guest->password !== null,',
        'new' => '            \'has_account\' => true,',
        'tests' => [['file' => $test, 'filter' => 'calls a Direct message "Saved"']],
    ],
    [
        'id' => 'IB-M15',
        'name' => 'the picker says "In-house" of an arrival not checked in again',
        'file' => 'app/Livewire/Operator/OperatorInbox.php',
        'old' => '            $when = StayLabel::for($reservation, $today, \'j M\');',
        'new' => '            $when = $in->toDateString() > $todayStr ? \'Arrives \'.$in->format(\'j M\') : ($outDate->toDateString() < $todayStr || (string) $reservation->status === \'checked_out\' ? \'Checked out \'.$outDate->format(\'j M\') : \'In-house\');',
        'tests' => [['file' => $test, 'filter' => 'says where each guest’s stay stands by the Guests list’s own rule']],
    ],
    [
        'id' => 'IB-M16',
        'name' => 'a guest checked in is not "In house"',
        'file' => 'app/Support/Reservations/StayLabel.php',
        'old' => '            $own === ReservationStage::IN_HOUSE => \'In house\',
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'says where each guest’s stay stands by the Guests list’s own rule']],
    ],
    [
        'id' => 'IB-M17',
        'name' => 'the conversation search is a bare input again',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => 'class="vy-gl-search vy-ibx-search" role="search"',
        'new' => 'class="vy-inbox-search-form" role="search"',
        'tests' => [['file' => $test, 'filter' => 'keeps the conversation search on the shared search box']],
    ],
    [
        'id' => 'IB-M18',
        'name' => 'the tabs go back to one row on a phone — "Closed" cut to "Clos" (seen live)',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '            .vy-inbox-tabs { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); width: 100%; max-width: none; }',
        'new' => '            .vy-inbox-tabs { width: 100%; max-width: none; }',
        'tests' => [['file' => $test, 'filter' => 'keeps every filter tab’s words on a phone']],
    ],
    [
        'id' => 'IB-M19',
        'name' => 'a label may not wrap — on the narrowest phones "Arriving today" runs past its cell',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '.vy-inbox-tabs > .vy-seg-opt { grid-column: span 2; white-space: normal; padding: 9px 6px; line-height: 1.25; }',
        'new' => '.vy-inbox-tabs > .vy-seg-opt { grid-column: span 2; white-space: nowrap; padding: 9px 6px; line-height: 1.25; }',
        'tests' => [['file' => $test, 'filter' => 'keeps every filter tab’s words on a phone']],
    ],
    [
        'id' => 'IB-M20',
        'name' => 'the second row loses its spans — a lone tab beside an empty cell',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '            .vy-inbox-tabs > .vy-seg-opt:nth-child(n + 4) { grid-column: span 3; }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'keeps every filter tab’s words on a phone']],
    ],
    [
        'id' => 'IB-M21',
        'name' => 'the tabs no longer get the toolbar’s whole line on a phone',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '            .vy-ibx-bar > .vy-left, .vy-ibx-bar > .vy-right { flex: 1 1 100%; }',
        'new' => '            .vy-ibx-bar > .vy-right { flex: 1 1 100%; }',
        'tests' => [['file' => $test, 'filter' => 'keeps every filter tab’s words on a phone']],
    ],
    [
        'id' => 'IB-M22',
        'name' => 'the tabs go back to an inline style with no phone rule of their own',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '<div class="vy-seg vy-inbox-tabs">',
        'new' => '<div class="vy-seg" style="max-width: 560px;">',
        'tests' => [['file' => $test, 'filter' => 'keeps every filter tab’s words on a phone']],
    ],
    [
        'id' => 'IB-M23',
        'name' => 'the filter shown is marked by colour only again',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => ' @if ($filter === $tabKey) aria-current="page" @endif',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'marks the filter it shows']],
    ],
    [
        'id' => 'IB-M24',
        'name' => 'every tab claims to be the one shown',
        'file' => 'resources/views/livewire/operator/operator-inbox.blade.php',
        'old' => '@if ($filter === $tabKey) aria-current="page" @endif',
        'new' => 'aria-current="page"',
        'tests' => [['file' => $test, 'filter' => 'marks the filter it shows']],
    ],
];
