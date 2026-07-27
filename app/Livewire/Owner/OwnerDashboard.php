<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Dashboard (design pass · Screen 2) — the owner-portal overview, styled from the catalogued
 * "Owner Dashboard" design and wired to LIVE, owner-scoped data. Presentation only: every read goes
 * through the read-only {@see OwnerPortalService} (called, never modified); NO backend/schema/guarantee
 * change. Owner isolation ({@see OwnerScope}) makes every read the owner's own rows;
 * a non-linked user fails closed (empty).
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — unit count + list (codes), recent statements (period/status/net → link to the statement
 *                screen), this-month earnings hero, next-payout amount, YTD-earned + paid-to-date totals.
 *   • DERIVED  — month-over-month earnings delta + the monthly earnings chart series, both computed from
 *                the owner's REAL finalized statements only (never invented or persisted).
 *   • DEFERRED — occupancy KPI + per-unit occupancy: `OwnerPortalService::occupancy()` wraps
 *                `availabilityRange`, which is ROOM-TYPE-wide (counts every unit of the type in the
 *                property, not just this owner's), so it does NOT represent owner occupancy — stubbed +
 *                flagged "not yet available" rather than shown misleadingly.
 *   • NO BACKEND — "Needs your attention" action centre, quick actions, IBAN/payout-method, statement
 *                downloads: disabled "Soon" affordances, never a fake action.
 *   • Guest PII — the design's "upcoming stays" lists guest names (encrypted PII); NOT exposed here.
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

        return view('livewire.owner.dashboard', [
            'viewer' => $this->viewer(),
            'greeting' => $this->greeting(),
            'unitsCount' => $units->count(),
            'units' => $this->unitList($units),
            'statements' => $this->recentStatements($statements),
            'hero' => $this->hero($statements, $payouts, $currency),
            'metrics' => $this->metrics($statements, $payouts, $currency),
            'series' => $this->earningsSeries($statements),
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
     * The owner's units (real). Occupancy is intentionally NOT included — see the class docblock.
     *
     * @param  Collection<int, Unit>  $units
     * @return list<array{id: string, code: string, room_type: string|null}>
     */
    private function unitList(Collection $units): array
    {
        return $units->map(fn (Unit $u): array => [
            'id' => $u->id,
            'code' => (string) $u->code,
            'room_type' => $u->roomType?->name,
        ])->values()->all();
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
