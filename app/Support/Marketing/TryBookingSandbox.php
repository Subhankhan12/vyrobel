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
use App\Services\AvailabilityService;
use App\Services\BookingEngineService;
use App\Support\Money\MoneyFormatter;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * THE TRY-BOOKING SANDBOX (front-website Gate 4, ruling D3 = (b); INTERACTIVITY RESTORED in Gate 4b).
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
 *   · AVAILABILITY comes from {@see AvailabilityService::availabilityRange()} — the same spine the
 *     storefront sells against, so a sold-out night is really sold out.
 *   · the CONFIRMATION PREVIEW renders the REAL {@see BookingConfirmationMail} — its real template, fed by
 *     its own `content()` variables. Not an invented email.
 *
 * ⚠️⚠️ GATE 4b — THE PICKER IS BACK, AND EVERY FIGURE IS STILL THE ENGINE'S. Gate 4 removed the date
 * picking on the grounds that real interactivity means driving the engine, and the engine writes. That was
 * true of `quote()` and NOT of `priceOver()`, so the founder ruled the picker back — read-only.
 *
 * ⚠️ AND THERE IS NO SECOND REPRESENTATION OF A PRICE. The picked stay is re-priced SERVER-SIDE by
 * `priceOver()` on every request (the page reads `?from=&to=`), so the stay total, the per-night figure and
 * the confirmation email all come from ONE call and cannot disagree. Nothing sums prices in JavaScript;
 * there is no JavaScript on this page at all. ⚠️ This is deliberate: a client-side sum would be a second
 * implementation of the engine's "Σ nightly, refuse if any night is unpriced" rule, and §N.8f says exactly
 * how that ends.
 *
 * ⚠️⚠️ AND THERE IS NO PRICING ORACLE. The only inputs are two DATES and a month cursor. ⚠️ There is no
 * property parameter — {@see DEMO_SLUG} is a constant, so no request can make this price, name or expose
 * any property but the demo one. That is the whole reason it is a constant and not an argument: an endpoint
 * that took a property id would price every customer's rates for anyone who asked. Dates are clamped to a
 * bounded forward window and a maximum stay; anything unparseable or out of range falls back to the
 * default stay rather than to a guessed price. The parameterised path is rate-limited (`throttle:try-booking`).
 *
 * ⚠️⚠️ AND IT WRITES NOTHING. `BookingEngineService::quote()` persists a `booking_quotes` row, so it is
 * NEVER called here; `priceOver()` is the read-only half that runs before that write. The reservation used
 * for the mail preview is constructed in memory and never saved. No reservation, no quote row, no email
 * sent, no money moved — however many times the visitor picks.
 *
 * ⚠️⚠️ MAIL-PREVIEW SAFETY (#33). The real mail embeds five live URLs — `receiptUrl`, `reservationUrl`,
 * `checkinUrl`, `messagesUrl` and ⚠️ **`arrivalUrl`, which is the arrival-secrets page**. A preview that
 * emitted working links to that mechanism would be a real defect, not a cosmetic one. Every URL the mail
 * computes is therefore NEUTRALISED before the template sees it, and the reservation carries a synthetic,
 * unmistakably-sandbox identifier rather than a real key. The template is the real one; only the hrefs are
 * defused, and tests/Feature/MarketingTryBookingTest.php asserts none survives — on the picked path too,
 * because the interactive route is a new way to reach the same render. Referenced by path, ⚠️ not imported:
 * app code must not depend on a test class, and Gate 3b shipped a page claiming "Guarded by X" while X did
 * not exist.
 */
final class TryBookingSandbox
{
    /** ⚠️ Unmistakably not a real reference, and never persisted. */
    public const SANDBOX_REFERENCE = 'SANDBOX-PREVIEW';

    /**
     * The demo property the sandbox mirrors. Real, seeded, and publicly bookable at /book/seaside-demo.
     *
     * ⚠️ A CONSTANT, NEVER A PARAMETER. This is the entire anti-oracle design: with no property input there
     * is no request that can make this class read another organisation's rates.
     */
    public const DEMO_SLUG = 'seaside-demo';

    /** The longest stay the picker will price. Bounds both the UI and the work one request can cause. */
    public const MAX_NIGHTS = 14;

    /** Months rendered side by side. Bounds the per-night pricing work per request (~62 nights). */
    public const MONTHS_SHOWN = 2;

    /** The default stay when the visitor has picked nothing: the first run of N bookable nights. */
    private const DEFAULT_NIGHTS = 3;

    /**
     * The sandbox for THIS request — the picked stay if the visitor picked one, else the default.
     *
     * ⚠️ ONE ENTRY POINT. The page, the calendar and the mail preview all read this, so they cannot
     * disagree about which stay is on screen — the view resolves it ONCE and hands the same array to
     * {@see confirmationPreview()}.
     *
     * ⚠️ AND IT HOLDS NO STATE. An earlier version memoised the result in a static, which cost nothing
     * here (the view already resolves it once) and would have been a real hazard under a persistent
     * worker, where a static outlives the request that filled it — one visitor's picked dates leaking
     * into the next visitor's page. It also quietly weakened the pricing-oracle test, whose seven attacks
     * share a cache key and so would have re-read one cached answer instead of attacking seven times.
     *
     * @return array<string, mixed>|null
     */
    public static function fromRequest(?Request $request = null): ?array
    {
        $request ??= request();

        return self::data(
            self::stringParam($request, 'from'),
            self::stringParam($request, 'to'),
            self::stringParam($request, 'm'),
        );
    }

    /**
     * The sandbox's data, or NULL when the demo property does not exist.
     *
     * ⚠️ NULL IS A REAL STATE, NOT AN EDGE CASE. Production is never seeded (DEPLOYMENT §4), so
     * `seaside-demo` may simply not be there — and the page must then say so rather than invent a stand-in.
     * That is the whole point of mirroring: with nothing to mirror, there is nothing honest to show.
     *
     * @return array<string, mixed>|null
     */
    public static function data(?string $from = null, ?string $to = null, ?string $month = null): ?array
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
        return app(TenantContext::class)->runAsSuperAdmin(static function () use ($from, $to, $month): ?array {
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

            // ⚠️ "TODAY" IS THE PROPERTY'S TODAY. Stay dates are the property's calendar days, not the
            // server's (the Time convention); Faro and the host can disagree for an hour a day.
            $today = CarbonImmutable::now((string) ($property->timezone ?: config('app.timezone')))->startOfDay();
            $today = CarbonImmutable::parse($today->toDateString());

            $published = self::publishedNights($ratePlan, $today);

            if ($published === []) {
                return null;
            }

            $lastNight = CarbonImmutable::parse((string) array_key_last($published));
            $currency = (string) ($ratePlan->currency ?? $property->currency ?? 'EUR');
            $engine = app(BookingEngineService::class);

            $selection = self::selection($engine, $ratePlan, $roomType, $today, $lastNight, $published, $from, $to);
            $cursor = self::monthCursor($month, $selection['checkIn'], $today, $lastNight);
            $calendar = self::calendar($engine, $ratePlan, $roomType, $cursor, $today, $lastNight, $selection, $currency, $month);

            $nights = $selection['nights'];
            $totalMinor = $selection['totalMinor'];

            return [
                'property' => $property,
                'propertyName' => (string) $property->name,
                'place' => self::placeLabel($property),
                'roomTypeName' => (string) $roomType->name,
                'units' => Unit::query()->where('room_type_id', $roomType->getKey())->count(),
                'ratePlanName' => (string) $ratePlan->name,
                'checkIn' => $selection['checkIn'],
                'checkOut' => $selection['checkOut'],
                'nights' => $nights,
                'currency' => $currency,
                'totalMinor' => $totalMinor,
                'total' => $totalMinor === null ? null : MoneyFormatter::display($totalMinor, $currency),
                'perNight' => $totalMinor === null || $nights < 1
                    ? null
                    : MoneyFormatter::display(intdiv($totalMinor, $nights), $currency),
                // 'priced' | 'unavailable' | 'unpriced' | 'partial'
                'status' => $selection['status'],
                'picked' => $selection['picked'],
                'soldOutNights' => $selection['soldOutNights'],
                'unpricedNights' => $selection['unpricedNights'],
                'calendar' => $calendar,
                'clearUrl' => self::url([]),
                'bookForRealUrl' => '/book/'.self::DEMO_SLUG,
                'maxNights' => self::MAX_NIGHTS,
                'lastNight' => $lastNight,
                'publishedCount' => count($published),
            ];
        });
    }

    /**
     * The REAL confirmation email for the stay currently on screen, every link defused — or NULL when
     * there is nothing honest to confirm.
     *
     * ⚠️ This is the real `BookingConfirmationMail`: its own `content()` computes the variables (guest
     * name, reference, the reconciling total, the folio breakdown, the branded template per #86), and the
     * real `emails.booking-confirmation` template renders them. The ONLY departure is that the five URL
     * variables are replaced with a dead anchor before rendering — the safety requirement, applied as
     * narrowly as it can be.
     *
     * ⚠️ IT FOLLOWS THE PICKER. The stay it confirms is the stay the visitor selected, because the total is
     * re-priced server-side per request — so the email can never show a different stay from the one above
     * it. ⚠️ And it does not render at all for a stay that could not happen (an unpriced night, or a night
     * with no room free): a confirmation for an impossible booking is exactly the fabrication Gate 4
     * removed.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function confirmationPreview(?array $data = null): ?string
    {
        $data ??= self::fromRequest();

        if ($data === null || $data['status'] !== 'priced') {
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
        // reservation identifier. None may be actionable from a public marketing page. ⚠️ This runs on the
        // PICKED path too — the interactive route is a new way to reach this same render, not a new render.
        foreach (['receiptUrl', 'reservationUrl', 'arrivalUrl', 'checkinUrl', 'messagesUrl'] as $urlKey) {
            $with[$urlKey] = '#';
        }
        // Calendar links embed the same identifiers in query strings.
        $with['calendar'] = ['google' => '#', 'outlook' => '#', 'ics' => '#'];

        return View::make($content->view ?? 'emails.booking-confirmation', $with)->render();
    }

    /**
     * The pieces {@see MoneyFormatter::display()} builds a string from, for a template that must show the
     * same shape without re-deriving it.
     *
     * ⚠️ GENERATED, AND PROVEN SUFFICIENT. `MarketingTryBookingTest` reassembles a display string from
     * exactly these parts and requires it to equal `MoneyFormatter::display()` across a spread of amounts —
     * so if the formatter ever changes shape (a different symbol position, separator or precision), the
     * descriptor reds instead of silently describing the old one.
     *
     * @return array{symbol: string, decimals: int, decimal: string, thousands: string}
     */
    public static function moneyFormat(string $currency): array
    {
        return [
            'symbol' => MoneyFormatter::symbol($currency),
            'decimals' => 2,
            'decimal' => '.',
            'thousands' => ',',
        ];
    }

    // ── the picked stay ─────────────────────────────────────────────────────────────────────────────

    /**
     * Resolve what the visitor asked for into a stay — or refuse honestly.
     *
     * ⚠️ THE FAIL-SAFE DIRECTION (§N.18.4). A parameter that is unparseable, in the past, beyond the
     * published window or longer than MAX_NIGHTS falls back to the DEFAULT stay: it is a marketing page,
     * and the visitor sees a real price for a real window rather than an error. ⚠️ But a stay that is
     * genuinely UNPRICED or genuinely SOLD OUT does NOT fall back — it is reported as what it is. The
     * difference matters: the first is a bad input, the second is the truth about these dates, and quietly
     * replacing the truth with a working price is the fabrication this whole page exists to avoid.
     *
     * @param  array<string, int>  $published
     * @return array{checkIn: CarbonImmutable, checkOut: CarbonImmutable, nights: int, totalMinor: int|null, status: string, picked: bool, soldOutNights: int, unpricedNights: int}
     */
    private static function selection(
        BookingEngineService $engine,
        RatePlan $ratePlan,
        RoomType $roomType,
        CarbonImmutable $today,
        CarbonImmutable $lastNight,
        array $published,
        ?string $from,
        ?string $to,
    ): array {
        $checkIn = self::parseDate($from, $today, $lastNight);
        $checkOut = $checkIn === null ? null : self::parseDate($to, $today->addDay(), $lastNight->addDay());

        // A check-out that does not follow its check-in, or runs past the bound, is not a stay.
        if ($checkIn !== null && $checkOut !== null
            && ($checkOut->lte($checkIn) || $checkIn->diffInDays($checkOut) > self::MAX_NIGHTS)) {
            $checkOut = null;
        }

        if ($checkIn === null) {
            // Nothing usable was asked for — show the default stay rather than an error (§N.18.4).
            $picked = false;
            [$stayIn, $stayOut] = self::defaultStay($roomType, $today, $published);
        } elseif ($checkOut === null) {
            // Check-in without a check-out is the halfway state of picking a range, not an error.
            return [
                'checkIn' => $checkIn, 'checkOut' => $checkIn->addDay(), 'nights' => 0,
                'totalMinor' => null, 'status' => 'partial', 'picked' => true,
                'soldOutNights' => 0, 'unpricedNights' => 0,
            ];
        } else {
            $picked = true;
            [$stayIn, $stayOut] = [$checkIn, $checkOut];
        }

        $nights = (int) $stayIn->diffInDays($stayOut);

        // ⚠️ THE ENGINE'S OWN PRICE — the same method `quote()` calls, over the same rate_calendar rows,
        // for exactly the dates on screen. Null when ANY night is unpriced: a real state, not a bug.
        $totalMinor = $engine->priceOver(
            (string) $ratePlan->getKey(), $stayIn->toDateString(), $stayOut->toDateString(),
        );

        $availability = app(AvailabilityService::class)->availabilityRange(
            (string) $roomType->getKey(), $stayIn->toDateString(), $stayOut->toDateString(),
        );
        $soldOut = count(array_filter($availability, static fn (int $free): bool => $free < 1));

        $unpriced = 0;
        if ($totalMinor === null) {
            for ($d = $stayIn; $d->lt($stayOut); $d = $d->addDay()) {
                $unpriced += isset($published[$d->toDateString()]) ? 0 : 1;
            }
        }

        $status = match (true) {
            $totalMinor === null => 'unpriced',
            $soldOut > 0 => 'unavailable',
            default => 'priced',
        };

        return [
            'checkIn' => $stayIn, 'checkOut' => $stayOut, 'nights' => $nights,
            'totalMinor' => $totalMinor, 'status' => $status, 'picked' => $picked,
            'soldOutNights' => $soldOut, 'unpricedNights' => $unpriced,
        ];
    }

    /**
     * The default stay — the first run of {@see DEFAULT_NIGHTS} consecutive nights that are BOTH published
     * and have a room free.
     *
     * ⚠️ TWO EARLIER ATTEMPTS WERE WRONG, AND BOTH FAILED QUIETLY. A fixed offset from "today" fell outside
     * the seeded window; anchoring to the calendar's FIRST row landed in the past. And the calendar can
     * have GAPS — the engine's `priceOver()` returns null if any single night is unpriced, which reads
     * exactly like "the sandbox is broken". ⚠️ Gate 4b adds the availability half: a default stay the
     * storefront would refuse is not a good advertisement for the storefront.
     *
     * @param  array<string, int>  $published
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function defaultStay(RoomType $roomType, CarbonImmutable $today, array $published): array
    {
        $dates = array_keys($published);
        $free = app(AvailabilityService::class)->availabilityRange(
            (string) $roomType->getKey(),
            $today->toDateString(),
            CarbonImmutable::parse((string) array_key_last($published))->addDay()->toDateString(),
        );

        foreach ($dates as $candidate) {
            $start = CarbonImmutable::parse($candidate);
            $run = true;

            for ($i = 0; $i < self::DEFAULT_NIGHTS; $i++) {
                $night = $start->addDays($i)->toDateString();
                if (! isset($published[$night]) || ($free[$night] ?? 0) < 1) {
                    $run = false;
                    break;
                }
            }

            if ($run) {
                return [$start, $start->addDays(self::DEFAULT_NIGHTS)];
            }
        }

        // Nothing bookable ahead — a real state. The caller prices it, and the page reports what it finds
        // rather than inventing a stay.
        $first = CarbonImmutable::parse((string) array_key_first($published));

        return [$first, $first->addDays(self::DEFAULT_NIGHTS)];
    }

    // ── the calendar ────────────────────────────────────────────────────────────────────────────────

    /**
     * The visible months, as day cells carrying the REAL nightly price and the REAL free-unit count.
     *
     * ⚠️ EVERY CELL'S PRICE IS `priceOver()`'s OWN OUTPUT for that one night — not a rate_calendar row read
     * a second way. That is the difference between showing the engine's price and showing a number that
     * agrees with it today.
     *
     * @param  array<string, mixed>  $selection
     * @return array<string, mixed>
     */
    private static function calendar(
        BookingEngineService $engine,
        RatePlan $ratePlan,
        RoomType $roomType,
        CarbonImmutable $cursor,
        CarbonImmutable $today,
        CarbonImmutable $lastNight,
        array $selection,
        string $currency,
        ?string $monthParam,
    ): array {
        $windowStart = $cursor;
        $windowEnd = $cursor->addMonths(self::MONTHS_SHOWN)->startOfMonth();

        $free = app(AvailabilityService::class)->availabilityRange(
            (string) $roomType->getKey(), $windowStart->toDateString(), $windowEnd->toDateString(),
        );

        // ⚠️ THE HALFWAY STATE HAS A CHECK-IN AND NO CHECK-OUT, and the difference decides where every day
        // cell points. It carries `picked => true` (the visitor really did pick something) AND a
        // placeholder `checkOut` so the array shape stays fixed — so keying the calendar off `picked`
        // alone made every later cell RESTART the range instead of completing it, and the picker could
        // render perfectly while being impossible to actually pick with. Found in the browser, by
        // clicking twice; no assertion in the suite had ever completed a range.
        $partial = $selection['status'] === 'partial';
        $checkIn = $partial || $selection['picked'] ? $selection['checkIn'] : null;
        $checkOut = $partial ? null : ($selection['picked'] ? $selection['checkOut'] : null);
        $carry = $monthParam === null ? [] : ['m' => $cursor->format('Y-m')];

        $months = [];
        for ($i = 0; $i < self::MONTHS_SHOWN; $i++) {
            $monthStart = $cursor->addMonths($i)->startOfMonth();
            $cells = [];

            // Lead the grid with the blanks before the 1st (weeks start Monday, as the design draws them).
            for ($b = 1; $b < (int) $monthStart->dayOfWeekIso; $b++) {
                $cells[] = null;
            }

            for ($day = $monthStart; $day->month === $monthStart->month; $day = $day->addDay()) {
                $iso = $day->toDateString();
                $past = $day->lt($today);
                $beyond = $day->gt($lastNight);
                $priceMinor = $past || $beyond ? null : $engine->priceOver($ratePlan->getKey(), $iso, $day->addDay()->toDateString());
                $available = (int) ($free[$iso] ?? 0);

                $cells[] = [
                    'date' => $iso,
                    'day' => (int) $day->day,
                    'past' => $past,
                    'priceMinor' => $priceMinor,
                    'price' => $priceMinor === null ? null : MoneyFormatter::display($priceMinor, $currency),
                    'available' => $available,
                    'selectable' => ! $past && $priceMinor !== null && $available > 0,
                    'isCheckIn' => $checkIn !== null && $iso === $checkIn->toDateString(),
                    'isCheckOut' => $checkOut !== null && $iso === $checkOut->toDateString(),
                    'inStay' => $checkOut !== null && $checkIn !== null
                        && $day->gte($checkIn) && $day->lt($checkOut),
                    'href' => self::dayUrl($iso, $checkIn, $checkOut, $carry),
                ];
            }

            $months[] = ['label' => $monthStart->format('F Y'), 'cells' => $cells];
        }

        $prev = $cursor->subMonth()->startOfMonth();
        $next = $cursor->addMonth()->startOfMonth();
        $keep = array_filter([
            'from' => $checkIn?->toDateString(),
            'to' => $checkOut?->toDateString(),
        ]);

        return [
            'months' => $months,
            'prevUrl' => $prev->lt($today->startOfMonth()) ? null : self::url($keep + ['m' => $prev->format('Y-m')]),
            'nextUrl' => $next->gt($lastNight->startOfMonth()) ? null : self::url($keep + ['m' => $next->format('Y-m')]),
        ];
    }

    /**
     * Where one day cell points.
     *
     * ⚠️ THE WHOLE PICKER IS LINKS. With no check-in, a day sets one; with a check-in and no check-out, a
     * later day completes the stay and an earlier one starts over. There is no JavaScript, so there is no
     * second place a price could be computed — the server re-prices whatever the link asks for.
     *
     * @param  array<string, string>  $carry
     */
    private static function dayUrl(string $iso, ?CarbonImmutable $checkIn, ?CarbonImmutable $checkOut, array $carry): string
    {
        if ($checkIn !== null && $checkOut === null && $iso > $checkIn->toDateString()) {
            return self::url(['from' => $checkIn->toDateString(), 'to' => $iso] + $carry);
        }

        return self::url(['from' => $iso] + $carry);
    }

    /** @param array<string, string> $params */
    private static function url(array $params): string
    {
        return '/try-booking'.($params === [] ? '' : '?'.http_build_query($params)).'#vy-sandbox-stay';
    }

    // ── reading the demo property, narrowly ─────────────────────────────────────────────────────────

    /**
     * Every published night from today onward, date => price_minor.
     *
     * ⚠️ Called only from inside the super-admin window in {@see data()}, so a plain query is correctly
     * scoped. Used to decide WHICH nights exist; the PRICE a visitor is shown always comes back through
     * `priceOver()`.
     *
     * @return array<string, int>
     */
    private static function publishedNights(RatePlan $ratePlan, CarbonImmutable $today): array
    {
        $rows = RateCalendar::query()
            ->where('rate_plan_id', $ratePlan->getKey())
            ->whereDate('date', '>=', $today->toDateString())
            ->orderBy('date')
            ->get(['date', 'price_minor']);

        $out = [];
        foreach ($rows as $row) {
            $out[CarbonImmutable::parse((string) $row->date)->toDateString()] = (int) $row->price_minor;
        }

        return $out;
    }

    /** The month the calendar opens on: the explicit cursor, else the check-in's month, else this month. */
    private static function monthCursor(?string $month, CarbonImmutable $checkIn, CarbonImmutable $today, CarbonImmutable $lastNight): CarbonImmutable
    {
        $floor = $today->startOfMonth();
        $ceiling = $lastNight->startOfMonth()->subMonths(max(0, self::MONTHS_SHOWN - 1));
        $ceiling = $ceiling->lt($floor) ? $floor : $ceiling;

        $cursor = $checkIn->startOfMonth();

        if ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            try {
                $cursor = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
            } catch (\Throwable) {
                // An unparseable cursor is a bad input, not a state: fall back to the stay's own month.
            }
        }

        return match (true) {
            $cursor->lt($floor) => $floor,
            $cursor->gt($ceiling) => $ceiling,
            default => $cursor,
        };
    }

    /** A date parameter, or null if it is not a plain date inside the bounded window. */
    private static function parseDate(?string $raw, CarbonImmutable $min, CarbonImmutable $max): ?CarbonImmutable
    {
        if ($raw === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) !== 1) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($date->toDateString() !== $raw || $date->lt($min) || $date->gt($max)) {
            return null;
        }

        return $date;
    }

    private static function stringParam(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? $value : null;
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
