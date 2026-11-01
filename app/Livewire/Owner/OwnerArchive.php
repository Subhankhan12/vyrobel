<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\Scopes\OwnerScope;
use App\Support\I18n\LocalDate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Archive (Wave 1 · Sub-gate 1) — the owner's FINALIZED statement history, styled from the
 * catalogued "Owner Archive" design and wired to LIVE, owner-scoped rows. Presentation only: it reads
 * the CERTIFIED owner-statement projection through the owner-scoped {@see OwnerStatement} model and
 * changes NO service, schema, or owner-accounting guarantee. Owner isolation ({@see OwnerScope}) makes
 * every read the owner's own rows; a non-linked user fails closed (empty). No route-model-bound props —
 * the read happens in render() under the active owner+tenant scope.
 *
 * ARCHIVE == FINALIZED HISTORY: status ∈ ('finalized','paid') — equivalently `finalized_at` NOT NULL.
 * A DRAFT is NOT archive: it is still being prepared, carries no `sequence_no`, and its figures can
 * still move on the next regenerate. It is EXCLUDED here rather than listed as a document.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — the finalized/paid rows: the per-owner gap-free `sequence_no` (the real statement
 *                number, stamped AT FINALIZE — rendered "No. 7"; omitted, never faked, when null), the
 *                real period, `net_payable_minor`, the status badge from the REAL enum ONLY
 *                (paid → "Paid", finalized → "Payout pending"; no other value exists), the real
 *                `has_unverified` flag, the statement link, and the statement PDF — that route already
 *                exists, is owner-scoped, and reconciles to the cent, so it is a REAL download.
 *   • DERIVED  — "Net earned · lifetime" = Σ `net_payable_minor` over those REAL rows, totalled PER
 *                PAYOUT CURRENCY (`owner_statements.currency`) and rendered per currency: NEVER a
 *                cross-currency sum (no silent FX — invariant #17). Likewise the year grouping, the
 *                per-year net + count, the covered-month span, the statement count and the "since …"
 *                span — all computed from the real earliest/latest period rows. The per-year bars are
 *                rendered ONLY when every row shares one payout currency; bars across currencies would
 *                imply an FX comparison the backend never made. Search filters the REAL rows
 *                client-side (same idiom as OwnerPayouts) — it invents no rows.
 *   • REAL     — the per-year ANNUAL TAX SUMMARY PDF. ⚠️ This block used to read "there is no tax engine,
 *                and its Owner Annual Tax Summary PDF does not exist" — stale since the tax engine (#73)
 *                shipped. Both exist: the view links `owner.tax.annual` per year, CAP_EXPORTS-gated, for
 *                years that have committed statements (exactly what annualSummary() sums), so it never 404s.
 *   • NO BACKEND — "Download the full archive (ZIP)" only (there is no ZIP builder): a disabled "Soon",
 *                never a fake download link.
 *   • NOT PORTED — the design hard-codes "3 years · 34 documents", "since June 2023", a 7-year
 *                retention promise, and an "OS-2025-09" reference format. All four are static fiction
 *                with no backing row or policy: the counts + span are DERIVED from the real rows, the
 *                real reference is `sequence_no`, and the retention claim is dropped outright.
 */
#[Layout('components.layouts.owner')]
class OwnerArchive extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        // Owner-scoped by the OwnerScope global scope (a non-linked user resolves to an empty scope →
        // no rows, never every owner's). Finalized history only — a draft is not archive.
        // DO24-1 — the ONE archived-set definition, shared with the archive ZIP so the bundle and this list
        // can never disagree about what "the full archive" means (§N.8f).
        $statements = OwnerStatement::query()->archived()->get();

        $groups = $this->yearGroups($statements);
        $lifetime = $this->netByCurrency($statements);

        return view('livewire.owner.archive', [
            'viewer' => $this->viewer(),
            'groups' => $groups,
            'lifetime' => $lifetime,
            'bars' => $this->bars($groups, count($lifetime) === 1),
            'summary' => $this->summary($statements, count($groups)),
            'haystacks' => $statements->map(fn (OwnerStatement $s): string => $this->haystack($s))->values()->all(),
        ]);
    }

    /**
     * Real counts + the real span: how many finalized statements, across how many distinct years, and
     * the month/year of the EARLIEST real period. The design's static "3 years · 34 documents · since
     * June 2023" is never ported — with no rows there is simply no span to show.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return array{count: int, years: int, since: string|null}
     */
    private function summary(Collection $statements, int $years): array
    {
        $earliest = $statements->last();   // ordered period_start DESC → the last row is the earliest

        return [
            'count' => $statements->count(),
            'years' => $years,
            'since' => $earliest instanceof OwnerStatement ? $earliest->period_start->translatedFormat(LocalDate::pattern('F Y')) : null,
        ];
    }

    /**
     * The finalized rows grouped by the year of their REAL `period_start`, most recent year first.
     * Each group keeps the parent period_start DESC order.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function yearGroups(Collection $statements): array
    {
        return $statements
            ->groupBy(fn (OwnerStatement $s): string => $s->period_start->format('Y'))
            ->map(fn (Collection $group, int|string $year): array => [
                'year' => (int) $year,
                'count' => $group->count(),
                'nets' => $this->netByCurrency($group),
                'part_label' => $this->partLabel($group),
                'rows' => $this->rows($group),
                'haystacks' => $group->map(fn (OwnerStatement $s): string => $this->haystack($s))->values()->all(),
            ])
            ->sortKeysDesc()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, OwnerStatement>  $group
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $group): array
    {
        return $group->map(fn (OwnerStatement $s): array => [
            'id' => $s->id,
            // The REAL statement number (per-owner, gap-free, stamped at finalize). Null → omitted.
            'number_label' => $s->sequence_no === null ? null : $this->numberLabel((int) $s->sequence_no),
            'period_label' => $this->periodLabel($s),
            'net_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'status_meta' => $this->statusMeta((string) $s->status),
            'has_unverified' => (bool) $s->has_unverified,
            'search' => $this->haystack($s),
        ])->values()->all();
    }

    /**
     * Σ `net_payable_minor` grouped by the PAYOUT currency (`owner_statements.currency`). Rows spanning
     * more than one currency are totalled per currency and rendered separately — a cross-currency sum
     * would be a silent FX conversion the backend never made (invariant #17).
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array{currency: string, minor: int, display: string, symbol: string}>
     */
    private function netByCurrency(Collection $statements): array
    {
        return $statements
            ->groupBy(fn (OwnerStatement $s): string => (string) $s->currency)
            ->map(function (Collection $group, string $currency): array {
                $minor = (int) $group->sum(fn (OwnerStatement $s): int => (int) $s->net_payable_minor);

                return [
                    'currency' => $currency,
                    'minor' => $minor,
                    'display' => $this->money($minor, $currency),
                    'symbol' => $this->currencySymbol($currency),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * Per-year net bars — DERIVED from the REAL per-year totals, chronological, scaled against the real
     * series max. Rendered ONLY when every finalized row shares one payout currency (see netByCurrency).
     *
     * @param  list<array<string, mixed>>  $groups
     * @return list<array{label: string, amount_display: string, pct: int}>
     */
    private function bars(array $groups, bool $singleCurrency): array
    {
        if (! $singleCurrency || $groups === []) {
            return [];
        }

        /** @var list<array{year: int, minor: int, display: string}> $points */
        $points = [];
        foreach ($groups as $group) {
            $nets = is_array($group['nets']) ? $group['nets'] : [];
            if ($nets === []) {
                return [];
            }
            $net = $nets[0];
            $points[] = [
                'year' => (int) $group['year'],
                'minor' => (int) $net['minor'],
                'display' => (string) $net['display'],
            ];
        }

        // A negative year (a net carry-forward) has no bar height; scale against the real positive max.
        $max = max(array_map(static fn (array $p): int => max(0, $p['minor']), $points));
        $max = $max > 0 ? $max : 1;

        return array_map(static fn (array $p): array => [
            'label' => (string) $p['year'],
            'amount_display' => $p['display'],
            'pct' => (int) round(max(0, $p['minor']) / $max * 100),
        ], array_reverse($points));
    }

    /**
     * The REAL months a year's statements cover, e.g. "Sep" or "Jan – Sep" — derived from the real
     * period rows. It never claims a year is complete.
     *
     * @param  Collection<int, OwnerStatement>  $group
     */
    private function partLabel(Collection $group): string
    {
        $latest = $group->first();       // group inherits period_start DESC
        $earliest = $group->last();

        if (! $latest instanceof OwnerStatement || ! $earliest instanceof OwnerStatement) {
            return '';
        }

        $from = $earliest->period_start->translatedFormat(LocalDate::pattern('M'));
        $to = $latest->period_end->translatedFormat(LocalDate::pattern('M'));

        return $from === $to ? $from : $from.' – '.$to;
    }

    /**
     * The real period as a label: a whole calendar month collapses to "September 2026", anything else
     * keeps its real boundaries ("1–14 September 2026" / "28 Sep – 4 Oct 2026"). Stay dates are the
     * stored `[period_start, period_end]` — no date is invented.
     */
    private function periodLabel(OwnerStatement $s): string
    {
        $start = $s->period_start;
        $end = $s->period_end;

        if ($start->isSameMonth($end)) {
            $wholeMonth = $start->day === 1 && $end->day === $end->daysInMonth;

            return $wholeMonth
                ? $start->translatedFormat(LocalDate::pattern('F Y'))
                : $start->format('j').'–'.$end->translatedFormat(LocalDate::pattern('j F Y'));
        }

        return $start->translatedFormat(LocalDate::pattern('j M')).' – '.$end->translatedFormat(LocalDate::pattern('j M Y'));
    }

    /**
     * The lowercase search haystack for a row — built from the REAL period / year / status / statement
     * number, so the client-side filter only ever narrows real rows.
     */
    private function haystack(OwnerStatement $s): string
    {
        $parts = [
            $this->periodLabel($s),
            $s->period_start->format('Y'),
            $this->statusMeta((string) $s->status)['label'],
        ];

        if ($s->sequence_no !== null) {
            $parts[] = $this->numberLabel((int) $s->sequence_no);
        }

        return mb_strtolower(implode(' ', $parts));
    }

    /** The statement number as the owner reads it ("No. 7") — the same words the row shows and the search matches. */
    private function numberLabel(int $sequenceNo): string
    {
        return __('php/livewire/owner/owner_archive.number_label', ['number' => $sequenceNo]);
    }
}
