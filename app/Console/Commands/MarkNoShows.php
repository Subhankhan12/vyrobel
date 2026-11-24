<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\Booking\ReservationAlreadyTerminalException;
use App\Exceptions\Booking\ReservationInProgressException;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Models\Scopes\OrganizationScope;
use App\Services\AssignmentService;
use App\Support\Reservations\CancellationReason;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * ⚠️⚠️ VY-26 (audit of 2026-11-24, read in code) — AUTOMATIC NO-SHOWS, ONLY WHERE THE PROPERTY ASKED FOR THEM.
 *
 * A booking whose guest never came stayed "confirmed" — holding its room for the rest of the stay — until someone at the desk pressed
 * "No-show". A property that switches on `auto_no_show` (Settings → Policies; default OFF) now has it done for it: a CONFIRMED booking
 * (never one checked in, never one already ended) whose guest has not checked in by the property's check-out time on the day AFTER
 * arrival — in the property's own time zone — is marked a no-show through the certified {@see AssignmentService::markNoShow()}, which
 * frees the room for the nights left and writes the audit and domain events. The reason recorded is {@see CancellationReason::AUTO_NO_SHOW}.
 *
 * Runs hourly; the hour each property's line is crossed differs by its zone and its check-out time, and a booking is only ever marked once
 * (a second termination is refused — invariant #29).
 */
class MarkNoShows extends Command
{
    protected $signature = 'reservations:mark-no-shows';

    protected $description = 'Mark confirmed bookings whose guest never arrived as no-shows, for properties that switched it on (VY-26).';

    /** When the property has no check-out time, the line falls at noon the day after arrival. */
    private const DEFAULT_CUTOFF = '12:00';

    public function handle(TenantContext $tenant, AssignmentService $assignment): int
    {
        /** @var list<string> $propertyIds */
        $propertyIds = $tenant->runAsSuperAdmin(fn (): array => PropertyPolicy::query()->withoutGlobalScopes([OrganizationScope::class])
            ->where('auto_no_show', true)->pluck('property_id')->map(fn ($id): string => (string) $id)->all());

        $marked = 0;
        foreach ($propertyIds as $propertyId) {
            /** @var Property|null $property */
            $property = $tenant->runAsSuperAdmin(fn () => Property::query()->withoutGlobalScopes([OrganizationScope::class])->find($propertyId));
            $organization = $property !== null ? Organization::query()->find((string) $property->organization_id) : null;
            if ($property === null || $organization === null || in_array((string) $organization->status, ['suspended', 'cancelled'], true)) {
                continue;
            }

            $marked += $tenant->forOrganization((string) $property->organization_id, function () use ($property, $assignment): int {
                $now = CarbonImmutable::now($property->timezone ?: 'UTC');
                $cutoff = preg_match('/^\d{2}:\d{2}/', (string) $property->check_out_time) === 1 ? substr((string) $property->check_out_time, 0, 5) : self::DEFAULT_CUTOFF;

                // arrival day D is past its line once "now" (property time) >= D + 1 day at the cut-off: D <= the latest such day
                $latestArrival = $now->format('H:i') >= $cutoff ? $now->subDay() : $now->subDays(2);

                $count = 0;
                $due = Reservation::query()
                    ->where('property_id', $property->getKey())
                    ->where('status', 'confirmed')
                    ->whereDate('check_in', '<=', $latestArrival->toDateString())
                    // ⚠️ only a stay with nights still to come: marking one that is already over frees nothing and would only rewrite the
                    //    record of a stay the desk may simply never have checked in (a property switching this on must not relabel its past)
                    ->whereDate('check_out', '>', $now->toDateString())
                    ->get();
                foreach ($due as $reservation) {
                    try {
                        $assignment->markNoShow($reservation, CancellationReason::AUTO_NO_SHOW);
                        $count++;
                    } catch (ReservationAlreadyTerminalException|ReservationInProgressException) {
                        // a person got there first — nothing to do
                    }
                }

                return $count;
            });
        }

        $this->info('Marked as no-shows: '.$marked);

        return self::SUCCESS;
    }
}
