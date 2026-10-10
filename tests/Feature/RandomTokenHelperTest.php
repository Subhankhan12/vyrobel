<?php

declare(strict_types=1);

/**
 * ⚠️ `withoutRandomTokens()` (tests/Support/random_token_helpers.php) — the absence checks for short numbers ("4242", a test card
 * number; an IBAN's tail; a wireframe's invented "369") read a page past its RANDOM values. 🔬 2026-10-10: a Livewire checksum held
 * "4242" once in 300 renders of /owner/settings, and GitHub's run 38010017082 failed a docs-only commit on it. These prove the helper
 * blanks the random values and NOTHING the checks are there to catch — the snapshot's data included.
 */
it('blanks a Livewire checksum, the CSRF token, a signature and ULIDs — where a random "4242" can hide', function () {
    $html = '<div wire:snapshot="{&quot;data&quot;:{&quot;tab&quot;:&quot;fees&quot;},&quot;checksum&quot;:&quot;'
        .'364227b9310cd87691ae0d9e4242564647b8618fd328d375cc39bae790a3cced&quot;}" wire:id="x">'
        .'<meta name="csrf-token" content="ab4242cd"><input type="hidden" name="_token" value="zz4242yy" autocomplete="off">'
        .'<script data-csrf="q4242q">window.livewireScriptConfig = {"csrf":"w4242w"};</script>'
        .'<a href="/invite?expires=1&amp;signature=4242c0ffee4242c0ffee4242c0ffee4242c0ffee4242c0ffee4242c0ffee4242">join</a>'
        .'<a href="/owner/units/01m4h4vsf5ksqhjm4242ech3b3">unit</a></div>';

    expect(withoutRandomTokens($html))->not->toContain('4242')
        ->and(withoutRandomTokens($html))->toContain('&quot;tab&quot;:&quot;fees&quot;');
});

it('keeps every place a leaked number could appear — the text, other attributes, and a component\'s own data', function () {
    $snapshot = '<div wire:snapshot="{&quot;data&quot;:{&quot;last4&quot;:&quot;0154&quot;},&quot;checksum&quot;:&quot;'
        .str_repeat('a', 64).'&quot;}"></div>';

    expect(withoutRandomTokens('<p>Card •••• 4242</p>'))->toContain('4242')
        ->and(withoutRandomTokens('<input name="card" placeholder="4242 4242 4242 4242">'))->toContain('4242')
        ->and(withoutRandomTokens('<span data-last4="4242"></span>'))->toContain('4242')
        // A number held in a Livewire property reaches the browser in the snapshot: still caught.
        ->and(withoutRandomTokens($snapshot))->toContain('0154')
        // A long run of digits is not a ULID.
        ->and(withoutRandomTokens('<p>PT50000201231234567890154</p>'))->toContain('0154');
});
