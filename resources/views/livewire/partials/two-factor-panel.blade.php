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
            <h3 class="vy-sx-row-title">{{ __('livewire/partials/two-factor-panel.heading') }}
                @if ($tf['state'] === 'enabled')
                    <span class="vy-sx-chip">{{ __('livewire/partials/two-factor-panel.state_on') }}</span>
                @elseif ($tf['state'] === 'pending')
                    <span class="vy-sx-chip is-warn">{{ __('livewire/partials/two-factor-panel.state_setting_up') }}</span>
                @else
                    <span class="vy-sx-chip is-quiet">{{ __('livewire/partials/two-factor-panel.state_off') }}</span>
                @endif
            </h3>
            <span class="vy-sx-row-meta">{{ __('livewire/partials/two-factor-panel.meta') }}</span>
        </div>
        @if ($tf['state'] === 'enabled')
            <button type="button" class="vy-sx-switch" role="switch" aria-checked="true" aria-label="{{ __('livewire/partials/two-factor-panel.turn_off_switch_label') }}" @click="tfOff = true"></button>
        @elseif ($tf['state'] === 'disabled')
            <form method="POST" action="{{ $tf['routes']['enable'] }}">
                @csrf
                <button type="submit" class="vy-sx-switch" role="switch" aria-checked="false"><span class="vy-sx-sr">{{ __('livewire/partials/two-factor-panel.enable_label') }}</span></button>
            </form>
        @endif
    </div>

    @if (session('two_factor_status'))
        <p class="vy-sx-status" role="status">{{ session('two_factor_status') }}</p>
    @endif

    {{-- Recovery codes — shown EXACTLY ONCE (flashed on confirm/regenerate); only the encrypted form is stored. --}}
    @if ($tf['recovery_codes'])
        <div class="vy-sx-codes">
            <strong>{{ __('livewire/partials/two-factor-panel.save_codes_heading') }}</strong>
            <p>{{ __('livewire/partials/two-factor-panel.save_codes_body') }}</p>
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
                <span class="vy-sx-row-title">{{ __('livewire/partials/two-factor-panel.recovery_codes') }}</span>
                <span class="vy-sx-row-meta">{{ $rc['left'] > 0 ? __('livewire/partials/two-factor-panel.codes_left', ['left' => $rc['left'], 'total' => $rc['total']]) : __('livewire/partials/two-factor-panel.none_left') }}</span>
            </div>
            <form method="POST" action="{{ $tf['routes']['regenerate'] }}">
                @csrf
                <button type="submit" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm">{{ __('livewire/partials/two-factor-panel.regenerate_button') }}</button>
            </form>
        </div>
        <div class="vy-sx-gap"></div>

    @elseif ($tf['state'] === 'pending')
        <p class="vy-sx-row-meta">{{ __('livewire/partials/two-factor-panel.scan_qr') }}</p>
        <div class="vy-sx-setup">
            <div class="vy-sx-qr">{!! $tf['qr_svg'] !!}</div>
            <div class="vy-sx-field">
                <span class="vy-sx-label">{{ __('livewire/partials/two-factor-panel.manual_key') }}</span>
                <code class="vy-sx-key">{{ $tf['secret'] }}</code>
            </div>
        </div>

        <form method="POST" action="{{ $tf['routes']['confirm'] }}" class="vy-sx-field">
            @csrf
            {{-- marked like the turn-off form, so a page that shows unplaced errors at its top knows this one has a place --}}
            <input type="hidden" name="_form" value="2fa-confirm">
            <label class="vy-sx-label" for="tf_code">{{ __('livewire/partials/two-factor-panel.code_label') }}</label>
            <div class="vy-sx-inline-form">
                <input id="tf_code" type="text" name="code" class="vy-input" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required>
                <button type="submit" class="vy-sx-btn vy-sx-btn--grad">{{ __('livewire/partials/two-factor-panel.confirm_button') }}</button>
            </div>
            @if (old('_form') === '2fa-confirm')
                @error('code') <span class="vy-sx-err">{{ $message }}</span> @enderror
            @endif
        </form>

        <form method="POST" action="{{ $tf['routes']['disable'] }}" class="vy-sx-gap">
            @csrf
            <button type="submit" class="vy-sx-btn vy-sx-btn--text">{{ __('livewire/partials/two-factor-panel.cancel_setup_button') }}</button>
        </form>
    @endif
</div>
