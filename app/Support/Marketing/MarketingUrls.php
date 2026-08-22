<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use Illuminate\Support\Facades\Route;

/**
 * THE ONE MARKETING URL MAP (front-website Gate 1, §N.8f) — every marketing surface (nav, footer, later the
 * pages themselves, Gate 7's sitemap) resolves a page key through THIS map. The paths are the design's own
 * sitemap.xml verbatim (slugs are NOT filename-derived — "Guide Setup" is /docs/first-property), so nav,
 * footer, sitemap and canonicals can never disagree about where a page lives.
 *
 * ⚠️ PENDING-AWARE BY CONSTRUCTION: a marketing page EXISTS once Gate 2+ registers its named route
 * (`marketing.page.{key}`). Until then {@see self::pending()} is true and the nav/footer render the item as a
 * clearly-marked non-link — never an href that 404s while looking fine. Auth entries (login/signup/reset)
 * point at ALREADY-REAL app routes and are never pending (ruling D9, Tier 1 — no auth change).
 *
 * ⚠️ The /{locale} variants are DEFERRED with D7 and deliberately absent — the locale route carries E1,
 * the only change capable of shadowing /book/.
 */
final class MarketingUrls
{
    /** page key => canonical path (the design's sitemap.xml, verbatim). */
    public const MAP = [
        'home' => '/',
        'product' => '/product',
        'features' => '/features',
        'modules' => '/modules',
        'how-it-works' => '/how-it-works',
        'pricing' => '/pricing',
        'try-booking' => '/try-booking',
        'switching' => '/switching',
        // ⚠️ `alternatives` and `compare` were DROPPED (founder ruling, executed 2026-08-22). They are not
        // "held pending sources" any more — they are gone, so their keys are gone with them.
        //
        // ⚠️ REMOVING THE KEY IS NOT SELF-ENFORCING, AND THE FIRST DRAFT OF THIS COMMENT CLAIMED IT WAS.
        // `path()` falls back to '/' on an unknown key, and `<x-marketing.nav-link>` only calls `path()` on
        // the NON-pending branch — so a leftover `key="compare"` renders as a marked dead non-link rather
        // than a wrong link. Quiet, not loud. The actual guard is therefore a TEST that no marketing view
        // may name a dropped key (MarketingFoundationTest), mutation-proven by re-adding a row.
        //
        // The 12 audited competitor claims are KEPT in resources/marketing/claims.json as the audit record
        // — the drop removes the PAGES, not the finding that those claims were unsourced.
        'for-hotels' => '/for-hotels',
        'for-villas' => '/for-villas',
        'for-apartments' => '/for-apartments',
        // ⚠️ THE URL IS ITSELF A CLAIM (Gate 3b, ruling A). The design's slug was `/case-studies/{story}`,
        // which asserts real customers — and the pages are explicitly ILLUSTRATIVE SCENARIOS. Nothing is
        // published yet, so this is the free moment to change it: after launch it costs SEO and leaves dead
        // URLs behind. ⚠️ This deliberately DIVERGES from the design's own sitemap, because the page's
        // identity changed and the sitemap slug is not binding when it does. Surfaced to the founder.
        'case-studies' => '/example-scenarios',
        'resources' => '/resources',
        'docs' => '/docs',
        'faq' => '/faq',
        'glossary' => '/glossary',
        'integrations' => '/integrations',
        'changelog' => '/changelog',
        'security' => '/security',
        'about' => '/about',
        'careers' => '/careers',
        'contact' => '/contact',
        'privacy' => '/privacy',
        'terms' => '/terms',
        // The sixteen guides — the design's own slugs, which are NOT the source filenames.
        'guide-setup' => '/docs/first-property',
        'guide-reservation' => '/docs/taking-a-reservation',
        'guide-checkin' => '/docs/check-in-and-folio',
        'guide-arrival' => '/docs/online-check-in',
        'guide-housekeeping' => '/docs/housekeeping-board',
        'guide-rates' => '/docs/rate-plans',
        'guide-statements' => '/docs/owner-statements',
        'guide-payouts' => '/docs/payout-runs',
        'guide-channels' => '/docs/connecting-channels',
        'guide-refunds' => '/docs/refunds-and-changes',
        'guide-reports' => '/docs/reports-and-exports',
        'guide-access' => '/docs/invites-and-access',
        'guide-gdpr' => '/docs/gdpr-and-data',
        'guide-security' => '/docs/account-security',
        'guide-settings' => '/docs/settings',
        'guide-storefront' => '/docs/storefront',
        'story-cyclades' => '/example-scenarios/villa-portfolio',
        'story-lisbon' => '/example-scenarios/city-hotel',
        'story-rotterdam' => '/example-scenarios/serviced-apartments',
    ];

    /** The canonical path for a marketing page key. */
    public static function path(string $key): string
    {
        return self::MAP[$key] ?? '/';
    }

    /**
     * Is this marketing page still PENDING (its route not yet registered by a later gate)?
     *
     * ⚠️ The convention is `marketing.page.{key}` — NOT `marketing.{key}`. That namespace was already
     * occupied by the guest EMAIL-marketing routes (`marketing.unsubscribe`,
     * `marketing.unsubscribe.confirm`), so the shorter prefix would have let an unrelated route answer
     * this question for a page key that happened to collide.
     */
    public static function pending(string $key): bool
    {
        return ! Route::has('marketing.page.'.$key);
    }

    /**
     * ⚠️ S2 — THE PUBLISHED MEMBERS OF A PAGE SERIES, IN MAP ORDER.
     *
     * ⚠️⚠️ WHY THIS EXISTS. Every guide and article master carries a footer counter — "Guide 3 of 16",
     * "Article 2 of 7" — and each one asserts THE WHOLE SET EXISTS. Sixteen guides were audited and none
     * published, so any partial publication would ship a page announcing fifteen siblings that are not
     * there. That made the publishable set ZERO under every policy, whatever else was fixed.
     *
     * ⚠️ THE COUNT IS DERIVED FROM THE ROUTE TABLE, NOT WRITTEN DOWN. It reads exactly what
     * {@see pending()} reads, so there is ONE definition of "is this page published" (§N.8f): publish a
     * guide and every sibling's counter grows by itself; defer one and it shrinks. A hardcoded sixteen
     * would be a second source, and a second source of one fact is how these pages became false in the
     * first place.
     *
     * ⚠️ AND IT IS DELIBERATELY A RUNTIME DERIVATION RATHER THAN A CONVERTER TRANSFORM. The doctrine says
     * a fix belongs IN the converter (§N.31.4) because a post-generation patch is undone by the next
     * generation — but the converter (`pxbuild.py`) was NEVER COMMITTED; it exists only in a session
     * scratchpad under /private/tmp. A rule enforced by an unversioned script is not enforced. Deriving at
     * runtime means there is no literal count in any generated file for a regeneration to restore, and the
     * guard in MarketingDocSeriesTest forbids one from appearing.
     *
     * @return list<string> the ordered keys with this prefix whose route actually exists
     */
    public static function publishedSeries(string $prefix): array
    {
        $published = [];

        foreach (array_keys(self::MAP) as $key) {
            if (str_starts_with($key, $prefix) && ! self::pending($key)) {
                $published[] = $key;
            }
        }

        return $published;
    }

    /**
     * The 1-based position of a page within its PUBLISHED series, or null when it is not published.
     *
     * ⚠️ NULL IS THE IMPORTANT RETURN. An unpublished page has no honest position — it is not "1 of 0" and
     * it is not "3 of 3". The caller renders nothing.
     */
    public static function seriesPosition(string $key, string $prefix): ?int
    {
        $index = array_search($key, self::publishedSeries($prefix), true);

        return $index === false ? null : $index + 1;
    }
}
