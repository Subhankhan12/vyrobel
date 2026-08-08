<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Support\Payouts\RailLabel;
use App\Support\Pdf\OwnerRemittanceDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Documents (Wave 1 · Sub-gate 4) — the owner's document library, styled from the catalogued
 * "Owner Documents" design and wired to LIVE, owner-scoped rows. Presentation only: it reads the
 * CERTIFIED owner-accounting projection through the owner-scoped {@see OwnerStatement} / {@see Payout}
 * models and changes NO service, schema, or owner-accounting guarantee. Owner isolation
 * ({@see OwnerScope}) makes every read the owner's own rows; a non-linked user fails closed (empty).
 * No route-model-bound props — the read happens in render() under the active owner+tenant scope.
 *
 * AN AGGREGATION, NOT A STORE — the load-bearing rule of this screen
 * ------------------------------------------------------------------
 * There is no `documents` table behind owner PDFs: they are RENDERED ON DEMAND by three owner-scoped
 * controllers. So a row may exist here ONLY if its route genuinely produces a PDF today. Exactly three
 * do, and this screen lists nothing else:
 *   • `owner.statements.pdf`        — the statement PDF          (OwnerStatementPdfController)
 *   • `owner.statements.bookings`   — the booking-summary PDF    (OwnerBookingSummaryPdfController)
 *   • `owner.payouts.remittance`    — the remittance advice PDF  (OwnerRemittancePdfController)
 * Every listed row therefore downloads. A row that could not download would be the one lie this screen
 * is able to tell, so the row set is derived from the generators rather than from a wish list.
 *
 * WHICH ROWS EXIST (mirrors what the controllers actually accept — verified by reading all three)
 * ----------------------------------------------------------------------------------------------
 *  - None of the three controllers restricts by status: each resolves its id via `findOrFail` under the
 *    OwnerScope and renders. The DRAFT exclusion below is therefore this SCREEN's honesty rule, not a
 *    controller limit — and it matches the {@see OwnerArchive} sibling, which this screen must never
 *    contradict.
 *  - STATEMENT PDF + BOOKING SUMMARY: finalized/paid statements ONLY (status ∈ ('finalized','paid')).
 *    A DRAFT is NOT a document: it carries no `sequence_no` (so it has no real reference — the booking
 *    PDF's own filename would read `vyrobel-bookings--2026-09.pdf`), it is not locked, and its figures
 *    can still move on the next regenerate. Listing it would promise a fixed document that isn't one.
 *  - REMITTANCE ADVICE: EVERY real payout, whatever its status. Verified against
 *    {@see OwnerRemittanceDocument}, which renders for the whole enum — it branches on
 *    pending ("Net payable to owner"), paid ("Net paid to owner" + paid_on), failed (failure_reason) and
 *    reversed ("Net reversed"). So the advice is a real, meaningful document at every status, and each
 *    row is labelled with its REAL status rather than filtered out on a rule the controller lacks.
 *    The append-only payout lifecycle (a failed payout is superseded by a NEW row) means one period may
 *    legitimately carry two remittances; both are real and both are listed.
 *  - Every payout is grouped under its source statement's REAL period. `OwnerPayoutService::
 *    createFromStatement()` THROWS unless the statement is locked, so a payout's statement is always in
 *    the finalized set above. A payout whose statement cannot be resolved (FK-impossible) is SKIPPED
 *    rather than filed under an invented period.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — the three documents above and their working owner-scoped download links; the real
 *                reference (`sequence_no` → "No. 7", OMITTED when null, never faked; the payout's own
 *                `external_ref` only when the operator genuinely attached one); the real date
 *                (`finalized_at` for a statement; `paid_at`/`failed_at` for a payout — omitted when the
 *                field is unset, NEVER an estimate); the real amounts; the payout status badge and the
 *                rail label, both from the REAL enums only.
 *   • DERIVED  — the document COUNT (overall and per year) and the "since …" span, all computed from the
 *                real rows (the design's hard-coded "3 years · 34 documents · since June 2023" is not
 *                ported — the Archive already refused that and derives instead; this screen matches it).
 *                The year filter is built from the REAL years present and narrows the REAL rows
 *                client-side (the OwnerArchive idiom) — it invents no year and no row, and it is
 *                rendered only when there is genuinely more than one year to choose between.
 *   • CURRENCY — a statement's net payable is stated in its payout `currency`; the booking summary's
 *                gross is stated in `source_currency`, because that is the real currency of
 *                `gross_minor` (the PDF itself totals in `source_currency` for exactly this reason).
 *                Each amount is rendered in its OWN currency and no two are ever added — a cross-currency
 *                sum would be a silent FX the backend never made (invariant #17). This screen sums
 *                nothing: each group's net is ONE statement's real figure, and counts are counts.
 *   • REAL     — the annual tax summary PDF now exists (OwnerAnnualTaxDocument via the export-gated
 *                owner.tax.annual route), so each year group links its own year and a shortcut links the
 *                latest year. Only years with committed statements are linked (exactly what
 *                OwnerTaxService::annualSummary sums), so the link never 404s.
 *   • NO BACKEND — "Download the full archive (ZIP)" (there is no ZIP builder): a disabled "Soon" /
 *                `vy-soon-tag`, NEVER a fake download link.
 *   • NOT PORTED — the design's "OS-2025-09" / "RA-2025-09" / "BS-2025-09" reference formats, its
 *                "issued 1 Oct" and "18 bookings" metas, and its FABRICATED "net €98,710" figure are static
 *                fiction with no backing row: the real reference is `sequence_no`, the real date is
 *                `finalized_at`, and no booking count is claimed. The annual-tax card's "net X" IS shown, but
 *                as the REAL Σ net_source_minor for the year (the annual PDF's own basis — reconciling, #17),
 *                never the design's invented total. The design's year filter is inert (a fixed const) — it is
 *                rewired to the real years.
 *   • OMITTED  — the full-archive ZIP + CSV export (no backend / a standing deferral) are not rendered at
 *                all rather than as dead "Soon" controls (the wireframe carries neither).
 */
#[Layout('components.layouts.owner')]
class OwnerDocuments extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        // Owner-scoped by the OwnerScope global scope (a non-linked user resolves to an empty scope →
        // no rows, never every owner's). Finalized history only — a draft is not a document.
        $statements = OwnerStatement::query()
            ->whereIn('status', ['finalized', 'paid'])
            ->orderByDesc('period_start')
            ->get();

        // Every real payout — the remittance advice renders at every status (see the class docblock).
        $payouts = Payout::query()->orderByDesc('created_at')->get();

        /** @var Collection<string, Collection<int, Payout>> $byStatement */
        $byStatement = $payouts->groupBy(fn (Payout $p): string => (string) $p->owner_statement_id);

        $years = $this->yearGroups($statements, $byStatement);

        return view('livewire.owner.documents', [
            'viewer' => $this->viewer(),
            'years' => $years,
            'summary' => $this->summary($statements, $years),
            'filters' => array_map(static fn (array $y): int => $y['year'], $years),
        ]);
    }

    /**
     * The real count + the real span: how many documents actually generate, and the month/year of the
     * EARLIEST real period. The design's static "3 years · 34 documents · since June 2023" is never
     * ported — with no rows there is simply no count and no span to show.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @param  list<array<string, mixed>>  $years
     * @return array{count: int, since: string|null}
     */
    private function summary(Collection $statements, array $years): array
    {
        $earliest = $statements->last();   // ordered period_start DESC → the last row is the earliest

        return [
            'count' => array_sum(array_map(static fn (array $y): int => (int) $y['count'], $years)),
            'since' => $earliest instanceof OwnerStatement ? $earliest->period_start->format('F Y') : null,
        ];
    }

    /**
     * The documents grouped by the year of their statement's REAL `period_start`, most recent year
     * first, then by the real statement period within the year (inherited period_start DESC).
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @param  Collection<string, Collection<int, Payout>>  $byStatement
     * @return list<array<string, mixed>>
     */
    private function yearGroups(Collection $statements, Collection $byStatement): array
    {
        return $statements
            ->groupBy(fn (OwnerStatement $s): string => $s->period_start->format('Y'))
            ->map(function (Collection $group, int|string $year) use ($byStatement): array {
                $periods = $group
                    ->map(fn (OwnerStatement $s): array => $this->periodGroup($s, $byStatement))
                    ->values()
                    ->all();

                // The year's annual-tax net — Σ net_source_minor in the EARLIEST statement's source currency,
                // the SAME basis as OwnerTaxService::annualSummary (which the annual PDF renders), so the card's
                // figure reconciles to that PDF to the cent (#17). Nothing summed across currencies beyond what
                // the existing annual summary already does.
                $earliest = $group->sortBy(fn (OwnerStatement $s): string => $s->period_start->toDateString())->first();
                $taxNetMinor = (int) $group->sum(fn (OwnerStatement $s): int => (int) $s->net_source_minor);

                // ⚠️ WAVE 2 · #5 — the card used to say "All 12 months" unconditionally. A year with three
                // finalized statements is not twelve months, and the annual PDF beside it prints the REAL
                // span, so the card and its own document disagreed. Report what is actually in the year.
                $latest = $group->sortByDesc(fn (OwnerStatement $s): string => $s->period_start->toDateString())->first();
                $statementCount = $group->count();

                return [
                    'year' => (int) $year,
                    // DERIVED: how many documents this year genuinely generates.
                    'count' => array_sum(array_map(static fn (array $p): int => count($p['docs']), $periods)),
                    'statement_count' => $statementCount,
                    'span_label' => $statementCount === 1
                        ? $earliest?->period_start->format('F') ?? ''
                        : ($earliest?->period_start->format('M') ?? '').'–'.($latest?->period_start->format('M') ?? ''),
                    'periods' => $periods,
                    'tax_net_display' => $this->money($taxNetMinor, (string) $earliest?->source_currency),
                ];
            })
            ->sortKeysDesc()
            ->values()
            ->all();
    }

    /**
     * ONE finalized statement's period and every document that genuinely generates for it: its statement
     * PDF, its booking summary, and a remittance advice per REAL payout raised against it.
     *
     * @param  Collection<string, Collection<int, Payout>>  $byStatement
     * @return array<string, mixed>
     */
    private function periodGroup(OwnerStatement $s, Collection $byStatement): array
    {
        // The REAL statement number (per-owner, gap-free, stamped AT FINALIZE). Null → omitted, never faked.
        $number = $s->sequence_no === null ? null : 'No. '.$s->sequence_no;
        // The REAL issue date. `finalized_at` is stamped at finalize; guarded, so an unset field omits
        // the date rather than inventing one.
        $issued = $s->finalized_at === null ? null : Carbon::parse($s->finalized_at)->format('j M Y');

        $docs = [
            [
                'kind' => 'STMT',
                'name' => 'Owner statement',
                'ref_label' => $number,
                'date_label' => $issued === null ? null : 'issued '.$issued,
                // The statement's headline figure, in its PAYOUT currency — the same figure the PDF
                // leads with, through the same shared MoneyFormatter, so the two reconcile to the cent.
                'amount_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
                'amount_note' => 'net payable',
                'status_meta' => null,
                'rail_label' => null,
                'href' => route('owner.statements.pdf', $s->id),
                'title' => 'Download this statement as a PDF',
            ],
            [
                'kind' => 'BKG',
                'name' => 'Booking summary',
                'ref_label' => $number,
                'date_label' => $issued === null ? null : 'issued '.$issued,
                // `gross_minor` is denominated in `source_currency`, NOT in the payout `currency` — the
                // booking-summary PDF totals in `source_currency` for exactly that reason. Rendering it
                // against the payout currency here would state a silent FX the backend never made
                // (invariant #17) and would contradict the very document this row downloads.
                'amount_display' => $this->money((int) $s->gross_minor, (string) $s->source_currency),
                'amount_note' => 'gross',
                'status_meta' => null,
                'rail_label' => null,
                'href' => route('owner.statements.bookings', $s->id),
                'title' => 'Download the bookings behind this statement as a PDF',
            ],
        ];

        /** @var Collection<int, Payout>|null $raised */
        $raised = $byStatement->get((string) $s->id);

        foreach ($raised ?? collect() as $payout) {
            $docs[] = $this->remittanceDoc($payout);
        }

        return [
            'period_label' => $this->periodLabel($s),
            'number_label' => $number,
            // ONE statement's own real net — nothing is summed here, so no currency can be mixed.
            'net_display' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'docs' => $docs,
        ];
    }

    /**
     * The remittance advice for ONE real payout. Mirrors {@see OwnerRemittanceDocument}
     * deliberately — the amount wording, the rail label and the date rule are the document's own, so the
     * row and the PDF it downloads can never disagree about what happened.
     *
     * @return array<string, mixed>
     */
    private function remittanceDoc(Payout $p): array
    {
        $status = (string) $p->status;
        // A real settlement/failure date, or nothing. A pending payout has NO date and never gets an
        // estimate — the status badge carries the truth instead.
        $when = $p->paid_at ?? $p->failed_at;

        return [
            'kind' => 'RA',
            'name' => 'Remittance advice',
            // The operator's own bank reference, ONLY when they attached one. Never generated here — the
            // design's "RA-2025-09" format does not exist.
            'ref_label' => (is_string($p->external_ref) && $p->external_ref !== '') ? $p->external_ref : null,
            'date_label' => $when === null ? null : Carbon::parse($when)->format('j M Y'),
            'amount_display' => $this->money((int) $p->amount_minor, (string) $p->currency),
            // DERIVED from the real status, mirroring the document's own amount_label: a pending/failed
            // payout has NOT been paid, so neither the row nor the PDF claims it was.
            'amount_note' => match ($status) {
                'paid' => 'net paid',
                'reversed' => 'net reversed',
                default => 'net payable',
            },
            'status_meta' => $this->payoutStatusMeta($status),
            // The SHARED rail map — the same one the owner Payouts screen and the remittance PDF render.
            'rail_label' => RailLabel::for((string) $p->rail),
            'href' => route('owner.payouts.remittance', $p->id),
            'title' => 'Download the remittance advice for this payout as a PDF',
        ];
    }

    /**
     * The real period as a label: a whole calendar month collapses to "September 2026", anything else
     * keeps its real boundaries ("1–14 September 2026" / "28 Sep – 4 Oct 2026"). Identical to the
     * {@see OwnerArchive} sibling's rule, so the two screens name the same period the same way.
     */
    private function periodLabel(OwnerStatement $s): string
    {
        $start = $s->period_start;
        $end = $s->period_end;

        if ($start->isSameMonth($end)) {
            $wholeMonth = $start->day === 1 && $end->day === $end->daysInMonth;

            return $wholeMonth
                ? $start->format('F Y')
                : $start->format('j').'–'.$end->format('j F Y');
        }

        return $start->format('j M').' – '.$end->format('j M Y');
    }
}
