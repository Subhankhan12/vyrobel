{{-- Guest Account (Phase 4a · SG4) — the authed guest's OWN profile, change-password, remove-access. Self-only
     (resolved from the guest guard). Polish Batch 4: renders IN the portal shell (keeps the Trips/Payments/…/Account
     nav) instead of the narrow auth card. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="account" title="Account">
    <h1 class="vy-h1">Your account</h1>
    <p class="vy-lead">Welcome, {{ $name }}. Manage your details and sign-in.</p>

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Profile --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Your details</div>
        <form method="POST" action="{{ route('guest.account.update') }}" data-guard-submit novalidate>
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
                <input class="vy-input" id="email" value="{{ $profile['email'] }}" disabled>
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
            <x-busy-btn class="vy-btn" busy-label="Saving…">Save details</x-busy-btn>
        </form>
    </section>

    {{-- Change password --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Change password</div>
        <form method="POST" action="{{ route('guest.account.password') }}" data-guard-submit novalidate>
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
            <x-busy-btn class="vy-btn" busy-label="Saving…">Change password</x-busy-btn>
        </form>
    </section>

    {{-- Remove portal access (safe: revokes login, keeps records) --}}
    <section class="vy-card">
        <div class="vy-eyebrow" style="margin-bottom:8px">Remove portal access</div>
        <p style="font-size:12.5px;line-height:1.5;color:var(--vy-muted);margin:0 0 14px">This removes your access to the guest portal. Your bookings stay on record with the property; to have your personal data erased, contact the property.</p>
        <form method="POST" action="{{ route('guest.account.delete') }}" data-guard-submit onsubmit="return confirm('Remove your access to the guest portal?')">
            @csrf
            <x-busy-btn class="vy-btn" style="background:none;color:var(--vy-amber);border:1px solid rgba(178,106,8,.45);box-shadow:none" busy-label="Removing…">Remove portal access</x-busy-btn>
        </form>
    </section>
</x-guest.shell>
