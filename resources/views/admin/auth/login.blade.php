{{-- SUPER ADMIN · GATE 0 — platform-admin login. Session + CSRF + rate-limited; 2FA is mandatory downstream. --}}
<x-admin.auth :title="__('admin/auth/login.page_title')" :heading="__('admin/auth/login.heading')" :sub="__('admin/auth/login.subheading')">
    <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
        @csrf
        <div class="ad-field">
            <label class="ad-label" for="email">{{ __('common.email') }}</label>
            <input class="ad-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
        </div>
        <div class="ad-field">
            <label class="ad-label" for="password">{{ __('auth/common.password') }}</label>
            <input class="ad-input" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="ad-btn">{{ __('common.continue') }}</button>
    </form>
    <p class="ad-note">{{ __('admin/auth/login.invitation_only_note') }}</p>
</x-admin.auth>
