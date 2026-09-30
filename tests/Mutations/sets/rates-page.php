<?php

declare(strict_types=1);

// Mutation set rates-page — the Rates page rebuilt (2026-10-01): every dialog centred, all fourteen nights on screen with
// Friday and Saturday marked as the weekend, click-or-drag to edit, one cell design matching its legend, bulk edit on its own
// dates, a price never taken to zero or past the ceiling, seasons refused while paused, plans that can actually be priced,
// refusals as alerts, every write back on the page it started from — and DEC-l, rate writes inside the person's properties.
// Run with `php tests/Mutations/run.php --set=rates-page`.

$fix = 'tests/Feature/RatesPageFixesTest.php';
$look = 'tests/Feature/RatesPageRedesignTest.php';
$seasonTest = 'tests/Feature/SeasonEditDeleteTest.php';
$bulkUnits = 'tests/Feature/BulkUnitRateOverrideTest.php';
$xshow = 'tests/Feature/XShowInlineDisplayContractTest.php';
$rates = 'app/Http/Controllers/Operator/RateActionController.php';
$bulk = 'app/Services/RateBulkService.php';
$seasons = 'app/Http/Controllers/Operator/SeasonActionController.php';
$inventory = 'app/Http/Controllers/Operator/InventoryActionController.php';
$units = 'app/Http/Controllers/Operator/UnitRateActionController.php';
$overrides = 'app/Services/UnitRateOverrideService.php';
$grid = 'app/Livewire/Operator/RatesGrid.php';
$view = 'resources/views/livewire/operator/rates-grid.blade.php';
$cell = 'resources/views/livewire/operator/partials/rate-cell.blade.php';
$css = 'resources/views/components/layouts/operator.blade.php';

return [
    // ── money ────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'RP-M1',
        'name' => 'a 100% decrease is accepted (every night given away at zero)',
        'file' => $rates,
        'old' => "            if (\$mode === 'decrease_pct' && (float) \$raw >= 100) {",
        'new' => '            if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'never writes a price of']],
    ],
    [
        'id' => 'RP-M2',
        'name' => 'a change that rounds a night below one cent is written',
        'file' => $bulk,
        'old' => '                    if ($minor < 1) {',
        'new' => '                    if ($minor < 0) {',
        'tests' => [['file' => $fix, 'filter' => 'never writes a price of']],
    ],
    [
        'id' => 'RP-M3',
        'name' => 'bulk edit takes a price past the page ceiling',
        'file' => $bulk,
        'old' => '                    if ($minor > self::MAX_PRICE_MINOR) {',
        'new' => '                    if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'never takes a price past']],
    ],
    [
        'id' => 'RP-M4',
        'name' => 'a season price has no ceiling',
        'file' => $seasons,
        'old' => "        \$plan = app(RateScope::class)->ratePlan(\$data['rate_plan_id']);\n\n        \$minor = MoneyInput::toMinor(\$data['price'], self::MAX_PRICE_MINOR);",
        'new' => "        \$plan = app(RateScope::class)->ratePlan(\$data['rate_plan_id']);\n\n        \$minor = MoneyInput::toMinor(\$data['price']);",
        'tests' => [['file' => $fix, 'filter' => 'caps a season price']],
    ],

    // ── DEC-l: inside the person's properties ────────────────────────────────────────────────────
    [
        'id' => 'RP-M5',
        'name' => '"All room types" means every room type in the organisation again',
        'file' => $rates,
        'old' => "            return app(RateScope::class)->roomTypes()->orderBy('name')->get()->all();",
        'new' => "            return RoomType::query()->orderBy('name')->get()->all();",
        'tests' => [['file' => $fix, 'filter' => 'reads "All room types"']],
    ],
    [
        'id' => 'RP-M6',
        'name' => 'one room type is looked up across the whole organisation',
        'file' => $rates,
        'old' => '        return [app(RateScope::class)->roomType($roomTypeId)];',
        'new' => '        return [RoomType::query()->findOrFail($roomTypeId)];',
        'tests' => [['file' => $fix, 'filter' => 'keeps every rate write inside']],
    ],
    [
        'id' => 'RP-M7',
        'name' => 'applying a season looks it up across the whole organisation',
        'file' => $seasons,
        'old' => "    public function apply(Request \$request, SeasonalRateService \$seasons, string \$season): RedirectResponse\n    {\n        \$model = app(RateScope::class)->season(\$season);",
        'new' => "    public function apply(Request \$request, SeasonalRateService \$seasons, string \$season): RedirectResponse\n    {\n        \$model = RateSeason::query()->findOrFail(\$season);",
        'tests' => [['file' => $fix, 'filter' => 'keeps every rate write inside']],
    ],
    [
        'id' => 'RP-M8',
        'name' => 'a rate plan can be added to another property’s room type',
        'file' => $inventory,
        'old' => "        \$roomType = app(RateScope::class)->roomType(\$data['room_type_id']);",
        'new' => "        \$roomType = RoomType::query()->findOrFail(\$data['room_type_id']);",
        'tests' => [['file' => $fix, 'filter' => 'keeps every rate write inside']],
    ],

    // ── a chosen plan ────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'RP-M9',
        'name' => 'the chosen plan is ignored and the main plan priced instead',
        'file' => $rates,
        'old' => '                $plan = $chosenPlan ?? $roomType->primaryRatePlan();',
        'new' => '                $plan = $roomType->primaryRatePlan();',
        'tests' => [['file' => $fix, 'filter' => 'SECOND plan when it is chosen']],
    ],
    [
        'id' => 'RP-M10',
        'name' => 'a plan of another room type can be priced through this one',
        'file' => $rates,
        'old' => '            if ((string) $chosenPlan->room_type_id !== (string) $roomTypes[0]->getKey()) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'refuses to price a plan of another room type']],
    ],
    [
        'id' => 'RP-M11',
        'name' => 'a derived plan can be priced directly',
        'file' => $rates,
        'old' => '            if ($chosenPlan->derived_from_rate_plan_id !== null) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'refuses to price a plan of another room type']],
    ],
    [
        'id' => 'RP-M12',
        'name' => 'a chosen plan is accepted across "All room types"',
        'file' => $rates,
        'old' => "            if (count(\$roomTypes) !== 1 || \$request->input('room_type_id') === 'all') {",
        'new' => '            if (false) {',
        'tests' => [['file' => $fix, 'filter' => 'refuses to price a plan of another room type']],
    ],
    [
        'id' => 'RP-M13',
        'name' => 'the editor offers a plan choice where there is nothing to choose',
        'file' => $grid,
        'old' => '            if ($plans->count() < 2) {',
        'new' => '            if ($plans->count() < 1) {',
        'tests' => [['file' => $look, 'filter' => 'offers no plan choice']],
    ],
    [
        'id' => 'RP-M14',
        'name' => 'every plan says the grid prices it',
        'file' => $grid,
        'old' => '                $isMain = in_array((string) $plan->getKey(), $mainPlanIds, true);',
        'new' => '                $isMain = true;',
        'tests' => [['file' => $look, 'filter' => 'prices for the fortnight']],
    ],
    [
        'id' => 'RP-M15',
        'name' => 'a plan row loses its Set prices button',
        'file' => $view,
        'old' => "                                    @unless (\$plan['is_derived'])",
        'new' => '                                    @unless (true)',
        'tests' => [['file' => $look, 'filter' => 'prices for the fortnight']],
    ],
    [
        'id' => 'RP-M16',
        'name' => 'a new plan sends the operator to the grid, which cannot price it',
        'file' => $inventory,
        'old' => 'Use Set prices on its row to give it nightly prices — guests can be offered it once it has them.',
        'new' => 'Set its nightly rates in the grid to start selling.',
        'tests' => [['file' => $fix, 'filter' => 'does not send the operator to the grid']],
    ],

    // ── the editor and its sentences ─────────────────────────────────────────────────────────────
    [
        'id' => 'RP-M17',
        'name' => 'a maximum stay or an arrival/departure rule cannot be set on its own',
        'file' => $rates,
        'old' => '        if (! $setRate && ! $setMinStay && ! $setStopSell && ! $setMaxStay && ! $setCta && ! $setCtd) {',
        'new' => '        if (! $setRate && ! $setMinStay && ! $setStopSell) {',
        'tests' => [['file' => $fix, 'filter' => 'sets a maximum stay']],
    ],
    [
        'id' => 'RP-M18',
        'name' => 'the success sentence joins its parts with "+"',
        'file' => $rates,
        'old' => "        \$list = \$what === [] ? (string) \$last : implode(', ', \$what).' and '.\$last;",
        'new' => "        \$list = implode(' + ', array_merge(\$what, [\$last]));",
        'tests' => [['file' => $fix, 'filter' => 'sets a maximum stay']],
    ],
    [
        'id' => 'RP-M19',
        'name' => 'a refusal arrives as a plain notice again',
        'file' => $rates,
        'old' => "        return RatesListParams::redirect(\$request, \$from)->with('rates_error', \$message);",
        'new' => "        return RatesListParams::redirect(\$request, \$from)->with('rates_notice', \$message);",
        'tests' => [['file' => $fix, 'filter' => 'shows a refused change as an alert']],
    ],
    [
        'id' => 'RP-M20',
        'name' => 'a per-unit refusal arrives as a plain notice again',
        'file' => $units,
        'old' => '    private function back(?string $from, ?string $message, bool $refused = true): RedirectResponse',
        'new' => '    private function back(?string $from, ?string $message, bool $refused = false): RedirectResponse',
        'tests' => [['file' => $bulkUnits, 'filter' => 'refuses to apply when the change set moved']],
    ],
    [
        'id' => 'RP-M21',
        'name' => 'pricing units individually reports rows written as nights',
        'file' => $units,
        'old' => "        \$nights = (int) \$result['nights'];",
        'new' => "        \$nights = (int) \$result['applied'];",
        'tests' => [['file' => $fix, 'filter' => 'records who priced units individually']],
    ],
    [
        'id' => 'RP-M22',
        'name' => 'pricing units individually leaves no trail',
        'file' => $overrides,
        'old' => "            \$this->audit->record('unit_rate.applied', \$plan, null, [",
        'new' => "            \$this->audit->record('unit_rate.noted', \$plan, null, [",
        'tests' => [['file' => $fix, 'filter' => 'records who priced units individually']],
    ],

    // ── seasons ──────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'RP-M23',
        'name' => 'a paused workspace can still save a season',
        'file' => $seasons,
        'old' => "    public function store(Request \$request): RedirectResponse\n    {\n        if (\$refused = \$this->paused(\$request)) {\n            return \$refused;\n        }\n",
        'new' => "    public function store(Request \$request): RedirectResponse\n    {\n",
        'tests' => [['file' => $fix, 'filter' => 'refuses every season write while the workspace is paused']],
    ],
    [
        'id' => 'RP-M24',
        'name' => 'a paused workspace can still apply a season (writes prices)',
        'file' => $seasons,
        'old' => "        \$model = app(RateScope::class)->season(\$season);   // DEC-l — inside the person's properties\n\n        if (\$refused = \$this->paused(\$request)) {\n            return \$refused;\n        }\n\n        \$preview = \$seasons->preview(\$model);",
        'new' => "        \$model = app(RateScope::class)->season(\$season);   // DEC-l — inside the person's properties\n\n        \$preview = \$seasons->preview(\$model);",
        'tests' => [['file' => $fix, 'filter' => 'refuses every season write while the workspace is paused']],
    ],
    [
        'id' => 'RP-M25',
        'name' => 'the season form speaks in Laravel’s default words again',
        'file' => $seasons,
        'old' => "            'price' => ['required', 'string'],\n        ], \$this->messages());\n\n        // ⚠️ DEC-l",
        'new' => "            'price' => ['required', 'string'],\n        ]);\n\n        // ⚠️ DEC-l",
        'tests' => [['file' => $look, 'filter' => 'shows every refusal a season save or edit can return']],
    ],
    [
        'id' => 'RP-M26',
        'name' => 'an edit’s refusal is filed under the save form instead of shown at the top',
        'file' => $seasons,
        'old' => "            return \$this->toPage(\$request)->with('rates_error', (string) \$validator->errors()->first());",
        'new' => '            return $this->toPage($request)->withErrors($validator);',
        'tests' => [['file' => $look, 'filter' => 'shows every refusal a season save or edit can return']],
    ],
    [
        'id' => 'RP-M27',
        'name' => 'the refusal to edit an applied season is set but never shown',
        'file' => $view,
        'old' => "        @error('season')",
        'new' => "        @error('season-never')",
        'tests' => [['file' => $look, 'filter' => 'shows every refusal a season save or edit can return']],
    ],
    [
        'id' => 'RP-M28',
        'name' => 'the season form’s refusals are never shown',
        'file' => $view,
        'old' => "                            @if (\$errors->hasAny(['rate_plan_id', 'name', 'starts_on', 'ends_on', 'price']))",
        'new' => '                            @if (false)',
        'tests' => [['file' => $look, 'filter' => 'shows every refusal a season save or edit can return']],
    ],
    [
        'id' => 'RP-M29',
        'name' => 'the season dialogs read state no enclosing scope declares (F-20)',
        'file' => $view,
        'old' => '                <div class="vyr-scope" x-data="{ confirmSeason: null, editSeason: null, deleteSeason: null }">',
        'new' => '                <div class="vyr-scope" x-data="{ editSeason: null, deleteSeason: null }">',
        'tests' => [['file' => $seasonTest, 'filter' => 'every season dialog reads a variable']],
    ],

    // ── rate plans ───────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'RP-M30',
        'name' => 'Refundable switched off still makes a refundable plan',
        'file' => $inventory,
        'old' => "            'is_refundable' => \$request->boolean('is_refundable'),",
        'new' => "            'is_refundable' => \$request->boolean('is_refundable', true),",
        'tests' => [['file' => $fix, 'filter' => 'creates a NON-refundable plan']],
    ],
    [
        'id' => 'RP-M31',
        'name' => 'creating a plan leaves no trail',
        'file' => $inventory,
        'old' => "        \$audit->record('rate_plan.created', \$ratePlan, null, [",
        'new' => "        \$audit->record('rate_plan.noted', \$ratePlan, null, [",
        'tests' => [['file' => $fix, 'filter' => 'creates a NON-refundable plan']],
    ],
    [
        'id' => 'RP-M32',
        'name' => 'a paused workspace says "adding inventory" on the rate-plan writes',
        'file' => $inventory,
        'old' => "        \$what = \$surface === 'rates' ? 'rate changes are' : 'adding inventory is';",
        'new' => "        \$what = 'adding inventory is';",
        'tests' => [['file' => $fix, 'filter' => 'says a paused workspace pauses RATE changes']],
    ],

    // ── the grid and its figures ─────────────────────────────────────────────────────────────────
    [
        'id' => 'RP-M33',
        'name' => 'the grid shades Saturday and Sunday while every price rule means Friday and Saturday',
        'file' => $grid,
        'old' => "            'is_weekend' => in_array(\$carbon->dayOfWeekIso, [5, 6], true),",
        'new' => "            'is_weekend' => in_array(\$carbon->dayOfWeekIso, [6, 7], true),",
        'tests' => [['file' => $look, 'filter' => 'shows all fourteen nights']],
    ],
    [
        'id' => 'RP-M34',
        'name' => 'today is not marked',
        'file' => $grid,
        'old' => "            'is_today' => \$carbon->isToday(),",
        'new' => "            'is_today' => false,",
        'tests' => [['file' => $look, 'filter' => 'shows all fourteen nights']],
    ],
    [
        'id' => 'RP-M35',
        'name' => '"Next 14 days" counts the window on screen instead of from today',
        'file' => $grid,
        'old' => '        $today = CarbonImmutable::today();',
        'new' => '        $today = $this->resolveFrom();',
        'tests' => [['file' => $look, 'filter' => 'counts "Next 14 days" from today']],
    ],
    [
        'id' => 'RP-M36',
        'name' => 'the last unit left is drawn as an ordinary open night',
        'file' => $grid,
        'old' => "                'last_one' => \$avail !== null && \$avail['available'] === 1 && \$avail['total'] > 1,",
        'new' => "                'last_one' => false,",
        'tests' => [['file' => $look, 'filter' => 'draws open, last-one, full and closed nights']],
    ],
    [
        'id' => 'RP-M37',
        'name' => 'the legend’s Closed swatch is not the closed cell’s style',
        'file' => $view,
        'old' => '                            <span><i class="vyr-sw is-closed" aria-hidden="true"></i>Closed</span>',
        'new' => '                            <span><i class="vyr-sw" aria-hidden="true"></i>Closed</span>',
        'tests' => [['file' => $look, 'filter' => 'draws open, last-one, full and closed nights']],
    ],
    [
        'id' => 'RP-M38',
        'name' => 'a maximum stay is enforced at booking but not written on its night',
        'file' => $cell,
        'old' => "            <span class=\"vyr-flag vyr-flag--min\" title=\"Maximum stay: {{ \$cell['max_stay'] }} nights\">Max {{ \$cell['max_stay'] }}</span>",
        'new' => '',
        'tests' => [['file' => $look, 'filter' => 'draws open, last-one, full and closed nights']],
    ],
    [
        'id' => 'RP-M39',
        'name' => 'a long price is not stepped down and runs into the next night',
        'file' => $grid,
        'old' => "            \$length >= 8 => 'is-long',",
        'new' => "            \$length >= 80 => 'is-long',",
        'tests' => [['file' => $look, 'filter' => 'steps a long price down a size']],
    ],
    [
        'id' => 'RP-M40',
        'name' => 'the cell drops the size its price was given',
        'file' => $cell,
        'old' => "<span class=\"vyr-rate {{ \$cell['rate_fit'] ?? '' }} @if",
        'new' => '<span class="vyr-rate @if',
        'tests' => [['file' => $look, 'filter' => 'steps a long price down a size']],
    ],

    // ── the window and the forms ─────────────────────────────────────────────────────────────────
    [
        'id' => 'RP-M41',
        'name' => 'the window’s links drop the room-type filter',
        'file' => $grid,
        'old' => "        \$keep = \$rtFilter !== '' ? ['rt' => \$rtFilter] : [];",
        'new' => '        $keep = [];',
        'tests' => [['file' => $fix, 'filter' => 'keeps the room-type filter on the window']],
    ],
    [
        'id' => 'RP-M42',
        'name' => '"go to the first priced night" searches every plan in the organisation',
        'file' => $grid,
        'old' => "            ->whereIn('rate_plan_id', \$planIds)\n            ->whereDate('date', '>=', \$fromStr)",
        'new' => "            ->whereDate('date', '>=', \$fromStr)",
        'tests' => [['file' => $fix, 'filter' => 'offers to jump only to prices on screen']],
    ],
    [
        'id' => 'RP-M43',
        'name' => 'the dialogs open on one night instead of the whole window',
        'file' => $grid,
        'old' => "                'to' => \$throughStr,",
        'new' => "                'to' => \$fromStr,",
        'tests' => [['file' => $look, 'filter' => 'opens it and bulk edit on the whole window']],
    ],
    [
        'id' => 'RP-M44',
        'name' => 'bulk edit forgets where it was started, and lands on today’s fortnight',
        'file' => $view,
        'old' => "                        <form method=\"POST\" action=\"{{ route('app.rates.bulk') }}\" x-on:submit=\"stamp(\$event.target)\">\n                            @csrf\n                            <input type=\"hidden\" name=\"return\" value=\"list\">\n",
        'new' => "                        <form method=\"POST\" action=\"{{ route('app.rates.bulk') }}\" x-on:submit=\"stamp(\$event.target)\">\n                            @csrf\n",
        'tests' => [['file' => $look, 'filter' => 'brings every form on the page back']],
    ],
    [
        'id' => 'RP-M45',
        'name' => 'a paused workspace is offered controls that would all be refused',
        'file' => $view,
        'old' => '            $vyCanWrite = $canManage && ! $readOnly;',
        'new' => '            $vyCanWrite = $canManage;',
        'tests' => [['file' => $look, 'filter' => 'shows a view-only role the grid']],
    ],
    [
        'id' => 'RP-M46',
        'name' => 'a dialog is centred by an inline display again (x-show erases it: the top-left corner)',
        'file' => $view,
        'old' => '                <div class="vy-modal-backdrop" x-cloak x-show="bulkOpen"',
        'new' => '                <div style="position: fixed; inset: 0; display: grid; place-items: center;" x-cloak x-show="bulkOpen"',
        'tests' => [['file' => $xshow, 'filter' => 'leaves no element on the Guests'], ['file' => $look, 'filter' => 'opens every dialog as a centred panel']],
    ],
    [
        'id' => 'RP-M47',
        'name' => 'a class the page uses loses its rule (the unstyled-label defect)',
        'file' => $css,
        'old' => '        .vyr-plan-priced { margin-top: 3px; font-size: 12px; line-height: 1.45; color: var(--vy-ink); font-variant-numeric: tabular-nums; }',
        'new' => '',
        'tests' => [['file' => $look, 'filter' => 'gives every class the Rates views use a rule']],
    ],

    // ── found in the live look on production (2026-10-01) ─────────────────────────────────────────
    [
        'id' => 'RP-M48',
        'name' => 'the season form is offered with an empty plan picker when no plan is on sale',
        'file' => $view,
        'old' => '                        @if ($vyCanWrite && $vyActivePlans === 0)',
        'new' => '                        @if (false)',
        'tests' => [['file' => $look, 'filter' => 'offers no season form where there is no rate plan']],
    ],
    [
        'id' => 'RP-M49',
        'name' => 'the grid’s scroll box stops containing its positioned labels (they widen the page)',
        'file' => $css,
        'old' => '        .vyr-scroll { position: relative; overflow: auto;',
        'new' => '        .vyr-scroll { overflow: auto;',
        'tests' => [['file' => $look, 'filter' => 'keeps every positioned label inside the scroll box']],
    ],
];
