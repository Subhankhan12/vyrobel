<?php

declare(strict_types=1);

use App\Listeners\RecordAuthActivity;
use App\Support\Log\LogSafe;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * ⚠️ RESIDENCY TRACE (2026-08-26) — an exception message carries PII with NO logging statement naming it.
 *
 * The inventory said "application logs — UNKNOWN". This is the half of the answer that needed fixing: the
 * context arrays were already clean, and the one key beside them was not.
 */
it('⚠️⚠️ A REAL QueryException CARRIES THE LOGIN IDENTIFIER — and the logged message does not', function () {
    $user = operatorUser(seedScenario(1), [], 'Property Owner');
    $email = (string) $user->email;
    expect($email)->toContain('@');

    // ⚠️ THE REAL MECHANISM, NOT A HAND-BUILT STRING. Dropping the table makes the listener's INSERT throw a
    //    genuine QueryException, whose message Laravel builds with Str::replaceArray('?', $bindings, $sql) —
    //    so the bindings, INCLUDING the login identifier, are interpolated into it by the framework itself.
    Schema::drop('login_activity');

    Log::spy();

    // The listener must swallow it: authentication is unaffected by a failed security-signal row.
    (new RecordAuthActivity)->login(new Login('web', $user, false));

    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) use ($email) {
        // ⚠️ The address the framework interpolated is gone…
        expect($context['error'])->not->toContain($email)
            ->and($context['error'])->toContain(LogSafe::PLACEHOLDER);

        // …and the message is still worth reading: the SQL error survives the redaction.
        expect(strlen((string) $context['error']))->toBeGreaterThan(20);

        return true;
    });
});

it('⚠️ the redaction is NARROW — it removes the address and keeps everything a debugger needs', function () {
    $raw = 'SQLSTATE[42S02]: Base table not found (Connection: mysql, SQL: insert into `login_activity` '
        .'(`identifier`, `ip`) values (ada@example.test, 203.0.113.9))';

    $safe = LogSafe::scrub($raw);

    expect($safe)->not->toContain('ada@example.test')
        ->and($safe)->toContain(LogSafe::PLACEHOLDER)
        // ⚠️ Everything else is untouched — the SQLSTATE, the table, the statement shape.
        ->and($safe)->toContain('SQLSTATE[42S02]')
        ->and($safe)->toContain('login_activity')
        // ⚠️ …and the IP deliberately SURVIVES. Recorded as a known limit in the residency inventory, not
        //    quietly widened: broad redaction on a hunch is the failure this rule exists to prevent.
        ->and($safe)->toContain('203.0.113.9');
});

it('⚠️ a message with no address is returned unchanged', function () {
    $raw = 'Connection refused by the mail transport (code 421).';

    expect(LogSafe::scrub($raw))->toBe($raw);
});

it('⚠️⚠️ NO REMAINING LOG SITE PASSES A RAW EXCEPTION MESSAGE ON A PII-CARRYING PATH', function () {
    // ⚠️ §N.27.5 — a source-scanning guard must read what RUNS, not what is written ABOUT it. Comments are
    //    stripped first, or this test matches its own explanation and the docblock in LogSafe.
    $files = [
        'app/Listeners/RecordAuthActivity.php',
        'app/Services/GuestDeletionService.php',
        'app/Listeners/SendGuestTransactionalEmails.php',
        'app/Jobs/SendBookingConfirmationEmail.php',
    ];

    foreach ($files as $file) {
        $code = '';
        foreach (token_get_all(file_get_contents(base_path($file))) as $token) {
            $code .= is_array($token)
                ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $token[1])
                : $token;
        }

        expect($code)->not->toContain('getMessage()', "raw exception message still logged in {$file}")
            ->and($code)->toContain('LogSafe::message(');
    }
});
