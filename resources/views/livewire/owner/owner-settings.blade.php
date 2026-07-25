{{-- Owner "PROFILE & ACCOUNT" (owner account area · Gate 1). TWO identities, separated:
       • MY PROFILE — per-USER (auth-resolved): avatar, name, login-email (re-verified), language. Works for
         EVERY owner-realm user incl. accountant/view-only SUB-USERS (the sub-user 404 fix).
       • OWNER / BUSINESS DETAILS — PRIMARY-OWNER-ONLY (#42); tax/VAT + bank live on their own screens (linked).
     Every write is a controller POST (never a Livewire action). See App\Livewire\Owner\OwnerSettings.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php
    $lbl = 'display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:7px;';
    $h2 = 'font-family:var(--vy-display);font-size:15px;font-weight:600;margin:0 0 4px;';
    $sub = 'font-size:12.5px;color:var(--vy-muted);margin:0 0 16px;';
    $err = 'font-size:11.5px;color:var(--vy-accent);display:block;margin-top:5px;';
    $btn = 'background:var(--vy-grad);color:#fff;padding:11px 22px;';
    $ghost = 'background:rgba(255,255,255,.6);border:1px solid var(--vy-glass-border);color:var(--vy-ink);padding:9px 16px;';
@endphp
<div>
    <x-owner.shell active="settings" :title="'Profile & account'" :viewer="$viewer">

        @if (session('owner_notice'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px;">
                <div style="font-size:13.5px;color:var(--vy-ink);">{{ session('owner_notice') }}</div>
            </div>
        @endif

        {{-- ══ MY PROFILE — per-USER (every owner-realm user, incl. sub-users) ══════════════════════════ --}}
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">My profile</h2>
            <p style="{{ $sub }}">Your personal sign-in — photo, name, email and language. Only you can change these.</p>

            {{-- avatar --}}
            <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding-bottom:18px;border-bottom:1px solid var(--vy-hairline);margin-bottom:18px;">
                @if ($account['avatar_url'])
                    <img src="{{ $account['avatar_url'] }}" alt="Your photo" style="width:64px;height:64px;border-radius:50%;object-fit:cover;box-shadow:var(--vy-shadow-sm);">
                @else
                    <span style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(140deg,#F0A57A,#D9662E);color:#fff;font-family:var(--vy-display);font-weight:600;font-size:22px;">{{ $account['initials'] }}</span>
                @endif
                <div>
                    <form method="POST" action="{{ route('owner.account.avatar') }}" enctype="multipart/form-data" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        @csrf
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" style="font-size:12.5px;color:var(--vy-muted);max-width:220px;">
                        <button type="submit" class="vy-btn" style="{{ $ghost }}">Upload photo</button>
                    </form>
                    @error('avatar') <span style="{{ $err }}">{{ $message }}</span> @enderror
                    @if ($account['avatar_url'])
                        <form method="POST" action="{{ route('owner.account.avatar.remove') }}" style="margin-top:8px;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background:none;border:none;color:var(--vy-accent);font-size:12px;cursor:pointer;padding:0;">Remove photo</button>
                        </form>
                    @endif
                    <p style="font-size:11px;color:var(--vy-faint);margin:8px 0 0;">JPG, PNG or WebP · up to 4&nbsp;MB.</p>
                </div>
            </div>

            {{-- name + language --}}
            <form method="POST" action="{{ route('owner.account.update') }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
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
                        <span style="font-size:11px;color:var(--vy-faint);display:block;margin-top:6px;">Vyrobel is in English for now — your choice is saved for when more languages arrive.</span>
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                    <button type="submit" class="vy-btn" style="{{ $btn }}">Save profile</button>
                </div>
            </form>

            {{-- login email — RE-VERIFIED before it switches --}}
            <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--vy-hairline);">
                <label style="{{ $lbl }}">Login email</label>
                <div style="font-size:13.5px;color:var(--vy-ink);margin-bottom:10px;">Currently <strong>{{ $account['email'] }}</strong></div>
                @if ($account['pending_email'] !== '')
                    <div class="vy-card" style="background:rgba(224,146,30,.08);border:1px solid rgba(224,146,30,.25);padding:12px 14px;margin-bottom:10px;">
                        <div style="font-size:12.5px;color:var(--vy-ink);">Pending change to <strong>{{ $account['pending_email'] }}</strong> — check that inbox and click the confirmation link. Your email stays <strong>{{ $account['email'] }}</strong> until you do.</div>
                    </div>
                    <form method="POST" action="{{ route('owner.account.email.cancel') }}">
                        @csrf
                        <button type="submit" style="background:none;border:none;color:var(--vy-accent);font-size:12.5px;cursor:pointer;padding:0;">Cancel this change</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('owner.account.email') }}" style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                        @csrf
                        <div style="flex:1;min-width:220px;">
                            <input type="email" name="email" class="vy-input" placeholder="new@example.com" aria-label="New login email">
                            @error('email') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="vy-btn" style="{{ $ghost }}">Change email</button>
                    </form>
                    <span style="font-size:11px;color:var(--vy-faint);display:block;margin-top:8px;">We’ll email a confirmation link to the new address. Your email only changes once you click it.</span>
                @endif
            </div>
        </div>

        {{-- ══ OWNER / BUSINESS DETAILS — PRIMARY-OWNER-ONLY (#42) ══════════════════════════════════════ --}}
        @if ($isPrimaryOwner && $ownerParty)
            <div class="vy-card vy-fade" style="margin-bottom:16px;">
                <h2 style="{{ $h2 }}">Owner &amp; business details</h2>
                <p style="{{ $sub }}">The business the management team pays out to. Only you, the account owner, can change these.</p>
                <form method="POST" action="{{ route('owner.settings.update') }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                        <div>
                            <label style="{{ $lbl }}" for="o_name">Business / owner name</label>
                            <input id="o_name" type="text" name="name" class="vy-input" value="{{ old('name', $ownerParty['name']) }}" required>
                            @error('name') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="{{ $lbl }}" for="o_email">Billing email <span style="text-transform:none;font-weight:500;color:var(--vy-faint);">· optional</span></label>
                            <input id="o_email" type="email" name="email" class="vy-input" value="{{ old('email', $ownerParty['email']) }}">
                            @error('email') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label style="{{ $lbl }}" for="o_phone">Phone <span style="text-transform:none;font-weight:500;color:var(--vy-faint);">· optional</span></label>
                            <input id="o_phone" type="text" name="phone" class="vy-input" value="{{ old('phone', $ownerParty['phone']) }}">
                            @error('phone') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                        <button type="submit" class="vy-btn" style="{{ $btn }}">Save business details</button>
                    </div>
                </form>
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--vy-hairline);display:flex;gap:20px;flex-wrap:wrap;">
                    <a href="{{ route('owner.tax') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none;">Tax ID, VAT &amp; company details →</a>
                    <a href="{{ route('owner.payout-method') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none;">Bank &amp; payout account →</a>
                </div>
            </div>
        @else
            <div class="vy-card vy-fade" style="margin-bottom:16px;">
                <h2 style="{{ $h2 }}">Owner &amp; business details</h2>
                <p style="{{ $sub }}margin-bottom:0;">Business, tax and bank details are managed by the account owner. You can update your own sign-in above.</p>
            </div>
        @endif

        {{-- ══ Change password — self-only, current-password-verified (moves to Security next) ══════════ --}}
        @if (session('password_status'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px;">
                <div style="font-size:13.5px;color:var(--vy-ink);">{{ session('password_status') }}</div>
            </div>
        @endif
        <div class="vy-card vy-fade" style="margin-bottom:16px;">
            <h2 style="{{ $h2 }}">Change password</h2>
            <p style="{{ $sub }}">Enter your current password to confirm it’s you. Other devices with “remember me” are signed out.</p>
            <form method="POST" action="{{ route('owner.settings.password') }}">
                @csrf
                <div style="display:grid;gap:14px;max-width:380px;">
                    <div>
                        <label style="{{ $lbl }}" for="cur_pw">Current password</label>
                        <input id="cur_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" required>
                        @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="new_pw">New password</label>
                        <input id="new_pw" type="password" name="password" class="vy-input" autocomplete="new-password" required>
                        @error('password') <span style="{{ $err }}">{{ $message }}</span> @enderror
                        <span style="font-size:11px;color:var(--vy-faint);">At least 8 characters.</span>
                    </div>
                    <div>
                        <label style="{{ $lbl }}" for="conf_pw">Confirm new password</label>
                        <input id="conf_pw" type="password" name="password_confirmation" class="vy-input" autocomplete="new-password" required>
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                    <button type="submit" class="vy-btn" style="{{ $btn }}">Update password</button>
                </div>
            </form>
        </div>

        {{-- ══ Two-factor — REAL TOTP (shared panel) ═══════════════════════════════════════════════════ --}}
        @include('livewire.partials.two-factor-panel')

        {{-- ══ More — REAL links (the old "Soon" stubs now exist) ══════════════════════════════════════ --}}
        <div class="vy-card vy-fade">
            <h2 style="{{ $h2 }}margin-bottom:12px;">More</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <a href="{{ route('owner.notification-preferences') }}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 14px;border:1px solid var(--vy-hairline);border-radius:10px;text-decoration:none;color:var(--vy-ink);">
                    <span style="font-size:13px;">Notification preferences</span>
                    <span style="color:var(--vy-accent);">→</span>
                </a>
                @if ($canPayout)
                    <a href="{{ route('owner.payout-method') }}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 14px;border:1px solid var(--vy-hairline);border-radius:10px;text-decoration:none;color:var(--vy-ink);">
                        <span style="font-size:13px;">Payout account &amp; bank details</span>
                        <span style="color:var(--vy-accent);">→</span>
                    </a>
                @endif
                @if ($isPrimaryOwner)
                    <a href="{{ route('owner.team') }}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 14px;border:1px solid var(--vy-hairline);border-radius:10px;text-decoration:none;color:var(--vy-ink);">
                        <span style="font-size:13px;">Team &amp; access</span>
                        <span style="color:var(--vy-accent);">→</span>
                    </a>
                @endif
            </div>
        </div>

    </x-owner.shell>
</div>
