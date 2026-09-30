<?php

declare(strict_types=1);

// Mutation set guests-list — the Guests page rebuilt (2026-09-29): centred dialogs, sorting, page sizes, links that keep
// every setting, row Edit and Erase that come back to the list, and the see-through dialog panel made readable.
// Run with `php tests/Mutations/run.php --set=guests-list`.

$t = 'tests/Feature/GuestsListRedesignTest.php';
$x = 'tests/Feature/XShowInlineDisplayContractTest.php';
$c = 'tests/Feature/DataConditionalContrastTest.php';
$list = 'app/Livewire/Operator/GuestsList.php';
$params = 'app/Support/Guest/GuestListParams.php';
$ctl = 'app/Http/Controllers/Operator/GuestActionController.php';
$view = 'resources/views/livewire/operator/guests-list.blade.php';

return [
    [
        'id' => 'GL-M1',
        'name' => 'the sort is not checked against the list (any column name reaches the query)',
        'file' => $params,
        'old' => "        \$sort = is_string(\$sort) && array_key_exists(\$sort, self::SORTS) ? \$sort : 'name';",
        'new' => "        \$sort = is_string(\$sort) ? \$sort : 'name';",
        'tests' => [['file' => $t, 'filter' => 'refuses a sort']],
    ],
    [
        'id' => 'GL-M2',
        'name' => 'Last stay sorts by the check-in, not by the date the column shows',
        'file' => $list,
        'old' => "->selectRaw('CASE WHEN reservations.check_in > ? THEN reservations.check_in ELSE reservations.check_out END', [\$t])",
        'new' => "->selectRaw('CASE WHEN reservations.check_in > ? THEN reservations.check_in ELSE reservations.check_in END', [\$t])",
        'tests' => [['file' => $t, 'filter' => 'sorts by guest']],
    ],
    [
        'id' => 'GL-M3',
        'name' => 'guests who never stayed come FIRST in an oldest-first list',
        'file' => $list,
        'old' => "        \$never = \$dir === 'asc' ? '9999-12-31' : '0001-01-01';",
        'new' => "        \$never = '0001-01-01';",
        'tests' => [['file' => $t, 'filter' => 'sorts by guest']],
    ],
    [
        'id' => 'GL-M4',
        'name' => 'a guest in house does not sort at today',
        'file' => $list,
        'old' => '            [...$inHouse->getBindings(), $t, ...$latest->getBindings(), $never],',
        'new' => "            [...\$inHouse->getBindings(), '2000-01-01', ...\$latest->getBindings(), \$never],",
        'tests' => [['file' => $t, 'filter' => 'sorts by guest']],
    ],
    [
        'id' => 'GL-M5',
        'name' => 'the Stays sort counts cancelled and no-show bookings',
        'file' => $list,
        'old' => "                    ->whereNotIn('status', ReservationStage::CANCELLED_STATUSES)])",
        'new' => "                    ->whereNotNull('status')])",
        'tests' => [['file' => $t, 'filter' => 'sorts by guest']],
    ],
    [
        'id' => 'GL-M6',
        'name' => 'Stays starts with the fewest first',
        'file' => $params,
        'old' => "    public const SORTS = ['name' => 'asc', 'stays' => 'desc', 'last_stay' => 'desc'];",
        'new' => "    public const SORTS = ['name' => 'asc', 'stays' => 'asc', 'last_stay' => 'desc'];",
        'tests' => [['file' => $t, 'filter' => 'sorts by guest']],
    ],
    [
        'id' => 'GL-M7',
        'name' => 'any page size is accepted (the URL can ask for any number of rows)',
        'file' => $params,
        'old' => '        if ($per !== null && $per !== self::PER_PAGE && in_array($per, self::PER_PAGE_OPTIONS, true)) {',
        'new' => '        if ($per !== null && $per !== self::PER_PAGE && $per > 0) {',
        'tests' => [['file' => $t, 'filter' => 'pages 25']],
    ],
    [
        'id' => 'GL-M8',
        'name' => 'a filter, sort or page-size link drops the other settings',
        'file' => $list,
        'old' => '        return GuestListParams::url(array_merge($this->params($state), $changes));',
        'new' => '        return GuestListParams::url($changes);',
        'tests' => [['file' => $t, 'filter' => 'keeps every setting on every link']],
    ],
    [
        'id' => 'GL-M9',
        'name' => 'the way back uses the query string as sent (not rebuilt from the allow-list)',
        'file' => $params,
        'old' => '        return self::url($raw);',
        'new' => "        return route('app.guests').(is_string(\$query) && \$query !== '' ? '?'.\$query : '');",
        'tests' => [['file' => $t, 'filter' => 'never sends you anywhere but'], ['file' => $t, 'filter' => 'edits a guest from the list']],
    ],
    [
        'id' => 'GL-M10',
        'name' => 'an edit from the list lands on the profile instead of back on the list',
        'file' => $ctl,
        'old' => "        if (\$request->input('return') === 'list') {",
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'edits a guest from the list']],
    ],
    [
        'id' => 'GL-M11',
        'name' => 'an edit from the list wipes the guest\'s stay preferences',
        'file' => $ctl,
        'old' => "        if (\$request->boolean('keep_preferences')) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'stay preferences when the list edits']],
    ],
    [
        'id' => 'GL-M12',
        'name' => 'an edit the server refuses sends you to the profile instead of back to the list',
        'file' => $ctl,
        'old' => "            return \$this->done(\$request, \$model->id, 'guest_error', 'That guest has been anonymized — their details cannot be edited.');",
        'new' => "            return \$this->back(\$model->id, 'guest_error', 'That guest has been anonymized — their details cannot be edited.');",
        'tests' => [['file' => $t, 'filter' => 'refused edit from the list comes back']],
    ],
    [
        'id' => 'GL-M13',
        'name' => 'Add and Edit are offered to a role that may only view guests',
        'file' => $view,
        'old' => "            @can('guests.manage')\n                {{-- ── ADD GUEST — a real create",
        'new' => "            @can('guests.view')\n                {{-- ── ADD GUEST — a real create",
        'tests' => [['file' => $t, 'filter' => 'offers Edit only with']],
    ],
    [
        'id' => 'GL-M14',
        'name' => 'a row\'s Erase is offered to a role without guests.erase',
        'file' => $view,
        'old' => "            @can('guests.erase')\n                {{-- ── ERASE (one guest, from its row)",
        'new' => "            @can('guests.manage')\n                {{-- ── ERASE (one guest, from its row)",
        'tests' => [['file' => $t, 'filter' => 'offers Edit only with']],
    ],
    [
        'id' => 'GL-M15',
        'name' => 'the Add guest dialog centres itself with an inline display beside x-show again (the reported bug)',
        'file' => $view,
        'old' => '<div class="vy-modal-backdrop" x-cloak x-show="addOpen" x-transition.opacity',
        'new' => '<div x-cloak x-show="addOpen" style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;" x-transition.opacity',
        'tests' => [['file' => $x, 'filter' => 'leaves no element on the Guests'], ['file' => $t, 'filter' => 'centres every dialog']],
    ],
    [
        'id' => 'GL-M16',
        'name' => 'the pager goes back inside the table body',
        'file' => $view,
        'old' => '                                </tbody>',
        'new' => "                                <tr><td><nav data-gl-foot>pages</nav></td></tr>\n                                </tbody>",
        'tests' => [['file' => $t, 'filter' => 'real footer']],
    ],
    [
        'id' => 'GL-M17',
        'name' => 'the search box promises phone and email search it cannot do',
        'file' => $view,
        'old' => 'placeholder="Search name, city or exact email"',
        'new' => 'placeholder="Search by name, email or phone…"',
        'tests' => [['file' => $t, 'filter' => 'says honestly what search']],
    ],
    [
        'id' => 'GL-M18',
        'name' => 'an edit the server refuses does not reopen with what was typed',
        'file' => $list,
        'old' => "                'retry' => \$canManage ? \$this->editRetry() : null,",
        'new' => "                'retry' => null,",
        'tests' => [['file' => $t, 'filter' => 'reopens with what was typed']],
    ],
    [
        'id' => 'GL-M19',
        'name' => 'the Add guest panel is the see-through glass again (labels below AA)',
        'file' => $view,
        'old' => '<div class="vy-modal vy-modal-card vy-modal--wide" x-show="addOpen"',
        'new' => '<div class="vy-modal vy-modal--wide" x-show="addOpen"',
        'tests' => [['file' => $c, 'filter' => 'every modal card carries the class']],
    ],
    [
        'id' => 'GL-M20',
        'name' => 'the rule that makes a modal card near-opaque on the shared panel is gone',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-modal.vy-modal-card { background: rgba(255, 252, 248, 0.94); }',
        'new' => '        .vy-modal.vy-modal-card { }',
        'tests' => [['file' => $c, 'filter' => 'every modal card carries the class']],
    ],
    [
        'id' => 'GL-M21',
        'name' => 'the Portfolio Add panel is the see-through glass again (measured 3.52 and 3.25)',
        'file' => 'resources/views/livewire/operator/portfolio.blade.php',
        'old' => '<div class="vy-modal vy-modal-card" x-show="addOpen"',
        'new' => '<div class="vy-modal" x-show="addOpen"',
        'tests' => [['file' => $c, 'filter' => 'every modal card carries the class']],
    ],
    [
        'id' => 'GL-M22',
        'name' => 'the list\'s Edit form stops telling the server to keep the preferences',
        'file' => $view,
        'old' => "                            <input type=\"hidden\" name=\"keep_preferences\" value=\"1\">\n",
        'new' => '',
        'tests' => [['file' => $t, 'filter' => 'tells the server to keep']],
    ],
    [
        'id' => 'GL-M23',
        'name' => 'the profile\'s Edit dialog centres itself with an inline display beside x-show again',
        'file' => 'resources/views/livewire/operator/guest-profile.blade.php',
        'old' => '<div class="vy-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity',
        'new' => '<div x-cloak x-show="editOpen" style="position: fixed; inset: 0; z-index: 50; display: flex;" x-transition.opacity',
        'tests' => [['file' => $x, 'filter' => 'leaves no element on the Guests']],
    ],
    [
        'id' => 'GL-M24',
        'name' => 'another screen gains the x-show + inline display pattern (the ceiling must hold)',
        'file' => 'resources/views/livewire/operator/portfolio.blade.php',
        'old' => '<div class="vy-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity',
        'new' => '<div class="vy-modal-backdrop" x-cloak x-show="editOpen" style="display: grid;" x-transition.opacity',
        'tests' => [['file' => $x, 'filter' => 'does not let the pattern grow']],
    ],
];
