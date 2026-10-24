<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\LoginActivity;
use App\Models\User;
use App\Notifications\NewDeviceSignInNotification;
use App\Services\NotificationPreferences;
use App\Support\Auth\LoginApprovalService;
use App\Support\Security\NewDeviceSignIn;
use App\Support\Security\SessionTimeout;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

uses(DatabaseTransactions::class);

// ⚠️⚠️ SECURITY, PART 3 (2026-10-24, the founder's design): the protections the design shows that had no backend — login alerts
// (the "Login from a new device" switch existed and sent nothing), the session timeout ("Soon" on the owner page), when the
// password was last changed (never stored), login approval on the back office, and approving a waiting sign-in from a device
// already signed in. Each test failed first.

const SXP_CHROME_WIN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36';
const SXP_SAFARI_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15';

function sxpPriorLogin(User $user, string $ua, int $daysAgo = 3): void
{
    LoginActivity::create(['event' => 'login', 'guard' => 'web', 'subject_type' => 'user', 'subject_id' => (string) $user->getKey(),
        'identifier' => (string) $user->email, 'ip' => '10.1.1.1', 'user_agent' => $ua, 'occurred_at' => now()->subDays($daysAgo)]);
}

function sxpSignIn(User $user, string $ua, string $password = 'password'): TestResponse
{
    return test()->withHeader('User-Agent', $ua)->post('/login', ['email' => $user->email, 'password' => $password]);
}

// ── LOGIN ALERTS ─────────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ alerts a person when their account signs in from a device it has not used — and only then', function () {
    Notification::fake();
    $s = seedScenario(1);
    $me = operatorUser($s, ['password' => 'password'], 'Front Desk');
    sxpPriorLogin($me, SXP_SAFARI_MAC);

    sxpSignIn($me, SXP_CHROME_WIN);
    Notification::assertSentTo($me, NewDeviceSignInNotification::class, function (NewDeviceSignInNotification $n) use ($me): bool {
        $data = $n->data($me);

        return $n->eventKey() === NewDeviceSignIn::OPERATOR_KEY && $data['title'] === 'New sign-in from Chrome on Windows'
            && $data['url'] === route('app.security', [], false);
    });

    // the same device again is not news
    Notification::fake();
    test()->post('/logout');
    sxpSignIn($me, SXP_SAFARI_MAC);
    Notification::assertNotSentTo($me, NewDeviceSignInNotification::class);
});

it('⚠️⚠️ never treats the sign-in happening now as its own history — whichever listener wrote it first', function () {
    // one frozen instant for the row and the sign-in: the log keeps whole seconds, the sign-in's own clock does not
    test()->freezeTime();
    Notification::fake();
    $s = seedScenario(1);
    $me = operatorUser($s, ['password' => 'password'], 'Front Desk');
    sxpPriorLogin($me, SXP_SAFARI_MAC);
    // the sign-in log's row for THIS sign-in, already written in this same second (as if its listener had run first)
    LoginActivity::create(['event' => 'login', 'guard' => 'web', 'subject_type' => 'user', 'subject_id' => (string) $me->getKey(),
        'identifier' => (string) $me->email, 'ip' => '10.1.1.2', 'user_agent' => SXP_CHROME_WIN, 'occurred_at' => now()]);

    sxpSignIn($me, SXP_CHROME_WIN);
    Notification::assertSentTo($me, NewDeviceSignInNotification::class);
});

it('⚠️ never alerts on an account’s very first sign-in — there is nothing to compare it with', function () {
    Notification::fake();
    $s = seedScenario(1);
    $me = operatorUser($s, ['password' => 'password'], 'Front Desk');

    sxpSignIn($me, SXP_CHROME_WIN);
    Notification::assertNotSentTo($me, NewDeviceSignInNotification::class);
});

it('⚠️⚠️ makes the “Login from a new device” switch real: off sends nothing to the bell, and the Security switch is the same setting', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, ['password' => 'password'], 'Front Desk');
    sxpPriorLogin($me, SXP_SAFARI_MAC);

    test()->actingAs($me)->post(route('app.security.login-alerts'), ['enabled' => '0'])->assertRedirect();
    expect(app(NotificationPreferences::class)->allows($me, 'new_device_login', 'in_app'))->toBeFalse()
        ->and(AuditEvent::query()->where('action', 'app.login_alerts_changed')->exists())->toBeTrue();

    test()->post('/logout');
    sxpSignIn($me, SXP_CHROME_WIN);
    expect($me->notifications()->count())->toBe(0);

    // on again — the bell gets it (the database channel; email is the preference gate's and the mailer's to decide)
    test()->post('/logout');
    test()->actingAs($me->fresh())->post(route('app.security.login-alerts'), ['enabled' => '1']);
    test()->post('/logout');
    sxpSignIn($me, 'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0');
    expect($me->notifications()->count())->toBe(1);
});

it('⚠️ says an owner’s sign-in alerts are always on — the owner settings promise it — and offers no switch', function () {
    $s = seedScenario(1);
    $html = (string) test()->actingAs(ownerRoleUser($s, 0))->get(route('owner.security'))->assertOk()->getContent();

    expect($html)->toContain('<span class="vy-sx-chip">Always on</span>')
        ->and(NewDeviceSignIn::eventKeyFor(ownerRoleUser($s, 0)))->toBe(NewDeviceSignIn::OWNER_KEY);
});

// ── SESSION TIMEOUT ──────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ signs a person out after the inactivity THEY chose — and only then', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Front Desk');

    test()->actingAs($me)->post(route('app.security.session-timeout'), ['minutes' => '15'])->assertRedirect();
    expect($me->fresh()->session_timeout_minutes)->toBe(15)
        ->and(AuditEvent::query()->where('action', 'app.session_timeout_changed')->exists())->toBeTrue();
    $me = $me->fresh();

    // (a test request carries no session cookie, so the time of the person's last own request is given to it, as a browser's
    // session would hold it)
    // 14 minutes since their last own request: still in — and the clock restarts on this one
    test()->actingAs($me)->withSession([SessionTimeout::KEY => time() - 14 * 60])->get(route('app.security'))->assertOk();
    expect(session(SessionTimeout::KEY))->toBeGreaterThanOrEqual(time() - 2);

    // 16 minutes: signed out, and sent to sign in with the reason
    test()->actingAs($me)->withSession([SessionTimeout::KEY => time() - 16 * 60])->get(route('app.security'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', fn (string $m): bool => str_contains($m, '15 minutes without activity'));
    test()->assertGuest();
});

it('⚠️⚠️ never counts a page refreshing itself as the person — the bell’s pulse does not keep a session alive', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Front Desk');
    $me->forceFill(['session_timeout_minutes' => 15])->save();

    // the pulse an open tab sends every few seconds moves nothing…
    $tenMinutesAgo = time() - 10 * 60;
    test()->actingAs($me->fresh())->withSession([SessionTimeout::KEY => $tenMinutesAgo])->getJson(route('app.pulse'))->assertOk();
    expect(session(SessionTimeout::KEY))->toBe($tenMinutesAgo);

    // …and once the person has been away past the limit, the tab's pulse is told so (a 401, not a page)
    test()->actingAs($me->fresh())->withSession([SessionTimeout::KEY => time() - 16 * 60])->getJson(route('app.pulse'))->assertStatus(401);
    test()->assertGuest();
});

it('leaves everyone who chose no timeout exactly as before', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Front Desk');

    test()->actingAs($me)->get(route('app.security'))->assertOk();
    test()->travel(100)->minutes();
    test()->get(route('app.security'))->assertOk();
    expect(SessionTimeout::minutesFor($me->fresh()))->toBeNull();
});

it('refuses a timeout that is not one of the choices', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Front Desk');

    test()->actingAs($me)->post(route('app.security.session-timeout'), ['minutes' => '1'])->assertSessionHasErrors('minutes');
    expect($me->fresh()->session_timeout_minutes)->toBeNull();
});

// ── PASSWORD LAST CHANGED ────────────────────────────────────────────────────────────────────────────────────────

it('⚠️ records when a password changes — on every path, through the model — and says "not recorded" before that', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Front Desk');
    $me->forceFill(['password_changed_at' => null])->saveQuietly();

    expect((string) test()->actingAs($me->fresh())->get(route('app.security'))->getContent())->toContain('Not recorded yet');

    test()->actingAs($me->fresh())->post(route('app.security.password'), ['_form' => 'password', 'current_password' => 'password',
        'password' => 'A-new-pass-12!', 'password_confirmation' => 'A-new-pass-12!'])->assertRedirect();
    expect($me->fresh()->password_changed_at)->not->toBeNull()
        ->and((string) test()->actingAs($me->fresh())->get(route('app.security'))->getContent())->toContain('Changed 0 seconds ago');
});

// ── LOGIN APPROVAL, BACK OFFICE + APPROVE FROM HERE ──────────────────────────────────────────────────────────────

it('⚠️⚠️ gives the back office login approval too — on only while email really goes out, off always', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, ['password' => 'password'], 'Front Desk');

    test()->actingAs($me)->from(route('app.security'))->post(route('app.security.login-approval'), ['enabled' => '1', 'current_password' => 'password'])
        ->assertSessionHas('security_error');
    expect($me->fresh()->require_login_approval)->toBeFalse();

    Mail::fake();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.hostinger.com']);
    test()->actingAs($me)->post(route('app.security.login-approval'), ['enabled' => '1', 'current_password' => 'password'])->assertRedirect();
    expect($me->fresh()->require_login_approval)->toBeTrue()
        ->and(AuditEvent::query()->where('action', 'app.login_approval_changed')->exists())->toBeTrue();
});

it('⚠️⚠️ lets a person approve a waiting sign-in from a device where they are signed in — their own only', function () {
    Mail::fake();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.hostinger.com']);
    $s = seedScenario(1);
    $me = operatorUser($s, ['password' => 'password', 'require_login_approval' => true], 'Front Desk');
    $other = operatorUser($s, ['password' => 'password', 'require_login_approval' => true], 'Front Desk');

    [$mine] = app(LoginApprovalService::class)->open($me, request()->duplicate(server: ['HTTP_USER_AGENT' => SXP_CHROME_WIN, 'REMOTE_ADDR' => '82.14.7.20']));
    [$theirs] = app(LoginApprovalService::class)->open($other, request());

    $html = (string) test()->actingAs($me)->get(route('app.security'))->getContent();
    expect($html)->toContain('Approval needed')->toContain('New sign-in · Chrome on Windows')->toContain('82.14.7.20');

    // someone else's waiting sign-in cannot be decided from here
    test()->actingAs($me)->post(route('app.security.login-approval.decide'), ['approval' => $theirs->id, 'decision' => 'approve'])
        ->assertSessionHas('security_error');
    expect($theirs->fresh()->approved_at)->toBeNull();

    test()->actingAs($me)->post(route('app.security.login-approval.decide'), ['approval' => $mine->id, 'decision' => 'approve'])->assertRedirect();
    expect($mine->fresh()->approved_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('action', 'app.login_approval_approved')->exists())->toBeTrue();
});

it('⚠️ closes a denied sign-in for good — the waiting browser cannot finish it', function () {
    Mail::fake();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.hostinger.com']);
    $s = seedScenario(1);
    $me = operatorUser($s, ['password' => 'password', 'require_login_approval' => true], 'Front Desk');
    [$mine] = app(LoginApprovalService::class)->open($me, request());

    test()->actingAs($me)->post(route('app.security.login-approval.decide'), ['approval' => $mine->id, 'decision' => 'deny'])->assertRedirect();
    expect($mine->fresh()->consumed_at)->not->toBeNull()->and($mine->fresh()->approved_at)->toBeNull()
        ->and(app(LoginApprovalService::class)->pending($me, (string) $mine->id))->toBeNull();
});
