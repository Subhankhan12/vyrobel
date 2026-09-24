{{-- ADMIN 01 · PHASE 1 — the platform KILL SWITCHES. Stored in the database (never .env — L14), so a flip takes
     effect on the next request without a deploy. A missing row is ON. ⚠️ None of them touches /admin. --}}
@php
    $admin = auth('admin')->user();
    $canFlip = $admin?->may('switches.manage') ?? false;
@endphp
<x-admin.shell active="switches" title="Kill switches"
    subtitle="Stop new sign-ups, or sign-in through one provider, without a deploy. Every flip is audited with a reason.">


    <div class="ad-card" style="margin-bottom: 18px;">
        <p class="ad-stat-sub" style="margin: 0; line-height: 1.6;">
            Every switch is <strong>on</strong> unless someone turns it off here — including right after a deploy.
            None of them can affect this console. Turning off <strong>sign-in</strong> with a provider locks out
            anyone whose only way in is that provider, so those switches show the count and ask twice.
        </p>
    </div>

    <div style="display: grid; gap: 14px;">
        @foreach ($catalogue as $key => $meta)
            @php $state = $states[$key]; @endphp
            <div class="ad-card" style="padding: 18px 22px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div style="min-width: 0; flex: 1 1 320px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h2 class="ad-panel-title" style="margin: 0;">{{ $meta['label'] }}</h2>
                            <span class="ad-hpill {{ $state['on'] ? 'ad-hpill--green' : 'ad-hpill--amber' }}">{{ $state['on'] ? 'On' : 'Off' }}</span>
                        </div>
                        <p class="ad-stat-sub" style="margin: 6px 0 0;">{{ $meta['controls'] }}</p>
                        <p class="ad-reason-hint" style="margin: 6px 0 0;">
                            @if ($state['updated_at'])
                                Last changed {{ $state['updated_at'] }} UTC{{ $state['updated_by'] && isset($updaters[$state['updated_by']]) ? ' by '.$updaters[$state['updated_by']] : '' }}.
                            @else
                                Never changed — on by default.
                            @endif
                        </p>
                        @if (isset($lockouts[$key]))
                            <p class="ad-stat-sub" style="margin: 8px 0 0;">
                                @if ($lockouts[$key] === 0)
                                    No account relies on this provider alone right now — every one also has a password or another way in.
                                @else
                                    Turning this off would lock out <strong>{{ $lockouts[$key] }}</strong>
                                    account{{ $lockouts[$key] === 1 ? '' : 's' }} — no password, and this provider is their only way in.
                                @endif
                            </p>
                        @endif
                    </div>

                    @if ($canFlip)
                        <form method="POST" action="{{ route('admin.switches.update', $key) }}" style="flex: 1 1 300px; max-width: 420px;" data-guard-submit>
                            @csrf
                            <input type="hidden" name="on" value="{{ $state['on'] ? '0' : '1' }}">
                            <label class="ad-label" for="reason-{{ $loop->index }}">Reason</label>
                            <input aria-label="Reason" class="ad-input" id="reason-{{ $loop->index }}" name="reason" required minlength="5" maxlength="500"
                                   placeholder="Why are you turning this {{ $state['on'] ? 'off' : 'on' }}?">
                            @if ($state['on'] && isset($lockouts[$key]))
                                <label style="display: flex; gap: 8px; align-items: flex-start; font-size: 12.5px; margin: 10px 0 0; color: var(--ad-ink);">
                                    <input type="checkbox" name="confirm_lockout" value="1" required style="margin-top: 2px;">
                                    {{-- ⚠️ ADMIN 03 · F20 — still required at zero (the count can change between loading this page and
                                         pressing the button — SwitchController), but it no longer asks him to "understand" locking out 0. --}}
                                    @if ($lockouts[$key] === 0)
                                        I understand anyone who signs in only this way from now until it is back on will be locked out.
                                    @else
                                        I understand this locks out {{ $lockouts[$key] }} account{{ $lockouts[$key] === 1 ? '' : 's' }} until it is turned back on.
                                    @endif
                                </label>
                            @endif
                            <button type="submit" class="ad-btn {{ $state['on'] ? 'ad-btn--danger' : '' }} ad-btn--sm" style="margin-top: 10px;">
                                Turn {{ $state['on'] ? 'off' : 'on' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-admin.shell>
