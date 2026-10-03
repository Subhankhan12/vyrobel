<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Controllers\Operator\OwnerManageController;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Services\AgreementAmendmentService;
use App\Services\OwnerAttributionService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️⚠️ THE ONE RULE FOR CHANGING AN OWNER'S COMMISSION FROM THEIR PAGE (2026-10-03). The page reads it to say what a save will
 * do; the write ({@see OwnerManageController::update()}) runs it. A second copy is how the old write and its dialog came to
 * disagree.
 *
 * A change is an owner-wide agreement of its own. It starts on the FIRST OF NEXT MONTH — or TODAY, for an owner with no
 * agreement in effect today (there is nothing mid-period to protect, and waiting would leave a gap). Every active owner-wide
 * agreement covering that day is bounded the day before (and ENDED if it would only have started that day); nothing that has
 * already applied is rewritten. The new agreement carries every term in {@see AgreementAmendmentService::TERM_KEYS}, keeps the
 * end date the agreement it replaces had, and ends the day before any change already planned for later.
 *
 * Defects this replaced, each 🔬 measured in OwnerPageFixesTest before the fix:
 *   • ⚠️ the decision was made against TODAY'S agreement, not the one covering the start day — so a second change in one month
 *     stacked a second open-ended agreement on the same day (the payout maths takes the first row the database returns), and
 *     setting the rate back to today's did nothing while the waiting change still came into force;
 *   • ⚠️ the new agreement copied only the payout schedule and currency — a management fee, the term and any attribution rules
 *     silently ended with the old one;
 *   • ⚠️ a change already planned for later (an amendment the owner agreed) was overlapped by an open-ended new one;
 *   • (the independent review) only the newest covering agreement was ended, so rows an older write had stacked stayed active
 *     beside the new one; an owner with no agreement got one from the 1st only, and stays before it were worked out with none;
 *     a fixed end date was dropped; two saves at once could race.
 *
 * ⚠️ HOW IT REACHES MONEY: an owner's earnings for a booking are worked out once, from the agreement covering the stay's date
 * ({@see OwnerAttributionService}), when the booking is made — or later for one imported without them, and again
 * when a booking moves to another owner's unit. A change therefore applies to bookings worked out after it is saved; a booking
 * already worked out keeps its rate.
 */
final class CommissionChange
{
    /** The first of next month — when a change starts for an owner with an agreement in effect. */
    public static function firstOfNextMonth(?CarbonInterface $today = null): CarbonImmutable
    {
        return self::day($today)->startOfMonth()->addMonth();
    }

    /** The day a change saved now starts for this owner: the first of next month, or today if no agreement is in effect. */
    public static function startsOn(string $ownerId, ?CarbonInterface $today = null): CarbonImmutable
    {
        $today = self::day($today);

        return self::governing($ownerId, $today) === null ? $today : self::firstOfNextMonth($today);
    }

    /**
     * The owner-wide agreement that governs a day. ⚠️ Ordered so the answer is one row, not the database's choice: the latest
     * to start, then the latest made.
     */
    public static function governing(string $ownerId, CarbonInterface|string $date): ?ManagementAgreement
    {
        return self::covering($ownerId, $date)->first();
    }

    /** The next owner-wide agreement planned to start after a day, if any. */
    public static function plannedAfter(string $ownerId, CarbonInterface $date): ?ManagementAgreement
    {
        return ManagementAgreement::query()
            ->where('owner_id', $ownerId)
            ->whereNull('unit_id')
            ->where('status', 'active')
            ->whereDate('effective_from', '>', $date->toDateString())
            ->orderBy('effective_from')
            ->orderByDesc('created_at')
            ->first();
    }

    /** The rate a save would start from (the agreement governing the start day), as the page shows it — "18", never "18.00". */
    public static function rateOn(string $ownerId, CarbonInterface $date): ?string
    {
        return OwnerCommissionRate::trim(self::governing($ownerId, $date)?->commission_pct);
    }

    /**
     * Set the owner-wide commission from {@see startsOn()}. Returns false — and writes nothing — when exactly one agreement covers
     * that day and its rate is already this one.
     */
    public static function apply(Owner $owner, float $pct, ?CarbonInterface $today = null): bool
    {
        $ownerId = (string) $owner->getKey();
        $today = self::day($today);
        $pct = round($pct, 2);

        return DB::transaction(function () use ($owner, $ownerId, $today, $pct): bool {
            // ⚠️ One save at a time per owner: two at once (two tabs, two people) would each read the same agreements and both
            //    write — the stacked rows this rule exists to prevent. Every read below happens under this lock.
            Owner::query()->whereKey($ownerId)->lockForUpdate()->first();

            $startsOn = self::startsOn($ownerId, $today);
            $covering = self::covering($ownerId, $startsOn);
            $governing = $covering->first();

            if ($covering->count() === 1 && $governing->commission_pct !== null && round((float) $governing->commission_pct, 2) === $pct) {
                return false;
            }

            $planned = self::plannedAfter($ownerId, $startsOn);
            $base = $governing ?? self::governing($ownerId, $today);

            // The new agreement ends where the one it replaces ended (a fixed term is kept), or the day before a change planned
            // for later — whichever comes first; otherwise it is open-ended.
            $ends = array_filter([
                $governing?->effective_to !== null ? CarbonImmutable::parse($governing->effective_to->toDateString()) : null,
                $planned !== null ? CarbonImmutable::parse($planned->effective_from->toDateString())->subDay() : null,
            ]);
            $endsOn = $ends === [] ? null : min($ends);

            foreach ($covering as $agreement) {
                // ⚠️ One that would only have started on the change day never applies: it is ENDED as well as bounded, so it
                //    reads as replaced in the agreement history instead of as an active agreement with backwards dates.
                $neverStarted = CarbonImmutable::parse($agreement->effective_from->toDateString())->greaterThanOrEqualTo($startsOn);
                $agreement->forceFill(['effective_to' => $startsOn->subDay()->toDateString()]
                    + ($neverStarted ? ['status' => 'ended'] : []))->save();
            }

            $terms = $base instanceof ManagementAgreement ? $base->only(AgreementAmendmentService::TERM_KEYS) : [];

            ManagementAgreement::create(array_merge($terms, [
                'owner_id' => $owner->getKey(),
                'unit_id' => null,
                'commission_pct' => $pct,
                'payout_schedule' => $terms['payout_schedule'] ?? 'monthly',
                'payout_currency' => $terms['payout_currency'] ?? $owner->payout_currency,
                'effective_from' => $startsOn->toDateString(),
                'effective_to' => $endsOn?->toDateString(),
                'status' => 'active',
            ]));

            return true;
        });
    }

    /**
     * Every active owner-wide agreement covering a day, newest first (the latest to start, then the latest made).
     *
     * @return Collection<int, ManagementAgreement>
     */
    private static function covering(string $ownerId, CarbonInterface|string $date): Collection
    {
        return ManagementAgreement::query()
            ->where('owner_id', $ownerId)
            ->whereNull('unit_id')
            ->effectiveOn($date)
            ->orderByDesc('effective_from')
            ->orderByDesc('created_at')
            ->get();
    }

    private static function day(?CarbonInterface $today): CarbonImmutable
    {
        return CarbonImmutable::parse(($today ?? CarbonImmutable::today())->toDateString());
    }
}
