<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/*
 * DO-EMAIL-5 / DO-EMAIL-6 — THE ROLE-COVERAGE CONTRAST GUARD (2026-08-12).
 *
 * §N.14.1: a contrast pass is bounded by the ROLE it runs as. Every prior operator pass ran as the demo Hotel
 * Manager, which holds neither `users.manage` nor `settings.manage` — so the markup behind those gates was never
 * in the DOM to be measured. This file guards the failures that re-audit found, and it does so the only way that
 * means anything: ⚠️ **rendering AS A ROLE THAT PASSES THE GATE.** A contrast guard run as a role that cannot see
 * the markup is VACUOUS — it asserts nothing about markup it never received. That is the exact DO-EMAIL-2 trap.
 */

/** Relative luminance per WCAG 2.1. */
function rcLuminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    $f = static function (float $v): float {
        $v /= 255;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $f((float) hexdec(substr($hex, 0, 2)))
        + 0.7152 * $f((float) hexdec(substr($hex, 2, 2)))
        + 0.0722 * $f((float) hexdec(substr($hex, 4, 2)));
}

function rcRatio(string $ink, string $plate): float
{
    $a = rcLuminance($ink);
    $b = rcLuminance($plate);

    return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
}

it('⚠️ renders the invite modal AS a users.manage role — the markup a Hotel Manager pass never received', function () {
    $scenario = seedScenario(1);

    // ⚠️ NON-VACUITY, FIRST: the demo role every prior pass ran as genuinely CANNOT see this markup. If this
    // ever stops being true the guard below stops being a role-coverage guard and becomes an ordinary one.
    $hotelManager = operatorUser($scenario, [], 'Hotel Manager');
    $admin = operatorUser($scenario, [], 'Property Owner');

    // ⚠️ spatie teams: a permission check needs the team id, which production sets in middleware. Without this
    // both can() calls answer false and the "premise" assertions below would pass for the wrong reason.
    app(PermissionRegistrar::class)->setPermissionsTeamId($scenario['org']->getKey());
    expect($hotelManager->fresh()->can('users.manage'))->toBeFalse();
    expect($admin->fresh()->can('users.manage'))->toBeTrue();

    $asManager = (string) $this->actingAs($hotelManager)->get('/app/owners')->getContent();
    $asAdmin = (string) $this->actingAs($admin)->get('/app/owners')->getContent();

    // The gate really does hide it — this is the coverage bound, demonstrated rather than asserted in prose.
    expect(str_contains($asManager, 'vy-inv-lbl'))->toBeFalse('the Hotel Manager can see the invite markup — the premise of this guard is gone');
    expect(str_contains($asAdmin, 'vy-inv-lbl'))->toBeTrue();

    // …and in the markup only the privileged role receives, the label colour is the TOKEN, never the old
    // literal. ⚠️ Scoped to the RULE, not the document: the operator layout DOCUMENTS #9C9089 in a CSS comment
    // explaining its removal, and CSS comments ship — a document-wide scan fires on that prose (the trap, in a
    // guard written by someone who had just written about the trap).
    preg_match('/\.vy-inv-lbl\s*\{[^}]*\}/s', $asAdmin, $rule);
    expect($rule)->not->toBeEmpty('the .vy-inv-lbl rule is absent — this assertion would be vacuous');
    expect($rule[0])->toContain('var(--vy-muted)')
        ->and($rule[0])->not->toContain('#9C9089');
});

it('keeps every re-audited ink at AA on the plate it was measured on', function (string $ink, string $plate, float $floor, string $where) {
    expect(rcRatio($ink, $plate))->toBeGreaterThanOrEqual($floor, "{$where}: {$ink} on {$plate}");
})->with([
    // DO-EMAIL-5 — measured live as a Property Owner, invite modal forced open (anchor read 5.18).
    'invite labels' => ['#5F5853', '#F8F2ED', 4.5, 'invite modal labels (was #9C9089 = 2.80)'],
    'invite optional' => ['#6C645F', '#F8F3EF', 4.5, 'invite modal "· optional" (was #B7ADA4 = 2.00)'],
    // ⚠️ The storefront filter keys sit on the PEACH GRADIENT, not a card — which is why --vy-muted (3.98) also
    // fails there and only --vy-ink clears. The plate decides the token, not the precedent.
    'storefront filter key' => ['#2B2320', '#F2B794', 4.5, 'storefront filter keys on the gradient (was #9C9089 = 1.77)'],
    // DO-EMAIL-6 — the two standalone pages. Their TEXT already passed; the CTA did not.
    'feature-locked body' => ['#5F5853', '#F6EBE2', 4.5, 'feature-locked body copy'],
    'feature-locked accent' => ['#B4400F', '#F6EBE2', 4.5, 'feature-locked accent text'],
    'login-approval body' => ['#4A4038', '#F6EBE2', 4.5, 'login-approval body copy'],
    'login-approval amber' => ['#7a5200', '#F6EBE2', 4.5, 'login-approval amber note'],
    'standalone CTA' => ['#FFFFFF', '#C2410C', 4.5, 'the standalone CTAs, now SOLID (the gradient read 3.08 at its lighter stop)'],
]);

it('⚠️ DO-EMAIL-6 — neither standalone CTA rides the accent GRADIENT any more', function () {
    // White 14px on linear-gradient(140deg,#E8703A,#C2410C) measures 3.08 at the lighter stop. The repo's
    // settled fix for this construct is the solid accent (the guest .vy-btn carries the same note; 5.18).
    foreach ([
        'billing/feature-locked.blade.php',
        'auth/login-approval.blade.php',
    ] as $view) {
        $source = (string) file_get_contents(resource_path('views/'.$view));

        // Scoped to the BUTTON rule — the brand mark legitimately keeps the gradient (it carries no text).
        preg_match('/\.btn\s*\{[^}]*\}/s', $source, $m);
        expect($m)->not->toBeEmpty("no .btn rule found in {$view} — this assertion would be vacuous");
        expect($m[0])->not->toContain('linear-gradient')
            ->and($m[0])->toContain('#C2410C');
    }
});

it('leaves the two superseded greys nowhere in any view', function () {
    $hits = [];
    foreach ((array) glob(resource_path('views/**/*.blade.php')) as $file) {
        $code = (string) preg_replace('/\{\{--.*?--\}\}|\/\*.*?\*\//s', ' ', (string) file_get_contents($file));
        foreach (['#9C9089', '#B7ADA4'] as $dead) {
            if (str_contains($code, $dead)) {
                $hits[] = basename($file).' :: '.$dead;
            }
        }
    }

    // Comments are stripped first: several files DOCUMENT these values while explaining their removal, and a
    // guard that fired on its own documentation would be measuring prose.
    expect($hits)->toBe([]);
});
