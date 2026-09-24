<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use App\Support\Demo\DemoDataSafetyGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SUPER ADMIN · GATE 0 — seeds the first PLATFORM ADMIN for the demo/dev environment so `migrate:fresh --seed`
 * yields a usable /admin login. This is DEMO data (flagged, like DemoSeeder) — in production the first admin is
 * created out-of-band via `php artisan admin:create`, NEVER a public route.
 *
 * The seeded admin has NO 2FA, so the very first /admin sign-in is forced through mandatory 2FA enrollment —
 * which is exactly the hardened behaviour we want to demonstrate.
 *
 * DEMO credentials: admin@vyrobel-demo.test / password
 */
class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        // ⚠️ Defence in depth: this seeder can be invoked on its own (`db:seed --class=PlatformAdminSeeder`),
        // so it refuses production independently of DatabaseSeeder. It mints a super-admin whose password is
        // the literal string "password", in a realm that sits ABOVE tenancy.
        // ⚠️⚠️ GUARDED HERE TOO, AND THAT IS THE POINT. `db:seed --class=PlatformAdminSeeder` NEVER RUNS
        // `DatabaseSeeder`, so this class's own check is the only thing between a mistyped command and a
        // publicly-known super-admin. That is exactly how the hole was demonstrated: calling this seeder
        // directly with APP_ENV=staging created `admin@vyrobel-demo.test` / `password`.
        DemoDataSafetyGuard::ensurePermitted((string) app()->environment(), 'a demo platform admin');

        PlatformAdmin::query()->updateOrCreate(
            ['email' => 'admin@vyrobel-demo.test'],
            [
                'name' => 'Platform Admin (demo)',
                'password' => Hash::make('password'),
                'status' => 'active',
                // ⚠️ ADMIN 01 · Phase 1 — every admin carries an EXPLICIT role; a null role can do nothing. Found by
                //    a fresh seed: the migration's backfill had run against an empty table, so this admin had none.
                'role' => 'owner',
            ],
        );
    }
}
