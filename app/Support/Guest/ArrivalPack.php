<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Folio;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Models\Unit;
use App\Support\Folio\FolioTotals;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\ReservationStage;
use App\Support\Schedule\BoardDate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The arrival pack for ONE booking (Phase 4b · Sub-gate 4) — directions, check-in/out times, and the sensitive
 * access secrets (door code + wifi) that live on the assigned physical {@see Unit}.
 *
 * 🔒 The CALLER supplies an OWNED booking (resolved through the authed guest's own relations — cross-guest is a 404 before we get
 * here). Whether the door code is then returned is decided HERE, in this order — the first rule that is not met is the one the guest
 * is told about ({@see WITHHELD_REASONS}):
 *
 *   1. CANCELLED — a cancelled or no-show booking never gets the code.
 *   2. WINDOW    — withheld until the stay's arrival window opens ({@see leadDays()} before check-in, through check-out). A door
 *                  code exposed months early is a physical-security leak. Before the window we return null + `released_from` so the
 *                  screen can say WHEN, never fabricate a code.
 *   3. BALANCE   — withheld while the guest still owes money for the stay ({@see FolioTotals}, the one formula).
 *   4. CHECK-IN  — withheld until the guest has completed online check-in, only if the property asks for it.
 *
 * ⚠️⚠️ VY-06 (audit of 2026-11-24, reproduced). Rules 3 and 4 did not exist and rule 2 was one constant for every property: a stay
 * with 200.00 owing and no online check-in showed its door code two days before arrival, and the pre-arrival email carried it. The
 * three rules are now EACH PROPERTY'S (`property_policies.arrival_*`, Settings → Policies); a property that has set nothing — or has
 * no policy row — gets 2 days, balance required, check-in not required. "Balance required" as the default is the fix: a house that
 * takes payment at its front desk switches it off.
 *
 * ⚠️ ONE READER, FOUR SURFACES: the Arrival page, the reservation page, the printable guide and the pre-arrival email all print this
 * pack — the code, or `withheld_message`, the one sentence that says what is missing. None of them re-reads the unit's secrets.
 *
 * Directions come from the property's REAL address via {@see directions()} — the SAME source the Phase-3
 * pre-arrival email reads, so the two surfaces can't drift.
 */
final class ArrivalPack
{
    /** Days before check-in that the access secrets are released when the property has set no number of its own (kept small — the code is physically sensitive). */
    public const LEAD_DAYS = 2;

    /** The most a property may set: a door code a month ahead is already generous. */
    public const MAX_LEAD_DAYS = 30;

    /** Why the secrets are being withheld — NULL in the pack means they are released. */
    public const WITHHELD_REASONS = ['cancelled', 'window', 'balance', 'checkin'];

    /**
     * The property's own lead time in days — {@see LEAD_DAYS} when it has set none (or has no policy row at all).
     */
    public static function leadDays(?Property $property): int
    {
        $days = self::policy($property)?->arrival_lead_days;

        return $days === null ? self::LEAD_DAYS : max(0, min(self::MAX_LEAD_DAYS, (int) $days));
    }

    /**
     * @return array{
     *   directions: ?string, check_in_time: ?string, check_out_time: ?string,
     *   released: bool, released_from: string, has_code: bool,
     *   withheld: ?string, withheld_message: ?string, withheld_url: ?string,
     *   door_code: ?string, wifi_ssid: ?string, wifi_password: ?string, arrival_notes: ?string,
     *   rooms: list<array{label: string, door_code: ?string, wifi_ssid: ?string, wifi_password: ?string, arrival_notes: ?string}>
     * }
     *
     * ⚠️⚠️ VY-32 (audit of 2026-11-24) — EVERY ROOM, NOT THE FIRST. A stay of two rooms showed only the first room's door code: the pack
     *    read `reservationUnits->first()`. `rooms` now carries each assigned room's own code, Wi-Fi and notes (released or withheld
     *    together, by the same rules); the scalar keys stay the FIRST room's, as before, for the one-room screens and their tests.
     */
    public static function for(Reservation $reservation, CarbonInterface $today): array
    {
        $property = $reservation->property;
        /** @var list<Unit> $units */
        $units = $reservation->reservationUnits->map(fn ($line) => $line->unit)->filter(fn ($u): bool => $u instanceof Unit)
            ->unique(fn (Unit $u): string => (string) $u->getKey())->values()->all();
        $unit = $units[0] ?? null;
        $policy = self::policy($property);

        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();
        $opensOn = $checkIn->subDays(self::leadDays($property));
        $todayDate = CarbonImmutable::parse($today)->startOfDay();

        $cancelled = in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true);
        $inWindow = $todayDate->greaterThanOrEqualTo($opensOn) && $todayDate->lessThanOrEqualTo($checkOut);

        // ⚠️ THE ORDER IS THE RULE: the first condition not met is the one reported. The balance is only read once the window is open —
        //    before it the guest is told the DATE, exactly as before.
        $withheld = match (true) {
            $cancelled => 'cancelled',
            ! $inWindow => 'window',
            default => null,
        };

        $owingMinor = 0;
        if ($withheld === null && ($policy === null || (bool) $policy->arrival_requires_paid)) {
            $owingMinor = self::owingMinor($reservation);
            $withheld = $owingMinor > 0 ? 'balance' : null;
        }
        if ($withheld === null && $policy !== null && (bool) $policy->arrival_requires_checkin && $reservation->guest_checked_in_at === null) {
            $withheld = 'checkin';
        }

        $released = $withheld === null;
        $code = $unit instanceof Unit ? self::clean($unit->door_code) : null;

        return [
            'directions' => self::directions($property),
            'check_in_time' => self::time($property?->check_in_time),
            'check_out_time' => self::time($property?->check_out_time),
            'released' => $released,
            'released_from' => $opensOn->translatedFormat(LocalDate::pattern('D, j M')),
            'has_code' => collect($units)->contains(fn (Unit $u): bool => self::clean($u->door_code) !== null),
            // WHY it is withheld (null = released), and — for the two reasons the guest can act on — the sentence and the place to act.
            'withheld' => $withheld,
            'withheld_message' => match ($withheld) {
                'balance' => __('php/support/guest/arrival_pack.pay_first', ['amount' => MoneyFormatter::display($owingMinor, (string) $reservation->currency)]),
                'checkin' => __('php/support/guest/arrival_pack.check_in_first'),
                default => null,
            },
            'withheld_url' => match ($withheld) {
                'balance' => route('guest.payments'),
                'checkin' => route('guest.checkin', $reservation->getKey()),
                default => null,
            },
            // Sensitive fields are gated on EVERY rule above — withheld (null) until all of them are met.
            'door_code' => $released ? $code : null,
            'wifi_ssid' => $released && $unit instanceof Unit ? self::clean($unit->wifi_ssid) : null,
            'wifi_password' => $released && $unit instanceof Unit ? self::clean($unit->wifi_password) : null,
            'arrival_notes' => $released && $unit instanceof Unit ? self::clean($unit->arrival_notes) : null,
            'rooms' => array_map(fn (Unit $u): array => [
                'label' => trim((string) ($u->name ?: $u->code)),
                'door_code' => $released ? self::clean($u->door_code) : null,
                'wifi_ssid' => $released ? self::clean($u->wifi_ssid) : null,
                'wifi_password' => $released ? self::clean($u->wifi_password) : null,
                'arrival_notes' => $released ? self::clean($u->arrival_notes) : null,
            ], $units),
        ];
    }

    /**
     * What the GUEST still owes for this stay, in minor units (0 when nothing is owing).
     *
     * ⚠️ THE ONE FORMULA ({@see FolioTotals}): charges − settled payments + refunds. A payment still being processed is not money
     *    received, so it does not release the code — the same "fulfil on the webhook, never on the redirect" rule as everywhere else.
     * ⚠️ NOT THE GUEST'S TO PAY → NOT OWING: a stay with no folio at all (an imported booking the channel collected for) and a folio
     *    billed to a company account (the company is invoiced; the front desk already lets that guest leave with a balance).
     */
    private static function owingMinor(Reservation $reservation): int
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->first();

        if ($folio === null || $folio->billed_to_account_id !== null) {
            return 0;
        }

        return max(0, (int) FolioTotals::for($folio)['balance_minor']);
    }

    /**
     * ⚠️⚠️ VY-32 (audit of 2026-11-24) — "TODAY" FOR THE RELEASE IS THE PROPERTY'S DAY. The screens passed the server's date (UTC), so a
     * guest in a property ahead of UTC waited until the server's midnight, and one behind it got the code a day early by local time. Every
     * caller that means "now" asks here: the property's own date ({@see BoardDate::today()}, the Calendar's and the Front Desk's rule).
     */
    public static function today(?Property $property): CarbonImmutable
    {
        return BoardDate::today($property?->timezone);
    }

    /** The property's policy row, or null when it has none (every rule then reads as its default). */
    private static function policy(?Property $property): ?PropertyPolicy
    {
        return $property?->policies()->first();
    }

    /** The property's real address as a single directions line, or null — SHARED with PreArrivalMail (no drift). */
    public static function directions(?Property $property): ?string
    {
        $address = is_array($property?->address) ? $property->address : [];
        $line = trim(implode(', ', array_filter(array_map('strval', $address))));

        return $line !== '' ? $line : null;
    }

    private static function time(mixed $value): ?string
    {
        $t = self::clean(is_string($value) ? $value : null);

        return $t !== null ? substr($t, 0, 5) : null;   // 'HH:MM' from a time/'HH:MM:SS'
    }

    private static function clean(mixed $value): ?string
    {
        $s = is_string($value) ? trim($value) : '';

        return $s !== '' ? $s : null;
    }
}
