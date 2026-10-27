<?php

declare(strict_types=1);

// Mutation set units-five — Units in the five-page pass (2026-10-05, the founder: "on this page there must be an option to edit and
// delete the unit. And Add inventory section design and functionality is not good. It is so confusing"). Delete removes only what
// nothing has recorded (one rule, App\Support\Unit\InventoryDeletion, asked again inside the transaction); room types are edited and
// deleted from compact cards; units are added several at once; "All properties" asks which property; the setup guide stays until
// there is a price; a Dorm's units are dorm beds; the table stacks inside its card; a paused workspace changes no arrival info or
// photo. Run with `php tests/Mutations/run.php --set=units-five`. ⚠️ The look is what a browser draws: these prove the markup,
// words, rules and the server's behaviour; the 🖥 walk proved the look at 1,280 / 1,024 / 390 and each dialog's round trip.

$test = 'tests/Feature/UnitsPassFiveTest.php';
$ctl = 'app/Http/Controllers/Operator/InventoryActionController.php';
$rules = 'app/Support/Unit/InventoryDeletion.php';
$codes = 'app/Support/Unit/UnitCodes.php';
$page = 'app/Livewire/UnitsList.php';
$view = 'resources/views/livewire/units-list.blade.php';
$css = 'resources/views/components/layouts/operator.blade.php';
$photos = 'app/Http/Controllers/Operator/RoomTypePhotoController.php';

return [
    // ── delete a unit ─────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'UF-M1',
        'name' => 'a unit with history is deleted anyway — its sold stay loses its room, its cleaning history is erased',
        'file' => $ctl,
        'old' => "            \$blockers = InventoryDeletion::unitBlockers([(string) \$locked->getKey()])[(string) \$locked->getKey()] ?? [];\n            if (\$blockers !== []) {\n                return \$blockers;\n            }\n",
        'new' => "            \$blockers = InventoryDeletion::unitBlockers([(string) \$locked->getKey()])[(string) \$locked->getKey()] ?? [];\n",
        'tests' => [['file' => $test, 'filter' => 'refuses to delete a unit with history']],
    ],
    [
        'id' => 'UF-M2',
        'name' => 'a booking is not a reason to keep a unit',
        'file' => $rules,
        'old' => "        \$add(self::grouped('reservation_units', 'unit_id', \$unitIds, 'reservation_id'), 'booking', 'bookings');\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'refuses to delete a unit with history'], ['file' => $test, 'filter' => 'puts Edit in plain sight']],
    ],
    [
        'id' => 'UF-M3',
        'name' => 'a cleaning task is not a reason to keep a unit (the CASCADE would erase it)',
        'file' => $rules,
        'old' => "        \$add(self::grouped('housekeeping_tasks', 'unit_id', \$unitIds), 'cleaning task', 'cleaning tasks');\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'refuses to delete a unit with history']],
    ],
    [
        'id' => 'UF-M4',
        'name' => 'the delete is a soft one — the code stays taken for ever (the unique index counts soft-deleted rows)',
        'file' => $ctl,
        'old' => "            \$locked->amenities()->detach();\n            \$locked->forceDelete();\n\n            // its room type sells one fewer",
        'new' => "            \$locked->amenities()->detach();\n            \$locked->delete();\n\n            // its room type sells one fewer",
        'tests' => [['file' => $test, 'filter' => 'deletes a unit nothing has recorded']],
    ],
    [
        'id' => 'UF-M5',
        'name' => 'the room type goes on selling the deleted unit (no availability recompute)',
        'file' => $ctl,
        'old' => "            // its room type sells one fewer from today (the past has no availability to change: nothing was booked)\n            \$availability->recompute(\n                (string) \$locked->room_type_id,\n                CarbonImmutable::today()->toDateString(),\n                CarbonImmutable::today()->addDays(self::AVAILABILITY_HORIZON_DAYS)->toDateString(),\n            );\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'deletes a unit nothing has recorded']],
    ],
    [
        'id' => 'UF-M6',
        'name' => 'a deleted unit leaves no audit row',
        'file' => $ctl,
        'old' => "            \$audit->record('unit.deleted', \$locked, \$before, null);\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'deletes a unit nothing has recorded']],
    ],
    [
        'id' => 'UF-M7',
        'name' => 'DEC-k — Delete reaches a unit of a property the person does not work in',
        'file' => $ctl,
        'old' => '        $model = $this->accessibleUnit($unit);   // property-scoped → another property\'s id 404s (DEC-k)',
        'new' => '        $model = Unit::query()->findOrFail($unit);',
        'tests' => [['file' => $test, 'filter' => 'guards Delete like every unit write']],
    ],
    [
        'id' => 'UF-M8',
        'name' => 'a paused workspace can still delete a unit',
        'file' => $ctl,
        'old' => "    public function destroyUnit(Request \$request, AvailabilityService \$availability, AuditEventRecorder \$audit, string \$unit): RedirectResponse\n    {\n        if (\$guard = \$this->readOnly('units', \$request)) {\n            return \$guard;\n        }\n",
        'new' => "    public function destroyUnit(Request \$request, AvailabilityService \$availability, AuditEventRecorder \$audit, string \$unit): RedirectResponse\n    {\n",
        'tests' => [['file' => $test, 'filter' => 'guards Delete like every unit write']],
    ],
    [
        'id' => 'UF-M9',
        'name' => 'Delete is open to a role without units.manage',
        'file' => 'routes/web.php',
        'old' => "        ->middleware('can:units.manage')->name('inventory.units.destroy');",
        'new' => "        ->name('inventory.units.destroy');",
        'tests' => [['file' => $test, 'filter' => 'guards Delete like every unit write']],
    ],
    // ── the room types ────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'UF-M10',
        'name' => 'a room type with units is deleted anyway — the database CASCADEs its units and sold stays',
        'file' => $ctl,
        'old' => "            \$blockers = InventoryDeletion::roomTypeBlockers([(string) \$locked->getKey()])[(string) \$locked->getKey()] ?? [];\n            if (\$blockers !== []) {\n                return \$blockers;\n            }\n",
        'new' => "            \$blockers = InventoryDeletion::roomTypeBlockers([(string) \$locked->getKey()])[(string) \$locked->getKey()] ?? [];\n",
        'tests' => [['file' => $test, 'filter' => 'deletes only an empty room type']],
    ],
    [
        'id' => 'UF-M11',
        'name' => 'a room type’s units are not a reason to keep it',
        'file' => $rules,
        'old' => "        \$add(self::grouped('units', 'room_type_id', \$roomTypeIds), 'unit', 'units');\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'deletes only an empty room type']],
    ],
    [
        'id' => 'UF-M12',
        'name' => 'a room type’s kind changes under units that were added as another kind',
        'file' => $ctl,
        'old' => "        if (\$kind !== (string) \$model->kind && Unit::withTrashed()->where('room_type_id', \$model->getKey())->exists()) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'edits a room type']],
    ],
    [
        'id' => 'UF-M13',
        'name' => 'the standard occupancy shown to guests stays behind when the adults change',
        'file' => $ctl,
        'old' => '        $std = (int) $model->std_occupancy === (int) $model->max_adults ? $adults : min((int) $model->std_occupancy, $adults);',
        'new' => '        $std = (int) $model->std_occupancy;',
        'tests' => [['file' => $test, 'filter' => 'edits a room type']],
    ],
    [
        'id' => 'UF-M14',
        'name' => 'a Dorm’s units are written with a kind the database refuses (an error page)',
        'file' => $ctl,
        'old' => "    private const UNIT_KIND = ['room' => 'room', 'apartment' => 'apartment', 'villa' => 'villa', 'dorm' => 'dorm_bed'];",
        'new' => "    private const UNIT_KIND = ['room' => 'room', 'apartment' => 'apartment', 'villa' => 'villa', 'dorm' => 'dorm'];",
        'tests' => [['file' => $test, 'filter' => 'adds a unit to a Dorm room type']],
    ],
    // ── adding several at once ────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'UF-M15',
        'name' => 'a range is taken as one code — "101-103" adds one unit called "101-103"',
        'file' => $codes,
        'old' => '        if (preg_match(self::RANGE, $part, $m) === 1 && (int) $m[3] > (int) $m[2]) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'adds several units at once']],
    ],
    [
        'id' => 'UF-M16',
        'name' => 'a code listed twice reaches the unique index (a server error, not a sentence)',
        'file' => $codes,
        'old' => '                if (isset($seen[$key])) {',
        'new' => '                if (false) {',
        'tests' => [['file' => $test, 'filter' => 'refuses a list with a code already used']],
    ],
    [
        'id' => 'UF-M17',
        'name' => 'a typo makes hundreds of rooms — the 100 cap is gone',
        'file' => $codes,
        'old' => '        if ($total > self::MAX) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'refuses a list with a code already used']],
    ],
    [
        'id' => 'UF-M18',
        'name' => 'Add room type drops the units typed with it',
        'file' => $ctl,
        'old' => "            if (\$codes !== []) {\n                \$this->createUnits(\$roomType, \$codes, null, null, \$availability);\n            }\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'adds a room type and its units in one step']],
    ],
    [
        'id' => 'UF-M19',
        'name' => '"All properties" silently adds the room type to the first property again',
        'file' => $ctl,
        'old' => '        if ($context->active() && $context->isAll() && count($context->accessibleIds()) > 1) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'asks which property under']],
    ],
    [
        'id' => 'UF-M20',
        'name' => 'the chosen property is not checked against the person’s own',
        'file' => $ctl,
        'old' => '            if (! is_string($chosen) || ! in_array($chosen, $context->accessibleIds(), true)) {',
        'new' => '            if (! is_string($chosen) || $chosen === \'\') {',
        'tests' => [['file' => $test, 'filter' => 'asks which property under']],
    ],
    // ── the page ──────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'UF-M21',
        'name' => 'Delete is not offered on a row',
        'file' => $page,
        'old' => "            \$actions[] = 'delete';\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'puts Edit in plain sight']],
    ],
    [
        'id' => 'UF-M22',
        'name' => 'every row claims Delete can remove it — the dialog would promise a delete the server refuses',
        'file' => $page,
        'old' => "        return \$row + ['deletable' => \$found === [], 'blockers' => \$found ?? []];",
        'new' => "        return \$row + ['deletable' => true, 'blockers' => \$found ?? []];",
        'tests' => [['file' => $test, 'filter' => 'puts Edit in plain sight']],
    ],
    [
        'id' => 'UF-M23',
        'name' => 'Edit leaves the row for the ⋯ menu again (his report)',
        'file' => $view,
        'old' => "                                                    @if (in_array('edit', \$row['actions'], true))",
        'new' => '                                                    @if (false)',
        'tests' => [['file' => $test, 'filter' => 'puts Edit in plain sight']],
    ],
    [
        'id' => 'UF-M24',
        'name' => 'a room type card loses its Delete',
        'file' => $view,
        'old' => "@click=\"openDeleteRoomType('{{ \$rt['id'] }}')\"",
        'new' => "@click=\"openEditRoomType('{{ \$rt['id'] }}')\"",
        'tests' => [['file' => $test, 'filter' => 'shows each room type as a compact card']],
    ],
    [
        'id' => 'UF-M25',
        'name' => 'a room type with no prices is called set (nothing says why it cannot be booked)',
        'file' => $page,
        'old' => "            ! \$isPriced => [__('php/livewire/units_list.state_no_prices'), 'warn'],\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'shows each room type as a compact card']],
    ],
    [
        'id' => 'UF-M26',
        'name' => 'nothing ever counts as priced — the setup guide never leaves',
        'file' => 'app/Support/Unit/RoomTypePricing.php',
        'old' => "        \$priced = fn (\$q) => \$q->where('date', '>=', \$today)->where('price_minor', '>', 0);",
        'new' => "        \$priced = fn (\$q) => \$q->where('date', '>=', \$today)->where('price_minor', '<', 0);",
        'tests' => [['file' => $test, 'filter' => 'keeps the setup steps until the room type has prices'], ['file' => $test, 'filter' => 'shows each room type as a compact card']],
    ],
    // ── a paused workspace; DEC-k for photos ──────────────────────────────────────────────────────────────────────
    [
        'id' => 'UF-M27',
        'name' => 'a paused workspace can still change door codes and wifi',
        'file' => $ctl,
        'old' => "        // wifi while every other unit write was refused. Reading them back (the audited reveal) stays allowed — it is a read.\n        if (\$guard = \$this->readOnly('units', \$request)) {\n            return \$guard;\n        }\n",
        'new' => "        // wifi while every other unit write was refused. Reading them back (the audited reveal) stays allowed — it is a read.\n",
        'tests' => [['file' => $test, 'filter' => 'offers no arrival-info or photo writes while the workspace is paused']],
    ],
    [
        'id' => 'UF-M28',
        'name' => 'the arrival dialog offers Save in a paused workspace',
        'file' => $view,
        'old' => "                                @if (\$canWrite)\n                                    <button type=\"submit\" class=\"vy-btn vy-btn--accent\">{{ __('common.save') }}</button>",
        'new' => "                                @if (true)\n                                    <button type=\"submit\" class=\"vy-btn vy-btn--accent\">{{ __('common.save') }}</button>",
        'tests' => [['file' => $test, 'filter' => 'offers no arrival-info or photo writes while the workspace is paused']],
    ],
    [
        'id' => 'UF-M29',
        'name' => 'a paused workspace can still add a photo',
        'file' => $photos,
        'old' => "        \$rt = \$this->roomType(\$roomType);\n        if (\$guard = \$this->readOnly(\$rt)) {\n            return \$guard;\n        }\n\n        \$request->validate([",
        'new' => "        \$rt = \$this->roomType(\$roomType);\n\n        \$request->validate([",
        'tests' => [['file' => $test, 'filter' => 'offers no arrival-info or photo writes while the workspace is paused']],
    ],
    [
        'id' => 'UF-M30',
        'name' => 'DEC-k — the photo routes reach another property’s room type',
        'file' => $photos,
        'old' => "        return RoomType::query()\n            ->when(\$ids !== [], fn (\$q) => \$q->whereIn('property_id', \$ids))\n            ->findOrFail(\$id);",
        'new' => "        return RoomType::query()\n            ->findOrFail(\$id);",
        'tests' => [['file' => $test, 'filter' => 'manages photos only inside the person']],
    ],
    [
        'id' => 'UF-M31',
        'name' => 'after a photo change the page comes back with the photos closed',
        'file' => $photos,
        'old' => "        return back()->with('units_photos', (string) \$rt->getKey());",
        'new' => '        return back();',
        'tests' => [['file' => $test, 'filter' => 'manages a room type’s photos in one dialog']],
    ],
    // ── text visible at every width ───────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'UF-M32',
        'name' => 'the units card is not a size container — the right columns slide out of sight again',
        'file' => $css,
        'old' => "        .vy-units .vy-gl-card { container: vy-ul-list / inline-size; }\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'fits the units table inside its card']],
    ],
    [
        'id' => 'UF-M33',
        'name' => 'the leftover pill rule turns the page’s wrapper into a flex row again',
        'file' => $css,
        'old' => "        .vy-units .vy-ul-table { min-width: 0; }\n",
        'new' => "        .vy-units .vy-ul-table { min-width: 0; }\n        .vy-units { display: flex; flex-wrap: wrap; gap: 8px; }\n",
        'tests' => [['file' => $test, 'filter' => 'fits the units table inside its card']],
    ],
    [
        'id' => 'UF-M34',
        'name' => 'a stacked fact loses its name',
        'file' => $view,
        'old' => '<td data-label="{{ __(\'livewire/units-list.col_owner\') }}">',
        'new' => '<td>',
        'tests' => [['file' => $test, 'filter' => 'fits the units table inside its card']],
    ],
    [
        'id' => 'UF-M35',
        'name' => 'a channel connected to a room type’s rate plan does not keep it — the delete cuts the connection (the review)',
        'file' => $rules,
        'old' => "        \$add(\$mapped, 'channel connection', 'channel connections');\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'keeps a room type whose rate plan is connected to a channel']],
    ],
    [
        'id' => 'UF-M36',
        'name' => 'two codes the database counts as one end in a server error page again (the review)',
        'file' => $ctl,
        'old' => "        try {\n            return \$write();\n        } catch (UniqueConstraintViolationException \$e) {",
        'new' => "        try {\n            return \$write();\n        } catch (\\LogicException \$e) {",
        'tests' => [['file' => $test, 'filter' => 'refuses two codes the database counts as one']],
    ],
    [
        'id' => 'UF-M37',
        'name' => 'any unique violation is told as "one of these codes is already used" — an availability race included (the second review)',
        'file' => $ctl,
        'old' => '            if ($e->index !== self::UNIT_CODE_INDEX) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $test, 'filter' => 'says "already used" only for the unit-code index']],
    ],
];
