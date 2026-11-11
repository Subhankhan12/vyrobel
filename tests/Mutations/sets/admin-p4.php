<?php

declare(strict_types=1);

// Mutation set admin-p4 — run with `php tests/Mutations/run.php --set=admin-p4`. Each row: the exact text a guard lives in (`old`),
// what it becomes with the guard removed (`new`), and the test aimed at it. See tests/Mutations/run.php.

return [
    [
        'id' => 'P4-R1',
        'name' => 'unprocessed invoices counted',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '            ->where(\'status\', WebhookEvent::STATUS_PROCESSED)
            ->whereIn(\'organization_id\', $orgIds)',
        'new' => '            ->whereIn(\'organization_id\', $orgIds)',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R2',
        'name' => 'internal not excluded',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '        if (! $includeInternal) {
            $q->customers();
        }

        return $q->pluck(\'id\')',
        'new' => '        return $q->pluck(\'id\')',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R3',
        'name' => 'yearly not divided',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '            \'year\' => intdiv($amount, 12 * $count),',
        'new' => '            \'year\' => $amount,',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R4',
        'name' => 'monthly bucket removed',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '            $days >= 27 && $days <= 32 => $amount,
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R5',
        'name' => 'earliest invoice valued',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '$latest = $paid->where(\'subscription\', $sub->stripe_subscription_id)->sortByDesc(\'at\')->first();',
        'new' => '$latest = $paid->where(\'subscription\', $sub->stripe_subscription_id)->sortBy(\'at\')->first();',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R6',
        'name' => 'churn without cancellation',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '} elseif ($was > 0 && $is === 0 && $cancelled->has($org)) {',
        'new' => '} elseif ($was > 0 && $is === 0) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R7',
        'name' => 'live claimed with no charge',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '            \'live\' => $paid->isNotEmpty(),',
        'new' => '            \'live\' => true,',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R8',
        'name' => 'revenue opened to every role',
        'file' => 'routes/web.php',
        'old' => '            Route::middleware($can(PlatformAdminRole::BILLING_VIEW))->group(function () {
                Route::get(\'revenue\',',
        'new' => '            Route::middleware($can(PlatformAdminRole::CONSOLE_VIEW))->group(function () {
                Route::get(\'revenue\',',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-R9',
        'name' => 'stated interval ignored',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => '        return match ($interval) {
            \'month\' => intdiv($amount, $count),',
        'new' => '        return match ($interval) {
            \'month\' => $amount,',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformRevenueTest.php']],
    ],
    [
        'id' => 'P4-F1',
        'name' => 'verified needs no verification',
        'file' => 'app/Support/Platform/PlatformFunnel.php',
        'old' => '->where(\'organization_user.status\', \'active\')->whereNotNull(\'users.email_verified_at\'))->count(),',
        'new' => '->where(\'organization_user.status\', \'active\'))->count(),',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-F2',
        'name' => 'funnel counts internal',
        'file' => 'app/Support/Platform/PlatformFunnel.php',
        'old' => '            $q = Organization::query();
            if (! $includeInternal) {
                $q->customers();
            }

            return $source($q);',
        'new' => '            $q = Organization::query();

            return $source($q);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-F3',
        'name' => 'check-in = any reservation',
        'file' => 'app/Support/Platform/PlatformFunnel.php',
        'old' => '->whereColumn(\'reservations.organization_id\', \'organizations.id\')->whereIn(\'reservations.status\', [\'checked_in\', \'checked_out\']))->count(),',
        'new' => '->whereColumn(\'reservations.organization_id\', \'organizations.id\'))->count(),',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P1',
        'name' => 'overdue never flagged',
        'file' => 'app/Support/Privacy/PrivacyQueue.php',
        'old' => '            \'overdue\' => $open()->where(\'scheduled_deletion_at\', \'<\', $now)->count(),',
        'new' => '            \'overdue\' => 0,',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P2',
        'name' => 'ruling picked',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => '    public const RULING = null;',
        'new' => '    public const RULING = \'strict\';',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P3',
        'name' => 'category count typed',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => '\'categories\' => count($rows),',
        'new' => '\'categories\' => 142,',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P4',
        'name' => 'missing inventory not handled',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => '        if (! is_readable($path)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P5',
        'name' => 'agreement claimed',
        'file' => 'app/Support/Privacy/SubProcessors.php',
        'old' => '            \'agreement\' => self::say(\'agreement_none\'),',
        'new' => '            \'agreement\' => \'Signed\',',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P6',
        'name' => 'due date not one month',
        'file' => 'app/Http/Controllers/Admin/PrivacyController.php',
        'old' => '            \'due_at\' => $received->addMonthsNoOverflow(PrivacyRequest::DUE_MONTHS),',
        'new' => '            \'due_at\' => $received->addDays(40),',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P7',
        'name' => 'closed request reopenable',
        'file' => 'app/Http/Controllers/Admin/PrivacyController.php',
        'old' => '        if (! $row->isOpen()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P8',
        'name' => 'unknown email accepted',
        'file' => 'app/Http/Controllers/Admin/PrivacyController.php',
        'old' => '            if ($userId === null) {
                return $back->withErrors([\'email\'',
        'new' => '            if (false) {
                return $back->withErrors([\'email\'',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
    [
        'id' => 'P4-P9',
        'name' => 'register open to every role',
        'file' => 'routes/web.php',
        'old' => '            Route::middleware($can(PlatformAdminRole::SUPPORT_USE))->group(function () {
                Route::get(\'support\',',
        'new' => '            Route::middleware($can(PlatformAdminRole::CONSOLE_VIEW))->group(function () {
                Route::get(\'support\',',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php']],
    ],
];
