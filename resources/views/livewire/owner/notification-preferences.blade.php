{{-- Owner Notification Preferences (Phase 3 · Sub-gate 2) — the owner's own per-event, per-channel toggles.
     Self-only POST → controller. Account & security is always-on (locked). Email delivery arrives with the email
     templates (Sub-gate 4). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="notifications" title="Notification preferences" :subtitle="'Choose what we tell you and where'" :viewer="$viewer">

    @if (session('prefs_notice'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('prefs_notice') }}</div>
        </div>
    @endif

    <p class="vy-line-sub" style="font-size:13px;margin-bottom:16px">Statements are always kept in your portal regardless. Urgent account and payout alerts always come through.</p>

    <form method="POST" action="{{ route('owner.notification-preferences.update') }}">
        @csrf

        <section class="vy-card vy-fade">
            <div style="display:grid;grid-template-columns:1fr 70px 70px;gap:10px;align-items:center;padding-bottom:8px;border-bottom:1px solid var(--vy-hairline)">
                <span class="vy-eyebrow">What you hear about</span>
                <span class="vy-eyebrow" style="text-align:center">In-app</span>
                <span class="vy-eyebrow" style="text-align:center">Email</span>
            </div>

            @foreach ($groups as $groupName => $events)
                <div class="vy-eyebrow" style="color:var(--vy-accent);margin:14px 0 4px">{{ $groupName }}</div>
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
                                       style="width:17px;height:17px;accent-color:var(--vy-accent);cursor:{{ $event['always_on'] ? 'not-allowed' : 'pointer' }}">
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endforeach

            {{-- Quiet hours (Wave B · GATE 6) — REAL: routine EMAIL is held during the window; the in-app feed still
                 records everything, and security alerts always come through. --}}
            <div x-data="{ on: {{ $quiet['on'] ? 'true' : 'false' }} }" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--vy-hairline)">
                <label style="display:flex;align-items:center;gap:9px;cursor:pointer">
                    <input type="checkbox" name="quiet_hours_enabled" value="1" x-model="on">
                    <span style="font-weight:600;font-size:13.5px;color:var(--vy-ink)">Quiet hours</span>
                </label>
                <p class="vy-line-sub" style="font-size:11.5px;margin:6px 0 10px">Hold routine emails during these hours — you’ll still see everything in-app, and security alerts always come through.</p>
                <div x-show="on" x-cloak style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <label style="font-size:12px;color:var(--vy-muted)">From <input type="time" name="quiet_start" value="{{ $quiet['start'] }}" class="vy-input" style="width:auto;padding:5px 8px"></label>
                    <label style="font-size:12px;color:var(--vy-muted)">to <input type="time" name="quiet_end" value="{{ $quiet['end'] }}" class="vy-input" style="width:auto;padding:5px 8px"></label>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:10px">
                <span class="vy-line-sub" style="font-size:11.5px">In-app and email delivery are both live — anything left on for email also lands in your inbox.</span>
                <button type="submit" class="vy-btn vy-btn--accent" style="padding:9px 20px">Save changes</button>
            </div>
        </section>
    </form>
</x-owner.shell>
