{{--
    Owner Reports (exact-match pass · invariant #61) — the owner's REAL analytics for their OWN units, rebuilt to
    the decoded "Owner Reports" wireframe. Shell from <x-owner.shell active="reports">. All figures reconcile to
    the owner ledger/statements (#17); owner-scoped (#28); nothing fabricated (#39).

    HONESTY MAP (see App\Livewire\Owner\OwnerReports):
      • REAL — KPIs, net-by-month, per-unit (ADR/RevPAR/nights/occ/net/share), by-channel (real source enum),
        gross-to-net (reconciles #17, REAL agreement commission rate — never 15%), payout summary, real Documents
        + exports (CAP_EXPORTS-gated), Notifications (the self-scoped feed).
      • HONEST-DEFER — "Email to me" (no report Mailable) = a "Soon"; the Tax & VAT summary is NOT computed by
        Vyrobel (no VAT model) → an honest deferred card, no fabricated VAT figure / "Mark filed" (#39).
    RAW-BLADE RULE: every `@` follows a space, newline or tag.
--}}
<x-owner.shell active="reports" title="Reports" :subtitle="'Your real performance — earnings, occupancy and where it comes from.'" :viewer="$viewer">
    <style>
        .vy-rep-top { display:flex; align-items:center; justify-content:space-between; gap:14px 20px; flex-wrap:wrap; margin-bottom:18px; }
        .vy-rep-pills { display:flex; gap:8px; flex-wrap:wrap; }
        .vy-rep-pill { padding:7px 15px; border-radius:var(--vy-r-pill); border:1px solid rgba(120,70,40,.16); background:rgba(255,255,255,.6); color:var(--vy-ink); font-family:var(--vy-sans); font-size:13px; font-weight:500; text-decoration:none; }
        .vy-rep-pill.is-active { background:var(--vy-grad); color:#fff; border-color:transparent; box-shadow:var(--vy-shadow-accent); }
        .vy-rep-hero { background:var(--vy-grad-dark); color:#fff; }
        .vy-rep-gtn-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-top:1px solid rgba(120,70,40,.1); font-size:13px; }
        .vy-rep-gtn-row:first-of-type { border-top:none; }
        .vy-rep-gtn-net { border-top:1.5px solid rgba(120,70,40,.18); margin-top:4px; padding-top:11px; font-weight:600; }
        .vy-rep-vat { background:rgba(255,252,248,.5); border:1px dashed rgba(120,70,40,.28); }
        .vy-rep-doc { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:9px 0; border-top:1px solid rgba(120,70,40,.1); }
        .vy-rep-doc:first-of-type { border-top:none; }
        .vy-rep-note { display:flex; align-items:flex-start; gap:9px; padding:10px 0; border-top:1px solid rgba(120,70,40,.1); font-size:12.5px; }
        .vy-rep-note:first-of-type { border-top:none; }
    </style>

    @if ($unitsCount === 0)
        <div class="vy-card vy-fade"><div class="vy-empty">No units linked to your account yet.</div></div>
    @else
    {{-- ── Top row: reporting period + exports ── --}}
    <div class="vy-rep-top">
        <div class="vy-rep-pills">
            @foreach ($ranges as $r)
                <a class="vy-rep-pill {{ $r['active'] ? 'is-active' : '' }}" href="{{ route('owner.reports', ['range' => $r['key']]) }}">{{ $r['label'] }}</a>
            @endforeach
        </div>
        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
            <div style="position:relative" x-data="{ open: false }">
                <button type="button" class="vy-btn vy-btn--ghost" @click="open = !open" style="gap:7px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                    Export
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" style="position:absolute;right:0;top:calc(100% + 6px);z-index:20;background:#fff;border:1px solid var(--vy-glass-border);border-radius:var(--vy-r-md);box-shadow:0 14px 40px rgba(150,90,50,.2);padding:6px;min-width:230px">
                    <a href="{{ route('owner.statements.history.csv') }}" style="display:block;padding:9px 11px;border-radius:8px;text-decoration:none;color:var(--vy-ink);font-size:13px">Statement history · CSV</a>
                    <a href="{{ route('owner.tax.annual', (int) now()->year) }}" style="display:block;padding:9px 11px;border-radius:8px;text-decoration:none;color:var(--vy-ink);font-size:13px">Annual tax summary · PDF</a>
                    <span style="display:flex;justify-content:space-between;align-items:center;padding:9px 11px;color:var(--vy-muted);font-size:13px">Email to me <span class="vy-soon-tag">Soon</span></span>
                </div>
            </div>
        @endownercan
    </div>

    {{-- ── KPIs — REAL (net reconciles to statements; occupancy + nights from the metric layer) ── --}}
    <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
        <div class="vy-kpi">
            <div class="vy-kpi-label">Net earned · {{ $kpis['net_period'] }}</div>
            <div class="vy-kpi-val">{{ $kpis['net'] }}</div>
            <div class="vy-kpi-sub">Reconciles to your statements</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Avg occupancy</div>
            <div class="vy-kpi-val">{{ $kpis['occupancy'] }}</div>
            <div class="vy-kpi-sub">Across {{ $unitsCount }} {{ \Illuminate\Support\Str::plural('unit', $unitsCount) }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Nights sold</div>
            <div class="vy-kpi-val">{{ $kpis['nights'] }}</div>
            <div class="vy-kpi-sub">Room-nights in the period</div>
        </div>
    </div>

    <div class="vy-cols" style="margin-top:18px">
        {{-- ── LEFT ── --}}
        <div class="vy-stack">
            {{-- Net earnings by month --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <h2 class="vy-card-title">Net earnings by month</h2>
                <div class="vy-line-sub">{{ $activeRange['label'] }} · your ledger, per month</div>
                @if ($netByMonth['has_data'])
                    <div style="display:grid;grid-template-columns:repeat({{ count($netByMonth['months']) }},1fr);gap:6px;align-items:end;height:150px;margin-top:16px">
                        @foreach ($netByMonth['months'] as $m)
                            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end" title="{{ $m['label'] }} · {{ $m['net_display'] }}">
                                <div style="width:100%;max-width:26px;height:{{ max(2, $m['pct']) }}%;border-radius:6px 6px 0 0;background:var(--vy-grad)"></div>
                                <span style="font-size:10px;color:var(--vy-muted)">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty" style="margin-top:14px">No earnings in this period yet.</div>
                @endif
            </section>

            {{-- Performance by unit --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s">
                <h2 class="vy-card-title">Performance by unit</h2>
                <div style="overflow-x:auto;margin-top:10px">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th class="vy-num">ADR</th>
                                <th class="vy-num">RevPAR</th>
                                <th class="vy-num">Nights</th>
                                <th class="vy-num">Occ.</th>
                                <th class="vy-num">Net earned</th>
                                <th class="vy-num">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($perUnit as $u)
                                <tr>
                                    <td class="vy-row-month">{{ $u['name'] }}</td>
                                    <td class="vy-num vy-mono">{{ $u['adr'] }}</td>
                                    <td class="vy-num vy-mono">{{ $u['revpar'] }}</td>
                                    <td class="vy-num">{{ $u['nights'] }}</td>
                                    <td class="vy-num">{{ $u['occupancy_pct'] }}%</td>
                                    <td class="vy-num vy-mono">{{ $u['net'] }}</td>
                                    <td class="vy-num">{{ $u['share_pct'] }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="vy-empty">No units yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="vy-fx-note">ADR = revenue per night sold · RevPAR = revenue per available night · Share = of your net.</div>
            </section>

            {{-- Booking sources by channel --}}
            <section class="vy-card vy-fade" style="animation-delay:.11s">
                <h2 class="vy-card-title">Booking sources</h2>
                <div class="vy-line-sub">Room-nights by channel · {{ $activeRange['label'] }}</div>
                @forelse ($byChannel as $c)
                    <div style="margin-top:12px">
                        <div style="display:flex;justify-content:space-between;align-items:baseline;font-size:13px">
                            <span style="font-weight:500">{{ $c['label'] }}</span>
                            <span class="vy-muted">{{ $c['nights'] }} {{ \Illuminate\Support\Str::plural('night', $c['nights']) }} · {{ $c['pct'] }}%</span>
                        </div>
                        <div class="vy-track" style="margin-top:6px"><span style="width:{{ $c['pct'] }}%"></span></div>
                    </div>
                @empty
                    <div class="vy-empty" style="margin-top:12px">No bookings in this period yet.</div>
                @endforelse
            </section>
        </div>

        {{-- ── RIGHT ── --}}
        <div class="vy-stack">
            {{-- Dark hero: the period net --}}
            <section class="vy-card vy-rep-hero vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow" style="color:var(--vy-peach)">{{ $activeRange['label'] }} so far</span>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:30px;letter-spacing:-.02em;margin-top:4px">{{ $kpis['net'] }}</div>
                <div style="font-size:12.5px;color:rgba(255,255,255,.72);margin-top:4px">Net earned · reconciles to your statements</div>
                <a href="{{ route('owner.archive') }}" class="vy-btn vy-btn--ghost" style="margin-top:14px;background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.3);color:#fff">View statements</a>
            </section>

            {{-- Gross to net — reconciles (#17), REAL commission rate --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s">
                <h2 class="vy-card-title">Gross to net</h2>
                <div style="margin-top:8px">
                    <div class="vy-rep-gtn-row"><span>Gross revenue</span><span class="vy-mono">{{ $grossToNet['gross'] }}</span></div>
                    <div class="vy-rep-gtn-row"><span>{{ $commissionLabel }}</span><span class="vy-mono">{{ $grossToNet['commission'] }}</span></div>
                    <div class="vy-rep-gtn-row"><span>Fees &amp; OTA</span><span class="vy-mono">{{ $grossToNet['fees'] }}</span></div>
                    @if ($grossToNet['has_adjustments'])
                        <div class="vy-rep-gtn-row"><span>Costs &amp; adjustments</span><span class="vy-mono">{{ $grossToNet['adjustments'] }}</span></div>
                    @endif
                    <div class="vy-rep-gtn-row vy-rep-gtn-net"><span>Net earned</span><span class="vy-mono" style="color:var(--vy-accent)">{{ $grossToNet['net'] }}</span></div>
                </div>
            </section>

            {{-- Payout summary --}}
            <section class="vy-card vy-fade" style="animation-delay:.11s">
                <span class="vy-eyebrow">Payouts</span>
                @if ($payout['latest_display'])
                    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:8px">
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:24px">{{ $payout['latest_display'] }}</div>
                        <span class="vy-status vy-status--{{ $payout['status'] === 'Paid' ? 'paid' : 'pending' }}"><span class="vy-dot"></span>{{ $payout['status'] }}</span>
                    </div>
                    <div class="vy-line-sub" style="margin-top:6px">Most recent payout · {{ $payout['paid_year_display'] }} paid this year</div>
                @else
                    <div class="vy-empty" style="margin-top:8px">No payouts yet.</div>
                @endif
                <a href="{{ route('owner.payouts') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600;display:inline-block;margin-top:10px">All payouts →</a>
            </section>

            {{-- Documents (real PDFs) --}}
            <section class="vy-card vy-fade" style="animation-delay:.14s">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <h2 class="vy-card-title">Documents</h2>
                    <a href="{{ route('owner.documents') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">All →</a>
                </div>
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    @forelse ($documents as $d)
                        <div class="vy-rep-doc">
                            <span style="font-size:13px">{{ $d['label'] }}</span>
                            <a href="{{ $d['href'] }}" class="vy-btn vy-btn--ghost" style="padding:4px 10px;font-size:12px">PDF</a>
                        </div>
                    @empty
                        <div class="vy-empty" style="margin-top:10px">No documents yet.</div>
                    @endforelse
                @else
                    <div class="vy-line-sub" style="margin-top:10px">Your co-owner or accountant can download statement PDFs here.</div>
                @endownercan
            </section>

            {{-- Tax & VAT summary — HONEST-DEFER (Vyrobel does not compute VAT; no fabricated figure, #39) --}}
            <section class="vy-card vy-rep-vat vy-fade" style="animation-delay:.17s">
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                    <h2 class="vy-card-title">Tax &amp; VAT summary</h2>
                </div>
                <div style="margin-top:10px;font-size:12.5px;color:var(--vy-muted);line-height:1.7">
                    Vyrobel doesn't compute or file your VAT — the rates and bases depend on your jurisdiction and registration.
                    Your <strong style="color:var(--vy-ink)">annual tax summary</strong> gives your accountant the real earnings
                    figures for the year; VAT is filed by you or your accountant.
                </div>
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    <a href="{{ route('owner.tax.annual', (int) now()->year) }}" class="vy-btn vy-btn--ghost" style="margin-top:12px;padding:6px 13px;font-size:12.5px">Annual tax summary · PDF</a>
                @endownercan
                <a href="{{ route('owner.tax') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600;display:inline-block;margin-top:10px">Tax center →</a>
            </section>

            {{-- Notifications — the REAL self-scoped feed (never fabricated rows) --}}
            <section class="vy-card vy-fade" style="animation-delay:.2s">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="vy-eyebrow">Notifications</span>
                    <a href="{{ route('owner.notifications') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">All →</a>
                </div>
                @forelse ($notifications['items'] as $n)
                    <div class="vy-rep-note">
                        <span style="flex:none;width:7px;height:7px;border-radius:50%;background:{{ $n['read'] ? 'rgba(120,70,40,.2)' : 'var(--vy-accent)' }};margin-top:5px"></span>
                        <div>
                            <div style="font-weight:600">{{ $n['title'] }}</div>
                            @if ($n['context'])<div class="vy-line-sub">{{ $n['context'] }}</div>@endif
                            @if ($n['when'])<div style="font-size:11px;color:#A69A92;margin-top:2px">{{ $n['when'] }}</div>@endif
                        </div>
                    </div>
                @empty
                    <div class="vy-empty" style="margin-top:10px">You're all caught up.</div>
                @endforelse
            </section>
        </div>
    </div>
    @endif
</x-owner.shell>
