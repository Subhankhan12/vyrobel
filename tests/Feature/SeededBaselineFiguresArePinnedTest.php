<?php

declare(strict_types=1);

use App\Support\Tenancy\TenantContext;
use Database\Seeders\BillingPlanSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformAdminSeeder;
use Database\Seeders\RoleCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ TENANT ONE'S BASELINE FIGURES, PINNED IN THE SUITE — FIX 53.
 *
 * ⚠️⚠️ WHY THIS EXISTS, AND IT IS NOT A HYPOTHETICAL. The all-tenant permission figure moved **469 → 678**
 * when a third organisation was added, and **nothing anywhere went red** — because no test asserted it. It
 * was "pinned" in the session memory and in briefs and nowhere else. 🔬 A soft-pin sweep then found the same
 * of `owner_ledger`'s sum, `availability.sold`, and tenant one's whole RBAC census.
 * **A figure stated as pinned that nothing asserts is not a pin — it is a claim**, and this project had been
 * treating several of them as evidence.
 *
 * ⚠️⚠️ TENANT ONE IS THE UNTOUCHABLE BASELINE, so every figure below must NEVER move and **a red here is
 * always a real signal.** That is precisely what makes them safe to pin: there is no legitimate reason for
 * any of them to change, so nobody will ever be tempted to re-pin on reflex.
 *
 * ── ⚠️ WHAT IS DELIBERATELY *NOT* PINNED HERE, AND WHY ──────────────────────────────────────────────────
 *
 *   · ⚠️⚠️ **THE ALL-TENANT FIGURE (678 / 51 / 40 / 17).** It moves legitimately every time an organisation
 *     is added — it already has. **A pin that reddens for good reasons teaches people to re-pin on reflex**,
 *     and a pin that gets re-pinned on reflex is worse than no pin at all.
 *   · ⚠️ **THE SUITE AND ASSERTION COUNTS.** Historical the moment they are written.
 *   · ⚠️⚠️ **ANYTHING BELONGING TO THE THIRD TENANT.** It is disposable by design and destroyed by every
 *     reseed; 🔬 `tests/` contains zero references to it and must stay that way — ⚠️ which is why the seed
 *     list above stops at the platform admin and names no other organisation, not even to exclude one.
 *
 * ── ⚠️⚠️ THE DISTINCTION THIS PROJECT'S LANGUAGE HAS BEEN BLURRING ──────────────────────────────────────
 *
 * ⚠️⚠️ **THE MONEY GATE'S SIX FIGURES ARE ENFORCED BY `ops/money-as-seeded.sh` — A SHELL SCRIPT THE SUITE
 * NEVER RUNS.** So a green `php artisan test` has never said anything whatsoever about them, and "all gates
 * green" has been one sentence covering two different instruments. This file does not replace that gate:
 * `rate_calendar`'s count and sum are date-dependent and belong there. What it adds is the figures the gate
 * does **not** print — 🔬 its own header says it does not print `owner_ledger` at all.
 *
 * ⚠️ THE SEED SET IS THE REAL BASELINE ORDER, MINUS THE OTHER TENANTS. 🔬 Measured: `DemoSeeder` alone gives
 * `role_has_permissions` 209 and `roles` 13, because the GLOBAL role is created by the catalogue seeder —
 * 209 + 51 = 260 and 13 + 1 = 14. Seeding the wrong set would have pinned the wrong numbers, which is the
 * whole reason this was measured before it was written.
 */
beforeEach(function () {
    $this->seed(RoleCatalogSeeder::class);
    $this->seed(BillingPlanSeeder::class);
    $this->seed(DemoSeeder::class);
    $this->seed(PlatformAdminSeeder::class);
});

/** ⚠️ Whole-database assertions need a super-admin context; tenant-scoped models would otherwise throw. */
function baselineRead(callable $fn): mixed
{
    return app(TenantContext::class)->runAsSuperAdmin($fn);
}

// ── 1. ⚠️ THE OWNER LEDGER — the figure the money gate does not print ─────────────────────────────
it('⚠️ pins tenant one\'s owner ledger — 29 rows summing to 730,640 minor units', function () {
    baselineRead(function (): void {
        expect((int) DB::table('owner_ledger')->count())->toBe(29,
            'owner_ledger row count moved — tenant one is the untouchable baseline, so this is a real signal');

        expect((int) DB::table('owner_ledger')->sum('amount_minor'))->toBe(730_640,
            'owner_ledger SUM(amount_minor) moved — the demo money is not as seeded');
    });
});

// ── 2. ⚠️ THE INVENTORY SPINE ─────────────────────────────────────────────────────────────────────
it('⚠️ pins tenant one\'s sold inventory — availability.sold sums to 105', function () {
    baselineRead(function (): void {
        expect((int) DB::table('availability')->sum('sold'))->toBe(105,
            'availability.sold moved — a booking was created, released, or double-counted in the demo seed');
    });
});

// ── 3. ⚠️⚠️ THE RBAC CENSUS — the class of figure that moved 469 → 678 unnoticed ──────────────────
it('⚠️⚠️ pins tenant one\'s RBAC census — 260 / 51 / 14 / 14', function () {
    baselineRead(function (): void {
        $organization = DB::table('organizations')->where('slug', 'vyrobel-demo')->value('id');

        // ⚠️ TENANT-ONE-SCOPED MEANS "this organisation's roles PLUS the global one". 🔬 209 + 51 = 260 and
        //    13 + 1 = 14 — a pass that compares an unscoped total against these will invent a discrepancy.
        $roles = DB::table('roles')
            ->where(fn ($q) => $q->where('organization_id', $organization)->orWhereNull('organization_id'))
            ->pluck('id');

        expect((int) DB::table('role_has_permissions')->whereIn('role_id', $roles)->count())->toBe(260,
            'role_has_permissions for tenant one moved — a role gained or lost permissions');

        expect((int) DB::table('permissions')->count())->toBe(51,
            '⚠️⚠️ THE PERMISSION CATALOGUE MOVED. Permissions are GLOBAL definitions, not per-tenant: this '
            .'figure held through the addition of an entire organisation. A move here means one was MINTED '
            .'or REMOVED, which changes access in every tenant at once — a founder decision, never a build step');

        expect($roles->count())->toBe(14,
            'the tenant-one role count moved — 13 organisation roles plus the global one');

        expect((int) DB::table('model_has_roles')->where('organization_id', $organization)->count())->toBe(14,
            'model_has_roles for tenant one moved — a demo account gained or lost a role assignment');
    });
});
