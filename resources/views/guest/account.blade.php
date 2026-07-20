{{-- Guest Account (Phase 4a · SG4) — the authed guest's OWN profile, change-password, remove-access. Self-only
     (resolved from the guest guard). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Your account')

@section('card')
    <h1 class="vy-title">Your account</h1>
    <p class="vy-sub">Welcome, {{ $name }}. Manage your details and sign-in.</p>

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Profile --}}
    <form method="POST" action="{{ route('guest.account.update') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="first_name">First name</label>
            <input class="vy-input" id="first_name" name="first_name" value="{{ old('first_name', $profile['first_name']) }}" required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="last_name">Last name</label>
            <input class="vy-input" id="last_name" name="last_name" value="{{ old('last_name', $profile['last_name']) }}" required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="email">Email</label>
            <input class="vy-input" id="email" value="{{ $profile['email'] }}" disabled style="opacity:.6">
            <span style="display:block;font-size:11px;color:var(--vy-faint);margin-top:5px">Your email is your sign-in — contact the property to change it.</span>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="phone">Phone</label>
            <input class="vy-input" id="phone" name="phone" value="{{ old('phone', $profile['phone']) }}" autocomplete="tel">
        </div>
        <div class="vy-field">
            <label class="vy-label" for="nationality">Country (2-letter code)</label>
            <input class="vy-input" id="nationality" name="nationality" value="{{ old('nationality', $profile['nationality']) }}" maxlength="2">
        </div>
        <div class="vy-field">
            <label class="vy-label" for="language">Language</label>
            <input class="vy-input" id="language" name="language" value="{{ old('language', $profile['language']) }}" maxlength="10">
        </div>
        <button type="submit" class="vy-btn">Save details</button>
    </form>

    <hr style="border:none;border-top:1px solid var(--vy-hairline);margin:26px 0 20px">

    {{-- Change password --}}
    <h2 style="font-family:var(--vy-display);font-size:16px;font-weight:600;margin:0 0 14px;color:var(--vy-ink)">Change password</h2>
    <form method="POST" action="{{ route('guest.account.password') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="current_password">Current password</label>
            <input class="vy-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password">New password</label>
            <input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password_confirmation">Confirm new password</label>
            <input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="vy-btn">Change password</button>
    </form>

    <hr style="border:none;border-top:1px solid var(--vy-hairline);margin:26px 0 20px">

    {{-- Remove portal access (safe: revokes login, keeps records) --}}
    <h2 style="font-family:var(--vy-display);font-size:16px;font-weight:600;margin:0 0 8px;color:var(--vy-ink)">Remove portal access</h2>
    <p style="font-size:12.5px;line-height:1.5;color:var(--vy-muted);margin:0 0 14px">This removes your access to the guest portal. Your bookings stay on record with the property; to have your personal data erased, contact the property.</p>
    <form method="POST" action="{{ route('guest.account.delete') }}" onsubmit="return confirm('Remove your access to the guest portal?')">
        @csrf
        <button type="submit" class="vy-btn" style="background:none;color:var(--vy-amber);border:1px solid rgba(178,106,8,.45);box-shadow:none">Remove portal access</button>
    </form>

    <div class="vy-foot">
        <a class="vy-link" href="{{ route('guest.home') }}">Back</a>
        &nbsp;·&nbsp;
        <form method="POST" action="{{ route('guest.logout') }}" style="display:inline">
            @csrf
            <button type="submit" style="background:none;border:none;color:var(--vy-accent);font-size:11.5px;font-weight:500;cursor:pointer;padding:0">Sign out</button>
        </form>
    </div>
@endsection
