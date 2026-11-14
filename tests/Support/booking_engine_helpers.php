<?php

use App\Models\Organization;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Booking\BookingTerms;
use App\Support\Money\Money;
use Illuminate\Support\Str;

if (! function_exists('publicPropertyScenario')) {
    /**
     * A fresh org + a public (slugged) property with one room_type ($units units), a priced rate
     * plan, and published availability. Leaves tenant context on the org (as a real request would
     * after ResolvePublicProperty). Stripe is the selected gateway.
     *
     * @return array{org: Organization, property: Property, roomType: RoomType, ratePlan: RatePlan, units: list<Unit>}
     */
    function publicPropertyScenario(int $units = 1, int $nightlyMinor = 12000): array
    {
        config(['services.payments.gateway' => 'stripe']);

        $org = newDemoOrg();
        $property = Property::create([
            'name' => 'Public Hotel',
            'slug' => 'public-'.Str::lower(Str::random(8)),
            'type' => 'hotel',
            'currency' => 'EUR',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
        $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Std', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 2]);

        $unitModels = [];
        for ($i = 1; $i <= $units; $i++) {
            $unitModels[] = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'P'.$i]);
        }

        $ratePlan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);

        app(AvailabilityService::class)->recompute($roomType->id, '2026-09-01', '2026-09-10');
        app(RateAndRestrictionService::class)->setRate($ratePlan, '2026-09-01', '2026-09-10', Money::of($nightlyMinor, 'EUR'));

        return ['org' => $org, 'property' => $property, 'roomType' => $roomType, 'ratePlan' => $ratePlan, 'units' => $unitModels];
    }
}

if (! function_exists('acceptedTerms')) {
    /**
     * The two fields the public checkout form posts when the guest ticks the booking-terms box (his ruling 2026-11-14): the box itself and the VERSION of
     * the terms the page showed. ⚠️ A test that books through the public checkout or the JSON `book` endpoint must send these — the terms are a rule of the
     * booking, not a thing to switch off; a test that is NOT about them takes them from here so its own guard is not masked by a terms refusal.
     *
     * @return array{terms_accepted: string, terms_version: string}
     */
    function acceptedTerms(Property $property, string $checkIn = '2026-09-01'): array
    {
        return ['terms_accepted' => '1', 'terms_version' => BookingTerms::for(Property::query()->findOrFail($property->getKey()), $checkIn)['version']];
    }
}
