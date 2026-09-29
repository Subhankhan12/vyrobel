<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * DO-EMAIL-5-continued · PART B — THE DATA-CONDITIONAL SWEEP (§N.16.2 axis 3, 2026-08-13).
 *
 * ⚠️ THE THIRD AXIS, SWEPT FOR REAL. A surface hides three ways: by STYLE/STATE (G10 — force the state), by
 * AUTHORIZATION (§N.14.1 — render as the role), and by DATA — markup that does not exist in the DOM until a
 * list is populated, a search is performed, or a record reaches a triggering state. The first two axes had
 * been swept; the third never had, in any realm. Every prior contrast pass measured the EMPTY default and
 * called the page clean.
 *
 * What it found is exactly what an unswept axis predicts: defects on markup that only the populated state
 * renders — an expense's "Not deductible"/"Pending review" row chip, and the guest NAME on a booked calendar
 * cell, which cannot exist until a real booking occupies that day.
 *
 * ⚠️ THIS FILE MUST RENDER WITH THE DATA PRESENT. A guard that renders the empty state is VACUOUS — it cannot
 * see the markup it claims to protect, which is the DATA-axis twin of the wrong-role and light-plate traps.
 * So each test below asserts the populated markup is really there before asserting anything about its colour.
 *
 * ⚠️ CONSTANTS ARE PREFIXED. A top-level `const` in a Pest file is a GLOBAL: in DO-A11Y-1 an unprefixed plate
 * constant collided across two test files, and the guard passed alone while failing in-suite.
 */

/** WCAG contrast, computed here so the guard never depends on the palette it checks (§N.18.1). */
function partBRatio(array $fg, array $plate): float
{
    $lum = static function (array $c): float {
        $s = array_map(static function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (float) pow(($v + 0.055) / 1.055, 2.4);
        }, $c);

        return 0.2126 * $s[0] + 0.7152 * $s[1] + 0.0722 * $s[2];
    };

    $x = $lum($fg);
    $y = $lum($plate);
    if ($x < $y) {
        [$x, $y] = [$y, $x];
    }

    return round(($x + 0.05) / ($y + 0.05), 2);
}

/** Plates pixel-sampled on the POPULATED surfaces, with the data forced. Prefixed — see the header. */
const PARTB_EXPENSE_CHIP_PLATE = [233, 190, 166];   // "Not deductible", on its own tint over the card
const PARTB_PENDING_CHIP_PLATE = [231, 199, 182];   // "Pending review"
const PARTB_BOOKED_CELL_PLATE = [242, 178, 145];    // a calendar cell a booking occupies
const PARTB_LEGEND_PLATE = [234, 195, 172];         // the calendar legend strip

it('⚠️ AXIS 3 — the owner EXPENSES row chips clear AA, and the markup only exists when populated', function () {
    $shell = (string) file_get_contents(resource_path('views/components/layouts/owner.blade.php'));

    // ⚠️ NON-VACUITY: the rule exists, so this is about its colour and not about a deleted rule.
    expect($shell)->toContain('.vy-status--draft {');

    // The row chip renders per-expense — invisible on an empty Expenses page, which is why no earlier pass
    // measured it. --vy-muted read 4.12 on the chip's own tint.
    expect($shell)->toMatch('/\.vy-status--draft \{[^}]*color:\s*var\(--vy-on-grad\)/');

    expect(partBRatio([76, 66, 60], PARTB_EXPENSE_CHIP_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([95, 88, 83], PARTB_EXPENSE_CHIP_PLATE))->toBeLessThan(4.5);   // the old value failed
});

it('⚠️ --vy-amber-text was darkened again — it failed on a chip only the POPULATED page renders', function () {
    $tokens = (string) file_get_contents(resource_path('views/components/design/tokens.blade.php'));

    expect($tokens)->toContain('--vy-amber-text:')                        // non-vacuity
        ->and($tokens)->toContain('--vy-amber-text: rgb(112, 66, 4)')
        ->and($tokens)->not->toContain('rgb(128, 76, 5)');

    // 4.48 on the "Pending review" chip — a real miss, so darkening is required rather than the headroom
    // change posture A declined.
    expect(partBRatio([112, 66, 4], PARTB_PENDING_CHIP_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([128, 76, 5], PARTB_PENDING_CHIP_PLATE))->toBeLessThan(4.5);
});

it('⚠️ AXIS 3 — the BOOKED-cell guest name clears AA (it cannot exist without a booking)', function () {
    $view = (string) file_get_contents(resource_path('views/livewire/owner/block-calendar.blade.php'));

    expect($view)->toContain('.vy-cal-guest {')                            // non-vacuity
        ->and($view)->toMatch('/\.vy-cal-guest \{[^}]*color:\s*var\(--vy-on-grad\)/')
        ->and($view)->toMatch('/\.vy-cal-leg \{[^}]*color:\s*var\(--vy-on-grad\)/');

    expect(partBRatio([76, 66, 60], PARTB_BOOKED_CELL_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([76, 66, 60], PARTB_LEGEND_PLATE))->toBeGreaterThanOrEqual(4.5)
        // Both old values failed on the plates the populated state actually paints.
        ->and(partBRatio([151, 54, 13], PARTB_BOOKED_CELL_PLATE))->toBeLessThan(4.5)
        ->and(partBRatio([95, 88, 83], PARTB_LEGEND_PLATE))->toBeLessThan(4.5);
});

it('⚠️ §N.8f — .vy-avatar is defined in BOTH shells, and BOTH carry the wireframe background', function () {
    $owner = (string) file_get_contents(resource_path('views/components/layouts/owner.blade.php'));
    $operator = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // ⚠️ RE-POINTED BY DO-RESTORE-3 (2026-08-14). DO-A11Y-1 moved these backgrounds to a deep orange so white
    // would clear AA; the founder ruled to restore the WIREFRAME backgrounds and ACCEPT the sub-AA. What this
    // test protects is UNCHANGED and is the point: the two shells must agree. It caught an owner-copy miss in
    // one direction (Part B); it now catches a missed copy in the other.
    //
    // ⚠️ RE-POINTED AGAIN BY DO-RESTORE-5 (2026-08-15). The two shells used to carry DIFFERENT wireframe
    // backgrounds — operator the peach gradient, owner the CTA gradient — which is precisely why neither could
    // be fixed by text colour. Both are now the SAME FLAT plate, so the §N.8f invariant this test has always
    // protected (the shells must agree) is stronger than before, not weaker.
    expect($operator)->toMatch('/\.vy-avatar \{[^}]*background: var\(--vy-avatar-bg\)/')
        ->and($owner)->toMatch('/\.vy-avatar \{[^}]*background: var\(--vy-avatar-bg\)/');

    // ⚠️ THE PROTECTION: the AA-clearing background DO-A11Y-1 shipped must not reappear on either copy — its
    // return means someone "fixed" a contrast report and reverted the founder's decision.
    foreach ([$owner, $operator] as $shell) {
        expect($shell)->not->toMatch('/\.vy-avatar \{[^}]*background:\s*linear-gradient\(140deg, #C2410C, #9A3412\)/');
    }

    // ⚠️ THE SUB-AA IS GONE, NOT ACCEPTED ANY MORE. DO-RESTORE-3 recorded white-on-peach at 2.03 as a decided
    // exception; DO-RESTORE-5 removed the cause by flattening the plate and darkening the text. What the
    // pairing below pins is WHY: white on this plate was never viable (2.03), dark ink on it is (7.60).
    expect(partBRatio([255, 255, 255], [240, 165, 122]))->toBeLessThan(3.0)
        ->and(partBRatio([43, 35, 32], [240, 165, 122]))->toBeGreaterThanOrEqual(7.0);

    // For contrast with the decision that was NOT taken: the deep orange cleared it comfortably.
    expect(partBRatio([255, 255, 255], [194, 65, 12]))->toBeGreaterThanOrEqual(4.5);
});

/*
 * ⚠️ THE DATA-AWARENESS PROOF. Everything above reads source files, which is honest about WHAT it checks but
 * says nothing about whether the markup ever renders. This test renders the real owner surfaces with the demo
 * data present and asserts the populated markup is in the DOM — so if a future change makes these surfaces
 * empty-only, the guards above stop being about anything and this test says so.
 */
it('⚠️ NOT VACUOUS — the populated markup really renders on the owner surfaces', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);

    $calendar = (string) $this->actingAs($owner)->get('/owner/calendar')->assertOk()->getContent();

    // The legend is unconditional; the guest-name cell is not. Assert the CLASS the guard protects is present
    // in the rendered document, which is what "the data condition is forced" means here.
    expect($calendar)->toContain('vy-cal-leg')
        ->and($calendar)->toContain('vy-cal-guest');
});

/*
 * ⚠️ DO-EMAIL-5-continued-3 — THE THROUGHPUT PASS (2026-08-13). Same proven procedure, more surfaces.
 *
 * My Units is the densest data-conditional surface in the owner realm: its action centre, attention items and
 * per-unit performance blocks only exist once there is something to act on. Measured populated: 16 failures.
 */
const PARTB_MU_CARD_PLATE = [228, 211, 202];      // behind the attention-item sublines
const PARTB_MU_TINT_PLATE = [224, 201, 184];      // the unit-performance tint
const PARTB_MU_DARK_PLATE = [64, 43, 32];         // the DARK performance card
const PARTB_MU_ADDCARD_PLATE = [245, 214, 195];   // the Add-a-unit card

it('⚠️ AXIS 3 — the My Units attention-item text clears AA (it exists only when items do)', function () {
    $shell = (string) file_get_contents(resource_path('views/components/layouts/owner.blade.php'));

    // ⚠️ NON-VACUITY: the rules exist, so these are statements about their colour.
    foreach (['.vy-mu-ac-sub {', '.vy-mu-item-sub {', '.vy-mu-uplabel {'] as $rule) {
        expect($shell)->toContain($rule);
    }

    expect($shell)->toMatch('/\.vy-mu-ac-sub \{[^}]*color:\s*var\(--vy-muted\)/')
        ->and($shell)->toMatch('/\.vy-mu-item-sub \{[^}]*color:\s*var\(--vy-muted\)/')
        ->and($shell)->toMatch('/\.vy-mu-uplabel \{[^}]*color:\s*var\(--vy-on-grad\)/');

    expect(partBRatio([95, 88, 83], PARTB_MU_CARD_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([108, 100, 95], PARTB_MU_CARD_PLATE))->toBeLessThan(4.5)     // --vy-faint failed
        ->and(partBRatio([76, 66, 60], PARTB_MU_TINT_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([95, 88, 83], PARTB_MU_TINT_PLATE))->toBeLessThan(4.5);      // --vy-muted failed there
});

it('⚠️ the DARK performance card and the Add-a-unit label use values that clear their own plates', function () {
    $shell = (string) file_get_contents(resource_path('views/components/layouts/owner.blade.php'));
    $units = (string) file_get_contents(resource_path('views/livewire/owner/units.blade.php'));

    // A literal hard-coding "accent as text" where a corrected token exists (N.14.9).
    expect($shell)->toMatch('/\.vy-mu-addcard \{[^}]*color:\s*var\(--vy-accent-text\)/')
        ->and($shell)->not->toContain('.vy-mu-addcard { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; color: #B4400F');

    // ⚠️ THE INVERTED CASE, and worth pinning: on the DARK card the text must be LIGHT. --vy-accent-3 read
    // 4.30 there, so this is the one fix in the family that goes lighter, not darker.
    expect($units)->toContain('color:var(--vy-peach);font-weight:500">occupancy')
        ->and($units)->not->toContain('color:var(--vy-accent-3);font-weight:500">occupancy');

    expect(partBRatio([147, 42, 7], PARTB_MU_ADDCARD_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([180, 64, 15], PARTB_MU_ADDCARD_PLATE))->toBeLessThan(4.5)
        ->and(partBRatio([240, 165, 122], PARTB_MU_DARK_PLATE))->toBeGreaterThanOrEqual(4.5)   // --vy-peach
        ->and(partBRatio([232, 112, 58], PARTB_MU_DARK_PLATE))->toBeLessThan(4.5);             // --vy-accent-3
});

/*
 * ⚠️ DO-EMAIL-5-continued-4 — THE GUEST REALM (2026-08-13). Axis 3 applied to the realm that had never been
 * measured on it at all.
 *
 * ⚠️ AND A LESSON RE-LEARNED THE HARD WAY, IN THE SAME PASS THAT RECORDED IT. `.vy-link` measured 4.45 on a
 * populated booking. The class rule was fixed — and the page did not move, because an INLINE
 * `style="color:var(--vy-accent)"` on the anchor won the cascade (§N.21.6). The class fix was still correct
 * and is kept: it covers every `.vy-link` WITHOUT an override. But the defect lived in the inline, and there
 * were 38 of them across 15 guest files. BOTH halves are guarded below, because fixing either alone leaves
 * real text failing.
 */
const PARTB_GUEST_CARD_PLATE = [249, 235, 228];   // a guest booking card, where the brand fill was used as text

it('⚠️ the guest .vy-link CLASS uses the text token — both copies of it (§N.8f)', function () {
    $shell = (string) file_get_contents(resource_path('views/components/guest/shell.blade.php'));
    $auth = (string) file_get_contents(resource_path('views/guest/auth/layout.blade.php'));

    // ⚠️ NON-VACUITY: the rule exists in both files — it is defined twice, which is why both must be checked.
    foreach ([$shell, $auth] as $source) {
        expect($source)->toContain('.vy-link {')
            ->and($source)->toMatch('/\.vy-link \{[^}]*color: var\(--vy-accent-text\)/');
    }
});

it('⚠️ NO INLINE OVERRIDE PAINTS THE BRAND FILL AS TEXT anywhere in the guest realm', function () {
    // ⚠️ THE HALF THAT ACTUALLY FIXED THE PAGE. An inline style beats the class, so the class guard above is
    // not sufficient on its own — this is the regression that would silently return.
    $inline = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views/guest')));
    foreach ($it as $f) {
        if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.blade.php')) {
            continue;
        }
        $inline += preg_match_all('/color:\s*var\(--vy-accent\)/', (string) file_get_contents($f->getPathname()));
    }

    expect($inline)->toBe(0);

    // The measured reason: the FILL misses on a guest card, the purpose-built TEXT value clears it comfortably.
    expect(partBRatio([194, 65, 12], PARTB_GUEST_CARD_PLATE))->toBeLessThan(4.5)          // --vy-accent  4.45
        ->and(partBRatio([147, 42, 7], PARTB_GUEST_CARD_PLATE))->toBeGreaterThanOrEqual(4.5); // --vy-accent-text 7.00
});

/*
 * ⚠️ DO-EMAIL-5-continued-5 — THE OPERATOR TAIL (2026-08-14).
 *
 * ⚠️ THE PATTERN THIS PASS KEPT HITTING: a status colour written as a TERNARY or a set of sibling modifier
 * classes, where only ONE branch is currently rendered. The probe measures the branch the data produces; the
 * siblings are invisible to it and fail just as hard. Both were found by READING the branch beside the
 * measured one — §N.13.1, in its axis-3 form. They are marked LATENT below.
 */
const PARTB_SLA_PLATE = [252, 211, 188];        // the maintenance SLA line, on its row
const PARTB_PAYSTATUS_HOLD = [236, 191, 167];   // the owners "On hold" chip, on its own tint
const PARTB_NAV_ACTIVE_PLATE = [251, 236, 226]; // the active sidebar pill

it('⚠️ AXIS 3 — the maintenance SLA line clears AA in BOTH failing states, not just the rendered one', function () {
    $view = (string) file_get_contents(resource_path('views/livewire/maintenance-list.blade.php'));

    // ⚠️ NON-VACUITY: the conditional really is there, so this is about its branches.
    expect($view)->toContain("\$row['sla']['breached']");

    // The BREACHED branch was measured at 4.12. The DUE_SOON branch was NOT rendered by the demo data at all —
    // it is a LATENT defect found by reading the sibling, and it was worse (3.79).
    // ⚠️ SCOPED TO THE TERNARY, not a blanket ban on the literal. #B4400F legitimately survives in this file
    // as a KPI DOT colour (non-text — the 1.4.11 bar is 3:1, not 4.5) and inside a state-hidden modal.
    // Banning the string outright would fail on correct markup — the `.vy-chan-glyph` lesson.
    expect($view)->toMatch("/breached'\\] \\? 'var\\(--vy-accent-text\\)'/")
        ->and($view)->toMatch("/due_soon'\\] \\? 'var\\(--vy-amber-text\\)'/")
        ->and($view)->not->toMatch("/breached'\\] \\? '#B4400F'/");

    expect(partBRatio([147, 42, 7], PARTB_SLA_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([180, 64, 15], PARTB_SLA_PLATE))->toBeLessThan(4.5)     // #B4400F failed
        ->and(partBRatio([143, 100, 20], PARTB_SLA_PLATE))->toBeLessThan(4.5);   // #8F6414 failed harder
});

it('⚠️ AXIS 3 — the owner payout-status chips: three branches fixed, the PASSING one left alone', function () {
    $view = (string) file_get_contents(resource_path('views/livewire/operator/owners.blade.php'));

    expect($view)->toContain('.vy-paystatus--hold')                              // non-vacuity
        ->and($view)->toMatch('/\.vy-paystatus--hold \{[^}]*color: var\(--vy-accent-text\)/')
        ->and($view)->toMatch('/\.vy-paystatus--due \{[^}]*color: var\(--vy-accent-text\)/')
        ->and($view)->toMatch('/\.vy-paystatus--draft \{[^}]*color: var\(--vy-amber-text\)/');

    // ⚠️ POSTURE A, GUARDED AS A NON-CHANGE: --paid measures 5.35 and was deliberately NOT touched. Without
    // this line a future "consistency" sweep would darken a passing declaration and call it a fix.
    expect($view)->toMatch('/\.vy-paystatus--paid \{[^}]*color: #1F6E4B/');

    expect(partBRatio([147, 42, 7], PARTB_PAYSTATUS_HOLD))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([180, 64, 15], PARTB_PAYSTATUS_HOLD))->toBeLessThan(4.5);
});

it('⚠️ the ACTIVE sidebar item used the brand FILL as text — 4.49, a miss by 0.01', function () {
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    expect($shell)->toContain('.vy-nav-item.is-active {')                        // non-vacuity
        ->and($shell)->toMatch('/\.vy-nav-item\.is-active \{[^}]*color: var\(--vy-accent-text\)/');

    // Fixed at the SOURCE (O25/O27), not from the page that revealed it.
    expect(partBRatio([194, 65, 12], PARTB_NAV_ACTIVE_PLATE))->toBeLessThan(4.5)          // --vy-accent 4.49
        ->and(partBRatio([147, 42, 7], PARTB_NAV_ACTIVE_PLATE))->toBeGreaterThanOrEqual(4.5);
});

/*
 * ⚠️ DO-STATE-1 — AXIS 1 (STYLE/STATE), the last unswept axis (2026-08-14).
 *
 * A surface hides three ways, and each needs its own forcing move:
 *   axis 1 STATE  → FORCE THE STATE VISIBLE (open the modal, activate the tab, expand the accordion)
 *   axis 2 ROLE   → render as the role (§N.14.1)
 *   axis 3 DATA   → force the data condition (§N.16.2)
 *
 * ⚠️ THE FINDING: A MODAL CARD IS NOT A PAGE CARD. `--vy-glass` (rgba(255,252,248,.58)) was tuned for a card
 * on the LIGHT page. Inside a modal the identical card sits on a DARK scrim (rgba(43,35,32,.4)) which
 * composites through it, dropping the plate from ~rgb(249,223,207) to ~rgb(215,199,189). Every muted/faint
 * label in every modal is measured against THAT — and no earlier pass could see it, because a closed modal
 * renders nothing.
 */
const STATE1_MODAL_PLATE = [215, 199, 189];   // the glass card composited over the scrim, at the old .58
const STATE1_PAGE_PLATE = [249, 223, 207];    // the same card on a page, for contrast

it('⚠️ AXIS 1 — the MODAL card is opaque enough that its labels clear AA', function () {
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // ⚠️ NON-VACUITY: the rule exists and the concept is named once (§N.8f) rather than patched per modal.
    expect($shell)->toContain('.vy-modal-card {')
        ->and($shell)->toMatch('/\.vy-modal-card \{[^}]*background: rgba\(255, 252, 248, 0\.94\)/');

    // ⚠️ ORDER IS LOAD-BEARING, AND THIS IS THE ASSERTION THAT PROVES IT. `.vy-card` and `.vy-modal-card` are
    // both single-class selectors, so specificity ties and the LATER rule wins. Defined before `.vy-card`,
    // this rule silently loses and the page does not move — which is exactly what happened twice before the
    // re-measure caught it (§N.21.6).
    expect(strpos($shell, '.vy-modal-card {'))->toBeGreaterThan(strrpos($shell, "\n        .vy-card {"));

    // The measured reason: the old glass over the scrim failed both greys; the page card never did.
    expect(partBRatio([108, 100, 95], STATE1_MODAL_PLATE))->toBeLessThan(4.5)      // --vy-faint 2.79 measured
        ->and(partBRatio([95, 88, 83], STATE1_MODAL_PLATE))->toBeLessThan(4.5)     // --vy-muted 3.38 measured
        ->and(partBRatio([108, 100, 95], STATE1_PAGE_PLATE))->toBeGreaterThan(4.5); // the same grey on a page
});

it('⚠️ AXIS 1 — every modal card carries the class, so the fix is not one dialog deep (§N.8f)', function () {
    // ⚠️ A GUARD ON THE CLOSED MODAL WOULD BE VACUOUS — it cannot see the surface at all. This asserts the
    // MARKUP that the forced-open measurement proved, across every file that builds a scrim modal.
    $files = ['livewire/operator/guests-list.blade.php', 'livewire/operator/guest-profile.blade.php',
        'livewire/maintenance-list.blade.php', 'livewire/housekeeping-board.blade.php'];

    $total = 0;
    foreach ($files as $f) {
        $src = (string) file_get_contents(resource_path('views/'.$f));

        // ⚠️ non-vacuity per file: it really does build a scrim modal — with the old inline scrim, or (the two Guests
        // pages since 2026-09-29) the shared `.vy-modal-backdrop`, because an inline `display` beside x-show is erased
        // and left their dialogs in the top-left corner. (No second argument to toContain — Pest takes MULTIPLE
        // NEEDLES, so a "message" there is silently searched for as text and the guard fails for the wrong reason.)
        expect(str_contains($src, 'rgba(43,35,32,.4)') || str_contains($src, 'vy-modal-backdrop'))->toBeTrue($f);
        $total += substr_count($src, 'vy-modal-card');
    }

    expect($total)->toBeGreaterThanOrEqual(11);

    // ⚠️ And on the shared backdrop the card still WINS. `.vy-modal` is the see-through glass and is defined LATER than
    // `.vy-modal-card`, so on its own the class would silently lose (the §N.21.6 trap again). The two-class rule is
    // what makes a `.vy-modal` panel that is a modal card near-opaque.
    // 🖥 Measured 2026-09-29 on the Portfolio's Add dialog, the see-through panel read 3.52:1 (sub-text, --vy-muted) and
    //    3.25:1 (field labels, --vy-faint); with the card the Guests dialogs read 6.27:1 and 5.28:1.
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    expect($shell)->toMatch('/\.vy-modal\.vy-modal-card \{[^}]*background: rgba\(255, 252, 248, 0\.94\)/');
    foreach (['livewire/operator/guests-list.blade.php', 'livewire/operator/guest-profile.blade.php', 'livewire/operator/portfolio.blade.php'] as $f) {
        $src = (string) file_get_contents(resource_path('views/'.$f));
        // Every panel's class list, read whole — a first draft counted `class="vy-modal "` WITH a trailing space and never
        // saw a panel whose class is exactly `vy-modal` (the mutation harness found it: GL-M21 stayed green).
        preg_match_all('/class="(vy-modal(?:\s[^"]*)?)"/', $src, $panels);
        expect($panels[1])->not->toBeEmpty();
        foreach ($panels[1] as $classes) {
            expect(str_contains($classes, 'vy-modal-card'))->toBeTrue($f.': the panel "'.$classes.'" is the see-through glass');
        }
    }
});

/*
 * ⚠️ DO-FRONTDESK-1 — the three axis-3 findings the ROLLED DEMO DATE surfaced (2026-08-14).
 *
 * They appeared because an arrival row began rendering (59 → 75 candidates). Confirmed real by recreating the
 * condition — and the condition needed no seeding at all: the demo has a genuine arrival today, so there was
 * no fixture to revert.
 *
 * ⚠️ CONFIRM-BEFORE-FIXING PAID OFF TWICE:
 *   · "2 guests" (4.41 in the original run) did NOT fail on re-measure — a BOUNDARY case, not a defect. It is
 *     fixed anyway, because it inherits from the same rule as "3 nights" which did fail.
 *   · Two NEW timeline labels reported 1.00 and 1.35 — both PLATE ARTEFACTS. Hand-reading the pixels showed
 *     the real plate under them is light cream, not the accent the sampler claimed: their small boxes contain
 *     an adjacent accent marker that captured the plate scan. On their TRUE plates the dot label measures
 *     5.63 (fine, no defect) — but the now-label measured 4.48, a REAL marginal miss. ⚠️ The finding was real
 *     while the NUMBER was wrong; discarding the artefact wholesale would have discarded a genuine defect.
 */
const FD1_TL_FOOT_PLATE = [245, 220, 204];     // the timeline footnote's row
const FD1_META_PLATE = [228, 202, 187];        // the arrival row's meta line
const FD1_NOWLABEL_PLATE = [251, 236, 225];    // hand-read, NOT the sampler's mis-identified accent

it('⚠️ AXIS 3 — the arrival-row text clears AA (it renders only when an arrival exists)', function () {
    $fd = (string) file_get_contents(resource_path('views/livewire/front-desk.blade.php'));
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // ⚠️ NON-VACUITY: the rules exist, so these are statements about their colour.
    expect($fd)->toContain('.vy-tl-foot {')->toContain('.vy-tl-nowlabel {')
        ->and($shell)->toContain('.vy-fd-meta {');

    expect($fd)->toMatch('/\.vy-tl-foot \{[^}]*color: var\(--vy-muted\)/')
        ->and($fd)->toMatch('/\.vy-tl-nowlabel \{[^}]*color: var\(--vy-accent-text\)/')
        ->and($shell)->toMatch('/\.vy-fd-meta \{[^}]*color: var\(--vy-on-grad\)/');

    // ⚠️ BOTH HALVES OF THE CASCADE. The "3 nights"/"2 guests" spans carry no colour of their own — they
    // INHERIT from .vy-fd-meta, whose shell rule AND a page inline both said --vy-muted. Fixing either alone
    // leaves the other winning (§N.21.6 / the DO-A11Y-5 inheritance pattern).
    expect($fd)->not->toContain('class="vy-fd-meta" style="margin-top: 4px; font-size: 12px; color: var(--vy-muted);"');

    expect(partBRatio([95, 88, 83], FD1_TL_FOOT_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([108, 100, 95], FD1_TL_FOOT_PLATE))->toBeLessThan(4.5)        // --vy-faint 4.41
        ->and(partBRatio([68, 59, 54], FD1_META_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([95, 88, 83], FD1_META_PLATE))->toBeLessThan(4.5)             // --vy-muted 4.48
        ->and(partBRatio([147, 42, 7], FD1_NOWLABEL_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(partBRatio([194, 65, 12], FD1_NOWLABEL_PLATE))->toBeLessThan(4.5);       // --vy-accent 4.48
});

it('⚠️ NOT VACUOUS — the arrival row really renders, so the guard above is about live markup', function () {
    // ⚠️ THE DATA-AXIS TRAP: with no arrival today these classes never reach the DOM and the assertions above
    // would be protecting markup nobody sees. The demo seed carries a genuine arrival, which is WHY the
    // findings surfaced at all.
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Property Owner');

    $html = (string) $this->actingAs($user)->get('/app/front-desk')->assertOk()->getContent();

    expect($html)->toContain('vy-fd-meta')->toContain('vy-tl-foot');
});

/*
 * ⚠️ DO-RESTORE-5 — THE AVATAR FAMILY IS FLAT, AND THE WHOLE FAMILY PASSES (2026-08-15).
 *
 * ⚠️ WHY FLATTENING WAS THE ONLY FIX. The family rode TWO gradients, and a gradient has two worst cases at
 * OPPOSITE ends: white fails at the light end, dark text at the dark end. DO-RESTORE-4 could therefore fix
 * only the peach copies (dark text, 4.73 — thin), and the --vy-grad copies were unfixable by ANY text colour
 * (white 1.67 at one end, ink 3.36 at the other). A FLAT plate has ONE value and no end to hug.
 *
 *   --vy-avatar-bg = #F0A57A, the wireframe peach's own light stop, so the avatars stay on-brand
 *   dark ink on it = 7.60 — robust, replacing the 4.32 the gradient left
 *   .vy-plan-tag took option (b): solid --vy-accent + white = 5.18, so "Pro" still reads as a BADGE
 *
 * ⚠️ SCOPE: only these surfaces stopped USING the CTA gradient. The gradient token itself is untouched and
 * still carries the restored wireframe orange, and every CTA still uses it (DO-RESTORE-1a, unchanged).
 */
const R5_AVATAR_BG = [240, 165, 122];      // the flat plate — ONE value, no worst end
const R5_INK = [43, 35, 32];
const R5_ACCENT = [194, 65, 12];           // the badge fill
const R5_OLD_GRAD_DARK = [203, 76, 22];    // the dark end that made the gradient unfixable

it('⚠️ DO-RESTORE-5 — every avatar copy is FLAT and robustly passing (§N.8f)', function () {
    $operator = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    $owner = (string) file_get_contents(resource_path('views/components/layouts/owner.blade.php'));
    $tokens = (string) file_get_contents(resource_path('views/components/design/tokens.blade.php'));

    // ⚠️ NON-VACUITY + named, not inlined: the concept has six copies and a bare hex would drift again.
    expect($tokens)->toContain('--vy-avatar-bg: #F0A57A');

    // ⚠️ ALL SIX avatar backgrounds — including .vy-owner-avatar, which DO-RESTORE-4 had to leave DECIDED
    // because it rode the gradient. It is flattened now, so it is a PASSING surface.
    foreach (['.vy-avatar', '.vy-thread-av', '.vy-arr-av', '.vy-owner-avatar'] as $cls) {
        expect($operator)->toMatch('/'.preg_quote($cls, '/').' \{[^}]*background: var\(--vy-avatar-bg\)/');
    }
    expect($owner)->toMatch('/\.vy-avatar \{[^}]*background: var\(--vy-avatar-bg\)/');

    // ⚠️ THE SIXTH COPY, and the one this pass nearly shipped past: `.vy-fd-av` is PAGE-LOCAL, so the two
    // earlier passes — which both searched the shells — never saw it. It carried the family's WORST pairing,
    // white on the peach gradient. §N.8f is not "check the other shell", it is "check everywhere".
    expect((string) file_get_contents(resource_path('views/livewire/front-desk.blade.php')))
        ->toMatch('/\.vy-fd-av \{[^}]*background: var\(--vy-avatar-bg\)/');

    // ⚠️ AND THE SWEEP THAT FOUND THE SEVENTH — this is not a hypothetical. Written to catch the next stray,
    // it immediately caught `.vy-convo-av` in the owner shell, which had escaped every earlier search by
    // writing the peach gradient as TOKENS (`var(--vy-peach)`) instead of a hex literal. It was UNUSED, and
    // was fixed rather than deleted: a dead rule is a live defect the moment someone uses it.
    //
    // ⚠️ THE GENERAL LESSON: a hand-search finds the copies you can imagine. This matches on the CONCEPT
    // (a class named like an avatar, painted with a gradient) and found the one nobody imagined.
    $strays = [];
    foreach ((new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')))) as $f) {
        if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.blade.php')) {
            continue;
        }
        foreach (preg_split('/\R/', (string) file_get_contents($f->getPathname())) ?: [] as $line) {
            if (preg_match('/\.vy-[\w-]*(av|avatar)\b[^{]*\{[^}]*linear-gradient/i', $line)) {
                $strays[] = $f->getFilename().': '.trim(substr($line, 0, 60));
            }
        }
    }
    expect($strays)->toBe([]);

    // ⚠️ ROBUST, not thin — that is the point of flattening. Measured on the page: .vy-avatar 7.60.
    expect(partBRatio(R5_INK, R5_AVATAR_BG))->toBeGreaterThanOrEqual(7.0)
        // and the gradient's dark end, which no text colour could clear, is simply gone from these surfaces.
        ->and(partBRatio(R5_INK, R5_OLD_GRAD_DARK))->toBeLessThan(4.5);
});

it('⚠️ the "Pro" badge took option (b) — a solid accent fill, so it still reads as a badge', function () {
    $operator = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    expect($operator)->toMatch('/\.vy-plan-tag \{[^}]*background: var\(--vy-accent\)/')
        ->and($operator)->toMatch('/\.vy-plan-tag \{[^}]*color: #fff/');

    // White on the solid accent is comfortably AA — unlike the gradient it replaced, where white hit 1.67 at
    // the light end. Option (a) (flat peach + ink) would have measured higher still but read as a muted chip.
    expect(partBRatio([255, 255, 255], R5_ACCENT))->toBeGreaterThanOrEqual(4.5);
});

it('⚠️ THE CTA GRADIENT IS UNTOUCHED — only the avatars stopped using it (DO-RESTORE-1a intact)', function () {
    $tokens = (string) file_get_contents(resource_path('views/components/design/tokens.blade.php'));
    $operator = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // ⚠️ THE SCOPE GUARD. Flattening the shared gradient token would have destroyed the founder's CTA restore.
    // The token keeps the wireframe orange, and the CTAs keep using it.
    expect($tokens)->toContain('--vy-grad: linear-gradient(140deg, #E8703A, #C2410C)')
        ->and(substr_count($operator, 'var(--vy-grad)'))->toBeGreaterThan(20);
});
