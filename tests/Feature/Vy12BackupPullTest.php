<?php

declare(strict_types=1);

use App\Support\Ops\BackupStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

// ⚠️⚠️ VY-12 (2026-11-25) — THE OFF-SERVER COPY, PULLED: `ops/backup-pull.sh`.
//
// His Mac fetches the newest encrypted archive over SSH. It counts as an off-server copy ONLY once three checksums agree — the one
// written beside the archive at backup time, the server's fresh one, and the one of what arrived — and only then is the server's
// marker written (the console's Backups card reads it). These tests run the real script with its server replaced by a local folder
// (VY_PULL_FAKE_SERVER: nothing leaves this machine); the folder has production's layout — the shared storage reached through
// public_html/current, a release, and the .env.

function vyPullSandbox(): array
{
    $root = rtrim(sys_get_temp_dir(), '/').'/vy-pull-'.bin2hex(random_bytes(5));
    $home = $root.'/server-home';
    $site = $home.'/domains/vyrobel.com';
    File::ensureDirectoryExists($site.'/public_html/storage/backups');
    File::ensureDirectoryExists($site.'/releases/rel_T');
    symlink('../releases/rel_T', $site.'/public_html/current');
    symlink('../../public_html/storage', $site.'/releases/rel_T/storage');
    File::put($site.'/public_html/.env', "APP_ENV=production\n");

    return ['root' => $root, 'backups' => $site.'/public_html/storage/backups', 'copies' => $root.'/copies', 'home' => $home];
}

function vyPullArchive(array $s, string $stamp, string $bytes, ?string $writtenSum = null): string
{
    $name = 'vyrobel-'.$stamp.'.tar.gz.enc';
    File::put($s['backups'].'/'.$name, $bytes);
    File::put($s['backups'].'/'.$name.'.sha256', ($writtenSum ?? hash('sha256', $bytes)).'  '.$name."\n");
    touch($s['backups'].'/'.$name, strtotime(substr($stamp, 0, 8).' '.substr($stamp, 9, 2).':'.substr($stamp, 11, 2)));

    return $name;
}

function vyPull(array $s, array $env = []): Process
{
    $process = new Process(['bash', base_path('ops/backup-pull.sh')], base_path(), [
        'VY_PULL_FAKE_SERVER' => $s['home'], 'VY_BACKUP_COPY_DIR' => $s['copies'], ...$env,
    ], null, 60);
    $process->run();

    return $process;
}

afterEach(function (): void {
    foreach (glob(rtrim(sys_get_temp_dir(), '/').'/vy-pull-*') ?: [] as $dir) {
        File::deleteDirectory($dir);
    }
});

it('⚠️ copies the newest archive, proves it by checksum, and only then writes the server\'s off-server marker', function () {
    $s = vyPullSandbox();
    vyPullArchive($s, '20261124T023000Z', 'older archive bytes');
    $name = vyPullArchive($s, '20261125T023000Z', 'newest archive bytes');

    $p = vyPull($s);

    expect($p->getExitCode())->toBe(0, $p->getOutput().$p->getErrorOutput());
    expect(file_get_contents($s['copies'].'/'.$name))->toBe('newest archive bytes');
    expect(file_exists($s['copies'].'/vyrobel-20261124T023000Z.tar.gz.enc'))->toBeFalse();
    expect(sprintf('%o', fileperms($s['copies'].'/'.$name) & 0777))->toBe('600');
    expect(file_get_contents($s['copies'].'/'.$name.'.sha256'))->toContain(hash('sha256', 'newest archive bytes'));
    $marker = trim((string) file_get_contents($s['backups'].'/.offsite-last'));
    expect(abs(CarbonImmutable::parse($marker)->getTimestamp() - time()))->toBeLessThan(120);
    expect($p->getOutput())->toContain('proven: the same sha256 on both sides');
});

it('⚠️⚠️ refuses an archive whose checksum at backup time is not its checksum now — no copy, no marker', function () {
    $s = vyPullSandbox();
    vyPullArchive($s, '20261125T023000Z', 'bytes that changed after the backup', hash('sha256', 'the bytes the backup wrote'));

    $p = vyPull($s);

    expect($p->getExitCode())->toBe(1);
    expect($p->getErrorOutput())->toContain('not copied');
    expect(file_exists($s['backups'].'/.offsite-last'))->toBeFalse();
    expect(glob($s['copies'].'/vyrobel-*') ?: [])->toBe([]);
});

it('says the backup has not run when the server has no backup folder or no archive — and marks nothing', function () {
    $s = vyPullSandbox();
    expect(vyPull($s)->getErrorOutput())->toContain('no archive');

    File::deleteDirectory($s['backups']);
    $p = vyPull($s);
    expect($p->getExitCode())->toBe(1)->and($p->getErrorOutput())->toContain('no backup folder');
    expect(file_exists($s['backups'].'/.offsite-last'))->toBeFalse();
});

it('a second pull of the same archive copies nothing again, and the copies kept are capped', function () {
    $s = vyPullSandbox();
    vyPullArchive($s, '20261124T023000Z', 'monday');
    expect(vyPull($s, ['VY_BACKUP_COPY_KEEP' => '1'])->getExitCode())->toBe(0);
    expect(vyPull($s, ['VY_BACKUP_COPY_KEEP' => '1'])->getOutput())->toContain('already copied and proven');

    vyPullArchive($s, '20261125T023000Z', 'tuesday');
    expect(vyPull($s, ['VY_BACKUP_COPY_KEEP' => '1'])->getExitCode())->toBe(0);

    expect(array_map('basename', glob($s['copies'].'/vyrobel-*.tar.gz.enc') ?: []))->toBe(['vyrobel-20261125T023000Z.tar.gz.enc']);
});

it('⚠️ the Backups card counts a PULLED copy as off-server once OPS_BACKUP_OFFSITE_PULL names the machine — by the marker\'s age', function () {
    $s = vyPullSandbox();
    vyPullArchive($s, '20261125T023000Z', 'bytes');
    touch($s['backups'].'/vyrobel-20261125T023000Z.tar.gz.enc');   // a recent backup
    config(['ops.backup.dest' => $s['backups'], 'ops.backup.offsite_disk' => null, 'ops.backup.offsite_pull' => null]);

    expect(BackupStatus::report()['offsite_configured'])->toBeFalse();

    config(['ops.backup.offsite_pull' => 'founder laptop']);
    $none = BackupStatus::report();
    expect($none['offsite_configured'])->toBeTrue()->and($none['offsite_healthy'])->toBeFalse();   // set up, never pulled

    expect(vyPull($s)->getExitCode())->toBe(0);
    $pulled = BackupStatus::report();
    expect($pulled['offsite_healthy'])->toBeTrue()->and($pulled['offsite_age_seconds'])->toBeLessThan(120);

    File::put($s['backups'].'/.offsite-last', CarbonImmutable::now()->subHours(30)->toIso8601String());
    expect(BackupStatus::report()['offsite_healthy'])->toBeFalse();   // a pull that stopped turns it amber again
});

it('the backup\'s facts and the drill compare THE SAME spot-check records and money sums', function () {
    $lists = function (string $file): array {
        $src = (string) file_get_contents(base_path($file));
        preg_match('/^SPOT_PAIRS="([^"]+)"/m', $src, $spot);
        preg_match('/^SUM_COLS="([^"]+)"/m', $src, $sums);

        return [$spot[1] ?? null, $sums[1] ?? null];
    };

    [$spotB, $sumB] = $lists('ops/backup.sh');
    [$spotD, $sumD] = $lists('ops/restore-drill.sh');
    expect($spotB)->not->toBeNull()->and($spotB)->toBe($spotD);
    expect($sumB)->not->toBeNull()->and($sumB)->toBe($sumD);
});

it('⚠️⚠️ ops/secrets.sh refuses to run without a terminal, so no session — and no pipe a session builds — can feed it a secret', function () {
    $p = new Process(['bash', base_path('ops/secrets.sh'), 'mail'], base_path(), null, "a-secret-from-a-pipe\n", 30);
    $p->run();

    expect($p->getExitCode())->toBe(1);
    expect($p->getErrorOutput())->toContain('run this yourself, in Terminal');
});

it('⚠️⚠️ ops/secrets.sh never prints, logs or writes the secret: it only reads it, compares it, sends it on standard input and clears it', function () {
    $lines = preg_split('/\R/', (string) file_get_contents(base_path('ops/secrets.sh'))) ?: [];
    // code only: whole-line comments and trailing ` # …` comments are not uses
    $code = array_map(fn (string $l): string => (string) preg_replace('/\s#\s.*$/', '', $l), $lines);
    $uses = array_values(array_filter($code, fn (string $l): bool => ! str_starts_with(ltrim($l), '#') && str_contains($l, 'SECRET')));

    foreach ($uses as $line) {
        // the only shapes allowed: read into it · test/compare it · send it on stdin to env-set · clear it
        $ok = preg_match('/read -r -s -p .*SECRET|\[ -n "\$\{SECRET\}" \]|\$\{#SECRET\}|\[ "\$\{SECRET\}" = "\$\{again\}" \]|printf \'%s\\\\n\' "\$\{SECRET\}" \| ssh_|SECRET=(;|$| )|local again/', $line) === 1;
        expect($ok)->toBeTrue('an unexpected use of SECRET: '.trim($line));
        expect($line)->not->toMatch('/(echo|say|die|step|>>?)\s[^|]*\$\{?SECRET/');
    }
    expect(count($uses))->toBeGreaterThan(5);
});
