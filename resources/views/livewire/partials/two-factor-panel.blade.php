{{-- SHARED two-factor section — operator (/app/security) + owner (/owner/security). Real TOTP (google2fa) + QR (bacon).
     $twoFactor = ['state' => disabled|pending|enabled, 'qr_svg', 'secret', 'recovery_codes', 'routes' => [enable, confirm,
     regenerate, disable]]; $recoveryCodes = ['left', 'total'] (App\Support\Security\SecurityOverview). Self-only writes
     (auth()-resolved in the controller).

     ⚠️ 2026-10-24 — a row of the Security kit's "Signing in" card on both pages (x-security.*, the founder's design): the state
     as a chip and a switch; turning it OFF opens the page's dialog (`tfOff`, two-factor-off-dialog at the page root — a dialog
     inside a card would be placed in the card, not the window). The forms, their `_form` marks and the code box are unchanged.
     ⚠️ WIDTH BELONGS TO THE CALLER, NOT HERE — the owner page once got a lone 560 px card from a width set in this partial.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php $tf = $twoFactor; $rc = $recoveryCodes ?? ['left' => 0, 'total' => 0]; @endphp

<div class="vy-sx-block vy-twofactor" id="sx-two-factor">
    <div class="vy-sx-row">
        <div class="vy-sx-row-main">
            <h3 class="vy-sx-row-title">Two-factor authentication
                @if ($tf['state'] === 'enabled')
                    <span class="vy-sx-chip">On</span>
                @elseif ($tf['state'] === 'pending')
                    <span class="vy-sx-chip is-warn">Setting up</span>
                @else
                    <span class="vy-sx-chip is-quiet">Off</span>
                @endif
            </h3>
            <span class="vy-sx-row-meta">A 6-digit code from your authenticator app (Google Authenticator, 1Password, …) at each sign-in.</span>
        </div>
        @if ($tf['state'] === 'enabled')
            <button type="button" class="vy-sx-switch" role="switch" aria-checked="true" aria-label="Two-factor authentication — turn off" @click="tfOff = true"></button>
        @elseif ($tf['state'] === 'disabled')
            <form method="POST" action="{{ $tf['routes']['enable'] }}">
                @csrf
                <button type="submit" class="vy-sx-switch" role="switch" aria-checked="false"><span class="vy-sx-sr">Enable two-factor</span></button>
            </form>
        @endif
    </div>

    @if (session('two_factor_status'))
        <p class="vy-sx-status" role="status">{{ session('two_factor_status') }}</p>
    @endif

    {{-- Recovery codes — shown EXACTLY ONCE (flashed on confirm/regenerate); only the encrypted form is stored. --}}
    @if ($tf['recovery_codes'])
        <div class="vy-sx-codes">
            <strong>Save your recovery codes</strong>
            <p>Each code works once if you lose your authenticator device. They won’t be shown again — store them somewhere safe.</p>
            <div class="vy-sx-codes-grid">
                @foreach ($tf['recovery_codes'] as $code)
                    <code>{{ $code }}</code>
                @endforeach
            </div>
        </div>
    @endif

    @if ($tf['state'] === 'enabled')
        <div class="vy-sx-item" id="sx-recovery">
            <span class="vy-sx-ico"><x-security.icon name="lock" /></span>
            <div class="vy-sx-row-main">
                <span class="vy-sx-row-title">Recovery codes</span>
                <span class="vy-sx-row-meta">{{ $rc['left'] > 0 ? $rc['left'].' of '.$rc['total'].' recovery codes left' : 'None left — make a new set now' }}</span>
            </div>
            <form method="POST" action="{{ $tf['routes']['regenerate'] }}">
                @csrf
                <button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">Regenerate</button>
            </form>
        </div>
        <div class="vy-sx-gap"></div>

    @elseif ($tf['state'] === 'pending')
        <p class="vy-sx-row-meta">Scan this QR code in your authenticator app, then enter the 6-digit code it shows to finish.</p>
        <div class="vy-sx-setup">
            <div class="vy-sx-qr">{!! $tf['qr_svg'] !!}</div>
            <div class="vy-sx-field">
                <span class="vy-sx-label">Or enter this key manually</span>
                <code class="vy-sx-key">{{ $tf['secret'] }}</code>
            </div>
        </div>

        <form method="POST" action="{{ $tf['routes']['confirm'] }}" class="vy-sx-field">
            @csrf
            {{-- marked like the turn-off form, so a page that shows unplaced errors at its top knows this one has a place --}}
            <input type="hidden" name="_form" value="2fa-confirm">
            <label class="vy-sx-label" for="tf_code">6-digit code from your app</label>
            <div class="vy-sx-inline-form">
                <input id="tf_code" type="text" name="code" class="vy-input" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required>
                <button type="submit" class="vy-sx-btn vy-sx-btn--grad">Confirm &amp; turn on</button>
            </div>
            @if (old('_form') === '2fa-confirm')
                @error('code') <span class="vy-sx-err">{{ $message }}</span> @enderror
            @endif
        </form>

        <form method="POST" action="{{ $tf['routes']['disable'] }}" class="vy-sx-gap">
            @csrf
            <button type="submit" class="vy-sx-btn vy-sx-btn--text">Cancel setup</button>
        </form>
    @endif
</div>
