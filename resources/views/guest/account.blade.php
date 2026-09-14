{{-- Guest Account (Guest Portal · GATE 3) — the authed guest's OWN profile, rebuilt to the wireframe's
     hero + 5-KPI + 3-column bento. Self-only (resolved from the guest guard). All figures REAL: credit via
     GuestWalletService, stays via reservations, waitlists via GuestWaitlistEntry, trip-offers via
     marketing_consent (#87). HONEST: saved cards = 0 (no vault, no raw PAN — D3); SMS disabled (no provider);
     delete = a 14-DAY SCHEDULED, reversible erase (D2). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="account" title="Account">
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
                <div class="ac-since"><span style="width:7px;height:7px;border-radius:50%;background:#4F7A46;display:inline-block"></span> Member since {{ $hero['member_since'] }}</div>
                {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
                <h1 style="font-family:var(--vy-display);font-weight:600;font-size:20px;letter-spacing:-.02em;margin:0">{{ $hero['name'] }}</h1>
                <div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ $hero['email'] }}</div>
            </div>
        </div>
        <div class="ac-kpis">
            <a class="ac-kpi" href="{{ route('guest.wallet') }}" style="text-decoration:none"><b>{{ $kpi['credit'] }}</b><span>Credit</span></a>
            <div class="ac-kpi"><b>{{ $kpi['stays'] }}</b><span>Stays</span></div>
            <div class="ac-kpi"><b>{{ $kpi['cards'] }}</b><span>Saved cards</span></div>
            <a class="ac-kpi" href="{{ route('guest.saved') }}" style="text-decoration:none"><b>{{ $kpi['waitlists'] }}</b><span>Waitlists</span></a>
            <div class="ac-kpi"><b style="font-size:16px">{{ strtoupper($profile['language'] ?: '—') }}</b><span>Language</span></div>
        </div>
    </div>

    <div class="ac-bento">
        {{-- COLUMN 1 — personal details + delete --}}
        <div class="ac-col">
            <section class="vy-card">
                <div class="vy-eyebrow" style="margin-bottom:12px">Personal details</div>
                <form method="POST" action="{{ route('guest.account.update') }}" data-guard-submit novalidate>
                    @csrf
                    <div class="vy-field"><label class="vy-label" for="first_name">First name</label><input class="vy-input" id="first_name" name="first_name" value="{{ old('first_name', $profile['first_name']) }}" required></div>
                    <div class="vy-field"><label class="vy-label" for="last_name">Last name</label><input class="vy-input" id="last_name" name="last_name" value="{{ old('last_name', $profile['last_name']) }}" required></div>
                    <div class="vy-field"><label class="vy-label" for="email">Email</label><input class="vy-input" id="email" value="{{ $profile['email'] }}" disabled><span style="display:block;font-size:11px;color:var(--vy-faint);margin-top:5px">Your email is your sign-in — contact the property to change it.</span></div>
                    <div class="vy-field"><label class="vy-label" for="phone">Phone</label><input class="vy-input" id="phone" name="phone" value="{{ old('phone', $profile['phone']) }}" autocomplete="tel"></div>
                    <div class="vy-field"><label class="vy-label" for="nationality">Country (2-letter code)</label><input class="vy-input" id="nationality" name="nationality" value="{{ old('nationality', $profile['nationality']) }}" maxlength="2"></div>
                    <input type="hidden" name="language" value="{{ $profile['language'] }}">
                    <x-busy-btn class="vy-btn" busy-label="Saving…">Save details</x-busy-btn>
                </form>
                <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--vy-hairline)">
                    <div class="vy-eyebrow" style="margin-bottom:8px">Password</div>
                    <a class="vy-link" href="#change-password" style="font-size:12.5px;color:var(--vy-accent-text)" onclick="document.getElementById('change-password').style.display='block';this.style.display='none';return false">Change your password →</a>
                    <form id="change-password" method="POST" action="{{ route('guest.account.password') }}" data-guard-submit novalidate style="display:none;margin-top:10px">
                        @csrf
                        <div class="vy-field"><label class="vy-label" for="current_password">Current password</label><input class="vy-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required></div>
                        <div class="vy-field"><label class="vy-label" for="password">New password</label><input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" required></div>
                        <div class="vy-field"><label class="vy-label" for="password_confirmation">Confirm new password</label><input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></div>
                        {{-- DO13-4: this form enforced a composition rule it never stated, so a rejection had no visible reason. --}}
                        <div class="vy-foot" style="margin:-4px 0 10px">{{ \App\Support\Auth\PasswordPolicy::hint() }}</div>
                        <x-busy-btn class="vy-btn" busy-label="Saving…">Change password</x-busy-btn>
                    </form>
                </div>
            </section>

            {{-- Delete account — a 14-day SCHEDULED, reversible erase (D2) --}}
            <section class="vy-card ac-danger">
                <div class="vy-eyebrow" style="margin-bottom:8px;color:#8F3110">Delete account</div>
                @php $vyStays = (int) ($deletion['stays'] ?? 0); @endphp
                @if ($deletion['scheduled'])
                    <div style="display:flex;align-items:flex-start;gap:11px;padding:12px 14px;border-radius:12px;background:rgba(217,102,46,.08);border:1px solid rgba(232,112,58,.25);margin-bottom:12px">
                        <span style="font-size:12.5px;color:#6B615A;line-height:1.55">Your account is scheduled for deletion on <strong style="color:var(--vy-ink)">{{ $deletion['on'] }}</strong>.
                            @if ($vyStays > 0)
                                When it runs we will also <strong style="color:var(--vy-ink)">cancel {{ $vyStays }} booking{{ $vyStays === 1 ? '' : 's' }}</strong> you still have with us, including a stay already under way. Whether any money comes back follows each booking's cancellation policy, so some or all of it may not be refunded.
                            @endif
                            You have until then to change your mind — nothing is removed until it runs.</span>
                    </div>
                    <form method="POST" action="{{ route('guest.account.delete.undo') }}" data-guard-submit>
                        @csrf
                        <x-busy-btn class="vy-btn vy-btn--accent" busy-label="Restoring…">Undo — keep my account</x-busy-btn>
                    </form>
                @else
                    {{-- ⚠️ THE CONSEQUENCE LEADS, AND IT RENDERS. The erase cancels every booking that is not
                         already cancelled / no-show / checked-out, so a stay IN PROGRESS goes too, and a refund
                         only happens inside a booking's free-cancellation window. Both limits are stated in the
                         markup below, never only here — a warning a reader never sees is not a warning. The count
                         comes from GuestDeletionService::reservationsToCancel(), the same set the erase uses. --}}
                    <p style="font-size:12.5px;line-height:1.5;color:var(--vy-muted);margin:0 0 12px">
                        @if ($vyStays > 0)
                            <strong style="color:#8F3110">This also cancels {{ $vyStays }} booking{{ $vyStays === 1 ? '' : 's' }}.</strong>
                            In <strong>14 days</strong> we permanently remove your profile, saved details and preferences, and cancel every booking you still have with us — including a stay already under way. Whether any money comes back follows each booking's cancellation policy, so some or all of it may not be refunded.
                        @else
                            In <strong>14 days</strong> we permanently remove your profile, saved details and preferences. You have no bookings with us right now, so none will be cancelled.
                        @endif
                        Nothing changes until then, and you can undo it at any point in those 14 days.
                    </p>
                    <form method="POST" action="{{ route('guest.account.delete') }}" data-guard-submit>
                        @csrf
                        <div class="vy-field"><label class="vy-label" for="confirm">Type <strong style="color:#8F3110">DELETE</strong> to confirm</label><input class="vy-input" id="confirm" name="confirm" placeholder="DELETE" autocomplete="off" style="letter-spacing:.1em"></div>
                        <x-busy-btn class="vy-btn" style="background:none;color:#8F3110;border:1px solid rgba(143,49,16,.4);box-shadow:none" busy-label="Scheduling…">Delete my account</x-busy-btn>
                    </form>
                @endif
            </section>
        </div>

        {{-- COLUMN 2 — payment methods (honest) + waitlists --}}
        <div class="ac-col">
            <section class="vy-card">
                <div class="vy-eyebrow" style="margin-bottom:12px">Payment methods</div>
                {{-- D3 — no saved-card vault + no raw PAN on the server (SAQ A). Honest empty state, never a fake card. --}}
                <div style="text-align:center;padding:18px 6px 8px">
                    <div style="font-family:var(--vy-display);font-weight:500;font-size:14px">No saved cards</div>
                    <div style="font-size:11.5px;color:var(--vy-muted);margin-top:4px;line-height:1.5">You’ll add your card securely at checkout — we never store it here.</div>
                </div>
            </section>

            <section class="vy-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px">
                    <div class="vy-eyebrow">Waitlists</div>
                    <a class="vy-link" href="{{ route('guest.saved') }}" style="font-size:12px;color:var(--vy-accent-text)">Manage →</a>
                </div>
                @forelse ($waitlist as $entry)
                    <div class="ac-row">
                        <div style="min-width:0">
                            <div style="font-size:13.5px;color:var(--vy-ink)">{{ $entry['room'] }}</div>
                            <div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">{{ $entry['dates'] }}</div>
                        </div>
                        <form method="POST" action="{{ route('guest.saved.waitlist.leave', $entry['id']) }}" style="margin:0;flex:none">@csrf<button type="submit" class="vy-btn vy-btn--ghost" style="font-size:12px">Leave</button></form>
                    </div>
                @empty
                    <div style="font-size:12.5px;color:var(--vy-muted);padding:6px 0">You’re not on any waitlists. Join one for sold-out dates while browsing.</div>
                @endforelse
            </section>
        </div>

        {{-- COLUMN 3 — preferences + account actions --}}
        <div class="ac-col">
            <section class="vy-card">
                <div class="vy-eyebrow" style="margin-bottom:4px">Preferences</div>
                <div style="font-size:11.5px;color:var(--vy-muted);margin-bottom:6px">How we reach you</div>

                {{-- Trip offers — the REAL #87 marketing-consent gate (submits on toggle) --}}
                <form method="POST" action="{{ route('guest.account.preferences') }}" style="margin:0">
                    @csrf
                    <div class="ac-row" style="border-top:none">
                        <div><div style="font-size:13.5px;font-weight:500">Trip offers &amp; news</div><div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">Occasional emails about deals and new places</div></div>
                        <button type="submit" name="marketing_consent" value="{{ $marketingConsent ? '0' : '1' }}" class="ac-toggle"
                                aria-label="Trip offers &amp; news" aria-pressed="{{ $marketingConsent ? 'true' : 'false' }}"
                                style="background:{{ $marketingConsent ? 'var(--vy-accent-3)' : 'rgba(120,70,40,.2)' }}"><span class="knob" style="left:{{ $marketingConsent ? '20px' : '3px' }}"></span></button>
                    </div>
                </form>

                {{-- SMS — honest-gated (no provider) --}}
                <div class="ac-row">
                    <div><div style="font-size:13.5px;font-weight:500;color:var(--vy-muted)">Booking updates by SMS</div><div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">Not available yet — coming when SMS is set up</div></div>
                    <span class="ac-toggle" style="background:rgba(120,70,40,.12);cursor:not-allowed" title="Not available yet"><span class="knob" style="left:3px;opacity:.6"></span></span>
                </div>

                {{-- Language — real (submits on change) --}}
                <form method="POST" action="{{ route('guest.account.preferences') }}" style="margin:0">
                    @csrf
                    <div class="ac-row" style="flex-wrap:wrap">
                        <div style="font-size:13.5px;font-weight:500">Language</div>
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
                <div class="vy-eyebrow" style="margin-bottom:4px">Emails about your stay</div>
                <div style="font-size:11.5px;color:var(--vy-muted);margin-bottom:6px">You will always get the essentials</div>

                {{-- LOCKED — a transactional record. --}}
                <div class="ac-row" style="border-top:none">
                    <div><div style="font-size:13.5px;font-weight:500">Booking confirmations &amp; receipts</div><div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">Confirmations, payment receipts and cancellations — always sent</div></div>
                    <span class="ac-toggle" style="background:var(--vy-accent-3);opacity:.55;cursor:not-allowed" title="Always sent — this is your booking record" aria-label="Booking confirmations and receipts — always sent"><span class="knob" style="left:20px"></span></span>
                </div>

                {{-- LOCKED — the door-code nudge. --}}
                <div class="ac-row">
                    <div><div style="font-size:13.5px;font-weight:500">Pre-arrival &amp; check-in</div><div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">Door code, directions and arrival details — always sent so you can get in</div></div>
                    <span class="ac-toggle" style="background:var(--vy-accent-3);opacity:.55;cursor:not-allowed" title="Always sent — it carries your access details" aria-label="Pre-arrival and check-in — always sent"><span class="knob" style="left:20px"></span></span>
                </div>

                {{-- LIVE — the two the guest controls. --}}
                @foreach (['payment' => ['Payment reminders', 'A nudge when a balance is due before check-in'], 'reviews' => ['Review requests', 'A note after your stay asking how it went']] as $cat => $copy)
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
                    <div><div style="font-size:13.5px;font-weight:500;color:var(--vy-muted)">Push notifications</div><div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">Not available yet — coming when push is set up</div></div>
                    <span class="ac-toggle" style="background:rgba(120,70,40,.12);cursor:not-allowed" title="Not available yet"><span class="knob" style="left:3px;opacity:.6"></span></span>
                </div>

                <div style="font-size:11px;color:var(--vy-faint);padding:10px 2px 2px;line-height:1.5">
                    Turning an email off does not hide it in the app — you will still see it in your notifications.
                    Marketing emails are controlled separately by <strong>Trip offers &amp; news</strong> above.
                </div>
            </section>

            <section class="vy-card" style="padding:8px">
                <a href="{{ route('guest.wallet') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">Credits &amp; wallet</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">Your credit balance &amp; history</span></span>
                    <span style="color:var(--vy-faint)">›</span>
                </a>
                <a href="{{ route('guest.payments') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">Receipts &amp; documents</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">Invoices, folios and receipts</span></span>
                    <span style="color:var(--vy-faint)">›</span>
                </a>
                {{-- Help & support (audit 2026-08-07 · GATE 6). The prototype's standalone Help page was
                     never built and nothing linked to it, so a guest had no discoverable support route
                     from their account. It points at the REAL operator relay rather than a new page of
                     invented FAQ answers — a help centre whose content we'd have to make up is worse than
                     a direct line to the people who can actually answer (#39). --}}
                <a href="{{ route('guest.messages') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">Help &amp; support</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">Message your property directly</span></span>
                    <span style="color:var(--vy-faint)">›</span>
                </a>
                <form method="POST" action="{{ route('guest.logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:none;border:none;width:100%;text-align:left;cursor:pointer;font-family:var(--vy-sans);color:var(--vy-ink)">
                        <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">Sign out</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">You can sign back in any time</span></span>
                        <span style="color:var(--vy-faint)">›</span>
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-guest.shell>
