{{-- SUPER ADMIN · GATE 0 — MANDATORY 2FA enrollment. An admin cannot reach the console until this is confirmed. --}}
<x-admin.auth title="Set up 2FA" heading="Secure your account" sub="Two-factor authentication is required for platform access.">
    <div style="display: grid; place-items: center; margin: 4px 0 16px;">
        <div style="background: #fff; padding: 12px; border-radius: 12px; line-height: 0;">
            {!! $qr !!}
        </div>
    </div>
    <p class="ad-note" style="margin-top: 0; text-align: center;">
        Scan with your authenticator app, or enter this key manually:
    </p>
    @if ($secret !== null)
        <div class="ad-well" style="text-align: center; font-family: ui-monospace, monospace; letter-spacing: .12em; font-size: 13px; color: var(--ad-ink); padding: 10px; margin: 8px 0 16px; word-break: break-all;">
            {{ $secret }}
        </div>
    @endif
    <form method="POST" action="{{ route('admin.2fa.setup.store') }}" novalidate>
        @csrf
        <div class="ad-field">
            <label class="ad-label" for="code">Enter the 6-digit code to confirm</label>
            <input class="ad-input" type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required
                   style="letter-spacing: .3em; font-size: 18px; text-align: center;" placeholder="000000">
        </div>
        <button type="submit" class="ad-btn">Confirm &amp; enable</button>
    </form>
</x-admin.auth>
