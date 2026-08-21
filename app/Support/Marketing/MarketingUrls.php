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
        'alternatives' => '/alternatives',
        'compare' => '/compare',
        'for-hotels' => '/for-hotels',
        'for-villas' => '/for-villas',
        'for-apartments' => '/for-apartments',
        'case-studies' => '/case-studies',
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
        'story-cyclades' => '/case-studies/cyclades',
        'story-lisbon' => '/case-studies/lisbon',
        'story-rotterdam' => '/case-studies/rotterdam',
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
}
