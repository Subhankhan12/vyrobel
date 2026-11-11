<?php

declare(strict_types=1);

// Mutation set dec-o-portal-access — DEC-o (2026-11-12): each guard that switches an owner's portal access off, removed one at a time.
// Run with `php tests/Mutations/run.php --set=dec-o-portal-access`. The tests are tests/Feature/OwnerPortalAccessSwitchTest.php (dop01…dop24).

$test = 'tests/Feature/OwnerPortalAccessSwitchTest.php';
$ctx = 'app/Support/Owner/OwnerContext.php';
$gate = 'app/Http/Middleware/EnsureOwnerPortalAccess.php';
$api = 'app/Http/Middleware/ResolveApiOwnerContext.php';
$team = 'app/Services/OwnerTeamInviteService.php';
$invite = 'app/Services/OwnerInviteService.php';
$ctl = 'app/Http/Controllers/Operator/OwnerManageController.php';
$detail = 'app/Livewire/Operator/OperatorOwnerDetail.php';
$view = 'resources/views/livewire/operator/owner-detail.blade.php';

return [
    // ── the owner scope (the second layer — a Livewire update runs no route middleware) ──────────────────────────────────────────────
    [
        'id' => 'DO-M1',
        'name' => 'the owner scope resolves a PRIMARY owner whose portal is off (the scope lets them read their data on a Livewire update)',
        'file' => $ctx,
        'old' => "Owner::query()->where('user_id', \$user->getKey())->portalOn()->pluck('id')",
        'new' => "Owner::query()->where('user_id', \$user->getKey())->pluck('id')",
        'tests' => [['file' => $test, 'filter' => 'dop16']],
    ],
    [
        'id' => 'DO-M2',
        'name' => 'the owner scope resolves a TEAM member of an owner whose portal is off',
        'file' => $ctx,
        'old' => "            ->whereNotIn('owner_id', OwnerPortalAccess::offOwnerIds())\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'dop17']],
    ],
    [
        'id' => 'DO-M3',
        'name' => '⚠️ "linked" stops counting an owner whose portal is off — the scope would fall OPEN to every owner\'s rows',
        'file' => $ctx,
        'old' => "\$linked = Owner::query()->where('user_id', \$user->getKey())->exists()",
        'new' => "\$linked = Owner::query()->where('user_id', \$user->getKey())->portalOn()->exists()",
        'tests' => [['file' => $test, 'filter' => 'dop16']],
    ],
    [
        'id' => 'DO-M4',
        'name' => 'the Owner scope for "portal on" matches every owner (the one definition of on)',
        'file' => 'app/Models/Owner.php',
        'old' => "        return \$query->whereNull(\$query->qualifyColumn('portal_disabled_at'));",
        'new' => '        return $query;',
        'tests' => [['file' => $test, 'filter' => 'dop16']],
    ],

    // ── the request gate ────────────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DO-M5',
        'name' => 'the owner gate lets a linked person in without asking whether their owner\'s portal is on',
        'file' => $gate,
        'old' => 'if ($linked && OwnerPortalAccess::hasOpenLink($user)) {',
        'new' => 'if ($linked) {',
        'tests' => [['file' => $test, 'filter' => 'dop08'], ['file' => $test, 'filter' => 'dop09']],
    ],
    [
        'id' => 'DO-M6',
        'name' => 'the gate refuses an off owner with a bare 403 instead of the plain page',
        'file' => $gate,
        'old' => "        if (\$linked) {\n            return \$this->refuseSwitchedOff(\$request);\n        }\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'dop08']],
    ],
    [
        'id' => 'DO-M7',
        'name' => 'a person who also works for the operator is no longer sent to their back office when the portal is off',
        'file' => $gate,
        'old' => 'if ($backOffice !== [] && $user->hasAnyRole($backOffice)) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dop11']],
    ],
    [
        'id' => 'DO-M8',
        'name' => '"has an open link" ignores a switched-off PRIMARY owner',
        'file' => 'app/Support/Owner/OwnerPortalAccess.php',
        'old' => "        return Owner::query()->where('user_id', \$user->getKey())->portalOn()->exists()",
        'new' => "        return Owner::query()->where('user_id', \$user->getKey())->exists()",
        'tests' => [['file' => $test, 'filter' => 'dop08']],
    ],
    [
        'id' => 'DO-M9',
        'name' => '"has an open link" ignores a switched-off owner for a TEAM member',
        'file' => 'app/Support/Owner/OwnerPortalAccess.php',
        'old' => "                ->whereNotIn('owner_id', self::offOwnerIds())->exists();",
        'new' => '                ->exists();',
        'tests' => [['file' => $test, 'filter' => 'dop09']],
    ],

    // ── the API ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DO-M10',
        'name' => 'an owner\'s API token still resolves their owner while the portal is off',
        'file' => $api,
        'old' => "\$primary = Owner::query()->where('user_id', \$user->getKey())->portalOn()->get(['id', 'organization_id']);",
        'new' => "\$primary = Owner::query()->where('user_id', \$user->getKey())->get(['id', 'organization_id']);",
        'tests' => [['file' => $test, 'filter' => 'dop10']],
    ],
    [
        'id' => 'DO-M11',
        'name' => 'a team member\'s API token still resolves the owner while the portal is off',
        'file' => $api,
        'old' => "                ->whereNotIn('owner_id', OwnerPortalAccess::offOwnerIds())->get(['owner_id', 'role']);",
        'new' => "                ->get(['owner_id', 'role']);",
        'tests' => [['file' => $test, 'filter' => 'dop10']],
    ],
    [
        'id' => 'DO-M12',
        'name' => 'the API says "not an owner" instead of the plain portal-off sentence',
        'file' => $api,
        'old' => "        if (\$switchedOff) {\n            abort(403, __('php/http/middleware/resolve_api_owner_context.portal_off'));\n        }\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'dop10']],
    ],

    // ── sign-in routing ─────────────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DO-M13',
        'name' => 'sign-in sends an off owner who also works for the operator to the owner portal, not their back office',
        'file' => 'app/Http/Controllers/Auth/Concerns/RedirectsByRole.php',
        'old' => "fn (): bool => Owner::query()->where('user_id', \$user->getKey())->portalOn()->exists(),",
        'new' => "fn (): bool => Owner::query()->where('user_id', \$user->getKey())->exists(),",
        'tests' => [['file' => $test, 'filter' => 'dop11']],
    ],

    // ── the two public doors back in ────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DO-M14',
        'name' => 'a team invite link to an off owner is still redeemable (the link opens the set-password form)',
        'file' => $team,
        'old' => ' && ! $this->ownerPortalIsOff($invite)) ? $invite : null;',
        'new' => ') ? $invite : null;',
        'tests' => [['file' => $test, 'filter' => 'dop18']],
    ],
    [
        'id' => 'DO-M15',
        'name' => 'the team invite SERVICE writes a membership under an off owner when called directly',
        'file' => $team,
        'old' => "                if (Owner::query()->whereKey(\$invite->owner_id)->portalOff()->exists()) {\n                    throw new InviteNoLongerRedeemable('This owner’s portal access is switched off, so the team link can’t be used.');\n                }\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'dop18']],
    ],
    [
        'id' => 'DO-M16',
        'name' => 'an owner invite link to an off owner is still redeemable',
        'file' => $invite,
        'old' => ' && ! $this->wouldReplaceAPassword($invite) && ! $this->ownerPortalIsOff($invite)) ? $invite : null;',
        'new' => ' && ! $this->wouldReplaceAPassword($invite)) ? $invite : null;',
        'tests' => [['file' => $test, 'filter' => 'dop19']],
    ],
    [
        'id' => 'DO-M17',
        'name' => 'the owner invite SERVICE activates a login for an off owner when called directly',
        'file' => $invite,
        'old' => 'if ($invite->owner_id !== null && Owner::query()->whereKey($invite->owner_id)->portalOff()->exists()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dop19']],
    ],
    [
        'id' => 'DO-M18',
        'name' => 'a fresh invite to the address of an off owner is sent (it would re-open a paused login)',
        'file' => $invite,
        'old' => 'if ($isOwner && $existing !== null && Owner::query()->where(\'user_id\', $existing->getKey())->portalOff()->exists()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dop20']],
    ],

    // ── the write itself ────────────────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DO-M19',
        'name' => 'the switch is open to any back-office role (the route loses can:users.manage)',
        'file' => 'routes/web.php',
        'old' => "->middleware(['throttle:10,1', 'can:users.manage'])->name('owners.portal-access');",
        'new' => "->middleware(['throttle:10,1'])->name('owners.portal-access');",
        'tests' => [['file' => $test, 'filter' => 'dop01']],
    ],
    [
        'id' => 'DO-M20',
        'name' => 'a paused (read-only) workspace can still switch an owner\'s access',
        'file' => $ctl,
        'old' => "        if (\$this->paused()) {\n            return \$this->back(\$owner, 'owners_error', \$this->pausedMessage(), \$request);\n        }\n\n        // tenant-scoped: another organisation's owner is a 404 BEFORE anything is validated (no existence oracle)",
        'new' => "        // tenant-scoped: another organisation's owner is a 404 BEFORE anything is validated (no existence oracle)",
        'tests' => [['file' => $test, 'filter' => 'dop03']],
    ],
    [
        'id' => 'DO-M21',
        'name' => 'another organisation\'s owner is found by the switch (a PERMITTED actor switches a foreign owner)',
        'file' => $ctl,
        'old' => "        \$model = Owner::query()->findOrFail(\$owner);\n\n        \$request->validate(['portal' =>",
        'new' => "        \$model = Owner::query()->withoutGlobalScopes()->findOrFail(\$owner);\n\n        \$request->validate(['portal' =>",
        'tests' => [['file' => $test, 'filter' => 'dop02']],
    ],
    [
        'id' => 'DO-M22',
        'name' => 'a one-character reason is enough to switch an owner off',
        'file' => $ctl,
        'old' => "['required', 'string', 'min:5', 'max:300']",
        'new' => "['required', 'string', 'max:300']",
        'tests' => [['file' => $test, 'filter' => 'dop04']],
    ],
    [
        'id' => 'DO-M23',
        'name' => 'switching back on no longer asks for the operator\'s password',
        'file' => $ctl,
        'old' => "\$request->validate(['current_password' => ['required', 'current_password']], [\n            'current_password.required' => __('php/http/controllers/operator/owner_manage_controller.portal_password_required'),",
        'new' => "\$request->validate([], [\n            'current_password.required' => __('php/http/controllers/operator/owner_manage_controller.portal_password_required'),",
        'tests' => [['file' => $test, 'filter' => 'dop07']],
    ],
    [
        'id' => 'DO-M24',
        'name' => 'an owner whose login is not active can be "switched off" by a hand-made request',
        'file' => $ctl,
        'old' => 'if (! ($model->user instanceof User) || (string) $model->user->status !== User::SIGN_IN_STATUS) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dop05']],
    ],
    [
        'id' => 'DO-M25',
        'name' => '⚠️ #28 — the audit row copies the owner\'s email address',
        'file' => $ctl,
        'old' => "['portal' => 'off', 'team' => \$team, 'reason' => (string) \$validated['reason']]",
        'new' => "['portal' => 'off', 'team' => \$team, 'reason' => (string) \$validated['reason'], 'email' => (string) \$model->email]",
        'tests' => [['file' => $test, 'filter' => 'dop04']],
    ],
    [
        'id' => 'DO-M26',
        'name' => 'the audit row (and the notice) lose the size of the team that went with the owner',
        'file' => $ctl,
        'old' => "\$team = OwnerUser::query()->where('owner_id', \$model->id)->where('status', 'active')->count();",
        'new' => '$team = 0;',
        'tests' => [['file' => $test, 'filter' => 'dop04'], ['file' => $test, 'filter' => 'dop24']],
    ],
    [
        'id' => 'DO-M27',
        'name' => '⚠️ switching off also disables the owner\'s LOGIN (a person who also works for the operator loses theirs)',
        'file' => $ctl,
        'old' => "                \$model->forceFill(['portal_disabled_at' => now()])->save();\n",
        'new' => "                \$model->forceFill(['portal_disabled_at' => now()])->save();\n                \$model->user?->forceFill(['status' => 'disabled'])->save();\n",
        'tests' => [['file' => $test, 'filter' => 'dop11'], ['file' => $test, 'filter' => 'dop13']],
    ],
    [
        'id' => 'DO-M28',
        'name' => '⚠️ switching off also disables the owner\'s TEAM rows (they would have to be restored one by one)',
        'file' => $ctl,
        'old' => "                \$model->forceFill(['portal_disabled_at' => now()])->save();\n",
        'new' => "                \$model->forceFill(['portal_disabled_at' => now()])->save();\n                OwnerUser::query()->where('owner_id', \$model->id)->update(['status' => 'disabled']);\n",
        'tests' => [['file' => $test, 'filter' => 'dop13'], ['file' => $test, 'filter' => 'dop15']],
    ],
    [
        'id' => 'DO-M29',
        'name' => 'the notice says nobody was emailed "yet" even while email IS being sent',
        'file' => $ctl,
        'old' => "(MailSafetyGuard::sendingNow() ? 'portal_no_email_sent' : 'portal_no_email_not_sent')",
        'new' => "'portal_no_email_not_sent'",
        'tests' => [['file' => $test, 'filter' => 'dop24']],
    ],

    // ── where the operator sees it ──────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'DO-M30',
        'name' => 'the owner\'s page stops showing the off state',
        'file' => $detail,
        'old' => "        if (\$record->portalIsOff()) {\n            return [\n                'label' => __('php/livewire/operator/operator_owner_detail.portal_off'),",
        'new' => "        if (false) {\n            return [\n                'label' => __('php/livewire/operator/operator_owner_detail.portal_off'),",
        'tests' => [['file' => $test, 'filter' => 'dop23']],
    ],
    [
        'id' => 'DO-M31',
        'name' => 'the page offers "switch off" for an owner whose login is not active',
        'file' => $detail,
        'old' => "'portalCanSwitchOff' => ! \$record->portalIsOff() && \$record->user instanceof User && (string) \$record->user->status === User::SIGN_IN_STATUS,",
        'new' => "'portalCanSwitchOff' => ! \$record->portalIsOff(),",
        'tests' => [['file' => $test, 'filter' => 'dop05']],
    ],
    [
        'id' => 'DO-M32',
        'name' => 'the close-a-period dialog claims an off owner can see the statement in their portal',
        'file' => $view,
        'old' => "__(\$portalOff ? 'livewire/operator/owner-detail.close_fact_portal_off' : 'livewire/operator/owner-detail.close_fact_portal',",
        'new' => "__('livewire/operator/owner-detail.close_fact_portal',",
        'tests' => [['file' => $test, 'filter' => 'dop23']],
    ],
    [
        'id' => 'DO-M33',
        'name' => 'the propose-a-rate dialog claims an off owner can answer in their portal',
        'file' => $view,
        'old' => "__(\$portalOff ? 'livewire/operator/owner-detail.propose_sub_portal_off' : 'livewire/operator/owner-detail.propose_sub',",
        'new' => "__('livewire/operator/owner-detail.propose_sub',",
        'tests' => [['file' => $test, 'filter' => 'dop23']],
    ],
    [
        'id' => 'DO-M34',
        'name' => 'the switch dialog says "not told by email" while email IS sent',
        'file' => $view,
        'old' => "{{ \$mailSends ? __('livewire/operator/owner-detail.portal_fact_email_sent') : __('livewire/operator/owner-detail.portal_fact_email_not_sent') }}",
        'new' => "{{ __('livewire/operator/owner-detail.portal_fact_email_not_sent') }}",
        'tests' => [['file' => $test, 'filter' => 'dop23']],
    ],
    [
        'id' => 'DO-M35',
        'name' => 'the Owners list stops marking an owner whose portal is off',
        'file' => 'resources/views/livewire/operator/owners.blade.php',
        'old' => "@if (\$row['portal_off'])",
        'new' => '@if (false)',
        'tests' => [['file' => $test, 'filter' => 'dop21']],
    ],
    [
        'id' => 'DO-M36',
        'name' => 'the Owners rows lose the portal_off fact',
        'file' => 'app/Support/Owner/OwnerRows.php',
        'old' => "'portal_off' => \$owner->portalIsOff(),",
        'new' => "'portal_off' => false,",
        'tests' => [['file' => $test, 'filter' => 'dop21']],
    ],
    [
        'id' => 'DO-M37',
        'name' => 'the Inbox lists an owner whose portal is off as one that can be messaged',
        'file' => 'app/Livewire/Operator/OperatorInbox.php',
        'old' => "'has_portal' => \$owner->user !== null && ! \$owner->portalIsOff(),",
        'new' => "'has_portal' => \$owner->user !== null,",
        'tests' => [['file' => $test, 'filter' => 'dop22']],
    ],
    [
        'id' => 'DO-M38',
        'name' => 'a conversation can be started with an owner whose portal is off',
        'file' => 'app/Http/Controllers/Operator/InboxActionController.php',
        'old' => 'if ($owner->portalIsOff()) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $test, 'filter' => 'dop22']],
    ],
];
