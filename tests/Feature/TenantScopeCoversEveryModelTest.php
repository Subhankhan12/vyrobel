<?php

use App\Exceptions\TenantContextMissingException;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

// ══ AUDIT 2026-08-29 · H-5 — THE SUITE NAMED FIVE MODELS OUT OF NINETY-SEVEN ═════════════════════════
//
// ⚠️ A tenancy test on `unit_rate_overrides` (a table holding `price_minor`) was SKIPPED citing "the shared
// BelongsToOrganization suite". That suite — TenantIsolationTest — is 57 lines naming FIVE models, while
// NINETY-SEVEN carry the trait. The cited coverage did not exist. One table's missing test was the SYMPTOM;
// the hand-written list of five was the disease, because it goes stale silently as models are added.
//
// ⚠️ THIS SUITE IS DATA-DRIVEN: it DISCOVERS the models carrying the trait, so a model added tomorrow is
// covered tomorrow with no edit here. NO MODEL IS EXCLUDED to make it pass — excluding one would rebuild
// the exact defect (a skip with a false justification).
//
// ⚠️ WHAT THIS PROVES, AND WHAT IT DOES NOT.
//   PROVES  — every trait-carrying model REFUSES to query with no tenant context (the fail-closed
//             guarantee of OrganizationScope), on all 97, needing no fixtures.
//   DOES NOT — prove row-level cross-org invisibility for all 97; that needs a real row per model, and
//             only 21 of the 97 have a factory. TenantIsolationTest covers five that way, and
//             UnitRateOverrideTest now covers `unit_rate_overrides`. The remaining 76 would need
//             factories written — reported, deliberately not stubbed here.

/**
 * Every model class under app/Models that uses the tenancy trait — DISCOVERED, never listed.
 *
 * @return list<class-string<Model>>
 */
function tenantScopedModels(): array
{
    $models = [];

    foreach (File::allFiles(app_path('Models')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $class = 'App\\Models\\'.str_replace(
            ['/', '.php'], ['\\', ''],
            str_replace(app_path('Models').'/', '', $file->getPathname())
        );

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            continue;
        }

        if (in_array(BelongsToOrganization::class, class_uses_recursive($class), true)) {
            $models[] = $class;
        }
    }

    sort($models);

    return $models;
}

// ── 1. the discovery itself is not vacuous ──────────────────────────────────────────────────────────
// An empty list would make test 2 pass for the wrong reason — the defect class this file exists for.
it('discovers the tenant-scoped models rather than naming them', function () {
    expect(count(tenantScopedModels()))->toBeGreaterThan(90);   // 97 today, and it grows on its own
});

// ── 2. ⛔ EVERY tenant-scoped model FAILS CLOSED with no context — all 97, not five ─────────────────
it('throws for every tenant-scoped model when no organization context is set', function () {
    app(TenantContext::class)->forget();

    $fellOpen = [];

    foreach (tenantScopedModels() as $model) {
        try {
            $model::query()->count();
            $fellOpen[] = $model;          // returned rows with NO tenant context — a boundary that is absent
        } catch (TenantContextMissingException) {
            // correct: the scope refused
        }
    }

    expect($fellOpen)->toBe([],
        'These tenant-scoped models did NOT refuse a query with no organization context: '
        .implode(', ', $fellOpen));
});
