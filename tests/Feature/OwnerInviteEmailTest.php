<?php

use App\Mail\OwnerInviteMail;
use App\Services\OwnerInviteService;
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
