<?php

declare(strict_types=1);

// Mutation set invite-picker — D-DEC-P-INVITE-PICKER (2026-11-12): a unit a still-waiting owner invitation lists is promised, not free.
// Run with `php tests/Mutations/run.php --set=invite-picker`.

$test = 'tests/Feature/OperatorGiveUnitToOwnerTest.php';

return [
    [
        'id' => 'IP-M1',
        'name' => 'the picker offers a promised unit again',
        'file' => 'app/Livewire/Operator/OperatorOwnerDetail.php',
        'old' => "->when(\$promised !== [], fn (\$q) => \$q->whereNotIn('id', \$promised))",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'dpu22']],
    ],
    [
        'id' => 'IP-M2',
        'name' => 'the write itself no longer refuses a promised unit (a hand-made request gets round the picker)',
        'file' => 'app/Services/UnitOwnerAssignmentService.php',
        'old' => 'if (in_array((string) $unit->getKey(), OwnerInvite::unitIdsOnPendingInvites(), true)) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu22']],
    ],
    [
        'id' => 'IP-M3',
        'name' => 'a cancelled invitation still holds its units (the status filter is dropped)',
        'file' => 'app/Models/OwnerInvite.php',
        'old' => "foreach (self::query()->where('status', 'pending')->whereNotNull('assigned_unit_ids')",
        'new' => "foreach (self::query()->whereNotNull('assigned_unit_ids')",
        'tests' => [['file' => $test, 'filter' => 'dpu22']],
    ],
];
