<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️ ADMIN 01 · PHASE 5 — HOTEL-PLATFORM STATISTICS, MODULE ADOPTION AND COHORTS. ⚠️ AGGREGATES ONLY: a count, a
 * sum, a distribution — never a row, never a guest. Customers only unless internal organisations are asked for.
 *
 *   · rooms under management — active units in live properties.
 *   · bookings per day — reservations CREATED per day, last 30 days.
 *   · occupancy distribution — each property's last-30-night occupancy (sold ÷ sellable, from the availability
 *     spine), bucketed; a property with no sellable inventory is counted apart, never as 0%.
 *   · money processed through folios — succeeded / captured payments, refunds shown apart, PER CURRENCY.
 *   · average booking value — reservations created in the last 30 days, not cancelled or no-show, per currency.
 *   · module adoption — organisations with a real usage EVENT (`domain_events`, the product's own record of every
 *     business state change) in the last 30 days, per module.
 *   · cohorts — organisations by sign-up month; "active in month k" = at least one business event in the k-th month
 *     after sign-up. ⚠️ With few organisations this says almost nothing, and the screen says so.
 *
 * Must run inside {@see PlatformQuery::read()}.
 */
final class PlatformHotelStats
{
    /** Below this many organisations, a cohort table describes individual hotels, not a trend — and says so. */
    public const TREND_FLOOR = 10;

    public const COHORT_MONTHS = [1, 3, 6, 12];

    /** Module => the domain-event types that mean it was USED. */
    public const MODULES = [
        'Front desk' => ['reservation.checked_in', 'reservation.checked_out'],
        'Reservations' => ['reservation.created', 'reservation.confirmed', 'reservation.modified'],
        'Housekeeping' => ['housekeeping.task_assigned', 'housekeeping.task_unassigned', 'unit.housekeeping_changed'],
        'Folios & payments' => ['folio.charge_posted', 'payment.recorded', 'payment.settled', 'payment.refunded'],
        'Channel manager' => ['reservation.imported', 'channel_mapping.activated', 'channel.error'],
    ];

    public const OCCUPANCY_BUCKETS = ['0–20%', '20–40%', '40–60%', '60–80%', '80–100%'];

    /** @return array<string, mixed> */
    public function compute(bool $includeInternal = false, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $orgIds = $this->orgIds($includeInternal);
        $since = $now->subDays(30);

        return [
            'include_internal' => $includeInternal,
            'organizations' => count($orgIds),
            'rooms' => $this->rooms($orgIds),
            'bookings_per_day' => $this->bookingsPerDay($orgIds, $since, $now),
            'occupancy' => $this->occupancy($orgIds, $since, $now),
            'money' => $this->money($orgIds, $since),
            'average_booking' => $this->averageBooking($orgIds, $since),
            'adoption' => $this->adoption($orgIds, $since),
            'cohorts' => $this->cohorts($includeInternal, $now),
        ];
    }

    /** @param  list<string>  $orgIds */
    private function rooms(array $orgIds): int
    {
        return DB::table('units')
            ->join('properties', 'properties.id', '=', 'units.property_id')
            ->whereIn('units.organization_id', $orgIds)
            ->whereNull('units.deleted_at')->where('units.is_active', true)
            ->whereNull('properties.deleted_at')
            ->count();
    }

    /**
     * @param  list<string>  $orgIds
     * @return array<string, int> Y-m-d => bookings, every day present (zeros included)
     */
    private function bookingsPerDay(array $orgIds, CarbonImmutable $since, CarbonImmutable $now): array
    {
        $counts = DB::table('reservations')->whereIn('organization_id', $orgIds)->where('created_at', '>=', $since->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as n')->groupBy('d')->pluck('n', 'd');

        $out = [];
        for ($day = $since->startOfDay(); $day->lessThanOrEqualTo($now); $day = $day->addDay()) {
            $out[$day->toDateString()] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return $out;
    }

    /**
     * @param  list<string>  $orgIds
     * @return array{buckets: array<string, int>, no_inventory: int}
     */
    private function occupancy(array $orgIds, CarbonImmutable $since, CarbonImmutable $now): array
    {
        $rows = DB::table('availability')
            ->join('room_types', 'room_types.id', '=', 'availability.room_type_id')
            ->join('properties', 'properties.id', '=', 'room_types.property_id')
            ->whereIn('availability.organization_id', $orgIds)->whereNull('properties.deleted_at')
            ->where('availability.date', '>=', $since->toDateString())->where('availability.date', '<', $now->toDateString())
            ->groupBy('properties.id')
            ->selectRaw('properties.id, SUM(availability.sold) as sold, SUM(availability.total - availability.out_of_order - availability.blocked) as sellable')
            ->get();

        $buckets = array_fill_keys(self::OCCUPANCY_BUCKETS, 0);
        $propertyCount = DB::table('properties')->whereIn('organization_id', $orgIds)->whereNull('deleted_at')->count();
        $measured = 0;
        foreach ($rows as $r) {
            if ((int) $r->sellable <= 0) {
                continue;
            }
            $pct = min(100, (int) $r->sold * 100 / (int) $r->sellable);
            $buckets[self::OCCUPANCY_BUCKETS[min(4, intdiv((int) $pct, 20))]]++;
            $measured++;
        }

        return ['buckets' => $buckets, 'no_inventory' => $propertyCount - $measured];
    }

    /**
     * @param  list<string>  $orgIds
     * @return array<string, array{processed_minor: int, refunded_minor: int, processed_30d_minor: int}>
     */
    private function money(array $orgIds, CarbonImmutable $since): array
    {
        $base = fn () => DB::table('payments')->whereIn('organization_id', $orgIds);
        $in = fn ($q) => $q->whereIn('type', ['payment', 'capture'])->whereIn('status', ['succeeded', 'captured']);

        $out = [];
        foreach ($in($base())->groupBy('currency')->selectRaw('currency, SUM(amount_minor) as s')->pluck('s', 'currency') as $c => $s) {
            $out[$c]['processed_minor'] = (int) $s;
        }
        foreach ($in($base())->where('created_at', '>=', $since)->groupBy('currency')->selectRaw('currency, SUM(amount_minor) as s')->pluck('s', 'currency') as $c => $s) {
            $out[$c]['processed_30d_minor'] = (int) $s;
        }
        foreach ($base()->where('type', 'refund')->whereIn('status', ['succeeded', 'refunded'])->groupBy('currency')->selectRaw('currency, SUM(ABS(amount_minor)) as s')->pluck('s', 'currency') as $c => $s) {
            $out[$c]['refunded_minor'] = (int) $s;
        }
        foreach ($out as $c => $row) {
            $out[$c] += ['processed_minor' => 0, 'refunded_minor' => 0, 'processed_30d_minor' => 0];
        }
        ksort($out);

        return $out;
    }

    /**
     * @param  list<string>  $orgIds
     * @return array<string, array{average_minor: int, bookings: int}>
     */
    private function averageBooking(array $orgIds, CarbonImmutable $since): array
    {
        return DB::table('reservations')->whereIn('organization_id', $orgIds)->where('created_at', '>=', $since)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->groupBy('currency')->selectRaw('currency, AVG(total_minor) as a, COUNT(*) as n')->get()
            ->mapWithKeys(fn (object $r): array => [(string) $r->currency => ['average_minor' => (int) round((float) $r->a), 'bookings' => (int) $r->n]])
            ->sortKeys()->all();
    }

    /**
     * @param  list<string>  $orgIds
     * @return array<string, int> module => organisations that used it in the last 30 days
     */
    private function adoption(array $orgIds, CarbonImmutable $since): array
    {
        $out = [];
        foreach (self::MODULES as $module => $types) {
            $out[$module] = DB::table('domain_events')->whereIn('organization_id', $orgIds)->whereIn('event_type', $types)
                ->where('occurred_at', '>=', $since)->distinct()->count('organization_id');
        }

        return $out;
    }

    /**
     * @return list<array{month: string, size: int, active: array<int, ?int>}> active[k] null = that month has not ended yet
     */
    private function cohorts(bool $includeInternal, CarbonImmutable $now): array
    {
        $q = Organization::withTrashed();
        if (! $includeInternal) {
            $q->customers();
        }
        $orgs = $q->get(['id', 'created_at'])->groupBy(fn (Organization $o) => $o->created_at?->format('Y-m') ?? 'unknown');

        $out = [];
        foreach ($orgs->sortKeys() as $month => $members) {
            $active = [];
            foreach (self::COHORT_MONTHS as $k) {
                // ⚠️ The cohort's month k is over only when its LATEST member's month k is over — a hotel that joined on
                //    the 31st must not be counted inactive in a window it has not finished living through.
                $windowEnds = CarbonImmutable::parse($members->max('created_at'), 'UTC')->addMonthsNoOverflow($k + 1);
                if ($windowEnds->greaterThan($now)) {
                    $active[$k] = null;   // not over yet for this cohort — never counted as a zero

                    continue;
                }
                $active[$k] = $members->filter(function (Organization $o) use ($k): bool {
                    $from = CarbonImmutable::parse($o->created_at, 'UTC')->addMonthsNoOverflow($k);

                    return DB::table('domain_events')->where('organization_id', $o->id)
                        ->where('occurred_at', '>=', $from)->where('occurred_at', '<', $from->addMonthNoOverflow())->exists();
                })->count();
            }
            $out[] = ['month' => (string) $month, 'size' => $members->count(), 'active' => $active];
        }

        return $out;
    }

    /** @return list<string> */
    private function orgIds(bool $includeInternal): array
    {
        $q = Organization::query();
        if (! $includeInternal) {
            $q->customers();
        }

        return $q->pluck('id')->map(fn ($id): string => (string) $id)->all();
    }
}
