<?php

declare(strict_types=1);

// Mutation set dec-p-unit-assignment — DEC-p (2026-11-12): giving an existing owner a unit that has no owner, from a chosen day. REAL MONEY.
// Each guard removed one at a time; the aimed test must go RED. Run with `php tests/Mutations/run.php --set=dec-p-unit-assignment`.
// The tests are tests/Feature/OperatorGiveUnitToOwnerTest.php (dpu01…dpu21).

$test = 'tests/Feature/OperatorGiveUnitToOwnerTest.php';
$svc = 'app/Services/UnitOwnerAssignmentService.php';
$ctl = 'app/Http/Controllers/Operator/OwnerUnitAssignController.php';
$unit = 'app/Models/Unit.php';
$attr = 'app/Services/OwnerAttributionService.php';

return [
    // ── THE ONE RULE: a stay that began before the day is never the owner's ────────────────────────────────────────────────────────────
    [
        'id' => 'DP-M1',
        'name' => '⚠️ the attribution engine reads units.owner_id again, not the day-aware answer — a past stay is credited to the new owner (backfill, move, booking)',
        'file' => $attr,
        'old' => "\$stayOwnerId = \$unit?->ownerIdForStayStarting(\$sg['from']);",
        'new' => '$stayOwnerId = $unit?->owner_id === null ? null : (string) $unit->owner_id;',
        // ⚠️ NOT dpu05: its stays never reach attribution — the sweep covers only stays from the day, and the backfill filter (DP-M3) refuses the rest. Removing
        //    BOTH is DP-M2 (the cut itself), which dpu05 does catch. Each layer is proven by the test that reaches it.
        'tests' => [['file' => $test, 'filter' => 'dpu08'], ['file' => $test, 'filter' => 'dpu09']],
    ],
    [
        'id' => 'DP-M2',
        'name' => '⚠️ Unit::ownerIdForStayStarting() ignores owner_since — the day-aware answer no longer cuts anything (earnings, notice, repair, owner lists)',
        'file' => $unit,
        'old' => 'if ($this->owner_since !== null && Carbon::parse($date)->toDateString() < $this->owner_since->toDateString()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu05'], ['file' => $test, 'filter' => 'dpu08'], ['file' => $test, 'filter' => 'dpu19'], ['file' => $test, 'filter' => 'dpu20']],
    ],
    [
        'id' => 'DP-M3',
        'name' => 'the backfill filter (needsAttribution) counts a segment that began before the day — the command is called for every past stay on the unit',
        'file' => $attr,
        'old' => "if (\$sg['unit_id'] === (string) \$unit->id && \$unit->ownerIdForStayStarting(\$sg['from']) !== null) {",
        'new' => "if (\$sg['unit_id'] === (string) \$unit->id) {",
        'tests' => [['file' => $test, 'filter' => 'dpu05']],
    ],

    // ── what may be given at all ───────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DP-M4',
        'name' => 'the service gives a unit that already has an owner — their income is moved from under them',
        'file' => $svc,
        'old' => 'if ($unit->owner_id !== null) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu01'], ['file' => $test, 'filter' => 'dpu21']],
    ],
    [
        'id' => 'DP-M5',
        'name' => 'the service gives a unit to an owner no agreement covers — the owner is credited €0 and the sentence "payouts go to" is false',
        'file' => $svc,
        'old' => 'if (ManagementAgreement::resolveFor((string) $owner->getKey(), (string) $unit->getKey(), $day) === null) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu02']],
    ],
    [
        'id' => 'DP-M6',
        'name' => 'a day that has already passed is accepted — retroactive money',
        'file' => $svc,
        'old' => 'if ($day->lessThan($today)) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu14']],
    ],
    [
        'id' => 'DP-M7',
        'name' => 'a day more than 365 days ahead is accepted',
        'file' => $svc,
        'old' => 'if ($day->greaterThan($today->addDays(self::MAX_DAYS_AHEAD))) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu14']],
    ],
    [
        'id' => 'DP-M8',
        'name' => 'a day inside a live stay is accepted — the stay is worked out whole, and nights before the day are paid to the new owner',
        'file' => $svc,
        'old' => 'if ($across instanceof ReservationUnit) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu06']],
    ],
    [
        'id' => 'DP-M9',
        'name' => 'a unit whose future stays are already worked out for a removed owner is given anyway',
        'file' => $svc,
        'old' => 'if ($others->isNotEmpty()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu16']],
    ],
    [
        'id' => 'DP-M10',
        'name' => 'an owner who has not accepted their invitation can be given a unit',
        'file' => $svc,
        'old' => " || (string) \$owner->status !== 'active') {",
        'new' => ') {',
        'tests' => [['file' => $test, 'filter' => 'dpu15']],
    ],
    [
        'id' => 'DP-M11',
        'name' => 'stays that start from the day and were booked before are not worked out for the owner (no sweep)',
        'file' => $svc,
        'old' => '$this->attribution->attributeReservation($reservation->refresh());',
        'new' => '// (mutated: no sweep)',
        'tests' => [['file' => $test, 'filter' => 'dpu07']],
    ],
    [
        'id' => 'DP-M12',
        'name' => 'the audit row is not written',
        'file' => $svc,
        'old' => "\$this->audit->record('unit.owner_assigned', \$unit, \$before, \$after, actorId: \$actorId);",
        'new' => '// (mutated: no audit row)',
        'tests' => [['file' => $test, 'filter' => 'dpu12']],
    ],

    // ── who may do it, and where ───────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DP-M13',
        'name' => '⚠️ the route carries no users.manage gate — a Front Desk or Hotel Manager session can give a unit',
        'file' => 'routes/web.php',
        'old' => "->middleware(['throttle:10,1', 'can:users.manage'])->name('owners.units.assign');",
        'new' => "->middleware(['throttle:10,1'])->name('owners.units.assign');",
        'tests' => [['file' => $test, 'filter' => 'dpu03']],
    ],
    [
        'id' => 'DP-M14',
        'name' => 'the unit is found by tenant only — a unit at a property the person cannot reach is given',
        'file' => $ctl,
        'old' => '$unit = app(PropertyScope::class)->unit((string) $data[\'unit_id\']);',
        'new' => '$unit = \App\Models\Unit::query()->findOrFail((string) $data[\'unit_id\']);',
        'tests' => [['file' => $test, 'filter' => 'dpu03b']],
    ],
    [
        'id' => 'DP-M15',
        'name' => 'the password is not asked — a hijacked session can change whose income a unit is',
        'file' => $ctl,
        'old' => "\$request->validate(['current_password' => ['required', 'current_password']], [",
        'new' => "\$request->validate(['current_password' => ['nullable']], [",
        'tests' => [['file' => $test, 'filter' => 'dpu03']],
    ],
    [
        'id' => 'DP-M16',
        'name' => 'a paused workspace can still give a unit',
        'file' => $ctl,
        'old' => 'if (app(OperatorWorkspace::class)->isReadOnly()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu03c']],
    ],

    // ── the other readers of "who owns it" ─────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DP-M17',
        'name' => 'the new-booking notice goes to the owner for a stay that began before the day',
        'file' => 'app/Listeners/DispatchNotificationsForDomainEvent.php',
        'old' => '$ownerId = $unit->ownerIdForStayStarting($startByUnit[(string) $unit->id]);',
        'new' => '$ownerId = $unit->owner_id;',
        'tests' => [['file' => $test, 'filter' => 'dpu20']],
    ],
    [
        'id' => 'DP-M18',
        'name' => 'a repair resolved before the day is billed to the owner',
        'file' => 'app/Services/MaintenanceService.php',
        'old' => 'if ($unit === null || $unit->ownerIdForStayStarting($expenseDate) === null) {',
        'new' => 'if ($unit === null || $unit->owner_id === null) {',
        'tests' => [['file' => $test, 'filter' => 'dpu19']],
    ],
    [
        'id' => 'DP-M19',
        'name' => 'the owner\'s Bookings list shows a stay that began before the day',
        'file' => 'app/Support/Owner/OwnerBookingsReader.php',
        'old' => 'return $unit === null || $unit->owner_since === null || $unit->ownerIdForStayStarting($l->check_in) !== null;',
        'new' => 'return true;',
        'tests' => [['file' => $test, 'filter' => 'dpu13']],
    ],

    // ── a stale day never travels ──────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DP-M20',
        'name' => 'accepting an invite leaves the unit\'s old day in place — the new owner is cut from stays before a previous owner\'s day',
        'file' => 'app/Services/OwnerInviteService.php',
        'old' => "->update(['owner_id' => \$owner->getKey(), 'owner_since' => null]);",
        'new' => "->update(['owner_id' => \$owner->getKey()]);",
        'tests' => [['file' => $test, 'filter' => 'dpu17']],
    ],
    [
        'id' => 'DP-M21',
        'name' => 'removing an owner leaves the unit\'s day behind',
        'file' => 'app/Http/Controllers/Operator/OwnerManageController.php',
        'old' => "->update(['owner_id' => null, 'owner_since' => null]);",
        'new' => "->update(['owner_id' => null]);",
        'tests' => [['file' => $test, 'filter' => 'dpu17']],
    ],

    // ── the owner's page ───────────────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DP-M22',
        'name' => 'the owner\'s page offers "Give a unit" to a person who cannot use it',
        'file' => 'app/Livewire/Operator/OperatorOwnerDetail.php',
        'old' => "'giveUnits' => (bool) auth()->user()?->can('users.manage') ? \$this->giveableUnits(\$record) : [],",
        'new' => "'giveUnits' => \$this->giveableUnits(\$record),",
        'tests' => [['file' => $test, 'filter' => 'dpu18']],
    ],
    [
        'id' => 'DP-M23',
        'name' => 'the owner\'s page offers units to an owner who has not accepted their invitation',
        'file' => 'app/Livewire/Operator/OperatorOwnerDetail.php',
        'old' => "if ((string) \$owner->status !== 'active') {",
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dpu18b']],
    ],
];
