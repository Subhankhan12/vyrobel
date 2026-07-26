{{-- SUPER ADMIN · GATE 0 — the 2FA login challenge (pending state; TOTP or single-use recovery code). --}}
<x-admin.auth title="Two-factor" heading="Verify it's you" sub="Enter the 6-digit code from your authenticator app.">
    <form method="POST" action="{{ route('admin.2fa.challenge.store') }}" novalidate>
        @csrf
        <div class="ad-field">
            <label class="ad-label" for="code">Authentication code</label>
            <input class="ad-input" type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required
                   style="letter-spacing: .3em; font-size: 18px; text-align: center;" placeholder="000000">
        </div>
        <button type="submit" class="ad-btn">Verify</button>
    </form>
    <p class="ad-note">Lost your device? Enter one of your recovery codes instead.</p>
</x-admin.auth>
