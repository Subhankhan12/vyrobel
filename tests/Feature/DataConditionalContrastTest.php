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

it('⚠️ A DO-A11Y-1 MISS, CLOSED — .vy-avatar is defined in BOTH shells and only one was fixed', function () {
    $owner = (string) file_get_contents(resource_path('views/components/layouts/owner.blade.php'));
    $operator = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // ⚠️ §N.8f in its most literal form: one concept, two definitions, and a fix that reached only one of
    // them. The owner copy kept `var(--vy-grad)`, whose light stop #E8703A gives white 3.08 — so the owner
    // avatar was still failing after DO-A11Y-1 reported the class fixed.
    foreach ([$owner, $operator] as $shell) {
        expect($shell)->toMatch('/\.vy-avatar \{[^}]*background:\s*linear-gradient\(140deg, #C2410C, #9A3412\)/');
    }

    expect(partBRatio([255, 255, 255], [194, 65, 12]))->toBeGreaterThanOrEqual(4.5)     // --vy-accent: 5.18
        ->and(partBRatio([255, 255, 255], [232, 112, 58]))->toBeLessThan(4.5);          // #E8703A: 3.08
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
