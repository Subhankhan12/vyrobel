{{--
    Owner Archive (Wave 1 · Sub-gate 1) — the owner's FINALIZED statement history, styled from the
    catalogued "Owner Archive" design and wired to LIVE owner-scoped rows. Shell from <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerArchive):
      • REAL      — the finalized/paid statement rows: the real per-owner gap-free `sequence_no`
                    ("No. 7"; omitted when null, never faked), the real period, net payable, the status
                    badge from the REAL enum ONLY (paid → "Paid", finalized → "Payout pending"), the
                    real `has_unverified` flag, the statement link, and TWO REAL owner-scoped downloads —
                    the statement PDF and the booking-summary PDF ("Bookings", the real attributed
                    room_revenue behind that period's gross). Both reconcile to the cent, so real
                    downloads, not stubs.
                    A DRAFT is not archive and is excluded (it has no number and can still move).
      • DERIVED   — "Net earned · lifetime" = Σ net payable over those real rows, totalled PER PAYOUT
                    CURRENCY and rendered per currency — NEVER a cross-currency sum (invariant #17).
                    Year grouping, per-year net + count, the covered-month span, the statement count and
                    the "since …" span all come from the real rows. The per-year bars render ONLY in the
                    single-currency case. Search narrows the REAL rows client-side; it invents nothing.
      • REAL      — the per-year annual tax summary PDF (OwnerAnnualTaxDocument via owner.tax.annual),
                    export-gated, the same document the Tax Center offers. Linked only for years with
                    committed statements (exactly what annualSummary() sums), so it never 404s.
      • REAL (DO24-1) — the full-archive ZIP: one ZIP of exactly the statements listed here, each the same
        watermarked PDF its row downloads. CAP_EXPORTS-gated; owner+org scoped by the model's global scopes.
      • NOT PORTED— the design's static "3 years · 34 documents", "since June 2023", its 7-year
                    retention promise and its "OS-2025-09" reference format are all fiction with no
                    backing row or policy. Counts + span are derived from real rows, the real reference
                    is `sequence_no`, and the retention claim is dropped.
--}}
<x-owner.shell active="statements" :title="__('livewire/owner/archive.title')" :viewer="$viewer">
    <div x-data="{ q: '' }">
        {{-- Sub-header — REAL counts + REAL span (the design's "3 years · 34 documents" is not ported) --}}
        <div class="vy-line-sub" style="font-size:13px;margin-bottom:20px;color:var(--vy-on-grad)">
            @if ($summary['count'] > 0)
                {{ __('livewire/owner/archive.history_summary', ['years' => trans_choice('livewire/owner/archive.years', $summary['years'], ['count' => $summary['years']]), 'statements' => trans_choice('livewire/owner/archive.statements', $summary['count'], ['count' => $summary['count']])]) }}
            @else
                {{ __('livewire/owner/archive.archived_on_finalize') }}
            @endif
        </div>

        {{-- Lifetime earnings — DERIVED: Σ net payable of the REAL finalized rows, per payout currency --}}
        <section class="vy-card vy-card--accent vy-fade" style="animation-delay:.02s">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
                <div>
                    <span class="vy-eyebrow">{{ __('livewire/owner/archive.net_earned_lifetime') }}</span>
                    @forelse ($lifetime as $total)
                        @if (count($lifetime) === 1)
                            <div class="vy-hero-figure" style="font-size:44px" data-countup="{{ $total['minor'] }}" data-symbol="{{ $total['symbol'] }}">{{ $total['display'] }}</div>
                        @else
                            <div class="vy-hero-figure" style="font-size:32px">{{ $total['display'] }} <span class="vy-line-sub" style="font-size:12px">{{ $total['currency'] }}</span></div>
                        @endif
                    @empty
                        <div class="vy-hero-figure" style="font-size:44px;color:var(--vy-faint)">—</div>
                    @endforelse
                </div>
                @if ($summary['since'])
                    <span class="vy-eyebrow">{{ __('livewire/owner/archive.since', ['date' => $summary['since']]) }}</span>
                @endif
            </div>

            @if (count($lifetime) > 1)
                <div class="vy-fx-note">{{ __('livewire/owner/archive.per_currency_note') }}</div>
            @elseif ($lifetime === [])
                <div class="vy-hero-sub">{{ __('livewire/owner/archive.lifetime_empty') }}</div>
            @endif

            {{-- Per-year bars — DERIVED from the real per-year totals; single-currency only --}}
            @if ($bars !== [])
                <div class="vy-chart" style="height:120px;margin-top:18px">
                    @foreach ($bars as $i => $bar)
                        <div class="vy-chart-col" title="{{ $bar['label'] }} · {{ $bar['amount_display'] }}">
                            <div class="vy-bar {{ $i === count($bars) - 1 ? 'is-last' : '' }}" style="height:{{ max(4, $bar['pct']) }}%"></div>
                            <div class="vy-bar-label">{{ $bar['label'] }}</div>
                            <div class="vy-bar-label" style="font-weight:600;color:var(--vy-ink)">{{ $bar['amount_display'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Search — client-side over the REAL rows only (same idiom as the payouts screen) --}}
        @if ($summary['count'] > 0)
            <div style="display:flex;align-items:center;gap:8px;margin-top:22px">
                <input type="search" x-model="q" placeholder="{{ __('livewire/owner/archive.search_placeholder') }}" aria-label="{{ __('livewire/owner/archive.search_label') }}"
                       style="flex:1;padding:10px 14px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                <button type="button" class="vy-btn vy-btn--ghost" x-show="q !== ''" @click="q = ''" style="display:none">{{ __('common.clear') }}</button>
            </div>

            {{-- No results — over the REAL rows; the design's "OS-2025-09" reference format is fiction, the real reference is the statement number --}}
            <div class="vy-empty" x-show="q !== '' && !@js($haystacks).some(h => h.includes(q.toLowerCase()))" style="display:none">
                {!! __('livewire/owner/archive.no_match', ['query' => '<span x-text="q"></span>']) !!}
                <div class="vy-line-sub" style="margin-top:6px">{{ __('livewire/owner/archive.no_match_hint') }}</div>
            </div>
        @endif

        {{-- ── Archive, grouped by the year of the REAL period_start ── --}}
        @forelse ($groups as $group)
            <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.06s"
                     x-show="q === '' || @js($group['haystacks']).some(h => h.includes(q.toLowerCase()))">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:8px">
                    <div>
                        <h2 class="vy-card-title">
                            {{ $group['year'] }}
                            @if ($group['part_label'])<span class="vy-line-sub" style="margin-left:8px">{{ $group['part_label'] }}</span>@endif
                        </h2>
                        <div class="vy-line-sub" style="margin-top:4px">
                            {{ __('livewire/owner/archive.group_summary', ['statements' => trans_choice('livewire/owner/archive.statements', $group['count'], ['count' => $group['count']]), 'nets' => implode(' · ', array_column($group['nets'], 'display'))]) }}
                            @if (count($group['nets']) > 1)<span class="vy-line-sub">{{ __('livewire/owner/archive.per_payout_currency') }}</span>@endif
                        </div>
                    </div>
                    {{-- Annual tax summary — a REAL owner-scoped PDF for this year (OwnerAnnualTaxDocument
                         via owner.tax.annual), the same export the Tax Center offers. Export-gated; this
                         year is in the archive because it has committed statements — exactly what
                         annualSummary() sums — so the link never 404s. --}}
                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    <a href="{{ route('owner.tax.annual', $group['year']) }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="{{ __('livewire/owner/archive.tax_summary_title', ['year' => $group['year']]) }}">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        {{ __('livewire/owner/archive.tax_summary') }}
                    </a>
                    @endownercan
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>{{ __('livewire/owner/archive.col_statement') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th class="vy-num">{{ __('livewire/owner/archive.col_net_payable') }}</th>
                                <th class="vy-num" aria-label="{{ __('livewire/owner/archive.col_links') }}"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['rows'] as $row)
                                <tr x-show="q === '' || @js($row['search']).includes(q.toLowerCase())">
                                    <td>
                                        <a class="vy-row-link" href="{{ route('owner.statements.show', $row['id']) }}">
                                            <div class="vy-row-month">{{ $row['period_label'] }}</div>
                                            {{-- The REAL statement number (stamped at finalize); omitted when there is none --}}
                                            @if ($row['number_label'])<div class="vy-row-sub">{{ $row['number_label'] }}</div>@endif
                                        </a>
                                    </td>
                                    <td>
                                        <span class="vy-status vy-status--{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                                        @if ($row['has_unverified'])
                                            <span class="vy-provisional" style="margin-left:6px" title="{{ __('livewire/owner/archive.unverified_title') }}">{{ __('livewire/owner/archive.unverified') }}</span>
                                        @endif
                                    </td>
                                    <td class="vy-num vy-mono vy-net">{{ $row['net_display'] }}</td>
                                    <td class="vy-num" style="white-space:nowrap">
                                        <a href="{{ route('owner.statements.show', $row['id']) }}" style="color:var(--vy-accent-text);font-size:12.5px;font-weight:600">{{ __('common.view') }}</a>
                                        {{-- REAL downloads — owner-scoped PDFs. 🔒 SG2 — a view-only sub-user is
                                             "no exports": both are hidden (and the routes are export-gated). --}}
                                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                                        <a href="{{ route('owner.statements.pdf', $row['id']) }}" class="vy-btn vy-btn--ghost" style="margin-left:10px;padding:5px 10px;font-size:12px" title="{{ __('livewire/owner/archive.pdf_title') }}">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                            {{ __('livewire/owner/archive.pdf') }}
                                        </a>
                                        <a href="{{ route('owner.statements.bookings', $row['id']) }}" class="vy-btn vy-btn--ghost" style="margin-left:6px;padding:5px 10px;font-size:12px" title="{{ __('livewire/owner/archive.bookings_title') }}">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                            {{ __('livewire/owner/archive.bookings') }}
                                        </a>
                                        @endownercan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.06s">
                <div class="vy-empty">{{ __('livewire/owner/archive.empty') }}</div>
            </section>
        @endforelse

        @if (session('archive_zip_notice'))
            <div class="vy-flash" style="margin-top:18px">{{ session('archive_zip_notice') }}</div>
        @endif

        {{-- DO24-1 — the full-archive ZIP is REAL now: one ZIP of exactly the statements listed above (the
             same shared archived-set), each rendered as the same PDF its own row downloads, watermarked to
             whoever took the bundle. ⚠️ CAP_EXPORTS-gated like every other export here — bundling must not
             become a way around the gate on downloading one. Shown only when there is something to bundle. --}}
        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
            @if ($summary['count'] > 0)
                <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.1s;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
                    <div>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px">{{ __('livewire/owner/archive.zip_heading') }}</div>
                        <div class="vy-line-sub">{{ trans_choice('livewire/owner/archive.zip_sub', $summary['count'], ['count' => $summary['count']]) }}</div>
                    </div>
                    <a href="{{ route('owner.archive.zip') }}" class="vy-btn vy-btn--ghost">{{ __('livewire/owner/archive.download_zip') }}</a>
                </section>
            @endif
        @endownercan
    </div>
</x-owner.shell>