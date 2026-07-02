<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\ResolvePublicProperty;
use App\Models\BookingQuote;
use App\Models\Property;
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

    /** Bare, unstyled landing that documents the data contract each screen consumes. */
    public function stub(Request $request): View
    {
        return view('booking.stub', ['property' => $this->property($request)]);
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
