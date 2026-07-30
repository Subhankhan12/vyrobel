{{-- Owner Booking Detail — exact-match rebuild (#78) to the decoded "Owner Booking Detail" wireframe, on the
     REAL owner-scoped reservation. The controller has already 404'd anything that doesn't touch one of THIS
     owner's units.
     🔒 #28 — guest NAME + a derived owner-scoped tag ONLY. Email/phone/country are NEVER sent to this view;
        "Message {guest}" routes to the OPERATOR relay (owner↔operator staff), never owner→guest.
     🔒 #17 — earnings via the ONE shared OwnerLedgerAttribution; the Commission line shows the REAL rate
        (OwnerCommissionRate, 18%), NEVER the wireframe's hardcoded 15%. #39 — no fabricated timeline/rate-plan.
     Shell + --vy-* tokens (warm glass, Sora/Inter, vyFadeUp). RAW-BLADE RULE: every `@` follows a space/newline/tag. --}}
<x-owner.shell active="bookings"
               :breadcrumb="[['label' => 'Overview', 'url' => route('owner.dashboard')], ['label' => 'Bookings', 'url' => route('owner.bookings')], ['label' => $guest['name'] ?? $reservation['reference']]]"
               :title="''"
               :viewer="$viewer">
    <style>
        .vy-topbar h1:empty { display:none; margin:0; }
        .vy-bd-grid { display:grid; grid-template-columns:minmax(0,1fr) 288px; gap:18px; align-items:start; }
        @media (max-width: 900px) { .vy-bd-grid { grid-template-columns:1fr; } }
        .vy-bd-card { background:rgba(255,252,248,.58); backdrop-filter:blur(22px) saturate(1.15); border:1px solid rgba(255,255,255,.75); border-radius:20px; box-shadow:0 14px 40px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65); padding:22px 24px; }
        .vy-bd-h2 { margin:0; font-family:var(--vy-display); font-weight:500; font-size:16px; letter-spacing:-.01em; }
        .vy-bd-eyebrow { font-size:10.5px; font-weight:500; letter-spacing:.07em; text-transform:uppercase; color:#9C9089; }
        .vy-bd-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:8px 0; }
        .vy-bd-hair { border-top:1px solid rgba(120,70,40,.08); }
        .vy-mono { font-variant-numeric:tabular-nums; }
        .vy-bd-msg { display:inline-flex; align-items:center; gap:7px; margin-top:16px; padding:9px 15px; border-radius:11px; background:rgba(194,65,12,.10); color:#C2410C; font-size:13px; font-weight:500; text-decoration:none; }
        .vy-bd-msg:hover { background:rgba(194,65,12,.17); }
        .vy-bd-act { display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; font-size:13px; font-weight:500; text-decoration:none; margin-bottom:9px; }
        .vy-bd-act--primary { background:linear-gradient(180deg,#E8703A,#C2410C); color:#fff; box-shadow:0 6px 16px rgba(194,65,12,.28); }
        .vy-bd-act--ghost { background:rgba(255,255,255,.55); border:1px solid rgba(255,255,255,.8); color:#2B2320; }
        .vy-bd-act--ghost:hover { background:rgba(255,255,255,.85); }
        .vy-bd-act--muted { opacity:.55; cursor:not-allowed; }
    </style>

    {{-- ══ IDENTITY HEADER (avatar · name · status pill · sub) ══ --}}
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px" class="vy-fade">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(140deg,#F0A57A,#C2410C);display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--vy-display);font-weight:600;font-size:17px;letter-spacing:.02em;flex-shrink:0;box-shadow:0 6px 16px rgba(150,90,50,.22)">{{ $guest['initials'] }}</div>
        <div style="min-width:0">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <h1 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:27px;letter-spacing:-.02em">{{ $guest['name'] ?? 'Guest' }}</h1>
                <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:30px;background:{{ $reservation['stage']['bg'] }};color:{{ $reservation['stage']['co'] }};font-size:11.5px;font-weight:500"><span style="width:6px;height:6px;border-radius:50%;background:{{ $reservation['stage']['dot'] }}"></span>{{ $reservation['stage']['label'] }}</span>
            </div>
            <div style="font-size:12.5px;color:#A69A92;margin-top:3px">{{ $reservation['head_sub'] }}</div>
        </div>
    </div>

    <div class="vy-bd-grid">

        {{-- ══ LEFT COLUMN ══ --}}
        <div style="display:flex;flex-direction:column;gap:18px;min-width:0">

            {{-- Reservation --}}
            <section class="vy-bd-card vy-fade" style="animation-delay:.06s">
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:20px">
                    <h2 class="vy-bd-h2">Reservation</h2>
                    <span class="vy-mono" style="font-size:11.5px;color:#A69A92">{{ $reservation['reference'] }}</span>
                </div>

                <div style="display:flex;align-items:center;gap:8px">
                    <div style="flex:1;text-align:center">
                        <div class="vy-bd-eyebrow" style="margin-bottom:6px">Check-in</div>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:34px;letter-spacing:-.02em;line-height:1">{{ $reservation['ci_day'] }}</div>
                        <div style="font-size:12.5px;color:#8A8079;margin-top:5px">{{ $reservation['ci_sub'] }}</div>
                        <div style="font-size:11.5px;color:#A69A92;margin-top:2px">from 3:00 PM</div>
                    </div>
                    <div style="flex:1.2;position:relative;display:flex;align-items:center;justify-content:center;min-width:84px;align-self:flex-start;margin-top:30px">
                        <div style="position:absolute;left:4px;right:4px;top:50%;height:0;border-top:2px dashed rgba(120,70,40,.22)"></div>
                        <div style="position:relative;background:#fff;border:1px solid rgba(255,255,255,.9);box-shadow:0 4px 12px rgba(150,90,50,.16);border-radius:30px;padding:5px 13px;display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:#C2410C;white-space:nowrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                            {{ $reservation['nights'] }} {{ \Illuminate\Support\Str::plural('night', $reservation['nights']) }}
                        </div>
                    </div>
                    <div style="flex:1;text-align:center">
                        <div class="vy-bd-eyebrow" style="margin-bottom:6px">Check-out</div>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:34px;letter-spacing:-.02em;line-height:1">{{ $reservation['co_day'] }}</div>
                        <div style="font-size:12.5px;color:#8A8079;margin-top:5px">{{ $reservation['co_sub'] }}</div>
                        <div style="font-size:11.5px;color:#A69A92;margin-top:2px">by 10:00 AM</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:24px;padding-top:18px;border-top:1px solid rgba(120,70,40,.10)">
                    <div>
                        <div class="vy-bd-eyebrow" style="margin-bottom:4px">Unit</div>
                        @foreach ($reservation['units'] as $unit)
                            <a href="{{ route('owner.units.show', $unit['id']) }}" style="font-size:13.5px;font-weight:500;color:#C2410C;text-decoration:none">{{ $unit['label'] }}</a>@if (! $loop->last), @endif
                        @endforeach
                    </div>
                    <div>
                        <div class="vy-bd-eyebrow" style="margin-bottom:4px">Guests</div>
                        <div style="font-size:13.5px;font-weight:500">{{ $reservation['guests_label'] ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="vy-bd-eyebrow" style="margin-bottom:4px">Nights</div>
                        <div class="vy-mono" style="font-size:13.5px;font-weight:500">{{ $reservation['nights'] }}</div>
                    </div>
                    <div>
                        <div class="vy-bd-eyebrow" style="margin-bottom:4px">Nightly rate</div>
                        <div class="vy-mono" style="font-size:13.5px;font-weight:500">{{ $reservation['nightly_rate'] ?? '—' }}</div>
                    </div>
                </div>
            </section>

            {{-- Your earnings — the REAL gross→net via the shared rule; Commission shows the REAL rate --}}
            <section class="vy-bd-card vy-fade" style="animation-delay:.12s">
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:8px">
                    <h2 class="vy-bd-h2">Your earnings</h2>
                    <span style="font-size:11.5px;color:#A69A92">gross → net</span>
                </div>

                @php $isZero = fn ($d) => (int) preg_replace('/\D/', '', (string) $d) === 0; @endphp
                @forelse ($earnings as $block)
                    <div style="margin-top:8px">
                        @if (count($earnings) > 1)
                            <div class="vy-bd-eyebrow" style="margin-bottom:4px">{{ $block['currency'] }}</div>
                        @endif
                        @foreach ($block['credits'] as $line)
                            @unless ($isZero($line['display']))
                                <div class="vy-bd-row">
                                    <span style="font-size:13px;color:#6E635C">{{ $line['label'] }}</span>
                                    <span class="vy-mono" style="font-size:13px;font-weight:500">{{ $line['display'] }}</span>
                                </div>
                            @endunless
                        @endforeach
                        <div class="vy-bd-row vy-bd-hair" style="margin-top:2px">
                            <span style="font-size:13px;font-weight:500;color:#2B2320">Gross booking value</span>
                            <span class="vy-mono" style="font-size:13.5px;font-weight:600;font-family:var(--vy-display)">{{ $block['credited_display'] }}</span>
                        </div>
                        @foreach ($block['deductions'] as $line)
                            @unless ($isZero($line['display']))
                                <div class="vy-bd-row">
                                    <span style="font-size:13px;color:#6E635C">{{ $line['label'] }}@if ($line['label'] === 'Commission' && $commissionRate) · {{ $commissionRate }}%@endif</span>
                                    <span class="vy-mono" style="font-size:13px;color:#8A8079">{{ $line['display'] }}</span>
                                </div>
                            @endunless
                        @endforeach
                        <div class="vy-bd-row" style="padding:13px 0 4px;margin-top:4px;border-top:1.5px solid rgba(120,70,40,.16)">
                            <span style="font-size:14px;font-weight:600;font-family:var(--vy-display)">Net to you</span>
                            <span class="vy-mono" style="font-size:18px;font-weight:600;color:#C2410C;font-family:var(--vy-display)">{{ $block['net_display'] }}</span>
                        </div>
                    </div>
                @empty
                    <div style="margin-top:12px;padding:16px;border-radius:12px;background:rgba(120,70,40,.04);font-size:13px;color:#8A8079">
                        Not yet attributed.
                        <div style="font-size:11.5px;color:#A69A92;margin-top:6px">Your earnings appear here once this booking is attributed to your ledger — usually after check-out.</div>
                    </div>
                @endforelse
            </section>

            {{-- Guest — NAME + tag ONLY (#28); "Message" → the OPERATOR relay --}}
            <section class="vy-bd-card vy-fade" style="animation-delay:.18s">
                <h2 class="vy-bd-h2" style="margin-bottom:16px">Guest</h2>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                    <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(140deg,#F0A57A,#C2410C);display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--vy-display);font-weight:600;font-size:14px;flex-shrink:0">{{ $guest['initials'] }}</div>
                    <div style="min-width:0">
                        <div style="font-size:14px;font-weight:500">{{ $guest['name'] ?? 'Guest' }}</div>
                        @if ($guest['tag'])<div style="font-size:12px;color:#A69A92;margin-top:1px">{{ $guest['tag'] }}</div>@endif
                    </div>
                </div>
                {{-- ⚠️ #28 — no email / phone / country. An owner seeing guest contact is a privacy breach + a
                     platform-bypass risk. Contact stays with the operator; the owner sees the booking + earnings. --}}
                <div style="padding:11px 13px;border-radius:11px;background:rgba(120,70,40,.05);font-size:12px;color:#8A8079;line-height:1.5">
                    Guest contact details are handled by your management team — you see the booking and your earnings.
                </div>
                <a href="{{ route('owner.messages') }}" class="vy-bd-msg">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-3.2-.6L3 21l1.7-5.1a8.4 8.4 0 1 1 16.3-4.4z"/></svg>
                    Message your team
                </a>
            </section>

            {{-- Timeline — REAL events from real timestamps (fabricated pre-arrival email omitted, #39) --}}
            <section class="vy-bd-card vy-fade" style="animation-delay:.24s">
                <h2 class="vy-bd-h2" style="margin-bottom:18px">Timeline</h2>
                @foreach ($timeline as $ev)
                    @php
                        $done = $ev['state'] === 'done'; $now = $ev['state'] === 'now'; $cancel = $ev['state'] === 'cancel';
                        $on = $done || $now || $cancel;
                        $dotBg = $cancel ? '#C2564A' : ($done ? '#C2410C' : ($now ? '#E0A02A' : 'transparent'));
                    @endphp
                    <div style="display:flex;gap:13px;align-items:flex-start">
                        <div style="display:flex;flex-direction:column;align-items:center;align-self:stretch">
                            <div style="width:13px;height:13px;border-radius:50%;background:{{ $dotBg }};border:{{ $on ? 'none' : '2px solid #D8CFC8' }};flex-shrink:0;margin-top:2px"></div>
                            @unless ($loop->last)<div style="width:2px;flex:1;background:{{ $done ? 'rgba(194,65,12,.28)' : 'rgba(120,70,40,.12)' }};min-height:14px"></div>@endunless
                        </div>
                        <div style="padding-bottom:{{ $loop->last ? '0' : '16px' }}">
                            <div style="font-size:13px;font-weight:500;color:{{ $on ? '#2B2320' : '#9C9089' }}">{{ $ev['label'] }}</div>
                            <div style="font-size:11.5px;color:#A69A92;margin-top:1px">{{ $ev['when'] }}</div>
                        </div>
                    </div>
                @endforeach
            </section>
        </div>

        {{-- ══ RIGHT COLUMN ══ --}}
        <div style="display:flex;flex-direction:column;gap:18px;min-width:0">

            {{-- Net to you — dark hero + count-up (presentational; ends at the REAL net) --}}
            <div style="position:relative;animation-delay:.06s" class="vy-fade">
                <div style="position:absolute;inset:-16px -10px;background:radial-gradient(circle,rgba(232,112,58,.42),transparent 70%);filter:blur(28px);pointer-events:none"></div>
                <div style="position:relative;background:linear-gradient(160deg,#3A2E27 0%,#251C17 100%);border-radius:20px;box-shadow:0 10px 30px rgba(60,30,10,.28);padding:20px 22px">
                    <div style="font-size:11px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:#B9ABA0;margin-bottom:10px">Net to you</div>
                    @if ($net !== null)
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:34px;letter-spacing:-.02em;color:#fff"
                             class="vy-mono"
                             x-data="{ target: {{ $net['major'] }}, sym: @js($net['symbol']), final: @js($net['display']), n: 0 }"
                             x-init="if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { $el.textContent = final; return; } let d = 800, t0 = null; const f = (ts) => { if (! t0) t0 = ts; const p = Math.min(1, (ts - t0) / d); n = target * (1 - Math.pow(1 - p, 3)); if (p < 1) requestAnimationFrame(f); else $el.textContent = final; }; requestAnimationFrame(f)"
                             x-text="sym + n.toFixed(2)">{{ $net['display'] }}</div>
                    @else
                        @foreach ($earnings as $block)
                            <div style="font-family:var(--vy-display);font-weight:600;font-size:26px;color:#fff" class="vy-mono">{{ $block['net_display'] }} <span style="font-size:11px;color:#B9ABA0">{{ $block['currency'] }}</span></div>
                        @endforeach
                        @if ($earnings === [])
                            <div style="font-family:var(--vy-display);font-weight:600;font-size:34px;color:rgba(255,255,255,.4)">—</div>
                        @endif
                    @endif
                    <div style="font-size:12.5px;color:#B9ABA0;margin-top:6px">for this {{ $reservation['nights'] }}-night stay</div>
                    <div style="height:1px;background:rgba(255,255,255,.10);margin:14px 0 12px"></div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:30px;background:rgba(232,112,58,.18);color:#F0A57A;font-size:10.5px;font-weight:500"><span style="width:5px;height:5px;border-radius:50%;background:#F0A57A"></span>{{ $payout['label'] }}</span>
                    </div>
                    <div style="font-size:12px;color:#B9ABA0;margin-top:10px;line-height:1.5">{{ $payout['note'] }}</div>
                </div>
            </div>

            {{-- Booking details --}}
            <section class="vy-bd-card vy-fade" style="animation-delay:.12s;padding:18px 20px">
                <div class="vy-bd-eyebrow" style="letter-spacing:.08em;margin-bottom:12px">Booking details</div>
                <div class="vy-bd-row vy-bd-hair" style="padding:9px 0">
                    <span style="font-size:12.5px;color:#8A8079">Confirmation</span>
                    <span class="vy-mono" style="font-size:12.5px;font-weight:500">{{ $reservation['reference'] }}</span>
                </div>
                <div class="vy-bd-row vy-bd-hair" style="padding:9px 0">
                    <span style="font-size:12.5px;color:#8A8079">Source</span>
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:500"><span style="width:7px;height:7px;border-radius:50%;background:{{ $reservation['source_dot'] }}"></span>{{ $reservation['source_label'] }}</span>
                </div>
                <div class="vy-bd-row vy-bd-hair" style="padding:9px 0">
                    <span style="font-size:12.5px;color:#8A8079">Channel</span>
                    <span style="font-size:12.5px;font-weight:500">{{ $reservation['channel'] }}</span>
                </div>
                @if ($reservation['booked_on'])
                    <div class="vy-bd-row vy-bd-hair" style="padding:9px 0">
                        <span style="font-size:12.5px;color:#8A8079">Booked on</span>
                        <span style="font-size:12.5px;font-weight:500">{{ $reservation['booked_on'] }}</span>
                    </div>
                @endif
            </section>

            {{-- Actions --}}
            <section class="vy-bd-card vy-fade" style="animation-delay:.18s;padding:18px 20px">
                <div class="vy-bd-eyebrow" style="letter-spacing:.08em;margin-bottom:12px">Actions</div>
                @if ($canDownload)
                    <a href="{{ $pdfHref }}" class="vy-bd-act vy-bd-act--primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                        Download confirmation
                    </a>
                @else
                    <span class="vy-bd-act vy-bd-act--primary vy-bd-act--muted" title="Available to co-owners and accountants">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                        Download confirmation
                    </span>
                @endif
                @foreach ($reservation['units'] as $unit)
                    <a href="{{ route('owner.units.show', $unit['id']) }}" class="vy-bd-act vy-bd-act--ghost">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C2410C" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>
                        View {{ $unit['label'] }}
                    </a>
                @endforeach
            </section>
        </div>
    </div>
</x-owner.shell>
