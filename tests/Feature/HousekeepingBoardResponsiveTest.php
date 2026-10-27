<?php

// ══ D10 · THE HOUSEKEEPING BOARD ON A PHONE — WHAT THIS PROVES, AND WHAT IT CANNOT ════════════════
//
// ⚠️⚠️ NO BROWSER HARNESS — the founder declined one. Nothing here claims a visual result.
//
//   ✅ PROVABLE, AND PROVEN BELOW
//      · the board uses the SHARED `.vy-kanban` class rather than a private inline grid;
//      · the shell declares the responsive rules that class depends on, at breakpoints that already existed;
//      · the desk grid is unchanged (4 columns, the same gap) above the largest breakpoint;
//      · every dialog on this screen is height-bounded so its buttons cannot scroll away unreachably;
//      · a non-drag, no-JavaScript path to change a task's status exists on every card.
//
//   ⛔ NOT PROVABLE HERE — AND NOT CLAIMED
//      · how any of it LOOKS at 375px;
//      · that a lane is legible, or that a thumb can hit a target;
//      · that a media query actually fires — no engine evaluates CSS in this suite.
//    ⚠️ A HUMAN CHECK ON A REAL PHONE IS THE ONLY REAL VERIFICATION OF THIS PASS. It is recommended,
//    not performed, and these greens must not be read as a substitute for it.

// ── ⚠️⚠️ THE THING THAT OUTRANKS THE LAYOUT: STATUS IS NOT DRAG-ONLY ─────────────────────────────
//
// ⚠️ If a drag were the only way to advance a task, the board would be UNUSABLE on a phone whatever its
// width — HTML5 drag-and-drop does not work on touch without explicit handling, and this board adds none.
// ⚠️ It is not. Every card carries a real <form method="POST"> with a submit button, so the status path
// works by touch, by keyboard, and with no JavaScript at all. Drag is an ADDITIONAL desk affordance
// layered on top, and losing it on a phone costs nothing.
it('⚠️ advances a task status without drag, JavaScript, or a pointer', function () {
    $src = i18nEnglishSource(resource_path('views/livewire/housekeeping-board.blade.php'));

    // A real form POST per card — the touch/keyboard/no-JS path.
    expect($src)->toContain("route('app.housekeeping.status'");
    expect($src)->toMatch('/<form method="POST" action="\{\{ route\(\'app\.housekeeping\.status\'/');

    // ⚠️ Drag exists, but only as an extra: it must never be the sole path.
    expect(substr_count($src, 'hkDrop('))->toBeGreaterThan(0);
});

// ── ⚠️ THE SHARED CLASS, NOT A PRIVATE GRID ──────────────────────────────────────────────────────
//
// ⚠️ This is why the file had zero @media queries: `.vy-kanban` was defined in the shell WITH a responsive
// rule, and nobody used it — the board hand-rolled the same four columns inline and inherited the shape
// but not the behaviour. The rule was never missing; the class was.
it('⚠️ draws the board with the shell’s kanban class', function () {
    $board = i18nEnglishSource(resource_path('views/livewire/housekeeping-board.blade.php'));
    $shell = i18nEnglishSource(resource_path('views/components/layouts/operator.blade.php'));

    expect($board)->toContain('class="vy-kanban"');
    // ⚠️ No private four-column grid may come back alongside it.
    expect($board)->not->toContain('grid-template-columns: repeat(4, minmax(0, 1fr))');

    // The shell owns the definition and both narrowings.
    expect($shell)->toContain('.vy-kanban { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr))');
    expect($shell)->toContain('.vy-kanban { grid-template-columns: repeat(2, minmax(0, 1fr)); }');
    expect($shell)->toContain('.vy-kanban { grid-template-columns: minmax(0, 1fr); }');
});

// ⚠️ THE DESK MUST NOT MOVE. Above the largest breakpoint the class supplies exactly what the inline style
// used to — four columns, align-items:start — and the board keeps its own 14px gap inline so the shared
// class's 16px cannot silently retune the wide layout.
it('⚠️ leaves the desk grid at four columns with its original gap', function () {
    $board = i18nEnglishSource(resource_path('views/livewire/housekeeping-board.blade.php'));
    $shell = i18nEnglishSource(resource_path('views/components/layouts/operator.blade.php'));

    expect($board)->toContain('<div class="vy-kanban" style="gap: 14px;">');
    expect($shell)->toContain('grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; align-items: start;');
});

// ⚠️ THE BREAKPOINTS ARE THE SHELL'S OWN. Inventing a set for one screen is the drift this project keeps
// finding; 1100px and 700px both already existed before this pass.
it('⚠️ reuses breakpoints the shell already declared', function () {
    $shell = i18nEnglishSource(resource_path('views/components/layouts/operator.blade.php'));

    expect(substr_count($shell, '@media (max-width: 1100px)'))->toBeGreaterThan(0);
    expect(substr_count($shell, '@media (max-width: 700px)'))->toBeGreaterThan(1);   // the pre-existing one + ours
});

// ── ⚠️ A DIALOG THAT DOES NOT FIT IS A DIALOG YOU CANNOT DISMISS ─────────────────────────────────
//
// ⚠️ On a short phone viewport an unbounded modal pushes its own Cancel/Confirm past the fold with no way
// back to them. Two of this screen's three had `max-width` but no height bound; all three now cap at 90vh
// and scroll internally.
it('⚠️ bounds every dialog on this screen so its buttons cannot scroll away', function () {
    $src = i18nEnglishSource(resource_path('views/livewire/housekeeping-board.blade.php'));

    // ⚠️ 2026-10-05 — the three panels moved onto the SHARED dialog card (`vy-modal vy-modal-card`), and the bound moved with them:
    //    the shared `.vy-modal` caps its height and scrolls inside, so the inline `max-height: 90vh` each panel carried is gone.
    preg_match_all('/<div [^>]*class="vy-modal vy-modal-card[^"]*"[^>]*>/', $src, $panels);
    expect(count($panels[0]))->toBe(3);
    $layout = (string) i18nEnglishSource(resource_path('views/components/layouts/operator.blade.php'));
    expect($layout)->toMatch('~\n\s*\.vy-modal \{[^}]*max-height: 92vh; overflow-y: auto~');
});

// ⚠️ And the D2 contract, applied here because it is mechanical — every dialog announced and focus-managed.
it('⚠️ gives each dialog on this screen a role, a name, a trap and an Escape', function () {
    $src = i18nEnglishSource(resource_path('views/livewire/housekeeping-board.blade.php'));

    expect(substr_count($src, 'role="dialog"'))->toBe(3);
    expect(substr_count($src, 'aria-modal="true"'))->toBe(3);
    expect(substr_count($src, 'x-trap.noscroll='))->toBe(3);
    expect(substr_count($src, 'tabindex="-1"'))->toBe(3);

    // ⚠️ A name that RESOLVES. Two point at real headings; the detail dialog has no heading to point at,
    //    so it carries an `aria-label` — a name that exists beats an `aria-labelledby` that dangles.
    foreach (['vy-hk-assign-t', 'vy-hk-assignall-t'] as $id) {
        expect(substr_count($src, 'aria-labelledby="'.$id.'"'))->toBe(1);
        expect(substr_count($src, 'id="'.$id.'"'))->toBe(1);
    }
    expect($src)->toContain('aria-label="Task detail"');

    expect(substr_count($src, 'keydown.escape.window'))->toBe(3);
});
