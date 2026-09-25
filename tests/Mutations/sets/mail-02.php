<?php

declare(strict_types=1);

// Mutation set mail-02 — MAIL 02 (Hostinger SMTP): each guard that makes switching mail on safe, removed one at a time.
// Run with `php tests/Mutations/run.php --set=mail-02`.

return [
    [
        'id' => 'MAIL-M1',
        'name' => 'the guard forgets that SMTP to a real host is real sending',
        'file' => 'app/Support/Mail/MailSafetyGuard.php',
        'old' => '        if ($mailer === \'smtp\') {
            return ! self::isLocalSmtpHost($smtpHost);
        }

',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'SMTP to a REAL host']],
    ],
    [
        'id' => 'MAIL-M2',
        'name' => 'the suppression listener is not registered',
        'file' => 'app/Providers/AppServiceProvider.php',
        'old' => '        Event::listen(MessageSending::class, SuppressUndeliverableRecipients::class);
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'never sent — suppressed']],
    ],
    [
        'id' => 'MAIL-M3',
        'name' => '.invalid is no longer a reserved name',
        'file' => 'app/Support/Mail/UndeliverableRecipients.php',
        'old' => 'public const RESERVED_TLDS = [\'invalid\', \'test\', \'example\', \'localhost\'];',
        'new' => 'public const RESERVED_TLDS = [\'test\', \'example\', \'localhost\'];',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'never sent — suppressed']],
    ],
    [
        'id' => 'MAIL-M4',
        'name' => 'a message with nobody real left is sent anyway',
        'file' => 'app/Listeners/Mail/SuppressUndeliverableRecipients.php',
        'old' => '            return false;   // nobody real left to send it to — cancel the whole message',
        'new' => '            return true;',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'never sent — suppressed']],
    ],
    [
        'id' => 'MAIL-M5',
        'name' => 'the explicit suppression list is ignored',
        'file' => 'app/Support/Mail/UndeliverableRecipients.php',
        'old' => '        if (in_array($address, self::listed(), true)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'the rule is the RFC reserved names']],
    ],
    [
        'id' => 'MAIL-M6',
        'name' => 'the card no longer checks the sender matches the SMTP login',
        'file' => 'app/Support/Platform/Health/HealthBoard.php',
        'old' => '            if (strcasecmp(trim((string) config(\'mail.from.address\')), trim((string) config(\'mail.mailers.smtp.username\'))) !== 0) {',
        'new' => '            if (false) {',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'the mail card is RED for SMTP']],
    ],
    [
        'id' => 'MAIL-M7',
        'name' => 'the card no longer notices SMTP pointed at this machine',
        'file' => 'app/Support/Platform/Health/HealthBoard.php',
        'old' => '            if (MailSafetyGuard::isLocalSmtpHost((string) config(\'mail.mailers.smtp.host\'))) {',
        'new' => '            if (false) {',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'the mail card is RED for SMTP']],
    ],
    [
        'id' => 'MAIL-M8',
        'name' => 'accepted mail is not recorded',
        'file' => 'app/Providers/AppServiceProvider.php',
        'old' => '        Event::listen(MessageSent::class, RecordAcceptedMail::class);
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'records every message the mail server accepted']],
    ],
    [
        'id' => 'MAIL-M9',
        'name' => 'the public privacy page says email is active whatever the mailer',
        'file' => 'resources/views/marketing/privacy.blade.php',
        'old' => '          @if ($mailProcessor[\'active\'])',
        'new' => '          @if (true)',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'the public privacy page names Hostinger']],
    ],
    [
        'id' => 'MAIL-M10',
        'name' => 'a notification sent by email is recorded as "unknown"',
        'file' => 'app/Support/Mail/MailSendLog.php',
        'old' => '        return (string) ($data[\'__laravel_mailable\'] ?? $data[\'__laravel_notification\'] ?? \'unknown\');',
        'new' => '        return (string) ($data[\'__laravel_mailable\'] ?? \'unknown\');',
        'tests' => [['file' => 'tests/Feature/MailHostingerSmtpTest.php', 'filter' => 'recorded by its class']],
    ],
];
