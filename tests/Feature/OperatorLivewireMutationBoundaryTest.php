<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;

uses(RefreshDatabase::class);

// OPERATOR FIX 3 (B) — the operator counterpart to OwnerCapabilityMutationBoundaryTest.
//
// ⚠️⚠️ WHY THIS GUARD IS THE POINT OF THE WHOLE OPERATOR FIX SERIES. Invariant #30 puts authorization on the
// ROUTE, not in the service — a model that is only sound if EVERY write is routed. `/livewire/update` does not
// run route middleware, so a Livewire action that persists anything is a write path no `can:` gate can see.
//
// Audit run 34 MEASURED the property across the whole operator realm: 0 of 37 components expose a public
// mutating action, and every operator write is a controller-routed <form>. That measurement is what bounds
// OP-FIX-1's five findings to MISLEADING UI rather than privilege escalation — a control rendered for the
// wrong role still posts to a gated route.
//
// ⚠️ But the property was UNPINNED. One future `public function save()` on any operator component silently
// creates a write path route middleware never sees, and every one of those affordance defects becomes a real
// bypass — with nothing failing to announce it. This test is what makes that impossible to do quietly.

/** Livewire's own lifecycle surface — declaring one of these is not exposing an action. */
const OLB_LIFECYCLE = [
    'render', 'mount', 'boot', 'booted', 'hydrate', 'dehydrate', 'rendering', 'rendered',
    'updating', 'updated', 'rules', 'messages', 'validationAttributes', 'placeholder', 'exception',
];

/**
 * Every operator-realm Livewire component class (the owner and guest realms have their own guards).
 *
 * @return list<class-string>
 */
function olbOperatorComponents(): array
{
    $out = [];
    $base = app_path('Livewire');

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $relative = str_replace([$base.DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
        // ⚠️ Owner and Guest are covered by their own realm guards; this one is the operator realm.
        if (str_starts_with($relative, 'Owner'.DIRECTORY_SEPARATOR) || str_starts_with($relative, 'Guest'.DIRECTORY_SEPARATOR)) {
            continue;
        }
        $class = 'App\\Livewire\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        if (class_exists($class) && is_subclass_of($class, Component::class)) {
            $out[] = $class;
        }
    }

    sort($out);

    return $out;
}

/** The public methods a component DECLARES ITSELF (inherited framework methods are not its surface). */
function olbDeclaredPublics(string $component): array
{
    return collect((new ReflectionClass($component))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $m): bool => $m->getDeclaringClass()->getName() === $component)
        ->map(fn (ReflectionMethod $m): string => $m->getName())
        ->reject(fn (string $n): bool => str_starts_with($n, '__'))          // magic / constructor
        ->reject(fn (string $n): bool => str_starts_with($n, 'mountResolves')) // trait mount hook
        ->values()
        ->all();
}

// ── 1. ⚠️⚠️ THE PROBE — the sweep can SEE a public method, so its zero means something ─────────────────
it('finds the operator components and their real public methods before trusting any absence', function () {
    $components = olbOperatorComponents();

    // ⚠️ A guard that scans nothing passes trivially. Run 34 measured 37 operator components; the floor here
    // is deliberately below that so adding or moving a component does not red this for the wrong reason,
    // while a sweep that silently found NOTHING still fails loudly.
    expect(count($components))->toBeGreaterThan(25);

    // ⚠️ AND IT MUST FIND ACTUAL METHODS. If the reflection were broken, every component would look
    // action-free and test 2 would pass while proving nothing — the exact vacuous-guard failure this audit
    // hit repeatedly. `render` is declared by every component, so its absence would mean the sweep is blind.
    $allPublics = collect($components)->flatMap(fn (string $c): array => olbDeclaredPublics($c));

    expect($allPublics)->toContain('render')
        ->and($allPublics->filter(fn (string $m): bool => $m === 'render')->count())->toBeGreaterThan(25)
        ->and($allPublics)->toContain('mount');
});

// ── 2. ⚠️⚠️ THE GUARD — no operator component exposes a wire-callable action ───────────────────────────
it('exposes no mutating Livewire action anywhere in the operator realm', function () {
    $offenders = [];

    foreach (olbOperatorComponents() as $component) {
        $extra = array_values(array_diff(olbDeclaredPublics($component), OLB_LIFECYCLE));
        if ($extra !== []) {
            $offenders[$component] = $extra;
        }
    }

    // ⚠️ THE MESSAGE MATTERS AS MUCH AS THE ASSERTION. Whoever reds this will be adding a perfectly ordinary
    // Livewire action, and needs to know why that is a security decision and not a style preference.
    $detail = implode("\n", array_map(
        fn (string $c, array $m): string => "  {$c} exposes: ".implode(', ', $m),
        array_keys($offenders),
        $offenders,
    ));

    $why = "\n\n  A public method on an operator Livewire component is callable via /livewire/update, which"
        ."\n  does NOT run route middleware. If it persists anything it is an UNGATED write path that"
        ."\n  invariant #30's route-level authorization cannot see. Route the write through a controller"
        ."\n  with a `can:` gate instead — that is how all 116 operator writes already work.";

    expect($offenders)->toBe([], $detail.$why);
});
