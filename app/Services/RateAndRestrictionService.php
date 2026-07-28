<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The minimal write path for `rate_calendar` / `restrictions` (Phase 1 shipped only the schema —
 * no write path existed until Step 2's ARI-push wiring needed one). Each write is a plain
 * upsert-per-night in a transaction, then a single post-commit `rate_calendar.updated` /
 * `restriction.updated` domain event covering the whole edited range — the payload shape
 * ({room_type_id, start_date, end_date}) matches `inventory_block.created` so
 * `EnqueueAriPushOnInventoryChange` reads it identically.
 *
 * Does NOT touch inventory/`sold`/the AssignmentService locking core — pricing and stay-rules
 * are a completely separate concern from the no-double-booking guarantee.
 */
class RateAndRestrictionService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Set the same price for every night in [startDate, endDate) on one rate_plan.
     */
    public function setRate(RatePlan $ratePlan, CarbonInterface|string $startDate, CarbonInterface|string $endDate, Money $price): void
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        DB::transaction(function () use ($ratePlan, $start, $end, $price): void {
            for ($date = $start; $date->lt($end); $date = $date->addDay()) {
                RateCalendar::query()->updateOrCreate(
                    ['rate_plan_id' => $ratePlan->getKey(), 'date' => $date->toDateString()],
                    ['price' => $price],
                );
            }
        });

        $payload = [
            'room_type_id' => $ratePlan->room_type_id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];

        $this->domainEvents->record('rate_calendar.updated', $ratePlan, $payload);
        $this->audit->record('rate_calendar.updated', $ratePlan, null, $payload + ['price_minor' => $price->amountMinor(), 'currency' => $price->currency]);
    }

    /**
     * Set a WEEKEND-AWARE nightly rate over [startDate, endDate): the weekday price on Mon–Thu + Sun nights, and
     * the (higher) weekend price on Fri + Sat nights. One transaction + ONE `rate_calendar.updated` event for the
     * range (never per-day event spam). Powers the owner's weekend-uplift policy — real prices, not an inert flag.
     */
    public function setRateWeekendAware(RatePlan $ratePlan, CarbonInterface|string $startDate, CarbonInterface|string $endDate, Money $weekday, Money $weekend): void
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        DB::transaction(function () use ($ratePlan, $start, $end, $weekday, $weekend): void {
            for ($date = $start; $date->lt($end); $date = $date->addDay()) {
                $isWeekend = in_array($date->dayOfWeekIso, [5, 6], true);   // Fri = 5, Sat = 6 (check-in night)
                RateCalendar::query()->updateOrCreate(
                    ['rate_plan_id' => $ratePlan->getKey(), 'date' => $date->toDateString()],
                    ['price' => $isWeekend ? $weekend : $weekday],
                );
            }
        });

        $payload = [
            'room_type_id' => $ratePlan->room_type_id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];

        $this->domainEvents->record('rate_calendar.updated', $ratePlan, $payload);
        $this->audit->record('rate_calendar.updated', $ratePlan, null, $payload + [
            'price_minor' => $weekday->amountMinor(),
            'weekend_price_minor' => $weekend->amountMinor(),
            'currency' => $weekday->currency,
        ]);
    }

    /**
     * Upsert the same restriction flags for every night in [startDate, endDate), scoped to a
     * room_type and/or a rate_plan (at least one required — matches the `restrictions` schema).
     *
     * @param  array{room_type_id?: string|null, rate_plan_id?: string|null, min_stay?: int|null, max_stay?: int|null, closed_to_arrival?: bool, closed_to_departure?: bool, stop_sell?: bool}  $attributes
     */
    public function setRestriction(array $attributes, CarbonInterface|string $startDate, CarbonInterface|string $endDate): void
    {
        $roomTypeId = $attributes['room_type_id'] ?? null;
        $ratePlanId = $attributes['rate_plan_id'] ?? null;

        if ($roomTypeId === null && $ratePlanId === null) {
            throw new InvalidArgumentException('setRestriction() requires room_type_id and/or rate_plan_id.');
        }

        if ($roomTypeId === null) {
            $roomTypeId = RatePlan::query()->findOrFail($ratePlanId)->room_type_id;
        }

        $roomType = RoomType::query()->findOrFail($roomTypeId);
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();
        $flags = Arr::only($attributes, ['min_stay', 'max_stay', 'closed_to_arrival', 'closed_to_departure', 'stop_sell']);

        DB::transaction(function () use ($roomTypeId, $ratePlanId, $start, $end, $flags): void {
            for ($date = $start; $date->lt($end); $date = $date->addDay()) {
                Restriction::query()->updateOrCreate(
                    ['room_type_id' => $roomTypeId, 'rate_plan_id' => $ratePlanId, 'date' => $date->toDateString()],
                    $flags,
                );
            }
        });

        $payload = [
            'room_type_id' => $roomTypeId,
            'rate_plan_id' => $ratePlanId,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];

        $this->domainEvents->record('restriction.updated', $roomType, $payload);
        $this->audit->record('restriction.updated', $roomType, null, $payload + $flags);
    }
}
