<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\ResolvePublicOrganization;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\RateCalendar;
use App\Models\RoomType;
use App\Support\Reviews\ReviewList;
use App\Support\Reviews\ReviewRating;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The multi-property, per-ORGANIZATION storefront LANDING (Guest Browse · founder decision D1). Aggregates
 * the org's REAL active properties by town (their `address.city`), with a REAL per-town from-price (the
 * cheapest forward nightly in `rate_calendar`), REAL featured room types (real from-price + per-room
 * rating #76), REAL browse-by-amenity chips (only amenities actually present), and REAL recent reviews
 * (#76, first-name-only #28). Every figure is a real aggregate — no fabricated town/count/price/review
 * (#39). Where the wireframe claimed "4.8 across 369 stays", we show the REAL average + REAL count, and
 * hide the badge entirely when the org has no published reviews.
 *
 * Tenant context is set by {@see ResolvePublicOrganization} (real context, never a guard bypass); the org
 * is re-loaded tenant-scoped, and every downstream query runs scoped to it.
 */
class StorefrontLandingController extends Controller
{
    public function show(Request $request): View
    {
        $organization = $this->organization($request);

        $properties = Property::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $roomTypes = RoomType::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->with(['amenities', 'ratePlans'])
            ->orderBy('name')
            ->get();

        $rating = ReviewRating::forOrganization((string) $organization->getKey());

        return view('booking.landing', [
            'org' => [
                'name' => (string) $organization->name,
                'slug' => (string) $organization->slug,
            ],
            'signedInGuest' => $request->user('guest') !== null,
            'rating' => $rating,
            'towns' => $this->towns($properties, $roomTypes),
            'properties' => $properties->map(fn (Property $p): array => [
                'slug' => (string) $p->slug,
                'name' => (string) $p->name,
                'town' => $this->town($p),
            ])->all(),
            'featured' => $this->featured($properties, $roomTypes),
            'vibes' => $this->vibes($properties, $roomTypes),
            'perks' => $this->perks($properties),
            'reviews' => ReviewList::forOrganization((string) $organization->getKey(), 3),
        ]);
    }

    /**
     * The landing search — the multi-property equivalent of the per-property search: pick a property
     * (town), dates + guests → its real storefront search (`booking.browse`). Server-side (the guest realm
     * has no Alpine); an unknown/foreign property slug is rejected (404) rather than leaking cross-org.
     */
    public function search(Request $request): RedirectResponse
    {
        $organization = $this->organization($request);

        $slug = (string) $request->query('property', '');
        $property = Property::query()->where('status', 'active')->where('slug', $slug)->first();
        if ($property === null) {
            return redirect()->route('storefront.landing', ['organization' => $organization->slug]);
        }

        $query = array_filter([
            'check_in' => $this->validDate($request->query('check_in')),
            'check_out' => $this->validDate($request->query('check_out')),
            'adults' => (string) max(1, (int) $request->query('adults', '2')),
            'children' => (string) max(0, (int) $request->query('children', '0')),
        ], fn ($v): bool => $v !== null && $v !== '');

        return redirect()->route('booking.browse', array_merge(['property' => $property->slug], $query));
    }

    private function organization(Request $request): Organization
    {
        return Organization::query()->findOrFail($request->attributes->get(ResolvePublicOrganization::ATTRIBUTE));
    }

    /**
     * The org's properties grouped into "Where to stay" town cards — REAL town (from `address.city`),
     * REAL room-type count + capacity range, and a REAL from-price (cheapest forward nightly across the
     * town's rooms). A town with no priced room simply omits the from-price (never a fabricated figure).
     *
     * @param  Collection<int, Property>  $properties
     * @param  Collection<int, RoomType>  $roomTypes
     * @return list<array<string, mixed>>
     */
    private function towns(Collection $properties, Collection $roomTypes): array
    {
        $byTown = $properties->groupBy(fn (Property $p): string => $this->town($p) ?: 'Our stays');

        $cards = [];
        $i = 0;
        foreach ($byTown as $town => $group) {
            /** @var Collection<int, Property> $group */
            $propIds = $group->pluck('id')->all();
            $rooms = $roomTypes->whereIn('property_id', $propIds);
            $capacities = $rooms->map(fn (RoomType $rt): int => (int) $rt->max_adults + (int) $rt->max_children)->filter();
            $from = $this->fromPriceMinor($rooms);
            $currency = (string) $group->first()->currency;

            $cards[] = [
                'town' => (string) $town,
                'sub' => $this->typeLabel($group),
                'count' => $this->countLabel($rooms->count(), $capacities->min(), $capacities->max()),
                'from' => $from !== null ? $this->money($from, $currency) : null,
                // A town card links to its (single) property storefront, or the first when a town has several.
                'href' => route('booking.browse', ['property' => $group->first()->slug]),
                'thumb' => $this->towelGradient($i),
                'delay' => number_format(0.04 + $i * 0.06, 2).'s',
            ];
            $i++;
        }

        return $cards;
    }

    /**
     * REAL featured room types across the org — ordered by rating then name, capped at 6. Each carries a
     * REAL from-price (cheapest forward nightly) + REAL per-room rating (#76) + its town, and links to the
     * real room detail. No availability or nightly is invented.
     *
     * @param  Collection<int, Property>  $properties
     * @param  Collection<int, RoomType>  $roomTypes
     * @return list<array<string, mixed>>
     */
    private function featured(Collection $properties, Collection $roomTypes): array
    {
        $propsById = $properties->keyBy(fn (Property $p): string => (string) $p->getKey());

        $rows = $roomTypes->map(function (RoomType $rt) use ($propsById): array {
            $property = $propsById->get((string) $rt->property_id);
            $rating = ReviewRating::forRoomType((string) $rt->getKey());
            $from = $this->fromPriceMinor(collect([$rt]));

            return [
                'name' => (string) $rt->name,
                'kind' => $this->roomKind((string) $rt->kind),
                'town' => $property !== null ? ($this->town($property) ?: (string) $property->name) : '',
                'rating' => $rating['has_reviews'] ? number_format((float) $rating['average'], 1) : null,
                'from' => $from !== null ? $this->money($from, (string) ($property->currency ?? 'EUR')) : null,
                'from_minor' => $from,
                'cover' => $rt->coverPhoto()?->url,
                'href' => $property !== null
                    ? route('booking.room', ['property' => $property->slug, 'roomType' => $rt->getKey()])
                    : null,
            ];
        })->filter(fn (array $r): bool => $r['href'] !== null);

        // Guest-favourites first (real rating desc), then a stable name order; a thin catalogue just shows fewer.
        return $rows->sortByDesc(fn (array $r): float => $r['rating'] !== null ? (float) $r['rating'] : 0.0)
            ->values()
            ->take(6)
            ->map(function (array $r, int $i): array {
                unset($r['from_minor']);
                $r['delay'] = number_format(0.04 + $i * 0.05, 2).'s';
                $r['thumb'] = $this->roomGradient($i);

                return $r;
            })->all();
    }

    /**
     * "Browse by vibe" → REAL amenity chips: only amenities that actually exist on the org's rooms, each
     * linking to a property that genuinely has it (pre-filtered). No fabricated "Beachfront/Pet-friendly"
     * category with no backing filter (#39) — if the org has no amenities, the section is empty.
     *
     * @param  Collection<int, Property>  $properties
     * @param  Collection<int, RoomType>  $roomTypes
     * @return list<array<string, mixed>>
     */
    private function vibes(Collection $properties, Collection $roomTypes): array
    {
        $propsById = $properties->keyBy(fn (Property $p): string => (string) $p->getKey());

        // amenity name → [propertySlug (first that has it), count of rooms with it]
        $tally = [];
        foreach ($roomTypes as $rt) {
            $property = $propsById->get((string) $rt->property_id);
            if ($property === null) {
                continue;
            }
            foreach ($rt->amenities as $amenity) {
                $name = (string) $amenity->name;
                if (! isset($tally[$name])) {
                    $tally[$name] = ['slug' => (string) $property->slug, 'count' => 0, 'icon' => (string) $amenity->icon];
                }
                $tally[$name]['count']++;
            }
        }

        uasort($tally, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $out = [];
        foreach (array_slice($tally, 0, 4, true) as $name => $info) {
            $out[] = [
                'label' => $name,
                'sub' => $info['count'].' '.($info['count'] === 1 ? 'stay' : 'stays'),
                'icon' => $this->amenityIcon($info['icon']),
                'href' => route('booking.browse', ['property' => $info['slug'], 'amenities' => [$name]]),
            ];
        }

        return $out;
    }

    /**
     * Why-book-direct perks — honest for a direct booking. "Free cancellation" reflects the REAL policy
     * window (the most generous `free_until_days` across the org), never a fabricated "14 days".
     *
     * @param  Collection<int, Property>  $properties
     * @return list<array{label: string, sub: string}>
     */
    private function perks(Collection $properties): array
    {
        $freeDays = PropertyPolicy::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->get()
            ->map(fn (PropertyPolicy $p): int => (int) (($p->cancellation['free_until_days'] ?? 0)))
            ->max();

        $cancelSub = is_int($freeDays) && $freeDays > 0
            ? "Full refund up to {$freeDays} ".($freeDays === 1 ? 'day' : 'days').' before arrival.'
            : 'Full refund within the cancellation window.';

        return [
            ['label' => 'No booking fees', 'sub' => 'The price you see is the price you pay.'],
            ['label' => 'Best-rate direct', 'sub' => 'Book direct for our own best rate — no middleman.'],
            ['label' => 'Free cancellation', 'sub' => $cancelSub],
            ['label' => 'Direct host support', 'sub' => 'Message a real person on your booking.'],
        ];
    }

    /**
     * A REAL "from / night" (minor units) for a set of room types: the cheapest FORWARD nightly across
     * their active rate plans in the rate calendar. Independent of the guest's dates, so it never implies
     * availability — null when nothing is priced (the caller omits the figure).
     *
     * @param  Collection<int, RoomType>  $rooms
     */
    private function fromPriceMinor(Collection $rooms): ?int
    {
        $planIds = $rooms->flatMap(fn (RoomType $rt) => $rt->ratePlans->where('is_active', true)->pluck('id'))->all();
        if ($planIds === []) {
            return null;
        }

        $min = RateCalendar::query()
            ->whereIn('rate_plan_id', $planIds)
            ->whereDate('date', '>=', CarbonImmutable::now()->toDateString())
            ->min('price_minor');

        return $min !== null ? (int) $min : null;
    }

    private function town(Property $property): string
    {
        $address = is_array($property->address) ? $property->address : [];

        return trim((string) ($address['city'] ?? ''));
    }

    /** @param  Collection<int, Property>  $group */
    private function typeLabel(Collection $group): string
    {
        return $group->count() === 1
            ? $this->propertyType((string) $group->first()->type)
            : $group->count().' properties';
    }

    private function countLabel(int $roomCount, ?int $minCap, ?int $maxCap): string
    {
        $rooms = $roomCount.' '.($roomCount === 1 ? 'stay' : 'stays');
        if ($minCap === null || $maxCap === null || $maxCap === 0) {
            return $rooms;
        }
        $sleeps = $minCap === $maxCap ? "sleeps {$maxCap}" : "sleeps {$minCap}–{$maxCap}";

        return "{$rooms} · {$sleeps}";
    }

    private function validDate(mixed $raw): ?string
    {
        if (! is_string($raw) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function money(int $minor, string $currency): string
    {
        $symbol = match (strtoupper($currency)) {
            'EUR' => '€', 'GBP' => '£', 'USD' => '$', default => strtoupper($currency).' ',
        };

        return $symbol.number_format($minor / 100, 0, '.', ',');
    }

    private function roomKind(string $kind): string
    {
        return ucwords(str_replace('_', ' ', $kind));
    }

    private function propertyType(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }

    /** Decorative peach gradients keyed by index (matching the wireframe's warm tiles). */
    private function towelGradient(int $i): string
    {
        $grads = [
            'linear-gradient(150deg, #F3B98E, #D9662E)',
            'linear-gradient(150deg, #EDBE97, #C67B44)',
            'linear-gradient(150deg, #E7C6A0, #B98A57)',
        ];

        return $grads[$i % count($grads)];
    }

    private function roomGradient(int $i): string
    {
        $grads = [
            'linear-gradient(150deg, #F0A57A, #E8703A)',
            'linear-gradient(150deg, #EDA378, #D9662E)',
            'linear-gradient(150deg, #F3B98E, #E8875A)',
            'linear-gradient(150deg, #EDBE97, #C67B44)',
            'linear-gradient(150deg, #E7C6A0, #B98A57)',
            'linear-gradient(150deg, #EFCBA6, #CE9560)',
        ];

        return $grads[$i % count($grads)];
    }

    /** Map an amenity icon slug → an inline SVG path set (small, self-contained; guest realm has no icon lib). */
    private function amenityIcon(string $slug): string
    {
        return match ($slug) {
            'pool' => '<path d="M2 20c1.5 0 2-1 3.5-1s2 1 3.5 1 2-1 3.5-1 2 1 3.5 1 2-1 3.5-1"/><path d="M6 16V6a2 2 0 0 1 4 0"/><path d="M18 16V6a2 2 0 0 0-4 0"/>',
            'wifi' => '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8.5 16a5 5 0 0 1 7 0"/><path d="M12 19.5h.01"/>',
            'parking' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 16V8h3.5a2.5 2.5 0 0 1 0 5H9"/>',
            'ac' => '<path d="M12 3v18"/><path d="M3 12h18"/><path d="M6 6l12 12"/><path d="M18 6L6 18"/>',
            'kitchen' => '<path d="M8 3v6a4 4 0 0 1-8 0V3"/><path d="M4 3v18"/><path d="M17 3c-2 0-3 2-3 5s1 4 3 4 3-1 3-4-1-5-3-5z"/><path d="M17 12v9"/>',
            'balcony' => '<path d="M3 21V10l9-6 9 6v11"/><path d="M3 15h18"/><path d="M8 21v-6"/><path d="M16 21v-6"/>',
            default => '<path d="M20 6L9 17l-5-5"/>',
        };
    }
}
