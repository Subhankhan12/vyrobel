{{--
    Owner My Units (design pass · Screen 4 — completes the owner portal) — the owner's units + an
    OWNER-SCOPED occupancy calendar, styled from the catalogued "Owner My Units" design and wired to
    LIVE owner-scoped data. Shell from <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerMyUnits):
      • REAL      — unit list + count, per-unit name/type/status, per-unit + portfolio occupancy, nights
                    sold, and the occupancy heatmap — ALL owner-scoped (a non-owner unit in the same room
                    type is excluded; the room-type-wide availabilityRange trap is NOT used here).
      • DEFERRED  — per-unit revenue + nightly rate + best-performer: stubbed "not yet available".
      • NO BACKEND— Add unit, edit-pricing / message-guest, block-dates, export, "Needs attention":
                    disabled "Soon".
      • Guest PII — the calendar shows booking COUNTS only; no guest names anywhere.
--}}
<x-owner.shell active="units" title="My units" :viewer="$viewer">
    <div style="display:flex;justify-content:flex-end;margin-bottom:18px">
        <button type="button" class="vy-btn vy-btn--accent is-soon" style="opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Add a unit — coming soon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Add unit
        </button>
    </div>

    {{-- KPIs — all owner-scoped REAL --}}
    <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
        <div class="vy-kpi">
            <div class="vy-kpi-label">Units</div>
            <div class="vy-kpi-val">{{ $unitsCount }}</div>
            <div class="vy-kpi-sub">In your portfolio</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Occupancy</div>
            <div class="vy-kpi-val">{{ $occupancy['pct'] }}%</div>
            <div class="vy-kpi-sub">{{ $month['label'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Nights sold</div>
            <div class="vy-kpi-val">{{ $occupancy['nights'] }}</div>
            <div class="vy-kpi-sub">{{ $month['label'] }}</div>
        </div>
    </div>

    {{-- Needs attention — NO BACKEND (no approvals/tasks engine): disabled, not faked --}}
    <section class="vy-card vy-card--accent vy-fade" style="margin-top:22px;animation-delay:.05s;display:flex;justify-content:space-between;align-items:center;gap:16px">
        <div style="display:flex;align-items:center;gap:12px">
            <span class="vy-brand-mark" style="border-radius:12px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0"/></svg>
            </span>
            <div>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px">Needs your attention</div>
                <div class="vy-line-sub">Approvals, maintenance and tasks will appear here.</div>
            </div>
        </div>
        <span class="vy-soon-tag">Soon</span>
    </section>

    {{-- Your units --}}
    <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.08s">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h2 class="vy-card-title">Your units</h2>
            <span class="vy-soon-tag">Sort &amp; filter · Soon</span>
        </div>

        <div class="vy-unit-grid">
            @forelse ($units as $u)
                <div class="vy-unit-card">
                    <div class="vy-unit-head">
                        <span style="display:flex;align-items:center;gap:9px">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5"/></svg>
                            <span class="vy-unit-name">{{ $u['name'] }}</span>
                        </span>
                        <span class="vy-unit-badge">{{ $u['active'] ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="vy-unit-body">
                        <div class="vy-line-sub" style="margin-bottom:12px">{{ $u['type'] }}</div>

                        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--vy-muted);margin-bottom:6px">
                            <span>OCCUPANCY · {{ $month['label'] }}</span><span style="font-weight:600;color:var(--vy-ink)">{{ $u['occupancy_pct'] }}%</span>
                        </div>
                        <div class="vy-track"><span style="width:{{ $u['occupancy_pct'] }}%"></span></div>

                        <div class="vy-unit-stat" style="margin-top:12px"><span class="vy-muted">Nights sold</span><span style="font-weight:600">{{ $u['nights'] }}</span></div>
                        <div class="vy-unit-stat"><span class="vy-muted">Revenue</span><span class="vy-soon-tag">Soon</span></div>
                        <div class="vy-unit-stat"><span class="vy-muted">Nightly rate</span><span class="vy-soon-tag">Soon</span></div>

                        <div style="display:flex;gap:8px;margin-top:14px">
                            <button type="button" class="vy-btn vy-btn--ghost is-soon" style="flex:1;justify-content:center;opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">Edit pricing</button>
                            <button type="button" class="vy-btn vy-btn--ghost is-soon" style="flex:1;justify-content:center;opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">Message guest</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="vy-empty" style="grid-column:1/-1">No units yet.</div>
            @endforelse

            {{-- Add a unit — NO BACKEND (owner self-serve not wired): disabled affordance --}}
            <div class="vy-unit-card" style="border-style:dashed;background:rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;text-align:center;min-height:180px">
                <div>
                    <div style="width:42px;height:42px;border-radius:50%;background:var(--vy-grad);color:#fff;display:grid;place-items:center;margin:0 auto 10px">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    </div>
                    <div style="font-weight:600;font-size:13.5px">List a new property</div>
                    <div class="vy-soon-tag" style="margin-top:6px;display:inline-block">Soon</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Occupancy calendar (owner-scoped heatmap) + best performer --}}
    <div class="vy-cols" style="margin-top:22px">
        <div class="vy-stack">
            <section class="vy-card vy-fade" style="animation-delay:.1s">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:14px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <a class="vy-period-btn" href="{{ route('owner.units', ['month' => $month['prev']]) }}" aria-label="Previous month"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></a>
                        <span class="vy-period-cur">{{ $month['label'] }}</span>
                        <a class="vy-period-btn" href="{{ route('owner.units', ['month' => $month['next']]) }}" aria-label="Next month"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="button" class="vy-btn vy-btn--ghost is-soon" disabled aria-disabled="true" title="Coming soon">Block dates</button>
                        <button type="button" class="vy-btn vy-btn--ghost is-soon" disabled aria-disabled="true" title="Coming soon">Export</button>
                    </div>
                </div>

                <div class="vy-cal-grid" style="margin-bottom:6px">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd)
                        <div class="vy-cal-head">{{ $wd }}</div>
                    @endforeach
                </div>
                <div class="vy-cal-grid">
                    @for ($i = 1; $i < $month['first_weekday']; $i++)
                        <div class="vy-cal-empty"></div>
                    @endfor
                    @for ($dom = 1; $dom <= $occupancy['days']; $dom++)
                        @php
                            $count = $occupancy['heat'][$dom] ?? 0;
                            $level = $occupancy['unit_count'] > 0 ? $count / $occupancy['unit_count'] : 0;
                            $bg = $count === 0 ? 'rgba(120,70,40,.05)' : 'rgba(194,65,12,'.number_format(0.15 + 0.7 * $level, 2).')';
                            $fg = $level > 0.55 ? '#fff' : 'var(--vy-ink)';
                        @endphp
                        <div class="vy-cal-cell" style="background:{{ $bg }};color:{{ $fg }}" title="{{ $count }} of {{ $occupancy['unit_count'] }} booked">{{ $dom }}</div>
                    @endfor
                </div>

                <div class="vy-cal-legend">
                    <span>Booked</span>
                    @for ($n = 0; $n <= min($occupancy['unit_count'], 4); $n++)
                        @php
                            $lvl = $occupancy['unit_count'] > 0 ? $n / $occupancy['unit_count'] : 0;
                            $sw = $n === 0 ? 'rgba(120,70,40,.05)' : 'rgba(194,65,12,'.number_format(0.15 + 0.7 * $lvl, 2).')';
                        @endphp
                        <span class="vy-cal-swatch" style="background:{{ $sw }}"></span><span>{{ $n }}</span>
                    @endfor
                    <span>of {{ $occupancy['unit_count'] }} {{ \Illuminate\Support\Str::plural('unit', $occupancy['unit_count']) }}</span>
                </div>

                <div class="vy-line-sub" style="margin-top:12px;font-size:13px">
                    <strong style="color:var(--vy-ink)">{{ $occupancy['nights'] }} nights</strong> · {{ $occupancy['pct'] }}% occupancy · {{ $month['label'] }}
                </div>
            </section>
        </div>

        <div class="vy-stack">
            {{-- Best performer — DEFERRED (derivable, not built this pass) --}}
            <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.13s">
                <span class="vy-eyebrow">Best performer</span>
                <div style="margin-top:12px;font-size:13px;color:rgba(255,255,255,.7);line-height:1.6">
                    A ranked view of your top unit by occupancy and revenue is coming soon.
                </div>
            </section>
        </div>
    </div>
</x-owner.shell>
