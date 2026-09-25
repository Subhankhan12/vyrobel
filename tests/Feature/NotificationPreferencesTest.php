<?php

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\Operator\UrgentWorkOrderNotification;
use App\Services\MaintenanceService;
use App\Services\MaintenanceSettingsService;
use App\Services\NotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// NOTIFICATION PREFERENCES (Phase 3 · Sub-gate 2) — the per-user, per-event, per-channel toggles that GENUINELY
// gate delivery (never cosmetic). A typed store (constrained event_key + channel enum + bool), NOT the KV
// scaffold. Proves: prefs persist per user/event/channel; a POST is SELF-ONLY (writes only the AUTHED user's
// rows, never another's); an OFF toggle actually SUPPRESSES the notification through the real dispatch path
// (mutation-proven); the always-on "Account & security" group can NEVER be disabled; and the Phase-1 maintenance
// org-toggle (`notify_new_urgent`) now really gates the urgent-work-order notification.

// ── 1. Prefs persist per user/event/channel, and a POST is SELF-ONLY ──────────────────────────────
it('persists an operator’s per-event/channel prefs and never touches another user’s', function () {
    $s = seedScenario(1);
    $a = operatorUser($s, ['name' => 'Manager A']);
    $b = operatorUser($s, ['name' => 'Manager B']);

    // B has an existing stored preference we will prove A's POST cannot move.
    app(NotificationPreferences::class)->save($b, 'operator', ['new_booking' => ['in_app' => true, 'email' => true]]);

    // A submits the form: new_booking in-app ON, email OFF (absent = off). A real submit carries every checkbox.
    $this->actingAs($a)->post('/app/notification-preferences', [
        'prefs' => ['new_booking' => ['in_app' => '1']],
    ])->assertRedirect();

    // A's rows are stored, typed, and per-channel: in_app true, email false.
    $aIn = NotificationPreference::query()->where('user_id', $a->id)->where('event_key', 'new_booking')->where('channel', 'in_app')->firstOrFail();
    $aEmail = NotificationPreference::query()->where('user_id', $a->id)->where('event_key', 'new_booking')->where('channel', 'email')->firstOrFail();
    expect((bool) $aIn->enabled)->toBeTrue();
    expect((bool) $aEmail->enabled)->toBeFalse();

    // B's identical-key row is UNTOUCHED — the write keyed on the AUTHED user, never a request id.
    $bIn = NotificationPreference::query()->where('user_id', $b->id)->where('event_key', 'new_booking')->where('channel', 'in_app')->firstOrFail();
    expect((bool) $bIn->enabled)->toBeTrue();
});

// ── 2. An OFF toggle GENUINELY suppresses delivery (mutation-proven via the real dispatch path) ────
it('suppresses a notification when its in-app toggle is off, and delivers it when on', function () {
    $s = seedScenario(2);   // two units so the two bookings below don't collide on availability
    $operator = operatorUser($s, ['name' => 'Gate Keeper']);

    // Turn new_booking in-app OFF, then fire the REAL event.
    app(NotificationPreferences::class)->save($operator, 'operator', ['new_booking' => ['in_app' => false, 'email' => false]]);
    book($s, ['unit_id' => $s['units'][0]->id]);   // fires reservation.confirmed → dispatcher → via() consults prefs

    expect($operator->fresh()->notifications()->count())->toBe(0);   // suppressed — the gate held

    // Turn it back ON and fire again (a second unit) — now it lands.
    app(NotificationPreferences::class)->save($operator, 'operator', ['new_booking' => ['in_app' => true, 'email' => false]]);
    book($s, ['unit_id' => $s['units'][1]->id]);

    expect($operator->fresh()->notifications()->count())->toBe(1);   // delivered — only the second booking
});

// ── 3. Always-on (Account & security) can NEVER be disabled ───────────────────────────────────────
it('keeps the always-on account & security preference enabled even when submitted off', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);

    // The owner tries to switch Account & security OFF (both channels absent) through the real route.
    $this->actingAs($owner)->post('/owner/notification-preferences', [
        'prefs' => ['statement_ready' => ['in_app' => '1']],   // an ordinary toggle they DID leave on
    ])->assertRedirect();

    // account_security is forced enabled on every channel — save() overrides the submitted off.
    foreach (NotificationPreferences::CHANNELS as $channel) {
        $pref = NotificationPreference::query()->where('user_id', $owner->id)->where('event_key', 'account_security')->where('channel', $channel)->firstOrFail();
        expect((bool) $pref->enabled)->toBeTrue();
    }

    // And the GATE agrees regardless of any stored value — it short-circuits to allowed.
    expect(app(NotificationPreferences::class)->allows($owner, 'account_security', 'in_app'))->toBeTrue();
});

// ── 4. The Phase-1 maintenance ORG toggle (notify_new_urgent) now really gates ────────────────────
it('gates the urgent-work-order notification on the org maintenance toggle', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops Lead']);

    // Org opts OUT of urgent-WO notifications, then an urgent ticket opens (fires maintenance_ticket.opened).
    $settings = app(MaintenanceSettingsService::class)->forOrg();
    $settings->update(['notify_new_urgent' => false]);
    app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Boiler down', 'priority' => 'urgent']);

    expect($operator->fresh()->notifications()->count())->toBe(0);   // org toggle off → no notification

    // Org opts back IN — the next urgent ticket notifies.
    $settings->update(['notify_new_urgent' => true]);
    app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Flood in 204', 'priority' => 'urgent']);

    expect($operator->fresh()->notifications()->where('type', UrgentWorkOrderNotification::class)->count())->toBe(1);
});

// ── 5. A non-urgent ticket never fires the urgent-WO notification (the priority guard) ─────────────
it('does not notify for a non-urgent maintenance ticket', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops Lead']);

    app(MaintenanceSettingsService::class)->forOrg();   // ensure the org row exists (defaults notify_new_urgent = true)
    app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Squeaky door', 'priority' => 'normal']);

    expect($operator->fresh()->notifications()->count())->toBe(0);
});

// ── 6. GATE 11 — the Email channel's "Verified" badge reflects the REAL email_verified_at (never hardcoded #39) ──
it('shows a real email-verification badge, not a hardcoded one', function () {
    $s = seedScenario(1);

    // A verified operator (factory default email_verified_at = now()) → "Verified".
    $verified = operatorUser($s, ['name' => 'Verified Vera']);
    $this->actingAs($verified)->get('/app/notification-preferences')
        ->assertOk()->assertSee('Verified')->assertDontSee('Unverified');

    // ⚠️⚠️ THE "UNVERIFIED" BRANCH IS NOW UNREACHABLE IN THE PRODUCT — 2026-09-23 (GATE 01), and this is a
    //    CONSEQUENCE OF THE RULING, not a defect. `/app` is behind `verified`, so an operator whose email is
    //    not verified never reaches this page at all: the badge can only ever render "Verified".
    // ⚠️ The badge is NOT removed. It is real, it is honest, and it becomes meaningful again the moment any
    //    surface outside the gate renders it (the owner portal is ungated, for instance). Deleting a correct
    //    feature because a gate currently hides it is how a product loses things quietly.
    // ⚠️ UPDATE 2026-09-25 (FINISH 01 · Part C, the founder's ruling "remove it, or keep it with a comment if something
    //    still reaches it"): nothing reaches it — the owner portal renders its OWN view, not this one — so the
    //    "Unverified" ELSE-branch was removed. The "Verified" badge stays CONDITIONAL on the real column, so the page
    //    still can never claim an address is verified when it is not; only the unreachable alternative copy is gone.
    // ⚠️ What is asserted instead is the new truth: the unverified operator is TURNED AWAY from the page.
    $unverified = operatorUser($s, ['name' => 'Unverified Uma', 'email_verified_at' => null]);
    $this->actingAs($unverified)->get('/app/notification-preferences')
        ->assertRedirect(route('verification.notice'));
});
