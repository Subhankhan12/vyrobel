@props([
    'passkeys' => [],
    'optionsRoute',
    'storeRoute',
])
{{--
    THE SHARED PASSKEY SECTION — ONE implementation for both realms (D24-2, 2026-08-15).

    ⚠️ EXTRACTED FROM THE OWNER SECURITY PAGE, NOT COPIED TO THE OPERATOR ONE. The WebAuthn subsystem was
    fully built and realm-agnostic (`PasskeyService` has zero owner references; `Passkey belongsTo(User)`;
    `PasskeyController` works on `$request->user()`), and only the UI + the route mount were owner-only.
    Copying this card into the operator view would have been the quickest wiring and the §N.8f defect this
    project keeps paying for — two markups, two ceremonies, drifting the first time either is touched.

    ⚠️ ONLY THE ROUTE NAMES DIFFER BETWEEN REALMS. They are props, so the browser ceremony, the label header
    and the empty state are literally the same code on both pages.

    ⚠️ 2026-10-24 — a row of the Security kit's "Signing in" card on both pages (x-security.*). Remove opens the page's
    dialog (x-auth.passkey-remove-dialog, at the page root — a dialog inside a card is placed in the card, not the window),
    which re-asks for the password as before. The style props each page passed are gone: both pages look the same now.

    ⚠️ The ceremony itself is DEVICE-GATED: `navigator.credentials.create()` needs a real (or virtual)
    authenticator. What is automatable — the routes, the render, the user-scoping, the service invocation —
    is tested; the ceremony is proven separately in the owner realm via a CDP virtual authenticator and is
    unchanged by this pass.
--}}
<div class="vy-sx-block vy-passkeys" id="sx-passkeys" x-data="vyPasskeys()">
    <div class="vy-sx-row">
        <div class="vy-sx-row-main">
            <h3 class="vy-sx-row-title">{{ __('components/auth/passkeys.heading') }}</h3>
            {{-- ⚠️ 2026-10-05 — this said "a passkey never leaves your device": a synced one does (iCloud Keychain, Google
                 Password Manager). What is true for every passkey: no password is typed, and nothing a phishing page can reuse. --}}
            <span class="vy-sx-row-meta">{{ __('components/auth/passkeys.intro') }}</span>
        </div>
        <button type="button" class="vy-sx-btn vy-sx-btn--line vy-sx-btn--sm" @click="register()" x-bind:disabled="busy" aria-label="{{ __('components/auth/passkeys.add_aria') }}">
            <span x-show="!busy">{{ __('components/auth/passkeys.add_button') }}</span><span x-show="busy" x-cloak>{{ __('components/auth/passkeys.working') }}</span>
        </button>
    </div>
    <p x-show="msg" x-cloak x-text="msg" class="vy-sx-note is-warn" role="status"></p>
    @if (count($passkeys) > 0)
        <ul class="vy-sx-sub-rows">
            @foreach ($passkeys as $p)
                <li class="vy-sx-item">
                    <span class="vy-sx-ico"><x-security.icon name="fingerprint" /></span>
                    <div class="vy-sx-row-main">
                        <span class="vy-sx-row-title">{{ $p['label'] }}</span>
                        <span class="vy-sx-row-meta">{{ __('components/auth/passkeys.last_used', ['when' => $p['last_used']]) }}</span>
                    </div>
                    <button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="pkRemove = @js(['id' => $p['id'], 'label' => $p['label']])">{{ __('common.remove') }}</button>
                </li>
            @endforeach
        </ul>
    @else
        <p class="vy-sx-empty">{{ __('components/auth/passkeys.empty') }}</p>
    @endif
</div>
<script>
    function vyPasskeys() {
        return {
            busy: false, msg: '',
            token: document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
            // ⚠️ 2026-10-05 — the name the passkey keeps. It read only `navigator.userAgentData?.platform`, which Safari and
            //    Firefox do not have, so a passkey added there was called "This device" for ever — on every other device too.
            label() {
                const ua = navigator.userAgent || '';
                const platform = navigator.userAgentData?.platform
                    || (/iPhone/.test(ua) ? 'iPhone' : /iPad/.test(ua) ? 'iPad' : /Android/.test(ua) ? 'Android'
                        : /Mac OS X|Macintosh/.test(ua) ? 'Mac' : /Windows/.test(ua) ? 'Windows' : /Linux/.test(ua) ? 'Linux' : '');
                return platform ? 'Passkey on ' + platform : 'Passkey';
            },
            async register() {
                if (!window.PublicKeyCredential) { this.msg = @js(__('components/auth/passkeys.unsupported')); return; }
                this.busy = true; this.msg = '';
                try {
                    const optRes = await fetch('{{ route($optionsRoute) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': this.token, 'Accept': 'application/json' } });
                    if (!optRes.ok) throw new Error('options');
                    const options = PublicKeyCredential.parseCreationOptionsFromJSON(await optRes.json());
                    const cred = await navigator.credentials.create({ publicKey: options });
                    const res = await fetch('{{ route($storeRoute) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.token, 'Content-Type': 'application/json', 'X-Passkey-Label': this.label() },
                        body: JSON.stringify(cred.toJSON()),
                    });
                    if (res.ok) { location.reload(); } else { this.msg = @js(__('components/auth/passkeys.register_failed')); }
                } catch (e) { this.msg = @js(__('components/auth/passkeys.register_cancelled')); }
                this.busy = false;
            },
        };
    }
</script>
