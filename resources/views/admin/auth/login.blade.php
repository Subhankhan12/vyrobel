{{-- SUPER ADMIN · GATE 0 — platform-admin login. Session + CSRF + rate-limited; 2FA is mandatory downstream. --}}
<x-admin.auth title="Sign in" heading="Platform sign in" sub="Restricted to Vyrobel platform administrators.">
    <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
        @csrf
        <div class="ad-field">
            <label class="ad-label" for="email">Email</label>
            <input class="ad-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
        </div>
        <div class="ad-field">
            <label class="ad-label" for="password">Password</label>
            <input class="ad-input" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="ad-btn">Continue</button>
    </form>
    <p class="ad-note">Access is by invitation only — there is no sign-up. Every session requires a second factor.</p>
</x-admin.auth>
