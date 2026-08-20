<?php

use App\Jobs\SendBookingConfirmationEmail;
use App\Models\Guest;
use App\Notifications\Guest\GuestBookingConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// GUEST NOTIFICATIONS · GATE 3 — the FEED SCREEN + the bell in the guest shell.
//
// ⚠️ No guest notifications wireframe exists, so the screen MIRRORS the owner/operator feed and the real
// confirmation was a RENDER, not a wireframe diff. These tests pin the parts a render cannot re-run: that the
// screen is self-scoped, that the bell is in LINK mode here (this shell loads no Alpine, so a popover would be
// a dead control), and that the operator/owner shells keep the popover.

/** The scenario guest, claimable so it can authenticate on the guest guard. */
function g3Guest(array $s, string $email = 'ada@example.test'): Guest
{
    $s['guest']->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $g = $s['guest']->fresh();
    $g->password = 'guest-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

// ── The screen renders, for the authenticated guest ────────────────────────────────────────────────
it('renders the guest notification feed with the guest’s own rows', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g3Guest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $this->actingAs($guest, 'guest')->get('/guest/notifications')
        ->assertOk()
        ->assertSee('Notifications')
        ->assertSee((string) $reservation->reference);   // the row's real title carries the booking reference
});

// ── The honest empty state — the seeded reality (the demo persists zero guest rows) ─────────────────
it('shows an honest empty state when the guest has no notifications', function () {
    $s = seedScenario(1);
    $guest = g3Guest($s);

    $this->actingAs($guest, 'guest')->get('/guest/notifications')
        ->assertOk()
        ->assertSee('All caught up')
        ->assertSee('no notifications yet');
});

// ── #28 — the screen shows ONLY the authenticated guest's own ──────────────────────────────────────
it('never renders another guest’s notification on the feed screen', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $mine = g3Guest($s, 'ada@example.test');
    $theirs = Guest::create(['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'other@example.test']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ BOTH guests hold a row, and the rows are DISTINGUISHABLE. An earlier version emptied mine and
    // asserted the page was empty — which a WRONG-ACTOR resolution could still satisfy purely by row order.
    // Giving each guest a row the other does not have means only the correctly-resolved actor can pass.
    $theirs->notify(new GuestBookingConfirmedNotification($reservation));
    $mine->notifications()->delete();
    $mine->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => GuestBookingConfirmedNotification::class,
        'data' => ['title' => 'MINE-ONLY-MARKER', 'context' => 'c', 'url' => '/guest/trips', 'category' => 'trips'],
        'read_at' => null,
    ]);
    $theirsRow = $theirs->fresh()->notifications()->first();
    Assert::assertNotNull($theirsRow, 'the other guest must really hold a row, or this proves nothing');
    $theirsRow->forceFill(['data' => ['title' => 'THEIRS-ONLY-MARKER', 'context' => 'c', 'url' => '/guest/trips', 'category' => 'trips']])->save();

    $this->actingAs($mine, 'guest')->get('/guest/notifications')
        ->assertOk()
        ->assertSee('MINE-ONLY-MARKER')            // the authenticated guest's own row IS shown...
        ->assertDontSee('THEIRS-ONLY-MARKER');     // ...and the other guest's never is
});

// ── The screen requires the guest guard ────────────────────────────────────────────────────────────
it('refuses the guest feed screen to an unauthenticated visitor', function () {
    $this->get('/guest/notifications')->assertRedirect();
});

// ── ⚠️ THE BELL IS IN LINK MODE HERE — this shell loads no Alpine ──────────────────────────────────
it('renders the guest bell as a LINK, not a dead Alpine popover', function () {
    $s = seedScenario(1);
    $guest = g3Guest($s);

    $html = $this->actingAs($guest, 'guest')->get('/guest/trips')->assertOk()->getContent();

    // ⚠️ THE POINT. The guest shell pulls in NO Alpine bundle, so the popover the other two shells use would
    // render a control that looks right and does nothing. The bell must be an anchor to the feed.
    Assert::assertStringContainsString('class="vy-bell"', $html, 'the guest shell has no bell at all');
    Assert::assertMatchesRegularExpression(
        '~<a[^>]+href="[^"]*/guest/notifications"[^>]+class="vy-bell"~',
        $html,
        'the guest bell is not a link to the feed — in a shell with no Alpine that means a dead control'
    );
    Assert::assertStringNotContainsString('x-data="{ open: false }"', $html,
        'the guest shell rendered the Alpine popover bell; this realm loads no Alpine, so it would never open');

    // ...and the shell really does load no Alpine, which is WHY link mode is required (not a style choice).
    Assert::assertStringNotContainsString('livewireScripts', $html);
    Assert::assertStringNotContainsString('alpine', strtolower($html));
});

// ── The other two realms keep the popover — the shared component must not have flipped ─────────────
it('keeps the operator and owner bells in popover mode', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');

    $html = $this->actingAs($operator)->get('/app/dashboard')->assertOk()->getContent();

    Assert::assertStringContainsString('x-data="{ open: false }"', $html,
        'the operator bell lost its popover — the shared component defaulted to link mode');
    Assert::assertDoesNotMatchRegularExpression(
        '~<a[^>]+href="[^"]*notifications"[^>]+class="vy-bell"~', $html,
        'the operator bell became a link; link mode must stay opt-in'
    );
});

// ── `.vy-sr-only` must actually hide — an undefined one renders the label as stray visible text ────
it('defines vy-sr-only in the guest shell so screen-reader text is not visible', function () {
    $s = seedScenario(1);
    $guest = g3Guest($s);

    $html = $this->actingAs($guest, 'guest')->get('/guest/notifications')->assertOk()->getContent();

    // ⚠️ THE DEFINITION, NOT THE USAGE. An earlier version asserted the bare string '.vy-sr-only' — which is
    // present in every `class="vy-sr-only"` attribute in the MARKUP, so it stayed GREEN when the CSS RULE was
    // renamed away. It read the label, not the protection. This matches the rule and its clipping declaration.
    Assert::assertMatchesRegularExpression(
        '~\.vy-sr-only\s*\{[^}]*clip\s*:\s*rect\(~',
        $html,
        'the guest shell does not DEFINE a clipping .vy-sr-only rule — an undefined visually-hidden class hides '
        .'nothing and renders every screen-reader label as stray visible text'
    );
});
