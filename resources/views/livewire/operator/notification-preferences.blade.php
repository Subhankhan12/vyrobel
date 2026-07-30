{{-- Operator NOTIFICATION PREFERENCES (#90 exact-match) — the operator side of #79 on the SHARED prefs engine.
     EMAIL genuinely gates delivery (mutation-proven); quiet hours are real; always-on events lock. PUSH + SMS
     are HONEST-GATED — no infra exists, so they are display-only/disabled and NEVER submitted (#39). The real
     in_app channel (not a wireframe column) is preserved via hidden inputs. RAW-BLADE RULE: `@` follows a space/tag. --}}
<div>
    <x-operator.shell active="notifications" title="Notification preferences" :subtitle="'How Vyrobel reaches you'" :viewer="$viewer">

        @if (session('prefs_notice')) <div class="vy-flash is-ok vy-fade">{{ session('prefs_notice') }}</div> @endif

        <form method="POST" action="{{ route('app.notification-preferences.update') }}" x-data="{ quietOn: @js($quietOn), digest: @js($digest && $digest['state']['email'] ? 'on' : 'off') }">
            @csrf

            {{-- ── Channel cards: Email (real) · Push (honest) · SMS (honest) ─────────────────────────── --}}
            <div class="vy-np-channels vy-fade">
                <div class="vy-np-chan">
                    <div class="vy-np-chan-icon">✉</div>
                    <div style="flex: 1;"><div class="vy-np-chan-name">Email</div><div class="vy-np-chan-detail">{{ $email !== '' ? $email : 'No email on file' }}</div></div>
                    <span class="vy-np-status is-on">Verified</span>
                </div>
                <div class="vy-np-chan is-off" title="Push isn’t set up yet">
                    <div class="vy-np-chan-icon">🔔</div>
                    <div style="flex: 1;"><div class="vy-np-chan-name">Push <span class="vy-np-soon">not set up</span></div><div class="vy-np-chan-detail">Browser &amp; device push isn’t available yet</div></div>
                    <span class="vy-np-status">Off</span>
                </div>
                <div class="vy-np-chan is-off" title="SMS isn’t set up yet">
                    <div class="vy-np-chan-icon">💬</div>
                    <div style="flex: 1;"><div class="vy-np-chan-name">SMS <span class="vy-np-soon">not set up</span></div><div class="vy-np-chan-detail">No SMS provider connected yet</div></div>
                    <span class="vy-np-status">Off</span>
                </div>
            </div>

            {{-- ── Notify-me grid: Email real · Push / SMS honest-gated ───────────────────────────────── --}}
            <div class="vy-card vy-fade" style="margin-top: 16px;">
                <div class="vy-np-grid vy-np-head">
                    <span>Notify me when</span>
                    <span style="text-align: center;">Email</span>
                    <span style="text-align: center;">Push</span>
                    <span style="text-align: center;">SMS</span>
                </div>

                @foreach ($groups as $groupName => $events)
                    <div class="vy-np-group">{{ $groupName }}</div>
                    @foreach ($events as $event)
                        <div class="vy-np-grid vy-np-row">
                            <div>
                                <div class="vy-np-ev">{{ $event['label'] }}@if ($event['always_on'])<span class="vy-np-always">Always on</span>@endif</div>
                                <div class="vy-np-ev-sub">{{ $event['description'] }}</div>
                            </div>

                            {{-- Preserve the real IN-APP pref (not a wireframe column; the feed keeps working). --}}
                            @if ($event['state']['in_app'])<input type="hidden" name="prefs[{{ $event['key'] }}][in_app]" value="1">@endif

                            {{-- EMAIL — REAL. Always-on locks on (the service forces it); otherwise a real toggle. --}}
                            <div style="text-align: center;">
                                @if ($event['always_on'])
                                    <input type="hidden" name="prefs[{{ $event['key'] }}][email]" value="1">
                                    <span class="vy-np-cell is-on is-locked" title="Always on">✓</span>
                                @else
                                    <label class="vy-np-cell-wrap">
                                        <input type="checkbox" name="prefs[{{ $event['key'] }}][email]" value="1" @checked($event['state']['email']) class="vy-np-check">
                                        <span class="vy-np-cell"></span>
                                    </label>
                                @endif
                            </div>

                            {{-- PUSH — HONEST-GATED: display-only, disabled, never submitted (no name). --}}
                            <div style="text-align: center;"><span class="vy-np-cell is-disabled" title="Push isn’t set up yet"></span></div>
                            {{-- SMS — HONEST-GATED. --}}
                            <div style="text-align: center;"><span class="vy-np-cell is-disabled" title="SMS isn’t set up yet"></span></div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- ── Quiet hours (REAL — holds routine EMAIL during the window; in-app unaffected) ──────── --}}
            <div class="vy-card vy-fade" style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div><h2 class="vy-card-title" style="margin: 0;">Quiet hours</h2><p style="font-size: 12.5px; color: var(--vy-muted); margin: 3px 0 0;">Hold routine email between these hours — urgent security alerts always come through; the in-app feed is unaffected.</p></div>
                    <label class="vyms-switch"><input type="checkbox" name="quiet_hours_enabled" value="1" x-model="quietOn" @checked($quietOn)><span class="vyms-switch-track"><span class="vyms-switch-knob"></span></span></label>
                </div>
                <div class="vyn-grid3" style="margin-top: 12px;" :style="quietOn ? '' : 'opacity:.5;pointer-events:none;'">
                    <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="qs">From</label><input id="qs" type="time" name="quiet_start" value="{{ $quietStart }}" class="vy-input"></div>
                    <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="qe">To</label><input id="qe" type="time" name="quiet_end" value="{{ $quietEnd }}" class="vy-input"></div>
                </div>
            </div>

            {{-- ── Daily digest (HONEST — stores the real daily_summary email pref; not dispatched yet) ── --}}
            @if ($digest)
            <div class="vy-card vy-fade" style="margin-top: 16px;">
                <h2 class="vy-card-title" style="margin: 0 0 3px;">Daily digest email</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">A morning summary of the day. <span class="vy-np-soon">not dispatched yet</span> — the preference is saved for when the digest job ships.</p>
                <input type="hidden" name="prefs[{{ $digest['key'] }}][email]" x-bind:value="digest === 'on' ? '1' : ''">
                @if ($digest['state']['in_app'])<input type="hidden" name="prefs[{{ $digest['key'] }}][in_app]" value="1">@endif
                <div class="vy-seg" style="max-width: 260px;">
                    <label class="vy-seg-opt" :class="digest === 'off' ? 'is-checked' : ''" x-on:click="digest = 'off'">Off</label>
                    <label class="vy-seg-opt" :class="digest === 'on' ? 'is-checked' : ''" x-on:click="digest = 'on'">Morning · 08:00</label>
                </div>
            </div>
            @endif

            <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 22px;">Save changes</button>
            </div>
        </form>

    </x-operator.shell>

    <style>
        .vy-np-channels { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .vy-np-chan { display: flex; gap: 11px; align-items: center; background: var(--vy-glass, rgba(255,252,248,.6)); border: 1px solid var(--vy-hairline); border-radius: 14px; padding: 14px; }
        .vy-np-chan.is-off { opacity: .72; }
        .vy-np-chan-icon { width: 34px; height: 34px; border-radius: 10px; background: rgba(217,102,46,.12); display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .vy-np-chan-name { font-size: 13.5px; font-weight: 600; }
        .vy-np-chan-detail { font-size: 11.5px; color: var(--vy-faint); }
        .vy-np-status { font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; background: var(--vy-hairline); color: var(--vy-muted); }
        .vy-np-status.is-on { background: rgba(31,110,75,.14); color: #1F6E4B; }
        .vy-np-soon { font-size: 9px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-faint); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 1px 5px; margin-left: 4px; }
        .vy-np-grid { display: grid; grid-template-columns: 1fr 64px 64px 64px; gap: 10px; align-items: center; }
        .vy-np-head { padding-bottom: 10px; border-bottom: 1px solid var(--vy-hairline); font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--vy-muted); }
        .vy-np-group { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--vy-accent); font-weight: 700; margin: 16px 0 2px; }
        .vy-np-row { padding: 11px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-np-ev { font-size: 13px; font-weight: 600; color: var(--vy-ink); }
        .vy-np-ev-sub { font-size: 11.5px; color: var(--vy-muted); }
        .vy-np-always { font-size: 10px; color: var(--vy-muted); font-weight: 400; margin-left: 6px; }
        .vy-np-cell-wrap { display: inline-flex; cursor: pointer; }
        .vy-np-check { display: none; }
        .vy-np-cell { width: 26px; height: 26px; border-radius: 8px; border: 1px solid rgba(120,70,40,.2); background: rgba(255,255,255,.7); display: inline-flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; }
        .vy-np-check:checked + .vy-np-cell { background: linear-gradient(140deg, #E8703A, #C2410C); border-color: transparent; }
        .vy-np-check:checked + .vy-np-cell::after { content: '✓'; }
        .vy-np-cell.is-on { background: linear-gradient(140deg, #E8703A, #C2410C); border-color: transparent; }
        .vy-np-cell.is-locked { cursor: default; }
        .vy-np-cell.is-disabled { background: rgba(255,255,255,.4); border-color: rgba(120,70,40,.16); opacity: .5; cursor: not-allowed; }
        .vyms-switch { position: relative; display: inline-flex; cursor: pointer; }
        .vyms-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
        .vyms-switch-track { width: 42px; height: 24px; border-radius: 999px; background: var(--vy-hairline); transition: background .15s; display: inline-flex; align-items: center; padding: 0 3px; }
        .vyms-switch-knob { width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .15s; }
        .vyms-switch input:checked + .vyms-switch-track { background: var(--vy-accent); }
        .vyms-switch input:checked + .vyms-switch-track .vyms-switch-knob { transform: translateX(18px); }
        @media (max-width: 760px) { .vy-np-channels { grid-template-columns: 1fr; } }
    </style>
</div>
