{{-- Guest Account (Guest Portal · GATE 3) — the authed guest's OWN profile, rebuilt to the wireframe's
     hero + 5-KPI + 3-column bento. Self-only (resolved from the guest guard). All figures REAL: credit via
     GuestWalletService, stays via reservations, waitlists via GuestWaitlistEntry, trip-offers via
     marketing_consent (#87). HONEST: saved cards = 0 (no vault, no raw PAN — D3); SMS disabled (no provider);
     delete = a 14-DAY SCHEDULED, reversible erase (D2). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="account" :title="__('guest/account.page_title')">
    <style>
        .ac-hero { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; margin-bottom: 18px; }
        .ac-av { width: 56px; height: 56px; border-radius: 50%; background: var(--vy-accent); color: #fff;   /* initials are TEXT: 3.48 on --vy-grad -> 5.18 solid */ display: grid; place-items: center; font-family: var(--vy-display); font-weight: 600; font-size: 20px; box-shadow: var(--vy-shadow-accent); flex: none; }
        .ac-since { display: inline-flex; align-items: center; gap: 7px; padding: 3px 11px; border-radius: 20px; background: rgba(79,122,70,.13); font-size: 11px; font-weight: 600; color: var(--vy-green-text); margin-bottom: 6px; }
        .ac-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(96px, 1fr)); gap: 10px; flex: 1.4; min-width: 320px; }
        .ac-kpi { padding: 12px 13px; border-radius: 14px; background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); }
        .ac-kpi b { display: block; font-family: var(--vy-display); font-weight: 600; font-size: 20px; letter-spacing: -.02em; }
        .ac-kpi span { display: block; font-size: 11px; color: var(--vy-muted); margin-top: 3px; }
        .ac-bento { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; align-items: start; }
        .ac-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .ac-toggle { width: 40px; height: 23px; border-radius: 20px; border: none; position: relative; cursor: pointer; flex: none; transition: background .15s; }
        .ac-toggle .knob { position: absolute; top: 3px; width: 17px; height: 17px; border-radius: 50%; background: #fff; box-shadow: 0 2px 5px rgba(60,30,10,.25); transition: left .15s; }
        .ac-danger { border-color: rgba(143,49,16,.22) !important; }
        .ac-chip { position: relative; }
        .ac-chip input { position: absolute; opacity: 0; pointer-events: none; }
        /* An opacity:0 radio shows an INVISIBLE focus ring, so the chip carries one. Fourth recurrence of
           the same pattern in this realm (G5's add-on toggles, G8's star input, here) — every time a
           control is hidden for styling, its focus goes with it unless the visible proxy takes it over. */
        .ac-chip:focus-within span { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .ac-chip span { display: inline-block; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 500; color: var(--vy-muted); cursor: pointer; }
        .ac-chip input:checked + span { background: #fff; color: var(--vy-ink); box-shadow: 0 1px 3px rgba(60,30,10,.1); font-weight: 600; }
        .ac-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 0; border-top: 1px solid var(--vy-hairline); }
    </style>

    @if (session('status'))<div class="vy-notice" style="margin-bottom:14px">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="vy-error" role="alert" style="margin-bottom:14px">{{ $errors->first() }}</div>@endif

    {{-- Hero + KPIs --}}
    <div class="vy-card ac-hero">
        <div style="display:flex;align-items:center;gap:16px;flex:1;min-width:240px">
            <div class="ac-av">{{ $hero['initials'] }}</div>
            <div style="min-width:0">
                <div class="ac-since"><span style="width:7px;height:7px;border-radius:50%;background:#4F7A46;display:inline-block"></span> {{ __('guest/account.member_since', ['date' => $hero['member_since']]) }}</div>
                {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
                <h1 style="font-family:var(--vy-display);font-weight:600;font-size:20px;letter-spacing:-.02em;margin:0">{{ $hero['name'] }}</h1>
                <div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ $hero['email'] }}</div>
            </div>
        </div>
        <div class="ac-kpis">
            <a class="ac-kpi" href="{{ route('guest.wallet') }}" style="text-decoration:none"><b>{{ $kpi['credit'] }}</b><span>{{ __('guest/account.kpi_credit') }}</span></a>
            <div class="ac-kpi"><b>{{ $kpi['stays'] }}</b><span>{{ __('guest/account.kpi_stays') }}</span></div>
            <div class="ac-kpi"><b>{{ $kpi['cards'] }}</b><span>{{ __('guest/account.kpi_saved_cards') }}</span></div>
            <a class="ac-kpi" href="{{ route('guest.saved') }}" style="text-decoration:none"><b>{{ $kpi['waitlists'] }}</b><span>{{ __('guest/account.kpi_waitlists') }}</span></a>
            <div class="ac-kpi"><b style="font-size:16px">{{ strtoupper($profile['language'] ?: '—') }}</b><span>{{ __('guest/account.language') }}</span></div>
        </div>
    </div>

    <div class="ac-bento">
        {{-- COLUMN 1 — personal details + delete --}}
        <div class="ac-col">
            <section class="vy-card">
                <div class="vy-eyebrow" style="margin-bottom:12px">{{ __('guest/account.personal_details') }}</div>
                <form method="POST" action="{{ route('guest.account.update') }}" data-guard-submit novalidate>
                    @csrf
                    <div class="vy-field"><label class="vy-label" for="first_name">{{ __('guest/account.first_name') }}</label><input class="vy-input" id="first_name" name="first_name" value="{{ old('first_name', $profile['first_name']) }}" required></div>
                    <div class="vy-field"><label class="vy-label" for="last_name">{{ __('guest/account.last_name') }}</label><input class="vy-input" id="last_name" name="last_name" value="{{ old('last_name', $profile['last_name']) }}" required></div>
                    <div class="vy-field"><label class="vy-label" for="email">{{ __('common.email') }}</label><input class="vy-input" id="email" value="{{ $profile['email'] }}" disabled><span style="display:block;font-size:11px;color:var(--vy-faint);margin-top:5px">{{ __('guest/account.email_is_sign_in') }}</span></div>
                    <div class="vy-field"><label class="vy-label" for="phone">{{ __('common.phone') }}</label><input class="vy-input" id="phone" name="phone" value="{{ old('phone', $profile['phone']) }}" autocomplete="tel"></div>
                    <div class="vy-field"><label class="vy-label" for="nationality">{{ __('guest/account.country_code') }}</label><input class="vy-input" id="nationality" name="nationality" value="{{ old('nationality', $profile['nationality']) }}" maxlength="2"></div>
                    <input type="hidden" name="language" value="{{ $profile['language'] }}">
                    <x-busy-btn class="vy-btn" :busy-label="__('guest/account.saving')">{{ __('guest/account.save_details') }}</x-busy-btn>
                </form>
                <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--vy-hairline)">
                    <div class="vy-eyebrow" style="margin-bottom:8px">{{ __('auth/common.password') }}</div>
                    <a class="vy-link" href="#change-password" style="font-size:12.5px;color:var(--vy-accent-text)" onclick="document.getElementById('change-password').style.display='block';this.style.display='none';return false">{{ __('guest/account.change_password_link') }}</a>
                    <form id="change-password" method="POST" action="{{ route('guest.account.password') }}" data-guard-submit novalidate style="display:none;margin-top:10px">
                        @csrf
                        <div class="vy-field"><label class="vy-label" for="current_password">{{ __('guest/account.current_password') }}</label><input class="vy-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required></div>
                        <div class="vy-field"><label class="vy-label" for="password">{{ __('guest/account.new_password') }}</label><input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" required></div>
                        <div class="vy-field"><label class="vy-label" for="password_confirmation">{{ __('guest/account.confirm_new_password') }}</label><input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></div>
                        {{-- DO13-4: this form enforced a composition rule it never stated, so a rejection had no visible reason. --}}
                        <div class="vy-foot" style="margin:-4px 0 10px">{{ \App\Support\Auth\PasswordPolicy::hint() }}</div>
                        <x-busy-btn class="vy-btn" :busy-label="__('guest/account.saving')">{{ __('guest/account.change_password') }}</x-busy-btn>
                    </form>
                </div>
            </section>

            {{-- Delete account — a 14-day SCHEDULED, reversible erase (D2) --}}
            <section class="vy-card ac-danger">
                <div class="vy-eyebrow" style="margin-bottom:8px;color:#8F3110">{{ __('guest/account.delete_account') }}</div>
                @php $vyStays = (int) ($deletion['stays'] ?? 0); @endphp
                @if ($deletion['scheduled'])
                    <div style="display:flex;align-items:flex-start;gap:11px;padding:12px 14px;border-radius:12px;background:rgba(217,102,46,.08);border:1px solid rgba(232,112,58,.25);margin-bottom:12px">
                        <span style="font-size:12.5px;color:#6B615A;line-height:1.55">{!! __('guest/account.scheduled_on', ['date' => e($deletion['on'])]) !!}
                            @if ($vyStays > 0)
                                {!! trans_choice('guest/account.scheduled_cancels', $vyStays, ['count' => e($vyStays)]) !!}
                                {{ __('guest/account.stay_and_refund_note') }}
                            @endif
                            {{ __('guest/account.until_then') }}</span>
                    </div>
                    <form method="POST" action="{{ route('guest.account.delete.undo') }}" data-guard-submit>
                        @csrf
                        <x-busy-btn class="vy-btn vy-btn--accent" :busy-label="__('guest/account.restoring')">{{ __('guest/account.undo_keep_account') }}</x-busy-btn>
                    </form>
                @else
                    {{-- ⚠️ THE CONSEQUENCE LEADS, AND IT RENDERS. The erase cancels every booking that is not
                         already cancelled / no-show / checked-out, and a refund only happens inside a booking's
                         free-cancellation window. Both limits are stated in the markup below, never only here —
                         a warning a reader never sees is not a warning. The count comes from
                         GuestDeletionService::reservationsToCancel(), the same set the erase uses.
                         ⚠️⚠️ THIS COPY CHANGED 2026-09-22 WITH THE RULING and both sentences above and below
                         used to read "including a stay already under way". That became FALSE the moment
                         terminate() began refusing `checked_in`: the erase would have skipped the live stay
                         (eraseNow() swallows the refusal) while this paragraph went on promising it. Deleting an
                         account does not evict a guest from a room — say so, rather than quietly stopping. --}}
                    <p style="font-size:12.5px;line-height:1.5;color:var(--vy-muted);margin:0 0 12px">
                        @if ($vyStays > 0)
                            <strong style="color:#8F3110">{{ trans_choice('guest/account.also_cancels', $vyStays, ['count' => $vyStays]) }}</strong>
                            {!! __('guest/account.erase_with_bookings') !!}
                            {{ __('guest/account.stay_and_refund_note') }}
                        @else
                            {!! __('guest/account.erase_no_bookings') !!}
                        @endif
                        {{ __('guest/account.nothing_changes') }}
                    </p>
                    <form method="POST" action="{{ route('guest.account.delete') }}" data-guard-submit>
                        @csrf
                        <div class="vy-field"><label class="vy-label" for="confirm">{!! __('guest/account.type_to_confirm', ['word' => 'DELETE']) !!}</label><input class="vy-input" id="confirm" name="confirm" placeholder="DELETE" autocomplete="off" style="letter-spacing:.1em"></div>
                        <x-busy-btn class="vy-btn" style="background:none;color:#8F3110;border:1px solid rgba(143,49,16,.4);box-shadow:none" :busy-label="__('guest/account.scheduling')">{{ __('guest/account.delete_my_account') }}</x-busy-btn>
                    </form>
                @endif
            </section>
        </div>

        {{-- COLUMN 2 — payment methods (honest) + waitlists --}}
        <div class="ac-col">
            <section class="vy-card">
                <div class="vy-eyebrow" style="margin-bottom:12px">{{ __('guest/account.payment_methods') }}</div>
                {{-- D3 — no saved-card vault + no raw PAN on the server (SAQ A). Honest empty state, never a fake card. --}}
                <div style="text-align:center;padding:18px 6px 8px">
                    <div style="font-family:var(--vy-display);font-weight:500;font-size:14px">{{ __('guest/account.no_saved_cards') }}</div>
                    <div style="font-size:11.5px;color:var(--vy-muted);margin-top:4px;line-height:1.5">{{ __('guest/account.card_at_checkout') }}</div>
                </div>
            </section>

            <section class="vy-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px">
                    <div class="vy-eyebrow">{{ __('guest/account.waitlists') }}</div>
                    <a class="vy-link" href="{{ route('guest.saved') }}" style="font-size:12px;color:var(--vy-accent-text)">{{ __('guest/account.manage') }}</a>
                </div>
                @forelse ($waitlist as $entry)
                    <div class="ac-row">
                        <div style="min-width:0">
                            <div style="font-size:13.5px;color:var(--vy-ink)">{{ $entry['room'] }}</div>
                            <div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">{{ $entry['dates'] }}</div>
                        </div>
                        <form method="POST" action="{{ route('guest.saved.waitlist.leave', $entry['id']) }}" style="margin:0;flex:none">@csrf<button type="submit" class="vy-btn vy-btn--ghost" style="font-size:12px">{{ __('guest/account.leave_waitlist') }}</button></form>
                    </div>
                @empty
                    <div style="font-size:12.5px;color:var(--vy-muted);padding:6px 0">{{ __('guest/account.no_waitlists') }}</div>
                @endforelse
            </section>
        </div>

        {{-- COLUMN 3 — preferences + account actions --}}
        <div class="ac-col">
            <section class="vy-card">
                <div class="vy-eyebrow" style="margin-bottom:4px">{{ __('guest/account.preferences') }}</div>
                <div style="font-size:11.5px;color:var(--vy-muted);margin-bottom:6px">{{ __('guest/account.how_we_reach_you') }}</div>

                {{-- Trip offers — the REAL #87 marketing-consent gate (submits on toggle) --}}
                <form method="POST" action="{{ route('guest.account.preferences') }}" style="margin:0">
                    @csrf
                    <div class="ac-row" style="border-top:none">
                        <div><div style="font-size:13.5px;font-weight:500">{{ __('guest/account.trip_offers') }}</div><div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">{{ __('guest/account.trip_offers_hint') }}</div></div>
                        <button type="submit" name="marketing_consent" value="{{ $marketingConsent ? '0' : '1' }}" class="ac-toggle"
                                aria-label="{{ __('guest/account.trip_offers') }}" aria-pressed="{{ $marketingConsent ? 'true' : 'false' }}"
                                style="background:{{ $marketingConsent ? 'var(--vy-accent-3)' : 'rgba(120,70,40,.2)' }}"><span class="knob" style="left:{{ $marketingConsent ? '20px' : '3px' }}"></span></button>
                    </div>
                </form>

                {{-- SMS — honest-gated (no provider) --}}
                <div class="ac-row">
                    <div><div style="font-size:13.5px;font-weight:500;color:var(--vy-muted)">{{ __('guest/account.sms_updates') }}</div><div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">{{ __('guest/account.sms_not_available') }}</div></div>
                    <span class="ac-toggle" style="background:rgba(120,70,40,.12);cursor:not-allowed" title="{{ __('guest/account.not_available_yet') }}"><span class="knob" style="left:3px;opacity:.6"></span></span>
                </div>

                {{-- Language — real (submits on change) --}}
                <form method="POST" action="{{ route('guest.account.preferences') }}" style="margin:0">
                    @csrf
                    <div class="ac-row" style="flex-wrap:wrap">
                        <div style="font-size:13.5px;font-weight:500">{{ __('guest/account.language') }}</div>
                        <div style="display:flex;gap:4px;padding:4px;border-radius:12px;background:rgba(120,70,40,.06);border:1px solid var(--vy-hairline)">
                            @foreach (['en' => 'EN', 'de' => 'DE', 'es' => 'ES', 'fr' => 'FR'] as $code => $label)
                                <label class="ac-chip"><input type="radio" name="language" value="{{ $code }}" @checked($profile['language'] === $code) onchange="this.form.submit()"><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </section>

            {{-- ═══ EMAILS ABOUT YOUR STAY (Guest Email Preferences) ═══════════════════════════════════
                 The `Guest Email Preferences` wireframe's six rows, in their REAL states.

                 ⚠️ THIS IS THE EMAIL PROGRAMME, NOT THE IN-APP FEED. The guest transactional emails never
                 consult NotificationPreferences::allows() — they are gated at their SEND SITES, and these
                 toggles are that gate. Switching an email off does NOT remove the in-app notification: a
                 guest still sees it in their feed, they just are not mailed about it.

                 ⚠️ TWO ARE TOGGLEABLE. `txn` and pre-arrival are LOCKED by ruling — a booking confirmation
                 is a transactional record, and the pre-arrival email carries the door code, so a guest who
                 could switch it off could arrive unable to get in. They are rendered ON and inert.

                 ⚠️ MARKETING IS THE SEPARATE CONTROL ABOVE ("Trip offers & news" → marketing_consent, with
                 its own unsubscribe flow). Different legal basis; deliberately NOT collapsed into these, and
                 not duplicated here as a second offers switch that would contradict it.

                 ⚠️ #39 — push is rendered honestly disabled, exactly like the SMS row above: there is no
                 push channel and no provider, so a working-looking switch would control nothing. --}}
            <section class="vy-card">
                <div class="vy-eyebrow" style="margin-bottom:4px">{{ __('guest/account.emails_about_stay') }}</div>
                <div style="font-size:11.5px;color:var(--vy-muted);margin-bottom:6px">{{ __('guest/account.always_essentials') }}</div>

                {{-- LOCKED — a transactional record. --}}
                <div class="ac-row" style="border-top:none">
                    <div><div style="font-size:13.5px;font-weight:500">{{ __('guest/account.booking_confirmations') }}</div><div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">{{ __('guest/account.booking_confirmations_hint') }}</div></div>
                    <span class="ac-toggle" style="background:var(--vy-accent-3);opacity:.55;cursor:not-allowed" title="{{ __('guest/account.booking_confirmations_title') }}" aria-label="{{ __('guest/account.booking_confirmations_aria') }}"><span class="knob" style="left:20px"></span></span>
                </div>

                {{-- LOCKED — the door-code nudge. --}}
                <div class="ac-row">
                    <div><div style="font-size:13.5px;font-weight:500">{{ __('guest/account.pre_arrival') }}</div><div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">{{ __('guest/account.pre_arrival_hint') }}</div></div>
                    <span class="ac-toggle" style="background:var(--vy-accent-3);opacity:.55;cursor:not-allowed" title="{{ __('guest/account.pre_arrival_title') }}" aria-label="{{ __('guest/account.pre_arrival_aria') }}"><span class="knob" style="left:20px"></span></span>
                </div>

                {{-- LIVE — the two the guest controls. --}}
                @foreach (['payment' => [__('guest/account.payment_reminders'), __('guest/account.payment_reminders_hint')], 'reviews' => [__('guest/account.review_requests'), __('guest/account.review_requests_hint')]] as $cat => $copy)
                    <form method="POST" action="{{ route('guest.account.preferences') }}" style="margin:0">
                        @csrf
                        <input type="hidden" name="email_pref" value="{{ $cat }}">
                        <div class="ac-row">
                            <div><div style="font-size:13.5px;font-weight:500">{{ $copy[0] }}</div><div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">{{ $copy[1] }}</div></div>
                            <button type="submit" name="receiving" value="{{ $emailPrefs[$cat] ? '0' : '1' }}" class="ac-toggle"
                                    aria-label="{{ $copy[0] }}" aria-pressed="{{ $emailPrefs[$cat] ? 'true' : 'false' }}"
                                    style="background:{{ $emailPrefs[$cat] ? 'var(--vy-accent-3)' : 'rgba(120,70,40,.2)' }}"><span class="knob" style="left:{{ $emailPrefs[$cat] ? '20px' : '3px' }}"></span></button>
                        </div>
                    </form>
                @endforeach

                {{-- HONESTLY DISABLED — no push channel exists (#39), same treatment as SMS above. --}}
                <div class="ac-row">
                    <div><div style="font-size:13.5px;font-weight:500;color:var(--vy-muted)">{{ __('guest/account.push_notifications') }}</div><div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">{{ __('guest/account.push_not_available') }}</div></div>
                    <span class="ac-toggle" style="background:rgba(120,70,40,.12);cursor:not-allowed" title="{{ __('guest/account.not_available_yet') }}"><span class="knob" style="left:3px;opacity:.6"></span></span>
                </div>

                <div style="font-size:11px;color:var(--vy-faint);padding:10px 2px 2px;line-height:1.5">
                    {{ __('guest/account.email_off_still_in_app') }}
                    {!! __('guest/account.marketing_separate', ['name' => e(__('guest/account.trip_offers'))]) !!}
                </div>
            </section>

            <section class="vy-card" style="padding:8px">
                <a href="{{ route('guest.wallet') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">{{ __('guest/account.credits_wallet') }}</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">{{ __('guest/account.credits_wallet_hint') }}</span></span>
                    <span style="color:var(--vy-faint)">›</span>
                </a>
                <a href="{{ route('guest.payments') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">{{ __('guest/account.receipts_documents') }}</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">{{ __('guest/account.receipts_documents_hint') }}</span></span>
                    <span style="color:var(--vy-faint)">›</span>
                </a>
                {{-- Help & support (audit 2026-08-07 · GATE 6). The prototype's standalone Help page was
                     never built and nothing linked to it, so a guest had no discoverable support route
                     from their account. It points at the REAL operator relay rather than a new page of
                     invented FAQ answers — a help centre whose content we'd have to make up is worse than
                     a direct line to the people who can actually answer (#39). --}}
                <a href="{{ route('guest.messages') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">{{ __('guest/account.help_support') }}</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">{{ __('guest/account.help_support_hint') }}</span></span>
                    <span style="color:var(--vy-faint)">›</span>
                </a>
                <form method="POST" action="{{ route('guest.logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:none;border:none;width:100%;text-align:left;cursor:pointer;font-family:var(--vy-sans);color:var(--vy-ink)">
                        <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">{{ __('common.sign_out') }}</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">{{ __('guest/account.sign_out_hint') }}</span></span>
                        <span style="color:var(--vy-faint)">›</span>
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-guest.shell>
