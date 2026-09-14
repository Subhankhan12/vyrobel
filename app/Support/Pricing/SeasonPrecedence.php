<?php

declare(strict_types=1);

namespace App\Support\Pricing;

use App\Models\RateSeason;
use Illuminate\Support\Collection;

/**
 * THE ONE precedence rule for overlapping rate seasons (2026-09-01).
 *
 * ⚠️⚠️ WHY IT IS ITS OWN CLASS. Before this, precedence was answered TWICE and differently: the owner's PEAK
 * column took `orderBy('starts_on')->first()` (earliest start wins) while the actual PRICE came from whichever
 * season had been applied LAST. So the column could name one season while the guest paid another's price —
 * two expressions for one concept, which is exactly how surfaces drift (§N.8f), here with money attached.
 * Display, the preview and the write path now all call this and cannot disagree.
 *
 * ══ THE RULE ═════════════════════════════════════════════════════════════════════════════════════════
 *
 *   **When seasons overlap, the SHORTER season sets the price for the nights they share; if two are the
 *   same length, the one created most recently wins.**
 *
 * ⚠️ WHY NARROWEST, argued against the alternatives rather than assumed:
 *   • NARROWEST — the specific overrides the general, which is how people describe the actual case: a broad
 *     summer rate with a higher August peak inside it. ⚠️ It is also CONSISTENT WITH A PRECEDENCE THIS
 *     PRODUCT ALREADY HAS — a per-unit override already beats a room-type rate in {@see NightlyRate}. One
 *     mental model, not two.
 *   • MOST-RECENTLY-APPLIED — what the money did before this class existed, and the worst of the four: it
 *     depends on the order somebody clicked, which nothing displays and nobody remembers. Two operators with
 *     identical seasons could end up charging different prices.
 *   • HIGHEST PRICE — would make a discount inside a broad season impossible to express (a January sale
 *     inside a winter rate could never win), so it forbids a legitimate use rather than resolving a tie.
 *   • MOST-RECENTLY-CREATED — `created_at` is not shown anywhere and does not express intent about DATES;
 *     it is arbitrary as a primary rule. It is fine as a LAST resort, which is what it is used as below.
 *
 * ⚠️ THE TIEBREAK IS NOT OPTIONAL — a rule with an undefined case is not a rule. Equal length falls through
 * to `created_at`, and equal `created_at` falls through to the id, so the ordering is TOTAL: any two seasons
 * have a definite winner, and the answer never depends on the order rows came back from the database.
 */
final class SeasonPrecedence
{
    /**
     * The season that governs `$date`, or null when none covers it.
     *
     * @param  Collection<int, RateSeason>|iterable<RateSeason>  $seasons
     */
    public static function winnerOn(iterable $seasons, string $date): ?RateSeason
    {
        $covering = [];
        foreach ($seasons as $season) {
            if ($season->covers($date)) {
                $covering[] = $season;
            }
        }

        return self::strongest($covering);
    }

    /**
     * The highest-precedence season of a set, or null when the set is empty.
     *
     * @param  iterable<RateSeason>  $seasons
     */
    public static function strongest(iterable $seasons): ?RateSeason
    {
        $best = null;
        foreach ($seasons as $season) {
            if ($best === null || self::compare($season, $best) < 0) {
                $best = $season;
            }
        }

        return $best;
    }

    /**
     * Sort WEAKEST first — the order the write path must apply them in, so the strongest is written LAST and
     * therefore stands. ⚠️ This is the whole reason the rule can govern money at all: the calendar is
     * materialised, so precedence has to be expressed as WRITE ORDER, not as a read-time choice.
     *
     * @param  iterable<RateSeason>  $seasons
     * @return list<RateSeason>
     */
    public static function weakestFirst(iterable $seasons): array
    {
        $out = is_array($seasons) ? array_values($seasons) : iterator_to_array($seasons, false);

        // ⚠️ `usort` is not stable across PHP versions for equal elements — which is why compare() falls all
        // the way through to the id and never returns 0 for two different rows.
        usort($out, fn (RateSeason $a, RateSeason $b): int => -self::compare($a, $b));

        return $out;
    }

    /**
     * Negative when `$a` beats `$b`. The total order: fewer nights, then newer, then id.
     */
    public static function compare(RateSeason $a, RateSeason $b): int
    {
        $byLength = self::nights($a) <=> self::nights($b);
        if ($byLength !== 0) {
            return $byLength;   // fewer nights wins
        }

        $byAge = ($b->created_at?->getTimestamp() ?? 0) <=> ($a->created_at?->getTimestamp() ?? 0);
        if ($byAge !== 0) {
            return $byAge;      // more recently created wins
        }

        // ⚠️ THE LAST RESORT EXISTS SO THE ORDER IS TOTAL. Two seasons created in the same second would
        // otherwise tie, and the "winner" would be whichever the database happened to return first.
        return strcmp((string) $b->getKey(), (string) $a->getKey());
    }

    /** A season's length in nights, inclusive of both bounds — the same reading the form gives it. */
    public static function nights(RateSeason $season): int
    {
        // ⚠️ CAST DELIBERATELY — Carbon 3's `diffInDays()` returns a FLOAT, so the declared `int` return
        // fatally rejected it. Whole days by construction (both ends are startOfDay), so the cast loses
        // nothing; without it every call in this class threw.
        return (int) $season->starts_on->startOfDay()->diffInDays($season->ends_on->startOfDay()) + 1;
    }

    /** The one sentence an operator reads. Kept here so the copy cannot drift from the rule. */
    public const SENTENCE = 'When seasons overlap, the shorter season sets the price for the nights they share; if two are the same length, the one created most recently wins.';
}
