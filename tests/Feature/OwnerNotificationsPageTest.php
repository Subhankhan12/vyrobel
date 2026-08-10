<?php

use App\Models\NotificationPreference;
use App\Services\NotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER NOTIFICATIONS — exact-match rebuild (#79). The page is the REAL NotificationPreferences engine restyled:
// the EMAIL column is real (stored + honored by allows()); the SMS column is HONEST-GATED (display-only, never a
// send, never stored — no provider); IN-APP is dropped from the UI but PRESERVED (the feed is never turned off);
// the urgent-bypass guarantee is REAL (account_security is always_on). Self-only controller POST (#20).

it('renders the exact sections with the owner’s real email and an honestly-gated SMS column', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s);
    $user->update(['email' => 'olive@seasidevillas.test']);

    $response = $this->actingAs($user)->get('/owner/notification-preferences')->assertOk();

    $response
        ->assertSee('Where we reach you')
        ->assertSee('olive@seasidevillas.test')          // the owner's OWN email (the notification target)
        ->assertSee('What you hear about')
        ->assertSee('Statement ready')                   // the 5 real owner events…
        ->assertSee('Payout sent')
        ->assertSee('Performance digest')
        ->assertSee('Terms changed')
        ->assertSee('Account & security')
        ->assertSee('Always on')                         // account_security locked
        // The urgent-bypass guarantee (TRUE — account_security is always_on)…
        ->assertSee('urgent account or payout alerts always come through')
        // …and the SMS column is HONESTLY GATED — never a live-looking toggle:
        ->assertSee('not set up')
        ->assertSee('once it');                          // "…we’ll use these preferences once it’s set up."

    // The SMS column NEVER submits — there is no sms input in the form (no fabricated send path, #39).
    $response->assertDontSee('prefs[statement_ready][sms]', false)
        ->assertDontSee('prefs[payout_sent][sms]', false);

    // ⚠️ …and the deferred state is announced in TEXT, per event, not by the greyed cell alone.
    // An aria-label on a non-interactive <span> is not reliably exposed — the same P29 finding as the
    // unread dot — so each SMS cell carries real visually-hidden text, matching the operator prefs page.
    // Asserted as the STATE (the announcement itself), never as a class name.
    $response->assertSee('Statement ready — SMS not set up')
        ->assertSee('Payout sent — SMS not set up');

    assertNoRawBlade($response);
});

it('honors a per-event EMAIL toggle through the page POST — and preserves in-app', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s);
    $prefs = app(NotificationPreferences::class);

    // statement_ready email defaults ON. Save the page's shape with email OFF (absent) but in_app preserved.
    $this->actingAs($user)->post('/owner/notification-preferences', [
        'prefs' => [
            'statement_ready' => ['in_app' => '1'],   // email absent → off; in_app preserved (hidden input)
            'payout_sent' => ['in_app' => '1', 'email' => '1'],
        ],
    ])->assertRedirect(route('owner.notification-preferences'))->assertSessionHas('prefs_notice');

    // The EMAIL pref genuinely changed (the gate reads it — an email-off really stops that email)…
    expect($prefs->allows($user->fresh(), 'statement_ready', 'email'))->toBeFalse()
        ->and($prefs->allows($user->fresh(), 'payout_sent', 'email'))->toBeTrue()
        // …and IN-APP is preserved (the feed is never silently turned off).
        ->and($prefs->allows($user->fresh(), 'statement_ready', 'in_app'))->toBeTrue();

    // No SMS row is EVER created (the column is display-only, not stored).
    expect(NotificationPreference::query()->where('user_id', $user->id)->where('channel', 'sms')->count())->toBe(0);
});

it('keeps the urgent-bypass real — account & security email cannot be turned off through the page', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s);
    $prefs = app(NotificationPreferences::class);

    // Try to suppress account_security (omit it entirely) through the real route.
    $this->actingAs($user)->post('/owner/notification-preferences', [
        'prefs' => ['statement_ready' => ['in_app' => '1', 'email' => '1']],
    ])->assertRedirect();

    // always_on → allows() is true on every channel regardless of what was (not) submitted.
    expect($prefs->allows($user->fresh(), 'account_security', 'email'))->toBeTrue()
        ->and($prefs->allows($user->fresh(), 'account_security', 'in_app'))->toBeTrue();
});
