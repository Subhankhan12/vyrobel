<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\ResolvePublicProperty;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Scopes\OrganizationScope;
use App\Support\Storefront\StorefrontOffer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * STOREFRONT SEO (D10) — THE STOREFRONT SITEMAP.
 *
 * ⚠️⚠️ GENERATED FROM A ROUTE, NEVER A STATIC FILE. A static `sitemap.xml` decays silently: a property goes
 * inactive, sells out its last rate plan or is removed, and the file keeps advertising it until somebody
 * notices — which nothing in this system would. Generating it means the sitemap cannot disagree with the
 * application's own state at the moment a crawler asks.
 *
 * ⚠️⚠️ INCLUSION HAS EXACTLY TWO CONDITIONS, AND BOTH ARE BORROWED, NOT RE-DERIVED:
 *   1. `status = 'active'` — the SAME filter {@see ResolvePublicProperty} applies. If
 *      it did not match, `/book/{slug}` would 404, and a sitemap of 404s is worse than no sitemap.
 *   2. {@see StorefrontOffer::isSellable()} — a probe over the booking engine's own `search()`. A property
 *      with no room types, no active rate plan, no availability or no price offers NOTHING; indexing it
 *      burns crawl budget to land a visitor on an empty page.
 *
 * ⚠️ A PAUSED PROPERTY DROPS OUT BY CONSTRUCTION, not by a special case: pausing recomputes availability to
 * zero, so the engine probe finds nothing. That is deliberate — one authority (the engine) decides what is
 * sellable, so there is no second rule here to fall out of step with it.
 *
 * ⚠️ TENANCY. This route is public and unauthenticated, so there is no tenant context to inherit and the
 * enumeration MUST run through {@see TenantContext::runAsSuperAdmin()} — the same explicit, audited escape
 * hatch the scheduled jobs use. It is read-only: it lists rows, never writes one.
 *
 * ⚠️ IT IS DELIBERATELY NOT `sitemap.xml`. That name belongs to the marketing site's own sitemap, which is
 * SEO finalisation and explicitly out of scope for this pass. Claiming the root name now would force this
 * pass to decide the marketing site's URL set, which it is not entitled to do.
 */
class StorefrontSitemapController extends Controller
{
    /**
     * ⚠️ The probe costs up to three engine searches per property, so the rendered document is cached. Ten
     * minutes is short enough that a property becoming sellable appears promptly and long enough that a
     * crawler cannot turn the sitemap into a load generator.
     */
    private const CACHE_SECONDS = 600;

    public const CACHE_KEY = 'storefront-sitemap-xml';

    public function __construct(
        private readonly StorefrontOffer $offers,
        private readonly TenantContext $tenant,
    ) {}

    public function __invoke(Request $request): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): string => $this->build());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function build(): string
    {
        /** @var list<array{loc: string, lastmod: string|null}> $urls */
        $urls = $this->tenant->runAsSuperAdmin(function (): array {
            $entries = [];
            $sellableOrgIds = [];

            $properties = Property::query()
                // ⚠️ NAME THE SCOPE. The bare form also dropped `SoftDeletingScope`, so a property an
                //    operator ARCHIVED (`PropertyRemovalService` soft-deletes it) stayed in the PUBLIC
                //    sitemap — advertising a URL that returns 404 to the crawler that follows it.
                ->withoutGlobalScopes([OrganizationScope::class])
                ->where('status', 'active')
                // ⚠️⚠️ R-SUSPEND (founder, 2026-09-19) — AND THE ORGANISATION, NOT JUST THE PROPERTY. This
                //    query filtered the property alone, so a SUSPENDED organisation's active property stayed
                //    in the PUBLIC sitemap: the door was closed on `book/{property}` while this file went on
                //    advertising the URL. ⚠️ That is exactly the failure the comment above already names —
                //    "advertising a URL that returns 404 to the crawler that follows it" — so this makes the
                //    property half do what the organisation half below already does.
                // ⚠️⚠️ F-51 (founder, 2026-09-19) CLOSED THE DIVERGENCE FIX 36 RECORDED: `cancelled` is now
                //    closed alongside `suspended`, so this half and the organisation half below finally agree
                //    on all four states. ⚠️ It inherits the set from the ONE definition on the middleware —
                //    this file states no policy of its own, which is why the second ruling needed no edit here
                //    beyond `where` → `whereIn`.
                // ⚠️ ADMIN 01 · PHASE 2 — withTrashed: an ARCHIVED organisation's properties leave the sitemap too.
                //    The default query skipped the soft-deleted row, so its id was never excluded.
                ->whereNotIn('organization_id', Organization::withTrashed()
                    ->where(fn ($q) => $q->whereIn('status', ResolvePublicProperty::CLOSED_FOR_BOOKING)->orWhereNotNull('deleted_at'))
                    ->pluck('id'))
                ->orderBy('slug')
                ->get();

            foreach ($properties as $property) {
                if ($property->slug === null || (string) $property->slug === '') {
                    continue;
                }

                // ⚠️ The engine, running inside the property's OWN organisation — the probe reads tenant-scoped
                // models (room types, rate plans, availability), so it must not run under a foreign context.
                $sellable = $this->tenant->forOrganization(
                    (string) $property->organization_id,
                    fn (): bool => $this->offers->isSellable($property),
                );

                if (! $sellable) {
                    continue;
                }

                $sellableOrgIds[(string) $property->organization_id] = true;

                $entries[] = [
                    'loc' => route('booking.browse', ['property' => (string) $property->slug]),
                    'lastmod' => $property->updated_at?->toAtomString(),
                ];
            }

            // ⚠️ An organisation landing page is listed ONLY when at least one of its properties is sellable —
            // an org landing offering nothing bookable is the same empty-page problem one level up. The org
            // status filter mirrors ResolvePublicOrganization, for the same 404 reason as above.
            if ($sellableOrgIds !== []) {
                // ⚠️ `Organization` carries only `SoftDeletingScope` — there is no tenant scope on it to
                //    escape — so the bare call did nothing but list deleted organisations.
                $organizations = Organization::query()
                    ->whereIn('id', array_keys($sellableOrgIds))
                    ->whereIn('status', ['trialing', 'active'])
                    ->orderBy('slug')
                    ->get();

                foreach ($organizations as $organization) {
                    $entries[] = [
                        'loc' => route('storefront.landing', ['organization' => (string) $organization->slug]),
                        'lastmod' => $organization->updated_at?->toAtomString(),
                    ];
                }
            }

            return $entries;
        });

        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';
            if ($url['lastmod'] !== null) {
                $lines[] = '    <lastmod>'.htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</lastmod>';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }
}
