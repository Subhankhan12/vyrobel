<?php

declare(strict_types=1);

// Mutation set admin-p5 — run with `php tests/Mutations/run.php --set=admin-p5`. Each row: the exact text a guard lives in (`old`),
// what it becomes with the guard removed (`new`), and the test aimed at it. See tests/Mutations/run.php.

return [
    [
        'id' => 'P5-H2',
        'name' => 'internal counted',
        'file' => 'app/Support/Platform/PlatformHotelStats.php',
        'old' => '        $q = Organization::query();
        if (! $includeInternal) {
            $q->customers();
        }

        return $q->pluck(\'id\')',
        'new' => '        $q = Organization::query();

        return $q->pluck(\'id\')',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-H3',
        'name' => 'failed payments counted',
        'file' => 'app/Support/Platform/PlatformHotelStats.php',
        'old' => '->whereIn(\'type\', [\'payment\', \'capture\'])->whereIn(\'status\', [\'succeeded\', \'captured\']);',
        'new' => '->whereIn(\'type\', [\'payment\', \'capture\']);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-H4',
        'name' => 'adoption ignores 30 days',
        'file' => 'app/Support/Platform/PlatformHotelStats.php',
        'old' => '->whereIn(\'event_type\', $types)
                ->where(\'occurred_at\', \'>=\', $since)->distinct()',
        'new' => '->whereIn(\'event_type\', $types)
                ->distinct()',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-H5',
        'name' => 'unfinished month counted',
        'file' => 'app/Support/Platform/PlatformHotelStats.php',
        'old' => '                if ($windowEnds->greaterThan($now)) {',
        'new' => '                if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-A1',
        'name' => 'chosen reaches everyone',
        'file' => 'app/Models/PlatformAnnouncement.php',
        'old' => '        return $this->audience === \'all\' || in_array($organizationId, (array) $this->organization_ids, true);',
        'new' => '        return true;',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-A2',
        'name' => 'dismissals ignored',
        'file' => 'resources/views/components/platform-announcements.blade.php',
        'old' => '            ->whereNotIn(\'id\', \\Illuminate\\Support\\Facades\\DB::table(\'announcement_dismissals\')->where(\'user_id\', $vyAnnUser->getAuthIdentifier())->select(\'announcement_id\'))
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-A3',
        'name' => 'withdrawn still live',
        'file' => 'app/Models/PlatformAnnouncement.php',
        'old' => '        $query->whereNull(\'withdrawn_at\')->where(\'starts_at\', \'<=\', now())',
        'new' => '        $query->where(\'starts_at\', \'<=\', now())',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-A4',
        'name' => 'future already live',
        'file' => 'app/Models/PlatformAnnouncement.php',
        'old' => '        $query->whereNull(\'withdrawn_at\')->where(\'starts_at\', \'<=\', now())',
        'new' => '        $query->whereNull(\'withdrawn_at\')',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-A5',
        'name' => 'title unescaped',
        'file' => 'resources/views/components/platform-announcements.blade.php',
        'old' => '<strong>From Vyrobel · {{ $vyAnn->title }}</strong>',
        'new' => '<strong>From Vyrobel · {!! $vyAnn->title !!}</strong>',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-A6',
        'name' => 'dismiss any notice',
        'file' => 'app/Http/Controllers/AnnouncementDismissController.php',
        'old' => 'abort_if($user === null || $orgId === null || $row === null || ! $row->reaches($orgId), 404);',
        'new' => 'abort_if($user === null || $orgId === null || $row === null, 404);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-S1',
        'name' => 'internal on public page',
        'file' => 'resources/views/status.blade.php',
        'old' => '        <p class="sub">Incidents posted by the Vyrobel team. Times are UTC.</p>',
        'new' => '        <p class="sub">Incidents posted by the Vyrobel team. Times are UTC. ({{ config(\'app.env\') }})</p>',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-S2',
        'name' => 'empty page claims operational',
        'file' => 'resources/views/status.blade.php',
        'old' => '<div class="card">No incidents have been posted.</div>',
        'new' => '<div class="card">All systems operational.</div>',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-S3',
        'name' => 'resolved shown forever',
        'file' => 'app/Http/Controllers/StatusPageController.php',
        'old' => '->orWhere(\'resolved_at\', \'>=\', now()->subDays(StatusIncident::SHOW_RESOLVED_DAYS))',
        'new' => '->orWhereNotNull(\'resolved_at\')',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-S4',
        'name' => 'speaking opened to every role',
        'file' => 'routes/web.php',
        'old' => 'Route::middleware([$can(PlatformAdminRole::TENANTS_MANAGE), \'throttle:20,1\'])',
        'new' => 'Route::middleware([$can(PlatformAdminRole::CONSOLE_VIEW), \'throttle:20,1\'])',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
    [
        'id' => 'P5-C1',
        'name' => 'channex claims connected',
        'file' => 'app/Support/Platform/Health/HealthBoard.php',
        'old' => '        if (! $configured) {
            return new HealthCard(\'channex\'',
        'new' => '        if (false) {
            return new HealthCard(\'channex\'',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformHotelsCommsTest.php']],
    ],
];
