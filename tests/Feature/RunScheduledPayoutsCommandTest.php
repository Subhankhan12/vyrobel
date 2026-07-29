<?php

use App\Models\PayoutRun;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The `payouts:run` command (payout engine · Phase 2) — the config gate + TEST-MODE run.

it('is inert when the engine is disabled (no run created)', function () {
    config(['services.payouts.engine_enabled' => false]);

    $this->artisan('payouts:run', ['--date' => '2026-09-03'])
        ->expectsOutputToContain('disabled')
        ->assertExitCode(0);

    expect(PayoutRun::query()->count())->toBe(0);
});

it('runs (a TEST-MODE run record) when forced, even while disabled', function () {
    config(['services.payouts.engine_enabled' => false]);

    $this->artisan('payouts:run', ['--force' => true, '--date' => '2026-09-03'])->assertExitCode(0);

    $run = PayoutRun::query()->firstOrFail();
    expect($run->run_key)->toBe('scheduled:2026-09-03')
        ->and($run->mode)->toBe('test')            // ⚠️ TEST MODE — no real money
        ->and($run->status)->toBe(PayoutRun::STATUS_COMPLETED);
});

it('runs when the engine is enabled', function () {
    config(['services.payouts.engine_enabled' => true]);

    $this->artisan('payouts:run', ['--date' => '2026-09-03'])->assertExitCode(0);

    expect(PayoutRun::query()->where('run_key', 'scheduled:2026-09-03')->exists())->toBeTrue();
});
