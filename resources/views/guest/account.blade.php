{{-- Guest Account (Guest Portal · GATE 3) — the authed guest's OWN profile, rebuilt to the wireframe's
     hero + 5-KPI + 3-column bento. Self-only (resolved from the guest guard). All figures REAL: credit via
     GuestWalletService, stays via reservations, waitlists via GuestWaitlistEntry, trip-offers via
     marketing_consent (#87). HONEST: saved cards = 0 (no vault, no raw PAN — D3); SMS disabled (no provider);
     delete = a 14-DAY SCHEDULED, reversible erase (D2). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="account" title="Account">
    <style>
        .ac-hero { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; margin-bottom: 18px; }
        .ac-av { width: 56px; height: 56px; border-radius: 50%; background: var(--vy-grad); color: #fff; display: grid; place-items: center; font-family: var(--vy-display); font-weight: 600; font-size: 20px; box-shadow: var(--vy-shadow-accent); flex: none; }
        .ac-since { display: inline-flex; align-items: center; gap: 7px; padding: 3px 11px; border-radius: 20px; background: rgba(79,122,70,.13); font-size: 11px; font-weight: 600; color: #3F6338; margin-bottom: 6px; }
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
                <div style="font-family:var(--vy-display);font-weight:600;font-size:20px;letter-spacing:-.02em">{{ $hero['name'] }}</div>
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
                    <a class="vy-link" href="#change-password" style="font-size:12.5px;color:var(--vy-accent)" onclick="document.getElementById('change-password').style.display='block';this.style.display='none';return false">Change your password →</a>
                    <form id="change-password" method="POST" action="{{ route('guest.account.password') }}" data-guard-submit novalidate style="display:none;margin-top:10px">
                        @csrf
                        <div class="vy-field"><label class="vy-label" for="current_password">Current password</label><input class="vy-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required></div>
                        <div class="vy-field"><label class="vy-label" for="password">New password</label><input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" required></div>
                        <div class="vy-field"><label class="vy-label" for="password_confirmation">Confirm new password</label><input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></div>
                        <x-busy-btn class="vy-btn" busy-label="Saving…">Change password</x-busy-btn>
                    </form>
                </div>
            </section>

            {{-- Delete account — a 14-day SCHEDULED, reversible erase (D2) --}}
            <section class="vy-card ac-danger">
                <div class="vy-eyebrow" style="margin-bottom:8px;color:#8F3110">Delete account</div>
                @if ($deletion['scheduled'])
                    <div style="display:flex;align-items:flex-start;gap:11px;padding:12px 14px;border-radius:12px;background:rgba(217,102,46,.08);border:1px solid rgba(232,112,58,.25);margin-bottom:12px">
                        <span style="font-size:12.5px;color:#6B615A;line-height:1.55">Your account is scheduled for deletion on <strong style="color:var(--vy-ink)">{{ $deletion['on'] }}</strong>. You have until then to change your mind — everything is kept until it runs.</span>
                    </div>
                    <form method="POST" action="{{ route('guest.account.delete.undo') }}" data-guard-submit>
                        @csrf
                        <x-busy-btn class="vy-btn vy-btn--accent" busy-label="Restoring…">Undo — keep my account</x-busy-btn>
                    </form>
                @else
                    <p style="font-size:12.5px;line-height:1.5;color:var(--vy-muted);margin:0 0 12px">This schedules your profile, saved details and preferences for permanent removal in <strong>14 days</strong>. Upcoming reservations are cancelled per the property's policy. You can undo any time in those 14 days.</p>
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
                    <a class="vy-link" href="{{ route('guest.saved') }}" style="font-size:12px;color:var(--vy-accent)">Manage →</a>
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
                        <button type="submit" name="marketing_consent" value="{{ $marketingConsent ? '0' : '1' }}" class="ac-toggle" style="background:{{ $marketingConsent ? 'var(--vy-accent-3)' : 'rgba(120,70,40,.2)' }}"><span class="knob" style="left:{{ $marketingConsent ? '20px' : '3px' }}"></span></button>
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

            <section class="vy-card" style="padding:8px">
                <a href="{{ route('guest.wallet') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">Credits &amp; wallet</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">Your credit balance &amp; history</span></span>
                    <span style="color:var(--vy-faint)">›</span>
                </a>
                <a href="{{ route('guest.payments') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;text-decoration:none;color:var(--vy-ink)">
                    <span style="flex:1"><span style="display:block;font-size:13.5px;font-weight:500">Receipts &amp; documents</span><span style="display:block;font-size:11.5px;color:var(--vy-muted)">Invoices, folios and receipts</span></span>
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
