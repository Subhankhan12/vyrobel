<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use Illuminate\Support\Facades\Route;

/**
 * ⚠️ VY-29 (audit of 2026-11-24, seen live) — THE MARKETING PAGES' SITEMAP. There was none (only the storefront's), and robots.txt
 * named neither. It lists every marketing page the router serves (`marketing.page.*`) except the ones that tell a crawler NOT to index
 * them — the illustrative scenarios (`:index="false"` on the page). MarketingSitemapTest renders every page and checks both directions,
 * so a page cannot be listed while it says noindex, or say "index" while it is left out.
 */
final class MarketingSitemap
{
    /** @var list<string> page keys whose own robots tag says noindex (the illustrative scenarios) */
    public const NOINDEX = ['case-studies', 'story-cyclades', 'story-lisbon', 'story-rotterdam'];

    /** @return list<string> the absolute addresses to list */
    public static function urls(): array
    {
        $urls = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'marketing.page.') || ! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
                continue;
            }
            if (in_array(substr($name, strlen('marketing.page.')), self::NOINDEX, true)) {
                continue;
            }
            $urls[] = route($name);
        }
        sort($urls);

        return array_values(array_unique($urls));
    }
}
