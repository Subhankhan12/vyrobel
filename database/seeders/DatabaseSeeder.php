<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
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

        // ⚠️⚠️ AN OPTIONAL REFERENCE DATE, FOR REPRODUCIBILITY ONLY — `VY_SEED_DATE=YYYY-MM-DD`.
        //
        // ⚠️ THE DEMO SEEDER IS DELIBERATELY DATE-RELATIVE, and that is CORRECT: `showcaseMonth()` keeps the
        // demo's stays genuinely upcoming on any run day, which is what stops a prospect opening a storefront
        // full of bookings that already happened. DemoSeeder's own docblock records the consequence honestly —
        // *"the demo's exact money figures depend on the run day … it is simply no longer a constant."*
        //
        // ⚠️ THE CHECK WAS WHAT DISAGREED. `ops/money-as-seeded.sh` pinned six figures as constants, and two of
        // them — SUM(rate_calendar.price_minor) and the reservation count — MOVE with the calendar (measured:
        // 10,912,000 / 10,916,000 / 10,920,000 / 10,926,000 / 10,928,000 across seven seed dates, and 40/41/42
        // reservations). So the gate could red for reasons unrelated to code, which is what happened.
        //
        // ⚠️ SETTING THIS CHANGES NOTHING ABOUT THE DEMO when it is unset, which is the normal case: `today()`
        // is the real today and the demo stays alive. It exists so the money gate can seed at ONE reference
        // date and compare against hand-written constants. ⚠️ It does NOT compute the expectation from the
        // seeder — that would verify the seeder against itself and catch nothing.
        $referenceDate = config('database.seed_date');

        if (is_string($referenceDate) && $referenceDate !== '') {
            $frozen = CarbonImmutable::parse($referenceDate)->startOfDay();
            CarbonImmutable::setTestNow($frozen);
            Carbon::setTestNow(Carbon::parse($frozen->toDateTimeString()));
            $this->command->warn('⚠️ Seeding with VY_SEED_DATE='.$frozen->toDateString().' — a REFERENCE date, not today.');
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

        // ⚠️ ALWAYS release the frozen clock, even though this process is about to end. A seeder that leaves
        // Carbon frozen would silently poison anything running after it in the same process — which is
        // exactly how a batch probe in this pass produced a FALSE "the seeder throws today".
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
    }
}
