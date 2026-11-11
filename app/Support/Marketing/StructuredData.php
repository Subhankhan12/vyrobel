<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use Illuminate\Support\Str;

/**
 * STRUCTURED DATA FOR THE MARKETING SITE — the types whose values are REAL (A8, 2026-11-11, his go).
 *
 * ⚠️ STRUCTURED DATA IS A CLAIMS SURFACE: a search engine may reprint a value to someone who never reaches the page. So a type ships only when
 * every value in it is true, and this class cannot be asked for one that is not:
 *   · `Organization` (the name, the site's own address, the brand mark that is already the favicon) and `WebSite` (the same name and address) go on
 *     the HOME page; a `BreadcrumbList` (Home › this page) on every other indexable marketing page — but ONLY when the site's address is a REAL public
 *     one. `APP_URL` on a developer machine, a test run or a staging copy is `http://localhost…`, and a machine-readable `localhost` is worse than none.
 *   · `Product` / `Offer` / `SoftwareApplication` / `AggregateRating` stay REFUSED: the prices are placeholders (`MarketingPricing::isPlaceholder()`) and
 *     there are no reviews — an unset number or a rating nobody gave would be a fabrication. `FAQPage` already ships on /pricing on its own.
 * Nothing here invents a contact point, a social profile, a founding date or a search action.
 */
final class StructuredData
{
    /** Hosts that are never a public site: a machine-readable one would send a search engine nowhere. */
    private const NOT_PUBLIC_SUFFIXES = ['.test', '.local', '.localhost', '.example', '.invalid', '.internal'];

    /** The site's own address (`https://vyrobel.com`, no trailing slash) — or null when it is not a real public one. */
    public static function siteUrl(): ?string
    {
        $url = rtrim((string) config('app.url'), '/');
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || ! str_contains($host, '.') || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return null;
        }
        foreach (self::NOT_PUBLIC_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return null;
            }
        }

        return $url;
    }

    /**
     * The JSON-LD graph for a marketing page, or null when there is nothing true to say (no real public address, or a page that is not indexed).
     *
     * @param  string  $canonical  the page's path (`/pricing`) or absolute address
     * @return array<string, mixed>|null
     */
    public static function graph(string $canonical, bool $index = true): ?array
    {
        $site = self::siteUrl();
        if ($site === null || ! $index) {
            return null;
        }
        $path = '/'.trim((string) (parse_url($canonical, PHP_URL_PATH) ?? $canonical), '/');

        if ($path === '/') {
            $organization = ['@type' => 'Organization', '@id' => $site.'/#organization', 'name' => 'Vyrobel', 'url' => $site.'/'];
            // the brand mark IS the favicon (public/favicon.svg) — offered as the logo only while that file exists
            if (is_file(public_path('favicon.svg'))) {
                $organization['logo'] = $site.'/favicon.svg';
            }

            return ['@context' => 'https://schema.org', '@graph' => [
                $organization,
                ['@type' => 'WebSite', '@id' => $site.'/#website', 'name' => 'Vyrobel', 'url' => $site.'/', 'publisher' => ['@id' => $site.'/#organization']],
            ]];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $site.'/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => Str::headline(basename($path)), 'item' => $site.$path],
        ]];
    }
}
