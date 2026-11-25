<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

// ⚠️⚠️ VY-10 / VY-12 (2026-11-25) — THE ONE WAY A SETTING IS WRITTEN INTO THE SERVER'S .env: `ops/host/env-set.php`.
//
// His steps (the mailbox password, the backup passphrase, the heartbeat address) end with a value in the server's shared .env. A
// hand edit can break a line nobody reads back, lose the old file, or leave a secret in a shell history. The tool takes a secret on
// STANDARD INPUT only, prints its NAME only, keeps the old file, writes by rename, and proves the write by parsing the whole new file
// with phpdotenv (Laravel's own parser): the key reads back exactly, every other key reads back unchanged — or nothing is written.
//
// These tests run the script as a separate process, exactly as the host does.

function vyEnvSandbox(string $env = ''): array
{
    // the system temp folder, NOT storage/: the tool rightly refuses a passphrase file under any `/storage/`
    $root = rtrim(sys_get_temp_dir(), '/').'/vy-envset-'.bin2hex(random_bytes(5));
    File::ensureDirectoryExists($root.'/app');
    $envPath = $root.'/app/.env';
    File::put($envPath, $env !== '' ? $env : implode("\n", [
        'APP_NAME=Vyrobel',
        'APP_KEY=base64:dGVzdC1rZXktbm90LWEtcmVhbC1vbmUtMTIzNDU2Nzg=',
        'MAIL_MAILER=log',
        'MAIL_HOST=smtp.example.test',
        'MAIL_USERNAME=contact@example.test',
        'MAIL_FROM_ADDRESS="contact@example.test"',
        '# a comment line stays',
        'DB_PASSWORD="quoted value with spaces"',
        '',
    ]));
    chmod($envPath, 0600);

    return ['root' => $root, 'env' => $envPath, 'backups' => $root.'/env-backups'];
}

/** @param list<string> $args */
function vyEnvSet(array $s, array $args, ?string $stdin = null): Process
{
    $process = new Process(
        [PHP_BINARY, base_path('ops/host/env-set.php'), '--env='.$s['env'], '--backups='.$s['backups'], '--autoload='.base_path('vendor/autoload.php'), ...$args],
        null, null, $stdin, 30,
    );
    $process->run();

    return $process;
}

afterEach(function (): void {
    foreach (glob(rtrim(sys_get_temp_dir(), '/').'/vy-envset-*') ?: [] as $dir) {
        File::deleteDirectory($dir);
    }
});

it('⚠️⚠️ writes a secret from standard input, never prints it, and leaves every other setting exactly as it was', function () {
    $s = vyEnvSandbox();
    $before = Dotenv\Dotenv::parse((string) file_get_contents($s['env']));
    $secret = 'N3w-Mailbox-P@ss with spaces #and$dollar\'quote"';

    $p = vyEnvSet($s, ['secret', 'MAIL_PASSWORD'], $secret."\n");

    expect($p->getExitCode())->toBe(0, $p->getErrorOutput());
    expect($p->getOutput().$p->getErrorOutput())->not->toContain('N3w-Mailbox')
        ->and($p->getOutput())->toContain('MAIL_PASSWORD: set — read back');

    $after = Dotenv\Dotenv::parse((string) file_get_contents($s['env']));
    expect($after['MAIL_PASSWORD'])->toBe($secret);
    foreach ($before as $name => $value) {
        expect($after[$name])->toBe($value);
    }
    expect(sprintf('%o', fileperms($s['env']) & 0777))->toBe('600');
    expect(file_get_contents($s['env']))->toContain('# a comment line stays');
});

it('⚠️ keeps the previous .env, mode 600, before replacing it', function () {
    $s = vyEnvSandbox();
    $original = (string) file_get_contents($s['env']);

    vyEnvSet($s, ['secret', 'MAIL_PASSWORD'], "first-value\n");

    $saved = glob($s['backups'].'/.env.*.before-MAIL_PASSWORD') ?: [];
    expect($saved)->toHaveCount(1);
    expect(file_get_contents($saved[0]))->toBe($original);
    expect(sprintf('%o', fileperms($saved[0]) & 0777))->toBe('600');
});

it('replaces an existing line in place, and a duplicate line of the same key is dropped — one definition', function () {
    $s = vyEnvSandbox("A=1\nMAIL_MAILER=log\nB=2\nMAIL_MAILER=smtp\nC=3\n");

    $p = vyEnvSet($s, ['set', 'MAIL_MAILER', 'smtp']);

    expect($p->getExitCode())->toBe(0, $p->getErrorOutput());
    expect(file_get_contents($s['env']))->toBe("A=1\nMAIL_MAILER=smtp\nB=2\nC=3\n");
    expect($p->getOutput())->toContain('MAIL_MAILER: smtp — read back');
});

it('⚠️ refuses a secret key from an argument, a plain key from standard input, and any key not on its list', function () {
    $s = vyEnvSandbox();
    $original = (string) file_get_contents($s['env']);

    expect(vyEnvSet($s, ['set', 'MAIL_PASSWORD', 'typed-on-the-command-line'])->getExitCode())->toBe(2);
    expect(vyEnvSet($s, ['secret', 'MAIL_MAILER'], "smtp\n")->getExitCode())->toBe(2);
    expect(vyEnvSet($s, ['secret', 'APP_KEY'], "base64:x\n")->getExitCode())->toBe(2);
    expect(vyEnvSet($s, ['set', 'DB_PASSWORD', 'x'])->getExitCode())->toBe(2);
    expect(vyEnvSet($s, ['set', 'MAIL_MAILER', 'postmark'])->getExitCode())->toBe(2);

    expect(file_get_contents($s['env']))->toBe($original);
    expect(glob($s['backups'].'/.env.*') ?: [])->toBe([]);
});

it('refuses an empty value, or one with a line break in it, and writes nothing', function () {
    $s = vyEnvSandbox();
    $original = (string) file_get_contents($s['env']);

    expect(vyEnvSet($s, ['secret', 'MAIL_PASSWORD'], "\n")->getExitCode())->toBe(1);
    expect(vyEnvSet($s, ['secret', 'MAIL_PASSWORD'], "line-one\nline-two\n")->getExitCode())->toBe(1);

    expect(file_get_contents($s['env']))->toBe($original);
});

it('takes a heartbeat address only from a known ping service, over https', function () {
    $s = vyEnvSandbox();

    expect(vyEnvSet($s, ['secret', 'OPS_HEARTBEAT_PING_URL'], "http://hc-ping.com/abc\n")->getExitCode())->toBe(1);
    expect(vyEnvSet($s, ['secret', 'OPS_HEARTBEAT_PING_URL'], "https://evil.example/hc-ping.com\n")->getExitCode())->toBe(1);

    $ok = vyEnvSet($s, ['secret', 'OPS_HEARTBEAT_PING_URL'], "https://hc-ping.com/0f1e2d3c-test\n");
    expect($ok->getExitCode())->toBe(0, $ok->getErrorOutput())
        ->and($ok->getOutput())->not->toContain('0f1e2d3c');
    expect(Dotenv\Dotenv::parse((string) file_get_contents($s['env']))['OPS_HEARTBEAT_PING_URL'])->toBe('https://hc-ping.com/0f1e2d3c-test');
});

it('⚠️⚠️ writes the backup passphrase into a file OUTSIDE the application (600, its folder 700) and sets only the PATH in .env', function () {
    $s = vyEnvSandbox();
    $path = $s['root'].'/secrets/backup-passphrase';

    $p = vyEnvSet($s, ['secret-file', 'OPS_BACKUP_PASSPHRASE_FILE', $path], "a long passphrase typed by him\n");

    expect($p->getExitCode())->toBe(0, $p->getErrorOutput());
    expect($p->getOutput())->not->toContain('typed by him');
    expect(trim((string) file_get_contents($path)))->toBe('a long passphrase typed by him');
    expect(sprintf('%o', fileperms($path) & 0777))->toBe('600');
    expect(sprintf('%o', fileperms(dirname($path)) & 0777))->toBe('700');
    $env = (string) file_get_contents($s['env']);
    expect($env)->not->toContain('typed by him');
    expect(Dotenv\Dotenv::parse($env)['OPS_BACKUP_PASSPHRASE_FILE'])->toBe($path);
});

it('⚠️ refuses a passphrase file inside the application — under a release, the web root, storage or vendor', function () {
    $s = vyEnvSandbox();

    foreach (['/x/public_html/pass', '/x/releases/rel_A/pass', '/x/storage/pass', '/x/vendor/pass', 'relative/pass'] as $bad) {
        expect(vyEnvSet($s, ['secret-file', 'OPS_BACKUP_PASSPHRASE_FILE', $bad], "pass\n")->getExitCode())->toBe(2);
    }
    expect(Dotenv\Dotenv::parse((string) file_get_contents($s['env'])))->not->toHaveKey('OPS_BACKUP_PASSPHRASE_FILE');
});

it('refuses to touch a .env that does not parse', function () {
    $s = vyEnvSandbox("GOOD=1\nBROKEN=\"unterminated\n");
    $original = (string) file_get_contents($s['env']);

    expect(vyEnvSet($s, ['set', 'MAIL_MAILER', 'smtp'])->getExitCode())->toBe(1);
    expect(file_get_contents($s['env']))->toBe($original);
});
