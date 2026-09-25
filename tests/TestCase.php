<?php

namespace Tests;

use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeSafetyGuard;
use App\Services\Auth\GoogleIdentityProvider;
use App\Services\Channex\ChannexClient;
use App\Support\Llm\AnthropicLlmClient;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * ⚠️ SESSIONS 01 — `actingAs()` switches the signed-in account WITHOUT a login or a logout, inside the one session
     * this test process keeps. A real browser cannot do that: two people are two sessions, and every sign-out in this app
     * invalidates the session (LoginController, OwnerSecurityController, GuestLoginController, AdminLoginController,
     * TrackPlatformAdminSession — checked 2026-09-25), so a real session never carries another account's password hash.
     * Without this, `actingAs(A)` then `actingAs(B)` carried A's hash into B's requests and the password-change check
     * (EndSessionsOnPasswordChange) signed B out — four tests went red on the harness, not the product. A test that WANTS
     * a stale hash (a second, older session of the same account) sets it with `withSession()` AFTER `actingAs()`.
     */
    public function be(UserContract $user, $guard = null)
    {
        $guardName = $guard ?? $this->app['auth']->getDefaultDriver();
        if ($this->app->bound('session')) {
            $this->app['session']->forget('password_hash_'.$guardName);
        }

        return parent::be($user, $guard);
    }

    /**
     * ⚠️⚠️ F-35 — THE SUITE MAY NOT REACH THE REAL INTERNET THROUGH THE `Http` FACADE (ruled 2026-09-19).
     *
     * 🔬 `Http::preventStrayRequests()` had ZERO occurrences anywhere in this repository, so any test could
     * make a real outbound call and nothing would say so. A suite that *can* reach the network is
     * non-deterministic whether or not it currently does: it fails on a plane, and somebody else's outage
     * can turn it red.
     *
     * ══ ⚠️⚠️ WHY HERE AND NOT IN `Pest.php` ══════════════════════════════════════════════════════════
     *
     * `Pest.php` holds the other global hook — the frozen clock — and that one is bound
     * `->in('Feature', 'Unit')`. This cannot follow it. 🔬 FOUR OF THE FIVE UNIT TESTS ARE PURE PHP WITH NO
     * CONTAINER (`BoardDateTest`, `MoneyAllocateTest`, `PayoutScheduleTest`, `ReportPeriodTest` never call
     * `uses(TestCase::class)`), and `Http::` is a FACADE — calling it where no application is booted throws
     * "a facade root has not been set" and would turn four passing tests into errors.
     *
     * ⚠️ So it lives in the base test case, which is the container-aware half of the same convention: it runs
     * for every test that HAS an application — all of `Feature`, plus `HelpRetrievalTest`, the one Unit test
     * that opts in via `uses(TestCase::class)` — and the tests it does not reach are exactly the ones that
     * COULD NOT USE THE FACADE ANYWAY. Coverage is complete over the set where the guard can mean anything.
     *
     * ══ ⚠️⚠️ WHAT THIS DOES **NOT** GUARD — READ BEFORE QUOTING IT ═════════════════════════════════════
     *
     * ⚠️⚠️ **IT SEES LARAVEL'S `Http` FACADE AND NOTHING ELSE.** The honest claim is *"the facade is
     * guarded"*, NEVER *"the suite makes no outbound requests"* — the second is a bigger claim and this does
     * not measure it. 🔬 Enumerated in FIX 47:
     *
     *   · **Stripe — its SDK does its own transport** (`vendor/stripe/stripe-php/.../HttpClient/CurlClient`),
     *     so this guard cannot see it. It is held instead by a DIFFERENT mechanism, and that one is real:
     *     every caller goes through {@see StripeClientInterface}, tests are bound to
     *     `FakeStripeClient`, and {@see StripeSafetyGuard} refuses a live key outside
     *     production. ⚠️ Two independent guards, neither of which is this one.
     *   · A raw `curl_*` call or a `GuzzleHttp\Client` constructed directly — 🔬 measured: **neither appears
     *     anywhere in `app/`** today, which is why nothing here compensates for them.
     *   · Anything a queued job does in another process.
     *
     * ⚠️ THE THREE APP CLASSES THAT DO USE THE FACADE, and are therefore genuinely covered:
     * {@see ChannexClient}, {@see AnthropicLlmClient} and
     * {@see GoogleIdentityProvider}.
     *
     * ⚠️ NEVER ADD AN EXEMPTION HERE TO MAKE A TEST PASS. A test that needs the network is a finding, not a
     * special case — the same rule as never widening access to green a gate.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }
}
