<?php

declare(strict_types=1);

// Mutation set owner-module — the owners module pass (2026-10-03): the owner's page (the commission change by ONE rule — the
// latest change wins, every other term carries forward, a change planned for later is never overlapped; portal access no
// longer written from Edit; a refused write reopens its own dialog; dates in words; the waiting invite's actions), the payout
// run (no Run button while the engine is off; a confirm in front of every money form), the unit requests page (approve only
// into the person's properties — DEC-k; chips that count; the review dialogs) and the Owners list's unit picker.
// Run with `php tests/Mutations/run.php --set=owner-module`.

$fixes = 'tests/Feature/OwnerPageFixesTest.php';
$look = 'tests/Feature/OwnerPageRedesignTest.php';
$payTest = 'tests/Feature/PayoutRunPageTest.php';
$urTest = 'tests/Feature/UnitRequestsPageTest.php';
$gateTest = 'tests/Feature/OperatorControlGateTest.php';
$ownersTest = 'tests/Feature/OwnersPageRedesignTest.php';

$change = 'app/Support/Owner/CommissionChange.php';
$manage = 'app/Http/Controllers/Operator/OwnerManageController.php';
$invites = 'app/Http/Controllers/Operator/OwnerInviteActionController.php';
$proposals = 'app/Http/Controllers/Operator/OperatorRateProposalController.php';
$detail = 'app/Livewire/Operator/OperatorOwnerDetail.php';
$detailView = 'resources/views/livewire/operator/owner-detail.blade.php';
$payPage = 'app/Livewire/Operator/PayoutRun.php';
$payView = 'resources/views/livewire/operator/payout-run.blade.php';
$urAction = 'app/Http/Controllers/Operator/UnitRequestActionController.php';
$urPage = 'app/Livewire/Operator/OperatorUnitRequests.php';
$urView = 'resources/views/livewire/operator/unit-requests.blade.php';
$ownersView = 'resources/views/livewire/operator/owners.blade.php';

return [
    // ── The commission — CommissionChange ───────────────────────────────────────────────────────────
    [
        'id' => 'OM-M1',
        'name' => 'a change is decided against today’s agreement again, not the one governing its start day',
        'file' => $change,
        'old' => '            $covering = self::covering($ownerId, $startsOn);',
        'new' => '            $covering = self::covering($ownerId, $today);',
        'tests' => [['file' => $fixes, 'filter' => 'lets the latest commission change win']],
    ],
    [
        'id' => 'OM-M2',
        'name' => 'the same rate as the one coming is written again',
        'file' => $change,
        'old' => '            if ($pct !== null && $covering->count() === 1 && $governing->commission_pct !== null && round((float) $governing->commission_pct, 2) === $pct) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $fixes, 'filter' => 'writes nothing when the rate set is the one already coming']],
    ],
    [
        'id' => 'OM-M3',
        'name' => 'a change replaced before it started stays active',
        'file' => $change,
        'old' => "                    + (\$neverStarted ? ['status' => 'ended'] : []))->save();",
        'new' => '                    + [])->save();',
        'tests' => [['file' => $fixes, 'filter' => 'undoes a waiting change']],
    ],
    [
        'id' => 'OM-M4',
        'name' => 'only the payout schedule and currency carry forward — the fee and the rules are dropped again',
        'file' => $change,
        'old' => '            $terms = $base instanceof ManagementAgreement ? $base->only(AgreementAmendmentService::TERM_KEYS) : [];',
        'new' => "            \$terms = \$base instanceof ManagementAgreement ? \$base->only(['payout_schedule', 'payout_currency']) : [];",
        'tests' => [['file' => $fixes, 'filter' => 'keeps every other term when only the commission changes']],
    ],
    [
        'id' => 'OM-M5',
        'name' => 'a change runs open-ended over one already planned for later',
        'file' => $change,
        'old' => '                $planned !== null ? CarbonImmutable::parse($planned->effective_from->toDateString())->subDay() : null,',
        'new' => '                null,',
        'tests' => [['file' => $fixes, 'filter' => 'fits a change in before a planned one even over a row an older write left open-ended']],
    ],
    [
        'id' => 'OM-M6',
        'name' => 'the governing agreement is the database’s choice, not the newest',
        'file' => $change,
        'old' => "            ->effectiveOn(\$date)\n            ->orderByDesc('effective_from')\n            ->orderByDesc('created_at')\n            ->get();",
        'new' => "            ->effectiveOn(\$date)\n            ->get();",
        'tests' => [['file' => $fixes, 'filter' => 'collapses agreements an older write left on the same day into one']],
    ],
    [
        'id' => 'OM-M7',
        'name' => 'a change starts two months out instead of next month',
        'file' => $change,
        'old' => '        return self::day($today)->startOfMonth()->addMonth();',
        'new' => '        return self::day($today)->startOfMonth()->addMonths(2);',
        'tests' => [['file' => $fixes, 'filter' => 'lets the latest commission change win']],
    ],

    // ── The owner's page writes — OwnerManageController ─────────────────────────────────────────────
    [
        'id' => 'OM-M8',
        'name' => 'Edit owner writes portal access from the request again',
        'file' => $manage,
        'old' => "        \$pct = round((float) \$validated['commission_pct'], 2);\n        \$display = ",
        'new' => "        if (\$request->has('portal_active')) {\n            \$model->user?->forceFill(['status' => \$request->boolean('portal_active') ? 'active' : 'inactive'])->save();\n        }\n        \$pct = round((float) \$validated['commission_pct'], 2);\n        \$display = ",
        'tests' => [['file' => $fixes, 'filter' => 'never switches a login off from Edit owner']],
    ],
    [
        'id' => 'OM-M9',
        'name' => 'an undone change is announced as a change to today’s rate',
        'file' => $manage,
        'old' => '        if ($wasWaiting && $today->commission_pct !== null && round((float) $today->commission_pct, 2) === $pct) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $fixes, 'filter' => 'undoes a waiting change']],
    ],
    [
        'id' => 'OM-M10',
        'name' => 'a mistyped name no longer comes back — Remove owner stays shut',
        'file' => $manage,
        'old' => "                ->withInput(\$request->only('_form', 'confirm_name'));",
        'new' => ';',
        'tests' => [['file' => $fixes, 'filter' => 'reopens Remove owner when the typed name does not match']],
    ],
    [
        'id' => 'OM-M11',
        'name' => 'a wrong password is refused in the framework’s words',
        'file' => $manage,
        'old' => "            'current_password.current_password' => __('php/http/controllers/operator/owner_manage_controller.password_wrong'),\n            'confirm_name.required'",
        'new' => "            'confirm_name.required'",
        'tests' => [['file' => $fixes, 'filter' => 'reopens Remove owner with its refusal beside the password']],
    ],
    [
        'id' => 'OM-M12',
        'name' => 'a backwards period is refused in the framework’s words',
        'file' => $manage,
        'old' => "            'period_end.after_or_equal' => __('php/http/controllers/operator/owner_manage_controller.period_end_after_start'),",
        'new' => '',
        'tests' => [['file' => $fixes, 'filter' => 'reopens Close a period when the period ends before it starts']],
    ],
    [
        'id' => 'OM-M13',
        'name' => 'a resend from the owner’s page lands on the Owners list',
        'file' => $invites,
        'old' => "        if (\$request->input('return') === 'owner' && \$invite->owner_id !== null",
        'new' => "        if (\$request->input('return') === 'nobody' && \$invite->owner_id !== null",
        'tests' => [['file' => $fixes, 'filter' => 'offers Send invite again and Cancel invite']],
    ],
    [
        'id' => 'OM-M14',
        'name' => 'a proposal with its dates the wrong way round is refused in the framework’s words',
        'file' => $proposals,
        'old' => "            'to_date.after_or_equal' => __('php/http/controllers/operator/operator_rate_proposal_controller.to_date_after_from'),",
        'new' => '',
        'tests' => [['file' => $fixes, 'filter' => 'reopens Propose a rate change']],
    ],

    // ── The owner's page — what it shows ────────────────────────────────────────────────────────────
    [
        'id' => 'OM-M15',
        'name' => 'how far the owner is closed is printed as a database date',
        'file' => $detail,
        'old' => "            'lockedThroughWords' => \$lockedThrough !== null ? Carbon::parse(\$lockedThrough)->translatedFormat(LocalDate::pattern('j M Y')) : null,",
        'new' => "            'lockedThroughWords' => \$lockedThrough,",
        'tests' => [['file' => $fixes, 'filter' => 'says how far the owner is already closed in words']],
    ],
    [
        'id' => 'OM-M16',
        'name' => 'the change waiting to start is never named',
        'file' => $detailView,
        'old' => "                                    @if (\$commission['next'])",
        'new' => '                                    @if (false)',
        'tests' => [['file' => $fixes, 'filter' => 'says on the page what the commission is now']],
    ],
    [
        'id' => 'OM-M17',
        'name' => 'a change replaced before it started reads as an ordinary row',
        'file' => $detail,
        // (I18N-01: the check moved into replacedBeforeStart(), which both the note and `never_applied` read — as the note did before)
        'old' => '        return $from !== null && $to !== null && $to->lt($from);',
        'new' => '        return false;',
        'tests' => [['file' => $fixes, 'filter' => 'undoes a waiting change']],
    ],
    [
        'id' => 'OM-M18',
        'name' => 'cancelling an invite never warns that a never-activated owner leaves the list',
        'file' => $detail,
        'old' => "            'inviteCancelRemoves' => (string) \$record->status === 'inactive'",
        'new' => "            'inviteCancelRemoves' => false && (string) \$record->status === 'inactive'",
        'tests' => [['file' => $fixes, 'filter' => 'offers Send invite again and Cancel invite']],
    ],
    [
        'id' => 'OM-M19',
        'name' => 'the waiting invite’s actions are not offered on the owner’s page',
        'file' => $detailView,
        'old' => '                        @if ($canManage && $invite !== null)',
        'new' => '                        @if (false)',
        'tests' => [['file' => $fixes, 'filter' => 'offers Send invite again and Cancel invite']],
    ],
    [
        'id' => 'OM-M20',
        'name' => 'a refused removal no longer reopens its dialog',
        'file' => $detailView,
        'old' => "                removeOpen: @js(\$vyOpen === 'remove'),",
        'new' => '                removeOpen: false,',
        'tests' => [['file' => $fixes, 'filter' => 'reopens Remove owner with its refusal beside the password']],
    ],
    [
        'id' => 'OM-M21',
        'name' => 'a refused period no longer reopens its dialog',
        'file' => $detailView,
        'old' => "pauseOpen: false, closeOpen: @js(\$vyOpen === 'close'),",
        'new' => 'pauseOpen: false, closeOpen: false,',
        'tests' => [['file' => $fixes, 'filter' => 'reopens Close a period when the period ends before it starts']],
    ],
    [
        'id' => 'OM-M22',
        'name' => 'Close a period in the header is offered to a role without payouts.manage',
        'file' => $detailView,
        'old' => "                        @if (\$canPayout)\n                            <button type=\"button\" class=\"vy-btn vy-btn--ghost vyo-sm\" x-on:click=\"closeOpen = true\">",
        'new' => "                        @if (true)\n                            <button type=\"button\" class=\"vy-btn vy-btn--ghost vyo-sm\" x-on:click=\"closeOpen = true\">",
        'tests' => [['file' => $look, 'filter' => 'puts the actions on the owner in one bar']],
    ],
    [
        'id' => 'OM-M41',
        'name' => 'Change commission and Pause payouts in the header reach a role without users.manage',
        'file' => $detailView,
        'old' => "                    <div class=\"vyo-hero-actions\">\n                        @if (\$canManage)",
        'new' => "                    <div class=\"vyo-hero-actions\">\n                        @if (true)",
        'tests' => [['file' => $look, 'filter' => 'puts the actions on the owner in one bar']],
    ],
    [
        'id' => 'OM-M23',
        'name' => 'the password’s row is labelled as before — the founder’s report',
        'file' => $detailView,
        'old' => '<label class="vy-field-label" for="vyod-remove-pw">{{ __(\'livewire/operator/owner-detail.confirm_its_you\') }}</label>',
        'new' => '<label class="vy-field-label" for="vyod-remove-pw">Confirm with your password</label>',
        'tests' => [['file' => $look, 'filter' => 'asks for the password on its own row in Remove owner']],
    ],
    [
        'id' => 'OM-M24',
        'name' => 'Remove owner’s action button says something else',
        'file' => $detailView,
        'old' => '<button type="submit" class="vy-btn vy-btn--danger">{{ __(\'livewire/operator/owner-detail.remove_owner\') }}</button>',
        'new' => '<button type="submit" class="vy-btn vy-btn--danger">Remove owner permanently</button>',
        'tests' => [['file' => $look, 'filter' => 'asks for the password on its own row in Remove owner']],
    ],

    // ── The payout run ───────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'OM-M25',
        'name' => 'Run payouts now is offered while the engine is off (D-PAYRUN-BUTTON)',
        'file' => $payView,
        'old' => '                @if ($canPayout && $engineOn)',
        'new' => '                @if ($canPayout)',
        'tests' => [['file' => $payTest, 'filter' => 'offers no Run payouts now while the payout engine is off']],
    ],
    [
        'id' => 'OM-M26',
        'name' => 'the page believes the engine is always on',
        'file' => $payPage,
        'old' => "            'engineOn' => (bool) config('services.payouts.engine_enabled', false),",
        'new' => "            'engineOn' => true,",
        'tests' => [['file' => $payTest, 'filter' => 'offers no Run payouts now while the payout engine is off']],
    ],
    [
        'id' => 'OM-M27',
        'name' => 'a single Create payout posts without asking',
        'file' => $payView,
        'old' => "                                                <form method=\"POST\" action=\"{{ route('app.payouts.create') }}\" x-on:submit=\"ask(\$event)\" data-guard-submit",
        'new' => "                                                <form method=\"POST\" action=\"{{ route('app.payouts.create') }}\" data-guard-submit",
        'tests' => [['file' => $payTest, 'filter' => 'asks before creating a payout']],
    ],
    [
        'id' => 'OM-M28',
        'name' => 'the create confirm stops saying it moves no money',
        'file' => $payView,
        'old' => "'facts' => [__('livewire/operator/payout-run.create_one_fact_records'),",
        'new' => "'facts' => ['It records what you owe them.',",
        'tests' => [['file' => $payTest, 'filter' => 'asks before creating a payout']],
    ],
    [
        'id' => 'OM-M29',
        'name' => 'a single Mark paid posts without asking',
        'file' => $payView,
        'old' => "                                                    <form method=\"POST\" action=\"{{ route('app.payouts.pay') }}\" x-on:submit=\"ask(\$event)\" data-guard-submit",
        'new' => "                                                    <form method=\"POST\" action=\"{{ route('app.payouts.pay') }}\" data-guard-submit",
        'tests' => [['file' => $payTest, 'filter' => 'asks before marking a payout paid']],
    ],
    [
        'id' => 'OM-M30',
        'name' => 'the Mark paid confirm stops saying Vyrobel sends no money',
        'file' => $payView,
        'old' => "'facts' => [__('livewire/operator/payout-run.mark_one_fact_records'), __('livewire/operator/payout-run.fact_audit_log')]]",
        'new' => "'facts' => ['Vyrobel records the payout and its statement as paid.', __('livewire/operator/payout-run.fact_audit_log')]]",
        'tests' => [['file' => $payTest, 'filter' => 'asks before marking a payout paid']],
    ],

    // ── Unit requests ────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'OM-M31',
        'name' => 'a request is approved into any property of the organisation again (DEC-k)',
        'file' => $urAction,
        'old' => "        \$property = Property::query()->when(\$ids !== [], fn (\$q) => \$q->whereIn('id', \$ids))->findOrFail(\$data['property_id']);",
        'new' => "        \$property = Property::query()->findOrFail(\$data['property_id']);",
        'tests' => [['file' => $urTest, 'filter' => 'approves a request only into a property the person works in']],
    ],
    [
        'id' => 'OM-M32',
        'name' => 'the approve dialog offers every property of the organisation again',
        'file' => $urPage,
        'old' => "            foreach (Property::query()->when(\$ids !== [], fn (\$q) => \$q->whereIn('id', \$ids))->with('roomTypes:id,property_id,name')",
        'new' => "            foreach (Property::query()->with('roomTypes:id,property_id,name')",
        'tests' => [['file' => $urTest, 'filter' => 'offers in the approve dialog only the properties']],
    ],
    [
        'id' => 'OM-M33',
        'name' => 'the list opens on everything, not on what is waiting',
        'file' => $urPage,
        'old' => "(\$counts['pending'] > 0 ? 'pending' : 'all');",
        'new' => "'all';",
        'tests' => [['file' => $urTest, 'filter' => 'lists the requests behind chips']],
    ],
    [
        'id' => 'OM-M34',
        'name' => 'an empty Withdrawn chip is shown',
        'file' => $urPage,
        'old' => "            if (\$key === 'withdrawn' && \$counts['withdrawn'] === 0) {",
        'new' => '            if (false) {',
        'tests' => [['file' => $urTest, 'filter' => 'lists the requests behind chips']],
    ],
    [
        'id' => 'OM-M35',
        'name' => 'a missing room type is refused in the framework’s words',
        'file' => $urAction,
        'old' => "            'room_type_id.required' => __('php/http/controllers/operator/unit_request_action_controller.choose_room_type'),",
        'new' => '',
        'tests' => [['file' => $urTest, 'filter' => 'reopens Approve for the same request']],
    ],
    [
        'id' => 'OM-M36',
        'name' => 'a refused approve no longer reopens its dialog',
        'file' => $urView,
        'old' => "                approveOpen: @js(\$vyOpen === 'approve'),",
        'new' => '                approveOpen: false,',
        'tests' => [['file' => $urTest, 'filter' => 'reopens Approve for the same request']],
    ],
    [
        'id' => 'OM-M37',
        'name' => 'a request’s review addresses reach a role without units.manage',
        'file' => $urView,
        'old' => "<article class=\"vyo-ur-item\" @if (\$canManage && \$r['status'] === 'pending') data-review=",
        'new' => "<article class=\"vyo-ur-item\" @if (\$r['status'] === 'pending') data-review=",
        'tests' => [['file' => $gateTest, 'filter' => 'offers unit-request Approve and Decline only to a role holding units.manage']],
    ],
    [
        'id' => 'OM-M38',
        'name' => 'the review buttons reach a role without units.manage',
        'file' => $urView,
        'old' => "                        @if (\$canManage && \$r['status'] === 'pending')\n                            <div class=\"vyo-ur-actions\">",
        'new' => "                        @if (\$r['status'] === 'pending')\n                            <div class=\"vyo-ur-actions\">",
        'tests' => [['file' => $gateTest, 'filter' => 'offers unit-request Approve and Decline only to a role holding units.manage']],
    ],

    // ── The Owners list — the unit picker ───────────────────────────────────────────────────────────
    [
        'id' => 'OM-M39',
        'name' => 'a row of units that cannot be picked is shown when every unit has an owner',
        'file' => $ownersView,
        'old' => '                                    @if (count($inviteUnits) > 0 && $vyFreeUnits === 0)',
        'new' => '                                    @if (false)',
        'tests' => [['file' => $ownersTest, 'filter' => 'says so in one line when every unit already has an owner']],
    ],
    [
        'id' => 'OM-M40',
        'name' => 'the hint promises units can be given later from the owner’s page',
        'file' => $ownersView,
        'old' => "[__('livewire/operator/owners.units_hint_none')]",
        'new' => "['None chosen — you can give units later from the owner’s page.']",
        'tests' => [['file' => $ownersTest, 'filter' => 'never says units can be given to an owner later']],
    ],

    // ── The review's findings (2026-10-03) — each puts the old behaviour back ───────────────────────
    [
        'id' => 'OM-M42',
        'name' => 'only the newest agreement covering the start day is closed — an older stacked one stays active',
        'file' => $change,
        'old' => '            foreach ($covering as $agreement) {',
        'new' => '            foreach ($covering->take(1) as $agreement) {',
        'tests' => [['file' => $fixes, 'filter' => 'collapses agreements an older write left on the same day into one']],
    ],
    [
        'id' => 'OM-M43',
        'name' => 'an owner with no agreement still waits for the 1st',
        'file' => $change,
        'old' => '        return self::governing($ownerId, $today) === null ? $today : self::firstOfNextMonth($today);',
        'new' => '        return self::firstOfNextMonth($today);',
        'tests' => [['file' => $fixes, 'filter' => 'starts today for an owner with no agreement in effect']],
    ],
    [
        'id' => 'OM-M44',
        'name' => 'a fixed end date is dropped — the change runs open-ended',
        'file' => $change,
        'old' => '                $governing?->effective_to !== null ? CarbonImmutable::parse($governing->effective_to->toDateString()) : null,',
        'new' => '                null,',
        'tests' => [['file' => $fixes, 'filter' => 'keeps a fixed end date']],
    ],
    [
        'id' => 'OM-M45',
        'name' => 'a save at the newest row’s rate leaves stacked rows as they are',
        'file' => $change,
        'old' => '            if ($pct !== null && $covering->count() === 1 && $governing->commission_pct !== null',
        'new' => '            if ($pct !== null && $governing !== null && $governing->commission_pct !== null',
        'tests' => [['file' => $fixes, 'filter' => 'collapses agreements an older write left on the same day into one']],
    ],
    [
        'id' => 'OM-M46',
        'name' => 'a removed owner’s request is approved into a unit owned by an archived owner',
        'file' => 'app/Services/OwnerUnitRequestService.php',
        'old' => '                if (! Owner::query()->whereKey($locked->owner_id)->exists()) {',
        'new' => '                if (false) {',
        'tests' => [['file' => $urTest, 'filter' => 'refuses to approve a request whose owner was removed']],
    ],
    [
        'id' => 'OM-M47',
        'name' => 'Approve is offered on a removed owner’s request',
        'file' => $urView,
        'old' => "                                @unless (\$r['owner_removed'])",
        'new' => '                                @unless (false)',
        'tests' => [['file' => $urTest, 'filter' => 'refuses to approve a request whose owner was removed']],
    ],
    [
        'id' => 'OM-M48',
        'name' => 'a decline says the owner was notified when nothing was sent',
        'file' => $urAction,
        'old' => "        return \$this->back('notice', __('php/http/controllers/operator/unit_request_action_controller.request_declined', ['name' => \$model->requested_name]));",
        'new' => "        return \$this->back('notice', 'Request declined — the owner has been notified.');",
        'tests' => [['file' => $urTest, 'filter' => 'says honestly whether the owner is told']],
    ],
];
