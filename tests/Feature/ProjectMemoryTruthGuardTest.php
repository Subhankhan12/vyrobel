<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ `CLAUDE.md` IS WHAT EVERY SESSION READS FIRST, SO A FALSE LINE IN IT IS NOT A STALE COMMENT — IT IS AN
 * INSTRUCTION TO BELIEVE SOMETHING UNTRUE, AND IT WILL BE BELIEVED.
 *
 * The audit that produced this guard found the file had claimed since its BOOTSTRAP COMMIT (2026-07-02,
 * unchanged through 86 later commits) that the product ships "guest/staff PWAs". It ships none: no service
 * worker anywhere in the tree, and no `rel="manifest"` in any view. It also claimed a PHP floor of `^8.3`
 * against a `composer.json` requiring `^8.4.1`, and named a scheduled-command count that had moved.
 *
 * ── ⚠️⚠️ ONLY TWO CLASSES OF CLAIM CAN BE GUARDED HONESTLY, AND THIS GUARDS EXACTLY THOSE ────────────────
 *
 *   1. **A path it points at must exist.** A pointer that rots is the same failure as a false fact: the
 *      reader follows it, finds nothing, and cannot tell whether the fact or the path is wrong.
 *   2. **A version it quotes must match `composer.json`.** These are copied by hand and drift silently —
 *      the `^8.3` / `^8.4.1` disagreement is exactly this shape.
 *
 * ⚠️⚠️ EVERYTHING ELSE IN THAT FILE IS NOT HONESTLY GUARDABLE, AND SAYING SO IS PART OF THE RESULT.
 * "The architecture is multi-tenant", "the product is complete", "~89 screens", an invariant range — none
 * has a machine-checkable referent. A guard asserting them would either be vacuous or would encode one
 * reader's interpretation as a fact, which is the defect it is meant to prevent.
 *
 * ⚠️ AND THE COUNTS ARE NOT GUARDED BECAUSE THEY WERE REMOVED RATHER THAN PINNED. A count in prose has to be
 * re-verified by hand forever; a pointer to `route:list`, `schedule:list` or `config/authorization.php`
 * cannot go stale. Where the audit found a count, it replaced it with the command that answers it.
 */
uses(DatabaseTransactions::class);

it('⚠️ every docs/ path CLAUDE.md points at exists', function () {
    $source = file_get_contents(base_path('CLAUDE.md'));

    preg_match_all('#docs/[A-Za-z0-9/._-]+\.md#', $source, $matches);
    $paths = array_values(array_unique($matches[0]));

    // ⚠️ NOT VACUOUS: the file genuinely points at a couple of dozen documents. A regex that silently
    //    matched nothing would make this test pass while checking nothing — the failure mode this project
    //    has hit repeatedly.
    expect(count($paths))->toBeGreaterThan(15);

    $missing = array_values(array_filter($paths, fn (string $p): bool => ! file_exists(base_path($p))));

    Assert::assertSame([], $missing, 'CLAUDE.md points at documents that do not exist: '.implode(', ', $missing));
});

it('⚠️ the framework versions CLAUDE.md quotes match composer.json', function () {
    $source = file_get_contents(base_path('CLAUDE.md'));
    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
    $require = (array) ($composer['require'] ?? []) + (array) ($composer['require-dev'] ?? []);

    // The packages CLAUDE.md pins by name and constraint in its "Stack (locked)" block. ⚠️ `php` is here
    // deliberately: the file quoted `^8.3` while composer required `^8.4.1`, which is the drift that
    // prompted this guard and the kind nobody notices because both look plausible.
    $pinned = ['php', 'laravel/framework', 'livewire/livewire', 'laravel/horizon',
        'spatie/laravel-permission', 'pestphp/pest', 'stripe/stripe-php', 'barryvdh/laravel-dompdf'];

    // ⚠️ THE RULE IS DELIBERATELY SIMPLE: the constraint composer ACTUALLY REQUIRES must appear somewhere in
    //    the file. A first draft demanded it wrapped alone in backticks and matched only three of eight,
    //    because the file writes `stripe/stripe-php ^20.3` as one span — the guard was measuring its own
    //    formatting rather than the fact.
    $drifted = [];
    $checked = 0;

    foreach ($pinned as $package) {
        $constraint = (string) ($require[$package] ?? '');

        if ($constraint === '') {
            continue;
        }

        $checked++;

        if (! str_contains($source, $constraint)) {
            $drifted[] = sprintf('%s: composer.json requires %s, which CLAUDE.md does not state', $package, $constraint);
        }
    }

    // ⚠️ NON-VACUITY FLOOR — a matcher that stopped resolving packages would pass by checking none of them,
    //    which is indistinguishable from a clean result.
    expect($checked)->toBe(count($pinned));

    Assert::assertSame([], $drifted, implode("\n", $drifted));
});

it('⚠️⚠️ the product ships no service worker and no web-app manifest — verified by LOOKING', function () {
    // ⚠️ THE CLAIM THAT STARTED THE AUDIT. It is asserted here against the tree rather than against the
    //    report that found it, because a report is REPORTED and a file listing is measured.
    //
    // ⚠️ THIS GUARD DOES NOT FORBID BUILDING A PWA. If one is ever built this test fails, and that failure
    //    is the reminder to update CLAUDE.md in the same change — which is the whole defect it addresses.
    $serviceWorkers = [];
    foreach (['resources', 'public'] as $dir) {
        $path = base_path($dir);
        if (! is_dir($path)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $name = strtolower($file->getFilename());
            if ($name === 'sw.js' || str_starts_with($name, 'service-worker') || str_starts_with($name, 'workbox')) {
                $serviceWorkers[] = $file->getPathname();
            }
        }
    }

    expect($serviceWorkers)->toBe([]);

    // And no view links a web-app manifest. (`public/build/manifest.json` is Vite's ASSET manifest and is a
    // different thing entirely — it is not linked with rel="manifest" and does not make a page installable.)
    $linked = [];
    $views = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));
    $scanned = 0;
    foreach ($views as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $scanned++;
        if (preg_match('/rel=["\']manifest["\']/', (string) file_get_contents($file->getPathname())) === 1) {
            $linked[] = $file->getPathname();
        }
    }

    // ⚠️ Non-vacuity again: prove the scan actually read views.
    expect($scanned)->toBeGreaterThan(50)
        ->and($linked)->toBe([]);
});
