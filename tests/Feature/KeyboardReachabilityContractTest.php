<?php

declare(strict_types=1);

/**
 * ⚠️ THE KEYBOARD-REACHABILITY CONTRACT (docs/KEYBOARD-SURFACES.md).
 *
 * The class: AN ELEMENT WITH A CLICK HANDLER THAT NO KEYBOARD CAN ACTIVATE. Two were reported — a
 * maintenance work order and the owner action centre, the second holding rate proposals and expense
 * approvals — and sweeping the class found a third nobody had named (a housekeeping lane header).
 *
 * ⚠️ EVERY EXEMPTION CARRIES A REASON, AND MOST CARRY A BACKING STRING. An allow-list of bare paths rots
 * into a list of things nobody rechecks. Each entry below says WHY it is allowed, and where the reason is
 * "there is a real control inside doing the same job", it names that control — so DELETING THE CONTROL
 * DELETES THE EXEMPTION and the row goes red. The exemption cannot outlive the fix it describes.
 */
const VY_KEYBOARD_ALLOWED = [
    [
        'file' => 'resources/views/livewire/owner/units.blade.php',
        'match' => 'vy-mu-ac-head',
        'reason' => 'Mouse shortcut. The head keeps its whole-area click as a convenience; the real control '
            .'is the Expand/Collapse button inside it, which carries aria-expanded and aria-controls.',
        'requires' => '<button type="button" class="vy-mu-ac-toggle"',
    ],
    [
        'file' => 'resources/views/livewire/maintenance-list.blade.php',
        'match' => 'vy-unit-row',
        'reason' => 'Mouse shortcut. The row keeps its whole-area click; the work order opens from a real '
            .'button on the title, which mirrors the row in both normal and select mode.',
        'requires' => 'class="vy-um-name vy-rowopen"',
    ],
    [
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'match' => 'x-show="rt"',
        'reason' => 'Backdrop dismissal. The click closes an overlay a keyboard closes with the round close '
            .'button inside it. ⚠️ THIS OVERLAY HAS NO ESCAPE HANDLER and carries no role/aria-modal, so it '
            .'is not one of the 37 contract dialogs — REPORTED, not fixed here (out of this pass\'s scope).',
        'requires' => '@click="rt = false"',
    ],
    [
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'match' => 'x-show="mr"',
        'reason' => 'Backdrop dismissal, same shape and the same missing Escape as the rt overlay above.',
        'requires' => '@click="mr = false"',
    ],
    [
        'file' => 'resources/views/livewire/owner/units.blade.php',
        'match' => 'vy-mu-day',
        'reason' => '⚠️ AN OPEN DEFECT, DEFERRED IN THE OPEN — not a pass. The occupancy heat-map is 28-31 '
            .'day cells selecting a footer readout. Making each a button is 31 new tab stops between the '
            .'calendar and everything after it: technically reachable, practically unusable. It needs a '
            .'roving-tabindex grid model (one stop, arrow keys within), which docs/KEYBOARD-SURFACES.md '
            .'specifies and this pass was scoped not to build.',
        'requires' => null,
    ],
    [
        // 2026-09-29 — the operator's searchable dropdown (first used for the time zone)
        'file' => 'resources/views/components/operator/combobox.blade.php',
        'match' => 'role="option"',
        'reason' => 'ARIA listbox pattern. The options are not tab stops by design: the keyboard drives them from '
            .'the search box — ↑/↓ move the active option, Enter picks it, Esc closes — which points at the active '
            .'option with aria-activedescendant so a screen reader announces it. The click is the mouse path to '
            .'the same pick. Remove the search box\'s Enter handler and this exemption goes red.',
        'requires' => '@keydown.enter.prevent="pick(shown[active])"',
    ],
];

it('has no click handler a keyboard cannot reach, outside the reasoned allow-list', function () {
    $unexplained = [];

    foreach (vyUnreachableClickHandlers() as $hit) {
        $allowed = false;

        foreach (VY_KEYBOARD_ALLOWED as $entry) {
            if ($hit['file'] !== $entry['file'] || ! str_contains($hit['snippet'], $entry['match'])) {
                continue;
            }

            // ⚠️ THE EXEMPTION MUST STILL BE TRUE. Where the reason is "a real control does this job",
            //    that control has to still be in the file — otherwise the fix was deleted and the
            //    allow-list would quietly keep vouching for a defect that came back.
            if ($entry['requires'] !== null) {
                $source = file_get_contents(base_path($entry['file']));

                $this->assertStringContainsString(
                    $entry['requires'],
                    $source,
                    "The keyboard path this exemption rests on is gone from {$entry['file']}: {$entry['reason']}"
                );
            }

            $allowed = true;
            break;
        }

        if (! $allowed) {
            $unexplained[] = "{$hit['file']}:{$hit['line']} <{$hit['tag']}> {$hit['snippet']}";
        }
    }

    expect($unexplained)->toBe([],
        'A click handler no keyboard can activate. Give it a real control, make the element itself '
        ."interactive, or add a reasoned allow-list entry:\n".implode("\n", $unexplained));
});

it('actually detects the shape it is guarding', function () {
    // ⚠️ THE NON-VACUITY FLOOR. A census that silently stopped matching would report "nothing
    //    unreachable" and read exactly like success. These four probes pin the classifier's behaviour:
    //    it must FLAG a bare handler on a div, and must NOT flag the three legitimate shapes.
    $flag = fn (string $html): bool => collect(vyKeyboardClickTags($html))
        ->contains(fn (array $t): bool => ! $t['dismissal'] && ! $t['hasKeyboardPath'] && $t['tag'] === 'div');

    expect($flag('<div @click="go()">x</div>'))->toBeTrue('a bare click handler on a div must be flagged');
    expect($flag('<div @click.self="go()">x</div>'))->toBeFalse('a backdrop dismissal must not be flagged');
    expect($flag('<div @click="go()" @keydown.enter="go()" tabindex="0" role="button">x</div>'))
        ->toBeFalse('an element with its own keyboard path must not be flagged');

    // ⚠️ Blade expressions legitimately contain `>` and `=>`, which is why the scanner walks the tag
    //    rather than matching `<div[^>]*>`. If it regressed to the regex, this tag would end early.
    expect($flag('<div title="{{ $a > $b ? \'x\' : \'y\' }}" @click="go()">x</div>'))
        ->toBeTrue('the tag walker must survive a > inside an attribute value');
});

it('reads what runs, not what is written about it', function () {
    // ⚠️ THE TRAP THAT HAS FIRED REPEATEDLY IN THIS PROJECT: a source-scanning guard matching the prose
    //    written ABOUT its own rule. Both comment dialects and <style>/<script> are stripped first — and
    //    this file, the helper's docblock and the allow-list above are all full of the literal needle.
    $commented = <<<'BLADE'
    {{-- a div with @click="go()" and no keyboard path, described in a comment --}}
    <!-- <div @click="go()">x</div> -->
    <script>el.addEventListener('click', go)</script>
    <style>/* @click */ .x { color: red }</style>
    BLADE;

    expect(vyKeyboardClickTags(vyKeyboardStrip($commented)))->toBe([]);

    // And the line numbering survives the strip, or every reported location would be wrong.
    $withComment = "{{-- @click --}}\n\n<div @click=\"go()\">x</div>";
    $tags = vyKeyboardClickTags(vyKeyboardStrip($withComment));

    expect($tags)->toHaveCount(1)
        ->and($tags[0]['line'])->toBe(3);
});

it('gives every control it added a focus ring the class-scoped rule actually reaches', function () {
    // ⚠️⚠️ NEITHER SHELL HAS A UNIVERSAL FOCUS RULE. Both name specific classes — `.vy-btn`,
    //    `.vy-nav-item`, `.vy-icobtn`, `.vy-pill`, `.vy-period-btn` — and reach nothing else. That is how
    //    the app audit found 3 of 35 stops focusable but INVISIBLE, and a control that can be focused
    //    invisibly is unusable by keyboard even when it works. Every class added for a keyboard path must
    //    therefore be named in the rule, and named in the SAME rule so the ring has one definition.
    //    Measured in a browser at 2px solid rgb(194,65,12) on rgb(255,252,248) — 5.06:1, above the 3:1
    //    non-text minimum.
    $operator = file_get_contents(base_path('resources/views/components/layouts/operator.blade.php'));
    $owner = file_get_contents(base_path('resources/views/components/layouts/owner.blade.php'));

    foreach (['.vy-rowopen:focus-visible', '.vy-detail-head:focus-visible'] as $needle) {
        $this->assertStringContainsString($needle, $operator,
            "The operator shell's focus rule no longer reaches {$needle} — the control is focusable but invisible.");
    }

    $this->assertStringContainsString('.vy-mu-ac-toggle:focus-visible', $owner,
        "The owner shell's focus rule no longer reaches .vy-mu-ac-toggle — the control is focusable but invisible.");
});

it('keeps the work-order checkbox independent of the row that opens the work order', function () {
    // ⚠️⚠️ TICKING MUST NOT OPEN, AND OPENING MUST NOT TICK. The checkbox drives a BULK selection, so an
    //    accidental activation is a real consequence and not a nuisance. Two things hold that apart, and
    //    both are asserted because losing either silently re-couples them:
    //      · the checkbox stops the click reaching the row (which would open the work order), and
    //      · the title button stops ITS click reaching the row (which would act on it twice).
    //    Proven by keyboard in a browser: Space on the checkbox selected the row (1 selected, row marked)
    //    and opened NOTHING; Enter on the title opened the work order and moved focus into its dialog.
    $view = file_get_contents(base_path('resources/views/livewire/maintenance-list.blade.php'));

    $this->assertMatchesRegularExpression(
        '/<input type="checkbox" class="vy-mt-check"[^>]*@click\.stop/',
        $view,
        'The bulk-select checkbox no longer stops its click reaching the row — ticking it would also open the work order.'
    );

    $this->assertMatchesRegularExpression(
        '/class="vy-um-name vy-rowopen"[^>]*@click\.stop/s',
        $view,
        'The title opener no longer stops its click reaching the row — the row handler would fire on top of it.'
    );

    // And the checkbox keeps its own accessible name — a row-selection checkbox with none announces nothing.
    expect($view)->toContain('aria-label="Select {{ $row[\'title\'] }}"');
});
