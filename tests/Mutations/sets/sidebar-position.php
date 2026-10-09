<?php

declare(strict_types=1);

// Mutation set sidebar-position — the sidebar stays where it was on every page and reload, in all three shells (2026-10-09,
// his report: "upon the page refresh the sidebar is going up again … it shouldn't be moving"). One component after each
// sidebar, aimed at the box that scrolls; no sidebar animates, nor the owner portal's top bar (his go, the same day). Run with `php tests/Mutations/run.php --set=sidebar-position`.
// ⚠️ These prove the markup and the rules; the position itself is a browser fact, walked in all three realms.

$test = 'tests/Feature/SidebarPositionTest.php';
$keeper = 'resources/views/components/sidebar-position.blade.php';

return [
    [
        'id' => 'SP-M1',
        'name' => 'the operator sidebar loses its keeper (back to the top on every page)',
        'file' => 'resources/views/components/operator/shell.blade.php',
        'old' => "    <x-sidebar-position for=\".vy-side\" key=\"operator\" />\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'keeps the operator sidebar in place']],
    ],
    [
        'id' => 'SP-M2',
        'name' => 'the owner keeper aims at the sidebar, not the menu that scrolls (restores nothing)',
        'file' => 'resources/views/components/owner/shell.blade.php',
        'old' => '<x-sidebar-position for=".vy-side .vy-nav" key="owner" />',
        'new' => '<x-sidebar-position for=".vy-side" key="owner" />',
        'tests' => [['file' => $test, 'filter' => 'keeps the owner portal menu in place']],
    ],
    [
        'id' => 'SP-M3',
        'name' => 'the admin console sidebar loses its keeper',
        'file' => 'resources/views/components/admin/shell.blade.php',
        'old' => "        <x-sidebar-position for=\".ad-side\" key=\"admin\" />\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'keeps the admin console sidebar in place']],
    ],
    [
        'id' => 'SP-M4',
        'name' => 'scroll anchoring back on (half a pixel of creep a page)',
        'file' => $keeper,
        'old' => "        side.style.overflowAnchor = 'none';\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'runs before the page is drawn']],
    ],
    [
        'id' => 'SP-M5',
        'name' => 'the remembered value rounded again (a pixel of creep a page)',
        'file' => $keeper,
        'old' => 'store.setItem(KEY, String(side.scrollTop))',
        'new' => 'store.setItem(KEY, String(Math.round(side.scrollTop)))',
        'tests' => [['file' => $test, 'filter' => 'runs before the page is drawn']],
    ],
    [
        'id' => 'SP-M6',
        'name' => 'the current item is no longer brought into view (a direct link opens with it off screen)',
        'file' => $keeper,
        'old' => '            if (c.top < s.top || c.bottom > s.bottom) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $test, 'filter' => 'runs before the page is drawn']],
    ],
    [
        'id' => 'SP-M7',
        'name' => 'the keeper runs after the first paint (a module script — the jump comes back)',
        'file' => $keeper,
        'old' => "@props(['for', 'key'])\n<script>",
        'new' => "@props(['for', 'key'])\n<script type=\"module\">",
        'tests' => [['file' => $test, 'filter' => 'keeps the operator sidebar in place']],
    ],
    [
        'id' => 'SP-M8',
        'name' => 'storage read unguarded (a private window throws and the page loses the keeper)',
        'file' => $keeper,
        'old' => '        try { store = window.sessionStorage; } catch (e) { store = null; }',
        'new' => '        store = window.sessionStorage;',
        'tests' => [['file' => $test, 'filter' => 'runs before the page is drawn']],
    ],
    [
        'id' => 'SP-M9',
        'name' => 'the operator sidebar slides in on every page again',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => "            overflow-y: auto;\n            /* No entrance animation: the sidebar is the same on every page, so it stays still (2026-10-09, his report). */\n        }\n        .vy-brand {",
        'new' => "            overflow-y: auto;\n            animation: vyFadeUp .45s cubic-bezier(.22, .61, .36, 1) both;\n        }\n        .vy-brand {",
        'tests' => [['file' => $test, 'filter' => 'animates no sidebar']],
    ],
    [
        'id' => 'SP-M10',
        'name' => 'the owner sidebar slides in on every page again',
        'file' => 'resources/views/components/layouts/owner.blade.php',
        'old' => "            overflow-y: auto;\n            /* No entrance animation: the sidebar is the same on every page, so it stays still (2026-10-09, his report). */\n        }",
        'new' => "            overflow-y: auto;\n            animation: vyFadeUp .45s cubic-bezier(.22, .61, .36, 1) both;\n        }",
        'tests' => [['file' => $test, 'filter' => 'animates no sidebar']],
    ],
    [
        'id' => 'SP-M11',
        'name' => 'the admin sidebar slides in on every page again',
        'file' => 'resources/views/components/admin/shell.blade.php',
        'old' => ' overflow-y: auto; } /* No entrance animation',
        'new' => ' overflow-y: auto; animation: adFadeUp .45s cubic-bezier(.22, .61, .36, 1) both; } /* No entrance animation',
        'tests' => [['file' => $test, 'filter' => 'animates no sidebar']],
    ],
    [
        'id' => 'SP-M12',
        'name' => 'the owner top bar slides in on every page again (D-OWNER-TOPBAR-FADE)',
        'file' => 'resources/views/components/layouts/owner.blade.php',
        'old' => 'justify-content: space-between; gap: 16px; margin-bottom: 24px; }',
        'new' => 'justify-content: space-between; gap: 16px; margin-bottom: 24px; animation: vyFadeUp .45s cubic-bezier(.22, .61, .36, 1) .06s both; }',
        'tests' => [['file' => $test, 'filter' => 'animates no top bar']],
    ],
];
