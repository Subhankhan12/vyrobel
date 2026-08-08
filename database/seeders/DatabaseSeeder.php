<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * NOTE: WithoutModelEvents is intentionally NOT used here — ULID primary keys are
     * generated via the model `creating` event (HasUlids), which that trait would suppress.
     */
    public function run(): void
    {
        // ⚠️ REFUSE TO SEED PRODUCTION (audit 2026-08-07). No seeder carried an environment guard, so
        // `php artisan db:seed --force` against production would have created:
        //   • a PLATFORM SUPER-ADMIN, admin@vyrobel-demo.test / password — a realm that sits ABOVE tenancy,
        //   • a demo organisation with staff/owner/guest logins all using the password `password`,
        //   • a global `test@example.com` user.
        // Laravel's own --force prompt is the only thing that stood between a mistyped deploy command and a
        // publicly-known super-admin credential. The runbook says never to seed production; this makes it
        // true rather than a documented hope.
        //
        // Production platform admins are created out-of-band: `php artisan admin:create`.
        if (app()->isProduction()) {
            throw new RuntimeException(
                'Refusing to seed in production. These seeders create DEMO accounts with the password "password", '
                .'including a platform super-admin. Use `php artisan admin:create` instead — see docs/DEPLOYMENT.md.'
            );
        }

        $this->call([
            RoleCatalogSeeder::class,
            BillingPlanSeeder::class,   // the placeholder plan catalog (Phase 7b) — before the demo org's trial
            DemoSeeder::class,
            PlatformAdminSeeder::class, // Super Admin · Gate 0 — the demo platform admin (separate realm, above tenancy)
        ]);

        // Dev convenience only: a global platform user (not tenant data).
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
