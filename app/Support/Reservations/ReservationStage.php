<?php

declare(strict_types=1);

namespace App\Support\Reservations;

use App\Livewire\FrontDesk;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Reservation;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerOccupancy;
use App\Support\Payouts\RailLabel;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * THE ONE definition of a reservation's LIFECYCLE STAGE — In-house / Upcoming / Past — shared by the
 * operator Reservations list, the Guests CRM list and the Guest profile (Wave 2). Same discipline as
 * {@see MoneyFormatter} / {@see RailLabel} /
 * {@see OwnerOccupancy}: three screens showing the same guest must never disagree about
 * whether that guest is in-house.
 *
 * A STAGE IS NOT A STATUS. `reservations.status` is the real 7-value enum (enquiry, tentative, confirmed,
 * checked_in, checked_out, cancelled, no_show) and is rendered by
 * {@see FormatsOperatorData::reservationStatusMeta()}. A stage is a DERIVED
 * bucket over that enum PLUS the dates. Both are real; neither invents a value. (The Wave 2 design showed
 * "Completed" and "In house" as if they were statuses — they are not in the enum. "Completed" is
 * `checked_out`; "In house" is the *stage* of a `checked_in` reservation.)
 *
 * THE RULES (locked 2026-07-16; see docs/DECISIONS.md):
 *   • IN_HOUSE  — `checked_in`. The enum already means "not yet checked out" (checked_out is its own value).
 *   • UPCOMING  — `confirmed` AND check_in is in the FUTURE.
 *   • PAST      — `checked_out`, OR a `confirmed` stay whose check_out has passed (never checked in — a
 *                 stale booking; still genuinely past).
 *   • DUE       — `confirmed`, check_in has arrived but check_out has not, and nobody checked them in. The
 *                 three headline buckets do NOT cover this, and folding it into any of them would lie:
 *                 it is neither upcoming (the date came) nor in-house (they are not) nor past (the stay
 *                 is live). It is an arrival due/overdue.
 *   • TENTATIVE — `enquiry` / `tentative`. Not yet a confirmed stay.
 *   • CANCELLED — `cancelled` / `no_show`. Terminal, and deliberately NOT folded into PAST: the design
 *                 lumped them together, but a cancelled FUTURE stay is not "past" — it never happened.
 *
 * Every (status × date) combination therefore lands in exactly one stage — {@see stagesAgreeTest} proves
 * `for()` and `scope()` classify identically, so a filter can never disagree with the badge it filters on.
 *
 * ⚠️ "Today" is `CarbonImmutable::today()` — SERVER time, matching the shipped precedent
 * ({@see FrontDesk} line ~81). CLAUDE.md's convention is that guest-facing day logic belongs
 * in the PROPERTY's timezone (`properties.timezone` exists, default UTC), and neither this nor the front
 * desk honours it yet. Deliberately consistent with what ships rather than silently divergent; recorded as
 * an outstanding item. When it is fixed, fix it HERE and in FrontDesk together.
 */
final class ReservationStage
{
    public const IN_HOUSE = 'in_house';

    public const UPCOMING = 'upcoming';

    public const PAST = 'past';

    public const DUE = 'due';

    public const TENTATIVE = 'tentative';

    public const CANCELLED = 'cancelled';

    /** The stages a surface may filter by, in the order the operator thinks about them. */
    public const FILTERABLE = [self::UPCOMING, self::DUE, self::IN_HOUSE, self::PAST, self::TENTATIVE, self::CANCELLED];

    /**
     * The real `status` enum values behind the CANCELLED stage — named ONCE so a caller that needs the
     * inverse in SQL ("stays that actually happened") cannot drift from {@see for()} and {@see scope()} by
     * re-typing the pair. The Guests CRM's stay counts and its repeat-guest tile both key on this.
     *
     * @var list<string>
     */
    public const CANCELLED_STATUSES = ['cancelled', 'no_show'];

    /**
     * Guest-level precedence: which ONE stage speaks for a guest who has several reservations.
     *
     * Ordered by what an operator needs to know first — someone standing in the lobby outranks an arrival
     * due, which outranks a future booking, which outranks history. CANCELLED is LAST: a guest whose only
     * other stay is `past` is a past guest, not a cancelled one.
     *
     * @var list<string>
     */
    public const PRECEDENCE = [self::IN_HOUSE, self::DUE, self::UPCOMING, self::TENTATIVE, self::PAST, self::CANCELLED];

    /**
     * The stage of ONE reservation. Reads the real enum + the real dates; invents nothing.
     */
    public static function for(Reservation $reservation, ?CarbonInterface $today = null): string
    {
        $today = self::today($today);
        $status = (string) $reservation->status;

        // Terminal first — a cancelled future stay is NOT "past", it never happened.
        if (in_array($status, self::CANCELLED_STATUSES, true)) {
            return self::CANCELLED;
        }

        if ($status === 'checked_in') {
            return self::IN_HOUSE;
        }

        if ($status === 'checked_out') {
            return self::PAST;
        }

        if (in_array($status, ['enquiry', 'tentative'], true)) {
            return self::TENTATIVE;
        }

        // `confirmed` — decided by the dates.
        $checkIn = Carbon::parse($reservation->check_in)->startOfDay();
        $checkOut = Carbon::parse($reservation->check_out)->startOfDay();

        if ($checkIn->greaterThan($today)) {
            return self::UPCOMING;
        }

        // Half-open [check_in, check_out): the stay is over once check_out arrives.
        if ($checkOut->lessThanOrEqualTo($today)) {
            return self::PAST;
        }

        return self::DUE;
    }

    /**
     * Constrain a query to ONE stage — the SQL mirror of {@see for()}.
     *
     * This exists so a filter chip cannot drift from the badge it filters on: both come from this class,
     * and `ReservationStageTest` asserts they classify every row identically. Filtering in PHP instead would
     * break pagination; re-writing these predicates at the call site is exactly the drift this prevents.
     *
     * @param  Builder<Reservation>  $query
     * @return Builder<Reservation>
     */
    public static function scope(Builder $query, string $stage, ?CarbonInterface $today = null): Builder
    {
        $today = self::today($today);
        $t = $today->toDateString();

        return match ($stage) {
            self::CANCELLED => $query->whereIn('status', self::CANCELLED_STATUSES),
            self::IN_HOUSE => $query->where('status', 'checked_in'),
            self::TENTATIVE => $query->whereIn('status', ['enquiry', 'tentative']),
            self::UPCOMING => $query->where('status', 'confirmed')->whereDate('check_in', '>', $t),
            self::DUE => $query->where('status', 'confirmed')
                ->whereDate('check_in', '<=', $t)
                ->whereDate('check_out', '>', $t),
            // checked_out, OR a confirmed stay whose window has closed.
            self::PAST => $query->where(fn (Builder $q) => $q
                ->where('status', 'checked_out')
                ->orWhere(fn (Builder $q2) => $q2
                    ->where('status', 'confirmed')
                    ->whereDate('check_out', '<=', $t))),
            default => $query,
        };
    }

    /**
     * The ONE stage that speaks for a GUEST who has several reservations — the Guests CRM's status column
     * and the guest profile's header both read it, so the two cannot disagree about the same person.
     *
     * It lives here, in the class that owns the stage vocabulary, rather than in the Guests list: a guest's
     * status is not a different question from a reservation's status, it is the same question asked of a
     * set. Every stage still comes from {@see for()} — this method only PICKS between them, per
     * {@see PRECEDENCE}; it re-derives nothing and knows no dates of its own.
     *
     * Returns NULL for a guest with no reservations at all — a real state (a profile that has never booked),
     * which the caller renders as "—" rather than inventing a stage for someone who has no stay.
     *
     * @param  iterable<Reservation>  $reservations
     */
    public static function mostSignificant(iterable $reservations, ?CarbonInterface $today = null): ?string
    {
        $today = self::today($today);
        $found = [];

        foreach ($reservations as $reservation) {
            $found[self::for($reservation, $today)] = true;
        }

        foreach (self::PRECEDENCE as $stage) {
            if (isset($found[$stage])) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Stage → badge label + tone. Warm tones only, keyed to the operator design system. The label is translated here, at
     * call time, by the stage CODE (I18N-01) — compare the stage, never this label.
     *
     * @return array{label: string, tone: string}
     */
    public static function meta(string $stage): array
    {
        return match ($stage) {
            self::IN_HOUSE => ['label' => __('php/support/reservations/reservation_stage.in_house'), 'tone' => 'in-house'],
            self::UPCOMING => ['label' => __('php/support/reservations/reservation_stage.upcoming'), 'tone' => 'confirmed'],
            self::PAST => ['label' => __('php/support/reservations/reservation_stage.past'), 'tone' => 'departed'],
            self::DUE => ['label' => __('php/support/reservations/reservation_stage.arrival_due'), 'tone' => 'pending'],
            self::TENTATIVE => ['label' => __('php/support/reservations/reservation_stage.tentative'), 'tone' => 'pending'],
            self::CANCELLED => ['label' => __('php/support/reservations/reservation_stage.cancelled'), 'tone' => 'cancelled'],
            default => ['label' => __('php/support/reservations/reservation_stage.unknown'), 'tone' => 'pending'],
        };
    }

    private static function today(?CarbonInterface $today): CarbonInterface
    {
        return ($today ?? CarbonImmutable::today())->startOfDay();
    }
}
