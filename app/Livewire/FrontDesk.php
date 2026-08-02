<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\FrontDeskActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\FrontDeskService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Front Desk — today's arrivals / departures / in-house board (design pass · Operator
 * Screen 2). Styled from the catalogued "Operator Front Desk" design onto the SAME operator design
 * system as Screen 1 (the operator layout, <x-operator.shell>, FormatsOperatorData — reused, never
 * redefined). Presentation + a thin read: the board only READS; the check-in/out MUTATIONS run
 * through the real {@see FrontDeskService} via a POST route ({@see FrontDeskActionController}),
 * NOT from here — so no inventory/folio/schema/guarantee is touched by this component.
 *
 * TENANT-scoped (BelongsToOrganization) — one org never sees another's reservations. The day is
 * chosen via a plain `?date=` GET link (RMB-safe, re-runs the tenant middleware); no route-model-bound
 * props; the lists resolve in render().
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL       — arrivals/departures/in-house (FrontDeskService); per row the real guest name, real
 *                  reservation status, unit assignment or "Unassigned", source, nights, guests, and the
 *                  real folio balance (`balance_minor`, maintained by FolioService). Check-in/out are
 *                  REAL and persist (via the POST action).
 *   • DERIVED    — the count chips + outstanding-balance total, from the real figures only.
 *   • DEFERRED   — "Collect payment" (needs an amount + card-token + gateway flow — out of a
 *                  presentation pass) is shown real-looking but flagged "not yet available"; it never
 *                  fakes a payment. "Assign unit" links to the rack (the real assignment surface) —
 *                  never an invented assignment.
 *   • NO BACKEND — Walk-in, filters, print/export: disabled "Soon".
 *   • Guest PII  — names are shown (front-desk surface); encrypted email/phone are never rendered.
 *
 * The view receives, for each of arrivals/departures/inHouse:
 *   list<array{ id, reference, guest, status, status_label, tone, source, unit, unassigned,
 *     nights, guests, check_in, check_out, balance_minor, balance, has_balance,
 *     can_check_in, can_check_out }>
 */
#[Layout('components.layouts.operator')]
class FrontDesk extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    public function render(): View
    {
        $desk = app(FrontDeskService::class);
        $property = $this->currentProperty();

        $date = $this->resolveDate($property);

        $arrivals = [];
        $departures = [];
        $inHouse = [];

        if ($property !== null) {
            $arrivals = $this->present($desk->arrivals($property->id, $date));
            $departures = $this->present($desk->departures($property->id, $date));
            $inHouse = $this->present($desk->inHouse($property->id, $date));
        }

        $day = CarbonImmutable::parse($date);

        return view('livewire.front-desk', [
            'viewer' => $this->viewer(),
            'property' => $property !== null ? ['id' => $property->id, 'name' => $property->name] : null,
            'day' => [
                'date' => $date,
                'label' => $day->format('l, j M Y'),
                'prev' => $day->subDay()->toDateString(),
                'next' => $day->addDay()->toDateString(),
                'today' => CarbonImmutable::today()->toDateString(),
                'is_today' => $date === CarbonImmutable::today()->toDateString(),
            ],
            'arrivals' => $arrivals,
            'departures' => $departures,
            'inHouse' => $inHouse,
            'summary' => $this->summary($arrivals, $departures, $inHouse),
            'timeline' => $this->timeline($property, $arrivals, $departures, $date === CarbonImmutable::today()->toDateString()),
        ]);
    }

    /**
     * The day TIMELINE RAIL (the wireframe's plotted-dots axis) — REAL SCHEDULED times only, never a
     * fabricated "live arrival". An arrival plots at its parsed `expected_arrival` time when the guest gave
     * one, else the property's configured default check-in time; a departure plots at the default check-out
     * time. The now-line is the property's real local time (only on today). Every dot carries the guest
     * NAME only (#28). Presentation over already-real figures — nothing is invented.
     *
     * @param  list<array<string, mixed>>  $arrivals
     * @param  list<array<string, mixed>>  $departures
     * @return array{has_points: bool, points: list<array<string, mixed>>, marks: list<array{label: string, pos: float}>, start_min: int, span: int, now_min: int|null}
     */
    private function timeline(?Property $property, array $arrivals, array $departures, bool $isToday): array
    {
        $startMin = 6 * 60;    // 06:00
        $endMin = 23 * 60;     // 23:00 — covers a default 11:00 checkout, 15:00 check-in and late arrivals
        $span = $endMin - $startMin;

        $ciDefault = $this->minutesFromTime($property?->check_in_time, 15 * 60);
        $coDefault = $this->minutesFromTime($property?->check_out_time, 11 * 60);

        $points = [];
        foreach ($arrivals as $row) {
            $min = $this->parseArrivalMinutes(is_string($row['expected_arrival'] ?? null) ? $row['expected_arrival'] : null, $ciDefault);
            $points[] = $this->plot('arrival', $row, $min, $startMin, $span);
        }
        foreach ($departures as $row) {
            $points[] = $this->plot('departure', $row, $coDefault, $startMin, $span);
        }

        $marks = [];
        for ($h = 6; $h <= 21; $h += 3) {
            $marks[] = ['label' => sprintf('%02d:00', $h), 'pos' => round((($h * 60) - $startMin) / $span * 100, 2)];
        }

        $nowMin = null;
        if ($isToday) {
            $now = CarbonImmutable::now($property?->timezone !== null && $property->timezone !== '' ? $property->timezone : config('app.timezone'));
            $nowMin = ((int) $now->format('G')) * 60 + (int) $now->format('i');
        }

        return ['has_points' => $points !== [], 'points' => $points, 'marks' => $marks, 'start_min' => $startMin, 'span' => $span, 'now_min' => $nowMin];
    }

    /**
     * One plotted dot. `pos` is the percentage across the visible window (clamped); `in_window` says whether
     * the real scheduled time actually falls inside the axis (an out-of-window time is pinned to the edge and
     * flagged, never silently relocated).
     *
     * @param  array<string, mixed>  $row
     * @return array{type: string, guest: string, time: string, pos: float, in_window: bool, is_vip: bool, unassigned: bool}
     */
    private function plot(string $type, array $row, int $min, int $startMin, int $span): array
    {
        $pos = $span > 0 ? (($min - $startMin) / $span) * 100 : 0.0;

        return [
            'type' => $type,
            'guest' => (string) ($row['guest'] ?? ''),
            'time' => sprintf('%02d:%02d', intdiv($min, 60), $min % 60),
            'pos' => max(0.0, min(100.0, round($pos, 2))),
            'in_window' => $min >= $startMin && $min <= $startMin + $span,
            'is_vip' => (bool) ($row['is_vip'] ?? false),
            'unassigned' => (bool) ($row['unassigned'] ?? false),
        ];
    }

    /** Minutes-of-day from a stored `HH:MM[:SS]` time column, or a default when absent/unparseable. */
    private function minutesFromTime(mixed $time, int $default): int
    {
        if (preg_match('/^(\d{1,2}):(\d{2})/', trim((string) $time), $m) === 1) {
            return ((int) $m[1]) * 60 + (int) $m[2];
        }

        return $default;
    }

    /**
     * Minutes-of-day parsed from the guest's free-text arrival window — trusting ONLY an explicit HH:MM or an
     * am/pm-qualified hour (so a stray number never misfires), else the scheduled default. Honest: an
     * unparseable "Late"/"Morning" plots at the property's default check-in time, not a guessed minute.
     */
    private function parseArrivalMinutes(?string $text, int $default): int
    {
        if ($text === null) {
            return $default;
        }
        if (preg_match('/(\d{1,2}):(\d{2})/', $text, $m) === 1) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h <= 23 && $min <= 59) {
                return $h * 60 + $min;
            }
        }
        if (preg_match('/(\d{1,2})\s*(a|p)\.?\s*m/i', $text, $m) === 1) {
            $h = ((int) $m[1]) % 12;
            if (strtolower($m[2]) === 'p') {
                $h += 12;
            }

            return $h * 60;
        }

        return $default;
    }

    /**
     * The board's day: `?date=YYYY-MM-DD` if valid, else the most recent arrival date for the
     * property (so the demo/live board lands on real activity), else today.
     */
    private function resolveDate(?Property $property): string
    {
        $param = request()->query('date');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            return CarbonImmutable::parse($param)->toDateString();
        }

        $latest = $property === null ? null : Reservation::query()
            ->where('property_id', $property->id)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->max('check_in');

        return $latest !== null
            ? CarbonImmutable::parse($latest)->toDateString()
            : CarbonImmutable::today()->toDateString();
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return list<array<string, mixed>>
     */
    private function present(Collection $reservations): array
    {
        // Eager-load the active lines' units for the assignment display (presentation concern —
        // the service's own read query stays untouched). Wrap in an Eloquent collection locally so
        // the load happens without an N+1 and without mutating the service's contract.
        $rows = EloquentCollection::make($reservations->all());
        $rows->load(['reservationUnits' => fn ($q) => $q->where('status', 'active'), 'reservationUnits.unit']);

        // #104 GATE 0 — the check-in/out routes are now gated `reservations.checkin`/`checkout`; gate the row
        // CONTROLS on the SAME permissions so a back-office role that can VIEW the desk but not work it (e.g.
        // Reservation Agent / Accountant) never sees a button that 403s.
        $canCheckIn = (bool) auth()->user()?->can('reservations.checkin');
        $canCheckOut = (bool) auth()->user()?->can('reservations.checkout');

        return $rows->map(function (Reservation $reservation) use ($canCheckIn, $canCheckOut): array {
            $guest = $reservation->leadGuest;
            $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';

            $status = (string) $reservation->status;
            $meta = $this->reservationStatusMeta($status);

            $lines = $reservation->reservationUnits;
            $assignedCodes = $lines
                ->filter(fn ($l) => $l->unit_id !== null && $l->unit !== null)
                ->map(fn ($l) => (string) ($l->unit->name ?? $l->unit->code))
                ->unique()->values();
            $unassigned = $lines->isNotEmpty() && $lines->contains(fn ($l) => $l->unit_id === null);

            $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
            $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();
            $balanceMinor = (int) $reservation->balance_minor;
            $currency = (string) ($reservation->currency ?? 'EUR');

            return [
                'id' => $reservation->id,
                'reference' => (string) $reservation->reference,
                'guest' => $name !== '' ? $name : (string) $reservation->reference,
                'status' => $status,
                'status_label' => $meta['label'],
                'tone' => $meta['tone'],
                'source' => $this->sourceLabel((string) $reservation->source),
                'unit' => $assignedCodes->isNotEmpty() ? $assignedCodes->implode(', ') : null,
                'unassigned' => $unassigned,
                'nights' => (int) $checkIn->diffInDays($checkOut),
                'guests' => (int) $reservation->adults + (int) $reservation->children,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                // #104 GATE 3 — the guest's estimated arrival (real, was dropped at booking). Free-text window.
                'expected_arrival' => ($ea = trim((string) ($reservation->expected_arrival ?? ''))) !== '' ? $ea : null,
                'balance_minor' => $balanceMinor,
                'balance' => $this->money($balanceMinor, $currency),
                'has_balance' => $balanceMinor > 0,
                'can_check_in' => $status === 'confirmed' && $canCheckIn,
                'can_check_out' => $status === 'checked_in' && $canCheckOut,
                // #87 — the guest's VIP flag + stay preferences flow to the front desk so arrivals get the
                // right care (the once-empty "flags the front desk" promise, made true). These are operational
                // flags, NOT contact PII — the name-only perimeter (email/phone) is unaffected (#28).
                'is_vip' => $guest !== null && (bool) $guest->is_vip,
                'preferences' => $guest !== null ? $guest->preferenceLabels() : [],
            ];
        })->all();
    }

    /**
     * DERIVED count chips, from the real rows only. The four are the wireframe's KPI band —
     * Arriving / Departing / In-house / Unassigned — plus the due-count that drives the row balances.
     * "Unassigned" is the real number of TODAY'S ARRIVALS still without a unit (the actionable ones —
     * an unassigned in-house guest can't be seated today anyway).
     *
     * @param  list<array<string, mixed>>  $arrivals
     * @param  list<array<string, mixed>>  $departures
     * @param  list<array<string, mixed>>  $inHouse
     * @return array{arrivals: int, departures: int, in_house: int, unassigned: int, due_count: int}
     */
    private function summary(array $arrivals, array $departures, array $inHouse): array
    {
        $dueCount = 0;
        foreach ([$arrivals, $departures, $inHouse] as $set) {
            foreach ($set as $row) {
                if (($row['has_balance'] ?? false) === true) {
                    $dueCount++;
                }
            }
        }

        $unassigned = 0;
        foreach ($arrivals as $row) {
            if (($row['unassigned'] ?? false) === true) {
                $unassigned++;
            }
        }

        return [
            'arrivals' => count($arrivals),
            'departures' => count($departures),
            'in_house' => count($inHouse),
            'unassigned' => $unassigned,
            'due_count' => $dueCount,
        ];
    }
}
