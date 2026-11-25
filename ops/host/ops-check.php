<?php

declare(strict_types=1);

/*
 * ─────────────────────────────────────────────────────────────────────────────────────────────────
 * ops/host/ops-check.php — VY-10 / VY-12 (2026-11-25): what ops/secrets.sh reads on the host, through the LIVE release's own
 * config (the cached one — what the site actually runs on).
 *
 *   php ops/host/ops-check.php mail-preflight        is mail ready to switch on? (names, yes/no and counts only)
 *   php ops/host/ops-check.php mail-send <address>   send ONE test message to <address> through the configured mailer
 *   php ops/host/ops-check.php backup-status         the Backups card's facts (ages, sizes, yes/no)
 *
 * ⚠️ NOTHING SECRET IS PRINTED: a password is reported as "set" or "not set", never shown; an SMTP error is reported by its class
 *    and message (the transport's messages name the server and the username, never the password).
 * ⚠️ `mail-send` is the one write: one message, to the address HE types (his own) — never a guest, never a probe address.
 * ─────────────────────────────────────────────────────────────────────────────────────────────────
 */

use App\Support\Mail\MailSafetyGuard;
use App\Support\Ops\BackupStatus;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

$base = dirname(__DIR__, 2);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$out = static fn (string $line) => fwrite(STDOUT, '  '.$line.PHP_EOL);
$yes = static fn (mixed $v): string => filled($v) ? 'set' : 'not set';

switch ($argv[1] ?? '') {
    case 'mail-preflight':
        $default = (string) config('mail.default');
        $out('mailer now          : '.$default.(MailSafetyGuard::sendingNow() ? ' (SENDING)' : ' (not sending)'));
        $out('smtp host / port    : '.config('mail.mailers.smtp.host').' / '.config('mail.mailers.smtp.port'));
        $out('smtp username       : '.$yes(config('mail.mailers.smtp.username')));
        $out('smtp password       : '.$yes(config('mail.mailers.smtp.password')).'   (in the LIVE config)');
        $out('from address        : '.config('mail.from.address'));
        // ⚠️ RULES Part 1: never switch mail on with unreviewed mail in the queue — everything waiting would go out at once.
        $mailLike = static fn ($q) => $q->where(fn ($w) => $w->where('payload', 'like', '%Mail%')->orWhere('payload', 'like', '%Notification%'));
        $pending = $mailLike(DB::table('jobs'))->count();
        $failed = $mailLike(DB::table('failed_jobs'))->count();
        $out('mail jobs waiting   : '.$pending);
        $out('mail jobs failed    : '.$failed);
        fwrite(STDOUT, 'PREFLIGHT pending='.$pending.' failed='.$failed.PHP_EOL);
        exit(0);

    case 'mail-send':
        $to = (string) ($argv[2] ?? '');
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $out('✗ not an email address');
            exit(2);
        }
        if (! MailSafetyGuard::sendingNow()) {
            $out('✗ the live mailer is '.config('mail.default').' — it sends nothing, so there is nothing to test');
            exit(1);
        }
        try {
            Mail::raw(
                "This is the one test message from Vyrobel's mail switch (ops/secrets.sh mail).\n\n"
                ."If it reached you, the server sends mail. Nothing else was sent.\n",
                fn ($m) => $m->to($to)->subject('Vyrobel — mail is on (test message)'),
            );
        } catch (Throwable $e) {
            $out('✗ the mail server refused it: '.$e::class.' — '.mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()) ?? '', 0, 300));
            exit(1);
        }
        $out('✓ accepted by '.config('mail.mailers.smtp.host').' for '.$to.' — look in that inbox (and its spam folder)');
        exit(0);

    case 'backup-status':
        $r = BackupStatus::report();
        $age = static fn (?int $s): string => $s === null ? 'never' : ($s < 120 ? $s.' s ago' : intdiv($s, 60).' min ago');
        $out('passphrase file     : '.($r['configured'] ? 'set up' : 'NOT set up'));
        $out('newest backup       : '.($r['last_name'] ?? 'none').' ('.$age($r['age_seconds']).', '.($r['bytes'] ?? 0).' bytes)');
        $out('backup recent       : '.($r['healthy'] ? 'yes' : 'NO'));
        $out('off-server copy     : '.($r['offsite_configured'] ? 'set up' : 'not set up').', last '.$age($r['offsite_age_seconds'])
            .' — '.($r['offsite_healthy'] ? 'recent' : 'NOT recent'));
        exit(0);

    default:
        $out('usage: mail-preflight | mail-send <address> | backup-status');
        exit(2);
}
