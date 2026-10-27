<?php

declare(strict_types=1);

namespace App\Support\Storefront;

use App\Models\Property;
use App\Models\RoomType;
use App\Support\Reviews\ReviewRating;
use Tests\Feature\StorefrontSeoTest;

/**
 * STOREFRONT SEO (D10) — the ONE builder of the storefront's `<head>` payload and JSON-LD.
 *
 * ⚠️⚠️ EVERY FIELD IS DERIVED FROM REAL TENANT DATA, AND AN ABSENT FIELD IS OMITTED — never defaulted,
 * never estimated, never inferred. Structured data is a CLAIM: a `starRating` the tenant never set, an
 * `aggregateRating` with no reviews behind it, or a `streetAddress` invented to complete a schema are all
 * false statements that happen to be machine-readable (#39).
 *
 * ⚠️⚠️ THE `aggregateRating` RULE, EXPLICITLY. It is emitted ONLY from {@see ReviewRating}, the certified
 * single derivation, and ONLY when that helper reports `has_reviews === true` with a non-null average over
 * at least one PUBLISHED + PUBLIC review. There is no other path to a rating in this class, so a rating can
 * only exist here if real reviews exist. {@see StorefrontSeoTest} plants a rating and
 * observes the guard fire.
 *
 * ⚠️ THERE IS NO PROPERTY DESCRIPTION COLUMN IN THIS PRODUCT — not on `properties`, `room_types` or
 * `organizations`. So the meta description is STRUCTURAL: it names real attributes (type, town, country,
 * room-type count, occupancy) and says what the page does. It is never invented marketing prose, and it
 * degrades by dropping clauses whose data is absent rather than by inventing filler.
 *
 * ⚠️ PRICES COME FROM {@see StorefrontOffer}, which is a probe over the booking engine's own
 * `search()` — never a second reading of a rate (B12).
 */
final class StorefrontSeo
{
    /**
     * ⚠️ REAL schema.org LodgingBusiness subtypes only. An unmapped `properties.type` falls back to the
     * generic `LodgingBusiness` parent rather than being forced into a more specific type it may not be —
     * a wrong subtype is a wrong claim, and the parent is always true.
     */
    private const SCHEMA_TYPES = [
        'hotel' => 'Hotel',
        'resort' => 'Resort',
        'hostel' => 'Hostel',
        'guest_house' => 'BedAndBreakfast',
        'villa' => 'VacationRental',
        'apartment' => 'VacationRental',
        'serviced_apartment' => 'VacationRental',
        'apartment_building' => 'VacationRental',
    ];

    /**
     * The translation key of the human label for each `properties.type`, for titles and descriptions (translated where
     * it is read, never here — a constant runs before the request's language is set).
     *
     * ⚠️ THIS MAP IS TOTAL OVER THE ENUM, AND IS READ WITHOUT A FALLBACK ON PURPOSE. A `?? 'Stay'` default
     * would silently label a newly added property type as a generic "Stay" on every title and description
     * for that type; without it, PHPStan fails the build the moment the enum gains a value this map lacks.
     * A missing case should be loud at analysis time, not quietly wrong in production copy.
     *
     * ⚠️ {@see SCHEMA_TYPES} above is DELIBERATELY PARTIAL and does keep its fallback — there the generic
     * parent `LodgingBusiness` is always a TRUE statement, so falling back is correct rather than lossy.
     */
    private const TYPE_LABELS = [
        'hotel' => 'php/support/storefront/storefront_seo.type_hotel',
        'aparthotel' => 'php/support/storefront/storefront_seo.type_aparthotel',
        'serviced_apartment' => 'php/support/storefront/storefront_seo.type_serviced_apartment',
        'apartment_building' => 'php/support/storefront/storefront_seo.type_apartment_building',
        'apartment' => 'php/support/storefront/storefront_seo.type_apartment',
        'villa' => 'php/support/storefront/storefront_seo.type_villa',
        'hostel' => 'php/support/storefront/storefront_seo.type_hostel',
        'guest_house' => 'php/support/storefront/storefront_seo.type_guest_house',
        'resort' => 'php/support/storefront/storefront_seo.type_resort',
        'mixed' => 'php/support/storefront/storefront_seo.type_mixed',
        'portfolio' => 'php/support/storefront/storefront_seo.type_portfolio',
    ];

    public function __construct(private readonly StorefrontOffer $offers) {}

    /**
     * The property booking page — `/book/{slug}`.
     *
     * ⚠️ THE CANONICAL IS THE BARE URL. The page accepts check_in/check_out/adults/children/bedrooms/
     * max_price/sort/amenities, so it has effectively unbounded query-string variants of one page. Pointing
     * every variant at the bare URL is the single largest crawl win available here.
     *
     * @param  list<string>  $roomTypeNames
     * @return array<string, mixed>
     */
    public function forProperty(Property $property, array $roomTypeNames = [], ?string $ogImage = null): array
    {
        $name = (string) $property->name;
        $town = $this->town($property);
        $typeLabel = __(self::TYPE_LABELS[(string) $property->type]);

        $title = $town !== null
            ? __('php/support/storefront/storefront_seo.title_town', ['name' => $name, 'town' => $town])
            : __('php/support/storefront/storefront_seo.title', ['name' => $name]);

        $rating = ReviewRating::forProperty((string) $property->getKey());
        $offer = $this->offers->probe($property);

        return [
            'title' => $title,
            'description' => $this->propertyDescription($name, $typeLabel, $property, count($roomTypeNames)),
            'canonical' => route('booking.browse', ['property' => (string) $property->slug]),
            'og_type' => 'website',
            'og_image' => $ogImage,
            'site_name' => $name,
            'index' => true,
            'json_ld' => $this->propertyJsonLd($property, $rating, $offer, count($roomTypeNames)),
        ];
    }

    /**
     * The room-type page — `/book/{slug}/rooms/{roomType}`.
     *
     * ⚠️ `$offer` is the page's OWN real offer, present only when the visitor supplied valid dates. The
     * canonical (dateless) URL therefore normally carries NO price, and none is invented for it: a
     * `HotelRoom` with no `Offer` is honest, an `Offer` with a made-up window is not.
     *
     * @param  array<string, mixed>|null  $offer  the controller's real engine offer, or null
     * @return array<string, mixed>
     */
    public function forRoom(Property $property, RoomType $roomType, ?array $offer = null, ?string $ogImage = null): array
    {
        $propertyName = (string) $property->name;
        $roomName = (string) $roomType->name;
        $rating = ReviewRating::forRoomType((string) $roomType->getKey());

        return [
            'title' => __('php/support/storefront/storefront_seo.title_room', ['room' => $roomName, 'property' => $propertyName]),
            'description' => $this->roomDescription($roomName, $propertyName, $roomType, $this->town($property)),
            'canonical' => route('booking.room', [
                'property' => (string) $property->slug,
                'roomType' => (string) $roomType->getKey(),
            ]),
            'og_type' => 'product',
            'og_image' => $ogImage,
            'site_name' => $propertyName,
            'index' => true,
            'json_ld' => $this->roomJsonLd($property, $roomType, $rating, $offer),
        ];
    }

    /**
     * The organisation landing — `/stay/{org}`.
     *
     * ⚠️ A DIFFERENT ENTITY FROM A PROPERTY, not a duplicate of one, so it is canonical to itself. It is an
     * `Organization` that `owns` its properties; each property page remains the canonical page for that
     * property. Nothing here self-canonicalises a property to the org page or vice versa.
     *
     * @param  list<array{slug: string, name: string, town: string|null}>  $properties
     * @return array<string, mixed>
     */
    public function forOrganization(string $orgName, string $orgSlug, string $orgId, array $properties, ?string $ogImage = null): array
    {
        $towns = array_values(array_unique(array_filter(array_map(
            static fn (array $p): ?string => $p['town'],
            $properties,
        ))));

        $count = count($properties);
        $description = match (true) {
            $count > 0 && $towns !== [] => trans_choice('php/support/storefront/storefront_seo.org_description_towns', $count, ['count' => $count, 'towns' => $this->list($towns), 'org' => $orgName]),
            $count > 0 => trans_choice('php/support/storefront/storefront_seo.org_description', $count, ['count' => $count, 'org' => $orgName]),
            default => __('php/support/storefront/storefront_seo.org_description_empty', ['org' => $orgName]),
        };

        return [
            'title' => __('php/support/storefront/storefront_seo.title', ['name' => $orgName]),
            'description' => $description,
            'canonical' => route('storefront.landing', ['organization' => $orgSlug]),
            'og_type' => 'website',
            'og_image' => $ogImage,
            'site_name' => $orgName,
            'index' => true,
            'json_ld' => $this->organizationJsonLd($orgName, $orgSlug, $orgId, $properties),
        ];
    }

    /**
     * A transactional storefront page (checkout, confirmation, a minted reservation view).
     *
     * ⚠️⚠️ THESE MUST BE `noindex`. They are per-visitor, per-quote, frequently one-time URLs; a confirmation
     * page in a search index is both useless and a privacy hazard. Before this pass they carried no robots
     * directive at all, which means "index" by default.
     *
     * @return array<string, mixed>
     */
    public function forTransactionalPage(string $title, string $propertyName): array
    {
        return [
            'title' => $title,
            'description' => '',
            'canonical' => null,
            'og_type' => 'website',
            'og_image' => null,
            'site_name' => $propertyName,
            'index' => false,
            'json_ld' => null,
        ];
    }

    // ══ DESCRIPTIONS — structural, degrading by omission ═══════════════════════════════════════════════

    private function propertyDescription(string $name, string $typeLabel, Property $property, int $roomTypeCount): string
    {
        $where = $this->town($property);
        $country = $this->addressPart($property, 'country');

        $place = $where !== null && $country !== null && $country !== $where
            ? __('php/support/storefront/storefront_seo.place_town_country', ['town' => $where, 'country' => $country])
            : ($where ?? $country);

        // one whole sentence per shape: a clause whose data is absent is dropped, never filled
        $replace = ['name' => $name, 'type' => $typeLabel, 'place' => (string) $place, 'count' => $roomTypeCount];

        return match (true) {
            $place !== null && $roomTypeCount > 0 => trans_choice('php/support/storefront/storefront_seo.description_place_rooms', $roomTypeCount, $replace),
            $place !== null => __('php/support/storefront/storefront_seo.description_place', $replace),
            $roomTypeCount > 0 => trans_choice('php/support/storefront/storefront_seo.description_rooms', $roomTypeCount, $replace),
            default => __('php/support/storefront/storefront_seo.description', $replace),
        };
    }

    private function roomDescription(string $roomName, string $propertyName, RoomType $roomType, ?string $town): string
    {
        $parts = [];

        $sleeps = (int) $roomType->max_adults + (int) $roomType->max_children;
        if ($sleeps > 0) {
            $parts[] = __('php/support/storefront/storefront_seo.detail_sleeps', ['guests' => $sleeps]);
        }

        if ($roomType->bedrooms !== null && (int) $roomType->bedrooms > 0) {
            $bedrooms = (int) $roomType->bedrooms;
            $parts[] = trans_choice('php/support/storefront/storefront_seo.detail_bedrooms', $bedrooms, ['count' => $bedrooms]);
        }

        $replace = ['room' => $roomName, 'property' => $propertyName, 'town' => (string) $town, 'details' => $this->list($parts)];

        return match (true) {
            $town !== null && $parts !== [] => __('php/support/storefront/storefront_seo.room_description_town_details', $replace),
            $town !== null => __('php/support/storefront/storefront_seo.room_description_town', $replace),
            $parts !== [] => __('php/support/storefront/storefront_seo.room_description_details', $replace),
            default => __('php/support/storefront/storefront_seo.room_description', $replace),
        };
    }

    // ══ JSON-LD — every field conditional on real data ═════════════════════════════════════════════════

    /**
     * @param  array{count: int, average: float|null, has_reviews: bool}  $rating
     * @param  array{low_minor: int, high_minor: int, currency: string, offer_count: int, check_in: string, check_out: string, nights: int}|null  $offer
     * @return array<string, mixed>
     */
    private function propertyJsonLd(Property $property, array $rating, ?array $offer, int $roomTypeCount): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => self::SCHEMA_TYPES[(string) $property->type] ?? 'LodgingBusiness',
            'name' => (string) $property->name,
            'url' => route('booking.browse', ['property' => (string) $property->slug]),
        ];

        if (($address = $this->postalAddress($property)) !== null) {
            $node['address'] = $address;
        }

        if (($geo = $this->geo($property)) !== null) {
            $node['geo'] = $geo;
        }

        // ⚠️ REAL tenant-set star rating only. NULL is the default for every property this product creates,
        // and a null star rating means "not stated", never "unrated" and never a guess.
        if ($property->star_rating !== null && (int) $property->star_rating > 0) {
            $node['starRating'] = [
                '@type' => 'Rating',
                'ratingValue' => (int) $property->star_rating,
            ];
        }

        if (($aggregate = $this->aggregateRating($rating)) !== null) {
            $node['aggregateRating'] = $aggregate;
        }

        if ($property->support_phone !== null && trim((string) $property->support_phone) !== '') {
            $node['telephone'] = (string) $property->support_phone;
        }

        if ($property->check_in_time !== null) {
            $node['checkinTime'] = substr((string) $property->check_in_time, 0, 5);
        }

        if ($property->check_out_time !== null) {
            $node['checkoutTime'] = substr((string) $property->check_out_time, 0, 5);
        }

        // ⚠️ `numberOfRooms` here is the count of ROOM TYPES the property publishes, which is what the page
        // shows. It is omitted rather than guessed when there are none.
        if ($roomTypeCount > 0) {
            $node['numberOfRooms'] = $roomTypeCount;
        }

        if ($offer !== null) {
            $node['makesOffer'] = $this->aggregateOffer($property, $offer);
        }

        return $node;
    }

    /**
     * @param  array{count: int, average: float|null, has_reviews: bool}  $rating
     * @param  array<string, mixed>|null  $offer
     * @return array<string, mixed>
     */
    private function roomJsonLd(Property $property, RoomType $roomType, array $rating, ?array $offer): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'HotelRoom',
            'name' => (string) $roomType->name,
            'url' => route('booking.room', [
                'property' => (string) $property->slug,
                'roomType' => (string) $roomType->getKey(),
            ]),
            'containedInPlace' => [
                '@type' => self::SCHEMA_TYPES[(string) $property->type] ?? 'LodgingBusiness',
                'name' => (string) $property->name,
                'url' => route('booking.browse', ['property' => (string) $property->slug]),
            ],
        ];

        $sleeps = (int) $roomType->max_adults + (int) $roomType->max_children;
        if ($sleeps > 0) {
            $node['occupancy'] = [
                '@type' => 'QuantitativeValue',
                'maxValue' => $sleeps,
                'unitCode' => 'C62',
            ];
        }

        if ($roomType->bedrooms !== null && (int) $roomType->bedrooms > 0) {
            $node['numberOfRooms'] = (int) $roomType->bedrooms;
        }

        if (($aggregate = $this->aggregateRating($rating)) !== null) {
            $node['aggregateRating'] = $aggregate;
        }

        // ⚠️ Only the page's own REAL engine offer, present only when the visitor searched real dates.
        if ($offer !== null && isset($offer['total_minor'], $offer['currency'], $offer['check_in'], $offer['check_out'])) {
            $node['offers'] = [
                '@type' => 'Offer',
                'price' => $this->major((int) $offer['total_minor']),
                'priceCurrency' => (string) $offer['currency'],
                'availabilityStarts' => (string) $offer['check_in'],
                'availabilityEnds' => (string) $offer['check_out'],
                'url' => $node['url'],
            ];
        }

        return $node;
    }

    /**
     * @param  list<array{slug: string, name: string, town: string|null}>  $properties
     * @return array<string, mixed>
     */
    private function organizationJsonLd(string $orgName, string $orgSlug, string $orgId, array $properties): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $orgName,
            'url' => route('storefront.landing', ['organization' => $orgSlug]),
        ];

        $rating = ReviewRating::forOrganization($orgId);
        if (($aggregate = $this->aggregateRating($rating)) !== null) {
            $node['aggregateRating'] = $aggregate;
        }

        if ($properties !== []) {
            $node['owns'] = array_map(fn (array $p): array => array_filter([
                '@type' => 'LodgingBusiness',
                'name' => $p['name'],
                'url' => route('booking.browse', ['property' => $p['slug']]),
                'address' => $p['town'] !== null ? [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $p['town'],
                ] : null,
            ], static fn ($v): bool => $v !== null), $properties);
        }

        return $node;
    }

    /**
     * @param  array{low_minor: int, high_minor: int, currency: string, offer_count: int, check_in: string, check_out: string, nights: int}  $offer
     * @return array<string, mixed>
     */
    private function aggregateOffer(Property $property, array $offer): array
    {
        // ⚠️ `valueAddedTaxIncluded` IS DELIBERATELY OMITTED. The quoted figure is the all-in total from the
        // certified StayEstimator, so it includes whatever tax the property has configured — but a property
        // with NO tax configured has no VAT to include, and asserting `true` there would be a claim about a
        // tax that does not exist. An omitted optional attribute is true in every configuration; a present
        // one would not be.
        return [
            '@type' => 'AggregateOffer',
            'lowPrice' => $this->major($offer['low_minor']),
            'highPrice' => $this->major($offer['high_minor']),
            'priceCurrency' => $offer['currency'],
            'offerCount' => $offer['offer_count'],
            // ⚠️ THE WINDOW IS PART OF THE CLAIM. These prices are stay totals for exactly this window, from
            // the engine's own search; without the dates the figure would be an unbounded "from" price.
            'availabilityStarts' => $offer['check_in'],
            'availabilityEnds' => $offer['check_out'],
            'url' => route('booking.browse', ['property' => (string) $property->slug]),
        ];
    }

    /**
     * ⚠️⚠️ THE ONLY PATH TO A RATING IN THIS CLASS, and it is gated three ways on the certified helper's own
     * verdict. No caller can pass a rating in.
     *
     * @param  array{count: int, average: float|null, has_reviews: bool}  $rating
     * @return array<string, mixed>|null
     */
    private function aggregateRating(array $rating): ?array
    {
        if ($rating['has_reviews'] !== true || $rating['average'] === null || $rating['count'] < 1) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round((float) $rating['average'], 1),
            'reviewCount' => (int) $rating['count'],
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    /**
     * ⚠️ ONLY the address parts the tenant actually stored. `properties.address` is a nullable JSON blob and
     * the application's own writers put at most {city, region, country} in it — there is no street and no
     * postal code to render, so neither is emitted.
     *
     * @return array<string, string>|null
     */
    private function postalAddress(Property $property): ?array
    {
        $parts = array_filter([
            'addressLocality' => $this->addressPart($property, 'city'),
            'addressRegion' => $this->addressPart($property, 'region'),
            'addressCountry' => $this->addressPart($property, 'country'),
        ], static fn (?string $v): bool => $v !== null);

        if ($parts === []) {
            return null;
        }

        return ['@type' => 'PostalAddress'] + $parts;
    }

    /** @return array<string, mixed>|null */
    private function geo(Property $property): ?array
    {
        if ($property->lat === null || $property->lng === null) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $property->lat,
            'longitude' => (float) $property->lng,
        ];
    }

    private function addressPart(Property $property, string $key): ?string
    {
        // ⚠️ `properties.address` is cast to `array` on the model, so it arrives decoded or null — a
        // json_decode branch here would be unreachable code pretending to be defensive.
        $address = $property->address;

        if (! is_array($address)) {
            return null;
        }

        $value = $address[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function town(Property $property): ?string
    {
        return $this->addressPart($property, 'city') ?? $this->addressPart($property, 'region');
    }

    /** Integer minor units → a schema.org price string. Never a float on the money path. */
    private function major(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }

    /** @param list<string> $items */
    private function list(array $items): string
    {
        if (count($items) <= 1) {
            return (string) ($items[0] ?? '');
        }

        $last = array_pop($items);

        return __('php/support/storefront/storefront_seo.list_and', ['items' => implode(', ', $items), 'last' => $last]);
    }
}
