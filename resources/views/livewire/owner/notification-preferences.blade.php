{{-- Owner Notifications — exact-match rebuild (#79) to the decoded "Owner Notifications" wireframe, on the REAL
     NotificationPreferences engine. The grid is EMAIL + SMS (the external channels); IN-APP is dropped from the
     UI (the portal feed always records — "statements kept in your portal regardless") but PRESERVED via hidden
     inputs so the feed is never silently turned off.
     🔒 EMAIL is REAL (stored + honored by the owner Mailables via allows()). SMS is HONEST-GATED — display-only,
        clearly "not active until SMS is set up", NEVER a toggle that implies a send (#39). The urgent-bypass
        guarantee is REAL (account_security is always_on → can never be suppressed). #20 self-only controller POST.
     The owner's OWN email/phone are shown (their contact — NOT a guest #28 concern).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="notifications"
               :breadcrumb="[['label' => 'Settings', 'url' => route('owner.settings')], ['label' => 'Notifications']]"
               title="Notifications"
               :titleLg="true"
               :subtitle="'Choose what we tell you and where. Statements are always kept in your portal regardless.'"
               :viewer="$viewer">
    <style>
        .vy-nt-wrap { max-width:760px; }
        .vy-nt-card { background:rgba(255,252,248,.58); backdrop-filter:blur(22px) saturate(1.15); border:1px solid rgba(255,255,255,.75); border-radius:20px; box-shadow:0 14px 40px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65); }
        .vy-nt-h2 { font-family:var(--vy-display); font-weight:500; font-size:16px; letter-spacing:-.01em; }
        .vy-nt-reach { flex:1 1 200px; display:flex; align-items:center; gap:12px; padding:13px 14px; border-radius:14px; background:rgba(255,255,255,.55); border:1px solid rgba(120,70,40,.12); }
        .vy-nt-ricon { width:34px; height:34px; border-radius:10px; background:rgba(217,102,46,.13); color:#B4400F; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .vy-nt-sw { width:40px; height:24px; border-radius:30px; border:none; cursor:pointer; position:relative; flex-shrink:0; transition:background .16s; padding:0; }
        .vy-nt-sw-knob { position:absolute; top:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 2px 6px rgba(60,30,10,.25); transition:left .16s; }
        .vy-nt-cell { width:22px; height:22px; border-radius:7px; display:flex; align-items:center; justify-content:center; padding:0; }
        .vy-nt-row { display:grid; grid-template-columns:1fr 58px 58px; gap:10px; align-items:center; }
        .vy-nt-note { display:flex; align-items:center; gap:12px; padding:14px 18px; border-radius:16px; background:rgba(120,70,40,.05); border:1px solid rgba(120,70,40,.1); }
        .vy-nt-toast { position:fixed; bottom:28px; right:34px; z-index:60; display:flex; align-items:center; gap:13px; padding:14px 16px 14px 14px; border-radius:15px; background:rgba(255,252,248,.98); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,.85); box-shadow:0 18px 50px rgba(120,70,40,.28); }
    </style>

    <div class="vy-nt-wrap vy-fade"
         x-data="{
             emailOn: true,
             dirty: false,
             toast: {{ session('prefs_notice') ? 'true' : 'false' }},
             em: { @foreach ($events as $e)@unless ($e['always_on'])'{{ $e['key'] }}': {{ $e['email'] ? 'true' : 'false' }}, @endunless @endforeach },
             touch() { this.dirty = true; },
         }"
         x-init="if (toast) setTimeout(() => toast = false, 3500)">

        <form method="POST" action="{{ route('owner.notification-preferences.update') }}">
            @csrf

            {{-- ══ WHERE WE REACH YOU ══ --}}
            <div class="vy-nt-card" style="padding:20px 22px;margin-bottom:20px">
                <div class="vy-nt-h2" style="margin-bottom:4px">Where we reach you</div>
                <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:14px">
                    {{-- Email — REAL: the owner's notification email + a master toggle (bulk email on/off) --}}
                    <div class="vy-nt-reach">
                        <span class="vy-nt-ricon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2.5"/><path d="M22 7 12 13 2 7"/></svg></span>
                        <span style="min-width:0;flex:1">
                            <span style="display:block;font-size:12.5px;font-weight:500">Email</span>
                            <span style="display:block;font-size:11px;color:var(--vy-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $email }}</span>
                        </span>
                        <button type="button" class="vy-nt-sw" @click="emailOn = ! emailOn; touch()" :style="emailOn ? 'background:linear-gradient(140deg,#D14102,#C2410C)' : 'background:rgba(120,70,40,.18)'" :aria-pressed="emailOn ? 'true' : 'false'" aria-label="Email channel">
                            <span class="vy-nt-sw-knob" :style="emailOn ? 'left:19px' : 'left:3px'"></span>
                        </button>
                    </div>
                    {{-- SMS — HONEST-GATED: the owner's own number (or an honest placeholder); the switch is disabled
                         with a clear "not set up" state. NEVER a toggle that implies a send (#39). --}}
                    <div class="vy-nt-reach" style="opacity:.72" title="SMS delivery isn't set up yet">
                        <span class="vy-nt-ricon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18.5h4"/></svg></span>
                        <span style="min-width:0;flex:1">
                            <span style="display:block;font-size:12.5px;font-weight:500">SMS <span style="font-size:10px;color:var(--vy-muted);font-weight:400">· not set up</span></span>
                            <span style="display:block;font-size:11px;color:var(--vy-muted);font-variant-numeric:tabular-nums">{{ $phone ?? 'No number on file yet' }}</span>
                        </span>
                        <span class="vy-nt-sw" style="background:rgba(120,70,40,.14);cursor:not-allowed" aria-hidden="true">
                            <span class="vy-nt-sw-knob" style="left:3px;opacity:.6"></span>
                        </span>
                    </div>
                </div>
                <div style="font-size:11px;color:var(--vy-muted);margin-top:10px">SMS delivery isn’t active yet — we’ll use these preferences once it’s set up.</div>
            </div>

            {{-- ══ WHAT YOU HEAR ABOUT — the per-event × Email/SMS matrix ══ --}}
            <div class="vy-nt-card" style="padding:8px 22px 16px;margin-bottom:20px">
                <div class="vy-nt-row" style="padding:16px 0 10px">
                    <span class="vy-nt-h2">What you hear about</span>
                    <span style="text-align:center;font-size:10px;font-weight:500;letter-spacing:.06em;text-transform:uppercase" :style="emailOn ? 'color:var(--vy-accent-text)' : 'color:var(--vy-muted)'">Email</span>
                    {{-- ⚠️ This header measured 1.63 — the worst contrast on the page, on the label of the column
                         the owner most needs explained. It also said only "SMS", leaving the whole empty column
                         unexplained inside the grid; the state now travels with the header. --}}
                    <span style="text-align:center;font-size:10px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-muted)">SMS <span style="text-transform:none;letter-spacing:0;font-weight:400">· not set up</span></span>
                </div>

                @foreach ($events as $e)
                    <div class="vy-nt-row" style="padding:14px 0;border-top:1px solid rgba(120,70,40,.1)">
                        <span style="min-width:0">
                            <span style="display:block;font-size:13.5px;font-weight:500">{{ $e['label'] }}@if ($e['always_on'])<span style="font-size:10px;color:var(--vy-muted);font-weight:400;margin-left:6px">Always on</span>@endif</span>
                            <span style="display:block;font-size:11.5px;color:var(--vy-muted);margin-top:1px">{{ $e['description'] }}</span>
                        </span>

                        {{-- Preserve the current IN-APP pref (column dropped from the UI, feed unaffected). --}}
                        @if ($e['in_app'])<input type="hidden" name="prefs[{{ $e['key'] }}][in_app]" value="1">@endif

                        {{-- EMAIL cell — REAL --}}
                        @if ($e['always_on'])
                            {{-- Locked ON (always_on) — save() forces it; render fixed-on, submit "1". --}}
                            <input type="hidden" name="prefs[{{ $e['key'] }}][email]" value="1">
                            <span style="display:flex;justify-content:center">
                                <span class="vy-nt-cell" style="border:1px solid transparent;background:linear-gradient(140deg,#D14102,#C2410C);cursor:default" title="Always on">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                </span>
                            </span>
                        @else
                            <input type="hidden" name="prefs[{{ $e['key'] }}][email]" :value="(emailOn && em['{{ $e['key'] }}']) ? '1' : ''">
                            <span style="display:flex;justify-content:center">
                                <button type="button" class="vy-nt-cell"
                                        @click="if (emailOn) { em['{{ $e['key'] }}'] = ! em['{{ $e['key'] }}']; touch(); }"
                                        :disabled="! emailOn"
                                        :style="((emailOn && em['{{ $e['key'] }}']) ? 'border:1px solid transparent;background:linear-gradient(140deg,#D14102,#C2410C);' : 'border:1px solid rgba(120,70,40,.2);background:rgba(255,255,255,.7);') + (emailOn ? 'cursor:pointer' : 'cursor:not-allowed')"
                                        aria-label="{{ $e['label'] }} · Email">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" :style="(emailOn && em['{{ $e['key'] }}']) ? 'opacity:1' : 'opacity:0'"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                </button>
                            </span>
                        @endif

                        {{-- SMS cell — HONEST-GATED: display-only, disabled, off-appearance. No name → never submitted. --}}
                        {{-- ⚠️ An aria-label on a NON-INTERACTIVE span is not reliably exposed — the same P29
                             finding as the unread dot. The operator prefs page already announces this state with
                             real .vy-sr-only TEXT; matched here (the class reached the owner shell at Owner-6). --}}
                        <span style="display:flex;justify-content:center" title="Not active until SMS is set up">
                            <span class="vy-nt-cell" style="border:1px solid rgba(120,70,40,.16);background:rgba(255,255,255,.4);cursor:not-allowed;opacity:.55"><span class="vy-sr-only">{{ $e['label'] }} — SMS not set up</span></span>
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- ══ THE GUARANTEE (real — account_security is always_on; no marketing event exists) ══ --}}
            <div class="vy-nt-note" style="margin-bottom:18px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-muted)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M12 3a6 6 0 0 0-6 6c0 4-1.5 5-2 6h16c-.5-1-2-2-2-6a6 6 0 0 0-6-6z"/><path d="M10 20.5a2 2 0 0 0 4 0"/></svg>
                <span style="min-width:0;flex:1;font-size:12px;color:var(--vy-muted);line-height:1.5">We never send marketing without asking, and urgent account or payout alerts always come through even if a channel is off.</span>
            </div>

            {{-- ══ QUIET HOURS (real, kept — the wireframe omits it; a shipped feature we don't drop) ══ --}}
            <div class="vy-nt-card" style="padding:16px 20px;margin-bottom:18px" x-data="{ on: {{ $quiet['on'] ? 'true' : 'false' }} }">
                <label style="display:flex;align-items:center;gap:9px;cursor:pointer">
                    <input type="checkbox" name="quiet_hours_enabled" value="1" x-model="on" @change="touch()" style="width:16px;height:16px;accent-color:var(--vy-accent)">
                    <span style="font-weight:600;font-size:13.5px;color:var(--vy-ink)">Quiet hours</span>
                </label>
                <p style="font-size:11.5px;color:var(--vy-muted);margin:6px 0 10px">Hold routine emails during these hours — you’ll still see everything in-app, and urgent alerts always come through.</p>
                <div x-show="on" x-cloak style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <label style="font-size:12px;color:var(--vy-muted)">From <input type="time" name="quiet_start" value="{{ $quiet['start'] }}" @change="touch()" style="padding:5px 8px;border-radius:8px;border:1px solid rgba(120,70,40,.18)"></label>
                    <label style="font-size:12px;color:var(--vy-muted)">to <input type="time" name="quiet_end" value="{{ $quiet['end'] }}" @change="touch()" style="padding:5px 8px;border-radius:8px;border:1px solid rgba(120,70,40,.18)"></label>
                </div>
            </div>

            {{-- ══ SAVE ══ --}}
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px">
                <span style="font-size:12px;margin-right:auto" :style="dirty ? 'color:#B4400F' : 'color:var(--vy-muted)'" x-text="dirty ? 'Unsaved changes' : 'All preferences saved'">All preferences saved</span>
                <button type="submit" style="padding:11px 22px;border-radius:30px;background:linear-gradient(140deg,#D14102,#C2410C);border:none;color:#fff;font-family:var(--vy-body,Inter),sans-serif;font-size:13px;font-weight:500;cursor:pointer;box-shadow:0 6px 18px rgba(194,65,12,.35)">Save changes</button>
            </div>
        </form>

        {{-- ══ TOAST (on a real save — the PRG flash) ══ --}}
        <div x-show="toast" x-cloak x-transition.opacity.duration.200ms class="vy-nt-toast" @click.outside="toast = false">
            <span style="width:34px;height:34px;border-radius:10px;background:rgba(31,138,91,.14);color:#1F7A50;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <span style="min-width:0">
                <span style="display:block;font-size:13px;font-weight:600;color:var(--vy-ink)">Preferences saved</span>
                <span style="display:block;font-size:11.5px;color:var(--vy-muted);margin-top:1px">Your notification preferences are live</span>
            </span>
            <button type="button" @click="toast = false" aria-label="Dismiss" style="width:26px;height:26px;border-radius:50%;background:rgba(120,70,40,.07);border:none;color:var(--vy-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </button>
        </div>
    </div>
</x-owner.shell>
