<?php

declare(strict_types=1);

// Mutation set select-engine — the one drop-down engine, in every shell (2026-10-10, his approval: "the same sort of select and
// same sort of drop down in the entire application, everywhere"). The native select stays in charge; the engine skins it, opens
// the Vyrobel panel in place of the operating system's list, keeps a dialog's trap and Esc intact, and gives the menus the same
// panel. Run with `php tests/Mutations/run.php --set=select-engine`. ⚠️ These prove the shells, the rules and the source; the
// behaviour is a browser fact, walked across the six realms.

$test = 'tests/Feature/SelectEngineTest.php';
$engine = 'resources/views/components/vy-selects.blade.php';

return [
    [
        'id' => 'SE-M1',
        'name' => 'the owner portal loses the engine (its selects open the operating system\'s list again)',
        'file' => 'resources/views/components/layouts/owner.blade.php',
        'old' => "<x-vy-selects />\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'is included by every shell']],
    ],
    [
        'id' => 'SE-M2',
        'name' => 'a mouse press no longer stops the operating system\'s list',
        'file' => $engine,
        'old' => 'e.preventDefault();   // no operating-system list',
        'new' => '// no operating-system list',
        'tests' => [['file' => $test, 'filter' => 'leaves the native select in charge']],
    ],
    [
        'id' => 'SE-M3',
        'name' => 'a finger loses the phone\'s own picker (touch is intercepted too)',
        'file' => $engine,
        'old' => "if (eligible(t) && e.button === 0 && pointer === 'mouse') {",
        'new' => 'if (eligible(t) && e.button === 0) {',
        'tests' => [['file' => $test, 'filter' => 'leaves the native select in charge']],
    ],
    [
        'id' => 'SE-M4',
        'name' => 'a choice fires no change (x-model, the auto-submitting filters and onchange all go deaf)',
        'file' => $engine,
        'old' => "            s.dispatchEvent(new Event('change', { bubbles: true }));\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'leaves the native select in charge']],
    ],
    [
        'id' => 'SE-M5',
        'name' => 'Esc in the list also closes the dialog behind it',
        'file' => $engine,
        'old' => "case 'Escape': e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); close(true); break;",
        'new' => "case 'Escape': e.preventDefault(); close(true); break;",
        'tests' => [['file' => $test, 'filter' => 'leaves the native select in charge']],
    ],
    [
        'id' => 'SE-M6',
        'name' => 'the panel always goes to <body> (a dialog\'s trap and click-outside see it as outside)',
        'file' => $engine,
        'old' => 'return select.closest(\'[aria-modal="true"], [role="dialog"], dialog\') || D.body;',
        'new' => 'return D.body;',
        'tests' => [['file' => $test, 'filter' => 'places the panel inside a dialog']],
    ],
    [
        'id' => 'SE-M7',
        'name' => 'the containing block read from the moving panel (4px off mid-animation)',
        'file' => $engine,
        'old' => 'st.home.appendChild(probe); var o = probe.getBoundingClientRect(); probe.remove();',
        'new' => 'var o = p.getBoundingClientRect();',
        'tests' => [['file' => $test, 'filter' => 'places the panel inside a dialog']],
    ],
    [
        'id' => 'SE-M8',
        'name' => 'the choice centred from screen rects (short during the opening scale)',
        'file' => $engine,
        'old' => 'st.list.scrollTop = (row.offsetTop - st.list.offsetTop) - (st.list.clientHeight - row.offsetHeight) / 2;',
        'new' => 'var rr = row.getBoundingClientRect(), lr = st.list.getBoundingClientRect(); st.list.scrollTop += (rr.top - lr.top) - (st.list.clientHeight - rr.height) / 2;',
        'tests' => [['file' => $test, 'filter' => 'places the panel inside a dialog']],
    ],
    [
        'id' => 'SE-M9',
        'name' => 'a list of plain numbers gets a search box again',
        'file' => $engine,
        'old' => 'return rows.length > 8 && ! rows.every(function (r) { return /^\d+$/.test(r.label.trim()); });',
        'new' => 'return rows.length > 8;',
        'tests' => [['file' => $test, 'filter' => 'searches long lists']],
    ],
    [
        'id' => 'SE-M10',
        'name' => 'a field\'s own background shorthand erases the chevron again',
        'file' => $engine,
        'old' => 'background-repeat: no-repeat !important; background-position: right 12px center !important;',
        'new' => 'background-repeat: no-repeat; background-position: right 12px center;',
        'tests' => [['file' => $test, 'filter' => 'skins every closed select']],
    ],
    [
        'id' => 'SE-M11',
        'name' => 'the browser\'s blue focus ring comes back',
        'file' => $engine,
        'old' => ':where(select:not([data-native])):focus { outline: none;',
        'new' => ':where(select:not([data-native])):focus-within-nothing { outline: none;',
        'tests' => [['file' => $test, 'filter' => 'skins every closed select']],
    ],
    [
        'id' => 'SE-M12',
        'name' => 'the menus keep their old surfaces (two looks for one drop-down)',
        'file' => $engine,
        'old' => ':is(.vy-acct-menu, .vy-gl-menu, .vy-ov-menu, .vy-st-menu, .vy-rep-menu, [data-vy-bell] > [role="menu"]) {'."\n".'        background: rgba(255, 252, 248, .97) !important;',
        'new' => ':is(.vy-nothing) {'."\n".'        background: rgba(255, 252, 248, .97) !important;',
        'tests' => [['file' => $test, 'filter' => 'gives the drop-down menus the same panel']],
    ],
    [
        'id' => 'SE-M13',
        'name' => 'the next console card covers an open list again',
        'file' => $engine,
        'old' => '.ad-card:has(.ad-combo-trigger[aria-expanded="true"]) { position: relative; z-index: 5; }',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'gives the drop-down menus the same panel']],
    ],
];
