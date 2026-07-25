{{--
    Owner Documents (Wave 1 · Sub-gate 4) — the owner's document library, styled from the catalogued
    "Owner Documents" design and wired to LIVE owner-scoped rows. Shell from <x-owner.shell>.

    AN AGGREGATION, NOT A STORE: there is no `documents` table behind owner PDFs — they are rendered on
    demand. A row exists here ONLY if its route genuinely produces a PDF today, so EVERY row below
    downloads. See App\Livewire\Owner\OwnerDocuments for the full triage.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11):
      • REAL      — the three generatable documents (statement PDF · booking summary · remittance advice)
                    and their working owner-scoped links; the real reference (`sequence_no` → "No. 7",
                    omitted when null, never faked; a payout's `external_ref` only when genuinely set);
                    the real date (`finalized_at` / `paid_at` / `failed_at` — omitted when unset, NEVER
                    an estimate); the real amounts; the payout status badge + rail from the REAL enums.
                    A DRAFT is not a document (no number, not locked, figures can still move) — excluded,
                    exactly as the Archive sibling excludes it.
      • DERIVED   — the document count, the year count and the "since …" span, all from the real rows.
                    The year filter is built from the REAL years and narrows the REAL rows client-side.
      • CURRENCY  — net payable is in the payout `currency`; the booking summary's gross is in
                    `source_currency` (the real currency of `gross_minor`, and what that PDF totals in).
                    Each amount stands in its own currency; nothing is summed, so no silent FX
                    (invariant #17).
      • REAL      — the annual tax summary PDF (OwnerAnnualTaxDocument via owner.tax.annual): a per-year
                    link on each year group + a shortcut to the latest year, export-gated, the same
                    document the Tax Center offers. Only linked for years that have committed statements.
      • NO BACKEND— the full-archive ZIP (no ZIP builder): a disabled "Soon", never a fake download link.
      • NOT PORTED— the design's "OS-2025-09"/"RA-2025-09"/"BS-2025-09" reference formats, its
                    "issued 1 Oct" and "18 bookings" metas, its "net €98,710" tax figure and its inert
                    year filter (a fixed const) are all fiction — replaced by the real number, the real
                    date, no invented count, and a filter over the real years.
      • OMITTED   — CSV export (a standing deferral; this screen is a PDF library) is not rendered.
--}}
<x-owner.shell active="documents" title="Documents" :viewer="$viewer">
    <div x-data="{ year: 'all' }">
        {{-- Sub-header — REAL counts + REAL span (the design's "3 years · 34 documents" is not ported) --}}
        <div class="vy-line-sub" style="font-size:13px;margin-bottom:20px">
            @if ($summary['count'] > 0)
                Statements, booking summaries &amp; remittance advices ·
                {{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('document', $summary['count']) }}
                @if ($summary['since']) · since {{ $summary['since'] }} @endif
                ·
                <a href="{{ route('owner.archive') }}" style="color:var(--vy-accent);font-weight:600">full archive ›</a>
            @else
                Every document your statements and payouts produce is collected here.
            @endif
        </div>

        {{-- Year filter — DERIVED from the REAL years present; narrows the REAL rows client-side.
             (The design's filter is inert: its groups are a fixed const. Never a dead control.) --}}
        @if (count($filters) > 1)
            <div class="vy-quick" style="margin-bottom:20px">
                <button type="button" class="vy-pill" :class="year === 'all' ? 'is-active' : ''" @click="year = 'all'">All years</button>
                @foreach ($filters as $y)
                    <button type="button" class="vy-pill" :class="year === {{ $y }} ? 'is-active' : ''" @click="year = {{ $y }}">{{ $y }}</button>
                @endforeach
            </div>
        @endif

        {{-- ── The library, grouped by the year of the REAL period_start, then the real period ── --}}
        @forelse ($years as $yearGroup)
            <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.04s"
                     x-show="year === 'all' || year === {{ $yearGroup['year'] }}">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:6px">
                    <div>
                        <h2 class="vy-card-title">{{ $yearGroup['year'] }}</h2>
                        <div class="vy-line-sub" style="margin-top:4px">
                            {{-- DERIVED: how many documents this year genuinely generates --}}
                            {{ $yearGroup['count'] }} {{ \Illuminate\Support\Str::plural('document', $yearGroup['count']) }}
                        </div>
                    </div>
                    {{-- Annual tax summary — a REAL owner-scoped PDF for this year (OwnerTaxController::
                         downloadAnnual → OwnerAnnualTaxDocument), the same export the Tax Center offers.
                         Export-gated (CAP_EXPORTS); the year is here only because it has committed
                         statements, exactly what annualSummary() sums, so this link never 404s. --}}
                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    <a href="{{ route('owner.tax.annual', $yearGroup['year']) }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="Download the {{ $yearGroup['year'] }} annual tax summary as a PDF">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        Tax summary
                    </a>
                    @endownercan
                </div>

                @foreach ($yearGroup['periods'] as $period)
                    <div style="margin-top:18px">
                        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;padding-bottom:6px;border-bottom:1px solid var(--vy-hairline)">
                            <div style="font-family:var(--vy-display);font-weight:600;font-size:14px">
                                {{ $period['period_label'] }}
                                {{-- The REAL statement number (stamped at finalize); omitted when there is none --}}
                                @if ($period['number_label'])
                                    <span class="vy-line-sub" style="margin-left:8px;font-weight:400">{{ $period['number_label'] }}</span>
                                @endif
                            </div>
                            <div class="vy-line-sub">Net <span class="vy-mono" style="color:var(--vy-accent);font-weight:600">{{ $period['net_display'] }}</span></div>
                        </div>

                        @foreach ($period['docs'] as $doc)
                            {{-- Every row is a REAL, owner-scoped download — a listed document always generates --}}
                            <div class="vy-list-row">
                                <div style="display:flex;align-items:center;gap:12px;min-width:0">
                                    <span class="vy-rail" style="font-family:var(--vy-display);font-weight:700;letter-spacing:.06em;min-width:46px;justify-content:center">{{ $doc['kind'] }}</span>
                                    <div style="min-width:0">
                                        <div class="vy-row-month">{{ $doc['name'] }}</div>
                                        <div class="vy-row-sub">
                                            {{-- REAL reference · REAL date · REAL rail — each omitted when the field is unset --}}
                                            @if ($doc['ref_label']) {{ $doc['ref_label'] }} · @endif
                                            @if ($doc['date_label']) {{ $doc['date_label'] }} · @endif
                                            @if ($doc['rail_label']) {{ $doc['rail_label'] }} · @endif
                                            {{ $doc['amount_note'] }} {{ $doc['amount_display'] }}
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;white-space:nowrap">
                                    {{-- The payout's REAL status — a remittance is a real document at every state --}}
                                    @if ($doc['status_meta'])
                                        <span class="vy-status vy-status--{{ $doc['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $doc['status_meta']['label'] }}</span>
                                    @endif
                                    <a href="{{ $doc['href'] }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="{{ $doc['title'] }}">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                        PDF
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </section>
        @empty
            <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.04s">
                <div class="vy-empty">
                    No documents yet.
                    <div class="vy-line-sub" style="margin-top:6px">Your statement, booking summary and remittance advice appear here as soon as a statement is finalized.</div>
                </div>
            </section>
        @endforelse

        {{-- Annual tax summary — a REAL owner-scoped year-end PDF (OwnerAnnualTaxDocument): a one-click
             shortcut to the MOST RECENT year's summary; the per-year links above cover every other year.
             No figure is claimed here (the design's "net €98,710" is fiction). Export-gated. --}}
        @php $latestTaxYear = $years[0]['year'] ?? null; @endphp
        <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.08s;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
            <div>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px">Annual tax summary</div>
                <div class="vy-line-sub">A single year-end PDF for your accountant{{ $latestTaxYear ? ', ready for '.$latestTaxYear : '' }}.</div>
            </div>
            @if ($latestTaxYear)
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                <a href="{{ route('owner.tax.annual', $latestTaxYear) }}" class="vy-btn vy-btn--ghost" title="Download the {{ $latestTaxYear }} annual tax summary as a PDF">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                    {{ $latestTaxYear }} PDF
                </a>
                @endownercan
            @else
                <span class="vy-line-sub" title="Appears once your first statement is finalized">Once finalized</span>
            @endif
        </section>

        {{-- Full-archive ZIP — NO BACKEND (no ZIP builder): disabled, never a fake download --}}
        <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.1s;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
            <div>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px">Download everything</div>
                <div class="vy-line-sub">One ZIP of every document above. Each one already downloads on its own.</div>
            </div>
            <button type="button" class="vy-btn vy-btn--ghost is-soon" style="opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">Download ZIP</button>
        </section>
    </div>
</x-owner.shell>
