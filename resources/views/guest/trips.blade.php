{{-- Guest Trips (Phase 4b · SG1; guest Phase-4 pixel-diff G1) — the authed guest's OWN stays: a travel-account
     HERO (real stats), Upcoming/Past/Cancelled tabs, search + year filter (native GET — no Alpine in the guest
     realm), and rich per-trip cards. Isolation is in the controller (the guest's own reservations only). Every
     figure is REAL (folio-reconciling balance, real completed-stay stats — never a fabricated "369 stays").
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" title="Your trips">
    @php
        // Preserve the other query params when a control changes one (native GET, no JS state).
        $qs = fn (array $over) => http_build_query(array_merge(['tab' => $tab, 'q' => $query, 'year' => $year], $over));
    @endphp

    {{-- ── Travel-account hero — welcome + REAL stats (completed stays · nights away · countries) ────────── --}}
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:24px 26px;margin-bottom:20px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="flex:1 1 220px;min-width:0">
            <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-accent-text)">Your travel account</div>
            {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
            <h1 style="font-family:var(--vy-display);font-weight:700;font-size:26px;letter-spacing:-.02em;color:var(--vy-ink);margin-top:4px;margin:0">Welcome back, {{ $firstName }}</h1>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:3px">Your stays with us — upcoming and past.</div>
        </div>
        <div style="display:flex;gap:28px;flex-wrap:wrap">
            <div>
                <div style="font-family:var(--vy-display);font-weight:700;font-size:26px;color:var(--vy-ink);font-variant-numeric:tabular-nums">{{ $stats['stays'] }}</div>
                <div style="font-size:11.5px;color:var(--vy-muted)">{{ \Illuminate\Support\Str::plural('stay', $stats['stays']) }} completed</div>
            </div>
            <div>
                <div style="font-family:var(--vy-display);font-weight:700;font-size:26px;color:var(--vy-ink);font-variant-numeric:tabular-nums">{{ $stats['nights'] }}</div>
                <div style="font-size:11.5px;color:var(--vy-muted)">{{ \Illuminate\Support\Str::plural('night', $stats['nights']) }} away</div>
            </div>
            <div>
                <div style="font-family:var(--vy-display);font-weight:700;font-size:26px;color:var(--vy-ink);font-variant-numeric:tabular-nums">{{ $stats['countries'] }}</div>
                <div style="font-size:11.5px;color:var(--vy-muted)">{{ \Illuminate\Support\Str::plural('country', $stats['countries']) }}</div>
            </div>
        </div>
    </div>

    {{-- ── Toolbar: tabs + search + year (native GET; preserves the other params) ─────────────────────── --}}
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:20px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .1s both">
        <div style="display:inline-flex;gap:3px;padding:4px;border-radius:30px;background:rgba(255,252,248,.78);border:1px solid var(--vy-glass-border);box-shadow:var(--vy-shadow)">
            @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label)
                @php $on = $tab === $key; @endphp
                <a href="?{{ $qs(['tab' => $key]) }}" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:30px;text-decoration:none;font-size:13px;font-weight:500;{{ $on ? 'background:#fff;color:var(--vy-ink);box-shadow:0 4px 12px rgba(150,90,50,.14)' : 'color:var(--vy-muted)' }}">
                    {{ $label }}
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:30px;font-size:11px;font-weight:600;font-variant-numeric:tabular-nums;{{ $on ? 'background:var(--vy-accent);color:#fff' : 'background:rgba(120,70,40,.1);color:var(--vy-muted)' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>

        <div style="flex:1 1 12px"></div>

        <form method="get" style="position:relative;flex:0 1 300px;min-width:190px;margin:0">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if ($year !== '')<input type="hidden" name="year" value="{{ $year }}">@endif
            <label class="vy-sr-only" for="vy-lbl-trips-q">Search by name, place or ref…</label>
            <input id="vy-lbl-trips-q" type="search" name="q" value="{{ $query }}" placeholder="Search by name, place or ref…" class="vy-input" style="width:100%;box-sizing:border-box;padding:11px 16px;border-radius:30px;font-size:13px">
        </form>

        @if (count($years) > 1)
            <select aria-label="Filter by year" onchange="window.location.href='?tab={{ $tab }}&q={{ rawurlencode($query) }}&year='+this.value" class="vy-input" style="padding:11px 14px;border-radius:30px;font-size:13px">
                <option value="" @selected($year === '')>All years</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected((string) $year === (string) $y)>{{ $y }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- ── Trip cards ────────────────────────────────────────────────────────────────────────────────── --}}
    @forelse ($trips as $t)
        <div style="display:flex;flex-wrap:wrap;background:var(--vy-card);border:1px solid var(--vy-glass-border);border-radius:20px;box-shadow:var(--vy-shadow);overflow:hidden;margin-bottom:18px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) both">
            {{-- image band (deterministic gradient placeholder — no fake photo) --}}
            <div style="position:relative;flex:1 1 220px;min-height:180px;background:{{ $t['gradient'] }};display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.9)">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>
                <span style="position:absolute;top:14px;left:16px;display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:30px;background:rgba(255,255,255,.9)"><span class="vy-badge vy-badge--{{ $t['stage_tone'] }}" style="padding:0;background:none;font-size:11px;font-weight:600">{{ $t['stage_label'] }}</span></span>
                <span style="position:absolute;bottom:14px;left:16px;display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:30px;background:rgba(43,35,32,.5);color:#fff;font-size:11.5px;font-weight:600">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>{{ $t['countdown'] }}
                </span>
            </div>
            {{-- details --}}
            <div style="flex:3 1 380px;padding:22px 24px;min-width:0;box-sizing:border-box;display:flex;flex-direction:column">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap">
                    <div style="min-width:0">
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:20px;letter-spacing:-.01em;line-height:1.15;color:var(--vy-ink)">{{ $t['property'] }}</div>
                        @if ($t['location'] !== '')
                            <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--vy-muted);margin-top:4px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.4-7-11a7 7 0 0 1 14 0c0 5.6-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $t['location'] }}</div>
                        @endif
                    </div>
                    <span style="font-size:11.5px;color:var(--vy-faint);font-variant-numeric:tabular-nums;white-space:nowrap">{{ $t['reference'] }}</span>
                </div>

                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:16px 0 18px">
                    <span style="display:inline-flex;align-items:center;gap:7px;padding:8px 13px;border-radius:12px;background:var(--vy-wash);border:1px solid var(--vy-hairline);font-size:12.5px;font-weight:500;color:var(--vy-ink)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9.5h18"/><path d="M8 2.5v4"/><path d="M16 2.5v4"/></svg>{{ $t['date_range'] }}</span>
                    <span style="display:inline-flex;align-items:center;padding:8px 13px;border-radius:12px;background:var(--vy-wash);border:1px solid var(--vy-hairline);font-size:12.5px;font-weight:500;color:var(--vy-muted)">{{ $t['nights'] }} {{ \Illuminate\Support\Str::plural('night', $t['nights']) }} · {{ $t['total'] }}</span>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:auto;padding-top:16px;border-top:1px solid var(--vy-hairline)">
                    <span style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:{{ $t['balance_due'] ? 'var(--vy-amber-text)' : 'var(--vy-green-text)' }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>{{ $t['balance_label'] }}
                    </span>
                    <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap">
                        @if ($t['reviewable'] ?? false)
                            <a href="{{ route('guest.review', $t['id']) }}" style="padding:10px 16px;border-radius:30px;background:var(--vy-wash);border:1px solid var(--vy-hairline);color:var(--vy-ink);font-size:12.5px;font-weight:500;text-decoration:none;white-space:nowrap"><span aria-hidden="true">★</span> Write a review</a>
                        @elseif ($t['reviewed'] ?? false)
                            <span style="font-size:12px;color:var(--vy-faint);white-space:nowrap"><span aria-hidden="true">★</span> Reviewed — thank you</span>
                        @endif
                        @if ($tab !== 'cancelled')
                            <a href="{{ route('guest.manage', $t['id']) }}" style="padding:10px 16px;border-radius:30px;background:var(--vy-wash);border:1px solid var(--vy-hairline);color:var(--vy-ink);font-size:12.5px;font-weight:500;text-decoration:none;white-space:nowrap">Manage</a>
                        @endif
                        <a href="{{ route('guest.reservation', $t['id']) }}" style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:30px;background:var(--vy-accent);color:#fff;font-size:12.5px;font-weight:500;text-decoration:none;white-space:nowrap;box-shadow:var(--vy-shadow-accent)">View reservation<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="vy-card vy-empty" style="text-align:center;padding:36px 20px">
            @if ($query !== '' || $year !== '')
                No {{ $tab }} trips match your search. <a href="?tab={{ $tab }}" style="color:var(--vy-accent-text)">Clear filters</a>.
            @elseif ($tab === 'upcoming')
                No upcoming stays. When you book, they’ll appear here.
            @elseif ($tab === 'cancelled')
                No cancelled bookings — all good.
            @else
                No past stays yet.
            @endif
        </div>
    @endforelse
</x-guest.shell>
