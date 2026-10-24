@props(['removeRoute'])
{{-- Remove a passkey — the dialog of the shared passkey section (x-auth.passkeys), at the page root of both Security pages.
     Opened by the page's `pkRemove` ({id, label}); posts the realm's own remove route; re-asks for the password (self-only
     and re-authed in PasskeyController). ⚠️ Marked `_form` = passkey-remove and `_passkey` = its id, so a wrong password
     reopens THIS dialog for THIS passkey, and shows nowhere else. --}}
<div class="vy-sx-scrim" x-cloak x-show="pkRemove !== null" x-transition.opacity @keydown.escape.window="pkRemove = null" @click.self="pkRemove = null">
    <div class="vy-sx-dialog" x-show="pkRemove !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-pk-t" tabindex="-1" x-trap.noscroll="pkRemove !== null">
        <form method="POST" :action="pkRemove ? @js(route($removeRoute, ['passkey' => '__ID__'])).replace('__ID__', pkRemove.id) : '#'">
            @csrf
            <input type="hidden" name="_form" value="passkey-remove">
            <input type="hidden" name="_passkey" :value="pkRemove ? pkRemove.id : ''">
            <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
            <div class="vy-sx-dialog-head">
                <span class="vy-sx-ico is-danger"><x-security.icon name="fingerprint" :size="18" /></span>
                <div class="vy-sx-dialog-text">
                    <h3 class="vy-sx-dialog-title" id="vy-sx-pk-t">Remove <span x-text="pkRemove ? pkRemove.label : ''"></span>?</h3>
                    <p class="vy-sx-dialog-sub">That device will need your password to sign in again. Confirm with your password.</p>
                </div>
                <button type="button" class="vy-sx-x" @click="pkRemove = null" aria-label="Close"><x-security.icon name="x" /></button>
            </div>
            <div class="vy-sx-dialog-body">
                @if (old('_form') === 'passkey-remove')
                    @error('current_password') <div class="vy-sx-errors" role="alert">{{ $message }}</div> @enderror
                @endif
                <div class="vy-sx-field">
                    <label class="vy-sx-label" for="vy-sx-pk-pw">Your password</label>
                    <x-password-field name="current_password" id="vy-sx-pk-pw" autocomplete="current-password" :required="true" label="password" autofocus />
                </div>
            </div>
            <div class="vy-sx-dialog-foot">
                <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="pkRemove = null">Cancel</button>
                <button type="submit" class="vy-sx-btn vy-sx-btn--danger">Remove</button>
            </div>
        </form>
    </div>
</div>
