<?php

declare(strict_types=1);

namespace App\Support\Demo;

use App\Payments\Stripe\StripeSafetyGuard;
use App\Services\Channex\FakeChannexClient;
use RuntimeException;

/**
 * ⚠️⚠️ THE DEMO SEEDERS CREATE LOGINS WHOSE PASSWORD IS PUBLISHED IN THIS REPOSITORY.
 *
 * A platform super-admin (`admin@vyrobel-demo.test`) that sits ABOVE tenancy, an organisation admin, and now
 * eleven back-office staff logins — every one of them `password`. Against a live database that is not a
 * seeding accident; it is a set of working credentials for the whole platform.
 *
 * ── ⚠️⚠️ WHY THE OLD CHECK WAS NOT ENOUGH, AND IT WAS MEASURED, NOT REASONED ─────────────────────────────
 * `DatabaseSeeder` refused when `app()->isProduction()`. That is a DENY-LIST OF ONE EXACT STRING, so it
 * fails OPEN for every other environment name. Driven against a scratch database:
 *
 *     APP_ENV=staging php artisan db:seed --class=PlatformAdminSeeder --force
 *       → platform_admins seeded: 1 · admin@vyrobel-demo.test · Hash::check('password', …) = TRUE
 *
 * `staging`, `uat`, `qa`, `demo`, and a typo such as `producton` all seeded a publicly-known super-admin.
 *
 * ── THE SHAPE, TAKEN FROM {@see StripeSafetyGuard} ───────────────────────────────────
 * That guard permits a LIVE Stripe key in `production` AND NOWHERE ELSE — an allow-list of one, so an
 * unrecognised environment gets the safe answer. This is the same inversion applied to credentials: demo
 * data is permitted in a NAMED DEVELOPMENT ENVIRONMENT and nowhere else, so an environment nobody
 * anticipated refuses instead of seeding.
 *
 * ⚠️ THE ANTI-MODEL IS {@see FakeChannexClient}, bound by a config default of TRUE.
 *    Failing open to the fake is right for a channel manager, where the worst case is no sync. Here the
 *    worst case is a login, so the default must point the other way.
 *
 * ⚠️ THERE IS DELIBERATELY NO ESCAPE HATCH. An `ALLOW_DEMO_SEED=1` override would be the first thing copied
 *    into a server `.env` to "just get the demo up", and it would silently disarm this. Seeding demo data
 *    somewhere new is a code change, reviewed, not an environment variable.
 */
final class DemoDataSafetyGuard
{
    /**
     * ⚠️ THE COMPLETE ALLOW-LIST. Everything not named here is refused, including environments that do not
     *    exist yet — which is the entire point of an allow-list.
     *
     * @var list<string>
     */
    public const DEVELOPMENT_ENVIRONMENTS = ['local', 'testing'];

    /** Whether demo data — accounts with a published password — may be created in this environment. */
    public static function permits(string $environment): bool
    {
        return in_array($environment, self::DEVELOPMENT_ENVIRONMENTS, true);
    }

    /**
     * Refuse unless this is a named development environment.
     *
     * @throws RuntimeException everywhere else — including staging, and including an environment name that
     *                          nobody has thought of yet.
     */
    public static function ensurePermitted(string $environment, string $what = 'demo data'): void
    {
        if (self::permits($environment)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to seed %s in the "%s" environment. These seeders create accounts whose password is '
            .'"password" — including a platform super-admin that sits above tenancy. Demo data is permitted '
            .'only in: %s. Create real platform admins with `php artisan admin:create` (docs/DEPLOYMENT.md).',
            $what,
            $environment,
            implode(', ', self::DEVELOPMENT_ENVIRONMENTS),
        ));
    }
}
