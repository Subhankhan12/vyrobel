<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use App\Mail\BookingConfirmationMail;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\BookingEngineService;
use App\Support\Money\MoneyFormatter;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\View;

/**
 * THE TRY-BOOKING SANDBOX (front-website Gate 4, ruling D3 = (b)).
 *
 * ⚠️ WHAT THIS REPLACES. The design's page is a client-side simulation of a FICTIONAL property
 * ("Villa Azure, Paros, Greece · sleeps 6"), ending in a FABRICATED confirmation email, under a meta
 * description promising *"Make a real booking on a demo property"*. Nothing was booked, no email existed,
 * and the property did not. The founder ruled: rebuild it properly.
 *
 * ⚠️ SO EVERY NUMBER HERE IS TRUE OF SOMETHING THAT EXISTS:
 *   · the PROPERTY is the real `seaside-demo` ("Demo Seaside Property", Faro, Algarve) — its real room
 *     types, its real unit counts, its real currency and timezone. No parallel fiction.
 *   · the PRICE comes from {@see BookingEngineService::priceOver()} — ⚠️ the engine's OWN method, the one
 *     `quote()` itself calls, summing the same `rate_calendar` rows a real guest is charged from. Not a
 *     second computation of one price (B12's lesson: two representations always drift).
 *   · the CONFIRMATION PREVIEW renders the REAL {@see BookingConfirmationMail} — its real template, fed by
 *     its own `content()` variables. Not an invented email.
 *
 * ⚠️⚠️ AND IT WRITES NOTHING. `BookingEngineService::quote()` persists a `booking_quotes` row, so it is
 * NEVER called here; `priceOver()` is the read-only half that runs before that write. The reservation used
 * for the mail preview is constructed in memory and never saved. No reservation, no quote row, no email
 * sent, no money moved.
 *
 * ⚠️⚠️ MAIL-PREVIEW SAFETY (#33). The real mail embeds five live URLs — `receiptUrl`, `reservationUrl`,
 * `checkinUrl`, `messagesUrl` and ⚠️ **`arrivalUrl`, which is the arrival-secrets page**. A preview that
 * emitted working links to that mechanism would be a real defect, not a cosmetic one. Every URL the mail
 * computes is therefore NEUTRALISED before the template sees it, and the reservation carries a synthetic,
 * unmistakably-sandbox identifier rather than a real key. The template is the real one; only the hrefs are
 * defused, and tests/Feature/MarketingTryBookingTest.php asserts none survives — referenced by path,
 * ⚠️ not imported: app code must not depend on a test class, and Gate 3b shipped a page claiming
 * "Guarded by X" while X did not exist. The guard is written in the same change as this sentence.
 */
final class TryBookingSandbox
{
    /** ⚠️ Unmistakably not a real reference, and never persisted. */
    public const SANDBOX_REFERENCE = 'SANDBOX-PREVIEW';

    /** The demo property the sandbox mirrors. Real, seeded, and publicly bookable at /book/seaside-demo. */
    public const DEMO_SLUG = 'seaside-demo';

    /**
     * The sandbox's data, or NULL when the demo property does not exist.
     *
     * ⚠️ NULL IS A REAL STATE, NOT AN EDGE CASE. Production is never seeded (DEPLOYMENT §4), so
     * `seaside-demo` may simply not be there — and the page must then say so rather than invent a stand-in.
     * That is the whole point of mirroring: with nothing to mirror, there is nothing honest to show.
     *
     * @return array<string, mixed>|null
     */
    public static function data(): ?array
    {
        // ⚠️ WHY runAsSuperAdmin, AND WHY IT IS NARROW. The marketing site is PUBLIC and deliberately
        // resolves no tenant — an anonymous request must never acquire a tenant context. But the demo
        // property's rates are tenant-scoped data, and the tenancy guard THROWS on an unscoped read (it
        // did, on the first run of this class — the invariant working). The project's sanctioned escape
        // for a legitimate cross-tenant read is this one.
        //
        // ⚠️ Everything inside reads the ONE demo property by its known slug and then keys every
        // subsequent query off that row's own ids, so the window cannot wander into another org — and it
        // reads only what `/book/seaside-demo` already shows any anonymous visitor. Nothing is written.
        return app(TenantContext::class)->runAsSuperAdmin(static function (): ?array {
            $property = Property::query()->where('slug', self::DEMO_SLUG)->first();

            if (! $property instanceof Property) {
                return null;
            }

            $roomType = RoomType::query()
                ->where('property_id', $property->getKey())->orderBy('name')->first();

            if (! $roomType instanceof RoomType) {
                return null;
            }

            $ratePlan = RatePlan::query()
                ->where('room_type_id', $roomType->getKey())->orderBy('name')->first();

            if (! $ratePlan instanceof RatePlan) {
                return null;
            }

            [$checkIn, $checkOut, $nights] = self::stayWindow($ratePlan);

            // ⚠️ THE ENGINE'S OWN PRICE — the same method `quote()` calls, over the same rate_calendar
            // rows. Null when no rate is published for the window: again a real state, not a bug.
            $totalMinor = app(BookingEngineService::class)
                ->priceOver((string) $ratePlan->getKey(), $checkIn->toDateString(), $checkOut->toDateString());

            if ($totalMinor === null) {
                return null;
            }

            $currency = (string) ($ratePlan->currency ?? $property->currency ?? 'EUR');

            return [
                'property' => $property,
                'propertyName' => (string) $property->name,
                'place' => self::placeLabel($property),
                'roomTypeName' => (string) $roomType->name,
                'units' => Unit::query()->where('room_type_id', $roomType->getKey())->count(),
                'ratePlanName' => (string) $ratePlan->name,
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
                'nights' => $nights,
                'currency' => $currency,
                'totalMinor' => $totalMinor,
                'total' => MoneyFormatter::display($totalMinor, $currency),
                'perNight' => MoneyFormatter::display((int) round($totalMinor / max(1, $nights)), $currency),
                'bookForRealUrl' => '/book/'.self::DEMO_SLUG,
            ];
        });
    }

    /**
     * The REAL confirmation email, rendered with sandbox data and every link defused — or NULL when there
     * is no demo property to mirror.
     *
     * ⚠️ This is the real `BookingConfirmationMail`: its own `content()` computes the variables (guest
     * name, reference, the reconciling total, the folio breakdown, the branded template per #86), and the
     * real `emails.booking-confirmation` template renders them. The ONLY departure is that the five URL
     * variables are replaced with a dead anchor before rendering — the safety requirement, applied as
     * narrowly as it can be.
     */
    public static function confirmationPreview(): ?string
    {
        $data = self::data();

        if ($data === null) {
            return null;
        }

        $reservation = self::sandboxReservation($data);

        // ⚠️ The mail's own content() looks up the reservation's Folio (also tenant-scoped) to build the
        // real price breakdown, so the render needs the same narrow window. ⚠️ The sandbox reservation has
        // a synthetic key and was never saved, so NO folio matches — the mail's documented degradation
        // applies and the template shows the reconciling Total alone. That is the real template behaving
        // exactly as it does for a reservation with no folio yet; nothing is faked to fill the gap.
        $content = app(TenantContext::class)->runAsSuperAdmin(
            static fn () => (new BookingConfirmationMail($reservation))->content(),
        );
        $with = $content->with;

        // ⚠️ #33 — DEFUSE EVERY LINK. `arrivalUrl` is the arrival-secrets page; the rest carry a
        // reservation identifier. None may be actionable from a public marketing page.
        foreach (['receiptUrl', 'reservationUrl', 'arrivalUrl', 'checkinUrl', 'messagesUrl'] as $urlKey) {
            $with[$urlKey] = '#';
        }
        // Calendar links embed the same identifiers in query strings.
        $with['calendar'] = ['google' => '#', 'outlook' => '#', 'ics' => '#'];

        return View::make($content->view ?? 'emails.booking-confirmation', $with)->render();
    }

    /**
     * A reservation that exists only in memory. ⚠️ Never saved: `exists` stays false, so nothing can
     * persist it by accident, and its key is the sandbox reference rather than a real ULID.
     *
     * @param  array<string, mixed>  $data
     */
    private static function sandboxReservation(array $data): Reservation
    {
        $reservation = new Reservation([
            'reference' => self::SANDBOX_REFERENCE,
            'check_in' => $data['checkIn']->toDateString(),
            'check_out' => $data['checkOut']->toDateString(),
            'adults' => 2,
            'children' => 0,
            'currency' => $data['currency'],
            'total_minor' => $data['totalMinor'],
            'status' => 'confirmed',
            'source' => 'direct',
        ]);

        // ⚠️ A synthetic key. The mail's `route(...)` calls need one, and a real ULID would leak a real
        // record's identifier into a public page even with the hrefs defused.
        $reservation->id = self::SANDBOX_REFERENCE;
        $reservation->exists = false;
        $reservation->setRelation('property', $data['property']);
        $reservation->setRelation('leadGuest', null);

        return $reservation;
    }

    /**
     * A stay window that is REALLY PRICED — the first run of three consecutive published nights from today.
     *
     * ⚠️ TWO EARLIER ATTEMPTS WERE WRONG, AND BOTH FAILED QUIETLY. A fixed offset from "today" fell outside
     * the seeded window; anchoring to the calendar's FIRST row landed in the past. And the calendar has
     * GAPS (183 published nights across a ~194-day span), so even an in-range window can miss — the
     * engine's `priceOver()` returns null if any single night is unpriced, which reads exactly like "the
     * sandbox is broken". So the window is derived from the published dates themselves.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: int}
     */
    private static function stayWindow(RatePlan $ratePlan): array
    {
        $nights = 3;

        // ⚠️ Called only from inside the super-admin window above, so a plain query is correctly scoped.
        $dates = RateCalendar::query()
            ->where('rate_plan_id', $ratePlan->getKey())
            ->whereDate('date', '>=', CarbonImmutable::now()->toDateString())
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($d): string => CarbonImmutable::parse((string) $d)->toDateString())
            ->all();

        $published = array_flip($dates);

        foreach ($dates as $candidate) {
            $start = CarbonImmutable::parse($candidate);
            $run = true;

            for ($i = 0; $i < $nights; $i++) {
                if (! isset($published[$start->addDays($i)->toDateString()])) {
                    $run = false;
                    break;
                }
            }

            if ($run) {
                return [$start, $start->addDays($nights), $nights];
            }
        }

        // No run of published nights ahead — a real state. The caller prices it, gets null, and the page
        // says the sandbox is unavailable rather than inventing a stay.
        $fallback = CarbonImmutable::now()->addDays(30)->startOfDay();

        return [$fallback, $fallback->addDays($nights), $nights];
    }

    private static function placeLabel(Property $property): string
    {
        $address = (array) ($property->address ?? []);
        $parts = array_filter([
            $address['city'] ?? null,
            $address['region'] ?? null,
            $address['country'] ?? null,
        ]);

        return $parts === [] ? '' : implode(' · ', $parts);
    }
}
