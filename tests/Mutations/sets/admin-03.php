<?php

declare(strict_types=1);

// Mutation set admin-03 — run with `php tests/Mutations/run.php --set=admin-03`. Each row: the exact text a guard lives in (`old`),
// what it becomes with the guard removed (`new`), and the test aimed at it. See tests/Mutations/run.php.

return [
    [
        'id' => 'A03-M1',
        'name' => 'F1 read list',
        'file' => 'app/Http/Controllers/Admin/AuditController.php',
        'old' => '\'platform.break_glass.page\', \'platform.revenue\', ',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/AuditReadActionsAreCompleteTest.php']],
    ],
    [
        'id' => 'A03-M2',
        'name' => 'F2 trial filter',
        'file' => 'app/Support/Platform/PlatformCustomers.php',
        'old' => '->where(\'subscriptions.status\', \'trialing\')->whereNull(\'subscriptions.deleted_at\'));',
        'new' => '->where(\'subscriptions.status\', \'no-such-status\')->whereNull(\'subscriptions.deleted_at\'));',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F2']],
    ],
    [
        'id' => 'A03-M3',
        'name' => 'F3 lone UTC',
        'file' => 'resources/views/admin/privacy/index.blade.php',
        'old' => '{{ $live[\'next_due\'] ? $live[\'next_due\']->format(\'j M Y, H:i\').\' UTC\' : \'Nothing scheduled\' }}',
        'new' => '{{ $live[\'next_due\']?->format(\'j M Y, H:i\').\' UTC\' ?? \'—\' }}',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F3']],
    ],
    [
        'id' => 'A03-M4',
        'name' => 'F12 first only',
        'file' => 'resources/views/components/admin/shell.blade.php',
        'old' => '@if (count($errors->all()) === 1)',
        'new' => '@if (true)',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F12']],
    ],
    [
        'id' => 'A03-M5',
        'name' => 'F12 page copy back',
        'file' => 'resources/views/admin/announcements.blade.php',
        'old' => '<div class="ad-card" style="overflow-x: auto;">',
        'new' => '@if ($errors->any())<div class="ad-flash ad-flash--error">{{ $errors->first() }}</div>@endif
    <div class="ad-card" style="overflow-x: auto;">',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F12']],
    ],
    [
        'id' => 'A03-M6',
        'name' => 'F13 announcement target',
        'file' => 'app/Http/Controllers/Admin/AnnouncementController.php',
        'old' => 'count($data[\'organization_ids\'] ?? [])], \'announcement\', $id);',
        'new' => 'count($data[\'organization_ids\'] ?? [])], \'announcement\', null);',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F13']],
    ],
    [
        'id' => 'A03-M7',
        'name' => 'F13 incident target',
        'file' => 'app/Http/Controllers/Admin/StatusIncidentController.php',
        'old' => '[\'impact\' => $data[\'impact\']], \'status_incident\', $id);',
        'new' => '[\'impact\' => $data[\'impact\']], \'status_incident\', null);',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F13']],
    ],
    [
        'id' => 'A03-M8',
        'name' => 'F6 raw key',
        'file' => 'resources/views/admin/customers/show.blade.php',
        'old' => '{{ \\App\\Support\\Platform\\ActionLabel::platform($action->action) }}</strong>',
        'new' => '{{ $action->action }}</strong>',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F6']],
    ],
    [
        'id' => 'A03-M9',
        'name' => 'F7 no zone',
        'file' => 'resources/views/components/admin/time.blade.php',
        'old' => '$utc->format(\'j M Y, H:i\').\' UTC\' }}</time>',
        'new' => '$utc->format(\'j M Y, H:i\') }}</time>',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F6']],
    ],
    [
        'id' => 'A03-M10',
        'name' => 'F7 relative only',
        'file' => 'resources/views/admin/customers/show.blade.php',
        'old' => '<br><x-admin.time :at="$action->created_at" />',
        'new' => '<br>{{ $action->created_at?->diffForHumans() }}',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F6']],
    ],
    [
        'id' => 'A03-M11',
        'name' => 'F6 failed suffix',
        'file' => 'app/Support/Platform/ActionLabel.php',
        'old' => 'if (str_ends_with($action, \'.failed\')) {',
        'new' => 'if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F6']],
    ],
    [
        'id' => 'A03-N1',
        'name' => 'announcement form drops the field-name map',
        'file' => 'app/Http/Controllers/Admin/AnnouncementController.php',
        'old' => '        ], [], ConsoleFields::NAMES);
        $reason',
        'new' => '        ]);
        $reason',
        'tests' => [['file' => 'tests/Feature/Admin/ConsoleFieldNamesAreUsedTest.php'], ['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F12']],
    ],
    [
        'id' => 'A03-N2',
        'name' => 'privacy form drops the field-name map',
        'file' => 'app/Http/Controllers/Admin/PrivacyController.php',
        'old' => '            \'received_on\' => [\'required\', \'date\', \'before_or_equal:today\'],
        ], [], ConsoleFields::NAMES);',
        'new' => '            \'received_on\' => [\'required\', \'date\', \'before_or_equal:today\'],
        ]);',
        'tests' => [['file' => 'tests/Feature/Admin/ConsoleFieldNamesAreUsedTest.php'], ['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F12']],
    ],
    [
        'id' => 'A03-N3',
        'name' => 'field-name map only in a comment',
        'file' => 'app/Http/Controllers/Admin/SwitchController.php',
        'old' => '], [], ConsoleFields::NAMES);',
        'new' => '] /* [], ConsoleFields::NAMES */);',
        'tests' => [['file' => 'tests/Feature/Admin/ConsoleFieldNamesAreUsedTest.php']],
    ],
    [
        'id' => 'A03-N4',
        'name' => 'the map loses subject_kind',
        'file' => 'app/Support/Platform/ConsoleFields.php',
        'old' => '        \'subject_kind\' => \'about\',
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'F12']],
    ],
    [
        'id' => 'A03-N5',
        'name' => 'plan placeholder removed',
        'file' => 'resources/views/admin/customers/show.blade.php',
        'old' => '                            <option value="" @selected(! $sub || ! $plans->contains(\'id\', $sub->plan_id))>
                                {{ $plans->isEmpty() ? \'No plan is active — turn one on under Plans first\' : \'Choose a plan…\' }}
                            </option>
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'deliberate choice']],
    ],
    [
        'id' => 'A03-N6',
        'name' => 'plan placeholder never selected',
        'file' => 'resources/views/admin/customers/show.blade.php',
        'old' => '@selected(! $sub || ! $plans->contains(\'id\', $sub->plan_id))',
        'new' => '@selected(false)',
        'tests' => [['file' => 'tests/Feature/Admin/Admin03QualityPassTest.php', 'filter' => 'deliberate choice']],
    ],
];
