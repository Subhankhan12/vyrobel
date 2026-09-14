{{-- Guest online check-in (Guest Portal batch 2 · GATE 2) — a 4-step wizard (Details → ID → Arrival →
     Done), server-rendered with a tiny no-Alpine stepper (JS-off → all steps visible + submittable). It
     submits a pre-check-in INTENT (guest_checked_in_at) + a real arrival window + an optional ENCRYPTED ID.
     HONESTY: door code is delivered BY EMAIL (SMS deferred — no provider); the ID is CAPTURED for the guest
     register, never "verified"; no fabricated co-guest name. RAW-BLADE RULE: every `@` follows a space/tag. --}}
@php
    $windowLabel = $arrivalWindows[$arrivalWindow] ?? null;
@endphp
<x-guest.shell active="trips" :title="'Check in · '.$reference">
    <x-slot:head>
        <style>
            .ci-hero { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; background: linear-gradient(135deg, rgba(255,252,248,.72), rgba(255,246,238,.5)); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 22px; margin-bottom: 18px; }
            .ci-hero-l { flex: 1 1 260px; min-width: 0; display: flex; gap: 16px; }
            .ci-hero-mark { width: 54px; height: 54px; border-radius: 16px; background: var(--vy-grad); display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); flex-shrink: 0; }
            .ci-pill { display: inline-flex; align-items: center; gap: 7px; padding: 3px 12px 3px 9px; border-radius: 30px; margin-bottom: 8px; font-size: 11px; font-weight: 600; }
            .ci-pill .dot { width: 7px; height: 7px; border-radius: 50%; }
            .ci-pill.pending { background: rgba(217,154,61,.16); color: var(--vy-amber-text); }
            .ci-pill.pending .dot { background: #d99a3d; }
            .ci-pill.done { background: rgba(79,122,70,.14); color: var(--vy-green-text); }
            .ci-pill.done .dot { background: #4f7a46; }
            .ci-title { font-family: var(--vy-display); font-weight: 600; font-size: 25px; letter-spacing: -0.02em; line-height: 1.12; margin: 0; }
            .ci-sub { font-size: 13px; color: var(--vy-muted); margin: 5px 0 0; line-height: 1.45; }
            .ci-stats { flex: 1 1 300px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .ci-stat { padding: 12px 14px; border-radius: 15px; background: rgba(255,255,255,.55); border: 1px solid var(--vy-hairline); }
            .ci-stat .n { font-family: var(--vy-display); font-weight: 600; font-size: 19px; letter-spacing: -0.02em; color: var(--vy-ink); line-height: 1; }
            .ci-stat .l { font-size: 11px; color: var(--vy-muted); margin-top: 5px; }

            .ci-bento { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; }
            .ci-main { flex: 1.4 1 400px; min-width: 0; }
            .ci-side { flex: 1 1 240px; min-width: 0; display: flex; flex-direction: column; gap: 14px; }

            .ci-rail { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
            .ci-rail .seg { display: flex; align-items: center; gap: 7px; flex: 1; }
            .ci-rail .seg.mid { justify-content: center; }
            .ci-rail .seg.end { justify-content: flex-end; }
            .ci-rail .circ { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; font-size: 11.5px; font-weight: 600; flex-shrink: 0; border: 1.5px solid var(--vy-hairline); color: var(--vy-faint); background: rgba(255,255,255,.6); }
            .ci-rail .circ.active { background: var(--vy-accent); color: #fff; border-color: transparent; }   /* white step number: 3.48 on the gradient -> 5.18 solid */
            .ci-rail .circ.on { background: rgba(217,102,46,.14); color:var(--vy-accent-text); border-color: transparent; }
            .ci-rail .lbl { font-size: 11.5px; font-weight: 500; color: var(--vy-muted); white-space: nowrap; }
            .ci-rail .bar { flex: 1; height: 2px; border-radius: 2px; background: var(--vy-hairline); }

            .ci-fl { font-size: 10.5px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 6px; }
            .ci-in { width: 100%; box-sizing: border-box; border: 1px solid rgba(120,70,40,.14); background: rgba(255,255,255,.75); border-radius: 14px; padding: 11px 14px; font-family: var(--vy-sans); font-size: 13.5px; color: var(--vy-ink); outline: none; }
            .ci-readonly { padding: 11px 14px; border-radius: 14px; background: rgba(120,70,40,.05); border: 1px solid var(--vy-hairline); font-size: 13.5px; color: var(--vy-ink); }
            .ci-step-h { font-family: var(--vy-display); font-weight: 600; font-size: 21px; letter-spacing: -0.02em; }
            .ci-step-sub { font-size: 13px; color: var(--vy-muted); margin: 5px 0 18px; }
            .ci-note-box { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; background: rgba(120,70,40,.06); border: 1px solid rgba(120,70,40,.10); font-size: 12px; color: var(--vy-muted); }
            .ci-btn { width: 100%; padding: 13px 0; border-radius: 26px; background: var(--vy-accent); border: none; color: #fff;   /* white 14px CTA: 3.39 on the gradient -> 5.18 solid */ font-family: var(--vy-sans); font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: var(--vy-shadow-accent); }
            .ci-btn-ghost { width: 100%; margin-top: 10px; padding: 11px 0; border-radius: 26px; background: transparent; border: 1px solid rgba(120,70,40,.16); color: var(--vy-muted); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; cursor: pointer; }

            .ci-idtypes { display: flex; gap: 8px; margin-bottom: 14px; }
            .ci-idtype { flex: 1; padding: 9px 0; border-radius: 24px; border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.6); color: var(--vy-muted); font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; cursor: pointer; text-align: center; }
            .ci-idtype.on { background: rgba(217,102,46,.10); border-color: rgba(232,112,58,.45); color:var(--vy-accent-text); }
            .ci-idzone { display: flex; flex-direction: column; align-items: center; gap: 9px; padding: 24px 20px; border-radius: 16px; border: 1.5px dashed rgba(120,70,40,.25); background: rgba(255,255,255,.5); cursor: pointer; text-align: center; margin-bottom: 12px; }
            .ci-idzone.has { border: 1px solid rgba(232,112,58,.45); background: rgba(217,102,46,.08); }
            .ci-idzone .ic { width: 46px; height: 46px; border-radius: 50%; background: rgba(217,102,46,.13); color:var(--vy-accent-text); display: grid; place-items: center; }
            .ci-idzone.has .ic { background: var(--vy-grad); color: #fff; }
            .ci-idzone .t { font-size: 13.5px; font-weight: 500; color: var(--vy-ink); }
            .ci-idzone .s { font-size: 11.5px; color: var(--vy-faint); }
            /* ⚠️ The ID upload was `hidden` (= display:none), which drops it out of the tab order entirely —
               and its wrapping <label> is not focusable — so a keyboard-only guest could not upload an ID AT
               ALL. The canon's "appearance:none, never display:none", on the realm's PII-capture control.
               Visually hidden but still focusable; :focus-within puts the ring on the drop-zone the guest
               can actually see. No JS involved, so it holds in the no-JS fallback too. */
            .ci-file { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); clip-path: inset(50%); white-space: nowrap; border: 0; }
            .ci-idzone:focus-within { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
            .ci-privacy { display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 18px; font-size: 11px; color: var(--vy-faint); text-align: center; }

            .ci-slots { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 18px; }
            .ci-slot { padding: 12px 0; border-radius: 14px; border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.6); color: var(--vy-ink); font-family: var(--vy-sans); font-size: 13px; font-weight: 500; cursor: pointer; text-align: center; }
            .ci-slot.on { background: rgba(217,102,46,.10); border-color: rgba(232,112,58,.45); color:var(--vy-accent-text); }

            .ci-side-card { background: var(--vy-card); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 18px; }
            .ci-side-h { display: flex; align-items: center; gap: 11px; margin-bottom: 12px; }
            .ci-side-h .ic { width: 32px; height: 32px; border-radius: 10px; background: rgba(217,102,46,.12); color:var(--vy-accent-text); display: grid; place-items: center; flex-shrink: 0; }
            .ci-side-h .t { font-family: var(--vy-display); font-weight: 500; font-size: 15px; }
            .ci-kv { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; font-size: 12.5px; }
            .ci-kv + .ci-kv { border-top: 1px solid var(--vy-hairline); }
            .ci-kv .k { color: var(--vy-muted); }
            .ci-kv .v { font-weight: 500; text-align: right; }
            .ci-unlock { display: flex; align-items: flex-start; gap: 11px; padding: 9px 0; }
            .ci-unlock + .ci-unlock { border-top: 1px solid var(--vy-hairline); }
            .ci-unlock .ic { width: 28px; height: 28px; border-radius: 9px; background: rgba(217,102,46,.12); color:var(--vy-accent-text); display: grid; place-items: center; flex-shrink: 0; }
            .ci-unlock .t { display: block; font-size: 12.5px; font-weight: 500; }
            /* display:block, per the prototype. Without it these two spans render INLINE and the title runs
               straight into its subtitle — "Door code by emailSent before arrival". Invisible to php -l,
               PHPStan, Pint and a Blade compile; only the render shows it. */
            .ci-unlock .s { display: block; font-size: 11px; color: var(--vy-faint); margin-top: 2px; }

            .ci-done-ic { width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 14px; background: var(--vy-grad); display: grid; place-items: center; box-shadow: 0 14px 34px rgba(194,65,12,.42); }
        </style>
    </x-slot:head>

    <a class="vy-link" href="{{ route('guest.reservation', $reservation) }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">← Back to my stay</a>

    @if (session('status'))
        <div class="vy-notice" style="margin:12px 0 16px">{{ session('status') }}</div>
    @endif

    {{-- HERO --}}
    <div class="ci-hero" style="margin-top:12px">
        <div class="ci-hero-l">
            <div class="ci-hero-mark"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="8.5" cy="9" r="4.2"></circle><path d="M11.4 11.6 20 20.2"></path><path d="M16.5 17.2l1.9-1.9"></path></svg></div>
            <div style="min-width:0">
                @if ($submitted)
                    <span class="ci-pill done"><span class="dot"></span>Checked in</span>
                @else
                    <span class="ci-pill pending"><span class="dot"></span>Not checked in yet</span>
                @endif
                <h1 class="ci-title">Online check-in</h1>
                <p class="ci-sub">Two minutes now — we’ll email your arrival details and door code before you travel, so you can head straight to {{ $property }}.</p>
            </div>
        </div>
        <div class="ci-stats">
            <div class="ci-stat"><div class="n">{{ $submitted ? 'Done' : '3 steps' }}</div><div class="l">Check-in</div></div>
            <div class="ci-stat"><div class="n">{{ $checkInShort }}</div><div class="l">Arrival · {{ $checkInDay }}</div></div>
            <div class="ci-stat"><div class="n">{{ $guestsTotal }}</div><div class="l">{{ \Illuminate\Support\Str::plural('Guest', $guestsTotal) }}{{ $daysToArrival > 0 ? ' · in '.$daysToArrival.'d' : '' }}</div></div>
            <div class="ci-stat"><div class="n">By email</div><div class="l">Door code · pre-arrival</div></div>
        </div>
    </div>

    @if (! $checkinable)
        <div class="vy-card"><div class="vy-empty">This booking can no longer be checked in online. Message the property if you need anything for your stay.</div></div>
    @else
        <div class="ci-bento">
            <div class="ci-main">
                @if ($submitted)
                    {{-- DONE state --}}
                    <div class="vy-card" style="padding:26px">
                        <div style="text-align:center;padding:6px 0 14px">
                            <div class="ci-done-ic"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"></path></svg></div>
                            <div style="font-family:var(--vy-display);font-weight:600;font-size:23px;letter-spacing:-.02em">You’re checked in{{ $firstName !== '' ? ', '.$firstName : '' }}</div>
                            <div style="font-size:12.5px;color:var(--vy-muted);margin-top:6px">Guest register {{ $idCaptured ? 'captured' : 'noted' }} · nothing to sign at the desk</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:14px;background:rgba(217,102,46,.10);border:1px solid rgba(232,112,58,.35);margin-bottom:10px">
                            <span style="width:32px;height:32px;border-radius:50%;background:rgba(217,102,46,.16);color:var(--vy-accent-text);display:grid;place-items:center;flex-shrink:0"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span>
                            <span style="min-width:0;flex:1"><span style="display:block;font-size:12.5px;font-weight:500;color:var(--vy-accent-text)">Door code arrives by email</span><span style="display:block;font-size:11px;color:var(--vy-faint);margin-top:1px">Before your arrival — no key pickup</span></span>
                        </div>
                        @if ($windowLabel !== null)
                            <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:14px;background:rgba(120,70,40,.06);border:1px solid rgba(120,70,40,.10);margin-bottom:18px">
                                <span style="width:32px;height:32px;border-radius:50%;background:rgba(217,102,46,.13);color:var(--vy-accent-text);display:grid;place-items:center;flex-shrink:0"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3.5 2"></path></svg></span>
                                <span style="min-width:0;flex:1"><span style="display:block;font-size:12.5px;font-weight:500">Arrival window noted</span><span style="display:block;font-size:11px;color:var(--vy-faint);margin-top:1px">{{ $windowLabel }} · we’ll prep the room</span></span>
                            </div>
                        @endif
                        <a class="ci-btn" style="display:block;text-align:center;text-decoration:none" href="{{ route('guest.reservation', $reservation) }}">Back to my stay</a>
                    </div>
                @else
                    {{-- WIZARD --}}
                    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:12px">
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:16px">Complete your check-in</div>
                        <div id="ci-stepof" style="font-size:11.5px;color:var(--vy-faint)">Step 1 of 3</div>
                    </div>
                    <div class="ci-rail">
                        <div class="seg"><span class="circ active" data-circ="1">1</span><span class="lbl">Details</span></div>
                        <div class="bar"></div>
                        <div class="seg mid"><span class="circ" data-circ="2">2</span><span class="lbl">ID</span></div>
                        <div class="bar"></div>
                        <div class="seg end"><span class="circ" data-circ="3">3</span><span class="lbl">Arrival</span></div>
                    </div>

                    <form id="ci-form" class="vy-card" method="POST" action="{{ route('guest.checkin.submit', $reservation) }}" enctype="multipart/form-data" data-guard-submit style="padding:24px">
                        @csrf

                        {{-- STEP 1 — details --}}
                        <div data-step="1">
                            <div class="ci-step-h">Let’s check you in{{ $firstName !== '' ? ', '.$firstName : '' }}</div>
                            <div class="ci-step-sub">Two minutes now — keys straight away on arrival. We prefilled what we know.</div>
                            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                    <div><div class="ci-fl">First name</div><div class="ci-readonly">{{ $firstName !== '' ? $firstName : '—' }}</div></div>
                                    <div><div class="ci-fl">Last name</div><div class="ci-readonly">{{ $lastName !== '' ? $lastName : '—' }}</div></div>
                                </div>
                                <div><div class="ci-fl">Email</div><div class="ci-readonly">{{ $email !== '' ? $email : '—' }}</div></div>
                                <div><div class="ci-fl">Phone · for your arrival details</div><input id="vy-lbl-checkin-phone" class="ci-in" type="tel" name="phone" value="{{ old('phone', $phone) }}" placeholder="+351 …"></div>
                            </div>
                            @if ($coGuests > 0)
                                <div class="ci-note-box" style="margin-bottom:18px">
                                    <span style="width:30px;height:30px;border-radius:50%;background:rgba(217,102,46,.13);color:var(--vy-accent-text);display:grid;place-items:center;flex-shrink:0"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"></circle><path d="M2.5 20c.8-3.2 3.4-5 6.5-5s5.7 1.8 6.5 5"></path></svg></span>
                                    <span>Travelling with {{ $coGuests }} more {{ \Illuminate\Support\Str::plural('guest', $coGuests) }} on the booking. Only the lead guest checks in online.</span>
                                </div>
                            @endif
                            <button type="button" class="ci-btn" onclick="vyCiStep(2)">Continue to ID</button>
                        </div>

                        {{-- STEP 2 — ID (real encrypted capture; captured, not verified) --}}
                        <div data-step="2" hidden>
                            <div class="ci-step-h">Add your ID</div>
                            <div class="ci-step-sub">Local law asks us to register guests. A quick photo does it — no queue at the desk.</div>
                            <input type="hidden" name="id_type" id="ci-idtype" value="{{ $idType }}">
                            <div class="ci-idtypes">
                                <button type="button" class="ci-idtype {{ $idType === 'passport' ? 'on' : '' }}" data-idtype="passport" onclick="vyCiIdType('passport')">Passport</button>
                                <button type="button" class="ci-idtype {{ $idType === 'id_card' ? 'on' : '' }}" data-idtype="id_card" onclick="vyCiIdType('id_card')">EU ID card</button>
                            </div>
                            <label class="ci-idzone {{ $idCaptured ? 'has' : '' }}" id="ci-idzone">
                                <input type="file" name="id_photo" accept="image/*" capture="environment" class="ci-file" onchange="vyCiIdFile(this)">
                                <span class="ic" id="ci-idzone-ic">
                                    @if ($idCaptured)
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"></path></svg>
                                    @else
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l2-3h6l2 3h3v12H4z"></path><circle cx="12" cy="13.5" r="3.5"></circle></svg>
                                    @endif
                                </span>
                                <span class="t" id="ci-idzone-t">{{ $idCaptured ? 'ID on file — retake to replace' : 'Photograph your ID' }}</span>
                                <span class="s" id="ci-idzone-s">{{ $idCaptured ? 'Captured for the guest register' : 'Tap to open the camera · photo page only' }}</span>
                            </label>
                            <div class="ci-privacy">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5c0 4.6 3.2 8.4 7.5 10 4.3-1.6 7.5-5.4 7.5-10V6z"></path><path d="M9 12l2 2 4-4.5"></path></svg>
                                <span>Encrypted · used only for the guest register · deleted 30 days after checkout</span>
                            </div>
                            <button type="button" class="ci-btn" onclick="vyCiStep(3)">Continue to arrival</button>
                            <button type="button" class="ci-btn-ghost" onclick="vyCiStep(1)">Back</button>
                        </div>

                        {{-- STEP 3 — arrival --}}
                        <div data-step="3" hidden>
                            <div class="ci-step-h">When do you arrive?</div>
                            <div class="ci-step-sub">Housekeeping plans around this — and an early-arrival request is noted for the team.</div>
                            <div class="ci-fl">Arrival window · {{ $checkInLabel }}</div>
                            <input type="hidden" name="arrival_window" id="ci-window" value="{{ $arrivalWindow }}">
                            <div class="ci-slots">
                                @foreach ($arrivalWindows as $slot => $label)
                                    <button type="button" class="ci-slot {{ $arrivalWindow === $slot ? 'on' : '' }}" data-slot="{{ $slot }}" onclick="vyCiSlot('{{ $slot }}')">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="ci-fl">Anything we should prepare? · optional</div>
                            <label class="vy-sr-only" for="vy-lbl-checkin-arrival-note">Allergies, a cot, parking for a larger car…</label>
                            <textarea id="vy-lbl-checkin-arrival-note" class="ci-in" name="arrival_note" rows="2" placeholder="Allergies, a cot, parking for a larger car…" style="resize:vertical;margin-bottom:18px">{{ old('arrival_note', $arrivalNote) }}</textarea>
                            <button type="submit" class="ci-btn">Complete check-in</button>
                            <button type="button" class="ci-btn-ghost" onclick="vyCiStep(2)">Back</button>
                        </div>
                    </form>
                @endif

                <div style="text-align:center;font-size:11px;color:var(--vy-faint);margin-top:14px;line-height:1.5">Reservation {{ $reference }} · questions? <a href="{{ route('guest.messages') }}" style="color:var(--vy-accent-text);font-weight:500;text-decoration:none">Message the property</a></div>
            </div>

            {{-- SIDEBAR --}}
            <div class="ci-side">
                <div class="ci-side-card">
                    <div class="ci-side-h"><span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11.5 12 5l8 6.5"></path><path d="M6 10.5V20h12v-9.5"></path></svg></span><span class="t">Your stay</span></div>
                    <div class="ci-kv"><span class="k">Property</span><span class="v">{{ $property }}</span></div>
                    <div class="ci-kv"><span class="k">Dates</span><span class="v">{{ $checkInShort }} · {{ $nights }} {{ \Illuminate\Support\Str::plural('night', $nights) }}</span></div>
                    <div class="ci-kv"><span class="k">Guests</span><span class="v">{{ $adults }} {{ \Illuminate\Support\Str::plural('adult', $adults) }}@if ($children > 0), {{ $children }} {{ \Illuminate\Support\Str::plural('child', $children) }}@endif</span></div>
                    <div class="ci-kv"><span class="k">Reservation</span><span class="v">{{ $reference }}</span></div>
                </div>
                <div class="ci-side-card">
                    <div class="ci-side-h"><span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.1 4.6L19 8.2l-3.5 3.4.9 4.9L12 14.2 7.6 16.5l.9-4.9L5 8.2l4.9-.6z"></path></svg></span><span class="t">What this unlocks</span></div>
                    <div class="ci-unlock"><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span><span><span class="t">Door code by email</span><span class="s">Sent before arrival — no key pickup</span></span></div>
                    <div class="ci-unlock"><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5z"></path></svg></span><span><span class="t">Skip the front desk</span><span class="s">Walk straight to your room on arrival</span></span></div>
                    <div class="ci-unlock"><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3.5 2"></path></svg></span><span><span class="t">Arrival window noted</span><span class="s">We’ll prep the room as soon as we can</span></span></div>
                </div>
            </div>
        </div>

        @if (! $submitted)
            <script>
                // No-Alpine 3-step stepper (progressive enhancement; JS-off → all steps visible + submittable).
                var vyCiCurrent = 1;   // the visible step, tracked so the Enter guard below knows where it is
                function vyCiStep(n) {
                    vyCiCurrent = n;
                    document.querySelectorAll('[data-step]').forEach(function (el) { el.hidden = (parseInt(el.dataset.step, 10) !== n); });
                    document.querySelectorAll('[data-circ]').forEach(function (c) {
                        var i = parseInt(c.dataset.circ, 10);
                        c.classList.toggle('active', i === n);
                        c.classList.toggle('on', i < n);
                    });
                    var so = document.getElementById('ci-stepof');
                    if (so) so.textContent = 'Step ' + n + ' of 3';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
                function vyCiIdType(t) {
                    document.getElementById('ci-idtype').value = t;
                    document.querySelectorAll('[data-idtype]').forEach(function (b) { b.classList.toggle('on', b.dataset.idtype === t); });
                }
                function vyCiIdFile(input) {
                    var zone = document.getElementById('ci-idzone');
                    var t = document.getElementById('ci-idzone-t');
                    var s = document.getElementById('ci-idzone-s');
                    if (input.files && input.files.length) {
                        zone.classList.add('has');
                        if (t) { t.textContent = 'Photo ready to submit'; }
                        if (s) { s.textContent = 'Captured for the guest register · not shared with the owner'; }
                    }
                }
                function vyCiSlot(v) {
                    document.getElementById('ci-window').value = v;
                    document.querySelectorAll('[data-slot]').forEach(function (b) { b.classList.toggle('on', b.dataset.slot === v); });
                }

                // ⚠️ §N.4k — Enter must never complete the check-in from an earlier step. This is ONE form
                // spanning all three steps with a single submit ("Complete check-in"), so a browser's implicit
                // submission fired the REAL write from the phone field on step 1: it saved the phone, stamped
                // guest_checked_in_at (a once-only stamp) and finished check-in without the guest ever seeing
                // the ID or arrival steps. A guest pressing Enter means "next field", not "submit my identity".
                //
                // Guarded HERE, in the stepper, on purpose: with JS off the stepper never runs, every step is
                // visible at once, and implicit submission is then the CORRECT behaviour — the form really is
                // complete. So the guard applies exactly when the wizard is hiding steps, and disappears with it.
                // Textareas are untouched (they never implicitly submit; Enter is a newline in the arrival note).
                (function () {
                    var form = document.getElementById('ci-form');
                    if (!form) { return; }
                    form.addEventListener('keydown', function (e) {
                        if (e.key !== 'Enter' || e.target.tagName !== 'INPUT' || e.target.type === 'file') { return; }
                        if (vyCiCurrent >= 3) { return; }        // on the last step Enter may submit — it is complete
                        e.preventDefault();                       // …otherwise never submit from an earlier step
                        vyCiStep(vyCiCurrent + 1);                // do what the guest meant: move on
                    });
                })();
            </script>
        @endif
    @endif
</x-guest.shell>
