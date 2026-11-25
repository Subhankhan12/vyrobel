<?php

declare(strict_types=1);

/*
 * ─────────────────────────────────────────────────────────────────────────────────────────────────
 * ops/host/env-set.php — VY-10 / VY-12 (2026-11-25): THE ONE WAY a setting is written into the server's shared .env.
 *
 * Run ON THE HOST, from the live release, by ops/secrets.sh (his steps) or by a session for a NON-secret setting:
 *
 *   php ops/host/env-set.php --env=<path to .env> --backups=<dir> secret <KEY>            value on STDIN, never printed
 *   php ops/host/env-set.php --env=<path to .env> --backups=<dir> set <KEY> <value>       a non-secret value
 *   php ops/host/env-set.php --env=<path to .env> --backups=<dir> secret-file <KEY> <path>
 *        the STDIN value is written into <path> (outside the application, dir 700, file 600) and <KEY> is set to <path>
 *
 * ⚠️⚠️ A SECRET NEVER TRAVELS AS AN ARGUMENT (arguments show in `ps`) AND IS NEVER PRINTED: it arrives on STDIN, and the output
 *    names the setting only ("MAIL_PASSWORD: set — read back"). Hard rule 1.
 * ⚠️ ONLY THE KEYS LISTED BELOW, each in its own mode — a secret key cannot be `set` from an argument, a plain one cannot be
 *    piped in, and nothing else in the file can be touched by this tool.
 * ⚠️ A WRITE IS PROVEN BY READING IT BACK (RULES Part 2): the new file is parsed with phpdotenv — the parser Laravel itself uses —
 *    and must give exactly the value written for <KEY> and exactly the OLD value for every other key. Otherwise the old file
 *    stays and the tool exits 1. The quoting is chosen the same way: the first form that parses back to the exact value.
 * ⚠️ THE OLD FILE IS KEPT (mode 600) in <backups> before anything is replaced, and the new one goes in by rename (mode 600).
 * ⚠️ IT CHANGES NOTHING THE LIVE SITE READS: production runs on a cached config. The value takes effect only when
 *    `ops/release.sh recache` builds a release from it and `swap` makes that live — and a rollback restores the old config.
 * ─────────────────────────────────────────────────────────────────────────────────────────────────
 */

const SECRET_KEYS = ['MAIL_PASSWORD', 'MAIL_SECOND_PASSWORD', 'OPS_HEARTBEAT_PING_URL'];
const PLAIN_KEYS = ['MAIL_MAILER', 'MAIL_SECOND_USERNAME', 'MAIL_DAILY_LIMIT', 'OPS_BACKUP_OFFSITE_PULL'];
const FILE_KEYS = ['OPS_BACKUP_PASSPHRASE_FILE'];
const MAX_LENGTH = 1024;

function fail(string $message, int $code = 1): never
{
    fwrite(STDERR, '  ✗ '.$message.PHP_EOL);
    exit($code);
}

function say(string $message): void
{
    fwrite(STDOUT, '  '.$message.PHP_EOL);
}

// ── arguments ────────────────────────────────────────────────────────────────────────────────────
$options = [];
$positional = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--(env|backups|autoload)=(.+)$/', $arg, $m) === 1) {
        $options[$m[1]] = $m[2];
    } else {
        $positional[] = $arg;
    }
}
$envPath = $options['env'] ?? fail('usage: --env=<path to .env> is required', 2);
$backupDir = $options['backups'] ?? fail('usage: --backups=<dir> is required', 2);
$autoload = $options['autoload'] ?? dirname(__DIR__, 2).'/vendor/autoload.php';
[$mode, $key] = [$positional[0] ?? '', $positional[1] ?? ''];

if (! is_file($autoload)) {
    fail('no vendor/autoload.php at '.$autoload.' — run this from a release', 2);
}
require $autoload;

if (! class_exists(Dotenv\Dotenv::class)) {
    fail('phpdotenv is not installed — cannot prove the write', 2);
}

// ── the value, by mode ───────────────────────────────────────────────────────────────────────────
$readStdin = static function (): string {
    $raw = stream_get_contents(STDIN);
    $value = is_string($raw) ? rtrim($raw, "\r\n") : '';
    if ($value === '') {
        fail('nothing arrived on standard input — nothing was written');
    }
    if (str_contains($value, "\n") || str_contains($value, "\r") || str_contains($value, "\0")) {
        fail('the value has a line break in it — nothing was written');
    }
    if (strlen($value) > MAX_LENGTH) {
        fail('the value is longer than '.MAX_LENGTH.' characters — nothing was written');
    }

    return $value;
};

$secret = false;
switch ($mode) {
    case 'secret':
        in_array($key, SECRET_KEYS, true) || fail("$key is not a setting this tool takes as a secret (".implode(', ', SECRET_KEYS).')', 2);
        $value = $readStdin();
        $secret = true;
        if ($key === 'OPS_HEARTBEAT_PING_URL') {
            $host = (string) parse_url($value, PHP_URL_HOST);
            $scheme = (string) parse_url($value, PHP_URL_SCHEME);
            $known = ['hc-ping.com', 'uptime.betterstack.com', 'cronitor.link'];
            if ($scheme !== 'https' || ! in_array($host, $known, true)) {
                fail('that is not an https ping address of Healthchecks, Better Stack or Cronitor — nothing was written');
            }
        }
        break;

    case 'set':
        in_array($key, PLAIN_KEYS, true) || fail("$key is not a setting this tool sets from an argument (".implode(', ', PLAIN_KEYS).')', 2);
        $value = (string) ($positional[2] ?? '');
        $value !== '' || fail('no value given', 2);
        $valid = match ($key) {
            'MAIL_MAILER' => in_array($value, ['log', 'smtp', 'rotating'], true),
            'MAIL_DAILY_LIMIT' => preg_match('/^[1-9][0-9]{0,5}$/', $value) === 1,
            'MAIL_SECOND_USERNAME' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'OPS_BACKUP_OFFSITE_PULL' => preg_match('/^[A-Za-z0-9 ._\'-]{1,60}$/', $value) === 1,
            default => false,
        };
        $valid || fail("that is not a value $key accepts — nothing was written", 2);
        break;

    case 'secret-file':
        in_array($key, FILE_KEYS, true) || fail("$key is not a setting this tool writes a secret file for", 2);
        $path = (string) ($positional[2] ?? '');
        str_starts_with($path, '/') || fail('the secret file must be an absolute path', 2);
        // ⚠️ OUTSIDE THE APPLICATION: never under a release or the web root, where a deploy copies files or the web can reach.
        foreach (['/public_html/', '/releases/', '/storage/', '/vendor/'] as $inside) {
            if (str_contains($path.'/', $inside)) {
                fail("the secret file must live outside the application, not under $inside — nothing was written", 2);
            }
        }
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0700, true)) {
            fail("could not create $dir");
        }
        chmod($dir, 0700);
        $contents = $readStdin();
        $tmp = $dir.'/.tmp-'.bin2hex(random_bytes(6));
        $old = umask(0077);
        $ok = file_put_contents($tmp, $contents.PHP_EOL) !== false && chmod($tmp, 0600) && rename($tmp, $path);
        umask($old);
        if (! $ok) {
            @unlink($tmp);
            fail("could not write $path");
        }
        // read back: the file holds exactly what arrived (trim as BackupStatus::passphrase() reads it)
        trim((string) file_get_contents($path)) === trim($contents) || fail("$path did not read back as written");
        sprintf('%o', fileperms($path) & 0777) === '600' || fail("$path is not mode 600");
        say(basename($path).': written outside the application (mode 600) — read back');
        $value = $path;   // the SETTING is the path, which is not secret
        break;

    default:
        fail('usage: secret <KEY> | set <KEY> <value> | secret-file <KEY> <path>', 2);
}

// ── the .env edit ────────────────────────────────────────────────────────────────────────────────
is_file($envPath) || fail("no .env at $envPath", 2);
$before = (string) file_get_contents($envPath);

try {
    $oldValues = Dotenv\Dotenv::parse($before);
} catch (Throwable $e) {
    fail('the current .env does not parse ('.$e::class.') — nothing was written');
}

// the quoting: the first form that parses back to EXACTLY the value
$line = null;
$candidates = [
    $value,
    "'".$value."'",
    '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"',
];
foreach ($candidates as $candidate) {
    try {
        $parsed = Dotenv\Dotenv::parse($key.'='.$candidate);
    } catch (Throwable) {
        continue;
    }
    if (($parsed[$key] ?? null) === $value) {
        $line = $key.'='.$candidate;
        break;
    }
}
$line !== null || fail('no way to write this value that reads back exactly — nothing was written');

$lines = preg_split('/\R/', $before) ?: [];
$out = [];
$placed = false;
foreach ($lines as $existing) {
    if (preg_match('/^\s*(export\s+)?'.preg_quote($key, '/').'\s*=/', $existing) === 1) {
        if (! $placed) {
            $out[] = $line;
            $placed = true;
        }

        continue;   // a duplicate line of the same key is dropped — one definition
    }
    $out[] = $existing;
}
if (! $placed) {
    while ($out !== [] && end($out) === '') {
        array_pop($out);
    }
    $out[] = $line;
    $out[] = '';
}
$after = implode("\n", $out);

// ⚠️ THE PROOF, BEFORE THE FILE IS REPLACED: the new text gives the value for KEY and the old value for every other key.
try {
    $newValues = Dotenv\Dotenv::parse($after);
} catch (Throwable $e) {
    fail('the new .env would not parse ('.$e::class.') — nothing was written');
}
($newValues[$key] ?? null) === $value || fail("$key would not read back as written — nothing was written");
foreach ($oldValues as $name => $old) {
    if ($name !== $key && ($newValues[$name] ?? null) !== $old) {
        fail("$name would change — nothing was written");
    }
}
foreach (array_keys($newValues) as $name) {
    if ($name !== $key && ! array_key_exists($name, $oldValues)) {
        fail("$name would appear — nothing was written");
    }
}

// the old file kept, then the new one in by rename
if (! is_dir($backupDir) && ! mkdir($backupDir, 0700, true)) {
    fail("could not create $backupDir");
}
$saved = rtrim($backupDir, '/').'/.env.'.gmdate('Ymd\THis\Z').'.before-'.$key;
$oldMask = umask(0077);
$tmp = dirname($envPath).'/.env.tmp-'.bin2hex(random_bytes(6));
$ok = copy($envPath, $saved) && chmod($saved, 0600)
    && file_put_contents($tmp, $after) !== false && chmod($tmp, 0600) && rename($tmp, $envPath);
umask($oldMask);
if (! $ok) {
    @unlink($tmp);
    fail('the write failed — the old .env is unchanged or kept at '.$saved);
}

// and read back from the disk itself
clearstatcache();
$onDisk = Dotenv\Dotenv::parse((string) file_get_contents($envPath));
($onDisk[$key] ?? null) === $value || fail("$key did not read back from the disk — the previous file is at $saved");
sprintf('%o', fileperms($envPath) & 0777) === '600' || fail('.env is not mode 600 after the write');

say($secret ? "$key: set — read back from the file (the value is not shown)" : "$key: $value — read back from the file");
say('previous .env kept: '.basename($saved).' (mode 600)');
say('⚠️ not live yet: production reads a cached config — `ops/release.sh recache` then `swap` makes it live.');
exit(0);
