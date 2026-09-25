<?php

declare(strict_types=1);

use App\Mail\PasswordResetMail;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Mail\MailSendLog;
use App\Support\Mail\UndeliverableRecipients;
use App\Support\Platform\Health\HealthBoard;
use App\Support\Platform\Health\HealthCard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

// ⚠️⚠️ MAIL 02 (2026-09-25) — the founder chose Hostinger's own mail (SMTP, contact@vyrobel.com) over Postmark. Three
// things had to become true before it could be switched on: the #1 guard must treat SMTP to a real host as REAL sending;
// nothing may ever be handed to the mail server for an address that can never receive mail (the probe accounts on
// .invalid would bounce against the founder's own mailbox); and the health board must report what it can really
// measure — accepted, suppressed, failed — and say plainly that bounces are not visible to the app.

uses(DatabaseTransactions::class);

/** Send one real PasswordResetMail through the ARRAY transport (the suite's mailer) and return what it captured. */
function mhSend(array $to, array $cc = []): array
{
    $mailer = app('mailer');
    $mailer->getSymfonyTransport()->flush();
    $pending = Mail::to($to);
    if ($cc !== []) {
        $pending->cc($cc);
    }
    $pending->send(new PasswordResetMail('tok-'.Str::random(8), 'someone@example.net', 'Someone'));

    return $mailer->getSymfonyTransport()->messages()->all();
}

function mhCard(): HealthCard
{
    return collect(app(HealthBoard::class)->cards())->firstOrFail(fn (HealthCard $c) => $c->key === 'mail');
}

// ══ 1 · THE #1 GUARD — SMTP to a real host is real sending ══════════════════════════════════════════════════════

it('⚠️⚠️ SMTP to a REAL host is refused outside production — Mailpit on this machine is not', function () {
    expect(MailSafetyGuard::safeMailerFor('local', 'smtp', 'smtp.hostinger.com'))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('testing', 'smtp', 'smtp.hostinger.com'))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('staging', 'smtp', 'SMTP.Hostinger.com'))->toBe('log')
        ->and(MailSafetyGuard::safeMailerFor('production', 'smtp', 'smtp.hostinger.com'))->toBe('smtp')
        // Mailpit, the local catch-inbox, stays usable in development — it never reaches a person.
        ->and(MailSafetyGuard::safeMailerFor('local', 'smtp', '127.0.0.1'))->toBe('smtp')
        ->and(MailSafetyGuard::safeMailerFor('local', 'smtp', 'localhost'))->toBe('smtp')
        ->and(MailSafetyGuard::safeMailerFor('local', 'smtp', 'mailpit'))->toBe('smtp')
        ->and(MailSafetyGuard::isSending('smtp', 'smtp.hostinger.com'))->toBeTrue()
        ->and(MailSafetyGuard::isSending('smtp', '127.0.0.1'))->toBeFalse();
})->group('security');

// ══ 2 · NEVER HAND THE SERVER AN ADDRESS THAT CAN NEVER RECEIVE MAIL ══════════════════════════════════════════

it('⚠️⚠️ an email to a probe address (.invalid) is never sent — suppressed, counted, and nobody else is harmed', function () {
    $before = MailSendLog::count(MailSendLog::SUPPRESSED, now()->subMinute());

    expect(mhSend(['probe-founder@vyrobel-probe.invalid']))->toBe([])   // cancelled outright
        ->and(MailSendLog::count(MailSendLog::SUPPRESSED, now()->subMinute()))->toBe($before + 1);

    // Mixed: the real recipient still gets it; the undeliverable one is removed from the envelope.
    $sent = mhSend(['real.person@vyrobel.com'], ['ghost@example.com']);
    expect($sent)->toHaveCount(1);
    $email = $sent[0]->getOriginalMessage();
    expect(array_map(fn ($a) => $a->getAddress(), $email->getTo()))->toBe(['real.person@vyrobel.com'])
        ->and($email->getCc())->toBe([]);
})->group('security');

it('the rule is the RFC reserved names plus an explicit list — never a pattern that could stop a real person', function () {
    expect(UndeliverableRecipients::reason('a@vyrobel-probe.invalid'))->toBe('reserved top-level domain .invalid')
        ->and(UndeliverableRecipients::reason('a@demo.test'))->toBe('reserved top-level domain .test')
        ->and(UndeliverableRecipients::reason('a@sub.example.org'))->toBe('reserved domain example.org')
        ->and(UndeliverableRecipients::reason('A@EXAMPLE.COM'))->toBe('reserved domain example.com')
        ->and(UndeliverableRecipients::reason('contact@vyrobel.com'))->toBeNull()
        ->and(UndeliverableRecipients::reason('someone@gmail.com'))->toBeNull()
        ->and(UndeliverableRecipients::reason('invalid@testing.com'))->toBeNull();   // a real domain that merely CONTAINS the words

    config(['mail.suppress_addresses' => ['internal.frontdesk@vyrobel.com']]);
    expect(UndeliverableRecipients::reason('Internal.FrontDesk@vyrobel.com'))->toBe('on the suppression list')
        ->and(mhSend(['internal.frontdesk@vyrobel.com']))->toBe([]);
})->group('security');

it('logs the reason and the email TYPE when it suppresses — never the address', function () {
    Log::spy();
    mhSend(['guest-person@hotel.invalid']);

    Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
        return str_contains($message, 'suppressed')
            && ! str_contains(json_encode($context), 'guest-person')
            && ($context['reasons'] ?? []) === ['reserved top-level domain .invalid'];
    })->once();
})->group('security');

// ══ 3 · THE COUNTS — what the server accepted is recorded, with no personal data ══════════════════════════════

it('records every message the mail server accepted — type and server id only, no address, subject or body', function () {
    $before = MailSendLog::count(MailSendLog::ACCEPTED, now()->subMinute());
    mhSend(['real.person@vyrobel.com']);

    expect(MailSendLog::count(MailSendLog::ACCEPTED, now()->subMinute()))->toBe($before + 1);
    $row = DB::table('mail_send_log')->where('outcome', 'accepted')->latest('created_at')->first();
    expect($row->mailable)->toBe('PasswordResetMail')
        ->and(json_encode($row))->not->toContain('real.person')->not->toContain('vyrobel.com');
})->group('security');

// ══ 4 · THE BOARD — red for every way SMTP can be half-set, green only when it can really deliver ══════════════

it('⚠️⚠️ the mail card is RED for SMTP on this machine, an incomplete login, or a sender that is not the login', function () {
    $ok = ['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.hostinger.com', 'mail.mailers.smtp.username' => 'contact@vyrobel.com',
        'mail.mailers.smtp.password' => 'secret-value-never-shown', 'mail.from.address' => 'contact@vyrobel.com'];

    config(array_merge($ok, ['mail.mailers.smtp.host' => '127.0.0.1']));
    expect(mhCard()->status)->toBe(HealthCard::RED)->and(mhCard()->headline)->toBe('Mail goes to this machine, not to people');

    config(array_merge($ok, ['mail.mailers.smtp.password' => null]));
    expect(mhCard()->status)->toBe(HealthCard::RED)->and(implode(' ', mhCard()->lines))->toContain('MAIL_PASSWORD');

    config(array_merge($ok, ['mail.from.address' => 'no-reply@vyrobel.com']));
    expect(mhCard()->status)->toBe(HealthCard::RED)->and(mhCard()->headline)->toBe('Sender does not match the SMTP login');

    config($ok);
    $c = mhCard();
    expect($c->status)->toBe(HealthCard::GREEN)
        ->and(implode(' ', $c->lines).' '.$c->headline)->not->toContain('secret-value-never-shown')->not->toContain('contact@vyrobel.com');
})->group('security');

it('a queued email that FAILED turns the card amber, counted from failed_jobs', function () {
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.hostinger.com', 'mail.mailers.smtp.username' => 'contact@vyrobel.com',
        'mail.mailers.smtp.password' => 'x', 'mail.from.address' => 'contact@vyrobel.com']);
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\SendBookingConfirmationEmail']),
        'exception' => 'Symfony\\Component\\Mailer\\Exception\\TransportException: 553 sender rejected', 'failed_at' => now(),
    ]);

    expect(mhCard()->status)->toBe(HealthCard::AMBER)
        ->and(implode(' ', mhCard()->lines))->toContain('Failed on the queue — 7 days: 1');
})->group('security');

// ══ 5 · THE PUBLIC PRIVACY PAGE — the email processor it names follows the configured mailer ══════════════════════

it('⚠️ the public privacy page names Hostinger for email, never Postmark, and says "active" only when mail really goes', function () {
    config(['mail.default' => 'log']);
    $off = (string) $this->get(route('marketing.page.privacy'))->assertOk()->getContent();

    config(['mail.default' => 'smtp']);
    $on = (string) $this->get(route('marketing.page.privacy'))->assertOk()->getContent();

    foreach ([$off, $on] as $html) {
        expect($html)->toContain('Hostinger (email)')->not->toContain('Postmark');
    }
    expect($off)->toContain('no email is currently being delivered from this system')
        ->and($on)->not->toContain('no email is currently being delivered from this system')
        ->and($on)->toMatch('/Hostinger \(email\)<\/strong>[^<]*\s+Active\.\s/');
})->group('security');

// ══ 6 · WHICH EMAIL — a notification sent by email is recorded by its class, never "unknown" ═════════════════════
// 🔬 Found on production (TONIGHT 01): the first real sign-up's verification link — a NOTIFICATION — was logged as
// "unknown", because a notification carries its class under `__laravel_notification`, not `__laravel_mailable`.

final class MhProbeNotification extends Illuminate\Notifications\Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Probe')->line('Probe.');
    }
}

it('⚠️ a NOTIFICATION sent by email is recorded by its class — accepted and suppressed alike, never "unknown"', function () {
    $since = now()->subSecond();
    Notification::route('mail', 'someone@vyrobel.com')->notify(new MhProbeNotification);   // accepted (the suite's array transport)
    Notification::route('mail', 'nobody@example.net')->notify(new MhProbeNotification);    // suppressed (a reserved domain)

    $outcomes = DB::table('mail_send_log')->where('created_at', '>=', $since)->where('mailable', 'MhProbeNotification')->pluck('outcome')->all();
    expect($outcomes)->toContain(MailSendLog::ACCEPTED)->toContain(MailSendLog::SUPPRESSED)
        ->and(DB::table('mail_send_log')->where('created_at', '>=', $since)->where('mailable', 'unknown')->count())->toBe(0);
})->group('security');
