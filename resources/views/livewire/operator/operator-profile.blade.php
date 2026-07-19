{{-- Operator PROFILE (Phase 1 · Sub-gate 5) — the logged-in operator's OWN name + work email (a real self-only
     persist, POST → controller → PRG, auth-resolved). Role / status / member-since are read-only. Unbacked
     design fields (phone / preferred name / timezone / density) are omitted, not faked.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="profile" title="Your profile" :subtitle="'Your account'" :viewer="$viewer">

        @if (session('profile_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('profile_notice') }}</div>
            </div>
        @endif

        {{-- Identity summary (read-only) --}}
        <div class="vy-card vy-fade vyp-head" style="margin-bottom: 16px;">
            <span class="vyp-av">{{ $viewer['initials'] }}</span>
            <div>
                <div class="vyp-name">{{ $user->name }}</div>
                <div class="vyp-meta">
                    @if ($roles !== '')<span class="vyp-role">{{ $roles }}</span>@endif
                    <span class="vyp-status">{{ ucfirst((string) $user->status) }}</span>
                    @if ($memberSince)<span class="vyp-since">Member since {{ $memberSince }}</span>@endif
                </div>
            </div>
        </div>

        {{-- Editable: name + work email (real users columns). --}}
        <form method="POST" action="{{ route('app.profile.update') }}" class="vy-card vy-fade">
            @csrf
            <h2 class="vy-card-title" style="margin-bottom: 4px;">Personal details</h2>
            <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">This is only your own profile — you can’t change anyone else’s here.</p>

            @if ($errors->any())
                <div class="vy-card" style="border-left: 3px solid var(--vy-amber); margin-bottom: 14px; padding: 11px 14px;">
                    <div style="font-size: 12.5px; color: var(--vy-ink);">{{ $errors->first() }}</div>
                </div>
            @endif

            <div class="vyp-grid2">
                <div class="vy-field" style="margin: 0;">
                    <label class="vy-field-label" for="name">Full name</label>
                    <input id="name" type="text" name="name" class="vy-input" value="{{ old('name', $user->name) }}" required autocomplete="name">
                </div>
                <div class="vy-field" style="margin: 0;">
                    <label class="vy-field-label" for="email">Work email</label>
                    <input id="email" type="email" name="email" class="vy-input" value="{{ old('email', $user->email) }}" required autocomplete="email">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save changes</button>
            </div>
        </form>

        {{-- Security lives on its own page (change password + 2FA). --}}
        <div class="vy-card vy-fade" style="margin-top: 16px;">
            <div style="font-size: 12.5px; color: var(--vy-muted);">
                Change your password or manage two-factor authentication on the <a href="{{ route('app.security') }}" style="color: var(--vy-accent);">Security</a> page.
            </div>
        </div>

    </x-operator.shell>

    <style>
        .vyp-head { display: flex; align-items: center; gap: 14px; }
        .vyp-av { width: 52px; height: 52px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 18px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vyp-name { font-size: 18px; font-weight: 700; color: var(--vy-ink); font-family: var(--vy-display, inherit); }
        .vyp-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; font-size: 12px; }
        .vyp-role { color: var(--vy-accent); font-weight: 600; }
        .vyp-status { color: var(--vy-muted); }
        .vyp-since { color: var(--vy-faint); }
        .vyp-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 720px) { .vyp-grid2 { grid-template-columns: 1fr; } }
    </style>
</div>
