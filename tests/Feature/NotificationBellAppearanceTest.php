<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

/** One unread notification, so the badge actually renders in the guard above. */
if (! function_exists('makeBellNotif')) {
    function makeBellNotif(object $notifiable): void
    {
        $notifiable->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test\\Fake',
            'data' => ['title' => 'T', 'context' => 'c', 'url' => '/app', 'category' => 'front_desk'],
            'read_at' => null,
        ]);
    }
}

// NOTIFICATION BELL — APPEARANCE GUARD (2026-08-20).
//
// ⚠️ WHY THIS FILE EXISTS. The bell shipped VISUALLY BROKEN in the operator realm while the owner realm
// was correct, and the whole suite stayed green over it — nothing asserted on the bell's appearance at
// all. The mechanism: <x-notifications.bell> set `background:none;border:none;padding:0` INLINE, and an
// inline style beats a stylesheet. The owner layout had escaped by adding `!important`; the operator
// layout had not, so its 38px frosted circle was erased and only the drop shadow rendered — a shadow
// hanging in mid-air next to three crisp glass pills.
//
// The fix removed the fight rather than duplicating the escape: the component no longer sets appearance
// inline, so each shell's `.vy-bell` rule is the SINGLE owner of it (§N.8f — one definition per concept),
// and owner's `!important` was deleted as no longer load-bearing.
//
// The reference is the prototype, read from the decoded `Owner Dashboard` bundle: a 42px frosted circle
// (`rgba(255,252,248,.58)` + `1px solid rgba(255,255,255,.75)` + `blur(22px) saturate(1.15)`), an 18px /
// 1.8-stroke bell glyph, and the unread badge INSIDE the circle with a `140deg #E8703A→#C2410C` gradient
// and a 1.5px `#FBEEE3` ring. The operator prototype imports the same bell at `hint-size 38px,38px`.
//
// ⚠️ THE BADGE LEFT THE CIRCLE ON 2026-10-09, AT HIS WORD ("the notification icon is completely messed because there is
// no proper spacing … make it proper"). 🔬 Inside the circle, a 19px "9+" badge sat on the 18px glyph and hid it. It now
// sits on the circle's OUTER top-right corner — the gradient kept, a 2px light ring, 10px bold figures — and the top bar's
// items are 14px apart so it never crowds the account chip. Guard 4 pinned the old placement; it now pins the new one.
//
// ⚠️ MIXED, DELIBERATELY. Guards 1–3 are SOURCE assertions (a computed style needs a browser this suite does
// not have). Guards 4–5 assert the RENDERED page instead — they were source assertions too until extracting
// the glyph into <x-notifications.bell-glyph> broke them while the output stayed byte-identical. A guard that
// names a FILE tracks a location; one that reads the render tracks the thing. Full computed-style
// verification still happens in the browser (both realms, all seven demo roles).
//
// ⚠️ Assertions use PHPUnit's Assert (not Pest's `toContain`) deliberately: `toContain()` accepts MULTIPLE
// needles, so passing a failure message as a second argument silently asserts the message itself — a
// vacuous test that reads like a strict one.

$bell = fn () => file_get_contents(resource_path('views/components/notifications/bell.blade.php'));
$layout = fn (string $realm) => file_get_contents(resource_path("views/components/layouts/{$realm}.blade.php"));

// ── 1. The component must not fight the cascade ─────────────────────────────────────────────────
it('does not set the bell button appearance inline, so the shell stylesheet owns it', function () use ($bell) {
    // Isolate the <button> tag itself — the panel below it legitimately uses inline styles.
    preg_match('/<button\b[^>]*class="vy-bell"[^>]*>/', $bell(), $m);
    Assert::assertNotEmpty($m, 'the bell button tag was not found');

    foreach (['background:none', 'border:none', 'padding:0', 'display:inline-flex'] as $killer) {
        Assert::assertStringNotContainsString($killer, $m[0],
            "the bell button sets `{$killer}` inline; an inline style beats the shell's .vy-bell rule and erases the frosted circle");
    }
});

// ── 2. Both shells must define the glass recipe ─────────────────────────────────────────────────
it('defines the frosted-glass bell in BOTH shells, at each prototype\'s own diameter', function () use ($layout) {
    $expected = [
        'operator' => '38px',   // Operator prototype: <dc-import name="Notif Bell" hint-size="38px,38px">
        'owner' => '42px',      // Owner prototype: inline 42px bell
    ];

    foreach ($expected as $realm => $diameter) {
        preg_match('/\.vy-bell\s*\{[^}]*\}/', $layout($realm), $m);
        Assert::assertNotEmpty($m, "{$realm} shell has no .vy-bell rule");
        $rule = $m[0];

        Assert::assertStringContainsString("width: {$diameter}", $rule, "{$realm} bell is not {$diameter}");
        Assert::assertStringContainsString('rgba(255, 252, 248, 0.58)', $rule, "{$realm} bell lost the prototype's glass fill");
        Assert::assertStringContainsString('border-radius: 50%', $rule, "{$realm} bell is not circular");
        Assert::assertStringContainsString('blur(22px) saturate(1.15)', $rule, "{$realm} bell lost the prototype's backdrop blur");
    }
});

// ── 3. Owner must not need !important — that was the symptom, not the cure ──────────────────────
it('no longer needs !important on the owner bell, because nothing fights it any more', function () use ($layout) {
    preg_match('/\.vy-topbar \.vy-bell\s*\{[^}]*\}/', $layout('owner'), $m);
    Assert::assertNotEmpty($m, 'owner .vy-topbar .vy-bell rule not found');

    Assert::assertStringNotContainsString('!important', $m[0],
        'the owner bell is overriding an inline style again; that means the component started setting appearance inline');
});

// ── 4. The unread badge sits on the circle's outer corner, clear of the bell ───────────────────
it('renders the unread badge on the circle’s outer corner — never over the bell (his report, 2026-10-09)', function () {
    // ⚠️ ASSERTS THE RENDER, NOT A FILE: it follows the glyph wherever its definition moves (see the history above).
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');
    makeBellNotif($user);

    $html = test()->actingAs($user)->get('/app/dashboard')->assertOk()->getContent();
    preg_match('/<span data-vy-bell-badge[^>]*>/', $html, $m);
    Assert::assertNotEmpty($m, 'the unread badge did not render');
    $badge = $m[0];

    Assert::assertStringContainsString('linear-gradient(140deg,#E8703A,#C2410C)', $badge, 'badge lost the prototype gradient fill');
    Assert::assertStringContainsString('border:2px solid #FFF8F2', $badge, 'badge lost the light ring that lifts it off the circle');
    Assert::assertStringContainsString('font-size:10px;font-weight:700', $badge, 'badge figures drifted from 10px bold');
    Assert::assertStringContainsString('min-width:19px;height:19px', $badge, 'badge size drifted');

    // ⚠️ THE FIX ITSELF: negative offsets put it on the outer corner. Inside the circle (the old 9px / 10px) a "9+" covered the glyph.
    Assert::assertStringContainsString('top:var(--vy-bell-badge-top,-5px);right:var(--vy-bell-badge-right,-6px)', $badge,
        'the badge moved back inside the circle, where a two-character count covers the bell');
    // It must never take the click meant for the bell button beneath it.
    Assert::assertStringContainsString('pointer-events:none', $badge, 'the badge can swallow a click on the bell');
});

it('lets no shell move the badge back — the corner fits every bell size, so none tunes it', function () {
    // The operator (38px) and guest (36px) shells used to set `--vy-bell-badge-*` for an INSIDE position. On the corner one
    // offset fits 36, 38 and 42px, and a shell override is exactly how the badge would creep back over the glyph.
    foreach (['components/layouts/operator.blade.php', 'components/layouts/owner.blade.php', 'components/guest/shell.blade.php', 'components/admin/shell.blade.php'] as $view) {
        $css = (string) file_get_contents(resource_path('views/'.$view));
        Assert::assertDoesNotMatchRegularExpression('/--vy-bell-badge-(top|right)\s*:/', $css, "{$view} sets the badge offsets again");
    }
});

it('spaces the top bar so the corner badge never crowds the next control', function () use ($layout) {
    foreach (['operator', 'owner'] as $realm) {
        preg_match('/\.vy-topbar-actions\s*\{[^}]*\}/', $layout($realm), $m);
        Assert::assertNotEmpty($m, "{$realm} shell has no .vy-topbar-actions rule");
        // The badge reaches 6px past the circle; at 14px the account chip keeps ~8px of air (🖥 9px measured in both shells).
        Assert::assertStringContainsString('gap: 14px', $m[0], "{$realm} top bar is cramped again");
    }
});

// ── 5. The glyph is the prototype's bell, not the old one ───────────────────────────────────────
it('draws the prototype bell glyph at 18px / 1.8 stroke', function () {
    // Rendered, for the same reason as the badge guard above.
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');

    $html = test()->actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

    Assert::assertStringContainsString('M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9z', $html, 'the prototype bell path is gone');
    Assert::assertStringContainsString('stroke-width="1.8"', $html, 'glyph stroke weight drifted from the prototype');
});
