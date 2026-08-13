<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * DO-TOKEN-1 — THE CANONICAL PALETTE HAS ONE DEFINITION (2026-08-13).
 *
 * Four shells each re-declared the whole palette, so every shared token had four independent definitions and
 * any one could drift — which `--vy-amber-text` actually did (DO-EMAIL-4). The 31 shared tokens now live in
 * `components/design/tokens.blade.php`, included by every shell before its own <style>.
 *
 * ⚠️ THIS FILE READS THE RENDERED OUTPUT AND COMPUTES THE CASCADE, NOT THE SOURCE FILES. A test that grepped
 * the Blade source could pass while the browser resolved something else entirely — the include could land in
 * the wrong place, an override could be shadowed, a shell could stop including the base. So every assertion
 * below is made against `:root` blocks extracted from the RESPONSE, in document order, with last-declaration-
 * wins applied (§N.18.1: the check must be independent of the mechanism it checks).
 *
 * ⚠️ The values pinned here were captured from a real browser (`getComputedStyle`) BEFORE the refactor, on the
 * clean worktree. They are the no-visual-change contract: if any of them moves, something rendered differently.
 */

/** Every `:root` block in the response, in document order → effective value per token (last wins). */
function effectiveTokens(string $html): array
{
    preg_match_all('/:root\s*\{(.*?)\}/s', $html, $blocks);

    $effective = [];
    foreach ($blocks[1] as $block) {
        preg_match_all('/(--vy[\w-]*)\s*:\s*([^;]+);/', $block, $decls, PREG_SET_ORDER);
        foreach ($decls as $d) {
            $effective[$d[1]] = trim(preg_replace('/\s+/', ' ', $d[2]));
        }
    }

    return $effective;
}

/** How many times a token is DECLARED across the whole document — 1 is the point of this pass. */
function declarationCount(string $html, string $token): int
{
    preg_match_all('/:root\s*\{(.*?)\}/s', $html, $blocks);

    $n = 0;
    foreach ($blocks[1] as $block) {
        $n += preg_match_all('/'.preg_quote($token, '/').'\s*:/', $block);
    }

    return $n;
}

/** The OPERATOR shell, rendered through the real route as a role that can reach it. */
function shellHtml(object $test): string
{
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Property Owner');

    return (string) $test->actingAs($user)->get('/app/dashboard')->assertOk()->getContent();
}

it('⚠️ ONE DEFINITION — a shared token is declared exactly ONCE in the rendered document', function () {
    $html = shellHtml($this);

    // ⚠️ NON-VACUITY: the token must actually be there. An assertion that "it appears at most once" is
    // trivially true of a token that appears zero times.
    expect(declarationCount($html, '--vy-accent'))->toBe(1)
        ->and(declarationCount($html, '--vy-ink'))->toBe(1)
        ->and(declarationCount($html, '--vy-muted'))->toBe(1)
        ->and(declarationCount($html, '--vy-grad'))->toBe(1);

    // ⚠️ THE STRUCTURAL PAYOFF: the DO-EMAIL-4 vector is closed. A shell can no longer hold a second copy of
    // a shared token, because re-declaring one is exactly what this counts.
    $effective = effectiveTokens($html);
    foreach (['--vy-sans', '--vy-display', '--vy-bg', '--vy-faint', '--vy-hairline', '--vy-wash'] as $token) {
        expect(declarationCount($html, $token))->toBe(1, "shared token {$token} is declared more than once");
        expect($effective)->toHaveKey($token);
    }
});

it('⚠️ NO VISUAL CHANGE — the operator shell resolves every token to its pre-refactor value', function () {
    $effective = effectiveTokens(shellHtml($this));

    // Captured in-browser on the clean worktree, before the refactor.
    $expected = [
        '--vy-accent' => 'rgb(194, 65, 12)',
        '--vy-accent-2' => 'rgb(217, 102, 46)',
        '--vy-accent-3' => 'rgb(232, 112, 58)',
        '--vy-accent-border' => 'rgba(232, 112, 58, 0.28)',
        '--vy-amber' => 'rgb(178, 106, 8)',
        '--vy-amber-2' => 'rgb(224, 146, 30)',
        '--vy-faint' => 'rgb(108, 100, 95)',
        '--vy-glass' => 'rgba(255, 252, 248, 0.58)',
        '--vy-hairline' => 'rgba(120, 70, 40, 0.10)',
        '--vy-ink' => 'rgb(43, 35, 32)',
        '--vy-muted' => 'rgb(95, 88, 83)',
        '--vy-peach' => 'rgb(240, 165, 122)',
        '--vy-bg' => 'linear-gradient(135deg, #F6C89B 0%, #F1B896 32%, #EBD9CF 62%, #D9D6D2 100%)',
        '--vy-dark' => 'rgb(42, 33, 28)',
        '--vy-wash' => '#F6EBE2',
        '--vy-r-md' => '12px',
        '--vy-r-pill' => '30px',
    ];

    foreach ($expected as $token => $value) {
        expect($effective[$token] ?? null)->toBe($value, "{$token} moved — that is a visual change");
    }
});

it('⚠️ THE DELIBERATE OVERRIDES SURVIVE — the operator shell keeps its own values, not guest’s', function () {
    $effective = effectiveTokens(shellHtml($this));

    // Four of the seven are exactly where GUEST deviates; this shell must resolve to the consensus value,
    // proving the base is not carrying one shell's theming to another.
    expect($effective['--vy-r-lg'])->toBe('20px')          // guest is 18px
        ->and($effective['--vy-r-sm'])->toBe('10px')       // guest is 9px
        ->and($effective['--vy-glass-border'])->toBe('rgba(255, 255, 255, 0.75)')   // guest is warm brown
        ->and($effective['--vy-shadow-accent'])->toBe('rgba(194, 65, 12, 0.30) 0 4px 12px');   // guest is 0.28

    // ⚠️ AND THE OTHER TWO — `--vy-on-grad` is MEASURED PER SHELL against each shell's own gradient
    // (§N.4i/§N.4j). The operator value must be the operator's, not the owner's, or the refactor has
    // flattened a contrast decision into a wrong one.
    // ⚠️ CHANGED DELIBERATELY (DO-A11Y-2): the old value was derived against a PICKED gradient stop and
    // measured 4.33–4.48 on the plates its users actually sit on — a real AA failure. Re-derived by
    // sampling. This is the one token whose movement is intended; the rest of the contract stands.
    expect($effective['--vy-on-grad'])->toBe('rgb(68, 59, 54)')            // owner is rgb(76, 66, 60)
        ->and($effective['--vy-on-grad-soft'])->toBe('rgb(88, 78, 72)');   // owner is rgb(82, 73, 67)
});

it('⚠️ THE BASE IS INCLUDED BEFORE THE SHELL’S OWN STYLES — order is what makes overrides win', function () {
    $html = shellHtml($this);

    // The canonical block carries a marker comment no shell declares. If the include moved AFTER the shell's
    // styles, every per-shell override would be silently undone — so its POSITION is load-bearing, not
    // cosmetic, and is asserted rather than assumed.
    $basePos = strpos($html, '--vy-sans');
    $shellPos = strpos($html, '--vy-r-lg');

    expect($basePos)->not->toBeFalse()
        ->and($shellPos)->not->toBeFalse()
        ->and($basePos)->toBeLessThan($shellPos);
});
