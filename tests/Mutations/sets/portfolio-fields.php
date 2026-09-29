<?php

declare(strict_types=1);

// Mutation set portfolio-fields — the Portfolio's "Open" and its dialogs' fields (2026-09-29): Open takes you INTO the
// property (its Dashboard, saying so) and can go nowhere else; the top-bar switcher keeps you where you are.
// Run with `php tests/Mutations/run.php --set=portfolio-fields`.

$t = 'tests/Feature/PortfolioOpenAndFieldsTest.php';
$ctl = 'app/Http/Controllers/Operator/PropertySwitchController.php';

return [
    [
        'id' => 'PF-M1',
        'name' => 'Open comes back to the Portfolio again ("Open does nothing")',
        'file' => $ctl,
        'old' => "            return redirect()->route(\$destination)->with('property_notice',",
        'new' => "            return redirect()->back()->with('property_notice',",
        'tests' => [['file' => $t, 'filter' => 'Open takes you INTO']],
    ],
    [
        'id' => 'PF-M2',
        'name' => 'any destination is accepted (the fixed list is not enforced)',
        'file' => $ctl,
        'old' => "            'open' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::DESTINATIONS))],",
        'new' => "            'open' => ['nullable', 'string'],",
        'tests' => [['file' => $t, 'filter' => 'Dashboard ONLY']],
    ],
    [
        'id' => 'PF-M3',
        'name' => 'the card\'s Open no longer asks to go into the property',
        'file' => 'resources/views/livewire/operator/portfolio.blade.php',
        'old' => "                                        <input type=\"hidden\" name=\"open\" value=\"dashboard\">\n",
        'new' => '',
        'tests' => [['file' => $t, 'filter' => 'Open takes you INTO']],
    ],
    [
        'id' => 'PF-M4',
        'name' => 'the Dashboard no longer says which property you are now working in',
        'file' => 'resources/views/livewire/operator/dashboard.blade.php',
        'old' => "        @if (session('property_notice'))",
        'new' => '        @if (false)',
        'tests' => [['file' => $t, 'filter' => 'Open takes you INTO']],
    ],
    [
        'id' => 'PF-M5',
        'name' => 'the switcher goes to the Dashboard even without being asked (the top bar loses your place)',
        'file' => $ctl,
        'old' => "        \$destination = self::DESTINATIONS[(string) (\$data['open'] ?? '')] ?? null;",
        'new' => "        \$destination = 'app.dashboard';",
        'tests' => [['file' => $t, 'filter' => 'top-bar switcher']],
    ],
    [
        'id' => 'PF-M6',
        'name' => 'the time zone list loses its keyboard path (Enter no longer picks) — its keyboard exemption must fall',
        'file' => 'resources/views/components/operator/combobox.blade.php',
        'old' => '@keydown.enter.prevent="pick(shown[active])"',
        'new' => '@keydown.enter.prevent=""',
        'tests' => [['file' => 'tests/Feature/KeyboardReachabilityContractTest.php', 'filter' => 'has no click handler a keyboard cannot reach']],
    ],
];
