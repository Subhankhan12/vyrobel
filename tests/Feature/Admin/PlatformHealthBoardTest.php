<?php

declare(strict_types=1);

use App\Models\PlatformAdminAction;
use App\Support\Ops\ConfigInventory;
use App\Support\Ops\Heartbeat;
use App\Support\Platform\Health\HealthBoard;
use App\Support\Platform\Health\HealthCard;
use App\Support\Platform\Health\LogErrorCounter;
use App\Support\Platform\Health\ReleaseReader;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 1 — THE "NOTHING IS SILENT" BOARD. ⚠️ Calibrated on the truths known on the day it shipped: mail
// going to `log` must be RED; no external heartbeat URL must NOT be green; a card that cannot look is GREY, never
// green. A board that is all green on a day that is not all green is broken.

uses(DatabaseTransactions::class);

function card(string $key): HealthCard
{
    return collect(app(HealthBoard::class)->cards())->firstOrFail(fn (HealthCard $c) => $c->key === $key);
}

// ══ 1 · CALIBRATION — today's reds must show red ═════════════════════════════════════════════════════════════

it('⚠️⚠️ mail on `log` (production today) is RED, in words', function () {
    config(['mail.default' => 'log']);
    $c = card('mail');
    expect($c->status)->toBe(HealthCard::RED)->and($c->headline)->toBe('MAIL IS NOT BEING DELIVERED');

    config(['mail.default' => 'array']);   // the test suite's own mailer delivers nothing either
    expect(card('mail')->status)->toBe(HealthCard::RED);
})->group('security');

it('a delivering mailer is green — and says delivery statistics are NOT measured', function () {
    config(['mail.default' => 'smtp']);
    $c = card('mail');
    expect($c->status)->toBe(HealthCard::GREEN)
        ->and(implode(' ', $c->lines))->toContain('not measured');
})->group('security');

it('⚠️⚠️ a scheduler that never beat is RED; beating but unwatched is AMBER; beating AND watched is GREEN', function () {
    Cache::forget(Heartbeat::SCHEDULER);
    config(['ops.heartbeat_ping_url' => null]);
    expect(card('scheduler')->status)->toBe(HealthCard::RED);

    Heartbeat::beat(Heartbeat::SCHEDULER);
    expect(card('scheduler')->status)->toBe(HealthCard::AMBER)
        ->and(card('scheduler')->headline)->toContain('nobody is told');

    config(['ops.heartbeat_ping_url' => 'https://hc.example/ping/abc']);
    expect(card('scheduler')->status)->toBe(HealthCard::GREEN);
})->group('security');

it('⚠️⚠️ the board, as configured for this suite, is NOT all green — mail alone guarantees it', function () {
    $statuses = collect(app(HealthBoard::class)->cards())->pluck('status')->unique()->values()->all();
    expect($statuses)->not->toBe([HealthCard::GREEN]);
})->group('security');

it('⚠️⚠️ a card whose check THROWS is GREY "could not measure" — never green, and the others still stand', function () {
    // Make the queue card's first query fail.
    DB::shouldReceive('table')->andThrow(new RuntimeException('simulated'));

    $c = card('queue');
    expect($c->status)->toBe(HealthCard::GREY)->and($c->headline)->toBe('Could not measure');
})->group('security');

it('the exposure battery card is GREY by design — its results live outside the host', function () {
    expect(card('exposure')->status)->toBe(HealthCard::GREY);
})->group('security');

it('⚠️ the config card shows key NAMES only — never a value', function () {
    config(['services.postmark.token' => null]);
    $c = card('config');
    $secret = (string) config('app.key');

    expect($c->status)->toBe(HealthCard::RED)
        ->and(implode(' ', $c->lines))->toContain('services.postmark.token')
        ->and(implode(' ', $c->lines).' '.$c->headline)->not->toContain($secret);
})->group('security');

// ══ 2 · THE RELEASE CARD READS, NEVER TYPES ═══════════════════════════════════════════════════════════════════

it('reads the live release and its rollback candidates from the layout on disk', function () {
    $root = storage_path('framework/testing/releases-'.Str::random(8));
    $live = "{$root}/releases/rel_M";
    File::ensureDirectoryExists($live);
    File::ensureDirectoryExists("{$root}/releases/rel_L");
    File::ensureDirectoryExists("{$root}/releases/rel_K");
    File::put("{$live}/.release", str_repeat('a', 40)."\n");
    File::put("{$root}/releases/rel_L/.release", str_repeat('b', 40));
    File::put("{$root}/releases/rel_K/.release", 'not-a-hash');

    $r = ReleaseReader::read($live);

    expect($r['in_layout'])->toBeTrue()
        ->and($r['current'])->toBe('rel_M')
        ->and($r['commit'])->toBe(str_repeat('a', 40))
        ->and(collect($r['retained'])->pluck('name')->all())->toBe(['rel_L', 'rel_K'])
        ->and($r['retained'][1]['commit'])->toBeNull();   // ⚠️ a malformed .release is reported unreadable, never guessed

    File::deleteDirectory($root);
})->group('security');

it('a development checkout is GREY "could not measure", not a fake release', function () {
    expect(ReleaseReader::read(base_path())['in_layout'])->toBeFalse()
        ->and(card('release')->status)->toBe(HealthCard::GREY);
})->group('security');

// ══ 3 · SERVER ERRORS — counted from the daily log, inside a 24-hour window ═════════════════════════════════

it('counts ERROR+ lines inside the last 24 hours only, warnings separately', function () {
    $dir = storage_path('framework/testing/logs-'.Str::random(8));
    File::ensureDirectoryExists($dir);
    $now = Carbon::parse('2026-09-24 12:00:00');
    File::put("{$dir}/laravel-2026-09-24.log", implode("\n", [
        '[2026-09-24 11:05:00] production.ERROR: boom {"exception":"x"}',
        '[2026-09-24 11:30:00] production.CRITICAL: bigger boom',
        '[2026-09-24 10:00:00] production.WARNING: a refusal',
        '[2026-09-22 09:00:00] production.ERROR: too old to count',
        '#0 a stack trace line that is not an entry',
    ]));

    $r = LogErrorCounter::lastDay($dir, $now);
    expect($r['errors_24h'])->toBe(2)->and($r['warnings_24h'])->toBe(1)->and($r['by_hour'])->toBe(['2026-09-24 11:00' => 2]);

    File::deleteDirectory($dir);
})->group('security');

it('the errors card says, on its face, that only origin errors are visible — the CDN is not', function () {
    $c = card('errors');
    expect((string) $c->note)->toContain('CDN')->toContain('route group');
})->group('security');

// ══ 4 · ⚠️⚠️ L14 — no raw env() outside config/, and the heartbeat URL is a real config key ══════════════════

it('⚠️⚠️ NO `env()` is called outside config/ (tokenised) — each one would read NULL under a cached config', function () {
    expect(ConfigInventory::strayEnvSites())->toBe([]);
})->group('security');

it('the heartbeat URL is a config key the inventory can see, and its calibration holds', function () {
    $inv = ConfigInventory::inventory();
    expect($inv['known'])->toContain('ops.heartbeat_ping_url');

    foreach (ConfigInventory::calibration($inv) as [$label, $ok]) {
        expect($ok)->toBeTrue("calibration failed: {$label}");
    }
})->group('security');

// ══ 5 · THE PAGE ═════════════════════════════════════════════════════════════════════════════════════════════

it('/admin is the health board — every card rendered, the view audited, nothing that can act', function () {
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('MAIL IS NOT BEING DELIVERED')
        ->assertSee('Could not measure')
        ->assertDontSee('Deploy')
        ->assertDontSee('Rollback now');

    expect(PlatformAdminAction::query()->where('action', 'platform.health.view')->exists())->toBeTrue();
})->group('security');
