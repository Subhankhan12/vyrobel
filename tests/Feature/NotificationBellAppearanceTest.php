<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

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
// These are SOURCE assertions, not render assertions: a computed style needs a browser and this suite has
// none. The render was verified separately in the browser (both realms, all seven demo roles).
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

// ── 4. The unread badge matches the prototype ───────────────────────────────────────────────────
it('renders the unread badge inside the circle with the prototype gradient and ring', function () use ($bell) {
    $src = $bell();

    Assert::assertStringContainsString('linear-gradient(140deg,#E8703A,#C2410C)', $src, 'badge lost the prototype gradient fill');
    Assert::assertStringContainsString('1.5px solid #FBEEE3', $src, 'badge lost the prototype ring that separates it from the glyph');
    Assert::assertStringContainsString('font-size:9px', $src, 'badge type size drifted from the prototype');

    // The badge sits INSIDE the button (positive offsets), never hung off the corner.
    Assert::assertStringNotContainsString('top:-3px', $src, 'badge is positioned outside the button again');
    Assert::assertStringNotContainsString('right:-3px', $src, 'badge is positioned outside the button again');
});

// ── 5. The glyph is the prototype's bell, not the old one ───────────────────────────────────────
it('draws the prototype bell glyph at 18px / 1.8 stroke', function () use ($bell) {
    $src = $bell();

    Assert::assertStringContainsString('M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9z', $src, 'the prototype bell path is gone');
    Assert::assertStringContainsString('stroke-width="1.8"', $src, 'glyph stroke weight drifted from the prototype');
});
