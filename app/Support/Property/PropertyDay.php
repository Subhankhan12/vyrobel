<?php

declare(strict_types=1);

namespace App\Support\Property;

use App\Models\Property;
use App\Models\ReservationUnit;
use App\Models\User;
use App\Support\Schedule\BoardDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * THE DAY IT IS AT THE PROPERTY (D-DAY-ELSEWHERE, 2026-11-11, his go) — the one place that turns "today" into the property's own day for
 * the surfaces that were still on the app's (UTC) day: the top bar's date pill, the Housekeeping board's Today / Tomorrow / Overdue and its
 * "guest arriving today" urgency, and the "Assign all" order. The Calendar, the Front Desk and the walk-in already read {@see BoardDate::today()}
 * (D-RACK-START-TZ); this is the same rule for a LIST of properties and for a request that cannot see the property context.
 *
 * ⚠️ A property with no timezone, or none at all, gets the app's day — exactly what those screens showed before.
 * ⚠️ "All properties" can span timezones, so a day is always per PROPERTY: a room is judged against the day at ITS property.
 */
final class PropertyDay
{
    /**
     * Each property's own today (`Y-m-d`), keyed by property id.
     *
     * @param  list<string>  $propertyIds
     * @return array<string, string>
     */
    public static function todays(array $propertyIds): array
    {
        if ($propertyIds === []) {
            return [];
        }
        $zones = Property::query()->whereIn('id', $propertyIds)->pluck('timezone', 'id');

        $out = [];
        foreach ($propertyIds as $id) {
            $zone = $zones[$id] ?? null;
            $out[(string) $id] = BoardDate::today(is_string($zone) && $zone !== '' ? $zone : null)->toDateString();
        }

        return $out;
    }

    /**
     * The properties grouped by the day it is at each one: `date => [property ids]` (usually a single group).
     *
     * @param  list<string>  $propertyIds
     * @return array<string, list<string>>
     */
    public static function groupedByToday(array $propertyIds): array
    {
        $groups = [];
        foreach (self::todays($propertyIds) as $id => $date) {
            $groups[$date][] = (string) $id;
        }

        return $groups;
    }

    /**
     * The active reservation lines CHECKING IN TODAY AT THEIR OWN PROPERTY (a cancelled or no-show booking excluded) — the rooms with a guest
     * arriving, for the Housekeeping urgency and for "Assign all"'s order. Each line carries `reservation:id,status`.
     *
     * @param  list<string>  $propertyIds
     * @return Collection<int, ReservationUnit>
     */
    public static function arrivingToday(array $propertyIds): Collection
    {
        $lines = collect();
        foreach (self::groupedByToday($propertyIds) as $date => $ids) {
            $lines = $lines->concat(
                ReservationUnit::query()
                    ->where('status', 'active')
                    ->whereNotNull('unit_id')
                    ->whereDate('check_in', $date)
                    ->whereHas('reservation', fn ($q) => $q->whereIn('property_id', $ids)->whereNotIn('status', ['cancelled', 'no_show']))
                    ->with('reservation:id,status')
                    ->get(['id', 'unit_id', 'reservation_id', 'check_in', 'status'])
            );
        }

        return $lines->values();
    }

    /**
     * The WORKING property's timezone for a request that may not carry the property context. A page load runs through the `/app` group, which
     * activates the context; a Livewire update (`/livewire/update`) does not — so the shell, which re-renders on both, asks the SESSION the same
     * question the context middleware asks (the person's allow-list, then the stored selection, "all" and a stale id falling back to the first).
     * Null when there is no working property or it has no timezone.
     */
    public static function workingTimezone(): ?string
    {
        $context = app(PropertyContext::class);
        if ($context->active()) {
            $id = $context->id();
        } else {
            $user = auth()->user();
            if (! $user instanceof User) {
                return null;
            }
            $selection = session('current_property_id');
            $id = $context->forProperty($user->accessiblePropertyIds(), is_string($selection) ? $selection : null, fn (): ?string => $context->id());
        }
        if ($id === null) {
            return null;
        }
        $zone = Property::query()->whereKey($id)->value('timezone');

        return is_string($zone) && $zone !== '' ? $zone : null;
    }

    /** The day it is at the working property — the top bar's date pill. */
    public static function workingToday(): CarbonImmutable
    {
        return BoardDate::today(self::workingTimezone());
    }
}
