<?php

declare(strict_types=1);

// Mutation set portfolio-remove — removing a property from its Portfolio card (2026-09-29): the archive-not-destroy
// removal, right away only when nobody is staying and nothing is booked ahead, never the last property, only for a
// manager, only a property the person can access, never read-only, only with the name typed — and the nightly pass never
// archives over a checked-in guest. Run with `php tests/Mutations/run.php --set=portfolio-remove`.

$t = 'tests/Feature/PortfolioRemovePropertyTest.php';
$svc = 'app/Services/PropertyRemovalService.php';
$ctl = 'app/Http/Controllers/Operator/PropertyController.php';
$view = 'resources/views/livewire/operator/portfolio.blade.php';

return [
    [
        'id' => 'PR-M1',
        'name' => 'the LAST property can be removed (the back office is left with none)',
        'file' => $svc,
        'old' => '        if ($this->isLastRemaining($property)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'LAST property']],
    ],
    [
        'id' => 'PR-M2',
        'name' => 'a property already leaving counts as one that remains',
        'file' => $svc,
        'old' => "return ! Property::query()->whereKeyNot(\$property->getKey())->whereNull('removal_scheduled_at')->exists();",
        'new' => 'return ! Property::query()->whereKeyNot($property->getKey())->exists();',
        'tests' => [['file' => $t, 'filter' => 'LAST property']],
    ],
    [
        'id' => 'PR-M3',
        'name' => 'a checked-in guest no longer stops "right away" (removed from under an overstay)',
        'file' => $svc,
        'old' => "'clear' => \$stays === 0 && \$inHouse === 0,",
        'new' => "'clear' => \$stays === 0,",
        'tests' => [['file' => $t, 'filter' => 'guest in the house']],
    ],
    [
        'id' => 'PR-M4',
        'name' => '"right away" ignores the stays ahead (archived over booked guests)',
        'file' => $svc,
        'old' => "        if (\$property->removal_scheduled_at === null || ! \$this->outlook(\$property)['clear']) {",
        'new' => '        if ($property->removal_scheduled_at === null) {',
        'tests' => [['file' => $t, 'filter' => 'stay ahead is SCHEDULED']],
    ],
    [
        'id' => 'PR-M5',
        'name' => 'nothing is ever removed right away (it waits for the nightly run)',
        'file' => $ctl,
        'old' => '        if ($removals->removeIfClear($target, $channels)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'RIGHT AWAY']],
    ],
    [
        'id' => 'PR-M6',
        'name' => 'a property outside the person\'s scope can be removed (the allow-list is skipped)',
        'file' => $ctl,
        'old' => "        abort_unless(in_array(\$id, app(PropertyContext::class)->accessibleIds(), true), 404);\n",
        'new' => '',
        'tests' => [['file' => $t, 'filter' => 'only a property the person can access']],
    ],
    [
        'id' => 'PR-M7',
        'name' => 'the remove route needs no manage permission',
        'file' => 'routes/web.php',
        'old' => "->middleware('can:properties.manage')->whereUlid('property')->name('portfolio.remove');",
        'new' => "->whereUlid('property')->name('portfolio.remove');",
        'tests' => [['file' => $t, 'filter' => 'only a manager may remove']],
    ],
    [
        'id' => 'PR-M8',
        'name' => 'the keep route needs no manage permission',
        'file' => 'routes/web.php',
        'old' => "->middleware('can:properties.manage')->whereUlid('property')->name('portfolio.keep');",
        'new' => "->whereUlid('property')->name('portfolio.keep');",
        'tests' => [['file' => $t, 'filter' => 'only a manager may remove']],
    ],
    [
        'id' => 'PR-M9',
        'name' => 'the typed name is not checked',
        'file' => $ctl,
        'old' => "        if (trim((string) \$request->input('confirm_name')) !== trim(\$name)) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'name is typed exactly']],
    ],
    [
        'id' => 'PR-M10',
        'name' => 'a read-only workspace can still remove',
        'file' => $ctl,
        'old' => "        if (\$refused = \$this->refusedWhileReadOnly()) {\n            return \$refused;\n        }\n\n        \$request->validate(['confirm_name' => ['required', 'string']]);",
        'new' => "        \$request->validate(['confirm_name' => ['required', 'string']]);",
        'tests' => [['file' => $t, 'filter' => 'read-only workspace']],
    ],
    [
        'id' => 'PR-M11',
        'name' => 'the nightly pass archives over a checked-in guest past their date again',
        'file' => $svc,
        'old' => '        if ($this->inHouse($property) > 0 && $effective->lte($today)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $t, 'filter' => 'NIGHTLY pass']],
    ],
    [
        'id' => 'PR-M12',
        'name' => 'the Remove control is offered to someone who cannot manage properties',
        'file' => $view,
        'old' => "                                @if (\$canManage)\n                                    <button type=\"button\" class=\"vy-btn vy-btn--ghost\"\n                                        @click=\"editing",
        'new' => "                                @if (true)\n                                    <button type=\"button\" class=\"vy-btn vy-btn--ghost\"\n                                        @click=\"editing",
        'tests' => [['file' => $t, 'filter' => 'only a manager may remove']],
    ],
    [
        'id' => 'PR-M13',
        'name' => 'a leaving property\'s card no longer says it is leaving, or when',
        'file' => $view,
        'old' => "                            @if (\$p['removal']['scheduled'])\n                                <div class=\"vy-prop-leaving\" role=\"status\">",
        'new' => "                            @if (false)\n                                <div class=\"vy-prop-leaving\" role=\"status\">",
        'tests' => [['file' => $t, 'filter' => 'stay ahead is SCHEDULED']],
    ],
    [
        'id' => 'PR-M14',
        'name' => 'a directive glued to a word ships raw Blade source to the page (`run@if`)',
        'file' => $view,
        'old' => "<span class=\"vy-rm-what\">{{ \$p['units'] > 0 ? __('livewire/operator/portfolio.then_removed_with_owners') : __('livewire/operator/portfolio.then_removed') }}</span>",
        'new' => "<span class=\"vy-rm-what\">Then it’s removed at the nightly run@if (\$p['units'] > 0), and owners of its units are told@endif. You can keep it any time before.</span>",
        'tests' => [['file' => $t, 'filter' => 'stay ahead is SCHEDULED']],
    ],
];
