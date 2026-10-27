{{-- ADMIN 01 · PHASE 1 — accepting a platform-admin invitation: the new admin chooses their OWN password. It does
     not sign them in; they then sign in normally and are forced to set up two-factor. --}}
<x-admin.auth :title="__('admin/auth/invite-accept.title')" :heading="__('admin/auth/invite-accept.heading')"
    :sub="__('admin/auth/invite-accept.invited_as', ['role' => $admin->roleEnum()?->label() ?? __('admin/auth/invite-accept.an_admin')])">
    <form method="POST" action="{{ route('admin.invite.store', $token) }}" novalidate>
        @csrf
        <div class="ad-field">
            <label class="ad-label">{{ __('common.email') }}</label>
            <input aria-label="{{ __('admin/auth/invite-accept.your_email_aria') }}" class="ad-input" value="{{ $admin->email }}" readonly>
        </div>
        <div class="ad-field">
            <label class="ad-label" for="password">{{ __('auth/common.new_password') }}</label>
            <input class="ad-input" type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div class="ad-field">
            <label class="ad-label" for="password_confirmation">{{ __('auth/common.confirm_password') }}</label>
            <input class="ad-input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>
        <p class="ad-note">{{ $hint }}</p>
        <button type="submit" class="ad-btn">{{ __('admin/auth/invite-accept.set_password_button') }}</button>
    </form>
    <p class="ad-note">{{ __('admin/auth/invite-accept.next_step_note') }}</p>
</x-admin.auth>
