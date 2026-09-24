{{-- ADMIN 01 · PHASE 1 — accepting a platform-admin invitation: the new admin chooses their OWN password. It does
     not sign them in; they then sign in normally and are forced to set up two-factor. --}}
<x-admin.auth title="Accept invitation" heading="Set your password"
    :sub="'You have been invited to the Vyrobel platform console as '.($admin->roleEnum()?->label() ?? 'an admin').'.'">
    <form method="POST" action="{{ route('admin.invite.store', $token) }}" novalidate>
        @csrf
        <div class="ad-field">
            <label class="ad-label">Email</label>
            <input aria-label="Your email" class="ad-input" value="{{ $admin->email }}" readonly>
        </div>
        <div class="ad-field">
            <label class="ad-label" for="password">New password</label>
            <input class="ad-input" type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div class="ad-field">
            <label class="ad-label" for="password_confirmation">Confirm password</label>
            <input class="ad-input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>
        <p class="ad-note">{{ $hint }}</p>
        <button type="submit" class="ad-btn">Set password</button>
    </form>
    <p class="ad-note">Next you sign in, and set up two-factor authentication before the console opens. That step cannot be skipped.</p>
</x-admin.auth>
