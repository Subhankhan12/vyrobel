{{-- Operator Email Templates (#86) — the customization manager: the real guest emails, each with its real
     trigger, channel, an Active/off toggle (OFF genuinely stops the send), a Preview and an Edit link.

     ⚠️ NO ENGAGEMENT STATS. The design puts "72% opened" / "81% opened" / "38% → review" / "64% settle early"
     on every card. No open/click tracking is collected anywhere in the product, so every one of those figures
     would be invented (#39, founder decision A). The honest per-card facts are the live status + the real
     trigger, and the footer says plainly why no stats appear.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator :title="__('operator/email-templates.title')">
<x-operator.shell active="email-templates" :title="__('operator/email-templates.title')" :subtitle="__('operator/email-templates.subtitle')">

    @if (session('email_template_notice'))
        <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
            <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('email_template_notice') }}</div>
        </div>
    @endif

    {{-- The design's header row: breadcrumb left, gallery action right.
         ⚠️ #30 — /app/settings requires `properties.manage` but THIS page carries no permission gate, so the
         crumb links only when the target would actually open (the same trap caught on Preferences at P30). --}}
    <div class="vy-et-top vy-fade">
        <nav class="vy-et-crumb" aria-label="{{ __('operator/email-templates.breadcrumb_label') }}">
            @can('properties.manage')
                <a href="{{ route('app.settings') }}">{{ __('operator/email-templates.settings_crumb') }}</a>
                <span aria-hidden="true">/</span>
            @endcan
            <span aria-current="page">{{ __('operator/email-templates.title') }}</span>
        </nav>
        {{-- The design's "Preview emails". The gallery route already exists and was never linked from here. --}}
        <a href="{{ route('app.email-previews') }}" class="vy-btn vy-btn--ghost vy-et-gallery">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
            {{ __('operator/email-templates.preview_emails') }}
        </a>
    </div>

    <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
        <div style="font-size: 13px; color: var(--vy-ink);">{!! __('operator/email-templates.intro') !!}</div>
    </div>

    {{-- The design's card grid (it was a flat list of rows). --}}
    <div class="vy-et-grid vy-fade">
        @foreach ($templates as $tpl)
            <article class="vy-et-card @unless ($tpl['enabled']) is-off @endunless">
                {{-- Decorative letter-preview header (the design's thumbnail). Purely ornamental — it does not
                     claim to be the template's real content, so it is hidden from assistive tech. --}}
                <div class="vy-et-thumb" aria-hidden="true">
                    <span class="vy-et-thumb-brand">
                        <span class="vy-et-thumb-mark">{{ mb_substr($senderName, 0, 1) }}</span>{{ $senderName }}
                    </span>
                    <span class="vy-et-skel" style="width: 62%"></span>
                    <span class="vy-et-skel" style="width: 44%"></span>
                </div>

                <div class="vy-et-body">
                    <div class="vy-et-head">
                        <h2 class="vy-et-name">{{ $tpl['label'] }}</h2>
                        {{-- Status carries its WORD, never colour alone (1.4.1). --}}
                        <span class="vy-et-status @if ($tpl['enabled']) is-on @endif">{{ $tpl['enabled'] ? __('operator/email-templates.status_active') : __('operator/email-templates.status_off') }}</span>
                    </div>
                    <p class="vy-et-trigger">{{ $tpl['trigger'] }}</p>

                    <div class="vy-et-meta">
                        <span class="vy-et-chip">{{ $tpl['channel'] }}</span>
                        @if ($tpl['customized'])
                            <span class="vy-et-chip is-accent">{{ __('operator/email-templates.customized') }}</span>
                        @endif
                    </div>

                    <div class="vy-et-actions">
                        <a href="{{ route('app.email-previews.show', $tpl['preview']) }}" target="_blank" rel="noopener" class="vy-btn vy-btn--ghost vy-et-preview">{{ __('operator/email-templates.preview') }}</a>

                        @if ($canManage)
                            <a href="{{ route('app.email-templates.edit', $tpl['key']) }}" class="vy-btn vy-btn--accent vy-et-edit">{{ __('common.edit') }}</a>
                            {{-- The design's switch. Same real POST, same gated route — restyled, not rewired. --}}
                            <form method="POST" action="{{ route('app.email-templates.toggle', $tpl['key']) }}" class="vy-et-toggle-form">
                                @csrf
                                <input type="hidden" name="enabled" value="{{ $tpl['enabled'] ? '0' : '1' }}">
                                <button type="submit" class="vy-et-switch @if ($tpl['enabled']) is-on @endif"
                                    role="switch" aria-checked="{{ $tpl['enabled'] ? 'true' : 'false' }}"
                                    aria-label="{{ $tpl['enabled'] ? __('operator/email-templates.toggle_turn_off', ['template' => $tpl['label']]) : __('operator/email-templates.toggle_turn_on', ['template' => $tpl['label']]) }}">
                                    <span class="vy-et-knob" aria-hidden="true"></span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <p class="vy-line-sub" style="font-size: 11.5px; color: var(--vy-muted); margin-top: 14px;">{{ __('operator/email-templates.footer') }}</p>

</x-operator.shell>

<style>
    .vy-et-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
    .vy-et-crumb { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--vy-muted); }
    .vy-et-crumb a { color: var(--vy-accent-text); text-decoration: none; font-weight: 600; }
    .vy-et-crumb a:hover { text-decoration: underline; }
    .vy-et-gallery { padding: 8px 15px; font-size: 12.5px; }

    .vy-et-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
    .vy-et-card { background: var(--vy-surface, rgba(255,255,255,.7)); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md, 16px); overflow: hidden; box-shadow: var(--vy-shadow-sm); display: flex; flex-direction: column; }
    .vy-et-card.is-off { opacity: .78; }
    /* The thumbnail keeps the brand gradient; a paused template greys out so the state reads at a glance
       (the WORD in .vy-et-status is what actually carries it — the tint is reinforcement, not the signal). */
    .vy-et-thumb { background: linear-gradient(140deg, #C2410C, #9A330A); padding: 14px 16px 18px; display: flex; flex-direction: column; gap: 7px; }
    /* ⚠️ The paused tint must clear AA too. #8C837C measured 3.77 against the white brand text (11.5px → 4.5
       bar); no template is off in the demo, so it would only have shown the day someone paused one. */
    .vy-et-card.is-off .vy-et-thumb { background: linear-gradient(140deg, #7A716A, #5C544F); }
    .vy-et-thumb-brand { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; color: #fff; margin-bottom: 4px; }
    .vy-et-thumb-mark { width: 19px; height: 19px; border-radius: 6px; background: rgba(255,255,255,.22); display: grid; place-items: center; font-size: 10px; }
    .vy-et-skel { display: block; height: 6px; border-radius: 4px; background: rgba(255,255,255,.34); }
    .vy-et-body { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 7px; flex: 1; }
    .vy-et-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .vy-et-name { font-family: var(--vy-display, inherit); font-size: 14.5px; font-weight: 600; color: var(--vy-ink); margin: 0; }
    /* ⚠️ Was raw #1B7F5C on #E7F5EE — measured 4.41 at 11px, under the 4.5 bar. Darkened to AA. */
    .vy-et-status { font-size: 10.5px; font-weight: 700; border-radius: 999px; padding: 3px 10px; white-space: nowrap; color: var(--vy-muted); background: var(--vy-hairline); }
    .vy-et-status.is-on { color: #14663F; background: #E7F5EE; }
    .vy-et-trigger { font-size: 12.5px; color: var(--vy-muted); margin: 0; }
    .vy-et-meta { display: flex; gap: 6px; flex-wrap: wrap; }
    .vy-et-chip { font-size: 10.5px; font-weight: 600; color: var(--vy-muted); background: rgba(120,70,40,.07); border-radius: 999px; padding: 2px 9px; }
    .vy-et-chip.is-accent { color: var(--vy-accent-text); background: rgba(232,112,58,.12); }
    .vy-et-actions { display: flex; align-items: center; gap: 8px; margin-top: auto; padding-top: 4px; }
    .vy-et-preview, .vy-et-edit { padding: 7px 14px; font-size: 12px; text-decoration: none; }
    .vy-et-preview { flex: 1; justify-content: center; }
    .vy-et-toggle-form { margin: 0; margin-left: auto; display: flex; }
    /* The design's switch — a real submit button, restyled. Same route, same semantics. */
    .vy-et-switch { width: 42px; height: 24px; border-radius: 999px; border: none; background: var(--vy-hairline); padding: 0 3px; display: inline-flex; align-items: center; cursor: pointer; transition: background .15s; }
    .vy-et-switch.is-on { background: var(--vy-accent); justify-content: flex-end; }
    .vy-et-knob { width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
</style>
</x-layouts.operator>
