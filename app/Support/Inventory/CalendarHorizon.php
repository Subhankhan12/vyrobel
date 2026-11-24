<?php

declare(strict_types=1);

namespace App\Support\Inventory;

use App\Models\Availability;
use App\Models\Organization;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * VY-07 (audit of 2026-11-24) — HOW FAR AHEAD THE BOOKABLE CALENDAR IS PUBLISHED, AND THE ONE THING THAT KEEPS IT THERE.
 *
 * ⚠️⚠️ WHAT WAS WRONG. Nights were published for a year when a unit or a room type was created or switched — and by nothing else. No
 * scheduled task added a day, so the window shrank by one night every night and closed for good a year after the last change to the
 * inventory: a hotel that set itself up once and then simply ran would, twelve months later, have no night left to sell, with no error
 * anywhere. (The local demo data had 95 and 35 days left when this was found.)
 *
 * ⚠️ {@see extend()} is that missing task's work (`availability:extend`, daily): every room type of every organisation is published
 * through today + {@see DAYS}. It only ever ADDS nights after the last published one, through the certified
 * {@see AvailabilityService::recompute()} — the same call that published the first year — so `sold` is never touched and a night that
 * already exists is never rewritten here.
 *
 * ⚠️ {@see shortfalls()} is the ALARM's reading, deliberately a SEPARATE query from the job: a job cannot report that it did not run.
 * The platform console's health board shows it, and the command itself fails (non-zero exit, an error line) if anything is still short
 * after it ran.
 *
 * ⚠️ PRICES ARE NOT PUBLISHED HERE. A night is bookable when it has availability AND a price; the price is the operator's (a rate, or a
 * season they applied — R-SEASON), and this never invents one. It keeps the half that nobody could keep by hand.
 */
final class CalendarHorizon
{
    /** Nights published ahead of today — the same year the first publication uses when a unit or room type is created. */
    public const DAYS = 365;

    /** The job runs once a day, so a window one night short is normal between midnight and the run. Shorter than that is a finding. */
    public const TOLERANCE_DAYS = 1;

    /** Short by more than this many days means the job has not run for weeks — red on the health board rather than amber. */
    public const RED_AFTER_DAYS = 14;

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Publish every room type through today + {@see DAYS}. Idempotent: a second run the same day adds nothing.
     *
     * @return array{room_types: int, extended: int, nights: int}
     */
    public function extend(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse($today ?? CarbonImmutable::today())->startOfDay();
        $end = $today->addDays(self::DAYS);              // exclusive: the last night published is $end − 1 day
        $lastWanted = $end->subDay()->toDateString();

        $result = ['room_types' => 0, 'extended' => 0, 'nights' => 0];

        // ⚠️ The scheduler has no tenant. `Organization` carries no tenant scope (only soft deletes — an archived organisation is skipped),
        //    and each organisation's room types are then read and written INSIDE that organisation's own context, like every other
        //    scheduled job that crosses tenants.
        $organizationIds = $this->tenant->runAsSuperAdmin(
            fn (): array => Organization::query()->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all(),
        );

        foreach ($organizationIds as $organizationId) {
            $this->tenant->forOrganization($organizationId, function () use ($today, $end, $lastWanted, &$result): void {
                $roomTypes = RoomType::query()
                    ->whereHas('property')      // an ARCHIVED property (soft-deleted) sells nothing; its room types are left alone
                    ->orderBy('id')
                    ->get(['id']);

                foreach ($roomTypes as $roomType) {
                    $result['room_types']++;

                    $last = Availability::query()->where('room_type_id', $roomType->getKey())->max('date');
                    $lastNight = $last === null ? null : CarbonImmutable::parse((string) $last)->startOfDay();

                    if ($lastNight !== null && $lastNight->toDateString() >= $lastWanted) {
                        continue;   // already published far enough
                    }

                    // From the night after the last published one — never before today, and from today when nothing is published at all.
                    $from = $lastNight !== null && $lastNight->gte($today) ? $lastNight->addDay() : $today;

                    $this->availability->recompute((string) $roomType->getKey(), $from, $end);

                    $result['extended']++;
                    $result['nights'] += (int) $from->diffInDays($end);
                }
            });
        }

        return $result;
    }

    /**
     * Every live room type whose calendar ends more than {@see TOLERANCE_DAYS} short of the target — the alarm's reading.
     *
     * ⚠️ ONE QUERY ACROSS EVERY ORGANISATION, on the query builder: it reads counts and dates for the platform's own health, the same
     *    way the platform statistics do. It returns ids and dates only — no name, no guest, no price.
     *
     * @return list<array{organization_id: string, room_type_id: string, last_night: ?string, days_ahead: int, short_by: int}>
     */
    public function shortfalls(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse($today ?? CarbonImmutable::today())->startOfDay();
        $short = [];

        foreach ($this->windows() as $row) {
            $daysAhead = $row['last_night'] === null
                ? 0
                : max(0, (int) $today->diffInDays(CarbonImmutable::parse($row['last_night'])->startOfDay(), false) + 1);
            $shortBy = self::DAYS - $daysAhead;

            if ($shortBy > self::TOLERANCE_DAYS) {
                $short[] = $row + ['days_ahead' => $daysAhead, 'short_by' => $shortBy];
            }
        }

        usort($short, fn (array $a, array $b): int => $b['short_by'] <=> $a['short_by']);

        return $short;
    }

    /** How many live room types there are to keep published (the health card says it, so "none short" is not read off an empty set). */
    public function roomTypeCount(): int
    {
        return count($this->windows());
    }

    /**
     * The last published night of every live room type (of a live property, of a live organisation).
     *
     * @return list<array{organization_id: string, room_type_id: string, last_night: ?string}>
     */
    private function windows(): array
    {
        return DB::table('room_types as rt')
            ->join('properties as p', 'p.id', '=', 'rt.property_id')
            ->join('organizations as o', 'o.id', '=', 'rt.organization_id')
            ->leftJoin('availability as a', 'a.room_type_id', '=', 'rt.id')
            ->whereNull('rt.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereNull('o.deleted_at')
            ->groupBy('rt.id', 'rt.organization_id')
            ->orderBy('rt.id')
            ->get(['rt.id as room_type_id', 'rt.organization_id', DB::raw('max(a.date) as last_night')])
            ->map(fn (object $r): array => [
                'organization_id' => (string) $r->organization_id,
                'room_type_id' => (string) $r->room_type_id,
                'last_night' => $r->last_night === null ? null : CarbonImmutable::parse((string) $r->last_night)->toDateString(),
            ])
            ->all();
    }
}
