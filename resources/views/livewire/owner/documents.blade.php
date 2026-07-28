{{--
    Owner Documents (exact-match pass · invariant #60) — the owner's document library, rebuilt to the decoded
    "Owner Documents" wireframe and wired to LIVE, owner-scoped rows. Shell from <x-owner.shell active="documents">.

    AN AGGREGATION, NOT A STORE: there is no `documents` table behind owner PDFs — they are rendered on demand.
    A row exists here ONLY if its route genuinely produces a PDF today, so EVERY row below downloads. See
    App\Livewire\Owner\OwnerDocuments for the full triage.

    HONESTY MAP:
      • REAL      — the three generatable documents (statement PDF · booking summary · remittance advice) + the
                    annual tax summary (owner.tax.annual → OwnerAnnualTaxDocument), all owner-scoped (#28),
                    CAP_EXPORTS-gated; real reference (`sequence_no`) / dates / amounts / status + rail from the
                    real enums; a draft is not a document (excluded). The annual card's "net X" is the REAL Σ
                    net_source_minor for the year (the annual PDF's basis — reconciling, #17).
      • DERIVED   — the document count + "since …" span + the year filter, all from the real rows.
      • REFUSED   — the design's fabricated multi-year history + invented references + the "net €98,710" figure
                    (#39); the full-archive ZIP + CSV (no backend) are dropped, not stubbed (the wireframe has
                    neither).
    RAW-BLADE RULE: every `@` follows a space, newline or tag.
--}}
<x-owner.shell active="documents" title="Documents" :subtitle="'Statements, remittances & tax summaries'" :viewer="$viewer">
    <style>
        .vy-doc-month { margin-top:18px; }
        .vy-doc-mhead { display:flex; justify-content:space-between; align-items:baseline; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        .vy-doc-annual { background:var(--vy-grad-dark); color:#fff; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
        .vy-doc-annual .vy-btn { background:rgba(255,255,255,.14); border-color:rgba(255,255,255,.32); color:#fff; }
        .vy-doc-annual .vy-btn:hover { background:rgba(255,255,255,.24); }
    </style>

    <div x-data="{ year: 'all' }">
        {{-- Sub-header — REAL count + REAL span + the archive link (the design's "3 years · 34 documents" refused) --}}
        <div class="vy-line-sub" style="font-size:13px;margin:-6px 0 20px">
            @if ($summary['count'] > 0)
                {{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('document', $summary['count']) }}
                @if ($summary['since']) · since {{ $summary['since'] }} @endif
                ·
                <a href="{{ route('owner.archive') }}" style="color:var(--vy-accent);font-weight:600">full archive ›</a>
            @else
                Every document your statements and payouts produce is collected here.
            @endif
        </div>

        {{-- Year filter — DERIVED from the REAL years present; narrows the REAL cards client-side --}}
        @if (count($filters) > 1)
            <div class="vy-quick" style="margin-bottom:6px">
                <button type="button" class="vy-pill" :class="year === 'all' ? 'is-active' : ''" @click="year = 'all'">All years</button>
                @foreach ($filters as $y)
                    <button type="button" class="vy-pill" :class="year === {{ $y }} ? 'is-active' : ''" @click="year = {{ $y }}">{{ $y }}</button>
                @endforeach
            </div>
        @endif

        {{-- ── One frosted card per REAL month (statement period), most recent first ── --}}
        @forelse ($years as $yearGroup)
            @foreach ($yearGroup['periods'] as $period)
                <section class="vy-card vy-fade vy-doc-month" style="animation-delay:.04s" x-show="year === 'all' || year === {{ $yearGroup['year'] }}">
                    <div class="vy-doc-mhead">
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:15px">
                            {{ $period['period_label'] }}
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
                                        @if ($doc['ref_label']) {{ $doc['ref_label'] }} · @endif
                                        @if ($doc['date_label']) {{ $doc['date_label'] }} · @endif
                                        @if ($doc['rail_label']) {{ $doc['rail_label'] }} · @endif
                                        {{ $doc['amount_note'] }} {{ $doc['amount_display'] }}
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;white-space:nowrap">
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
                </section>
            @endforeach
        @empty
            <section class="vy-card vy-fade" style="margin-top:18px;animation-delay:.04s">
                <div class="vy-empty">
                    No documents yet.
                    <div class="vy-line-sub" style="margin-top:6px">Your statement, booking summary and remittance advice appear here as soon as a statement is finalized.</div>
                </div>
            </section>
        @endforelse

        {{-- ── Annual tax summary — a REAL owner-scoped year-end PDF per year, with the REAL net (reconciles
             to the PDF, #17). Export-gated; only years with committed statements appear, so the link never 404s. --}}
        @foreach ($years as $yearGroup)
            <section class="vy-card vy-fade vy-doc-annual" style="margin-top:18px;animation-delay:.08s" x-show="year === 'all' || year === {{ $yearGroup['year'] }}">
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="flex:none;width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,.14);display:grid;place-items:center"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--vy-peach)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg></span>
                    <div>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px;color:#fff">{{ $yearGroup['year'] }} annual tax summary</div>
                        <div style="font-size:12.5px;color:rgba(255,255,255,.72);margin-top:2px">All 12 months · net {{ $yearGroup['tax_net_display'] }} · one PDF for your accountant</div>
                    </div>
                </div>
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    <a href="{{ route('owner.tax.annual', $yearGroup['year']) }}" class="vy-btn vy-btn--ghost" style="padding:6px 13px;font-size:12.5px" title="Download the {{ $yearGroup['year'] }} annual tax summary as a PDF">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        PDF
                    </a>
                @endownercan
            </section>
        @endforeach
    </div>
</x-owner.shell>
