<?php

declare(strict_types=1);

// Mutation set payout-run-scope — the payout run kept inside its own organisation (A11, 2026-10-02): "Run payouts now" names
// this organisation's owners, its run key carries the organisation, "Last run" reads only this organisation's payouts, and
// the read-only sentence never ends "until ."; and DEC-n ("a pause stops every payout"): the payout service refuses a paused
// owner on create, pay and retry, the engine never selects one, the buttons skip and count them, the page lists them apart,
// and every sentence about a pause says so. Plus A12 — the Units export link takes the view's address at the click.
// Run with `php tests/Mutations/run.php --set=payout-run-scope`.

$t = 'tests/Feature/PayoutRunScopeAndPauseTest.php';
$retryTest = 'tests/Feature/OwnerPayoutRetryTest.php';
$unitsTest = 'tests/Feature/UnitsListRedesignTest.php';
$controller = 'app/Http/Controllers/Operator/PayoutRunActionController.php';
$page = 'app/Livewire/Operator/PayoutRun.php';
$pageView = 'resources/views/livewire/operator/payout-run.blade.php';
$service = 'app/Services/OwnerPayoutService.php';
$engine = 'app/Services/PayoutRunService.php';
$prefs = 'app/Models/OwnerPayoutPreference.php';
$retry = 'app/Http/Controllers/Owner/OwnerPayoutRetryController.php';
$rows = 'app/Support/Owner/OwnerRows.php';
$detail = 'resources/views/livewire/operator/owner-detail.blade.php';
$units = 'resources/views/livewire/units-list.blade.php';

return [
    // ── A11 — one organisation ───────────────────────────────────────────────────────────────────
    [
        'id' => 'PS-M1',
        'name' => 'Run payouts now passes no owner list — the engine selects every organisation',
        'file' => $controller,
        'old' => '        $run = $runs->run(PayoutRunModel::TRIGGER_MANUAL, $ownerIds,',
        'new' => '        $run = $runs->run(PayoutRunModel::TRIGGER_MANUAL, null,',
        'tests' => [['file' => $t, 'filter' => 'runs payouts only for the organisation that pressed']],
    ],
    [
        'id' => 'PS-M2',
        'name' => 'Run payouts now reads payable statements past the tenant scope',
        'file' => $controller,
        'old' => "        \$ids = OwnerStatement::query()->where('status', 'finalized')",
        'new' => "        \$ids = OwnerStatement::withoutGlobalScopes()->where('status', 'finalized')",
        'tests' => [['file' => $t, 'filter' => 'runs payouts only for the organisation that pressed']],
    ],
    [
        'id' => 'PS-M3',
        'name' => 'a run starts with nobody due here',
        'file' => $controller,
        'old' => '        if ($ownerIds === []) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'starts no run when nothing is due here']],
    ],
    [
        'id' => 'PS-M4',
        'name' => 'the manual run key leaves out the organisation — two organisations at once are one run',
        'file' => $controller,
        'old' => "            PayoutRunModel::TRIGGER_MANUAL.':'.app(TenantContext::class)->id().':'.now()->format('U-u'));",
        'new' => "            PayoutRunModel::TRIGGER_MANUAL.':'.now()->format('U-u'));",
        'tests' => [['file' => $t, 'filter' => 'shows under Last run only what this organisation']],
    ],
    [
        'id' => 'PS-M5',
        'name' => 'Last run finds the newest run payout in any organisation',
        'file' => $page,
        'old' => "        \$lastRunPayout = Payout::query()->whereNotNull('payout_run_id')",
        'new' => "        \$lastRunPayout = Payout::withoutGlobalScopes()->whereNotNull('payout_run_id')",
        'tests' => [['file' => $t, 'filter' => 'shows under Last run only what this organisation']],
    ],
    [
        'id' => 'PS-M6',
        'name' => 'Last run counts every organisation’s payouts in that run',
        'file' => $page,
        'old' => "            \$here = Payout::query()->where('payout_run_id', \$lastRunPayout->payout_run_id)",
        'new' => "            \$here = Payout::withoutGlobalScopes()->where('payout_run_id', \$lastRunPayout->payout_run_id)",
        'tests' => [['file' => $t, 'filter' => 'counts only this organisation’s payouts under Last run']],
    ],
    [
        'id' => 'PS-M7',
        'name' => 'the read-only sentence ends "until ." with no resume date',
        'file' => $controller,
        'old' => "        return \$until !== null && \$until !== ''\n            ? __('php/http/controllers/operator/payout_run_action_controller.workspace_paused_until'",
        'new' => "        return true\n            ? __('php/http/controllers/operator/payout_run_action_controller.workspace_paused_until'",
        'tests' => [['file' => $t, 'filter' => 'never ends the payout run']],
    ],

    // ── DEC-n — a pause stops every payout ───────────────────────────────────────────────────────
    [
        'id' => 'PS-M8',
        'name' => 'the service creates a payout for a paused owner',
        'file' => $service,
        'old' => '        if (OwnerPayoutPreference::isPaused((string) $statement->owner_id)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'refuses a paused owner in the payout service itself']],
    ],
    [
        'id' => 'PS-M9',
        'name' => 'the service pays a paused owner’s waiting payout',
        'file' => $service,
        'old' => "            //    left exactly as it was, so resuming makes it payable again.\n            if (OwnerPayoutPreference::isPaused((string) \$locked->owner_id)) {",
        'new' => "            //    left exactly as it was, so resuming makes it payable again.\n            if (false) {",
        'tests' => [['file' => $t, 'filter' => 'refuses to pay a waiting payout by hand']],
    ],
    [
        'id' => 'PS-M10',
        'name' => 'the service re-queues a paused owner’s failed payout',
        'file' => $service,
        'old' => "            // ⚠️ DEC-n — a paused owner's payout is not re-queued either (no payout is created while paused).\n            if (OwnerPayoutPreference::isPaused((string) \$locked->owner_id)) {",
        'new' => "            // ⚠️ DEC-n — a paused owner's payout is not re-queued either (no payout is created while paused).\n            if (false) {",
        'tests' => [['file' => $retryTest, 'filter' => 'refuses a retry while the owner']],
    ],
    [
        'id' => 'PS-M11',
        'name' => 'no owner is ever read as paused',
        'file' => $prefs,
        'old' => "        return static::query()->where('owner_id', \$ownerId)->where('paused', true)->exists();",
        'new' => '        return false;',
        'tests' => [['file' => $t, 'filter' => 'refuses a paused owner in the payout service itself']],
    ],
    [
        'id' => 'PS-M12',
        'name' => 'the engine selects a paused owner it is named',
        'file' => $engine,
        'old' => "            return Owner::query()->whereIn('id', \$ownerIds)->whereNotIn('id', \$paused)->get();",
        'new' => "            return Owner::query()->whereIn('id', \$ownerIds)->get();",
        'tests' => [['file' => $t, 'filter' => 'refuses a paused owner in the payout service itself']],
    ],
    [
        'id' => 'PS-M13',
        'name' => 'the engine’s every-organisation run selects a paused owner',
        'file' => $engine,
        'old' => '            ->reject(fn ($id): bool => in_array((string) $id, $paused, true))->values()->all();',
        'new' => '            ->values()->all();',
        'tests' => [['file' => $t, 'filter' => 'refuses a paused owner in the payout service itself']],
    ],
    [
        'id' => 'PS-M14',
        'name' => 'Run payouts now neither skips nor counts a paused owner',
        'file' => $controller,
        'old' => '        $active = $ids->reject(fn (string $id): bool => isset($pausedIds[$id]))->values()->all();',
        'new' => '        $active = $ids->values()->all();',
        'tests' => [['file' => $t, 'filter' => 'skips a paused owner in Run payouts now']],
    ],
    [
        'id' => 'PS-M15',
        'name' => 'Create payouts tries a paused owner’s statement',
        'file' => $controller,
        'old' => '        $ready = $payable->reject(fn (OwnerStatement $s): bool => isset($pausedIds[(string) $s->owner_id]))->values();',
        'new' => '        $ready = $payable->values();',
        'tests' => [['file' => $t, 'filter' => 'skips a paused owner in Run payouts now']],
    ],
    [
        'id' => 'PS-M16',
        'name' => 'Pay all tries a paused owner’s waiting payout',
        'file' => $controller,
        'old' => '        $pending = $waiting->reject(fn (Payout $p): bool => isset($pausedIds[(string) $p->owner_id]))->values();',
        'new' => '        $pending = $waiting->values();',
        'tests' => [['file' => $t, 'filter' => 'skips a paused owner in Run payouts now']],
    ],
    [
        'id' => 'PS-M17',
        'name' => 'the payout run lists a paused owner as ready to pay',
        'file' => $page,
        'old' => '        $ready = $payable->reject(fn (OwnerStatement $s): bool => $pausedIds->has((string) $s->owner_id))->values();',
        'new' => '        $ready = $payable->values();',
        'tests' => [['file' => $t, 'filter' => 'lists a paused owner under Payouts paused']],
    ],
    [
        'id' => 'PS-M18',
        'name' => 'a paused owner’s waiting payout is offered Mark paid',
        'file' => $pageView,
        'old' => "                                                @if (\$row['paused'])",
        'new' => '                                                @if (false)',
        'tests' => [['file' => $t, 'filter' => 'lists a paused owner under Payouts paused']],
    ],
    [
        'id' => 'PS-M19',
        'name' => 'the batch Mark paid counts a paused owner’s payout',
        'file' => $page,
        'old' => "            'pendingPayable' => \$pending->reject(fn (Payout \$p): bool => \$pausedIds->has((string) \$p->owner_id))->count(),",
        'new' => "            'pendingPayable' => \$pending->count(),",
        'tests' => [['file' => $t, 'filter' => 'lists a paused owner under Payouts paused']],
    ],
    [
        'id' => 'PS-M20',
        'name' => 'the owner’s pay status says only the automatic run skips a pause',
        'file' => $rows,
        'old' => "__('php/support/owner/owner_rows.reason_paused_until_resumed')]",
        'new' => "'Paused by your team — the automatic payout run skips them']",
        'tests' => [['file' => $t, 'filter' => 'says a pause stops every payout']],
    ],
    [
        'id' => 'PS-M21',
        'name' => 'the owner’s page says amounts carry forward',
        // ⚠️ I18N-01: the sentence is now the page's English value (its markup and "Paused." with it)
        'file' => 'lang/en/livewire/operator/owner-detail.php',
        'old' => 'No payout is created or sent until resumed — closed statements stay owed.',
        'new' => 'Amounts accumulate and carry forward until resumed.',
        'tests' => [['file' => $t, 'filter' => 'says a pause stops every payout']],
    ],
    [
        'id' => 'PS-M22',
        'name' => 'the owner portal calls a paused retry "no longer failed"',
        'file' => $retry,
        'old' => '        } catch (PayoutsPausedException) {',
        'new' => '        } catch (\LogicException) {',
        'tests' => [['file' => $retryTest, 'filter' => 'refuses a retry while the owner']],
    ],

    [
        'id' => 'PS-M24',
        'name' => 'Create payouts counts a paused owner’s statements as paused owners',
        'file' => $controller,
        'old' => '        $paused = $payable->filter(fn (OwnerStatement $s): bool => isset($pausedIds[(string) $s->owner_id]))->pluck(\'owner_id\')->unique()->count();',
        'new' => '        $paused = $payable->filter(fn (OwnerStatement $s): bool => isset($pausedIds[(string) $s->owner_id]))->count();',
        'tests' => [['file' => $t, 'filter' => 'skips a paused owner in Run payouts now']],
    ],

    [
        'id' => 'PS-M25',
        'name' => 'the owner portal offers Retry payout while payouts are paused',
        'file' => 'app/Support/Owner/PayoutRecovery.php',
        'old' => "            && self::blockers(\$owner) === []\n            && ! OwnerPayoutPreference::isPaused((string) \$owner->getKey());",
        'new' => '            && self::blockers($owner) === [];',
        'tests' => [['file' => $retryTest, 'filter' => 'hides Retry payout while']],
    ],
    [
        'id' => 'PS-M26',
        'name' => 'the paused retry banner says nothing about the pause',
        'file' => 'resources/views/livewire/owner/payouts.blade.php',
        'old' => "                @elseif (\$recovery['paused'])",
        'new' => '                @elseif (false)',
        'tests' => [['file' => $retryTest, 'filter' => 'hides Retry payout while']],
    ],

    // ── A12 — the Units export follows the view ──────────────────────────────────────────────────
    [
        'id' => 'PS-M23',
        'name' => 'the Units Export link keeps the address the page was loaded with',
        'file' => $units,
        'old' => "\n                           x-on:click=\"\$el.href = \$el.dataset.exportBase + location.search\">",
        'new' => '>',
        'tests' => [['file' => $unitsTest, 'filter' => 'A12']],
    ],
];
