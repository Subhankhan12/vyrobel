<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\ResolvePublicProperty;
use App\Models\BookingQuote;
use App\Models\Property;
use App\Models\RoomType;
use App\Services\BookingEngineService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public booking engine (Step 7) — JSON endpoints + one bare, UNSTYLED contract-stub landing.
 * The tenant/property context is already set by {@see ResolvePublicProperty}; every action re-loads
 * the property TENANT-SCOPED from the stashed id, so nothing here runs unscoped and property A can
 * never reach property B. Styled UI is the deferred design pass — this documents the data contract.
 */
class BookingEngineController extends Controller
{
    public function __construct(
        private readonly BookingEngineService $engine,
    ) {}

    /**
     * PUBLIC storefront — Browse / Search + sellable-room results (design pass · Booking Screen 1).
     * Server-rendered, READ-ONLY, no auth. Context is already set by {@see ResolvePublicProperty}; the
     * property is re-loaded TENANT-SCOPED, and search runs property-scoped through the certified
     * {@see BookingEngineService::search} (availability via AvailabilityService, real prices). A visitor
     * for property X can only ever see property X's inventory — no other property, no operator data.
     * This screen NEVER writes inventory (booking/checkout is a later screen).
     */
    public function browse(Request $request): View
    {
        $property = $this->property($request);

        $adults = max(1, (int) $request->query('adults', '2'));
        $children = max(0, (int) $request->query('children', '0'));
        $checkIn = $this->validDate($request->query('check_in'));
        $checkOut = $this->validDate($request->query('check_out'));

        $searched = $checkIn !== null && $checkOut !== null && $checkOut > $checkIn;

        // REAL sellable rooms for the searched dates (only when a valid range is given).
        $rooms = [];
        $nights = 0;
        if ($searched) {
            $nights = (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));
            foreach ($this->engine->search($property, $checkIn, $checkOut, $adults, $children) as $offer) {
                $rooms[] = $this->roomOffer($offer);
            }
        }

        return view('booking.browse', [
            'property' => ['name' => (string) $property->name, 'type' => $this->propertyType((string) $property->type)],
            'search' => [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
                'children' => $children,
                'searched' => $searched,
                'nights' => $nights,
            ],
            'rooms' => $rooms,
            // Teaser (no valid search yet): real room-type names only — NO availability/price claim.
            'teaser' => $searched ? [] : $this->teaserRooms($property),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['sometimes', 'integer', 'min:1'],
            'children' => ['sometimes', 'integer', 'min:0'],
        ]);

        $offers = $this->engine->search(
            $this->property($request),
            $validated['check_in'],
            $validated['check_out'],
            (int) ($validated['adults'] ?? 1),
            (int) ($validated['children'] ?? 0),
        );

        return response()->json(['data' => $offers]);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_type_id' => ['required', 'string'],
            'rate_plan_id' => ['required', 'string'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['sometimes', 'integer', 'min:1'],
            'children' => ['sometimes', 'integer', 'min:0'],
            'email' => ['sometimes', 'email'],
            'session_token' => ['sometimes', 'string'],
        ]);

        $quote = $this->engine->quote(
            $this->property($request),
            $validated['room_type_id'],
            $validated['rate_plan_id'],
            $validated['check_in'],
            $validated['check_out'],
            (int) ($validated['adults'] ?? 1),
            (int) ($validated['children'] ?? 0),
            $validated['email'] ?? null,
            $validated['session_token'] ?? null,
        );

        return response()->json(['data' => $this->quotePayload($quote)], 201);
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => ['required', 'string'],
            'payment_token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
        ]);

        // Tenant- AND property-scoped: a quote from another property/org simply isn't found.
        $quote = BookingQuote::query()
            ->where('property_id', $this->property($request)->getKey())
            ->findOrFail($validated['quote_id']);

        $result = $this->engine->book($quote, $validated['payment_token'], [
            'email' => $validated['email'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
        ]);

        $status = $result['status'] === 'confirmed' ? 201 : ($result['status'] === 'unavailable' ? 409 : 200);
        $reservation = $result['reservation'];

        return response()->json([
            'data' => [
                'status' => $result['status'],
                'reservation_reference' => $reservation?->reference,
                'payment_status' => $result['payment']?->status,
                'client_secret' => $result['client_secret'], // present when SCA/3DS is required
            ],
        ], $status);
    }

    public function confirmation(Request $request, string $quote): JsonResponse
    {
        $bookingQuote = BookingQuote::query()
            ->where('property_id', $this->property($request)->getKey())
            ->findOrFail($quote);

        return response()->json(['data' => $this->quotePayload($bookingQuote)]);
    }

    private function property(Request $request): Property
    {
        // Re-load tenant-scoped from the id the middleware resolved (context is already set).
        return Property::query()->findOrFail($request->attributes->get(ResolvePublicProperty::ATTRIBUTE));
    }

    /**
     * Shape one real search offer for the storefront. per-night is DERIVED from the real stay total
     * (honest — from real figures), never a separately-invented nightly price.
     *
     * @param  array<string, mixed>  $offer
     * @return array<string, mixed>
     */
    private function roomOffer(array $offer): array
    {
        $nights = max(1, (int) $offer['nights']);
        $totalMinor = (int) $offer['total_minor'];
        $currency = (string) $offer['currency'];

        return [
            'room_type_id' => (string) $offer['room_type_id'],
            'rate_plan_id' => (string) $offer['rate_plan_id'],
            'name' => (string) $offer['name'],
            'kind' => $this->roomKind((string) $offer['kind']),
            'available' => (int) $offer['available'],
            'nights' => $nights,
            'per_night' => $this->money((int) round($totalMinor / $nights), $currency),
            'total' => $this->money($totalMinor, $currency),
        ];
    }

    /**
     * Real room-type teaser (names/kind/capacity only) shown before a date search — no availability or
     * price is claimed, so nothing is fabricated.
     *
     * @return list<array<string, mixed>>
     */
    private function teaserRooms(Property $property): array
    {
        return RoomType::query()
            ->where('property_id', $property->getKey())
            ->orderBy('name')
            ->get()
            ->map(fn (RoomType $rt): array => [
                'name' => (string) $rt->name,
                'kind' => $this->roomKind((string) $rt->kind),
                'capacity' => (int) $rt->max_adults + (int) $rt->max_children,
            ])->all();
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

        return $symbol.number_format($minor / 100, 2, '.', ',');
    }

    private function roomKind(string $kind): string
    {
        return ucwords(str_replace('_', ' ', $kind));
    }

    private function propertyType(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(BookingQuote $quote): array
    {
        return [
            'quote_id' => $quote->getKey(),
            'reference' => $quote->reference,
            'status' => $quote->status,
            'check_in' => CarbonImmutable::parse($quote->check_in)->toDateString(),
            'check_out' => CarbonImmutable::parse($quote->check_out)->toDateString(),
            'currency' => $quote->currency,
            'total_minor' => $quote->total_minor,
            'held_until' => $quote->held_until !== null ? CarbonImmutable::parse($quote->held_until)->toIso8601String() : null,
            'expires_at' => $quote->expires_at !== null ? CarbonImmutable::parse($quote->expires_at)->toIso8601String() : null,
            'reservation_reference' => $quote->convertedReservation?->reference,
        ];
    }
}
