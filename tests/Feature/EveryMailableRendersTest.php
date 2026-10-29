<?php

use App\Mail\AccountDeletionScheduledMail;
use App\Mail\AdminEmailChangeMail;
use App\Mail\BookingConfirmationMail;
use App\Mail\CancellationMail;
use App\Mail\GuestPasswordResetMail;
use App\Mail\LoginApprovalMail;
use App\Mail\MaintenanceCostReportMail;
use App\Mail\MarketingContactMail;
use App\Mail\OperatorGuestMessageMail;
use App\Mail\OwnerEmailChangeMail;
use App\Mail\OwnerInviteMail;
use App\Mail\OwnerReportMail;
use App\Mail\OwnerTeamInviteMail;
use App\Mail\PasswordResetMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PreArrivalMail;
use App\Mail\RefundConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WaitlistNotificationMail;
use App\Mail\WinbackMail;
use App\Models\MarketingContactMessage;
use App\Models\OwnerInvite;
use App\Models\OwnerTeamInvite;
use App\Models\Payment;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ══ EVERY MAILABLE MUST ACTUALLY BUILD — 2026-08-31 ═════════════════════════════════════════════════
//
// ⚠️⚠️ THE BLIND SPOT THIS CLOSES. `Mail::fake()` + `assertSent()` NEVER CALLS `render()`. A mailable whose
// template references a variable the mailer does not pass still passes every one of those assertions — the
// failure only appears when a real message is built, i.e. in front of a real invitee.
//
// ⚠️ THAT IS NOT HYPOTHETICAL: `OwnerTeamInviteMail` passed 7 of the 12 variables `emails/owner-invite`
// requires, so `{{ $eyebrow }}` threw and **every owner-team invitation ever attempted failed to send** —
// after the invite row and the invitee's User row had already been committed. 38 test files use `Mail::fake()`;
// before this test, only FOUR mailables were ever rendered anywhere in the suite.
//
// ⚠️ IT DISCOVERS, IT DOES NOT LIST. The classes come from the filesystem, and a mailable with no builder
// FAILS this test rather than being silently absent — the H-5 shape (a five-of-ninety-seven list) inverted.

/** @return list<class-string<Mailable>> every mailable in the app, discovered. */
function emrDiscover(): array
{
    $found = [];
    foreach (glob(app_path('Mail/*.php')) ?: [] as $file) {
        $class = 'App\\Mail\\'.basename($file, '.php');
        if (class_exists($class) && is_subclass_of($class, Mailable::class)) {
            $found[] = $class;
        }
    }
    sort($found);

    return $found;
}

/** Realistic-enough arguments for each mailable. @return array<class-string, callable(): Mailable> */
function emrBuilders(): array
{
    $s = seedScenario(1);

    // ⚠️ Inlined rather than reusing PermissionEnforcementTest's `payableReservation()`: a Pest function
    //    declared in one test file is only loaded when THAT file loads, so running this file alone would
    //    fatal on an undefined function (the same reason tests/Support/ exists).
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));
    $folios->recordPayment($folio, moneyEur(30000));
    $payment = Payment::query()->where('reservation_id', $reservation->id)->where('type', 'payment')->firstOrFail();
    $guest = $s['guest'];
    ownerRoleUser($s, 0);   // gives unit 0 a real Owner, which the team invite belongs to
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    $ownerInvite = OwnerInvite::create([
        'organization_id' => $s['org']->id, 'email' => 'invitee@example.test',
        'role' => 'owner', 'token_hash' => hash('sha256', 'tok'), 'status' => 'pending',
        'expires_at' => Carbon::now()->addDays(7),
    ]);
    $teamInvite = OwnerTeamInvite::create([
        'organization_id' => $s['org']->id, 'owner_id' => $ownerId,
        'email' => 'team@example.test', 'role' => 'accountant',
        'token_hash' => hash('sha256', 'tok2'), 'status' => 'pending', 'expires_at' => Carbon::now()->addDays(7),
    ]);
    $contact = MarketingContactMessage::create([
        'name' => 'Cara Contact', 'email' => 'cara@example.test', 'topic' => 'sales',
        'message' => 'Tell me more.',
    ]);

    return [
        AccountDeletionScheduledMail::class => fn () => new AccountDeletionScheduledMail($guest),
        AdminEmailChangeMail::class => fn () => new AdminEmailChangeMail('Ada Admin', 'new@example.test', 'https://example.test/verify'),
        BookingConfirmationMail::class => fn () => new BookingConfirmationMail($reservation),
        CancellationMail::class => fn () => new CancellationMail($reservation, 'Guest called', 12500),
        GuestPasswordResetMail::class => fn () => new GuestPasswordResetMail('tok', 'Gina Guest'),
        LoginApprovalMail::class => fn () => new LoginApprovalMail('Olive Owner', 'https://example.test/approve', 'Chrome on macOS', '203.0.113.4', 'today at 09:00'),
        MaintenanceCostReportMail::class => fn () => new MaintenanceCostReportMail('%PDF-1.4 fake'),
        MarketingContactMail::class => fn () => new MarketingContactMail($contact),
        OperatorGuestMessageMail::class => fn () => new OperatorGuestMessageMail('Gina Guest', 'Your room is ready.', 'Seaside', 'https://example.test/u', 'https://example.test/u1'),
        OwnerEmailChangeMail::class => fn () => new OwnerEmailChangeMail('Olive Owner', 'new@example.test', 'https://example.test/verify'),
        OwnerInviteMail::class => fn () => new OwnerInviteMail($ownerInvite, 'tok', 'Ivy Invitee'),
        OwnerReportMail::class => fn () => new OwnerReportMail('Olive Owner', 'September 2026', 'statement.pdf', '%PDF-1.4 fake'),
        OwnerTeamInviteMail::class => fn () => new OwnerTeamInviteMail($teamInvite, 'tok2', 'Tom Team'),
        PasswordResetMail::class => fn () => new PasswordResetMail('tok', 'user@example.test', 'Ada Admin'),
        PaymentReminderMail::class => fn () => new PaymentReminderMail($reservation, 12500),
        PreArrivalMail::class => fn () => new PreArrivalMail($reservation, null),
        RefundConfirmationMail::class => fn () => new RefundConfirmationMail($payment),
        ReviewRequestMail::class => fn () => new ReviewRequestMail($reservation, 'https://example.test/review'),
        VerifyEmailMail::class => fn () => new VerifyEmailMail('Gina Guest', 'https://example.test/v', 'in 24 hours', 'gina@example.test', (string) $s['org']->id),
        WaitlistNotificationMail::class => fn () => new WaitlistNotificationMail('Gina Guest', 'Seaside', '6–8 Sep', 'https://example.test/book'),
        WinbackMail::class => fn () => new WinbackMail('Gina Guest', 'Seaside', 'https://example.test/u', 'https://example.test/u1'),
    ];
}

// ── 1. ⚠️ NO MAILABLE MAY GO UNCOVERED — discovery, not a hand-written list ────────────────────────
it('has a builder for every mailable in the application', function () {
    $discovered = emrDiscover();
    $built = array_keys(emrBuilders());
    sort($built);

    expect($discovered)->not->toBeEmpty();

    $missing = array_values(array_diff($discovered, $built));
    expect($missing)->toBe([], 'a new mailable has no builder here, so nothing proves it can render: '.implode(', ', $missing));

    $stale = array_values(array_diff($built, $discovered));
    expect($stale)->toBe([], 'a builder names a class that no longer exists: '.implode(', ', $stale));
});

// ── 2. ⚠️⚠️ AND EVERY ONE OF THEM MUST ACTUALLY RENDER ─────────────────────────────────────────────
it('renders every mailable to real HTML', function () {
    $failures = [];
    $rendered = 0;

    foreach (emrBuilders() as $class => $make) {
        try {
            $html = $make()->render();
            if (trim($html) === '') {
                $failures[] = class_basename($class).': rendered EMPTY';

                continue;
            }
            $rendered++;
        } catch (Throwable $e) {
            $failures[] = class_basename($class).': '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 120);
        }
    }

    expect($failures)->toBe([], "mailables that cannot be built:\n  - ".implode("\n  - ", $failures));
    expect($rendered)->toBe(count(emrBuilders()));
});

// ── 3. I18N-01 Phase D — AND IN EVERY LANGUAGE THAT HAS BEEN APPLIED ────────────────────────────────────────────
// Each lang/<code>/ folder (written by ops/i18n/apply.php) is rendered through every mailable: no raw key may reach the
// recipient, and the message must not be the English one (a mailable that ignores its locale would send English to a
// German guest). VY_I18N_MAIL_DUMP=<dir> also writes each message to <dir>/<lang>/<Mailable>.html, to be looked at.
it('renders every mailable in every applied language — no raw key, and not the English text', function () {
    $languages = array_values(array_filter(array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR) ?: []), fn (string $l): bool => $l !== 'en' && $l !== 'vendor'));
    if ($languages === []) {
        expect(true)->toBeTrue();

        return;
    }
    $dump = getenv('VY_I18N_MAIL_DUMP') ?: null;
    $problems = [];

    foreach ($languages as $lang) {
        config(['app.available_locales' => ['en', $lang]]);
        foreach (emrBuilders() as $class => $make) {
            $english = $make()->locale('en')->render();
            $html = $make()->locale($lang)->render();
            $name = class_basename($class);
            if ($dump !== null) {
                @mkdir("$dump/$lang", 0777, true);
                file_put_contents("$dump/$lang/$name.html", $html);
            }
            if (preg_match_all('~(?<![\w./-])((?:[a-z_]+/)+[a-z_-]+\.[a-z0-9_]+(?:\.[a-z0-9_]+)*)(?![\w(])~', strip_tags($html), $m)) {
                foreach (array_unique($m[1]) as $k) {
                    if (Lang::has($k, 'en', false)) {
                        $problems[] = "$lang $name: raw key $k";
                    }
                }
            }
            if ($html === $english) {
                $problems[] = "$lang $name: identical to the English message";
            }
        }
    }

    expect($problems)->toBe([], "mail problems:\n  - ".implode("\n  - ", $problems));
});
