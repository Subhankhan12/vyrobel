<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlatformAdmin;
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
        PlatformAdmin::query()->updateOrCreate(
            ['email' => 'admin@vyrobel-demo.test'],
            [
                'name' => 'Platform Admin (demo)',
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );
    }
}
