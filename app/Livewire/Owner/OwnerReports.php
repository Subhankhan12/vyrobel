<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Analytics\MetricSet;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Money\MoneyFormatter;
use App\Support\Notifications\NotificationFeed;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner REPORTS (exact-match pass · invariant #61) — the owner's REAL analytics for their OWN units, rebuilt to
 * the decoded "Owner Reports" wireframe. OWNER-SCOPED via {@see OwnerPortalService::units()} + the owner-scoped
 * {@see OwnerLedgerEntry} (never a request id; an owner sees only their own). Every figure reconciles by
 * construction to the owner statements/ledger (#17) — the Batch-B analytics layer, reused (anti-drift).
 *
 * HONESTY MAP:
 *   • REAL — the KPIs (net earned / avg occupancy / nights sold), the net-by-month chart, per-unit performance
 *     (ADR/RevPAR/nights/occupancy/net/share), by-channel (the real `reservations.source` via `sourceNights`,
 *     not an OTA brand #39), the GROSS-TO-NET (reconciles #17, with the REAL agreement commission rate — never a
 *     hardcoded 15%), the payout summary, the real Documents links + exports (CAP_EXPORTS-gated), and the
 *     Notifications panel (the self-scoped {@see NotificationFeed}).
 *   • HONEST-DEFER — "Email to me" (no report Mailable) is a "Soon", never a fake send; the design's **Tax & VAT
 *     summary** is NOT computed by Vyrobel (no VAT model — only a stored VAT number), so it renders as an honest
 *     deferred card (no fabricated Output/Input/Net VAT figure, no "Mark filed" #39), pointing at the real annual
 *     tax summary + Tax Center.
 *   • REFUSED — the wireframe's fabricated multi-year year-dropdown figures (2025/2024/2023) + the invented
 *     notification rows; the real range options + the real feed are shown instead.
 */
#[Layout('components.layouts.owner')]
class OwnerReports extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed
        $currency = $this->currency($units);
        $range = $this->resolveRange();
        $unitIds = $units->map(fn (Unit $u): string => (string) $u->id)->all();

        // Owner-scoped ledger — the single source of "earnings", clipped to the range for the period figures.
        $allEntries = OwnerLedgerEntry::query()->get();
        $rangeEntries = $allEntries->filter(fn (OwnerLedgerEntry $e): bool => $this->inRange($e, $range))->values();

        $metrics = PerformanceMetrics::forOwnerUnits($unitIds, $range['start'], $range['end'], $currency);
        $netByUnit = $this->netByUnit($rangeEntries);
        $totalNet = OwnerLedgerAttribution::netDisplays($rangeEntries);

        $user = request()->user();

        return view('livewire.owner.reports', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'ranges' => $this->rangeOptions($range['key']),
            'activeRange' => $range,
            'kpis' => [
                'net' => $totalNet === [] ? $this->money(0, $currency) : implode(' · ', $totalNet),
                'net_period' => $range['label'],
                'occupancy' => $metrics->occupancyPct().'%',
                'nights' => $metrics->roomNightsSold,
            ],
            'netByMonth' => $this->netByMonth($rangeEntries, $range, $currency),
            'perUnit' => $this->perUnitRows($units, $range, $currency, $netByUnit, $totalNet),
            'byChannel' => $this->channelRows($metrics),
            'grossToNet' => $this->grossToNet($rangeEntries, $currency),
            'commissionLabel' => $this->commissionLabel(),
            'payout' => $this->payoutSummary($currency),
            'documents' => $this->recentDocuments(),
            'notifications' => $user !== null ? NotificationFeed::recentForUser($user, 4) : ['items' => [], 'unread' => 0],
        ]);
    }

    /** A ledger entry falls in range when its service_date is within `[start, end)`. */
    private function inRange(OwnerLedgerEntry $entry, array $range): bool
    {
        $date = CarbonImmutable::parse((string) $entry->service_date);

        return $date->greaterThanOrEqualTo($range['start']) && $date->lessThan($range['end']);
    }

    /**
     * Net per currency PER UNIT — the owner's ledger grouped by unit, each summed through the ONE shared
     * attribution rule so a per-unit figure and the total can never disagree.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array<string, array<string, int>> unitId → (currency → net minor)
     */
    private function netByUnit(Collection $entries): array
    {
        $out = [];

        foreach ($entries->groupBy('unit_id') as $unitId => $group) {
            if ((string) $unitId === '') {
                continue;
            }
            $out[(string) $unitId] = OwnerLedgerAttribution::netPerCurrency($group);
        }

        return $out;
    }

    /**
     * Net earnings by MONTH across the range — Σ signedMinor() of the owner's ledger entries per calendar month,
     * in the display currency (reconciles to the total by construction). Bars scale to the max month.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array{months: list<array{label: string, net_minor: int, net_display: string, pct: int}>, has_data: bool}
     */
    private function netByMonth(Collection $entries, array $range, string $currency): array
    {
        $byMonth = [];
        $cursor = $range['start']->startOfMonth();
        $end = $range['end'];

        while ($cursor->lessThan($end)) {
            $byMonth[$cursor->format('Y-m')] = ['label' => $cursor->format('M'), 'net' => 0];
            $cursor = $cursor->addMonth();
        }

        foreach ($entries as $entry) {
            if ((string) $entry->currency !== $currency) {
                continue;   // never mix currencies into one bar series (#17)
            }
            $key = CarbonImmutable::parse((string) $entry->service_date)->format('Y-m');
            if (isset($byMonth[$key])) {
                $byMonth[$key]['net'] += $entry->signedMinor();
            }
        }

        $maxAbs = 0;
        foreach ($byMonth as $m) {
            $maxAbs = max($maxAbs, abs($m['net']));
        }

        $months = [];
        $withData = 0;
        foreach ($byMonth as $m) {
            if ($m['net'] !== 0) {
                $withData++;
            }
            $months[] = [
                'label' => $m['label'],
                'net_minor' => $m['net'],
                'net_display' => $this->money($m['net'], $currency),
                'pct' => $maxAbs > 0 ? (int) round(abs($m['net']) / $maxAbs * 100) : 0,
            ];
        }

        return ['months' => $months, 'has_data' => $withData > 0];
    }

    /**
     * Per-unit performance rows — ADR / RevPAR / nights / occupancy / net / share of net, all REAL per-unit
     * aggregates. Share is this unit's net as a % of the owner's total net (display currency), so the shares
     * reconcile to 100% of the real total.
     *
     * @param  Collection<int, Unit>  $units
     * @param  array<string, array<string, int>>  $netByUnit
     * @param  list<string>  $totalNet
     * @return list<array<string, mixed>>
     */
    private function perUnitRows(Collection $units, array $range, string $currency, array $netByUnit, array $totalNet): array
    {
        $totalNetMinor = 0;
        foreach ($netByUnit as $byCurrency) {
            $totalNetMinor += $byCurrency[$currency] ?? 0;
        }

        return $units->map(function (Unit $unit) use ($range, $currency, $netByUnit, $totalNetMinor): array {
            $m = PerformanceMetrics::forOwnerUnits([(string) $unit->id], $range['start'], $range['end'], $currency);
            $net = $netByUnit[(string) $unit->id] ?? [];
            $netMinor = $net[$currency] ?? 0;

            return [
                'name' => $unit->name !== null ? (string) $unit->name : (string) $unit->code,
                'adr' => $this->money($m->adrMinor(), $currency),
                'revpar' => $this->money($m->revparMinor(), $currency),
                'nights' => $m->roomNightsSold,
                'occupancy_pct' => $m->occupancyPct(),
                'net' => $net === [] ? '—' : implode(' · ', array_map(fn (int $minor, string $c): string => $this->money($minor, $c), array_values($net), array_keys($net))),
                'share_pct' => $totalNetMinor > 0 ? (int) round($netMinor / $totalNetMinor * 100) : 0,
            ];
        })->all();
    }

    /**
     * Booking sources by channel — the REAL `reservations.source` enum via the owner MetricSet's `sourceNights`
     * (Direct / OTA / Corporate / …), each as a share of room-nights. NOT an OTA brand (#39).
     *
     * @return list<array{label: string, nights: int, pct: int}>
     */
    private function channelRows(MetricSet $m): array
    {
        $total = array_sum($m->sourceNights);
        if ($total === 0) {
            return [];
        }

        $rows = [];
        foreach ($m->sourceNights as $source => $nights) {
            $rows[] = [
                'label' => $this->sourceLabel((string) $source),
                'nights' => (int) $nights,
                'pct' => (int) round($nights / $total * 100),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['nights'] <=> $a['nights']);

        return $rows;
    }

    /** The `reservations.source` enum → an owner-facing channel label (the real value, never a brand). */
    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'direct' => 'Direct',
            'ota' => 'Online travel agency',
            'walk_in' => 'Walk-in',
            'corporate' => 'Corporate',
            'group' => 'Group',
            default => ucfirst(str_replace('_', ' ', $source)),
        };
    }

    /**
     * Gross-to-net for the range, in the display currency — bucketed from the REAL owner ledger so
     * gross + commission + fees + adjustments == net EXACTLY (Σ signedMinor over every entry — #17). The
     * commission line carries the REAL agreement rate (never a hardcoded 15%).
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array<string, mixed>
     */
    private function grossToNet(Collection $entries, string $currency): array
    {
        $inCurrency = $entries->filter(fn (OwnerLedgerEntry $e): bool => (string) $e->currency === $currency);

        $sum = fn (array $types): int => (int) $inCurrency
            ->filter(fn (OwnerLedgerEntry $e): bool => in_array((string) $e->entry_type, $types, true))
            ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);

        $gross = $sum(['room_revenue', 'addon']);
        $commission = $sum(['commission']);
        $fees = $sum(['ota_cost', 'management_fee']);
        $adjustments = $sum(['cleaning', 'expense', 'adjustment']);
        $net = (int) $inCurrency->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);

        return [
            'gross' => MoneyFormatter::display($gross, $currency),
            'commission' => MoneyFormatter::display($commission, $currency, true),
            'fees' => MoneyFormatter::display($fees, $currency, true),
            'adjustments' => MoneyFormatter::display($adjustments, $currency, true),
            'has_adjustments' => $adjustments !== 0,
            'net' => MoneyFormatter::display($net, $currency),
            // reconciliation guarantee: the four buckets sum to net (every entry_type is in exactly one bucket).
            'reconciles' => $gross + $commission + $fees + $adjustments === $net,
        ];
    }

    /**
     * The owner's CURRENT commission rate, from the effective {@see ManagementAgreement} (the REAL per-owner rate
     * — 18% in the demo, NOT the wireframe's hardcoded 15%). ManagementAgreement carries no OwnerScope, so the
     * owner boundary is applied EXPLICITLY here; a null/ambiguous set omits the rate label rather than guessing.
     */
    private function commissionLabel(): string
    {
        $percent = OwnerCommissionRate::percent(app(OwnerContext::class));

        return $percent === null ? 'Commission' : 'Commission · '.$percent.'%';
    }

    /**
     * The payout summary — the owner's most recent real {@see Payout} (amount + status) + the real paid-this-year
     * total. Owner-scoped; honest zeros when there are none.
     *
     * @return array{latest_display: ?string, status: ?string, paid_year_display: string}
     */
    private function payoutSummary(string $currency): array
    {
        $payouts = Payout::query()->orderByDesc('created_at')->get();
        $latest = $payouts->first();

        $paidYear = (int) $payouts
            ->filter(fn (Payout $p): bool => $p->status === 'paid' && (int) $p->created_at?->year === (int) now()->year)
            ->sum('amount_minor');

        return [
            'latest_display' => $latest instanceof Payout ? MoneyFormatter::display((int) $latest->amount_minor, (string) $latest->currency) : null,
            'status' => $latest instanceof Payout ? ucfirst((string) $latest->status) : null,
            'paid_year_display' => MoneyFormatter::display($paidYear, $currency),
        ];
    }

    /**
     * A compact list of the most recent REAL statement PDFs (owner-scoped, finalized/paid only) — each a real
     * CAP_EXPORTS-gated download. The full library lives on the Documents page.
     *
     * @return list<array{label: string, href: string}>
     */
    private function recentDocuments(): array
    {
        return OwnerStatement::query()
            ->whereIn('status', ['finalized', 'paid'])
            ->orderByDesc('period_start')
            ->limit(3)
            ->get()
            ->map(fn (OwnerStatement $s): array => [
                'label' => $s->period_start->format('F Y').' statement',
                'href' => route('owner.statements.pdf', $s->id),
            ])
            ->all();
    }

    /**
     * @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}
     */
    private function resolveRange(): array
    {
        $today = CarbonImmutable::today();
        $key = (string) request()->query('range', 'this-year');

        return match ($key) {
            'ytd' => ['key' => 'ytd', 'label' => 'Year to date', 'start' => $today->startOfYear(), 'end' => $today->addDay()],
            '6m' => ['key' => '6m', 'label' => 'Last 6 months', 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay()],
            default => ['key' => 'this-year', 'label' => $today->format('Y'), 'start' => $today->startOfYear(), 'end' => $today->startOfYear()->addYear()],
        };
    }

    /**
     * @return list<array{key: string, label: string, active: bool}>
     */
    private function rangeOptions(string $active): array
    {
        $year = CarbonImmutable::today()->format('Y');

        return [
            ['key' => 'this-year', 'label' => $year, 'active' => $active === 'this-year'],
            ['key' => 'ytd', 'label' => 'Year to date', 'active' => $active === 'ytd'],
            ['key' => '6m', 'label' => 'Last 6 months', 'active' => $active === '6m'],
        ];
    }

    /**
     * @param  Collection<int, Unit>  $units
     */
    private function currency(Collection $units): string
    {
        $first = $units->first();

        return (string) ($first?->property->currency ?? 'EUR');
    }
}
