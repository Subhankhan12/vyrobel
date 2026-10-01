<?php

declare(strict_types=1);

// Mutation set owners-page — the Owners page rebuilt (2026-10-01): Add owner as the centred shared dialog with the password on
// its own row; a unit that has an owner never given through an invite (refused at the invite, kept at acceptance, dropped on a
// resend); invite units inside the person's properties (DEC-k/DEC-l); invites resent (your password) and cancelled, each
// recorded without the address; the founder's link left alone; nothing written while the workspace is paused; statuses that
// follow what the payout run does; the pause read from the preference itself; a CSV of the view on screen; every write back on
// the list as it was. Run with `php tests/Mutations/run.php --set=owners-page`.

$fix = 'tests/Feature/OwnersPageFixesTest.php';
$look = 'tests/Feature/OwnersPageRedesignTest.php';
$payable = 'tests/Feature/OperatorOwnersPayableStateTest.php';
$service = 'app/Services/OwnerInviteService.php';
$inviteController = 'app/Http/Controllers/Operator/InviteOwnerController.php';
$actions = 'app/Http/Controllers/Operator/OwnerInviteActionController.php';
$export = 'app/Http/Controllers/Operator/OwnerExportController.php';
$rows = 'app/Support/Owner/OwnerRows.php';
$params = 'app/Support/Owner/OwnerListParams.php';
$page = 'app/Livewire/Operator/OperatorOwners.php';
$view = 'resources/views/livewire/operator/owners.blade.php';
$routes = 'routes/web.php';
$provider = 'app/Providers/AppServiceProvider.php';

return [
    // ── a unit's owner is never changed by an invite (money) ────────────────────────────────────
    [
        'id' => 'OW-M1',
        'name' => 'accepting an invite moves a unit that has an owner to the new owner',
        'file' => $service,
        'old' => "            Unit::query()->whereIn('id', \$unitIds)->whereNull('owner_id')->update(['owner_id' => \$owner->getKey()]);",
        'new' => "            Unit::query()->whereIn('id', \$unitIds)->update(['owner_id' => \$owner->getKey()]);",
        'tests' => [['file' => $fix, 'filter' => 'never gives an invited owner a unit']],
    ],
    [
        'id' => 'OW-M2',
        'name' => 'the invite accepts a unit that already has an owner',
        'file' => $inviteController,
        'old' => '            if ($owned->isNotEmpty()) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'never gives an invited owner a unit']],
    ],
    [
        'id' => 'OW-M3',
        'name' => 'an invite gives units outside the properties the person may access',
        'file' => $inviteController,
        'old' => "                ->when(\$scope !== [], fn (\$q) => \$q->whereIn('property_id', \$scope))\n",
        'new' => '',
        'tests' => [['file' => $fix, 'filter' => 'gives an invite only units inside the properties']],
    ],
    [
        'id' => 'OW-M4',
        'name' => 'a resend gives a unit that gained an owner since',
        'file' => $service,
        'old' => "\$unitIds = Unit::query()->whereIn('id', \$invite->assigned_unit_ids ?? [])->whereNull('owner_id')",
        'new' => "\$unitIds = Unit::query()->whereIn('id', \$invite->assigned_unit_ids ?? [])",
        'tests' => [['file' => $fix, 'filter' => 'resends without a unit that gained an owner']],
    ],

    // ── who may resend and cancel, and which invites ─────────────────────────────────────────────
    [
        'id' => 'OW-M5',
        'name' => 'a resend needs no password',
        'file' => $actions,
        'old' => "            ['current_password' => ['required', 'current_password']],",
        'new' => "            ['current_password' => ['nullable']],",
        'tests' => [['file' => $fix, 'filter' => 'resends a waiting invite with a fresh link']],
    ],
    [
        'id' => 'OW-M6',
        'name' => 'the resend route is open to anyone who can see the page',
        'file' => $routes,
        'old' => "        ->middleware(['throttle:10,1', 'can:users.manage'])->name('owners.invites.resend');",
        'new' => "        ->middleware(['throttle:10,1'])->name('owners.invites.resend');",
        'tests' => [['file' => $fix, 'filter' => 'lets only a person who may manage users']],
    ],
    [
        'id' => 'OW-M7',
        'name' => 'the cancel route is open to anyone who can see the page',
        'file' => $routes,
        'old' => "        ->middleware('can:users.manage')->name('owners.invites.cancel');",
        'new' => "        ->name('owners.invites.cancel');",
        'tests' => [['file' => $fix, 'filter' => 'lets only a person who may manage users']],
    ],
    [
        'id' => 'OW-M8',
        'name' => 'a founder\'s set-password link can be resent or cancelled from the Owners page',
        'file' => $actions,
        'old' => "        return OwnerInvite::query()->where('role', '!=', OwnerInviteService::FOUNDER_ROLE)->findOrFail(\$invite);",
        'new' => '        return OwnerInvite::query()->findOrFail($invite);',
        'tests' => [['file' => $fix, 'filter' => 'leaves a founder']],
    ],
    [
        'id' => 'OW-M9',
        'name' => 'the service resends a founder\'s link',
        'file' => $service,
        'old' => "        if (! in_array(\$invite->status, ['pending', 'expired'], true) || \$invite->role === self::FOUNDER_ROLE) {",
        'new' => "        if (! in_array(\$invite->status, ['pending', 'expired'], true)) {",
        'tests' => [['file' => $fix, 'filter' => 'leaves a founder']],
    ],
    [
        'id' => 'OW-M10',
        'name' => 'the service cancels a founder\'s link',
        'file' => $service,
        'old' => "        if (\$invite->status !== 'pending' || \$invite->role === self::FOUNDER_ROLE) {",
        'new' => "        if (\$invite->status !== 'pending') {",
        'tests' => [['file' => $fix, 'filter' => 'leaves a founder']],
    ],
    [
        'id' => 'OW-M11',
        'name' => 'a used invite is sent again',
        'file' => $service,
        'old' => "        if (! in_array(\$invite->status, ['pending', 'expired'], true) || \$invite->role === self::FOUNDER_ROLE) {",
        'new' => '        if ($invite->role === self::FOUNDER_ROLE) {',
        'tests' => [['file' => $fix, 'filter' => 'resends only an invite still waiting']],
    ],
    [
        'id' => 'OW-M12',
        'name' => 'a used or cancelled invite can be cancelled again',
        'file' => $service,
        'old' => "        if (\$invite->status !== 'pending' || \$invite->role === self::FOUNDER_ROLE) {",
        'new' => '        if ($invite->role === self::FOUNDER_ROLE) {',
        'tests' => [['file' => $fix, 'filter' => 'cancels a waiting invite']],
    ],
    [
        'id' => 'OW-M13',
        'name' => 'a cancelled invite leaves its never-activated owner on the list',
        'file' => $service,
        'old' => "\$login->status !== 'active')) {\n                \$owner->delete();",
        'new' => "\$login->status !== 'active')) {\n                // kept",
        'tests' => [['file' => $fix, 'filter' => 'cancels a waiting invite']],
    ],
    [
        'id' => 'OW-M14',
        'name' => 'cancelling an invite removes an owner who is active',
        'file' => $service,
        'old' => "            if (\$owner !== null && \$owner->status === 'inactive' && (\$login === null",
        'new' => '            if ($owner !== null && ($login === null',
        'tests' => [['file' => $fix, 'filter' => 'cancelling never removes an owner who is active']],
    ],

    // ── the trail ────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'OW-M15',
        'name' => 'an invite records nothing',
        'file' => $service,
        'old' => '            app(AuditEventRecorder::class)->record($trail, $invite, null, $this->inviteState($invite));',
        'new' => '',
        'tests' => [['file' => $fix, 'filter' => 'resends a waiting invite with a fresh link']],
    ],
    [
        'id' => 'OW-M16',
        'name' => 'a resend is recorded as a fresh send',
        'file' => $service,
        'old' => "            (string) \$invite->role, 'owner_invite.resent');",
        'new' => '            (string) $invite->role);',
        'tests' => [['file' => $fix, 'filter' => 'resends a waiting invite with a fresh link']],
    ],
    [
        'id' => 'OW-M17',
        'name' => 'the invite trail carries the address',
        'file' => $service,
        'old' => "            'role' => (string) \$invite->role,\n            'owner_id' =>",
        'new' => "            'role' => (string) \$invite->role,\n            'email' => (string) \$invite->email,\n            'owner_id' =>",
        'tests' => [['file' => $fix, 'filter' => 'resends a waiting invite with a fresh link']],
    ],
    [
        'id' => 'OW-M18',
        'name' => 'a cancel is not recorded',
        'file' => $service,
        'old' => "            app(AuditEventRecorder::class)->record('owner_invite.cancelled', \$invite, ['status' => 'pending'], ['status' => 'revoked']);",
        'new' => '',
        'tests' => [['file' => $fix, 'filter' => 'cancels a waiting invite']],
    ],
    [
        'id' => 'OW-M19',
        'name' => 'an invite cannot be recorded at all (no morph alias)',
        'file' => $provider,
        'old' => "            'owner_invite' => OwnerInvite::class,\n",
        'new' => '',
        'tests' => [['file' => $fix, 'filter' => 'resends a waiting invite with a fresh link']],
    ],

    // ── a paused workspace writes nothing, and offers nothing ────────────────────────────────────
    [
        'id' => 'OW-M20',
        'name' => 'an invite is sent while the workspace is paused',
        'file' => $inviteController,
        'old' => '        if (app(OperatorWorkspace::class)->isReadOnly()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'refuses every invite write while the workspace is paused']],
    ],
    [
        'id' => 'OW-M21',
        'name' => 'an invite is resent or cancelled while the workspace is paused',
        'file' => $actions,
        'old' => '        return app(OperatorWorkspace::class)->isReadOnly()',
        'new' => '        return false',
        'tests' => [['file' => $fix, 'filter' => 'refuses every invite write while the workspace is paused']],
    ],
    [
        'id' => 'OW-M22',
        'name' => 'the invite controls are offered while the workspace is paused',
        'file' => $page,
        'old' => '        $canWrite = $canManage && ! $readOnly;',
        'new' => '        $canWrite = $canManage;',
        'tests' => [['file' => $fix, 'filter' => 'refuses every invite write while the workspace is paused']],
    ],
    [
        'id' => 'OW-M23',
        'name' => 'the invite controls are offered to a role that may not manage users',
        'file' => $page,
        'old' => "        \$canManage = (bool) \$user?->can('users.manage');",
        'new' => '        $canManage = true;',
        'tests' => [['file' => $look, 'filter' => 'offers no invite control to a role']],
    ],

    // ── statuses follow what the payout run does ─────────────────────────────────────────────────
    [
        'id' => 'OW-M24',
        'name' => 'a paused owner with money due reads Ready',
        'file' => $rows,
        'old' => '        if ($net > 0 && $paused) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'counts an owner whose payouts are paused']],
    ],
    [
        'id' => 'OW-M25',
        'name' => 'a held payout reads Ready',
        'file' => $rows,
        'old' => "        if (\$statementPayouts->contains(fn (Payout \$p): bool => (string) \$p->status === 'held')) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'says why each owner on hold is on hold']],
    ],
    [
        'id' => 'OW-M26',
        'name' => 'missing tax details or payout account read Ready',
        'file' => $rows,
        'old' => '        if ($net > 0 && (! $owner->hasTaxDetails() || ! $hasMethod)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'says why each owner on hold is on hold']],
    ],
    [
        'id' => 'OW-M27',
        'name' => 'a statement with unconfirmed lines reads Ready',
        'file' => $rows,
        'old' => '        if ((bool) $latest->has_unverified) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $payable, 'filter' => 'still shows the real pending payable']],
    ],
    [
        'id' => 'OW-M28',
        'name' => 'Ready to pay counts the money of a paused owner',
        'file' => $page,
        'old' => "        \$ready = array_values(array_filter(\$all, fn (array \$r): bool => \$r['pay_key'] === 'due'));\n        \$hold =",
        'new' => "        \$ready = array_values(array_filter(\$all, fn (array \$r): bool => in_array(\$r['pay_key'], ['due', 'paused'], true)));\n        \$hold =",
        'tests' => [['file' => $fix, 'filter' => 'counts an owner whose payouts are paused']],
    ],
    [
        'id' => 'OW-M29',
        'name' => 'the menu offers Pause to a paused owner with nothing due',
        'file' => $page,
        'old' => "            \$actions[] = \$row['paused'] ? 'resume' : 'pause';",
        'new' => "            \$actions[] = \$row['pay_key'] === 'paused' ? 'resume' : 'pause';",
        'tests' => [['file' => $fix, 'filter' => 'offers Resume']],
    ],
    [
        'id' => 'OW-M30',
        'name' => 'the Payouts paused chip misses a paused owner with nothing due',
        'file' => $rows,
        'old' => "            'paused' => \$row['paused'],",
        'new' => "            'paused' => \$row['pay_key'] === 'paused',",
        'tests' => [['file' => $fix, 'filter' => 'offers Resume']],
    ],
    [
        'id' => 'OW-M31',
        'name' => 'a paused owner with nothing due says nothing about the pause',
        'file' => $rows,
        'old' => "            if (\$isPaused && \$reason === null && \$owner->status === 'active') {",
        'new' => '            if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'offers Resume']],
    ],
    [
        'id' => 'OW-M32',
        'name' => 'the side panel says payouts are automatic while the automatic run is off',
        'file' => $page,
        'old' => "            'engine' => (bool) config('services.payouts.engine_enabled', false),",
        'new' => "            'engine' => true,",
        'tests' => [['file' => $fix, 'filter' => 'says how statements close and how payouts go out']],
    ],

    // ── the export and the way back ──────────────────────────────────────────────────────────────
    [
        'id' => 'OW-M33',
        'name' => 'the owner export is open to a role that may not read owner finances',
        'file' => $routes,
        'old' => "Route::get('/owners/export', [OwnerExportController::class, 'download'])->middleware('can:reports.view')->name('owners.export');",
        'new' => "Route::get('/owners/export', [OwnerExportController::class, 'download'])->name('owners.export');",
        'tests' => [['file' => $fix, 'filter' => 'exports the owners in view as a CSV']],
    ],
    [
        'id' => 'OW-M34',
        'name' => 'an export cell is not formula-safe',
        'file' => $export,
        'old' => "                fputcsv(\$out, CsvSafe::row([\n",
        'new' => "                fputcsv(\$out, ([\n",
        'tests' => [['file' => $fix, 'filter' => 'exports the owners in view as a CSV']],
    ],
    [
        'id' => 'OW-M35',
        'name' => 'an export is not recorded',
        'file' => $export,
        'old' => "        \$audit->record('owner.list_exported', null, null,",
        'new' => "        null && \$audit->record('owner.list_exported', null, null,",
        'tests' => [['file' => $fix, 'filter' => 'exports the owners in view as a CSV']],
    ],
    [
        'id' => 'OW-M36',
        'name' => 'Export takes the view the page was loaded with, not the one on screen',
        'file' => $view,
        'old' => "\n                           x-on:click=\"\$el.href = \$el.dataset.exportBase + location.search\">",
        'new' => '>',
        'tests' => [['file' => $look, 'filter' => 'exports the view on screen']],
    ],
    [
        'id' => 'OW-M37',
        'name' => 'a write started on the list does not come back to it',
        'file' => $params,
        'old' => "        return \$request->input('return') === 'list' ? redirect()->to(self::returnUrl(\$request->input('return_query'))) : null;",
        'new' => '        return null;',
        'tests' => [['file' => $fix, 'filter' => 'brings pausing payouts from the list back']],
    ],
    [
        'id' => 'OW-M38',
        'name' => 'any filter in the address is taken as given',
        'file' => $params,
        'old' => '        if (is_string($filter) && in_array($filter, self::FILTERS, true)) {',
        'new' => '        if (is_string($filter)) {',
        'tests' => [['file' => $fix, 'filter' => 'settings from the address through an allow-list']],
    ],

    // ── the dialog ───────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'OW-M39',
        'name' => 'Add owner opens without the focus trap',
        'file' => $view,
        'old' => 'role="dialog" aria-modal="true" aria-labelledby="vy-invite-title" tabindex="-1" x-trap.noscroll="inviteOpen"',
        'new' => 'role="dialog" aria-modal="true" aria-labelledby="vy-invite-title"',
        'tests' => [['file' => $look, 'filter' => 'opens Add owner as the centred shared dialog']],
    ],
    [
        'id' => 'OW-M40',
        'name' => 'a unit that has an owner can be picked in the dialog',
        'file' => $view,
        'old' => " @disabled(\$u['owner'] !== null)",
        'new' => '',
        'tests' => [['file' => $look, 'filter' => 'offers a unit only while it has no owner']],
    ],
    [
        'id' => 'OW-M41',
        'name' => 'the payout run link is offered to a role that cannot open it',
        'file' => $view,
        'old' => "                        @can('payouts.manage')\n                            <a class=\"vyo-payout-link\"",
        'new' => "                        @can('reports.view')\n                            <a class=\"vyo-payout-link\"",
        'tests' => [['file' => $look, 'filter' => 'offers no invite control to a role']],
    ],
];
