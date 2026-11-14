<?php

declare(strict_types=1);

// Mutation set retention-decided — the money records' retention period, decided by the founder on 2026-11-14 without a lawyer. Replaces D13-M31 (the registry
// is no longer empty by ruling). Run with `php tests/Mutations/run.php --set=retention-decided`.

$t = 'tests/Feature/Admin/RetentionBucketsTest.php';
$reg = 'app/Support/Retention/RetentionDecisions.php';

return [
    [
        'id' => 'RD-M1',
        'name' => 'a money decision ships with no period (half-filled — counts as a decision nowhere, and the registry lies)',
        'file' => $reg,
        'old' => "'period' => self::FINANCIAL_RECORDS_YEARS.' years',",
        'new' => "'period' => '',",
        'tests' => [['file' => $t, 'filter' => 'every decision the registry ships']],
    ],
    [
        'id' => 'RD-M2',
        'name' => 'the decision stops saying it was the founder\'s own call without legal advice',
        'file' => $reg,
        'old' => 'DECIDED BY THE FOUNDER on 2026-11-14 WITHOUT legal advice',
        'new' => 'Decided on 2026-11-14',
        'tests' => [['file' => $t, 'filter' => 'every decision the registry ships']],
    ],
    [
        'id' => 'RD-M3',
        'name' => 'a typo in a table name — the decision counts for nothing and nothing says so',
        'file' => $reg,
        'old' => "'payments' => self::MONEY,",
        'new' => "'paymentz' => self::MONEY,",
        'tests' => [['file' => $t, 'filter' => 'names only tables that exist']],
    ],
    [
        'id' => 'RD-M4',
        'name' => 'the number of years changes in the registry but not on the card the customer reads',
        'file' => $reg,
        'old' => 'public const FINANCIAL_RECORDS_YEARS = 10;',
        'new' => 'public const FINANCIAL_RECORDS_YEARS = 7;',
        'tests' => [['file' => $t, 'filter' => 'promises the same number of years']],
    ],
    [
        'id' => 'RD-M5',
        'name' => 'the audit log is quietly decided with the money records',
        'file' => $reg,
        'old' => "        'subscriptions' => self::MONEY,\n    ];",
        'new' => "        'subscriptions' => self::MONEY,\n        'audit_events' => self::MONEY,\n    ];",
        'tests' => [['file' => $t, 'filter' => 'does not decide what is not a money record']],
    ],
];
