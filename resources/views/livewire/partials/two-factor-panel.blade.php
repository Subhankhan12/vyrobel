{{-- SHARED two-factor panel — operator (/app/security) + owner (/owner/settings). Real TOTP (google2fa) +
     QR (bacon). $twoFactor = ['state' => disabled|pending|enabled, 'qr_svg', 'secret', 'recovery_codes',
     'routes' => [enable, confirm, regenerate, disable]]. Self-only writes (auth()-resolved in the controller).
     Styled inline (no vy-field-label) so it renders identically in the operator AND owner layouts.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php $tf = $twoFactor; $tflbl = 'display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:7px;'; @endphp

<div class="vy-card vy-fade" style="margin-bottom: 16px; max-width: 560px;">
    <h2 style="font-family: var(--vy-display); font-size: 15px; font-weight: 600; margin: 0 0 4px;">Two-factor authentication</h2>
    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">An authenticator app (Google Authenticator, 1Password, …) generates a 6-digit code at each sign-in — a second layer beyond your password.</p>

    @if (session('two_factor_status'))
        <div style="font-size: 13px; color: var(--vy-ink); background: rgba(232,112,58,.08); border-radius: var(--vy-r-sm, 10px); padding: 10px 13px; margin-bottom: 14px;">{{ session('two_factor_status') }}</div>
    @endif

    {{-- Recovery codes — shown EXACTLY ONCE (flashed on confirm/regenerate); only the encrypted form is stored. --}}
    @if ($tf['recovery_codes'])
        <div style="border: 1px solid var(--vy-accent-border, rgba(232,112,58,.28)); border-radius: var(--vy-r-md, 12px); padding: 14px; margin-bottom: 16px; background: rgba(255,255,255,.5);">
            <strong style="display: block; font-size: 13.5px; color: var(--vy-ink); margin-bottom: 4px;">Save your recovery codes</strong>
            <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 11px;">Each code works once if you lose your authenticator device. They won’t be shown again — store them somewhere safe.</p>
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px;">
                @foreach ($tf['recovery_codes'] as $code)
                    <code style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; letter-spacing: .04em; color: var(--vy-ink); background: rgba(0,0,0,.04); padding: 6px 10px; border-radius: 7px; text-align: center;">{{ $code }}</code>
                @endforeach
            </div>
        </div>
    @endif

    @if ($tf['state'] === 'enabled')
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: var(--vy-ink); margin-bottom: 16px;">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            Two-factor authentication is <strong>on</strong>.
        </div>

        <form method="POST" action="{{ $tf['routes']['regenerate'] }}" style="margin-bottom: 16px;">
            @csrf
            <button type="submit" class="vy-btn vy-btn--ghost" style="padding: 9px 15px; font-size: 12.5px;">Regenerate recovery codes</button>
        </form>

        <div style="border-top: 1px solid var(--vy-hairline); padding-top: 14px;">
            <form method="POST" action="{{ $tf['routes']['disable'] }}">
                @csrf
                <label style="{{ $tflbl }}" for="tf_disable_pw">Turn off — confirm your password</label>
                <div style="display: flex; gap: 8px; max-width: 360px;">
                    <input id="tf_disable_pw" type="password" name="current_password" class="vy-input" autocomplete="current-password" required>
                    <button type="submit" class="vy-btn vy-btn--ghost" style="white-space: nowrap; padding: 9px 15px;">Turn off</button>
                </div>
                @error('current_password') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
            </form>
        </div>

    @elseif ($tf['state'] === 'pending')
        <p style="font-size: 12.5px; color: var(--vy-ink); margin: 0 0 12px;">Scan this QR code in your authenticator app, then enter the 6-digit code it shows to finish.</p>
        <div style="display: flex; gap: 18px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 14px;">
            <div style="width: 168px; height: 168px; background: #fff; border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md, 12px); padding: 8px; flex: none;">{!! $tf['qr_svg'] !!}</div>
            <div style="flex: 1; min-width: 200px;">
                <div style="{{ $tflbl }}">Or enter this key manually</div>
                <code style="display: inline-block; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; letter-spacing: .08em; color: var(--vy-ink); background: rgba(0,0,0,.04); padding: 7px 11px; border-radius: 8px; word-break: break-all;">{{ $tf['secret'] }}</code>
            </div>
        </div>

        <form method="POST" action="{{ $tf['routes']['confirm'] }}" style="margin-bottom: 10px;">
            @csrf
            <label style="{{ $tflbl }}" for="tf_code">6-digit code from your app</label>
            <div style="display: flex; gap: 8px; max-width: 320px;">
                <input id="tf_code" type="text" name="code" class="vy-input" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required>
                <button type="submit" class="vy-btn vy-btn--accent" style="white-space: nowrap; padding: 9px 16px;">Confirm &amp; turn on</button>
            </div>
            @error('code') <span style="font-size: 11.5px; color: var(--vy-accent);">{{ $message }}</span> @enderror
        </form>

        <form method="POST" action="{{ $tf['routes']['disable'] }}">
            @csrf
            <button type="submit" class="vy-btn" style="background: none; color: var(--vy-muted); padding: 4px 0; font-size: 12px; text-decoration: underline;">Cancel setup</button>
        </form>

    @else
        <form method="POST" action="{{ $tf['routes']['enable'] }}">
            @csrf
            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 18px;">Enable two-factor</button>
        </form>
    @endif
</div>
