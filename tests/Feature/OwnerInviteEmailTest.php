<?php

use App\Mail\OwnerInviteMail;
use App\Models\Guest;
use App\Services\OwnerInviteService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Auth Phase B · Step 3 — the real invite email. The Mailable is dispatched to the invitee carrying the
// single-use tokened /invite/{token} link, and renders EMAIL-SAFE (table layout, inline CSS, no
// backdrop-filter / flexbox). Nothing is faked as "sent" — we assert the captured Mailable.

// ── The invite dispatches the Mailable to the right recipient with the tokened link ──────────────
it('sends the owner invite email with the single-use tokened set-password link', function () {
    Mail::fake();
    seedScenario(1);

    $r = app(OwnerInviteService::class)->invite('Priya Anand', 'priya@example.test', [], 15.0, 'owner');
    $acceptUrl = url('/invite/'.$r['token']);

    Mail::assertSent(OwnerInviteMail::class, function (OwnerInviteMail $mail) use ($acceptUrl, $r) {
        $html = $mail->render();

        return $mail->hasTo('priya@example.test')
            && $mail->token === $r['token']                       // the single-use token from the invite
            && str_contains($html, $acceptUrl)                    // the tokened link is in the email
            && str_contains($html, 'Set your password');          // the CTA
    });
});

// ── The rendered email is EMAIL-SAFE (no app-glass/flex; table + inline CSS + fallbacks) ─────────
it('renders email-safe HTML (table layout, no backdrop-filter or flexbox)', function () {
    Mail::fake();
    seedScenario(1);
    $r = app(OwnerInviteService::class)->invite('Priya Anand', 'safe@example.test', [], null, 'owner');

    $html = (new OwnerInviteMail($r['invite'], $r['token'], 'Priya Anand'))->render();

    // Email-safe: adapted away from the app's frosted glass / flex to tables + solid warm colours.
    expect($html)
        ->toContain('<table')                       // table-based layout
        ->toContain('#C2410C')                       // terracotta brand button
        ->not->toContain('backdrop-filter')          // not supported by email clients
        ->not->toContain('display:flex')
        ->not->toContain('display: flex');

    // Web-safe font fallback + the honest single-use / expiry copy + the plaintext-link fallback.
    expect($html)
        ->toContain('Arial')                         // fallback beyond the web font
        ->toContain('single-use')
        ->toContain(url('/invite/'.$r['token']));    // plaintext link fallback present
});

// ── The design-parity disciplines an email must hold that a PAGE does not ────────────────────────
// Email clients strip custom properties and run no JS, so the warm-glass tokens cannot be referenced;
// the template carries LITERAL hex that must equal the token VALUE. This asserts the two halves that
// silently rot: no `--vy-*` survives into the output, and the pre-remediation greys the prototype uses
// (#8A8079 / #9C9089 / #A69A92 / #B7ADA4 — all below 4.5 on this email's plates) never ship.
it('carries no CSS custom properties and none of the pre-remediation greys', function () {
    Mail::fake();
    seedScenario(1);
    $r = app(OwnerInviteService::class)->invite('Priya Anand', 'tokens@example.test', [], null, 'owner');

    $html = (new OwnerInviteMail($r['invite'], $r['token'], 'Priya Anand'))->render();

    expect($html)
        ->not->toContain('--vy-')                    // stripped by Outlook — would render as no colour
        ->not->toContain('#8A8079')                  // 3.86 on the card — the prototype's muted grey
        ->not->toContain('#9C9089')
        ->not->toContain('#A69A92')                  // 2.74 on the wash — the footer/legal line
        ->not->toContain('#B7ADA4')
        ->toContain('#5F5853')                       // --vy-muted, the value that replaced them
        ->toContain('#6C645F');                      // --vy-faint

    // The brand literals must still equal the live token values, or the email drifts from the app.
    expect($html)
        ->toContain('#C2410C')                       // --vy-accent  = rgb(194, 65, 12)
        ->toContain('#E8703A')                       // --vy-accent-3 = rgb(232, 112, 58)
        ->toContain('#B03808')                       // --vy-accent-text = rgb(176, 56, 8)
        ->toContain('#2B2320');                      // --vy-ink = rgb(43, 35, 32)
});

// ── #28 — the invite carries ONLY what it must, and nothing about anyone else ────────────────────
// The invite lands in a PROSPECTIVE owner's inbox. It may name the organization, the recipient and the
// link — never another owner in that org, and never a guest. The org here is deliberately populated with
// both, so an accidental widening (an eager-load, a "recent activity" block, an owner roster) fails here.
it('leaks no other owner and no guest data into the rendered invite', function () {
    Mail::fake();
    $s = seedScenario(2);

    // An EXISTING owner in the same org — the invitee must never learn they exist.
    $existing = ownUnit($s['units'][0]);
    $existing->update(['legal_name' => 'Ingrid Halvorsen', 'billing_company' => 'Halvorsen Eiendom AS']);

    // A real guest of that org, with the encrypted contact PII.
    $guest = Guest::create([
        'first_name' => 'Tomasz', 'last_name' => 'Wieczorek',
        'email' => 'tomasz.wieczorek@example.test', 'phone' => '+48511222333',
    ]);

    $r = app(OwnerInviteService::class)->invite('Priya Anand', 'newowner@example.test', [], null, 'owner');
    $html = (new OwnerInviteMail($r['invite'], $r['token'], 'Priya Anand'))->render();

    foreach (['Ingrid Halvorsen', 'Halvorsen Eiendom AS', $existing->id,
        'Tomasz', 'Wieczorek', 'tomasz.wieczorek@example.test', '+48511222333', $guest->id] as $secret) {
        expect($html)->not->toContain($secret);
    }

    // Exactly two destinations: the tokened invite link and the real public sign-in route. Nothing else.
    preg_match_all('/href="([^"]*)"/', $html, $m);
    expect(array_values(array_unique($m[1])))->toEqualCanonicalizing([url('/invite/'.$r['token']), url('/login')]);
});

// ── #39 / #42 — the org and the link are BOUND to this invite, not hardcoded copy ────────────────
// A hardcoded demo string shipped to a real recipient is the fabrication trap this whole realm guards
// against, and a token that does not vary per invite is the isolation one. Rendering two invites in two
// different organizations proves both: each email names its OWN org and carries its OWN token.
it('binds the organization name and the token per invite, never a fixed string', function () {
    Mail::fake();

    $a = seedScenario(1);
    $a['org']->update(['name' => 'Aurora Coastal Retreats']);
    $inviteA = app(OwnerInviteService::class)->invite('Priya Anand', 'a@example.test', [], null, 'owner');
    $htmlA = (new OwnerInviteMail($inviteA['invite'], $inviteA['token'], 'Priya Anand'))->render();

    $b = seedScenario(1);
    $b['org']->update(['name' => 'Blackthorn Estates']);
    app(TenantContext::class)->set($b['org']->id);
    $inviteB = app(OwnerInviteService::class)->invite('Dario Costa', 'b@example.test', [], null, 'owner');
    $htmlB = (new OwnerInviteMail($inviteB['invite'], $inviteB['token'], 'Dario Costa'))->render();

    expect($htmlA)->toContain('Aurora Coastal Retreats')->not->toContain('Blackthorn Estates');
    expect($htmlB)->toContain('Blackthorn Estates')->not->toContain('Aurora Coastal Retreats');

    // Each carries its own single-use token — never the other org's.
    expect($htmlA)->toContain($inviteA['token'])->not->toContain($inviteB['token']);
    expect($htmlB)->toContain($inviteB['token'])->not->toContain($inviteA['token']);
});
