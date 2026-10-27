{{-- SUPER ADMIN · GATE 0 — the 2FA login challenge (pending state; TOTP or single-use recovery code). --}}
<x-admin.auth :title="__('admin/auth/two-factor-challenge.title')" :heading="__('admin/auth/two-factor-challenge.heading')" :sub="__('admin/auth/two-factor-challenge.sub')">
    <form method="POST" action="{{ route('admin.2fa.challenge.store') }}" novalidate>
        @csrf
        <div class="ad-field">
            <label class="ad-label" for="code">{{ __('admin/auth/two-factor-challenge.code_label') }}</label>
            <input class="ad-input" type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required
                   style="letter-spacing: .3em; font-size: 18px; text-align: center;" placeholder="{{ __('admin/auth/two-factor-challenge.code_placeholder') }}">
        </div>
        <button type="submit" class="ad-btn">{{ __('admin/auth/two-factor-challenge.verify_button') }}</button>
    </form>
    <p class="ad-note">{{ __('admin/auth/two-factor-challenge.lost_device_note') }}</p>
</x-admin.auth>
