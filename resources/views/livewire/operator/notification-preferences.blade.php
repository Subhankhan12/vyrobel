{{-- Operator Notification Preferences (Phase 3 · Sub-gate 2) — the AUTHENTICATED user's own per-event,
     per-channel toggles. Self-only POST → controller. These GENUINELY gate delivery; always-on events are
     locked. Email delivery arrives with the email templates (Sub-gate 4); the toggle persists now.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="notifications" title="Notification preferences" :subtitle="'How Vyrobel reaches you'" :viewer="$viewer">

        @if (session('prefs_notice'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
                <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('prefs_notice') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('app.notification-preferences.update') }}">
            @csrf

            <div class="vy-card vy-fade">
                <div style="display:grid;grid-template-columns:1fr 70px 70px;gap:10px;align-items:center;padding-bottom:8px;border-bottom:1px solid var(--vy-hairline)">
                    <span style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--vy-muted)">Notify me when</span>
                    <span style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--vy-muted);text-align:center">In-app</span>
                    <span style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--vy-muted);text-align:center">Email</span>
                </div>

                @foreach ($groups as $groupName => $events)
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--vy-accent);font-weight:700;margin:14px 0 4px">{{ $groupName }}</div>
                    @foreach ($events as $event)
                        <div style="display:grid;grid-template-columns:1fr 70px 70px;gap:10px;align-items:center;padding:9px 0;border-bottom:1px solid var(--vy-hairline)">
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--vy-ink)">{{ $event['label'] }}@if ($event['always_on'])<span style="font-size:10px;color:var(--vy-muted);font-weight:400;margin-left:6px">Always on</span>@endif</div>
                                <div style="font-size:11.5px;color:var(--vy-muted)">{{ $event['description'] }}</div>
                            </div>
                            @foreach ($channels as $channel)
                                <div style="text-align:center">
                                    <input type="checkbox" name="prefs[{{ $event['key'] }}][{{ $channel }}]" value="1"
                                           @checked($event['state'][$channel]) @disabled($event['always_on'])
                                           aria-label="{{ $event['label'] }} · {{ $channel }}"
                                           style="width:17px;height:17px;accent-color:var(--vy-accent,#b5654a);cursor:{{ $event['always_on'] ? 'not-allowed' : 'pointer' }}">
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endforeach

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:10px">
                    <span class="vy-line-sub" style="font-size:11.5px;color:var(--vy-muted)">In-app and email delivery are both live — anything left on for email also lands in your inbox. Push &amp; SMS: coming later.</span>
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding:9px 20px">Save changes</button>
                </div>
            </div>
        </form>

    </x-operator.shell>
</div>
