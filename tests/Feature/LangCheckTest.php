<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

// ⚠️⚠️ I18N-01 Phase E — the translation gate runs WITH the suite, so the suite and GitHub's checks refuse what lang:check
// refuses. Each instrument proves its own detectors on planted needles (RULES Part 3: a guard never seen to fire is not a guard).

it('⚠️⚠️ passes lang:check — every finished language against English, and every view and class', function () {
    $code = Artisan::call('lang:check');

    expect($code)->toBe(0, Artisan::output());
})->group('i18n');

it('⚠️⚠️ lang:check catches each kind of mistake it guards — a planted needle per check, and a clean value passes', function () {
    $code = Artisan::call('lang:check', ['--self-test' => true]);
    $out = Artisan::output();

    expect($code)->toBe(0, $out)->and($out)->not->toContain('MISSED')->not->toContain('FALSE')
        ->and(substr_count($out, 'caught'))->toBeGreaterThanOrEqual(9);
})->group('i18n');

it('⚠️ lang:todo reports each kind it lists — a planted hardcoded string, a stale hash, a dynamic prefix', function () {
    $code = Artisan::call('lang:todo', ['--self-test' => true]);
    $out = Artisan::output();

    expect($code)->toBe(0, $out)->and($out)->not->toContain('MISSED')->not->toContain('FALSE');
})->group('i18n');
