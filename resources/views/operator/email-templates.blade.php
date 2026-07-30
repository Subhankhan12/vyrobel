{{-- Operator Email Templates (#86) — the customization manager: the 5 real guest emails, each with its real
     trigger, channel, an Active/off toggle (OFF genuinely stops the send), a Preview and an Edit link. No
     fabricated open-rate (no send-tracking exists — honest status/trigger only).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator title="Guest emails">
<x-operator.shell active="email-templates" title="Guest emails" subtitle="Customize the emails your guests receive">

    @if (session('email_template_notice'))
        <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
            <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('email_template_notice') }}</div>
        </div>
    @endif

    <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
        <div style="font-size: 13px; color: var(--vy-ink);">These are the transactional emails Vyrobel sends your guests. Edit the wording, turn sections on or off, or pause an email entirely — <strong>your changes take effect on the next real send</strong>. Delivery goes through your email provider in production (account-gated); in dev it’s caught locally.</div>
    </div>

    <section class="vy-card vy-fade">
        @foreach ($templates as $tpl)
            <div style="display: flex; align-items: center; gap: 14px; padding: 16px 4px; border-bottom: 1px solid var(--vy-hairline); flex-wrap: wrap;">

                {{-- Status --}}
                <div style="flex: none; width: 76px;">
                    @if ($tpl['enabled'])
                        <span style="font-size: 11px; font-weight: 700; color: #1B7F5C; background: #E7F5EE; border-radius: 999px; padding: 3px 10px;">● Active</span>
                    @else
                        <span style="font-size: 11px; font-weight: 700; color: var(--vy-muted); background: var(--vy-hairline); border-radius: 999px; padding: 3px 10px;">○ Off</span>
                    @endif
                </div>

                {{-- Name + trigger --}}
                <div style="flex: 1 1 240px; min-width: 200px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 14.5px; font-weight: 700; color: var(--vy-ink);">{{ $tpl['label'] }}</span>
                        @if ($tpl['customized'])
                            <span style="font-size: 10.5px; font-weight: 600; color: var(--vy-accent); background: var(--vy-hairline); border-radius: 5px; padding: 1px 7px;">Customized</span>
                        @endif
                    </div>
                    <div style="font-size: 12.5px; color: var(--vy-muted); margin-top: 2px;">{{ $tpl['trigger'] }}</div>
                </div>

                {{-- Channel --}}
                <div style="flex: none; font-size: 12px; color: var(--vy-muted);">{{ $tpl['channel'] }}</div>

                {{-- Actions --}}
                <div style="flex: none; display: flex; align-items: center; gap: 8px; margin-left: auto;">
                    <a href="{{ route('app.email-previews.show', $tpl['preview']) }}" target="_blank" rel="noopener" style="font-size: 12px; font-weight: 600; color: var(--vy-accent); text-decoration: none;">Preview →</a>

                    @if ($canManage)
                        <form method="POST" action="{{ route('app.email-templates.toggle', $tpl['key']) }}" style="display: inline;">
                            @csrf
                            <input type="hidden" name="enabled" value="{{ $tpl['enabled'] ? '0' : '1' }}">
                            <button type="submit" class="vy-btn vy-btn--ghost" style="padding: 6px 12px; font-size: 12px;">{{ $tpl['enabled'] ? 'Turn off' : 'Turn on' }}</button>
                        </form>
                        <a href="{{ route('app.email-templates.edit', $tpl['key']) }}" class="vy-btn vy-btn--accent" style="padding: 6px 14px; font-size: 12px; text-decoration: none;">Edit</a>
                    @endif
                </div>
            </div>
        @endforeach

        <p class="vy-line-sub" style="font-size: 11.5px; color: var(--vy-muted); margin-top: 14px;">Owner &amp; operator alerts email through the same preference gate — see Notification preferences. Open/click tracking isn’t collected, so no engagement stats are shown.</p>
    </section>

</x-operator.shell>
</x-layouts.operator>
