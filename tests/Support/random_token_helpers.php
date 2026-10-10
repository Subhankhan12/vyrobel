<?php

declare(strict_types=1);

if (! function_exists('withoutRandomTokens')) {
    /**
     * A page's HTML with its RANDOM values blanked — the Livewire snapshot's checksum (a fresh sha256 on every render), the CSRF
     * token wherever the page carries it, ULIDs and signed-link signatures — so a check that a fixed number is ABSENT cannot fail
     * on a coincidence.
     *
     * 🔬 Measured 2026-10-10: "4242" (the test card number three tests keep off their pages) sat inside a Livewire checksum once in
     * 300 renders of /owner/settings — and GitHub's run 38010017082 drew it, failing a docs-only commit.
     * ⚠️ Only the random VALUES go. The snapshot's data stays: a number leaking through a component's property is still caught, and
     * so is one in any other attribute or in the text (RandomTokenHelperTest).
     */
    function withoutRandomTokens(string $html): string
    {
        return (string) preg_replace([
            '/(checksum(?:&quot;|")\s*:\s*(?:&quot;|"))[0-9a-f]{64}/',
            '/(name="_token"\s+value=")[^"]*"/',
            '/(<meta name="csrf-token" content=")[^"]*"/',
            '/(data-csrf=")[^"]*"/',
            '/("csrf":")[^"]*"/',
            '/([?&](?:amp;)?signature=)[0-9a-f]{64}/',
            '/\b[0-7][0-9a-hjkmnp-tv-z]{25}\b/',   // ULIDs, lower-case Crockford as this app stores them
        ], ['$1', '$1"', '$1"', '$1"', '$1"', '$1', '{ulid}'], $html);
    }
}
