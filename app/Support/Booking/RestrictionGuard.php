<?php

declare(strict_types=1);

namespace App\Support\Booking;

use App\Exceptions\Booking\RestrictionsViolationException;
use App\Exceptions\Booking\RestrictionViolationException;
use App\Models\Restriction;
use Carbon\CarbonImmutable;

/**
 * ⚠️⚠️ THE FOUR RESTRICTIONS ARE FOUR DIFFERENT SHAPES, AND TWO OF THEM ARE MIRRORS.
 *
 * ⚠️ `min_stay`            — judged on the ARRIVAL date's row. A stay shorter than it is refused.
 * ⚠️ `max_stay`            — the MIRROR: also judged on the ARRIVAL date's row, and a stay LONGER than it
 *                            is refused. ⚠️ Verified rather than assumed symmetric.
 * ⚠️ `stop_sell`           — applies to EVERY night of the stay, not just arrival.
 * ⚠️ `closed_to_arrival`   — forbids STARTING on that date. ⚠️ A stay that merely PASSES THROUGH it is fine.
 * ⚠️ `closed_to_departure` — forbids ENDING on that date. ⚠️ Passing through is likewise fine.
 *
 * ⚠️⚠️ CTA AND CTD ARE THE EASIEST THINGS HERE TO BUILD BACKWARDS, and an inversion refuses good bookings
 * while still admitting the bad ones — which looks exactly like the feature working. So each is proven BOTH
 * ways in the tests: the forbidden case refused AND the pass-through case permitted. A refusal-only test
 * cannot tell a correct implementation from an inverted one.
 *
 * ⚠️ THE LOOKUP IS THE SAME ONE THE BADGE RENDERS (§N.8f): `room_type_id` + `rate_plan_id IS NULL` + `date`.
 * Every shipped writer (`RateActionController`, `OwnerRateService`) keys restrictions that way, and the rate
 * grid reads them that way. If the engine looked anywhere else the bug would simply move — the badge would
 * say one thing and the refusal another.
 *
 * ⚠️⚠️ ABSENT MEANS UNRESTRICTED, NEVER A DEFAULT. Where no row exists, or a column is null, nothing is
 * enforced — so a booking that succeeds today still succeeds. A null `min_stay` silently read as anything
 * other than "no minimum" would close inventory nobody chose to close.
 */
final class RestrictionGuard
{
    /**
     * ⚠️ Refuses the FIRST violation found, so the message names one concrete thing to fix.
     *
     * @param  string  $checkIn  the arrival date (the first night)
     * @param  string  $checkOut  the departure date (exclusive — the stay's nights are [checkIn, checkOut))
     */
    public static function assertBookable(string $roomTypeId, string $checkIn, string $checkOut, ?callable $override = null): void
    {
        $arrival = CarbonImmutable::parse($checkIn)->startOfDay();
        $departure = CarbonImmutable::parse($checkOut)->startOfDay();

        if ($departure->lessThanOrEqualTo($arrival)) {
            return;   // not a stay; the caller's own date guards handle it
        }

        $rows = Restriction::query()
            ->where('room_type_id', $roomTypeId)
            ->whereNull('rate_plan_id')
            ->whereBetween('date', [$arrival->toDateString(), $departure->toDateString()])
            ->get()
            ->keyBy(fn (Restriction $r): string => CarbonImmutable::parse($r->date)->toDateString());

        if ($rows->isEmpty()) {
            return;   // ⚠️ nothing set → nothing enforced
        }

        $nights = (int) $arrival->diffInDays($departure);

        // ⚠️⚠️ EVERY BREACH IS COLLECTED — NEVER FIRST-FAILURE-WINS. A booking can breach several
        //    restrictions at once, and stopping at the first one is a BLANK CHEQUE: the operator is shown a
        //    minimum stay, overrides it, and a stop-sell they never saw is waived silently. The refusal
        //    lists them all, and the audit records every one.
        $breaches = [];

        // ⚠️ min_stay — the ARRIVAL date's row governs the whole stay's length.
        $arrivalRow = $rows->get($arrival->toDateString());
        if ($arrivalRow !== null && $arrivalRow->min_stay !== null && $nights < (int) $arrivalRow->min_stay) {
            $breaches[] = RestrictionViolationException::minStay($arrival->toDateString(), (int) $arrivalRow->min_stay, $nights);
        }

        // ⚠️ max_stay — min_stay's mirror, also on the ARRIVAL date's row.
        //    ⚠️ THE BOUNDARY: a maximum of 7 permits EXACTLY 7 and refuses 8, so the comparison is `>`,
        //    not `>=`. Off-by-one here looks identical to working correctly until someone hits it.
        if ($arrivalRow !== null && $arrivalRow->max_stay !== null && $nights > (int) $arrivalRow->max_stay) {
            $breaches[] = RestrictionViolationException::maxStay($arrival->toDateString(), (int) $arrivalRow->max_stay, $nights);
        }

        // ⚠️ closed_to_arrival — ONLY the arrival date. A stay passing through is untouched.
        if ($arrivalRow !== null && (bool) $arrivalRow->closed_to_arrival) {
            $breaches[] = RestrictionViolationException::closedToArrival($arrival->toDateString());
        }

        // ⚠️ closed_to_departure — ONLY the departure date. Passing through is untouched.
        $departureRow = $rows->get($departure->toDateString());
        if ($departureRow !== null && (bool) $departureRow->closed_to_departure) {
            $breaches[] = RestrictionViolationException::closedToDeparture($departure->toDateString());
        }

        // ⚠️ stop_sell — EVERY night of the stay, i.e. [arrival, departure). The departure date is not a
        //    night anyone sleeps, so it is deliberately excluded.
        for ($d = $arrival; $d->lt($departure); $d = $d->addDay()) {
            $row = $rows->get($d->toDateString());
            if ($row !== null && (bool) $row->stop_sell) {
                $breaches[] = RestrictionViolationException::stopSell($d->toDateString());
            }
        }

        if ($breaches === []) {
            return;
        }

        // ⚠️ THE OVERRIDE IS INFORMED OR IT IS NOTHING. It waives exactly the breaches listed here, all of
        //    them, and the caller records every one — never "override used".
        if ($override !== null) {
            $override(array_map(
                static fn (RestrictionViolationException $e): array => [
                    'restriction' => $e->restriction,
                    'date' => $e->date,
                    'value' => $e->value,
                ],
                $breaches,
            ));

            return;
        }

        throw RestrictionsViolationException::fromBreaches($breaches);
    }
}
