<?php

declare(strict_types=1);

namespace App\Support\Pricing;

use App\Models\RateCalendar;
use App\Models\RateProposal;
use App\Models\RateSeason;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * ⚠️⚠️ WHAT A SEASON IS ABOUT TO OVERWRITE — the DISCLOSURE half only.
 *
 * ⚠️⚠️ THIS CHANGES NO POLICY. A season still overwrites whatever it covers, `SeasonPrecedence` is untouched,
 * and nothing here refuses anything. Whether a season SHOULD be able to replace a negotiated rate is an OPEN
 * FOUNDER RULING. This class only makes the existing behaviour visible before it lands.
 *
 * ── ⚠️⚠️ WHY THIS CAN BE SAID HONESTLY, WHERE UNDO COULD NOT ─────────────────────────────────────────────
 * Applying a season writes nightly `rate_calendar` rows through `updateOrCreate`, and **`rate_calendar` has
 * no provenance column whatsoever** — only `rate_plan_id`, `date` and `price_minor`. Nothing records what a
 * row replaced (the audit `before` is literally null), which is exactly why undo was refused rather than
 * built.
 *
 * ⚠️ BUT A NEGOTIATED RATE HAS A SECOND RECORD. An APPROVED `rate_proposals` row carries `owner_id`,
 * `room_type_id`, `from_date`, `to_date`, the rate that was applied, and the weekend uplift that was applied
 * with it. That IS a tie to the nights it set — per room type, per night.
 *
 * ⚠️⚠️ AND THE TEST IS A VALUE MATCH, NOT AN INFERENCE. A night is reported only when it BOTH falls inside an
 * approved agreement's window AND still carries the exact price that agreement applied for that night. So:
 *   • it never guesses — the price either equals the agreed figure or it does not;
 *   • it stays SILENT once the rate has moved on, which is correct: a warning about an agreement the owner
 *     or the operator has already replaced is noise, not information.
 *
 * ── ⚠️⚠️ WHAT IT DOES NOT COVER — THE FALSE NEGATIVE, NAMED ──────────────────────────────────────────────
 * ⚠️⚠️ **AN OWNER'S SELF-SET RATE IS INVISIBLE HERE, AND NOTHING CAN MAKE IT VISIBLE.** An owner setting a
 * price on `/owner/rates`, My Units or Unit Detail goes `OwnerRateController` → `OwnerRateService::apply()` →
 * an ordinary `rate_calendar` row, with **no proposal and no record of any kind**. A season replacing that is
 * indistinguishable from a season replacing a base rate or a manual one.
 *
 * ⚠️ THIS IS WHY THE WORDING ON SCREEN SAYS "NEGOTIATED" AND NOT "OWNER". An operator who trusts a warning
 * also trusts its silence, so the disclosure must not imply it has checked something it cannot check.
 * Covering self-set rates would need the same provenance column the undo question needs — costed in the
 * report, not built here.
 *
 * ⚠️ THE FALSE POSITIVE is a coincidental price match: another actor setting exactly the agreed figure inside
 * the agreed window would be reported as the agreement. It over-warns rather than under-warns, which is the
 * safe direction, and it cannot be removed without provenance either.
 */
final class AgreedRateDisclosure
{
    /**
     * How many of `$dates` currently carry a rate an owner negotiated and had approved.
     *
     * @param  list<string>  $dates  the nights this season covers, as Y-m-d
     * @param  Collection<string, RateCalendar>  $existing  current nightly rows keyed by Y-m-d — ⚠️ the SAME
     *                                                      collection the preview already loaded, never a
     *                                                      second read, so the disclosure and the blast
     *                                                      radius cannot disagree about the current price
     * @return array{nights: int, owners: int, from: ?string, to: ?string}
     */
    public static function forSeason(RateSeason $season, array $dates, Collection $existing): array
    {
        $none = ['nights' => 0, 'owners' => 0, 'from' => null, 'to' => null];

        $plan = $season->ratePlan;

        // ⚠️ No `room_type_id === null` branch: the column is NOT NULL and the model types it non-nullable,
        //    so such a check would be dead code that only looks careful.
        if ($plan === null || $dates === []) {
            return $none;
        }

        // ⚠️ APPROVED ONLY. A pending proposal has applied nothing, a declined or withdrawn one never did,
        //    and a countered one is still a conversation — none of them is a rate anybody agreed.
        $agreements = RateProposal::query()
            ->where('room_type_id', (string) $plan->room_type_id)
            ->where('state', RateProposal::STATE_APPROVED)
            ->whereDate('from_date', '<=', $season->ends_on->toDateString())
            ->whereDate('to_date', '>=', $season->starts_on->toDateString())
            ->orderBy('decided_at')
            ->get();

        if ($agreements->isEmpty()) {
            return $none;
        }

        $nights = 0;
        $owners = [];
        $first = null;
        $last = null;

        foreach ($dates as $date) {
            $current = $existing->get($date);

            if ($current === null) {
                continue;   // no row yet — there is nothing agreed to overwrite
            }

            $agreement = self::governing($agreements, $date);

            if ($agreement === null) {
                continue;
            }

            if ((int) $current->price_minor !== self::agreedPriceOn($agreement, $date)) {
                continue;   // ⚠️ the rate has moved on since it was agreed — silence is the honest answer
            }

            $nights++;
            $owners[(string) $agreement->owner_id] = true;
            $first ??= $date;
            $last = $date;
        }

        return ['nights' => $nights, 'owners' => count($owners), 'from' => $first, 'to' => $last];
    }

    /**
     * The agreement that actually set this night: the LAST one decided among those covering it.
     *
     * ⚠️ Later approvals supersede earlier ones, because the later apply overwrote the earlier one's rows.
     * Reading the earliest instead would compare today's price against a figure nothing ever wrote.
     *
     * @param  Collection<int, RateProposal>  $agreements  ordered by `decided_at` ascending
     */
    private static function governing(Collection $agreements, string $date): ?RateProposal
    {
        $winner = null;

        foreach ($agreements as $agreement) {
            $from = CarbonImmutable::parse($agreement->from_date)->toDateString();
            $to = CarbonImmutable::parse($agreement->to_date)->toDateString();

            if ($date >= $from && $date <= $to) {
                $winner = $agreement;   // ordered ascending, so the last match is the most recently decided
            }
        }

        return $winner;
    }

    /**
     * The exact figure this agreement wrote onto this night.
     *
     * ⚠️⚠️ THE WEEKEND UPLIFT IS PART OF THE AGREED RATE AND MUST BE RECONSTRUCTED THE SAME WAY THE WRITE
     * COMPUTED IT. `OwnerRateService::applyTo()` prices Fri/Sat at `round(base × (1 + pct/100))` when an
     * uplift is present. Comparing every night against the base figure alone would find no match on Friday
     * or Saturday and report FEWER agreed nights than there are — a false negative on up to two nights in
     * seven, in a disclosure whose entire value is that its silence can be trusted.
     *
     * ⚠️ An accepted COUNTER is the owner's own figure, and it is what was applied — so it wins over the
     * operator's proposed figure whenever it is present on an approved row.
     */
    private static function agreedPriceOn(RateProposal $agreement, string $date): int
    {
        $base = (int) ($agreement->countered_rate_minor ?? $agreement->proposed_rate_minor);
        $pct = $agreement->proposed_weekend_pct !== null ? (float) $agreement->proposed_weekend_pct : 0.0;

        if ($pct <= 0.0) {
            return $base;
        }

        return in_array(CarbonImmutable::parse($date)->dayOfWeekIso, [5, 6], true)
            ? (int) round($base * (1 + $pct / 100))
            : $base;
    }
}
