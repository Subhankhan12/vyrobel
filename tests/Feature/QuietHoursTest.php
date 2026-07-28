<?php

use App\Models\OwnerStatement;
use App\Models\User;
use App\Notifications\Owner\AccountSecurityNotification;
use App\Notifications\Owner\StatementReadyNotification;
use App\Support\Notifications\QuietHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// NOTIFICATION QUIET HOURS (Wave B · GATE 6) — during the window, routine EMAIL is held (the in-app feed still
// records everything); always-on security notifications ignore quiet hours. Mutation-proven at the channel gate.

afterEach(fn () => Carbon::setTestNow());

// ── 1. The window logic — same-day + overnight ────────────────────────────────────────────────────
it('computes the quiet window (same-day and overnight)', function () {
    $u = new User(['quiet_hours_start' => '22:00', 'quiet_hours_end' => '07:00']);   // overnight
    expect(QuietHours::active($u, Carbon::parse('2026-07-29 23:30')))->toBeTrue()    // inside (late)
        ->and(QuietHours::active($u, Carbon::parse('2026-07-29 06:30')))->toBeTrue() // inside (early)
        ->and(QuietHours::active($u, Carbon::parse('2026-07-29 12:00')))->toBeFalse(); // midday — outside

    $d = new User(['quiet_hours_start' => '09:00', 'quiet_hours_end' => '17:00']);   // same-day
    expect(QuietHours::active($d, Carbon::parse('2026-07-29 12:00')))->toBeTrue()
        ->and(QuietHours::active($d, Carbon::parse('2026-07-29 20:00')))->toBeFalse();

    expect(QuietHours::active(new User))->toBeFalse();   // not configured → never active
});

// ── 2. ⚠️ The channel gate — routine EMAIL held during quiet hours; in-app kept ───────────────────
it('holds routine email during quiet hours but keeps the in-app feed', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->forceFill(['quiet_hours_start' => '22:00', 'quiet_hours_end' => '07:00'])->save();

    $note = new StatementReadyNotification(new OwnerStatement);

    // Inside quiet hours → email dropped, in-app kept.
    Carbon::setTestNow(Carbon::parse('2026-07-29 23:30'));
    expect($note->via($user->fresh()))->toBe(['database']);

    // Outside quiet hours → both channels.
    Carbon::setTestNow(Carbon::parse('2026-07-29 12:00'));
    expect($note->via($user->fresh()))->toBe(['database', 'mail']);
});

// ── 3. Always-on security notifications ignore quiet hours ─────────────────────────────────────────
it('never holds an always-on security notification during quiet hours', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->forceFill(['quiet_hours_start' => '00:00', 'quiet_hours_end' => '23:59'])->save();   // almost all day

    Carbon::setTestNow(Carbon::parse('2026-07-29 12:00'));
    // The always-on account-security event still emails, quiet hours notwithstanding.
    expect(in_array('mail', (new AccountSecurityNotification('password_changed'))->via($user->fresh()), true))->toBeTrue();
});

// ── 4. The Settings form saves + clears the quiet window (self-only) ──────────────────────────────
it('saves and clears quiet hours from the notification preferences form', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $this->actingAs($user)->post(route('owner.notification-preferences.update'), [
        'prefs' => [], 'quiet_hours_enabled' => '1', 'quiet_start' => '22:30', 'quiet_end' => '06:30',
    ])->assertRedirect();
    expect($user->fresh()->quiet_hours_start)->toBe('22:30')->and($user->fresh()->quiet_hours_end)->toBe('06:30');

    // Unticking clears it.
    $this->actingAs($user)->post(route('owner.notification-preferences.update'), ['prefs' => []])->assertRedirect();
    expect($user->fresh()->quiet_hours_start)->toBeNull();
});
