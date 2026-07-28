{{--
    Owner SETTINGS (exact-match pass · invariant #62) — the settings HUB, rebuilt to the decoded "Owner Settings"
    wireframe. A HUB: most sections REUSE features already built (anti-drift). Every write is a guarded controller
    POST (never a Livewire action). See App\Livewire\Owner\OwnerSettings + docs/OWNER-SETTINGS-AUDIT.md.

    GATE 1 (identity & money): Hero · Profile · Business & tax · Billing & invoices · Payout account · Completeness.
    (GATE 2 adds: Notifications · Channels · Team · Language · Appearance · Data export.)

    HONESTY: billing = the REAL management fee (the real agreement rate, never 15%) + real statement PDFs, NO owner
    card (#39); masked IBAN CAP_PAYOUT-only (#28); hero + completeness DERIVED from the real state.
    RAW-BLADE RULE: every `@` follows a space, newline or tag.
--}}
@php
    $lbl = 'display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:7px;';
    $err = 'font-size:11.5px;color:var(--vy-accent);display:block;margin-top:5px;';
    $btn = 'background:var(--vy-grad);color:#fff;padding:11px 22px;';
    $ghost = 'background:rgba(255,255,255,.6);border:1px solid var(--vy-glass-border);color:var(--vy-ink);padding:9px 16px;';
@endphp
<x-owner.shell active="settings" title="Settings" :subtitle="'Your profile, business details, billing and account — one place.'" :viewer="$viewer">
    <style>
        .vy-set-hero { display:flex; align-items:center; gap:24px; flex-wrap:wrap; }
        .vy-set-ring { --p:0; flex:none; width:76px; height:76px; border-radius:50%; display:grid; place-items:center; background:conic-gradient(var(--vy-accent) calc(var(--p)*1%), rgba(120,70,40,.12) 0); }
        .vy-set-ring > span { width:60px; height:60px; border-radius:50%; background:var(--vy-card,#fff); display:grid; place-items:center; font-family:var(--vy-display); font-weight:600; font-size:17px; color:var(--vy-ink); }
        .vy-set-stats { display:flex; gap:26px; flex-wrap:wrap; margin-left:auto; }
        .vy-set-stat-n { font-family:var(--vy-display); font-weight:600; font-size:22px; line-height:1; color:var(--vy-ink); font-variant-numeric:tabular-nums; }
        .vy-set-stat-l { font-size:11px; color:var(--vy-muted); margin-top:5px; }
        .vy-set-h2 { font-family:var(--vy-display);font-size:15px;font-weight:600;margin:0 0 4px; }
        .vy-set-sub { font-size:12.5px;color:var(--vy-muted);margin:0 0 16px; }
        .vy-set-fee { display:flex;align-items:baseline;justify-content:space-between;padding:9px 0;border-top:1px solid var(--vy-hairline);font-size:13px; }
        .vy-set-fee:first-of-type { border-top:none; }
        .vy-set-check { display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px solid var(--vy-hairline);font-size:13px; }
        .vy-set-check:first-of-type { border-top:none; }
        .vy-set-tick { flex:none;width:20px;height:20px;border-radius:50%;display:grid;place-items:center; }
        .vy-set-iban { display:inline-flex;align-items:center;gap:6px;font-variant-numeric:tabular-nums;font-size:14px;font-weight:600;color:var(--vy-ink); }
    </style>

    @if (session('owner_notice'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('owner_notice') }}</div>
        </div>
    @endif

    {{-- ══ HERO — DERIVED completeness + real stats ══ --}}
    <div class="vy-card vy-fade vy-set-hero" style="margin-bottom:16px">
        <div class="vy-set-ring" style="--p:{{ $hero['completeness'] }}"><span>{{ $hero['completeness'] }}%</span></div>
        <div>
            <h2 class="vy-set-h2" style="margin-bottom:2px">{{ $account['name'] }}</h2>
            <div style="font-size:12.5px;color:var(--vy-muted)">Your account is {{ $hero['completeness'] }}% set up · External owner</div>
        </div>
        <div class="vy-set-stats">
            <div><div class="vy-set-stat-n">{{ $hero['channels_live'] }}</div><div class="vy-set-stat-l">Channels live</div></div>
            <div><div class="vy-set-stat-n">{{ $hero['team'] }}</div><div class="vy-set-stat-l">Team members</div></div>
            <div><div class="vy-set-stat-n">{{ $hero['invoices'] }}</div><div class="vy-set-stat-l">Fee invoices</div></div>
        </div>
    </div>

    <div class="vy-cols">
        {{-- ══ LEFT ══ --}}
        <div class="vy-stack">
            {{-- Profile — reuse OwnerAccountController (avatar / name+locale / email) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">Profile</h2>
                <p class="vy-set-sub">Your personal sign-in — photo, name, email and language. Only you can change these.</p>

                <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding-bottom:18px;border-bottom:1px solid var(--vy-hairline);margin-bottom:18px">
                    @if ($account['avatar_url'])
                        <img src="{{ $account['avatar_url'] }}" alt="Your photo" style="width:64px;height:64px;border-radius:50%;object-fit:cover;box-shadow:var(--vy-shadow-sm)">
                    @else
                        <span style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(140deg,#F0A57A,#D9662E);color:#fff;font-family:var(--vy-display);font-weight:600;font-size:22px">{{ $account['initials'] }}</span>
                    @endif
                    <div>
                        <form method="POST" action="{{ route('owner.account.avatar') }}" enctype="multipart/form-data" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            @csrf
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" style="font-size:12.5px;color:var(--vy-muted);max-width:220px">
                            <button type="submit" class="vy-btn" style="{{ $ghost }}">Upload photo</button>
                        </form>
                        @error('avatar') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        @if ($account['avatar_url'])
                            <form method="POST" action="{{ route('owner.account.avatar.remove') }}" style="margin-top:8px">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:var(--vy-accent);font-size:12px;cursor:pointer;padding:0">Remove photo</button>
                            </form>
                        @endif
                        <p style="font-size:11px;color:var(--vy-faint);margin:8px 0 0">JPG, PNG or WebP · up to 4&nbsp;MB.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('owner.account.update') }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
                        <div>
                            <label style="{{ $lbl }}" for="a_name">Name</label>
                            <input id="a_name" type="text" name="name" class="vy-input" value="{{ old('name', $account['name']) }}" required>
                            @error('name') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="{{ $lbl }}" for="a_locale">Language</label>
                            <select id="a_locale" name="locale" class="vy-input">
                                @foreach ($localeOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(old('locale', $account['locale']) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('locale') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div style="margin-top:16px;display:flex;justify-content:flex-end">
                        <button type="submit" class="vy-btn" style="{{ $btn }}">Save profile</button>
                    </div>
                </form>

                <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--vy-hairline)">
                    <label style="{{ $lbl }}">Login email</label>
                    <div style="font-size:13.5px;color:var(--vy-ink);margin-bottom:10px">Currently <strong>{{ $account['email'] }}</strong>
                        @if ($account['pending_email'] === '')<span class="vy-status vy-status--paid" style="margin-left:6px"><span class="vy-dot"></span>Verified</span>@endif
                    </div>
                    @if ($account['pending_email'] !== '')
                        <div class="vy-card" style="background:rgba(224,146,30,.08);border:1px solid rgba(224,146,30,.25);padding:12px 14px;margin-bottom:10px">
                            <div style="font-size:12.5px;color:var(--vy-ink)">Pending change to <strong>{{ $account['pending_email'] }}</strong> — check that inbox and click the confirmation link. Your email stays <strong>{{ $account['email'] }}</strong> until you do.</div>
                        </div>
                        <form method="POST" action="{{ route('owner.account.email.cancel') }}">
                            @csrf
                            <button type="submit" style="background:none;border:none;color:var(--vy-accent);font-size:12.5px;cursor:pointer;padding:0">Cancel this change</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('owner.account.email') }}" style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap">
                            @csrf
                            <div style="flex:1;min-width:220px">
                                <input type="email" name="email" class="vy-input" placeholder="new@example.com" aria-label="New login email">
                                @error('email') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="vy-btn" style="{{ $ghost }}">Change email</button>
                        </form>
                        <span style="font-size:11px;color:var(--vy-faint);display:block;margin-top:8px">We’ll email a confirmation link to the new address. Your email only changes once you click it.</span>
                    @endif
                </div>
            </div>

            {{-- Business & tax — reuse owner.settings.update (party) + owner.tax.save (tax, anti-drift) --}}
            @if ($isPrimaryOwner && $ownerParty)
                <div class="vy-card vy-fade">
                    <h2 class="vy-set-h2">Business &amp; tax details</h2>
                    <p class="vy-set-sub">The business the management team pays out to — appears on your statements. Only you can change these.</p>
                    <form method="POST" action="{{ route('owner.settings.update') }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
                            <div>
                                <label style="{{ $lbl }}" for="o_name">Business / owner name</label>
                                <input id="o_name" type="text" name="name" class="vy-input" value="{{ old('name', $ownerParty['name']) }}" required>
                                @error('name') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="o_email">Billing email <span style="text-transform:none;font-weight:500;color:var(--vy-faint)">· optional</span></label>
                                <input id="o_email" type="email" name="email" class="vy-input" value="{{ old('email', $ownerParty['email']) }}">
                                @error('email') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="o_phone">Phone <span style="text-transform:none;font-weight:500;color:var(--vy-faint)">· optional</span></label>
                                <input id="o_phone" type="text" name="phone" class="vy-input" value="{{ old('phone', $ownerParty['phone']) }}">
                                @error('phone') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div style="margin-top:14px;display:flex;justify-content:flex-end">
                            <button type="submit" class="vy-btn" style="{{ $btn }}">Save business details</button>
                        </div>
                    </form>

                    {{-- Inline tax form → the ONE owner.tax.save path (anti-drift; feeds the remittance PDF) --}}
                    <form method="POST" action="{{ route('owner.tax.save') }}" style="margin-top:18px;padding-top:18px;border-top:1px solid var(--vy-hairline)">
                        @csrf
                        <label style="{{ $lbl }}">Tax &amp; VAT</label>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-top:2px">
                            <div>
                                <label style="{{ $lbl }}" for="t_type">Registered as</label>
                                <select id="t_type" name="type" class="vy-input">
                                    <option value="individual" @selected(($ownerParty['type'] ?: 'individual') === 'individual')>Individual</option>
                                    <option value="company" @selected($ownerParty['type'] === 'company')>Company</option>
                                </select>
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_legal">Legal name <span style="text-transform:none;font-weight:500;color:var(--vy-faint)">· optional</span></label>
                                <input id="t_legal" type="text" name="legal_name" class="vy-input" value="{{ old('legal_name', $ownerParty['legal_name']) }}">
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_country">Tax country <span style="text-transform:none;font-weight:500;color:var(--vy-faint)">· optional</span></label>
                                <input id="t_country" type="text" name="tax_country" class="vy-input" value="{{ old('tax_country', $ownerParty['tax_country']) }}">
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_tax">Tax ID / NIF</label>
                                <input id="t_tax" type="text" name="tax_id" class="vy-input" placeholder="{{ $ownerParty['has_tax_id'] ? 'On file · enter to replace' : 'Required to release payouts' }}">
                                @error('tax_id') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_vat">VAT number <span style="text-transform:none;font-weight:500;color:var(--vy-faint)">· optional</span></label>
                                <input id="t_vat" type="text" name="vat_number" class="vy-input" value="{{ old('vat_number', $ownerParty['vat_number']) }}">
                            </div>
                        </div>
                        <div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                            <span style="font-size:11px;color:var(--vy-faint)">Encrypted at rest · also editable in the Tax center.</span>
                            <button type="submit" class="vy-btn" style="{{ $ghost }}">Save tax details</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Billing & invoices — the REAL management fee (never an owner card / subscription) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">Billing &amp; invoices</h2>
                <p class="vy-set-sub">How Vyrobel is paid for managing your units.</p>
                <div class="vy-set-fee">
                    <span>Management fee</span>
                    <span style="font-weight:600">{{ $fee['percent'] !== null ? $fee['percent'].'% of booking revenue' : 'Per your agreement' }}</span>
                </div>
                <div class="vy-set-fee">
                    <span>How it's billed</span>
                    <span class="vy-muted">Deducted on each statement — no card needed</span>
                </div>
                <div class="vy-set-fee">
                    <span>Fee invoices</span>
                    <span class="vy-muted">{{ $fee['invoices_count'] }} on your {{ \Illuminate\Support\Str::plural('statement', $fee['invoices_count']) }}</span>
                </div>
                <div style="margin-top:14px;display:flex;gap:16px;flex-wrap:wrap">
                    <a href="{{ route('owner.documents') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">Statement &amp; fee documents →</a>
                    <a href="{{ route('owner.agreement') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">Your agreement →</a>
                </div>
            </div>
        </div>

        {{-- ══ RIGHT ══ --}}
        <div class="vy-stack">
            {{-- Payout account — the REAL masked IBAN (CAP_PAYOUT-only, #28) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">Payout account</h2>
                @if ($canPayout && $payout['iban_masked'])
                    <div class="vy-set-iban" style="margin-top:6px">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
                        {{ $payout['iban_masked'] }}
                    </div>
                    <p style="font-size:12px;color:var(--vy-muted);margin:8px 0 0">Where your payouts land.</p>
                @elseif ($canPayout)
                    <p class="vy-set-sub" style="margin-top:6px">No payout account on file yet.</p>
                @else
                    <p class="vy-set-sub" style="margin-top:6px">Managed by the account owner.</p>
                @endif
                @if ($canPayout)
                    <a href="{{ route('owner.payout-method') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none;display:inline-block;margin-top:12px">Manage in Payouts →</a>
                @endif
            </div>

            {{-- Profile completeness — DERIVED checklist --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">Profile completeness</h2>
                <p class="vy-set-sub" style="margin-bottom:10px">{{ $hero['completeness'] }}% done</p>
                <div class="vy-track" style="margin-bottom:8px"><span style="width:{{ $hero['completeness'] }}%"></span></div>
                @foreach ($completeness as $c)
                    <div class="vy-set-check">
                        <span class="vy-set-tick" style="background:{{ $c['done'] ? 'var(--vy-accent)' : 'rgba(120,70,40,.1)' }}">
                            @if ($c['done'])
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </span>
                        <span style="flex:1;{{ $c['done'] ? 'color:var(--vy-muted)' : '' }}">{{ $c['label'] }}</span>
                        @if (! $c['done'] && $c['href'])
                            <a href="{{ $c['href'] }}" style="color:var(--vy-accent);font-size:12px;text-decoration:none">Add →</a>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Security & privacy — link to the real security page --}}
            <a href="{{ route('owner.security') }}" class="vy-card vy-fade" style="display:flex;align-items:center;justify-content:space-between;gap:12px;text-decoration:none;color:var(--vy-ink)">
                <div>
                    <h2 class="vy-set-h2">Security &amp; privacy</h2>
                    <p style="font-size:12.5px;color:var(--vy-muted);margin:0">Password, two-factor, active sessions and account protection.</p>
                </div>
                <span style="color:var(--vy-accent);font-size:18px">→</span>
            </a>
        </div>
    </div>
</x-owner.shell>
