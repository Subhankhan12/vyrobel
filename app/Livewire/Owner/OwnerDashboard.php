<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerAttention;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerOccupancy;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Owner\OwnerUpcomingStays;
use App\Support\Payouts\Iban;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Dashboard / Overview — the owner-portal landing, rebuilt to the decoded "Owner Dashboard" wireframe and
 * wired to LIVE, owner-scoped data (Owner-Overview exact-match pass). Every read is owner-scoped: the units /
 * statements / payouts come through the read-only {@see OwnerPortalService} + the {@see OwnerScope} global scope;
 * occupancy / attention / upcoming stays come through the SHARED owner derivations so this screen cannot drift
 * from My Units / Bookings. A non-linked user fails closed (empty) everywhere.
 *
 * Honesty triage (#39 — every figure a REAL owner aggregate; nothing invented):
 *   • REAL     — this-month earnings hero + MoM delta, total units, earned YTD + paid-to-date, recent
 *                statements, the monthly earnings chart, next-payout amount, the OCCUPANCY KPI + per-unit
 *                occupancy ({@see OwnerOccupancy} — owner-unit-scoped, the room-type-wide trap fixed), the
 *                attention feed ({@see OwnerAttention}), the upcoming-stays feed ({@see OwnerUpcomingStays} —
 *                guest NAME only, #28), and the masked payout IBAN + tax-hold paused-state.
 *   • OMITTED  — an earnings goal line (no goal column), a next-payout ESTIMATED DATE (no scheduled-date
 *                field), any "Scheduled/On hold" payout state (only the real tax-hold is "paused"): rendered
 *                NOWHERE, never faked.
 *   • Guest PII — upcoming stays show the guest NAME only (invariant #28) + owner NET; never email/phone.
 */
#[Layout('components.layouts.owner')]
class OwnerDashboard extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $portal = app(OwnerPortalService::class);
        $units = $portal->units();
        $statements = $portal->statements();   // owner-scoped, most recent first
        $payouts = $portal->payouts();

        $currency = $this->portfolioCurrency($statements, $payouts);
        $unitIds = array_map('strval', $units->pluck('id')->all());
        $occupancy = $this->occupancy($unitIds);

        return view('livewire.owner.dashboard', [
            'viewer' => $this->viewer(),
            'greeting' => $this->greeting(),
            'unitsCount' => $units->count(),
            'units' => $this->unitList($units, $occupancy['per_unit_pct']),
            'statements' => $this->recentStatements($statements),
            'hero' => $this->hero($statements, $payouts, $currency),
            'metrics' => $this->metrics($statements, $payouts, $currency),
            'series' => $this->earningsSeries($statements),
            'occupancy' => $occupancy,
            'attention' => $this->attention(),
            'upcoming' => OwnerUpcomingStays::forOwnerUnits($unitIds, $currency),
            'payout' => $this->payoutRail(),
            'currency' => $currency,
            'currencySymbol' => $this->currencySymbol($currency),
        ]);
    }

    /**
     * A real, time-aware greeting + the owner's first name (e.g. "Good morning, Amara"). Time-of-day from the
     * app clock; the name from the authenticated user (never invented — falls back to a plain greeting).
     */
    private function greeting(): string
    {
        $hour = (int) now()->format('G');
        $part = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        $name = trim($this->viewer()['name']);
        $first = $name === '' ? '' : (string) preg_split('/\s+/', $name)[0];

        return $first === '' || $first === 'Owner' ? $part : $part.', '.$first;
    }

    /**
     * The owner's units (real) + each unit's REAL current-month occupancy % (owner-scoped, from
     * {@see OwnerOccupancy}) for the My Units rail bars.
     *
     * @param  Collection<int, Unit>  $units
     * @param  array<string, int>  $perUnitPct
     * @return list<array{id: string, code: string, room_type: string|null, occupancy_pct: int}>
     */
    private function unitList(Collection $units, array $perUnitPct): array
    {
        return $units->map(fn (Unit $u): array => [
            'id' => $u->id,
            'code' => (string) $u->code,
            'room_type' => $u->roomType?->name,
            'occupancy_pct' => $perUnitPct[(string) $u->id] ?? 0,
        ])->values()->all();
    }

    /**
     * REAL owner-scoped occupancy: the current calendar month's portfolio % + per-unit % (via the shared
     * {@see OwnerOccupancy}, keyed by the owner's OWN physical unit ids — a non-owner unit in the same room
     * type is excluded by construction), a month-over-month delta, and a 6-month trend sparkline. Thin/no data
     * → an honest zero + no delta (never a fabricated 78%).
     *
     * @param  list<string>  $unitIds
     * @return array{has_data: bool, pct: int, pct_display: string, delta: array{label: string, positive: bool}|null, spark: array<string, mixed>, per_unit_pct: array<string, int>}
     */
    private function occupancy(array $unitIds): array
    {
        $now = Carbon::now()->startOfMonth();

        /** @var list<Carbon> $months */
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = $now->copy()->subMonthsNoOverflow($i);
        }

        $results = array_map(fn (Carbon $m): array => OwnerOccupancy::forMonth($unitIds, $m), $months);
        $pcts = array_map(fn (array $r): int => $r['pct'], $results);
        $current = $results[count($results) - 1];

        $days = $current['days'];
        $perUnitPct = [];
        foreach ($current['per_unit'] as $id => $nights) {
            $perUnitPct[(string) $id] = $days > 0 ? (int) round($nights / $days * 100) : 0;
        }

        $pct = $current['pct'];
        $prevPct = $pcts[count($pcts) - 2] ?? 0;
        $diff = $pct - $prevPct;
        $delta = $diff !== 0
            ? ['label' => ($diff > 0 ? '+' : '−').abs($diff).'%', 'positive' => $diff > 0]
            : null;

        return [
            'has_data' => array_sum($pcts) > 0,
            'pct' => $pct,
            'pct_display' => $pct.'%',
            'delta' => $delta,
            'spark' => $this->sparkline($pcts),
            'per_unit_pct' => $perUnitPct,
        ];
    }

    /**
     * A monthly-occupancy trend as an SVG path over a 0 0 100 34 viewBox (the wireframe's sparkline). `has`
     * is false when every month is 0 → the card shows the % alone rather than a flat line at the floor.
     *
     * @param  list<int>  $pcts  oldest → newest
     * @return array{has: bool, line: string, area: string, last_x: float, last_y: float}
     */
    private function sparkline(array $pcts): array
    {
        $n = count($pcts);
        $points = [];
        for ($i = 0; $i < $n; $i++) {
            $x = $n <= 1 ? 96.0 : round(2 + ($i / ($n - 1)) * 94, 1);
            $v = max(0, min(100, $pcts[$i]));
            $y = round(30 - $v / 100 * 26, 1);
            $points[] = [$x, $y];
        }

        $line = 'M'.implode(' L', array_map(fn (array $p): string => $p[0].','.$p[1], $points));
        $first = $points[0];
        $last = $points[$n - 1];

        return [
            'has' => $pcts !== [] && max($pcts) > 0,
            'line' => $line,
            'area' => $line.' L'.$last[0].',34 L'.$first[0].',34 Z',
            'last_x' => $last[0],
            'last_y' => $last[1],
        ];
    }

    /**
     * The owner's REAL "Needs your attention" items, via the ONE shared {@see OwnerAttention} feed (so the
     * Overview and My Units cannot disagree). Empty → the view's honest "caught up".
     *
     * @return list<array{tone: string, title: string, body: string, cta: array{label: string, route: string}}>
     */
    private function attention(): array
    {
        return OwnerAttention::items(app(OwnerContext::class));
    }

    /**
     * The next-payout rail's REAL data: the masked IBAN of the owner's default payout method (owner-scoped;
     * shown only to a payout-capable role — CAP_PAYOUT, co-owner) and the tax-hold paused-state (a pending
     * payout AND an owner with no tax details on file — the ONLY real "paused"). No estimated date (no field).
     *
     * @return array{iban_masked: string|null, paused: bool}
     */
    private function payoutRail(): array
    {
        $context = app(OwnerContext::class);

        $method = PayoutMethod::query()->orderByDesc('is_default')->first();
        $ibanMasked = ($context->can(OwnerTeamRole::CAP_PAYOUT) && $method instanceof PayoutMethod)
            ? Iban::masked((string) $method->iban_last4)
            : null;

        $paused = false;
        if ($context->active() && $context->ownerIds() !== []) {
            $hasPending = Payout::query()->where('status', 'pending')->exists();
            $missingTax = Owner::query()->whereIn('id', $context->ownerIds())->get()
                ->contains(fn (Owner $o): bool => ! $o->hasTaxDetails());
            $paused = $hasPending && $missingTax;
        }

        return ['iban_masked' => $ibanMasked, 'paused' => $paused];
    }

    /**
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function recentStatements(Collection $statements): array
    {
        return $statements->take(6)->map(fn (OwnerStatement $s): array => [
            'id' => $s->id,
            'month_label' => $s->period_start->format('F Y'),
            'period_range' => $s->period_start->format('j M').' – '.$s->period_end->format('j M Y'),
            'net_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'status_meta' => $this->statusMeta((string) $s->status),
        ])->values()->all();
    }

    /**
     * This-month earnings + a month-over-month delta (from the two most recent statements) + the next
     * payout (a real pending payout, else the latest statement's net). All real/derived, never invented.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @param  Collection<int, Payout>  $payouts
     * @return array<string, mixed>
     */
    private function hero(Collection $statements, Collection $payouts, string $currency): array
    {
        $latest = $statements->first();
        $prev = $statements->get(1);
        $thisMonthMinor = $latest instanceof OwnerStatement ? (int) $latest->net_payable_minor : 0;

        $delta = null;
        if ($latest instanceof OwnerStatement && $prev instanceof OwnerStatement) {
            $prevNet = (int) $prev->net_payable_minor;
            if ($prevNet !== 0) {
                $pct = (int) round(($thisMonthMinor - $prevNet) / abs($prevNet) * 100);
                $delta = [
                    'label' => ($pct >= 0 ? '+' : '−').abs($pct).'% vs '.$prev->period_start->format('F'),
                    'positive' => $pct >= 0,
                ];
            }
        }

        $pending = $payouts->first(fn (Payout $p): bool => $p->status === 'pending');
        $nextMinor = $pending instanceof Payout ? (int) $pending->amount_minor : $thisMonthMinor;
        $nextCurrency = $pending instanceof Payout ? (string) $pending->currency : $currency;
        $nextStatus = $pending instanceof Payout
            ? $this->payoutStatusMeta((string) $pending->status)
            : ($latest instanceof OwnerStatement ? $this->statusMeta((string) $latest->status) : ['label' => '—', 'tone' => 'draft']);

        return [
            'has_data' => $latest instanceof OwnerStatement,
            'month_label' => $latest instanceof OwnerStatement ? strtoupper($latest->period_start->format('F Y')) : '',
            'this_month_minor' => $thisMonthMinor,
            'this_month_display' => $this->money($thisMonthMinor, $currency),
            'currency_symbol' => $this->currencySymbol($currency),
            'delta' => $delta,
            'next_payout_display' => $this->money($nextMinor, $nextCurrency),
            'next_payout_status' => $nextStatus,
        ];
    }

    /**
     * YTD earned (Σ net across the owner's statements in the latest statement's year) + paid-to-date
     * (Σ paid payouts). Both real/derived.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @param  Collection<int, Payout>  $payouts
     * @return array{year: string|null, earned_ytd_display: string, paid_display: string}
     */
    private function metrics(Collection $statements, Collection $payouts, string $currency): array
    {
        $latest = $statements->first();
        $year = $latest instanceof OwnerStatement ? $latest->period_start->format('Y') : null;

        $earnedYtd = $year === null ? 0 : (int) $statements
            ->filter(fn (OwnerStatement $s): bool => $s->period_start->format('Y') === $year)
            ->sum(fn (OwnerStatement $s): int => (int) $s->net_payable_minor);

        $paid = (int) $payouts
            ->filter(fn (Payout $p): bool => $p->status === 'paid')
            ->sum(fn (Payout $p): int => (int) $p->amount_minor);

        return [
            'year' => $year,
            'earned_ytd_display' => $this->money($earnedYtd, $currency),
            'paid_display' => $this->money($paid, $currency),
        ];
    }

    /**
     * The monthly earnings series for the chart — the owner's REAL finalized statements (last 12,
     * chronological), each bar scaled against the series max. No fabricated series.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return array{bars: list<array{label: string, net_display: string, pct: int}>, count: int}
     */
    private function earningsSeries(Collection $statements): array
    {
        $chrono = $statements
            ->sortBy(fn (OwnerStatement $s): string => $s->period_start->toDateString())
            ->values()
            ->take(-12);

        $max = (int) $chrono->max(fn (OwnerStatement $s): int => max(0, (int) $s->net_payable_minor));
        $max = $max > 0 ? $max : 1;

        $bars = $chrono->map(fn (OwnerStatement $s): array => [
            'label' => $s->period_start->format('M'),
            'net_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'pct' => (int) round(max(0, (int) $s->net_payable_minor) / $max * 100),
        ])->values()->all();

        return ['bars' => $bars, 'count' => count($bars)];
    }
}
