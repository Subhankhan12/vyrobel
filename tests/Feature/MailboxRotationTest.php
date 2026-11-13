<?php

declare(strict_types=1);

use App\Mail\Transport\RotatingMailboxTransport;
use App\Support\Mail\MailboxRotation;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Platform\Health\HealthBoard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

uses(DatabaseTransactions::class);

/**
 * DEC-1 (his ruling, 2026-11-13) — SEND FROM TWO MAILBOXES. Keep Hostinger's daily limit; when the first mailbox
 * (contact@) has reached it, send from the second (sales@) automatically, and go back to the first when its limit frees.
 *
 * The real path is used end to end: `Mail::mailer('rotating')` → the rotating transport → an in-memory transport per
 * mailbox → the MessageSent listener that records each accepted message with the mailbox it left from. The count of "how many
 * has this mailbox sent in the last 24 hours" is read back from that record, so rotation and bookkeeping cannot disagree.
 */
function rotationSetup(int $limit = 3, bool $secondConfigured = true): void
{
    config([
        'mail.mailers.smtp' => ['transport' => 'array', 'username' => 'contact@vyrobel.test'],
        'mail.mailers.smtp_second' => ['transport' => 'array', 'username' => $secondConfigured ? 'sales@vyrobel.test' : null],
        'mail.mailers.rotating' => ['transport' => 'rotating', 'mailers' => ['smtp', 'smtp_second'], 'daily_limit' => $limit],
        'mail.from.address' => 'contact@vyrobel.test',
    ]);
    Mail::purge('rotating');
    Mail::purge('smtp');
    Mail::purge('smtp_second');
}

function rotationSend(string $to = 'guest@guestmail.dev'): void
{
    Mail::mailer('rotating')->raw('Hello from the rotation test.', function ($message) use ($to): void {
        $message->from('contact@vyrobel.test', 'Seaside Hotel')->to($to)->subject('Rotation test');
    });
}

/** What each mailbox's in-memory transport was handed. @return array<string, list<Email>> */
function rotationSent(): array
{
    $out = [];
    foreach (['smtp', 'smtp_second'] as $name) {
        $out[$name] = array_map(fn ($sent) => $sent->getOriginalMessage(), Mail::mailer($name)->getSymfonyTransport()->messages()->all());
    }

    return $out;
}

it('sends from the FIRST mailbox until its daily limit, then from the SECOND', function () {
    rotationSetup(limit: 3);

    foreach (range(1, 5) as $i) {
        rotationSend("g{$i}@guestmail.dev");
    }

    $sent = rotationSent();
    expect(count($sent['smtp']))->toBe(3)->and(count($sent['smtp_second']))->toBe(2);
});

it('sends as the mailbox it left from — the From address follows the login, the display name is kept', function () {
    rotationSetup(limit: 1);

    rotationSend('one@guestmail.dev');
    rotationSend('two@guestmail.dev');

    $sent = rotationSent();
    $first = $sent['smtp'][0]->getFrom()[0];
    $second = $sent['smtp_second'][0]->getFrom()[0];

    expect($first->getAddress())->toBe('contact@vyrobel.test')
        ->and($second->getAddress())->toBe('sales@vyrobel.test')
        ->and($second->getName())->toBe('Seaside Hotel');
});

it('records which mailbox each accepted message left from — a label, never an address', function () {
    rotationSetup(limit: 1);

    rotationSend();
    rotationSend();

    $rows = DB::table('mail_send_log')->where('outcome', 'accepted')->orderBy('id')->pluck('mailbox')->all();
    expect($rows)->toBe(['smtp', 'smtp_second']);
});

it('when BOTH mailboxes have reached the limit the send fails loudly — nothing is silently dropped or over-sent', function () {
    rotationSetup(limit: 1);

    rotationSend('a@guestmail.dev');
    rotationSend('b@guestmail.dev');

    expect(fn () => rotationSend('c@guestmail.dev'))->toThrow(TransportException::class, 'daily limit');

    $sent = rotationSent();
    expect(count($sent['smtp']))->toBe(1)->and(count($sent['smtp_second']))->toBe(1);
});

it('goes BACK to the first mailbox once its limit window has passed', function () {
    rotationSetup(limit: 2);
    // contact@ already sent its 2 within the last 24 hours…
    foreach ([2, 3] as $hoursAgo) {
        DB::table('mail_send_log')->insert(['id' => (string) Str::ulid(), 'outcome' => 'accepted', 'mailable' => 'x', 'mailbox' => 'smtp', 'suppressed_recipients' => 0, 'created_at' => now()->subHours($hoursAgo)]);
    }

    rotationSend('later@guestmail.dev');
    expect(count(rotationSent()['smtp_second']))->toBe(1);   // full → the second

    // …a day later those two are outside the rolling 24 hours: the first is free again.
    $this->travelTo(now()->addHours(25));
    rotationSetup(limit: 2);
    rotationSend('next-day@guestmail.dev');

    expect(count(rotationSent()['smtp']))->toBe(1);
});

it('counts only what was ACCEPTED in the last 24 hours — suppressed messages and older rows do not use the limit up', function () {
    rotationSetup(limit: 2);
    DB::table('mail_send_log')->insert([
        ['id' => (string) Str::ulid(), 'outcome' => 'suppressed', 'mailable' => 'x', 'mailbox' => 'smtp', 'suppressed_recipients' => 1, 'created_at' => now()->subHour()],
        ['id' => (string) Str::ulid(), 'outcome' => 'accepted', 'mailable' => 'x', 'mailbox' => 'smtp', 'suppressed_recipients' => 0, 'created_at' => now()->subHours(30)],
    ]);

    expect(MailboxRotation::usedInLastDay('smtp'))->toBe(0);

    rotationSend();
    expect(count(rotationSent()['smtp']))->toBe(1);
});

it('falls through to the second mailbox when the first REFUSES the message (a login or limit error from the host)', function () {
    $broken = new class implements TransportInterface
    {
        public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
        {
            throw new TransportException('Expected response code "250" but got code "550", with message "550 Daily sending limit exceeded".');
        }

        public function __toString(): string
        {
            return 'broken://';
        }
    };
    $healthy = new ArrayTransport;
    $rotating = new RotatingMailboxTransport([
        ['label' => 'smtp', 'address' => 'contact@vyrobel.test', 'transport' => $broken, 'limit' => 100],
        ['label' => 'smtp_second', 'address' => 'sales@vyrobel.test', 'transport' => $healthy, 'limit' => 100],
    ]);

    // ⚠️ Laravel always hands the transport the message's ORIGINAL envelope (sender = the first mailbox). A test that passes none
    //    proves nothing about what the transport does with the one the real caller gives it.
    $email = (new Email)->from('contact@vyrobel.test')->to('fallback@guestmail.dev')->subject('s')->text('t');
    $rotating->send($email, Envelope::create($email));

    expect(count($healthy->messages()))->toBe(1)
        ->and($healthy->messages()->first()->getEnvelope()->getSender()->getAddress())->toBe('sales@vyrobel.test');
});

it('when the first mailbox refuses AND the second does too, the second\'s error is raised — never swallowed', function () {
    $broken = fn (string $why) => new class($why) implements TransportInterface
    {
        public function __construct(private string $why) {}

        public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
        {
            throw new TransportException($this->why);
        }

        public function __toString(): string
        {
            return 'broken://';
        }
    };
    $rotating = new RotatingMailboxTransport([
        ['label' => 'smtp', 'address' => 'contact@vyrobel.test', 'transport' => $broken('first refused'), 'limit' => 100],
        ['label' => 'smtp_second', 'address' => 'sales@vyrobel.test', 'transport' => $broken('second refused'), 'limit' => 100],
    ]);

    expect(fn () => $rotating->send((new Email)->from('contact@vyrobel.test')->to('x@guestmail.dev')->subject('s')->text('t')))
        ->toThrow(TransportException::class, 'second refused');
});

it('with only ONE mailbox configured it uses that one, with the limit still applied', function () {
    rotationSetup(limit: 2, secondConfigured: false);

    rotationSend('a@guestmail.dev');
    rotationSend('b@guestmail.dev');

    expect(fn () => rotationSend('c@guestmail.dev'))->toThrow(TransportException::class);
    expect(count(rotationSent()['smtp']))->toBe(2)->and(count(rotationSent()['smtp_second']))->toBe(0);
});

it('the mail safety guard reads the rotating mailer through its mailboxes — real outside production is downgraded, local is safe', function () {
    $mailers = [
        'smtp' => ['transport' => 'smtp', 'host' => 'smtp.example-host.test'],
        'smtp_second' => ['transport' => 'smtp', 'host' => 'smtp.example-host.test'],
        'rotating' => ['transport' => 'rotating', 'mailers' => ['smtp', 'smtp_second']],
    ];

    expect(MailSafetyGuard::safeMailerFor('local', 'rotating', null, $mailers))->toBe('log')            // a real host, outside production
        ->and(MailSafetyGuard::safeMailerFor('production', 'rotating', null, $mailers))->toBe('rotating')
        ->and(MailSafetyGuard::isSending('rotating', null, $mailers))->toBeTrue();

    $local = $mailers;
    $local['smtp']['host'] = $local['smtp_second']['host'] = '127.0.0.1';
    expect(MailSafetyGuard::safeMailerFor('local', 'rotating', null, $local))->toBe('rotating')
        ->and(MailSafetyGuard::isSending('rotating', null, $local))->toBeFalse();
});

it('the Mail health card shows how much each mailbox has used today — counts and labels, no address, no secret', function () {
    rotationSetup(limit: 100);
    config(['mail.default' => 'rotating', 'mail.mailers.smtp.host' => 'smtp.example-host.test', 'mail.mailers.smtp.password' => 'x', 'mail.mailers.smtp_second.password' => 'y']);
    DB::table('mail_send_log')->insert(['id' => (string) Str::ulid(), 'outcome' => 'accepted', 'mailable' => 'x', 'mailbox' => 'smtp', 'suppressed_recipients' => 0, 'created_at' => now()->subHour()]);

    $card = collect((new HealthBoard)->cards())->firstWhere('key', 'mail');
    $text = json_encode($card);

    expect($text)->toContain('1 of 100')->not->toContain('contact@vyrobel.test')->not->toContain('sales@vyrobel.test');
});
