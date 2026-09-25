<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\OwnerInviteService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ GATE 01 — VERIFY BEFORE `/app` (founder ruling, 2026-09-23). Signup stays fully open and public; an
 * unverified account does nothing until the link is clicked.
 *
 * ⚠️⚠️ PROVED BY REACHING, NOT BY READING. `route:list` has lied in this project once already — it rewrote
 * `can:` to `Authorize:` and reported *"55 of 55 ungated"* when the truth was 19. So every assertion below
 * issues a real request and records the real status.
 *
 * ⚠️⚠️ AND BOTH HALVES, ALWAYS: **an unverified user is turned away AND a verified user passes.**
 * A door only ever seen to close is a wall.
 */
function gateUser(array $scenario, bool $verified): User
{
    $u = operatorUser($scenario, ['email_verified_at' => $verified ? now() : null], 'Hotel Manager');

    expect($u->fresh()->hasVerifiedEmail())->toBe($verified, 'the fixture must really be in the intended state');

    return $u->fresh();
}

// ── 1 · THE DOOR CLOSES ─────────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ turns an UNVERIFIED operator away from every /app route it can reach', function () {
    $s = seedScenario(1);
    $user = gateUser($s, verified: false);

    // ⚠️ EVERY GET route under /app with no required parameters — reached, not listed.
    $paths = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r): bool => str_starts_with((string) $r->uri(), 'app')
            && in_array('GET', $r->methods(), true)
            && ! str_contains((string) $r->uri(), '{'))
        ->map(fn ($r): string => '/'.$r->uri())->unique()->values();

    expect($paths->count())->toBeGreaterThan(20, 'the probe must actually have routes to walk');

    $leaked = [];
    foreach ($paths as $path) {
        // ⚠️ `baseResponse->getStatusCode()` rather than `->status()`: some /app routes return a
        //    StreamedResponse (exports/PDFs), and 🔬 that difference only surfaced when the gate-removal
        //    mutation let those routes actually EXECUTE. A walker that errors on the mutation is a weaker
        //    signal than one that reports the status, so it reads the code the same way for every route.
        $status = $this->actingAs($user)->get($path)->baseResponse->getStatusCode();
        // ⚠️ 302 → the verification notice is the PASS. A 200 means the gate let them in.
        if ($status === 200) {
            $leaked[] = $path;
        }
    }

    expect($leaked)->toBe([], 'these /app routes served an UNVERIFIED user: '.implode(', ', $leaked));
});

// ── 2 · THE DOOR OPENS — without this, the gate above is just a wall ────────────────────────────────
it('⚠️⚠️ lets a VERIFIED operator through the same routes', function () {
    $s = seedScenario(1);
    $user = gateUser($s, verified: true);

    $ok = 0;
    foreach (['/app/dashboard', '/app/front-desk', '/app/reservations', '/app/calendar'] as $path) {
        $status = $this->actingAs($user)->get($path)->status();
        expect($status)->toBe(200, "a VERIFIED operator was refused {$path} (status {$status})");
        $ok++;
    }
    expect($ok)->toBe(4);
});

// ── 3 · THE ROUTES THAT LIFT THE GATE MUST SIT OUTSIDE IT ───────────────────────────────────────────
it('⚠️⚠️ lets an unverified user reach the notice page and SIGN OUT — the way out is never gated', function () {
    $s = seedScenario(1);
    $user = gateUser($s, verified: false);

    // ⚠️ THE CLASSIC SELF-INFLICTED WOUND: gating the page that lifts the gate loops forever.
    $this->actingAs($user)->get('/email/verify')->assertOk();
    $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();

    // ⚠️ AND THEY MUST BE ABLE TO LEAVE.
    $this->actingAs($user)->post('/logout')->assertRedirect();
});

// ── 4 · THE CROSSING, WHICH NEEDS NO MAIL ───────────────────────────────────────────────────────────
it('⚠️⚠️ crosses from blocked to allowed when the SIGNED link is followed', function () {
    $s = seedScenario(1);
    $user = gateUser($s, verified: false);

    // blocked before
    $this->actingAs($user)->get('/app/dashboard')->assertRedirect(route('verification.notice'));

    // ⚠️ The signed URL is generated directly — no mail transport needed, which is why THIS half is
    //    provable today. ⚠️ The DELIVERY half is not, and is not claimed.
    $link = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($link)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue('the signed link must actually verify the account');

    // allowed after
    $this->actingAs($user->fresh())->get('/app/dashboard')->assertOk();
});

// ── 5 · ONE USER'S LINK CANNOT VERIFY ANOTHER'S ACCOUNT ─────────────────────────────────────────────
it('⚠️ refuses a signed link whose hash belongs to a different account', function () {
    $s = seedScenario(1);
    $victim = gateUser($s, verified: false);
    $attacker = operatorUser($s, ['email_verified_at' => null], 'Front Desk')->fresh();

    $link = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $victim->getKey(), 'hash' => sha1($victim->getEmailForVerification()),
    ]);

    $this->actingAs($attacker)->get($link)->assertForbidden();

    expect($victim->fresh()->hasVerifiedEmail())->toBeFalse('the victim must NOT have been verified')
        ->and($attacker->fresh()->hasVerifiedEmail())->toBeFalse('nor the attacker');
});

// ── 6 · ⚠️⚠️ THE ONE THAT MATTERS MOST — the sixty-second-old stranger ───────────────────────────────
it('⚠️⚠️ a freshly signed-up, UNVERIFIED stranger can no longer reach the invite surface', function () {
    // ⚠️ Driven through the REAL public signup, not a factory — because the defect measured on 2026-09-23
    //    was that ONE POST produced a full working tenant whose owner reached /app/owners at 200.
    $email = 'stranger-'.Str::lower((string) Str::ulid()).'@example.net';

    $this->post('/signup', [
        'name' => 'A Stranger', 'email' => $email,
        'password' => 'Str0ng-Passw0rd!x', 'password_confirmation' => 'Str0ng-Passw0rd!x',
        'org_name' => 'Drive-By Hotels', 'property_name' => 'Drive-By Property',
        'property_type' => 'hotel', 'currency' => 'EUR', 'location' => 'Lisbon',
    ])->assertRedirect();

    $stranger = User::query()->where('email', $email)->firstOrFail();

    // ⚠️⚠️ THE ROWS ARE STILL CREATED — the founder has NOT ruled on that, and this test does not pretend
    //    otherwise. What changed is that they are INERT.
    expect($stranger->hasVerifiedEmail())->toBeFalse('a self-signup must land unverified');

    // ⚠️ THE INVITE SURFACE — reachable at 200 by a sixty-second-old stranger before this gate existed.
    $this->actingAs($stranger)->get('/app/owners')
        ->assertRedirect(route('verification.notice'));

    // and the rest of the back office with it
    foreach (['/app/dashboard', '/app/front-desk', '/app/reservations'] as $path) {
        expect($this->actingAs($stranger)->get($path)->baseResponse->getStatusCode())
            ->toBe(302, "an unverified stranger reached {$path}");
    }
});

// ── 7 · ⚠️⚠️ AN INVITED USER LANDS VERIFIED — the bug this gate uncovered ────────────────────────────
it('⚠️⚠️ an accepted invite really sets email_verified_at, so invited staff are not locked out', function () {
    Mail::fake();
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, ['email' => 'inviter@example.test']));
    $r = app(OwnerInviteService::class)
        ->invite('Invited Ivy', 'ivy@example.test', [$s['units'][0]->id], 18.0, 'owner');

    $svc = app(OwnerInviteService::class);
    $svc->accept($svc->findRedeemable($r['token']), 'Str0ng-Passw0rd!x');

    $ivy = User::query()->where('email', 'ivy@example.test')->firstOrFail();

    // ⚠️⚠️ THE BUG THIS PINS. 🔬 `accept()` passed `email_verified_at` to `update()`, the column is NOT in
    //    `User::$fillable`, and Eloquent DROPPED IT SILENTLY. The line claimed to "verify the email" and
    //    never did — harmless until `verified` went on `/app`, at which point EVERY invited user would have
    //    been locked out on first login. ⚠️ Nothing read the column for a `User`, so nothing noticed.
    expect($ivy->hasVerifiedEmail())
        ->toBeTrue('an accepted invite must verify the address — clicking a mailed link IS proof of ownership');

    // ⚠️ AND THE COLUMN IS STILL NOT MASS-ASSIGNABLE — the fix was to write it explicitly, not to widen
    //    `$fillable`. If someone "tidies" this by adding it there, this assertion says why not.
    expect((new User)->isFillable('email_verified_at'))
        ->toBeFalse('a verification timestamp must not be mass-assignable');
});

// ── 8 · ⚠️⚠️ THE FIRST LINK IS SENT (TONIGHT 01, 2026-09-25) ──────────────────────────────────────────────────
// The notice page says "Click the link we sent you". Until this, nothing had sent one — the only senders were the
// page's own "Resend the link" and the console's support button — invisible while production discarded mail.
function firstLinkSignup(object $test, string $email): void
{
    $test->post('/signup', [
        'name' => 'First Link', 'email' => $email,
        'password' => 'Str0ng-Passw0rd!x', 'password_confirmation' => 'Str0ng-Passw0rd!x',
        'org_name' => 'First Link Hotels', 'property_name' => 'First Link House',
        'property_type' => 'hotel', 'currency' => 'EUR', 'location' => 'Lisbon',
    ])->assertRedirect('/app/portfolio');
}

it('⚠️⚠️ an email sign-up is SENT its first verification link — exactly one, to the new founder', function () {
    Notification::fake();
    $email = 'first-link-'.Str::lower((string) Str::ulid()).'@example.net';

    firstLinkSignup($this, $email);

    $founder = User::query()->where('email', $email)->firstOrFail();
    expect($founder->hasVerifiedEmail())->toBeFalse('the gate still stands — the link is sent, the address is not assumed');
    Notification::assertSentToTimes($founder, VerifyEmail::class, 1);
});

it('⚠️ a mail server that refuses never fails the sign-up — the account exists and the failure is logged', function () {
    // A real SMTP attempt at a port nothing listens on: the transport throws, as a rejecting provider would.
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 2]);
    Log::spy();
    // ⚠️ a deliverable-looking domain on purpose: a reserved one is suppressed before any send is attempted
    $email = 'first-link-'.Str::lower((string) Str::ulid()).'@vyrobel.com';

    firstLinkSignup($this, $email);

    expect(User::query()->where('email', $email)->exists())->toBeTrue();
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context = []): bool => str_contains($message, 'first verification link')
            && ! str_contains(json_encode($context), $email))
        ->once();
});
