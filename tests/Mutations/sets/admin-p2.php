<?php

declare(strict_types=1);

// Mutation set admin-p2 — run with `php tests/Mutations/run.php --set=admin-p2`. Each row: the exact text a guard lives in (`old`),
// what it becomes with the guard removed (`new`), and the test aimed at it. See tests/Mutations/run.php.

return [
    [
        'id' => 'P2-M1',
        'name' => 'gate never refuses',
        'file' => 'app/Http/Middleware/RefuseSuspendedOrganization.php',
        'old' => 'if (! TenantLifecycle::refusesAccess($organization)) {',
        'new' => 'if (true) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M2',
        'name' => 'archive not in access rule',
        'file' => 'app/Support/Platform/TenantLifecycle.php',
        'old' => '            || $organization->trashed()
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M3',
        'name' => 'suspended not in access rule',
        'file' => 'app/Support/Platform/TenantLifecycle.php',
        'old' => '            || $organization->status === self::SUSPENDED;',
        'new' => ';',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M4',
        'name' => '/owner group loses the gate',
        'file' => 'routes/web.php',
        'old' => 'Route::middleware([\'auth\', RefuseSuspendedOrganization::class, EnsureOwnerPortalAccess::class',
        'new' => 'Route::middleware([\'auth\', EnsureOwnerPortalAccess::class',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M5',
        'name' => 'api loses the gate',
        'file' => 'routes/api.php',
        'old' => 'ResolveApiOwnerContext::class, RefuseSuspendedOrganization::class]',
        'new' => 'ResolveApiOwnerContext::class]',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M6',
        'name' => 'livewire persistent removed',
        'file' => 'app/Providers/AppServiceProvider.php',
        'old' => '            RefuseSuspendedOrganization::class,
        ]);',
        'new' => '        ]);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M7',
        'name' => 'no guest acknowledgement',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '        if ($impact[\'in_house\'] + $impact[\'upcoming\'] > 0 && ! $request->boolean(\'acknowledge_guests\')) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M8',
        'name' => 'baseline lock ignored',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '        if ($lock === TenantLifecycle::LOCK_BASELINE) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M9',
        'name' => 'confirm lock ignored',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '        if ($lock === TenantLifecycle::LOCK_CONFIRM && $lifecycle',
        'new' => '        if (false && $lifecycle',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M10',
        'name' => 'baseline config ignored',
        'file' => 'app/Support/Platform/TenantLifecycle.php',
        'old' => '        if ($baseline !== \'\' && $baseline === (string) $organization->getKey()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M11',
        'name' => 'reactivate always active',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '        $to = in_array($org->suspended_from_status, [\'active\', \'trialing\', \'cancelled\'], true) ? $org->suspended_from_status : \'active\';',
        'new' => '        $to = \'active\';',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M12',
        'name' => 'archive without suspension',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '        if ($org->status !== TenantLifecycle::SUSPENDED) {
            return $this->back($org)->withErrors([\'status\' => \'Suspend it first',
        'new' => '        if (false) {
            return $this->back($org)->withErrors([\'status\' => \'Suspend it first',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M13',
        'name' => 'storefront ignores archive',
        'file' => 'app/Http/Middleware/ResolvePublicProperty.php',
        'old' => 'if ($organization === null || $organization->trashed() || in_array(',
        'new' => 'if ($organization === null || in_array(',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M14',
        'name' => 'sitemap ignores archive',
        'file' => 'app/Http/Controllers/StorefrontSitemapController.php',
        'old' => '->where(fn ($q) => $q->whereIn(\'status\', ResolvePublicProperty::CLOSED_FOR_BOOKING)->orWhereNotNull(\'deleted_at\'))',
        'new' => '->whereIn(\'status\', ResolvePublicProperty::CLOSED_FOR_BOOKING)',
        'tests' => [['file' => 'tests/Feature/StorefrontSeoTest.php']],
    ],
    [
        'id' => 'P2-M15',
        'name' => 'slug check skips archived',
        'file' => 'app/Services/TenantProvisioningService.php',
        'old' => '\'slug\' => $this->uniqueSlug(Organization::withTrashed(),',
        'new' => '\'slug\' => $this->uniqueSlug(Organization::query(),',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M16',
        'name' => 'signup source not recorded',
        'file' => 'app/Services/TenantProvisioningService.php',
        'old' => '            if (array_key_exists((string) $signupSource, TenantLifecycle::SIGNUP_SOURCES)) {',
        'new' => '            if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M17',
        'name' => 'no .failed ledger row',
        'file' => 'app/Support/Platform/PlatformQuery.php',
        'old' => '            $this->audit->record($action.\'.failed\',',
        'new' => '            null && $this->audit->record($action.\'.failed\',',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M18',
        'name' => 'switch not exempt',
        'file' => 'app/Http/Middleware/RefuseSuspendedOrganization.php',
        'old' => '        if ($request->routeIs(self::SWITCH_ROUTE) || ',
        'new' => '        if (false || ',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M19',
        'name' => 'unverified ignores 7 days',
        'file' => 'app/Support/Platform/PlatformCustomers.php',
        'old' => '                ->where(\'organizations.created_at\', \'<\', CarbonImmutable::now()->subDays(7))
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M20',
        'name' => 'directory shows archived',
        'file' => 'app/Support/Platform/PlatformCustomers.php',
        'old' => '        $query = Organization::query()->select(\'organizations.*\');
        $this->withFootprint($query);',
        'new' => '        $query = Organization::withTrashed()->select(\'organizations.*\');
        $this->withFootprint($query);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M21',
        'name' => 'suspend without password',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '    public function suspend(Request $request, string $organization): RedirectResponse
    {
        // ⚠️ `current_password:admin` — QUALIFIED. /admin runs on `auth:admin`; the bare rule would check the
        // `web` guard and, with no web user, validate nothing.
        $request->validate([\'current_password\' => [\'required\', \'current_password:admin\']], [], ConsoleFields::NAMES);',
        'new' => '    public function suspend(Request $request, string $organization): RedirectResponse
    {
',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M22',
        'name' => 'suspended_from_status not saved',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => 'fn () => $org->forceFill([\'suspended_from_status\' => $org->status, \'status\' => TenantLifecycle::SUSPENDED])->save(),',
        'new' => 'fn () => $org->forceFill([\'status\' => TenantLifecycle::SUSPENDED])->save(),',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
    [
        'id' => 'P2-M23',
        'name' => 'archive needs no reason',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '        $request->validate([\'current_password\' => [\'required\', \'current_password:admin\']], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, \'archive\', $reason)) {',
        'new' => '        $request->validate([\'current_password\' => [\'required\', \'current_password:admin\']], [], ConsoleFields::NAMES);
        $reason = \'no reason given\';
        $org = $this->resolveOrg($organization);

        if ($refused = $this->refusedByLock($request, $org, \'archive\', $reason)) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformAuditLedgerTest.php']],
    ],
    [
        'id' => 'P2-M24',
        'name' => 'lifecycle group loosened to console.view',
        'file' => 'routes/web.php',
        'old' => 'Route::middleware([$can(PlatformAdminRole::TENANTS_MANAGE), \'throttle:20,1\'])',
        'new' => 'Route::middleware([$can(PlatformAdminRole::CONSOLE_VIEW), \'throttle:20,1\'])',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformTenantLifecycleTest.php']],
    ],
];
