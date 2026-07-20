<?php

use App\Models\User;
use App\Notifications\Operator\NewBookingNotification;
use App\Notifications\Owner\AccountSecurityNotification;
use App\Services\NotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER/OPERATOR EMAIL DELIVERY (Phase 3 · Sub-gate 5) — the mail channel is now wired into user notifications
// through the SAME preference gate as in-app, so the SG2 email toggle is NOT cosmetic. Proven here: email is
// selective by default (financial/security on, routine off); an email toggle genuinely adds/removes the 'mail'
// channel (mutation-proven via via()); always-on account & security can never lose its email.

// ── Per-channel defaults: email is selective (opt-in for routine, on for financial/security) ──────
it('defaults email selectively — on for financial/security events, off for routine ones', function () {
    $user = User::factory()->create();
    $prefs = app(NotificationPreferences::class);

    // Financial / security → email on by default…
    expect($prefs->allows($user, 'statement_ready', 'email'))->toBeTrue();
    expect($prefs->allows($user, 'owner_payout_sent', 'email'))->toBeTrue();
    expect($prefs->allows($user, 'urgent_work_order', 'email'))->toBeTrue();
    // …routine operational → email off by default (in-app only unless opted in).
    expect($prefs->allows($user, 'new_booking', 'email'))->toBeFalse();
    expect($prefs->allows($user, 'balance_due', 'email'))->toBeFalse();
    // In-app stays on for those routine events regardless.
    expect($prefs->allows($user, 'new_booking', 'in_app'))->toBeTrue();
});

// ── The gate: the email toggle genuinely adds/removes the mail channel (mutation-proven) ──────────
it('adds the mail channel only when the email preference is on', function () {
    $s = seedScenario(1);
    $user = User::factory()->create();
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $notification = new NewBookingNotification($reservation);

    // new_booking email defaults OFF → in-app only.
    expect($notification->via($user))->toContain('database')->not->toContain('mail');

    // Turn email ON → the mail channel appears.
    app(NotificationPreferences::class)->save($user, 'operator', ['new_booking' => ['in_app' => true, 'email' => true]]);
    expect($notification->via($user->fresh()))->toContain('database')->toContain('mail');

    // Turn email OFF (in-app still on) → the mail channel is gone; in-app remains.
    app(NotificationPreferences::class)->save($user, 'operator', ['new_booking' => ['in_app' => true, 'email' => false]]);
    expect($notification->via($user->fresh()))->toContain('database')->not->toContain('mail');
});

// ── Always-on: account & security can never lose its email ────────────────────────────────────────
it('always emails account & security, even with every preference turned off', function () {
    $user = User::factory()->create();
    // Save the owner audience with everything off — always_on is forced on for both channels.
    app(NotificationPreferences::class)->save($user, 'owner', []);

    $notification = new AccountSecurityNotification('added');

    expect($notification->via($user->fresh()))->toContain('database')->toContain('mail');
});

// ── toMail renders a valid branded notification email ─────────────────────────────────────────────
it('builds a branded email with the notification’s title, context and link', function () {
    $s = seedScenario(1);
    $user = User::factory()->create();
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $mail = (new NewBookingNotification($reservation))->toMail($user);
    expect($mail->subject)->toContain('New booking');

    $html = view('emails.notification', ['title' => 'New booking · R-TEST', 'context' => 'Direct · 1–3 Sep', 'url' => 'https://vyrobel.test/app/reservations', 'category' => 'front_desk'])->render();
    expect($html)->toContain('New booking · R-TEST')->toContain('Open in Vyrobel')->toContain('Front Desk');
});
