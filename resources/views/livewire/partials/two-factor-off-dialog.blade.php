{{-- Turn off two-factor — the dialog of the shared two-factor section (two-factor-panel), at the page root of both Security
     pages. Opened by the page's `tfOff`; posts the panel's own disable route; re-asks for the password.
     ⚠️ Marked `_form` = 2fa-off, so a wrong password shows here only: the key `current_password` is shared by every password
     form on the page. --}}
@if ($twoFactor['state'] === 'enabled')
    <div class="vy-sx-scrim" x-cloak x-show="tfOff" x-transition.opacity @keydown.escape.window="tfOff = false" @click.self="tfOff = false">
        <div class="vy-sx-dialog" x-show="tfOff" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-tfoff-t" tabindex="-1" x-trap.noscroll="tfOff">
            <form method="POST" action="{{ $twoFactor['routes']['disable'] }}">
                @csrf
                <input type="hidden" name="_form" value="2fa-off">
                <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                <div class="vy-sx-dialog-head">
                    <span class="vy-sx-ico is-danger"><x-security.icon name="shield-alert" :size="18" /></span>
                    <div class="vy-sx-dialog-text">
                        <h3 class="vy-sx-dialog-title" id="vy-sx-tfoff-t">Turn off two-factor?</h3>
                        <p class="vy-sx-dialog-sub">Your password alone will sign you in, and your recovery codes stop working. Confirm with your password.</p>
                    </div>
                    <button type="button" class="vy-sx-x" @click="tfOff = false" aria-label="Close"><x-security.icon name="x" /></button>
                </div>
                <div class="vy-sx-dialog-body">
                    @if (old('_form') === '2fa-off')
                        @error('current_password') <div class="vy-sx-errors" role="alert">{{ $message }}</div> @enderror
                    @endif
                    <div class="vy-sx-field">
                        <label class="vy-sx-label" for="tf_disable_pw">Your password</label>
                        <x-password-field name="current_password" id="tf_disable_pw" autocomplete="current-password" :required="true" label="password" autofocus />
                    </div>
                </div>
                <div class="vy-sx-dialog-foot">
                    <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="tfOff = false">Keep it on</button>
                    <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Turn off</button>
                </div>
            </form>
        </div>
    </div>
@endif
