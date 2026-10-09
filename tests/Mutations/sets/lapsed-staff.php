<?php

declare(strict_types=1);

// Mutation set lapsed-staff — a lapsed workspace no longer traps its staff in a 403 loop (2026-10-09, his report:
// "I can't log into any of the roles … Back to dashboard is also not working"). Whoever holds `billing.manage` still
// gets the Paywall; everyone else gets a plain page with Sign out and their other workspaces; the switcher is let
// through; a background request gets a plain 403. Run with `php tests/Mutations/run.php --set=lapsed-staff`.

$test = 'tests/Feature/LapsedWorkspaceStaffTest.php';
$gate = 'app/Http/Middleware/EnsureSubscriptionActive.php';
$others = 'app/Support/Platform/OtherWorkspaces.php';

return [
    [
        'id' => 'LS-M1',
        'name' => 'everyone is sent to the Paywall again (the loop he reported)',
        'file' => $gate,
        'old' => "        if (\$request->user()?->can('billing.manage') === true) {",
        'new' => '        if (true) {',
        'tests' => [['file' => $test, 'filter' => 'shows every role that cannot renew']],
    ],
    [
        'id' => 'LS-M2',
        'name' => 'nobody is sent to the Paywall — the admin cannot renew',
        'file' => $gate,
        'old' => "        if (\$request->user()?->can('billing.manage') === true) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'still sends whoever can renew']],
    ],
    [
        'id' => 'LS-M3',
        'name' => 'the rule reads the role name, not the permission',
        'file' => $gate,
        'old' => "        if (\$request->user()?->can('billing.manage') === true) {",
        'new' => "        if (\$request->user()?->hasRole('Property Owner') === true) {",
        'tests' => [['file' => $test, 'filter' => 'still sends whoever can renew']],
    ],
    [
        'id' => 'LS-M4',
        'name' => 'the switcher is caught by the gate again',
        'file' => $gate,
        'old' => ' || $request->routeIs(RefuseSuspendedOrganization::SWITCH_ROUTE)) {',
        'new' => ') {',
        'tests' => [['file' => $test, 'filter' => 'move to another workspace']],
    ],
    [
        'id' => 'LS-M5',
        'name' => 'the page offers no other workspace',
        'file' => $gate,
        'old' => "            'otherWorkspaces' => OtherWorkspaces::for(\$request->user(), (string) \$organization->getKey()),",
        'new' => "            'otherWorkspaces' => [],",
        'tests' => [['file' => $test, 'filter' => 'move to another workspace']],
    ],
    [
        'id' => 'LS-M6',
        'name' => 'the page loses its way out (the generic home link instead of Sign out)',
        'file' => $gate,
        'old' => "            'actions' => 'errors.partials.suspended-actions',\n            'otherWorkspaces' => OtherWorkspaces::for(",
        'new' => "            'otherWorkspaces' => OtherWorkspaces::for(",
        'tests' => [['file' => $test, 'filter' => 'shows every role that cannot renew']],
    ],
    [
        'id' => 'LS-M7',
        'name' => 'a background request gets the HTML page',
        'file' => $gate,
        'old' => '        if ($request->expectsJson()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'background request']],
    ],
    [
        'id' => 'LS-M8',
        'name' => 'a suspended workspace is offered as the way out',
        'file' => $others,
        'old' => "            ->where('status', '!=', TenantLifecycle::SUSPENDED)\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'never offers a suspended workspace']],
    ],
    [
        'id' => 'LS-M9',
        'name' => 'the page offers the workspace that refused them',
        'file' => $others,
        'old' => "            ->where('organization_id', '!=', \$currentId)\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'move to another workspace']],
    ],
];
