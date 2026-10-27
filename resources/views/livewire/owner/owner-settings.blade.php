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
    $lbl = 'display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-on-grad-soft);margin-bottom:7px;';
    $err = 'font-size:11.5px;color:var(--vy-accent-text);display:block;margin-top:5px;';
    $btn = 'background:var(--vy-grad);color:#fff;padding:11px 22px;';
    $ghost = 'background:rgba(255,255,255,.6);border:1px solid var(--vy-glass-border);color:var(--vy-ink);padding:9px 16px;';
@endphp
<x-owner.shell active="settings" :title="__('livewire/owner/owner-settings.title')" :subtitle="__('livewire/owner/owner-settings.subtitle')" :viewer="$viewer">
    <style>
        .vy-set-hero { display:flex; align-items:center; gap:24px; flex-wrap:wrap; }
        .vy-set-ring { --p:0; flex:none; width:76px; height:76px; border-radius:50%; display:grid; place-items:center; background:conic-gradient(var(--vy-accent) calc(var(--p)*1%), rgba(120,70,40,.12) 0); }
        .vy-set-ring > span { width:60px; height:60px; border-radius:50%; background:var(--vy-card,#fff); display:grid; place-items:center; font-family:var(--vy-display); font-weight:600; font-size:17px; color:var(--vy-ink); }
        .vy-set-stats { display:flex; gap:26px; flex-wrap:wrap; margin-left:auto; }
        .vy-set-stat-n { font-family:var(--vy-display); font-weight:600; font-size:22px; line-height:1; color:var(--vy-ink); font-variant-numeric:tabular-nums; }
        .vy-set-stat-l { font-size:11px; color:var(--vy-muted); margin-top:5px; }
        .vy-set-file { position:relative; display:inline-flex; align-items:center; gap:7px; padding:9px 16px; border-radius:var(--vy-r-pill); background:rgba(255,255,255,.6); border:1px solid var(--vy-glass-border); color:var(--vy-ink); font-size:12.5px; font-weight:500; cursor:pointer; transition:background .16s, border-color .16s; }
        .vy-set-file:hover { background:rgba(255,255,255,.85); }
        .vy-set-file:focus-within { border-color:var(--vy-accent-border); box-shadow:0 0 0 3px rgba(232,112,58,.14); }
        .vy-set-file input[type=file] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
        .vy-set-file svg { width:14px; height:14px; color:var(--vy-accent-text); flex-shrink:0; }
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
            <div style="font-size:12.5px;color:var(--vy-muted)">{{ __('livewire/owner/owner-settings.hero_setup', ['percent' => $hero['completeness']]) }}</div>
        </div>
        <div class="vy-set-stats">
            <div><div class="vy-set-stat-n">{{ $hero['channels_live'] }}</div><div class="vy-set-stat-l">{{ __('livewire/owner/owner-settings.stat_channels_live') }}</div></div>
            <div><div class="vy-set-stat-n">{{ $hero['team'] }}</div><div class="vy-set-stat-l">{{ __('livewire/owner/owner-settings.stat_team_members') }}</div></div>
            <div><div class="vy-set-stat-n">{{ $hero['invoices'] }}</div><div class="vy-set-stat-l">{{ __('livewire/owner/owner-settings.stat_fee_invoices') }}</div></div>
        </div>
    </div>

    <div class="vy-cols">
        {{-- ══ LEFT ══ --}}
        <div class="vy-stack">
            {{-- Profile — reuse OwnerAccountController (avatar / name+locale / email) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.profile_heading') }}</h2>
                <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.profile_sub') }}</p>

                <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding-bottom:18px;border-bottom:1px solid var(--vy-hairline);margin-bottom:18px">
                    @if ($account['avatar_url'])
                        <img src="{{ $account['avatar_url'] }}" alt="{{ __('livewire/owner/owner-settings.photo_alt') }}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;box-shadow:var(--vy-shadow-sm)">
                    @else
                        <span style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(140deg,#F0A57A,#D9662E);color:#fff;font-family:var(--vy-display);font-weight:600;font-size:22px">{{ $account['initials'] }}</span>
                    @endif
                    <div>
                        <form method="POST" action="{{ route('owner.account.avatar') }}" enctype="multipart/form-data" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            @csrf
                            {{-- The native file widget ("Choose File / No file chosen") was the only unstyled
                                 control on the page and carried no accessible name. The input keeps its own box
                                 (opacity, never display:none — a hidden control is an unreachable one), the label
                                 draws the design's pill, and the ring lives on the label since the input is
                                 invisible. --}}
                            <label class="vy-set-file">
                                <input type="file" name="avatar" aria-label="{{ __('livewire/owner/owner-settings.choose_photo_aria') }}" accept="image/jpeg,image/png,image/webp">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="15" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-4-4-9 7"/></svg>
                                <span>{{ __('livewire/owner/owner-settings.choose_photo') }}</span>
                            </label>
                            <button type="submit" class="vy-btn" style="{{ $ghost }}">{{ __('livewire/owner/owner-settings.upload_photo') }}</button>
                        </form>
                        @error('avatar') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        @if ($account['avatar_url'])
                            <form method="POST" action="{{ route('owner.account.avatar.remove') }}" style="margin-top:8px">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:var(--vy-accent-text);font-size:12px;cursor:pointer;padding:0">{{ __('livewire/owner/owner-settings.remove_photo') }}</button>
                            </form>
                        @endif
                        <p style="font-size:11px;color:var(--vy-on-grad-soft);margin:8px 0 0">{{ __('livewire/owner/owner-settings.photo_hint') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('owner.account.update') }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
                        <div>
                            <label style="{{ $lbl }}" for="a_name">{{ __('common.name') }}</label>
                            <input id="a_name" type="text" name="name" class="vy-input" value="{{ old('name', $account['name']) }}" required>
                            @error('name') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="{{ $lbl }}" for="a_locale">{{ __('livewire/owner/owner-settings.language') }}</label>
                            <select id="a_locale" name="locale" class="vy-input">
                                @foreach ($localeOptions as $code => $label)
                                    <option value="{{ $code }}" @selected(old('locale', $account['locale']) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('locale') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div style="margin-top:16px;display:flex;justify-content:flex-end">
                        <button type="submit" class="vy-btn" style="{{ $btn }}">{{ __('livewire/owner/owner-settings.save_profile') }}</button>
                    </div>
                </form>

                <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--vy-hairline)">
                    <label style="{{ $lbl }}">{{ __('livewire/owner/owner-settings.login_email') }}</label>
                    <div style="font-size:13.5px;color:var(--vy-ink);margin-bottom:10px">{!! __('livewire/owner/owner-settings.currently', ['email' => e($account['email'])]) !!}
                        @if ($account['email_verified'])<span class="vy-status vy-status--paid" style="margin-left:6px"><span class="vy-dot"></span>{{ __('livewire/owner/owner-settings.verified') }}</span>@endif
                    </div>
                    @if ($account['pending_email'] !== '')
                        <div class="vy-card" style="background:rgba(224,146,30,.08);border:1px solid rgba(224,146,30,.25);padding:12px 14px;margin-bottom:10px">
                            <div style="font-size:12.5px;color:var(--vy-ink)">{!! __('livewire/owner/owner-settings.pending_change', ['new_email' => e($account['pending_email']), 'email' => e($account['email'])]) !!}</div>
                        </div>
                        <form method="POST" action="{{ route('owner.account.email.cancel') }}">
                            @csrf
                            <button type="submit" style="background:none;border:none;color:var(--vy-accent-text);font-size:12.5px;cursor:pointer;padding:0">{{ __('livewire/owner/owner-settings.cancel_change') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('owner.account.email') }}" style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap">
                            @csrf
                            <div style="flex:1;min-width:220px">
                                <input type="email" name="email" class="vy-input" placeholder="{{ __('livewire/owner/owner-settings.new_email_placeholder') }}" aria-label="{{ __('livewire/owner/owner-settings.new_email_aria') }}">
                                @error('email') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            {{-- ⚠️ RE-AUTH (audit H-3). Changing the login email moves the account's identity, so
                                 the current password is required — the same control the platform-admin and guest
                                 account pages already carry. Without it a hijacked session became permanent. --}}
                            <div style="flex:1;min-width:220px">
                                <label class="vy-sr-only" for="owner_email_current_password">{{ __('livewire/owner/owner-settings.current_password') }}</label>
                                <input type="password" id="owner_email_current_password" name="current_password"
                                       class="vy-input" placeholder="{{ __('livewire/owner/owner-settings.current_password') }}"
                                       autocomplete="current-password" required aria-label="{{ __('livewire/owner/owner-settings.current_password') }}">
                                @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="vy-btn" style="{{ $ghost }}">{{ __('livewire/owner/owner-settings.change_email') }}</button>
                        </form>
                        <span style="font-size:11px;color:var(--vy-on-grad-soft);display:block;margin-top:8px">{{ __('livewire/owner/owner-settings.change_email_hint') }}</span>
                    @endif
                </div>
            </div>

            {{-- Business & tax — reuse owner.settings.update (party) + owner.tax.save (tax, anti-drift) --}}
            @if ($isPrimaryOwner && $ownerParty)
                <div class="vy-card vy-fade">
                    <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.business_heading') }}</h2>
                    <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.business_sub') }}</p>
                    <form method="POST" action="{{ route('owner.settings.update') }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
                            <div>
                                <label style="{{ $lbl }}" for="o_name">{{ __('livewire/owner/owner-settings.business_name') }}</label>
                                <input id="o_name" type="text" name="name" class="vy-input" value="{{ old('name', $ownerParty['name']) }}" required>
                                @error('name') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="o_email">{!! __('livewire/owner/owner-settings.billing_email_label') !!}</label>
                                <input id="o_email" type="email" name="email" class="vy-input" value="{{ old('email', $ownerParty['email']) }}">
                                @error('email') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="o_phone">{!! __('livewire/owner/owner-settings.phone_label') !!}</label>
                                <input id="o_phone" type="text" name="phone" class="vy-input" value="{{ old('phone', $ownerParty['phone']) }}">
                                @error('phone') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        {{-- Registered address (GATE 3) — appears as the "Prepared for" block on your statement letterhead --}}
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:16px">
                            <div style="grid-column:1/-1">
                                <label style="{{ $lbl }}" for="o_street">{!! __('livewire/owner/owner-settings.street_label') !!}</label>
                                <input id="o_street" type="text" name="address_street" class="vy-input" value="{{ old('address_street', $ownerParty['address_street']) }}" placeholder="{{ __('livewire/owner/owner-settings.street_placeholder') }}">
                                @error('address_street') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="o_city">{{ __('livewire/owner/owner-settings.city') }}</label>
                                <input id="o_city" type="text" name="address_city" class="vy-input" value="{{ old('address_city', $ownerParty['address_city']) }}">
                                @error('address_city') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="o_postcode">{{ __('livewire/owner/owner-settings.postcode') }}</label>
                                <input id="o_postcode" type="text" name="address_postcode" class="vy-input" value="{{ old('address_postcode', $ownerParty['address_postcode']) }}">
                                @error('address_postcode') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="o_country">{{ __('livewire/owner/owner-settings.country') }}</label>
                                <input id="o_country" type="text" name="address_country" class="vy-input" value="{{ old('address_country', $ownerParty['address_country']) }}">
                                @error('address_country') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div style="margin-top:14px;display:flex;justify-content:flex-end">
                            <button type="submit" class="vy-btn" style="{{ $btn }}">{{ __('livewire/owner/owner-settings.save_business') }}</button>
                        </div>
                    </form>

                    {{-- Inline tax form → the ONE owner.tax.save path (anti-drift; feeds the remittance PDF) --}}
                    <form method="POST" action="{{ route('owner.tax.save') }}" style="margin-top:18px;padding-top:18px;border-top:1px solid var(--vy-hairline)">
                        @csrf
                        <label style="{{ $lbl }}">{{ __('livewire/owner/owner-settings.tax_vat') }}</label>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-top:2px">
                            <div>
                                <label style="{{ $lbl }}" for="t_type">{{ __('livewire/owner/owner-settings.registered_as') }}</label>
                                <select id="t_type" name="type" class="vy-input">
                                    <option value="individual" @selected(($ownerParty['type'] ?: 'individual') === 'individual')>{{ __('livewire/owner/owner-settings.type_individual') }}</option>
                                    <option value="company" @selected($ownerParty['type'] === 'company')>{{ __('livewire/owner/owner-settings.type_company') }}</option>
                                </select>
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_legal">{!! __('livewire/owner/owner-settings.legal_name_label') !!}</label>
                                <input id="t_legal" type="text" name="legal_name" class="vy-input" value="{{ old('legal_name', $ownerParty['legal_name']) }}">
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_country">{!! __('livewire/owner/owner-settings.tax_country_label') !!}</label>
                                <input id="t_country" type="text" name="tax_country" class="vy-input" value="{{ old('tax_country', $ownerParty['tax_country']) }}">
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_tax">{{ __('livewire/owner/owner-settings.tax_id') }}</label>
                                <input id="t_tax" type="text" name="tax_id" class="vy-input" placeholder="{{ $ownerParty['has_tax_id'] ? __('livewire/owner/owner-settings.tax_id_on_file') : __('livewire/owner/owner-settings.tax_id_required') }}">
                                @error('tax_id') <span style="{{ $err }}">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label style="{{ $lbl }}" for="t_vat">{!! __('livewire/owner/owner-settings.vat_number_label') !!}</label>
                                <input id="t_vat" type="text" name="vat_number" class="vy-input" value="{{ old('vat_number', $ownerParty['vat_number']) }}">
                            </div>
                        </div>
                        {{-- ⚠️ RE-AUTH: this changes the payee name on your remittance document and the residency your withholding is computed from. --}}
                        <div class="vy-field" style="margin:14px 0 0">
                            <label class="vy-field-label" for="settings_tax_pw">{{ __('livewire/owner/owner-settings.confirm_password') }}</label>
                            <input id="settings_tax_pw" type="password" name="current_password" class="vy-input"
                                   autocomplete="current-password" required aria-label="{{ __('livewire/owner/owner-settings.current_password') }}">
                            @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                        </div>
                        <div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                            <span style="font-size:11px;color:var(--vy-faint)">{{ __('livewire/owner/owner-settings.tax_encrypted_note') }}</span>
                            <button type="submit" class="vy-btn" style="{{ $ghost }}">{{ __('livewire/owner/owner-settings.save_tax') }}</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Billing & invoices — the REAL management fee (never an owner card / subscription) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.billing_heading') }}</h2>
                <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.billing_sub') }}</p>
                <div class="vy-set-fee">
                    <span>{{ __('livewire/owner/owner-settings.management_fee') }}</span>
                    <span style="font-weight:600">{{ $fee['percent'] !== null ? __('livewire/owner/owner-settings.fee_percent', ['percent' => $fee['percent']]) : __('livewire/owner/owner-settings.fee_per_agreement') }}</span>
                </div>
                <div class="vy-set-fee">
                    <span>{{ __('livewire/owner/owner-settings.how_billed') }}</span>
                    <span class="vy-muted">{{ __('livewire/owner/owner-settings.how_billed_value') }}</span>
                </div>
                <div class="vy-set-fee">
                    <span>{{ __('livewire/owner/owner-settings.fee_invoices') }}</span>
                    <span class="vy-muted">{{ trans_choice('livewire/owner/owner-settings.fee_invoices_count', $fee['invoices_count'], ['count' => $fee['invoices_count']]) }}</span>
                </div>
                <div style="margin-top:14px;display:flex;gap:16px;flex-wrap:wrap">
                    <a href="{{ route('owner.documents') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('livewire/owner/owner-settings.documents_link') }}</a>
                    <a href="{{ route('owner.agreement') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('livewire/owner/owner-settings.agreement_link') }}</a>
                </div>
            </div>

            {{-- Language & region — locale is editable in Profile; currency/timezone are the property's (honest) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.region_heading') }}</h2>
                <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.region_sub') }}</p>
                <div class="vy-set-fee">
                    <span>{{ __('livewire/owner/owner-settings.language') }}</span>
                    <span style="font-weight:600">{{ $localeOptions[$account['locale']] ?? 'English' }}</span>
                </div>
                @foreach ($region as $r)
                    <div class="vy-set-fee">
                        <span>{{ $r['label'] }}</span>
                        <span class="vy-muted">{{ $r['value'] }}</span>
                    </div>
                @endforeach
                <p style="font-size:11px;color:var(--vy-faint);margin:12px 0 0">{{ __('livewire/owner/owner-settings.region_note') }}</p>
            </div>

            {{-- Notifications — REAL in-app + email; SMS + Push honest-pending (no provider). Full per-event
                 control lives on the dedicated prefs page (reused — one store, anti-drift). --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.notif_heading') }}</h2>
                <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.notif_sub') }}</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                    @foreach ($notif['real'] as $ch)
                        <span class="vy-status vy-status--paid"><span class="vy-dot"></span>{{ $ch }}</span>
                    @endforeach
                    @foreach ($notif['pending'] as $ch)
                        <span class="vy-status vy-status--draft"><span class="vy-dot"></span>{{ __('livewire/owner/owner-settings.notif_channel_soon', ['channel' => $ch]) }}</span>
                    @endforeach
                </div>
                <p style="font-size:12px;color:var(--vy-muted);margin:0 0 12px">{{ __('livewire/owner/owner-settings.notif_note') }}</p>
                <a href="{{ route('owner.notification-preferences') }}" class="vy-btn" style="{{ $ghost }}">{{ __('livewire/owner/owner-settings.manage_notifications') }}</a>
            </div>

            {{-- Connected channels — Direct is REAL/always-on; OTA sync is account-gated → honest-pending,
                 never a fake "Connected" that syncs nothing (#37/#39). --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.channels_heading') }}</h2>
                <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.channels_sub') }}</p>
                @foreach ($channels as $c)
                    <div style="display:flex;align-items:center;gap:12px;padding:11px 0;border-top:1px solid var(--vy-hairline)">
                        <span style="flex:none;width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:{{ $c['status'] === 'live' ? 'var(--vy-grad)' : 'rgba(120,70,40,.1)' }};color:{{ $c['status'] === 'live' ? '#fff' : 'var(--vy-muted)' }};font-family:var(--vy-display);font-weight:600;font-size:14px">{{ mb_substr($c['name'], 0, 1) }}</span>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:13.5px;font-weight:600">{{ $c['name'] }}</div>
                            <div class="vy-line-sub">{{ $c['desc'] }}</div>
                        </div>
                        @if ($c['status'] === 'live')
                            <span class="vy-status vy-status--paid"><span class="vy-dot"></span>{{ __('livewire/owner/owner-settings.channel_always_on') }}</span>
                        @else
                            <span class="vy-status vy-status--draft"><span class="vy-dot"></span>{{ __('livewire/owner/owner-settings.channel_pending') }}</span>
                        @endif
                    </div>
                @endforeach
                <p style="font-size:11px;color:var(--vy-faint);margin:12px 0 0">{{ __('livewire/owner/owner-settings.channels_note') }}</p>
            </div>

            {{-- Team access — a read-only summary; full invite/role management on the dedicated page (reused) --}}
            <div class="vy-card vy-fade">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <h2 class="vy-set-h2" style="margin:0">{!! __('livewire/owner/owner-settings.team_heading', ['members' => e($team['count'])]) !!}</h2>
                    @if ($team['can_manage'])
                        <a href="{{ route('owner.team') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('livewire/owner/owner-settings.manage_team') }}</a>
                    @endif
                </div>
                <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.team_sub') }}</p>
                @foreach ($team['members'] as $m)
                    <div style="display:flex;align-items:center;gap:11px;padding:9px 0;border-top:1px solid var(--vy-hairline)">
                        <span style="flex:none;width:32px;height:32px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(140deg,#F0A57A,#D9662E);color:#fff;font-size:12px;font-weight:600">{{ $m['initials'] }}</span>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:13px;font-weight:600">{{ $m['name'] }}@if ($m['you'])<span style="color:var(--vy-faint);font-weight:500"> {{ __('livewire/owner/owner-settings.team_you') }}</span>@endif</div>
                        </div>
                        <span class="vy-muted" style="font-size:12px">{{ $m['role'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══ RIGHT ══ --}}
        <div class="vy-stack">
            {{-- Payout account — the REAL masked IBAN (CAP_PAYOUT-only, #28) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.payout_heading') }}</h2>
                @if ($canPayout && $payout['iban_masked'])
                    <div class="vy-set-iban" style="margin-top:6px">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
                        {{ $payout['iban_masked'] }}
                    </div>
                    <p style="font-size:12px;color:var(--vy-muted);margin:8px 0 0">{{ __('livewire/owner/owner-settings.payout_where') }}</p>
                @elseif ($canPayout)
                    <p class="vy-set-sub" style="margin-top:6px">{{ __('livewire/owner/owner-settings.payout_none') }}</p>
                @else
                    <p class="vy-set-sub" style="margin-top:6px">{{ __('livewire/owner/owner-settings.payout_managed') }}</p>
                @endif
                @if ($canPayout)
                    <a href="{{ route('owner.payout-method') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none;display:inline-block;margin-top:12px">{{ __('livewire/owner/owner-settings.payout_manage_link') }}</a>
                @endif
            </div>

            {{-- Appearance & motion — Reduce motion is REAL (the layout honors prefers-reduced-motion) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.appearance_heading') }}</h2>
                <div class="vy-set-fee">
                    <span>{{ __('livewire/owner/owner-settings.theme') }}</span>
                    <span class="vy-muted">{{ __('livewire/owner/owner-settings.theme_value') }}</span>
                </div>
                <div class="vy-set-fee">
                    <span>{{ __('livewire/owner/owner-settings.reduce_motion') }}</span>
                    <span class="vy-muted">{{ __('livewire/owner/owner-settings.reduce_motion_value') }}</span>
                </div>
                <p style="font-size:11px;color:var(--vy-faint);margin:12px 0 0">{{ __('livewire/owner/owner-settings.reduce_motion_note') }}</p>
            </div>

            {{-- Data export — the REAL CAP_EXPORTS-gated exports (owner-scoped #28) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.export_heading') }}</h2>
                @if ($exports !== [])
                    <p class="vy-set-sub">{{ __('livewire/owner/owner-settings.export_sub') }}</p>
                    @foreach ($exports as $x)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 0;border-top:1px solid var(--vy-hairline)">
                            <span style="font-size:13px">{{ $x['label'] }}</span>
                            <a href="{{ $x['href'] }}" class="vy-btn vy-btn--ghost" style="padding:4px 12px;font-size:12px">{{ __('common.download') }}</a>
                        </div>
                    @endforeach
                @else
                    <p class="vy-set-sub" style="margin:0">{{ __('livewire/owner/owner-settings.export_none') }}</p>
                @endif
            </div>

            {{-- Profile completeness — DERIVED checklist --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.completeness_heading') }}</h2>
                <p class="vy-set-sub" style="margin-bottom:10px">{{ __('livewire/owner/owner-settings.completeness_done', ['percent' => $hero['completeness']]) }}</p>
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
                            <a href="{{ $c['href'] }}" style="color:var(--vy-accent-text);font-size:12px;text-decoration:none">{{ __('livewire/owner/owner-settings.completeness_add') }}</a>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Security & privacy — link to the real security page --}}
            <a href="{{ route('owner.security') }}" class="vy-card vy-fade" style="display:flex;align-items:center;justify-content:space-between;gap:12px;text-decoration:none;color:var(--vy-ink)">
                <div>
                    <h2 class="vy-set-h2">{{ __('livewire/owner/owner-settings.security_heading') }}</h2>
                    <p style="font-size:12.5px;color:var(--vy-muted);margin:0">{{ __('livewire/owner/owner-settings.security_sub') }}</p>
                </div>
                <span style="color:var(--vy-accent-text);font-size:18px">→</span>
            </a>
        </div>
    </div>
</x-owner.shell>
