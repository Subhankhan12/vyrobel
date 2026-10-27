{{-- Operator NOTIFICATION PREFERENCES (#90 exact-match) — the operator side of #79 on the SHARED prefs engine.
     EMAIL genuinely gates delivery (mutation-proven); quiet hours are real; always-on events lock. PUSH + SMS
     are HONEST-GATED — no infra exists, so they are display-only/disabled and NEVER submitted (#39). The real
     in_app channel (not a wireframe column) is preserved via hidden inputs. RAW-BLADE RULE: `@` follows a space/tag. --}}
<div>
    <x-operator.shell active="notifications" :title="__('livewire/operator/notification-preferences.page_title')" :subtitle="__('livewire/operator/notification-preferences.subtitle')" :viewer="$viewer">

        @if (session('prefs_notice')) <div class="vy-flash is-ok vy-fade">{{ session('prefs_notice') }}</div> @endif

        <form method="POST" action="{{ route('app.notification-preferences.update') }}" x-data="{ quietOn: @js($quietOn), digest: @js($digest && $digest['state']['email'] ? 'on' : 'off') }">
            @csrf

            {{-- The design's breadcrumb + a top-right Save (it sat only at the foot of a long page). --}}
            <div class="vy-np-top vy-fade">
                {{-- ⚠️ #30 — gate the CONTROL, not just the route. This page is self-only and carries NO
                     permission gate, but /app/settings requires `properties.manage`. An ungated crumb would
                     invite a 403 for every user who can set their own preferences but cannot manage the
                     property — which is most of them. Link it only when the route would actually open. --}}
                <nav class="vy-np-crumb" aria-label="{{ __('livewire/operator/notification-preferences.breadcrumb_label') }}">
                    @can('properties.manage')
                        <a href="{{ route('app.settings') }}">{{ __('livewire/operator/notification-preferences.crumb_settings') }}</a>
                        <span aria-hidden="true">/</span>
                    @endcan
                    <span aria-current="page">{{ __('livewire/operator/notification-preferences.crumb_notifications') }}</span>
                </nav>
                <button type="submit" class="vy-btn vy-btn--accent vy-np-save-top">{{ __('common.save_changes') }}</button>
            </div>

            {{-- ── Channel cards: Email (real) · Push (honest) · SMS (honest) ─────────────────────────── --}}
            <div class="vy-np-channels vy-fade">
                <div class="vy-np-chan">
                    <div class="vy-np-chan-icon">✉</div>
                    <div style="flex: 1;"><div class="vy-np-chan-name">{{ __('livewire/operator/notification-preferences.channel_email') }}</div><div class="vy-np-chan-detail">{{ $email !== '' ? $email : __('livewire/operator/notification-preferences.no_email_on_file') }}</div></div>
                    {{-- SESSIONS 01 · Part C — the "Unverified" branch was removed as unreachable: this page is behind the /app
                         group's `verified` middleware, and nothing ever clears a user's email_verified_at (checked
                         2026-09-25). The @if stays, so the badge still never claims "Verified" for an address that is not. --}}
                    @if ($emailVerified)
                        <span class="vy-np-status is-on" title="{{ __('livewire/operator/notification-preferences.email_verified_title') }}">{{ __('livewire/operator/notification-preferences.email_verified') }}</span>
                    @endif
                </div>
                <div class="vy-np-chan is-off" title="{{ __('livewire/operator/notification-preferences.push_not_set_up_title') }}">
                    <div class="vy-np-chan-icon">🔔</div>
                    <div style="flex: 1;"><div class="vy-np-chan-name">{{ __('livewire/operator/notification-preferences.channel_push') }} <span class="vy-np-soon">{{ __('livewire/operator/notification-preferences.not_set_up_tag') }}</span></div><div class="vy-np-chan-detail">{{ __('livewire/operator/notification-preferences.push_not_available') }}</div></div>
                    <span class="vy-np-status">{{ __('livewire/operator/notification-preferences.channel_off') }}</span>
                </div>
                <div class="vy-np-chan is-off" title="{{ __('livewire/operator/notification-preferences.sms_not_set_up_title') }}">
                    <div class="vy-np-chan-icon">💬</div>
                    <div style="flex: 1;"><div class="vy-np-chan-name">{{ __('livewire/operator/notification-preferences.channel_sms') }} <span class="vy-np-soon">{{ __('livewire/operator/notification-preferences.not_set_up_tag') }}</span></div><div class="vy-np-chan-detail">{{ __('livewire/operator/notification-preferences.sms_no_provider') }}</div></div>
                    <span class="vy-np-status">{{ __('livewire/operator/notification-preferences.channel_off') }}</span>
                </div>
            </div>

            {{-- The design's two-column body: the matrix on the left, quiet hours + digest in a right rail. --}}
            <div class="vy-np-cols">
            {{-- ── Notify-me grid: Email real · Push / SMS honest-gated ───────────────────────────────── --}}
            <div class="vy-card vy-fade" style="margin-top: 16px;">
                <div class="vy-np-grid vy-np-head">
                    <span>{{ __('livewire/operator/notification-preferences.notify_me_when') }}</span>
                    <span style="text-align: center;">{{ __('livewire/operator/notification-preferences.channel_email') }}</span>
                    <span style="text-align: center;">{{ __('livewire/operator/notification-preferences.channel_push') }}</span>
                    <span style="text-align: center;">{{ __('livewire/operator/notification-preferences.channel_sms') }}</span>
                </div>

                @foreach ($groups as $groupName => $events)
                    <div class="vy-np-group">{{ \App\Services\NotificationPreferences::groupLabel($groupName) }}</div>
                    @foreach ($events as $event)
                        <div class="vy-np-grid vy-np-row">
                            <div>
                                <div class="vy-np-ev">{{ $event['label'] }}@if ($event['always_on'])<span class="vy-np-always">{{ __('livewire/operator/notification-preferences.always_on') }}</span>@endif</div>
                                <div class="vy-np-ev-sub">{{ $event['description'] }}</div>
                            </div>

                            {{-- Preserve the real IN-APP pref (not a wireframe column; the feed keeps working). --}}
                            @if ($event['state']['in_app'])<input type="hidden" name="prefs[{{ $event['key'] }}][in_app]" value="1">@endif

                            {{-- EMAIL — REAL. Always-on locks on (the service forces it); otherwise a real toggle.
                                 ⚠️ The checkbox was `display:none`, which removed it from the tab order AND the
                                 a11y tree — the page's ONLY real controls could not be reached or operated by
                                 keyboard at all (WCAG 2.1.1, Level A). It is now visually hidden but FOCUSABLE,
                                 with a visible focus ring on the styled cell. --}}
                            <div style="text-align: center;">
                                @if ($event['always_on'])
                                    <input type="hidden" name="prefs[{{ $event['key'] }}][email]" value="1">
                                    <span class="vy-np-cell is-on is-locked">✓<span class="vy-sr-only">{{ __('livewire/operator/notification-preferences.email_always_on_sr', ['event' => $event['label']]) }}</span></span>
                                @else
                                    <label class="vy-np-cell-wrap">
                                        <input type="checkbox" name="prefs[{{ $event['key'] }}][email]" value="1" @checked($event['state']['email']) class="vy-np-check"
                                            aria-label="{{ __('livewire/operator/notification-preferences.email_me_when_aria', ['event' => $event['label']]) }}">
                                        <span class="vy-np-cell" aria-hidden="true"></span>
                                    </label>
                                @endif
                            </div>

                            {{-- PUSH / SMS — HONEST-GATED: display-only, never submitted (no name attribute).
                                 ⚠️ The "no provider" state was carried by opacity alone with only a `title`,
                                 which is not announced on a non-interactive span — the same 1.4.1 gap found on
                                 the feed at P29. The state now has real text for assistive tech. --}}
                            <div style="text-align: center;"><span class="vy-np-cell is-disabled"><span class="vy-sr-only">{{ __('livewire/operator/notification-preferences.push_not_set_up_sr') }}</span></span></div>
                            <div style="text-align: center;"><span class="vy-np-cell is-disabled"><span class="vy-sr-only">{{ __('livewire/operator/notification-preferences.sms_not_set_up_sr') }}</span></span></div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- The right rail (the design's second column). --}}
            <div class="vy-np-rail">
            {{-- ── Quiet hours (REAL — holds routine EMAIL during the window; in-app unaffected) ──────── --}}
            <div class="vy-card vy-fade" style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div><h2 class="vy-card-title" style="margin: 0;">{{ __('livewire/operator/notification-preferences.quiet_hours') }}</h2><p style="font-size: 12.5px; color: var(--vy-muted); margin: 3px 0 0;">{{ __('livewire/operator/notification-preferences.quiet_hours_help') }}</p></div>
                    <label class="vyms-switch"><input type="checkbox" name="quiet_hours_enabled" value="1" x-model="quietOn" @checked($quietOn)><span class="vyms-switch-track"><span class="vyms-switch-knob"></span></span></label>
                </div>
                <div class="vyn-grid3" style="margin-top: 12px;" :style="quietOn ? '' : 'opacity:.5;pointer-events:none;'">
                    <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="qs">{{ __('livewire/operator/notification-preferences.quiet_from') }}</label><input id="qs" type="time" name="quiet_start" value="{{ $quietStart }}" class="vy-input"></div>
                    <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="qe">{{ __('livewire/operator/notification-preferences.quiet_to') }}</label><input id="qe" type="time" name="quiet_end" value="{{ $quietEnd }}" class="vy-input"></div>
                </div>
            </div>

            {{-- ── Daily digest (HONEST — stores the real daily_summary email pref; not dispatched yet) ── --}}
            @if ($digest)
            <div class="vy-card vy-fade" style="margin-top: 16px;">
                <h2 class="vy-card-title" style="margin: 0 0 3px;">{{ __('livewire/operator/notification-preferences.digest_heading') }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">{!! __('livewire/operator/notification-preferences.digest_help') !!}</p>
                <input type="hidden" name="prefs[{{ $digest['key'] }}][email]" x-bind:value="digest === 'on' ? '1' : ''">
                @if ($digest['state']['in_app'])<input type="hidden" name="prefs[{{ $digest['key'] }}][in_app]" value="1">@endif
                <div class="vy-seg" style="max-width: 260px;">
                    <label class="vy-seg-opt" :class="digest === 'off' ? 'is-checked' : ''" x-on:click="digest = 'off'">{{ __('livewire/operator/notification-preferences.digest_off') }}</label>
                    <label class="vy-seg-opt" :class="digest === 'on' ? 'is-checked' : ''" x-on:click="digest = 'on'">{{ __('livewire/operator/notification-preferences.digest_morning') }}</label>
                </div>
            </div>
            @endif
            </div>{{-- /.vy-np-rail --}}
            </div>{{-- /.vy-np-cols --}}

            {{-- The foot Save is kept as well as the new top one: the matrix is long, and losing the
                 bottom control would make a keyboard user tab back up the whole grid to submit. --}}
            <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 22px;">{{ __('common.save_changes') }}</button>
            </div>
        </form>

    </x-operator.shell>

    <style>
        /* The design's header row: breadcrumb left, Save right. */
        .vy-np-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .vy-np-crumb { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--vy-ink); }   /* muted 3.38 on the page gradient; --vy-on-grad reaches only 4.17 here */
        .vy-np-crumb a { color: var(--vy-accent-text); text-decoration: none; font-weight: 600; }
        .vy-np-crumb a:hover { text-decoration: underline; }
        .vy-np-save-top { padding: 9px 20px; font-size: 13px; }
        /* The design's two columns: the matrix left, quiet hours + digest in a rail. */
        .vy-np-cols { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        .vy-np-rail { display: flex; flex-direction: column; }
        @media (max-width: 1100px) { .vy-np-cols { grid-template-columns: 1fr; } }
        .vy-np-channels { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .vy-np-chan { display: flex; gap: 11px; align-items: center; background: var(--vy-glass, rgba(255,252,248,.6)); border: 1px solid var(--vy-hairline); border-radius: 14px; padding: 14px; }
        .vy-np-chan.is-off { opacity: .72; }
        .vy-np-chan-icon { width: 34px; height: 34px; border-radius: 10px; background: rgba(217,102,46,.12); display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .vy-np-chan-name { font-size: 13.5px; font-weight: 600; }
        .vy-np-chan-detail { font-size: 11.5px; color: var(--vy-muted); }   /* faint measured 3.95–4.15 on this card */
        .vy-np-status { font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; background: var(--vy-hairline); color: var(--vy-muted); }
        .vy-np-status.is-on { background: rgba(31,110,75,.14); color: #14532D; }   /* #1F6E4B measured 3.50 on its own tint */
        .vy-np-soon { font-size: 9px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-ink); border: 1px dashed var(--vy-hairline); border-radius: 6px; padding: 1px 5px; margin-left: 4px; }
        .vy-np-grid { display: grid; grid-template-columns: 1fr 64px 64px 64px; gap: 10px; align-items: center; }
        .vy-np-head { padding-bottom: 10px; border-bottom: 1px solid var(--vy-hairline); font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--vy-muted); }
        .vy-np-group { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--vy-ink); font-weight: 700; margin: 16px 0 2px; }
        .vy-np-row { padding: 11px 0; border-bottom: 1px solid var(--vy-hairline); }
        .vy-np-ev { font-size: 13px; font-weight: 600; color: var(--vy-ink); }
        .vy-np-ev-sub { font-size: 11.5px; color: var(--vy-muted); }
        .vy-np-always { font-size: 10px; color: var(--vy-muted); font-weight: 400; margin-left: 6px; }
        .vy-np-cell-wrap { display: inline-flex; cursor: pointer; }
        /* ⚠️ WCAG 2.1.1 — this was `display: none`, which takes the input out of the tab order and the a11y
           tree: the page's only real controls were unoperable by keyboard. Visually hidden but FOCUSABLE. */
        .vy-np-check { position: absolute; opacity: 0; width: 26px; height: 26px; margin: 0; cursor: pointer; }
        .vy-np-check:focus-visible + .vy-np-cell { outline: 2px solid var(--vy-accent-text); outline-offset: 2px; }
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
        /* Three channel cards need real width — below this the "not set up" tag and the email address wrap
           mid-phrase, so they stack at the same breakpoint the two columns collapse. */
        @media (max-width: 1100px) { .vy-np-channels { grid-template-columns: 1fr; } }
    </style>
</div>
