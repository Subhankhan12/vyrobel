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
    // ⚠️ THE TWO SHELLS USE DIFFERENT WIREFRAME BACKGROUNDS, and that is original, not drift: the operator
    // avatar is the peach gradient, the owner avatar rides `--vy-grad`.
    expect($operator)->toMatch('/\.vy-avatar \{[^}]*background:\s*linear-gradient\(140deg, #F0A57A, #D9662E\)/')
        ->and($owner)->toMatch('/\.vy-avatar \{[^}]*background:\s*var\(--vy-grad\)/');

    // ⚠️ THE PROTECTION: the AA-clearing background DO-A11Y-1 shipped must not reappear on either copy — its
    // return means someone "fixed" a contrast report and reverted the founder's decision.
    foreach ([$owner, $operator] as $shell) {
        expect($shell)->not->toMatch('/\.vy-avatar \{[^}]*background:\s*linear-gradient\(140deg, #C2410C, #9A3412\)/');
    }

    // ⚠️ ASSERTED OPENLY AS SUB-AA — this is the WORST reading in the product, and it is deliberate.
    // White on the operator avatar's light stop #F0A57A is 2.03 against a 4.5 bar; it does not even reach the
    // 3:1 LARGE-text bar, and the initials are 11px/500 so 4.5 is what applies. ACCEPTED per DO-RESTORE-3,
    // with the number surfaced to the founder — not discovered later as a defect.
    expect(partBRatio([255, 255, 255], [240, 165, 122]))->toBeLessThan(3.0)
        ->and(partBRatio([255, 255, 255], [240, 165, 122]))->toBeGreaterThan(1.9);

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

        // ⚠️ non-vacuity per file: it really does build a scrim modal. (No second argument — Pest's
        // toContain() takes MULTIPLE NEEDLES, so a "message" there is silently searched for as text and the
        // guard fails for the wrong reason. It did, once.)
        expect($src)->toContain('rgba(43,35,32,.4)');
        $total += substr_count($src, 'vy-modal-card');
    }

    expect($total)->toBeGreaterThanOrEqual(11);
});
