<?php

declare(strict_types=1);

// Mutation set maintenance-board — the Maintenance board rebuilt (2026-09-29): centred dialogs and a drawer on the right,
// a table that sorts by the values it shows, page sizes, chips that count, a row menu that offers only what the ticket's
// state and the viewer's permission allow, a real Edit and an honest Cancel that come back to the list, the work order
// page's two dialogs made real, and Alpine's string :style that erased their controls.
// Run with `php tests/Mutations/run.php --set=maintenance-board`.

$t = 'tests/Feature/MaintenanceBoardRedesignTest.php';
$bv = 'tests/Feature/MaintenanceBoardViewTest.php';
$x = 'tests/Feature/XShowInlineDisplayContractTest.php';
$st = 'tests/Feature/AlpineStyleBindingContractTest.php';
$k = 'tests/Feature/KeyboardReachabilityContractTest.php';
$c = 'tests/Feature/DataConditionalContrastTest.php';
$list = 'app/Livewire/MaintenanceList.php';
$params = 'app/Support/Maintenance/MaintenanceListParams.php';
$svc = 'app/Services/MaintenanceService.php';
$ctl = 'app/Http/Controllers/Operator/MaintenanceActionController.php';
$view = 'resources/views/livewire/maintenance-list.blade.php';
$detail = 'resources/views/livewire/maintenance-ticket-detail.blade.php';
$shell = 'resources/views/components/layouts/operator.blade.php';

return [
    [
        'id' => 'MB-M1',
        'name' => 'the sort is not checked against the list (any word reaches the ordering)',
        'file' => $params,
        'old' => "        \$sort = is_string(\$sort) && array_key_exists(\$sort, self::SORTS) ? \$sort : 'priority';",
        'new' => "        \$sort = is_string(\$sort) ? \$sort : 'priority';",
        'tests' => [['file' => $t, 'filter' => 'refuses a sort']],
    ],
    [
        'id' => 'MB-M2',
        'name' => 'time to target sorts by the reported time, not by the deadline the column shows',
        'file' => $list,
        'old' => "                    default => \$sign * (\$a['deadline_ts'] <=> \$b['deadline_ts']),",
        'new' => "                    default => \$sign * (\$a['opened_ts'] <=> \$b['opened_ts']),",
        'tests' => [['file' => $t, 'filter' => 'sorts by priority, time to target']],
    ],
    [
        'id' => 'MB-M3',
        // ⚠️ Both branches flip together. The first draft flipped only the first, which makes an inconsistent comparator
        //    (a before b AND b before a) whose result depends on the order the sort compares items — it stayed green
        //    without ever putting a no-deadline row first. That was the mutation's fault, not the test's.
        'name' => 'a work order with no deadline comes FIRST when the target sort is reversed',
        'file' => $list,
        'old' => "                    \$a['deadline_ts'] === null => 1,\n                    \$b['deadline_ts'] === null => -1,",
        'new' => "                    \$a['deadline_ts'] === null => \$sign,\n                    \$b['deadline_ts'] === null => -\$sign,",
        'tests' => [['file' => $t, 'filter' => 'sorts by priority, time to target']],
    ],
    [
        'id' => 'MB-M4',
        'name' => 'ties fall back to the oldest first, not the newest (the order the board always had)',
        'file' => $list,
        'old' => "                : ([\$rank(\$a), \$b['opened_ts'], \$a['id']] <=> [\$rank(\$b), \$a['opened_ts'], \$b['id']]);",
        'new' => "                : ([\$rank(\$a), \$a['opened_ts'], \$a['id']] <=> [\$rank(\$b), \$b['opened_ts'], \$b['id']]);",
        'tests' => [['file' => $t, 'filter' => 'sorts by priority, time to target']],
    ],
    [
        'id' => 'MB-M5',
        'name' => 'a closed work order is offered writes again (the old board offered Assign on a resolved one)',
        'file' => $list,
        'old' => "        if (! \$canWrite || ! in_array(\$status, ['open', 'in_progress'], true)) {",
        'new' => '        if (! $canWrite) {',
        'tests' => [['file' => $t, 'filter' => 'offers each action only where']],
    ],
    [
        'id' => 'MB-M6',
        'name' => 'a role without maintenance.manage is offered the writes and their dialogs',
        'file' => $list,
        'old' => '        $canWrite = $canManage && ! app(OperatorWorkspace::class)->isReadOnly();',
        'new' => '        $canWrite = ! app(OperatorWorkspace::class)->isReadOnly();',
        'tests' => [['file' => $t, 'filter' => 'offers each action only where']],
    ],
    [
        'id' => 'MB-M7',
        'name' => 'a resolved or cancelled work order can be edited (its title is already on the owner\'s expense line)',
        'file' => $svc,
        'old' => "        \$this->assertActive(\$ticket, 'edit');",
        'new' => '        // the guard is gone',
        'tests' => [['file' => $t, 'filter' => 'refuses to edit a resolved']],
    ],
    [
        'id' => 'MB-M8',
        'name' => 'Edit forgets the property allow-list — a manager scoped to one property edits another\'s work order',
        'file' => 'app/Support/Property/PropertyScope.php',   // DEC-j (2026-11-11): the rule moved into the ONE helper every maintenance write shares
        'old' => '        return MaintenanceTicket::query()->whereHas(\'unit\', fn (Builder $u) => $u->whereIn(\'property_id\', $ids));',
        'new' => '        return MaintenanceTicket::query();',
        'tests' => [['file' => $t, 'filter' => 'gates Edit like every']],
    ],
    [
        'id' => 'MB-M9',
        'name' => 'the audit copies the typed title and description instead of naming the fields',
        'file' => $svc,
        'old' => '        $after = [\'fields\' => $changed];',
        'new' => '        $after = [\'fields\' => $changed] + $next;',
        'tests' => [['file' => $t, 'filter' => 'edits an open work order from the list']],
    ],
    [
        'id' => 'MB-M10',
        'name' => 'the way back is the posted text itself, not a Maintenance URL rebuilt from an allow-list',
        'file' => $ctl,
        'old' => "            ? redirect()->to(MaintenanceListParams::returnUrl(\$request->input('return_query')))",
        'new' => "            ? redirect()->to((string) \$request->input('return_query'))",
        'tests' => [['file' => $t, 'filter' => 'never sends you anywhere but']],
    ],
    [
        'id' => 'MB-M11',
        'name' => 'an action started from the list lands on the work order page instead of the list as you had it',
        'file' => $ctl,
        'old' => "        return \$request->input('return') === 'list'",
        'new' => "        return \$request->input('return') === 'never'",
        'tests' => [['file' => $t, 'filter' => 'edits an open work order from the list'], ['file' => $t, 'filter' => 'cancels from the list']],
    ],
    [
        'id' => 'MB-M12',
        'name' => 'the drawer loses its modifier and opens centred like a dialog',
        'file' => $view,
        'old' => '<div class="vy-modal-backdrop vy-drawer-backdrop" x-cloak x-show="detail"',
        'new' => '<div class="vy-modal-backdrop" x-cloak x-show="detail"',
        'tests' => [['file' => $t, 'filter' => 'centres every board dialog']],
    ],
    [
        'id' => 'MB-M13',
        'name' => 'the drawer sits on the LEFT again',
        'file' => $shell,
        'old' => '        .vy-modal-backdrop.vy-drawer-backdrop { padding: 0; place-items: stretch; justify-items: end; }',
        'new' => '        .vy-modal-backdrop.vy-drawer-backdrop { padding: 0; place-items: stretch; justify-items: start; }',
        'tests' => [['file' => $t, 'filter' => 'centres every board dialog']],
    ],
    [
        'id' => 'MB-M14',
        'name' => 'the work order page\'s Mark resolved dialog centres itself with an inline display beside x-show again',
        'file' => $detail,
        'old' => '<div class="vy-modal-backdrop" x-show="mr" x-cloak x-transition.opacity',
        'new' => '<div x-show="mr" x-cloak style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;" x-transition.opacity',
        'tests' => [['file' => $x, 'filter' => 'leaves no element on the Guests'], ['file' => $t, 'filter' => 'centres every board dialog']],
    ],
    [
        'id' => 'MB-M15',
        'name' => 'a switch on the work order page binds a STRING :style again and loses its size (measured 16×6 px)',
        'file' => $detail,
        'old' => ":style=\"{ background: ret ? 'linear-gradient(140deg,#E8703A,#C2410C)' : 'rgba(120,70,40,.18)' }\"",
        'new' => ":style=\"ret ? 'background:linear-gradient(140deg,#E8703A,#C2410C)' : 'background:rgba(120,70,40,.18)'\"",
        'tests' => [['file' => $st, 'filter' => 'leaves no string :style beside a static style']],
    ],
    [
        'id' => 'MB-M16',
        'name' => 'the chip counts ignore the search, so a chip promises more than clicking it shows',
        'file' => $list,
        'old' => "                'count' => count(array_filter(\$cards, fn (array \$c): bool => \$this->matches(\$c, \$filter, \$state['q']))),",
        'new' => "                'count' => count(array_filter(\$cards, fn (array \$c): bool => \$this->matches(\$c, \$filter, ''))),",
        'tests' => [['file' => $t, 'filter' => 'filters with chips that count']],
    ],
    [
        'id' => 'MB-M17',
        'name' => 'the pager goes back inside the table body (not valid markup)',
        'file' => $view,
        'old' => '                                    </tbody>',
        'new' => "                                        <tr><td><nav data-gl-foot>pages</nav></td></tr>\n                                    </tbody>",
        'tests' => [['file' => $t, 'filter' => 'pages 25, 50 or 100']],
    ],
    [
        'id' => 'MB-M18',
        'name' => 'the hero says "1 units blocked" again',
        'file' => $view,
        // (I18N-01: the sentence is one plural key now — the mutation forces its plural form whatever the count)
        'old' => "{!! trans_choice('livewire/maintenance-list.oos_blocked', \$oosHero['count'], ['count' => e(\$oosHero['count'])]) !!}",
        'new' => "{!! trans_choice('livewire/maintenance-list.oos_blocked', 2, ['count' => e(\$oosHero['count'])]) !!}",
        'tests' => [['file' => $t, 'filter' => 'unit blocked'], ['file' => $bv, 'filter' => 'Unit OOS badge']],
    ],
    [
        'id' => 'MB-M19',
        'name' => 'any page size is accepted (37, 5000…)',
        'file' => $params,
        'old' => '        if ($per !== null && $per !== self::PER_PAGE && in_array($per, self::PER_PAGE_OPTIONS, true)) {',
        'new' => '        if ($per !== null && $per !== self::PER_PAGE) {',
        'tests' => [['file' => $t, 'filter' => 'pages 25, 50 or 100']],
    ],
    [
        'id' => 'MB-M20',
        'name' => 'a refused edit no longer reopens with what was typed',
        'file' => $list,
        'old' => "        \$id = old('_ticket');",
        'new' => '        $id = null;',
        'tests' => [['file' => $t, 'filter' => 'reopens with what was typed']],
    ],
    [
        'id' => 'MB-M21',
        'name' => 'the write dialogs are on the page for a role that cannot use them',
        'file' => $view,
        'old' => "            @if (\$canWrite)\n                {{-- ── NEW WORK ORDER",
        'new' => "            @if (true)\n                {{-- ── NEW WORK ORDER",
        'tests' => [['file' => $t, 'filter' => 'offers each action only where']],
    ],
    [
        'id' => 'MB-M22',
        'name' => 'a table row answers a click again, coupling the select checkbox to opening the work order',
        'file' => $view,
        'old' => "<tr data-gl-id=\"{{ \$row['id'] }}\" data-mt-actions=",
        'new' => "<tr data-gl-id=\"{{ \$row['id'] }}\" @click=\"openDetail('{{ \$row['id'] }}')\" data-mt-actions=",
        'tests' => [['file' => $k, 'filter' => 'keeps the work-order checkbox independent']],
    ],
    [
        'id' => 'MB-M23',
        'name' => 'a breached target is written in the colour that failed AA (#B4400F, 4.12:1)',
        'file' => $view,
        'old' => "\$row['sla']['breached'] ? 'var(--vy-accent-text)' :",
        'new' => "\$row['sla']['breached'] ? '#B4400F' :",
        'tests' => [['file' => $c, 'filter' => 'maintenance SLA line clears AA']],
    ],
    [
        'id' => 'MB-M24',
        'name' => 'Edit accepts a category or priority outside the real lists',
        'file' => $ctl,
        'old' => "            'category' => ['required', Rule::in(self::CATEGORIES)],\n            'priority' => ['required', Rule::in(self::PRIORITIES)],\n        ]);",
        'new' => "            'category' => ['required', 'string'],\n            'priority' => ['required', 'string'],\n        ]);",
        'tests' => [['file' => $t, 'filter' => 'refuses a category or priority it does not know']],
    ],
    [
        'id' => 'MB-M25',
        'name' => 'the table frame stops scrolling sideways by window width alone (clipped two columns at 1180 px)',
        'file' => $shell,
        'old' => '            .vy-gl-scroll.is-roomy { overflow: visible; }',
        'new' => '            .vy-gl-scroll { overflow: visible; }',
        'tests' => [['file' => $t, 'filter' => 'measured to fit']],
    ],
    [
        'id' => 'MB-M26',
        'name' => 'the board\'s Mark resolved stops telling whoever reported it (the server reads a missing notify as no)',
        'file' => $view,
        'old' => '<input type="checkbox" name="notify" value="1" x-model="notify">',
        'new' => '<input type="checkbox" value="1" x-model="notify">',
        'tests' => [['file' => $t, 'filter' => 'tells whoever reported it']],
    ],
];
