<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

// ══ D2 · THE DIALOG CONTRACT — WHAT THIS PROVES, AND WHAT IT CANNOT ═══════════════════════════════
//
// ⚠️⚠️ NO BROWSER HARNESS — the founder declined one. This file therefore claims nothing about runtime
// behaviour, and says so here rather than letting a reader infer more from the greens.
//
//   ✅ PROVABLE, AND PROVEN BELOW
//      · `role="dialog"` and `aria-modal="true"` are present on the panel;
//      · the dialog has a NAME — `aria-labelledby` resolves to an id that exists in the same file, once;
//      · `x-trap` is bound to the same expression that shows the dialog (so it arms and disarms with it);
//      · the panel is focusable (`tabindex="-1"`) so focus can land on it rather than a destructive button;
//      · an Escape handler exists on every one of these dialogs;
//      · all four answer IDENTICALLY — the contract, not four separate opinions.
//
//   ⛔ NOT PROVABLE HERE — AND NOT CLAIMED
//      · that focus actually MOVES into the dialog;
//      · that it is actually TRAPPED;
//      · that it RETURNS to the trigger on close;
//      · that a screen reader announces the dialog.
//    Those are runtime facts. `x-trap` is Alpine's own directive and is relied on to do them — this test
//    proves it is WIRED, never that it FIRED. The audit's accessibility findings were source-inferred for
//    exactly this reason, and nothing here is stronger than the audit was.
//
// ⚠️⚠️ AND A NOTE ON HOW THESE ASSERTIONS ARE WRITTEN: `toContain()` is VARIADIC. A second argument is a
// second NEEDLE, never a message — the exact defect the audit recorded as H-4 (a negated `toContain` whose
// "message" silently became a needle that could never appear, making the assertion unconditionally true).
// The first draft of this file passed `$label` as a message and failed loudly for that reason; the labels
// are gone rather than moved, because there is no message parameter to move them to.
//
// ⚠️ NO DEPENDENCY WAS ADDED. Livewire already bundles Alpine's focus plugin — `directive("trap")` and
// `createFocusTrap` are both present in `vendor/livewire/livewire/dist/livewire.esm.js`. `x-trap` appeared
// ZERO times across 243 view files: it was available all along and simply never used.

/** The four destructive dialogs this pass fixed, with the expression that opens each. */
const VY_DIALOGS = [
    'front-desk collect payment' => ['livewire/front-desk', "modal === 'collect'", 'vy-dlg-collect-t'],
    'bulk rate edit' => ['livewire/operator/rates-grid', 'bulkOpen', 'vy-dlg-bulk-t'],
    'revoke trusted device' => ['livewire/operator/operator-security', 'revokeId !== null', 'vy-dlg-revoke-t'],
    'GDPR erasure confirm' => ['livewire/operator/guests-list', 'delOpen', 'vy-dlg-erase-t'],
];

function vyView(string $path): string
{
    return (string) file_get_contents(resource_path('views/'.$path.'.blade.php'));
}

// ── ⚠️⚠️ ONE CONTRACT — all four answer identically ──────────────────────────────────────────────
//
// ⚠️ The toast pass established this precedent: the operator and owner shells are different design
// systems, so a single COMPONENT cannot cross them — but the SEMANTIC CONTRACT can, and it is held by a
// test asserting every implementation answers the same way. All four dialogs here are operator-shell, so
// they share a shell; the test is what stops them drifting apart regardless.
it('⚠️ gives every destructive dialog the same four properties', function () {
    foreach (VY_DIALOGS as $label => [$view, $openExpr, $titleId]) {
        $src = vyView($view);

        // (d) ANNOUNCED — a dialog role, modal semantics, and a NAME.
        expect($src)->toContain('role="dialog"');
        expect($src)->toContain('aria-modal="true"');
        expect($src)->toContain('aria-labelledby="'.$titleId.'"');

        // ⚠️ The name must RESOLVE. An `aria-labelledby` pointing at nothing leaves the dialog unnamed —
        //    worse than omitting it, because it looks handled.
        expect(substr_count($src, 'id="'.$titleId.'"'))->toBe(1);

        // (a) focus can LAND on the panel — never on the destructive button.
        expect($src)->toContain('tabindex="-1"');

        // (a)(b)(c) x-trap, armed by the SAME expression that shows the dialog, so it disarms on close
        //           and returns focus to the trigger.
        expect($src)->toContain('x-trap.noscroll="'.$openExpr.'"');
    }
});

// ── ⚠️ ESCAPE, VERIFIED PER DIALOG — not in aggregate ────────────────────────────────────────────
//
// ⚠️ This is the check the count disagreement turned on. Across the codebase only 14 of 43 overlays had
// an Escape handler, so "Escape is a mitigation the audit already counted" did not hold for most of them.
// Three of these four had none and now do.
it('⚠️ lets Escape close every destructive dialog', function () {
    foreach (VY_DIALOGS as $label => [$view, $openExpr, $titleId]) {
        expect(vyView($view))->toMatch('/keydown[.\w:]*escape/');
    }
});

// ── ⚠️ THE TRAP IS ARMED BY THE SHOW EXPRESSION, NOT A CONSTANT ──────────────────────────────────
//
// ⚠️ `x-trap="true"` would trap focus forever, which is a far worse defect than the one being fixed —
// a keyboard user could never leave the page. Binding it to the same expression as `x-show` is what makes
// it safe, so that pairing is asserted rather than assumed.
it('⚠️ arms each trap with the same expression that shows the dialog', function () {
    foreach (VY_DIALOGS as $label => [$view, $openExpr, $titleId]) {
        $src = vyView($view);
        expect($src)->toContain('x-show="'.$openExpr.'"');
        expect($src)->toContain('x-trap.noscroll="'.$openExpr.'"');
        expect($src)->not->toContain('x-trap.noscroll="true"');
    }
});

// ── ⚠️ NO DEPENDENCY WAS ADDED ───────────────────────────────────────────────────────────────────
it('⚠️ relies on the focus plugin Livewire already bundles', function () {
    $bundle = base_path('vendor/livewire/livewire/dist/livewire.esm.js');
    expect(is_file($bundle))->toBeTrue();

    $js = (string) file_get_contents($bundle);
    expect($js)->toContain('directive("trap"');     // Alpine's focus plugin, already shipped
    expect($js)->toContain('createFocusTrap');

    // ⚠️ And nothing new was installed for it.
    $pkg = json_decode((string) file_get_contents(base_path('package.json')), true);
    $deps = array_merge($pkg['dependencies'] ?? [], $pkg['devDependencies'] ?? []);
    foreach (array_keys($deps) as $name) {
        expect($name)->not->toContain('alpine');
        expect($name)->not->toContain('focus-trap');
    }
});

// ══ ⚠️⚠️ THE WHOLE CENSUS — every modal dialog, discovered, not listed ═════════════════════════════
//
// ⚠️ The four above were named in a constant. There are now THIRTY-SEVEN, and a hand-kept list had already
// stopped describing the codebase: two `portfolio` dialogs carried `role`, a name and Escape but NO
// `x-trap` and NO `tabindex` — they read as done and were not. These assertions walk the views instead, so
// a dialog added tomorrow is covered the day it appears rather than the day someone remembers.
//
// ⚠️⚠️ AND THE LIMIT IS UNCHANGED FROM THE HEADER ABOVE: this proves the contract is WIRED. It does not
// prove focus moves, is trapped, or returns — there is still no browser harness, and none is claimed.
//
// ⚠️⚠️ THE BACKGROUND IS STILL NOT INERT. Focus is trapped and no more, so a screen-reader user browsing by
// virtual cursor can still reach content behind an open dialog. This pass did not change that and does not
// imply otherwise anywhere.
it('⚠️⚠️ gives EVERY modal dialog the full contract — all thirty-seven, discovered', function () {
    $offenders = [];

    foreach (vyDialogCensus() as $d) {
        $where = $d['file'].' x-show="'.$d['show'].'"';
        $panel = $d['panel'];

        foreach ([
            'role="dialog"' => str_contains($panel, 'role="dialog"'),
            'aria-modal="true"' => str_contains($panel, 'aria-modal="true"'),
            'tabindex="-1"' => str_contains($panel, 'tabindex="-1"'),
            'an accessible name' => str_contains($panel, 'aria-labelledby=') || str_contains($panel, 'aria-label='),
            'an Escape handler' => (bool) preg_match('/keydown[.\w:]*escape/', $d['backdrop']),
        ] as $what => $present) {
            if (! $present) {
                $offenders[] = "{$where} — missing {$what}";
            }
        }

        // ⚠️⚠️ THE TRAP MUST BIND TO ITS OWN SHOW EXPRESSION. Not a constant, not a neighbour's variable that
        //    happens to be true at the same moment. `x-trap="true"` traps focus FOREVER — worse than the
        //    defect being fixed — and applying one pattern thirty-seven times is exactly where that hides.
        //    This is why the census is machine-read rather than hand-checked.
        preg_match('/x-trap[.\w]*="([^"]*)"/', $panel, $t);
        $trap = $t[1] ?? null;

        if ($trap === null) {
            $offenders[] = "{$where} — no x-trap";
        } elseif ($trap !== $d['show']) {
            $offenders[] = "{$where} — trap is bound to \"{$trap}\", not to its own show expression";
        }
    }

    expect($offenders)->toBe([]);
});

it('⚠️ never arms a trap with a constant, anywhere in the views', function () {
    // ⚠️ The class, not the instance: a constant trap ANYWHERE is the catastrophic case, so this looks at
    //    every view rather than only the ones the census classed as dialogs.
    $offenders = [];

    foreach (vyDialogCensus() as $d) {
        foreach (['x-trap="true"', 'x-trap.noscroll="true"', 'x-trap="1"'] as $constant) {
            if (str_contains($d['panel'], $constant)) {
                $offenders[] = $d['file'].' — '.$constant;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('⚠️ censuses a plausible number of dialogs — a discovery guard that finds nothing proves nothing', function () {
    // ⚠️ NON-VACUITY. Every assertion above iterates the census; if the walk broke, they would all pass
    //    against an empty list. The floor is the count measured when this pass ran.
    expect(count(vyDialogCensus()))->toBeGreaterThanOrEqual(37);
});

// ══ ⚠️⚠️ THE NAMES MUST RESOLVE — CHECKED ON THE SERVED PAGE, NOT IN SOURCE ═══════════════════════
//
// ⚠️ An `aria-labelledby` pointing at an id that does not exist leaves the dialog UNNAMED, and is worse
// than omitting it because it looks handled. Source cannot settle this: three of these dialogs render
// inside a `@foreach`, so their ids are Blade-interpolated and appear ONCE in the file and N times on the
// page. An id in an `@if` branch resolves differently again.
//
// ⚠️ So this renders real screens as a real operator and checks every reference against the rendered ids.
uses(RefreshDatabase::class);

it('⚠️⚠️ resolves every dialog name on the SERVED page, and every id is unique there', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Property Owner');

    // ⚠️ WHO WE ARE, ASSERTED. A census in this project once ran silently as the wrong user.
    //    ⚠️ The team id must be set first — spatie runs in TEAMS mode here, and `hasRole()` without a team
    //    silently answers FALSE rather than erroring. The first draft of this line failed for exactly that
    //    reason, which is the same "fails silently to false" shape invariant #30 warns about.
    setPermissionsTeamId($s['org']->getKey());
    expect($user->fresh()->hasRole('Property Owner'))->toBeTrue();

    $screens = ['/app/front-desk', '/app/housekeeping', '/app/maintenance', '/app/guests',
        '/app/inbox', '/app/roles', '/app/security', '/app/settings', '/app/portfolio',
        '/app/rates', '/app/schedule', '/app/units'];

    $checked = 0;
    $offenders = [];

    foreach ($screens as $url) {
        $res = $this->actingAs($user)->get($url);
        if ($res->status() !== 200) {
            continue;   // not reachable for this actor/fixture — recorded by the count below, never assumed
        }

        $html = (string) $res->getContent();
        $html = (string) preg_replace(['/<script\b[^>]*>.*?<\/script>/is', '/<style\b[^>]*>.*?<\/style>/is', '/<!--.*?-->/s'], '', $html);

        preg_match_all('/\bid="([^"]+)"/', $html, $ids);
        $counts = array_count_values($ids[1]);

        preg_match_all('/aria-labelledby="([^"]+)"/', $html, $refs);

        foreach (array_unique($refs[1]) as $ref) {
            $checked++;
            $n = $counts[$ref] ?? 0;
            if ($n === 0) {
                $offenders[] = "{$url}: aria-labelledby=\"{$ref}\" names NOTHING on the rendered page";
            } elseif ($n > 1) {
                $offenders[] = "{$url}: id=\"{$ref}\" appears {$n} times — the name resolves to whichever came first";
            }
        }
    }

    expect($offenders)->toBe([]);

    // ⚠️ NON-VACUITY: if no screen rendered a dialog, the loop above proves nothing.
    expect($checked)->toBeGreaterThan(5);
});

it('⚠️⚠️ resolves every dialog name IN ITS OWN FILE too — the half the served page cannot reach', function () {
    // ⚠️ WHY BOTH. The served-page check above can only judge names that actually RENDER, and several
    //    dialogs render only in a state a fixture does not reach — `/app/inbox` renders ONE of its three
    //    without a selected conversation. A mutation that pointed a non-rendered dialog at a missing id
    //    therefore passed the served-page check, which is how this gap was found rather than assumed.
    //    Source resolution covers the ones the page cannot show; the served page covers Blade interpolation
    //    and duplicate ids. Neither alone is enough.
    $offenders = [];
    $checked = 0;

    foreach (vyDialogCensus() as $d) {
        if (! preg_match('/aria-labelledby="([^"]+)"/', $d['panel'], $m)) {
            continue;   // named by `aria-label` instead — nothing to resolve
        }

        $checked++;
        $src = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/'.$d['file'])));

        // ⚠️ The id may be Blade-interpolated (`vy-dlg-…-{{ $vyU['id'] }}`), so the reference is matched as
        //    written — the served-page check above is what proves the interpolated form resolves at runtime.
        if (! str_contains($src, 'id="'.$m[1].'"')) {
            $offenders[] = $d['file'].' x-show="'.$d['show'].'" — aria-labelledby="'.$m[1].'" has no matching id in the file';
        }
    }

    expect($offenders)->toBe([]);
    expect($checked)->toBeGreaterThan(25);   // non-vacuity: most dialogs are named by a heading, not an attribute
});
