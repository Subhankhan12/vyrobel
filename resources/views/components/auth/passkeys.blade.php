@props([
    'passkeys' => [],
    'optionsRoute',
    'storeRoute',
    'removeRoute',
    // The two shells style headings differently; passed through so each realm keeps its own look.
    'h2' => '',
    'sub' => '',
    'ghost' => '',
    'err' => '',
])
{{--
    THE SHARED PASSKEY CARD — ONE implementation for both realms (D24-2, 2026-08-15).

    ⚠️ EXTRACTED FROM THE OWNER SECURITY PAGE, NOT COPIED TO THE OPERATOR ONE. The WebAuthn subsystem was
    fully built and realm-agnostic (`PasskeyService` has zero owner references; `Passkey belongsTo(User)`;
    `PasskeyController` works on `$request->user()`), and only the UI + the route mount were owner-only.
    Copying this card into the operator view would have been the quickest wiring and the §N.8f defect this
    project keeps paying for — two markups, two ceremonies, drifting the first time either is touched.

    ⚠️ ONLY THE ROUTE NAMES DIFFER BETWEEN REALMS. They are props, so the browser ceremony, the label header,
    the re-auth-to-remove form and the empty state are literally the same code on both pages.

    ⚠️ The ceremony itself is DEVICE-GATED: `navigator.credentials.create()` needs a real (or virtual)
    authenticator. What is automatable — the routes, the render, the user-scoping, the service invocation —
    is tested; the ceremony is proven separately in the owner realm via a CDP virtual authenticator and is
    unchanged by this pass.
--}}
{{-- ══ Passkeys (Wave B · GATE 4) — REAL WebAuthn; register runs the browser ceremony, remove re-auths ══ --}}
<div class="vy-card vy-fade" style="margin-bottom:16px;" x-data="vyPasskeys()">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="max-width:60ch;">
            <h2 style="{{ $h2 }}">Passkeys</h2>
            <p style="{{ $sub }}">Sign in with Face ID, Touch ID or your device PIN instead of a password — a passkey never leaves your device. Add one per device you use.</p>
        </div>
        <button type="button" class="vy-btn" @click="register()" x-bind:disabled="busy" style="flex-shrink:0;">
            <span x-show="!busy">Add a passkey</span><span x-show="busy" x-cloak>Working…</span>
        </button>
    </div>
    <p x-show="msg" x-cloak x-text="msg" style="font-size:12.5px;margin-top:10px;color:var(--vy-accent-text);"></p>
    @if (count($passkeys) > 0)
        <div style="display:flex;flex-direction:column;gap:2px;margin-top:10px;">
            @foreach ($passkeys as $p)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--vy-hairline);">
                    <div>
                        <div style="font-size:13.5px;font-weight:500;color:var(--vy-ink);">{{ $p['label'] }}</div>
                        <div style="font-size:11.5px;color:var(--vy-muted);">Last used {{ $p['last_used'] }}</div>
                    </div>
                    <form method="POST" action="{{ route($removeRoute, $p['id']) }}" x-data="{ open: false }" style="flex-shrink:0;">
                        @csrf
                        <button type="button" @click="open = !open" class="vy-btn" style="{{ $ghost }}font-size:12px;padding:6px 12px;">Remove</button>
                        <div x-show="open" x-cloak style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                            <label class="vy-sr-only" for="vy-lbl-passkeys-current-password">Your password</label>
                            <input id="vy-lbl-passkeys-current-password" type="password" name="current_password" class="vy-input" autocomplete="current-password" placeholder="Your password" style="max-width:180px;font-size:12.5px;">
                            <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12px;padding:6px 12px;">Confirm</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
        @error('current_password') <span style="{{ $err }}">{{ $message }}</span> @enderror
    @else
        <div style="font-size:13px;color:var(--vy-muted);margin-top:6px;">No passkeys yet. Add one to sign in without a password on this device.</div>
    @endif
</div>
<script>
    function vyPasskeys() {
        return {
            busy: false, msg: '',
            token: document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
            async register() {
                if (!window.PublicKeyCredential) { this.msg = 'This browser doesn’t support passkeys.'; return; }
                this.busy = true; this.msg = '';
                try {
                    const optRes = await fetch('{{ route($optionsRoute) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': this.token, 'Accept': 'application/json' } });
                    if (!optRes.ok) throw new Error('options');
                    const options = PublicKeyCredential.parseCreationOptionsFromJSON(await optRes.json());
                    const cred = await navigator.credentials.create({ publicKey: options });
                    const res = await fetch('{{ route($storeRoute) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.token, 'Content-Type': 'application/json', 'X-Passkey-Label': (navigator.userAgentData?.platform || 'This device') },
                        body: JSON.stringify(cred.toJSON()),
                    });
                    if (res.ok) { location.reload(); } else { this.msg = 'That passkey could not be registered. Try again.'; }
                } catch (e) { this.msg = 'Passkey registration was cancelled or failed.'; }
                this.busy = false;
            },
        };
    }
</script>
