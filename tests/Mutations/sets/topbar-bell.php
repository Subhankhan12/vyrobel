<?php

declare(strict_types=1);

// Mutation set topbar-bell — the notification bell's unread badge on the circle's outer corner, never over the bell, and the
// top bar spaced so it never crowds the account chip (2026-10-09, his report: "the notification icon is completely messed
// because there is no proper spacing … make it proper"). Run with `php tests/Mutations/run.php --set=topbar-bell`.
// ⚠️ These prove the markup and the rules; the look is a browser fact, walked in the owner, operator and guest realms.

$test = 'tests/Feature/NotificationBellAppearanceTest.php';
$glyph = 'resources/views/components/notifications/bell-glyph.blade.php';

return [
    [
        'id' => 'TB-M1',
        'name' => 'the badge goes back inside the circle (a "9+" covers the bell again — his report)',
        'file' => $glyph,
        'old' => 'top:var(--vy-bell-badge-top,-5px);right:var(--vy-bell-badge-right,-6px)',
        'new' => 'top:var(--vy-bell-badge-top,9px);right:var(--vy-bell-badge-right,10px)',
        'tests' => [['file' => $test, 'filter' => 'outer corner']],
    ],
    [
        'id' => 'TB-M2',
        'name' => 'the badge takes the click meant for the bell',
        'file' => $glyph,
        'old' => ';pointer-events:none">',
        'new' => '">',
        'tests' => [['file' => $test, 'filter' => 'outer corner']],
    ],
    [
        'id' => 'TB-M3',
        'name' => 'the badge loses its light ring',
        'file' => $glyph,
        'old' => 'border:2px solid #FFF8F2',
        'new' => 'border:1.5px solid #FBEEE3',
        'tests' => [['file' => $test, 'filter' => 'outer corner']],
    ],
    [
        'id' => 'TB-M4',
        'name' => 'the operator shell tunes the badge back inside its 38px bell',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => ' color: var(--vy-ink); transition: transform .14s ease; }',
        'new' => ' color: var(--vy-ink); transition: transform .14s ease; --vy-bell-badge-top: 8px; --vy-bell-badge-right: 9px; }',
        'tests' => [['file' => $test, 'filter' => 'lets no shell move the badge back']],
    ],
    [
        'id' => 'TB-M5',
        'name' => 'the guest shell tunes the badge back inside its 36px bell',
        'file' => 'resources/views/components/guest/shell.blade.php',
        'old' => "            color: var(--vy-ink); transition: transform .14s ease;\n        }\n        .vy-bell:hover",
        'new' => "            color: var(--vy-ink); transition: transform .14s ease;\n            --vy-bell-badge-top: 7px; --vy-bell-badge-right: 8px;\n        }\n        .vy-bell:hover",
        'tests' => [['file' => $test, 'filter' => 'lets no shell move the badge back']],
    ],
    [
        'id' => 'TB-M6',
        'name' => 'the owner top bar is cramped again (10px)',
        'file' => 'resources/views/components/layouts/owner.blade.php',
        'old' => '        .vy-topbar-actions { display: flex; align-items: center; gap: 14px; }',
        'new' => '        .vy-topbar-actions { display: flex; align-items: center; gap: 10px; }',
        'tests' => [['file' => $test, 'filter' => 'spaces the top bar']],
    ],
    [
        'id' => 'TB-M7',
        'name' => 'the operator top bar is cramped again (12px)',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-topbar-actions { display: flex; align-items: center; gap: 14px; }',
        'new' => '        .vy-topbar-actions { display: flex; align-items: center; gap: 12px; }',
        'tests' => [['file' => $test, 'filter' => 'spaces the top bar']],
    ],
];
