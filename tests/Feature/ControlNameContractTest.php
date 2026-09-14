<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ EVERY FORM CONTROL NEEDS AN ACCESSIBLE NAME — and 155 of them get it from a WRAPPING LABEL.
 *
 * ⚠️ WHY THE COUNT KEPT MOVING. A scan that looks only for `for`/`id` and `aria-label` reports a wrapping
 * `<label><input></label>` as broken. There are **155** of those here, so such a scan would have claimed
 * 241 unnamed controls where the truth was 86. The census this guard uses recognises all four means the
 * accessible-name computation actually uses.
 *
 * ⚠️⚠️ WHAT IT PROVES AND WHAT IT CANNOT: that an association EXISTS. **Not** that the name is a good one —
 * this pass proved that the hard way by briefly shipping a VISIBLE label reading "0.00", promoted from a
 * placeholder that was a format hint rather than a name. A guard cannot read; that judgement is recorded
 * per control in `docs/CONTROL-NAMES.md`.
 */
it('⚠️⚠️ leaves no form control without an accessible name, beyond the recorded remainder', function () {
    $census = vyControlCensus();
    $unnamed = $census['unnamed'];

    // ⚠️ A CEILING, NOT AN ALLOW-LIST OF NAMES. 35 controls remain unnamed and every one is recorded in the
    //    report with its reason — 29 carry no authored text to promote, and the rest could not be anchored
    //    uniquely or sit inside a `@foreach` where minting an id would duplicate it. This guard stops that
    //    number GROWING; it does not bless the remainder.
    //    ⚠️ 42, and the last figure was checked rather than bumped: it is 41 plus the `unit-price-form`
    //    amount field, whose minted id was REMOVED when a mis-wire was undone — a label reading
    //    "What to do" had been pointed at a price input. Re-deriving confirmed the delta before this
    //    ceiling moved; a number raised to green a gate is the thing this project refuses.
    Assert::assertLessThanOrEqual(42, count($unnamed),
        'the number of unnamed form controls grew. Every new control needs a name by one of the four valid '
        .'means — and the name must be authored text, never a format hint: a label reading "0.00" is worse '
        .'than none. Unnamed now: '.count($unnamed));

    // ⚠️ NO DANGLING NAMES. An `aria-labelledby` pointing at a missing id looks correct and names nothing.
    $dangling = array_filter($unnamed, fn (array $c): bool => str_contains((string) ($c['how'] ?? ''), 'DANGLING'));
    Assert::assertSame([], array_values($dangling), 'an aria-labelledby points at an id that does not exist');
});

it('⚠️ recognises all four valid associations — a scan missing wrapping labels reports correct controls as broken', function () {
    $by = [];
    foreach (vyControlCensus()['named'] as $c) {
        $by[$c['how']] = ($by[$c['how']] ?? 0) + 1;
    }

    // ⚠️ NON-VACUITY, and the specific blind spot: if wrapping-label detection ever breaks, this file's
    //    first assertion would suddenly "find" 155 new offenders and someone would go fix correct markup.
    Assert::assertGreaterThan(100, $by['wrapping label'] ?? 0,
        'wrapping-label detection is finding almost nothing — the census is about to report correct controls as broken');
    Assert::assertGreaterThan(200, $by['label[for]'] ?? 0);
    expect(array_keys($by))->toContain('aria-label');
});

it('⚠️ excludes only what genuinely needs no name, each with its reason', function () {
    $census = vyControlCensus();

    // ⚠️ THE DELIBERATE THREE ARE UNTOUCHED AND MUST STAY SO: naming the password-manager decoy defeats it,
    //    and a named honeypot is one a bot can read.
    $reasons = array_unique(array_column($census['excluded'], 'why'));

    foreach ($reasons as $why) {
        Assert::assertNotSame('', trim($why), 'an exclusion carries no reason');
    }

    expect(implode(' | ', $reasons))->toContain('password-manager decoy')->toContain('honeypot');

    // ⚠️ A floor, so a broken walk cannot pass every assertion built on this census.
    Assert::assertGreaterThan(400, count($census['named']));
    Assert::assertGreaterThan(200, count($census['excluded']));
});
