<?php

declare(strict_types=1);

use App\Support\Ops\BackupStatus;
use App\Support\Platform\Health\HealthBoard;
use App\Support\Platform\Health\HealthCard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// ⚠️⚠️ VY-12 (audit of 2026-11-24; his ruling the same day: "schedule now, copy later") — THE ENCRYPTED BACKUP RUNS EVERY DAY, AND THE
// CONSOLE SAYS WHEN IT DID NOT.
//
// `ops/backup.sh` made an encrypted archive only when somebody ran it by hand. `backup:run` puts it on the schedule: it reads the
// passphrase from a file OUTSIDE the application (a path in config — the secret itself is never in `.env`, a command line or a log),
// runs the SAME script the restore drill proves, and — when an off-server disk is named — copies the archive there.
// The health board reads the newest archive from the folder itself, because a job cannot report that it did not run.
//
// ⚠️ These tests drive the COMMAND with a small stand-in script (it writes an archive from the environment it is handed), so they prove
//    the orchestration: what is passed, what is refused, what a failure does, what is copied. The real script and its restore drill
//    are proven by running them (the pass's record), not here — a suite that dumped the whole test database on every run would not be
//    run.

function vy12Sandbox(): array
{
    $root = storage_path('framework/testing/vy12-'.bin2hex(random_bytes(5)));
    File::ensureDirectoryExists($root.'/backups');

    // the stand-in for ops/backup.sh: refuses without the passphrase (as the real one does), else writes one archive and prints its path last
    $script = $root.'/backup-stub.sh';
    File::put($script, <<<'SH'
        #!/usr/bin/env bash
        set -euo pipefail
        if [ -z "${BACKUP_PASSPHRASE:-}" ]; then echo "REFUSING: BACKUP_PASSPHRASE is not set." >&2; exit 2; fi
        if [ -f "$(dirname "$0")/FAIL" ]; then echo "mysqldump: connection refused" >&2; exit 1; fi
        ARCHIVE="${BACKUP_DEST}/vyrobel-$(date -u +%Y%m%dT%H%M%SZ)-$$.tar.gz.enc"
        printf 'len=%s retain=%s' "${#BACKUP_PASSPHRASE}" "${BACKUP_RETAIN_DAYS}" > "${ARCHIVE}"
        echo "✓ done"
        echo "${ARCHIVE}"
        SH);
    chmod($script, 0755);

    $pass = $root.'/backup.pass';
    File::put($pass, "a-test-passphrase-of-33-characters\n");
    chmod($pass, 0600);

    config([
        'ops.backup.script' => $script,
        'ops.backup.dest' => $root.'/backups',
        'ops.backup.passphrase_file' => $pass,
        'ops.backup.retain_days' => 14,
        'ops.backup.offsite_disk' => null,
    ]);

    return ['root' => $root, 'dest' => $root.'/backups', 'pass' => $pass];
}

afterEach(function (): void {
    foreach (File::glob(storage_path('framework/testing/vy12-*')) as $dir) {
        File::deleteDirectory($dir);
    }
});

/**
 * The suite's clock is fixed (tests/Pest.php) but a file's own time is the machine's: give the archives the SUITE'S "now", as a real
 * night would, so "27 hours later" is measured on one clock.
 */
function vy12StampNow(array $box): void
{
    foreach (File::glob($box['dest'].'/vyrobel-*.tar.gz.enc') as $file) {
        touch($file, now()->getTimestamp());
    }
}

function vy12Card(): HealthCard
{
    return collect(app(HealthBoard::class)->cards())->firstOrFail(fn (HealthCard $c): bool => $c->key === 'backup');
}

it('⚠️⚠️ VY-12: backup:run makes an archive with the passphrase read from the FILE — and never prints or logs the passphrase', function () {
    $box = vy12Sandbox();
    Log::spy();

    $this->artisan('backup:run')
        ->doesntExpectOutputToContain('a-test-passphrase-of-33-characters')
        ->assertExitCode(0);

    $archives = File::glob($box['dest'].'/vyrobel-*.tar.gz.enc');
    expect($archives)->toHaveCount(1)
        // the script received the file's content (trimmed: 34 characters, no newline) and the retention setting
        ->and(File::get($archives[0]))->toBe('len=34 retain=14');

    Log::shouldNotHaveReceived('error');
    expect(BackupStatus::report()['healthy'])->toBeTrue()
        ->and(BackupStatus::report()['last_name'])->toBe(basename($archives[0]));
});

it('⚠️⚠️ VY-12: with no passphrase file the command REFUSES, writes nothing and names the setting — never a clear-text archive', function (?string $file) {
    $box = vy12Sandbox();
    if ($file === 'empty') {
        File::put($box['pass'], "  \n");
    }
    config(['ops.backup.passphrase_file' => $file === 'empty' ? $box['pass'] : $file]);

    $this->artisan('backup:run')->expectsOutputToContain('OPS_BACKUP_PASSPHRASE_FILE')->assertExitCode(2);

    expect(File::glob($box['dest'].'/*'))->toBe([])->and(BackupStatus::configured())->toBeFalse();
})->with([null, '/nonexistent/vyrobel-backup.pass', 'empty']);

it('⚠️ VY-12: a failing backup exits non-zero and writes ONE error line — the scheduler\'s log is where a failed night is read', function () {
    $box = vy12Sandbox();
    File::put($box['root'].'/FAIL', '1');   // the stand-in now fails the way a dump that cannot connect does
    Log::spy();

    $this->artisan('backup:run')->assertExitCode(1);

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message): bool => str_contains($message, 'backup:run')
        && str_contains($message, 'connection refused') && ! str_contains($message, 'a-test-passphrase'));
    expect(File::glob($box['dest'].'/*'))->toBe([]);
});

it('⚠️ VY-12: when an off-server disk is named, the archive is copied there and old copies are pruned; a failed copy is a failed run', function () {
    $box = vy12Sandbox();
    Storage::fake('offsite-test');
    config(['ops.backup.offsite_disk' => 'offsite-test']);
    Storage::disk('offsite-test')->put('vyrobel-backups/vyrobel-20260101T000000Z.tar.gz.enc', 'old');   // far older than 14 days

    $this->artisan('backup:run')->assertExitCode(0);

    $local = File::glob($box['dest'].'/vyrobel-*.tar.gz.enc');
    $remote = Storage::disk('offsite-test')->files('vyrobel-backups');
    expect($remote)->toBe(['vyrobel-backups/'.basename($local[0])])                       // copied; the old one pruned
        ->and(Storage::disk('offsite-test')->get($remote[0]))->toBe(File::get($local[0]))
        ->and(BackupStatus::report()['offsite_last_at'])->not->toBeNull();

    // a disk that does not exist: the local archive is still made, the run FAILS loudly
    config(['ops.backup.offsite_disk' => 'no-such-disk']);
    Log::spy();
    $this->artisan('backup:run')->assertExitCode(1);
    Log::shouldHaveReceived('error')->once();
    expect(File::glob($box['dest'].'/vyrobel-*.tar.gz.enc'))->toHaveCount(2);
});

it('⚠️⚠️ VY-12 ALARM: the health board reads the folder itself — red when not set up or stale, amber on this server only, green with a copy', function () {
    $box = vy12Sandbox();

    // not set up
    config(['ops.backup.passphrase_file' => null]);
    expect(vy12Card()->status)->toBe(HealthCard::RED)
        ->and(vy12Card()->headline)->toBe(__('php/support/platform/health/health_board.backup_not_set_up'));

    // set up, nothing made yet
    config(['ops.backup.passphrase_file' => $box['pass']]);
    expect(vy12Card()->status)->toBe(HealthCard::RED)
        ->and(vy12Card()->headline)->toBe(__('php/support/platform/health/health_board.backup_stale'));

    // a fresh archive, on this server only
    $this->artisan('backup:run')->assertExitCode(0);
    vy12StampNow($box);
    expect(vy12Card()->status)->toBe(HealthCard::AMBER)
        ->and(vy12Card()->headline)->toBe(__('php/support/platform/health/health_board.backup_same_server'));

    // …and with an off-server copy
    Storage::fake('offsite-test');
    config(['ops.backup.offsite_disk' => 'offsite-test']);
    $this->artisan('backup:run')->assertExitCode(0);
    vy12StampNow($box);
    expect(vy12Card()->status)->toBe(HealthCard::GREEN);

    // 27 hours later nothing new has been made: red again, whatever was true yesterday
    $this->travel(27)->hours();
    expect(vy12Card()->status)->toBe(HealthCard::RED)
        ->and(vy12Card()->headline)->toBe(__('php/support/platform/health/health_board.backup_stale'));
});

it('VY-12: the job is on the schedule, daily — and only while a passphrase file is set up (it never fails every night on an unset host)', function () {
    $box = vy12Sandbox();
    $event = collect(app(Schedule::class)->events())->first(fn ($e): bool => is_string($e->command ?? null) && str_contains($e->command, 'backup:run'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('30 2 * * *')
        ->and($event->filtersPass(app()))->toBeTrue();

    config(['ops.backup.passphrase_file' => null]);
    expect($event->filtersPass(app()))->toBeFalse();
});

it('VY-12: the real script is the default, and it is the one the restore drill reads from', function () {
    expect((string) config('ops.backup.script'))->toBe(base_path('ops/backup.sh'))
        ->and(is_file(base_path('ops/backup.sh')))->toBeTrue()
        ->and(is_file(base_path('ops/restore-drill.sh')))->toBeTrue();
});
