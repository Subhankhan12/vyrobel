{{-- Guest login (Phase 4a · SG3) — authenticates on the GUEST guard. Static claim/forgot links (no enumeration).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Sign in')

@section('card')
    <h1 class="vy-title">Welcome back</h1>
    <p class="vy-sub">Sign in to manage your stays.</p>

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.login') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="email">Email</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <div class="vy-field">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <label class="vy-label" for="password" style="margin-bottom:0">Password</label>
                <a class="vy-link" href="{{ route('guest.password.request') }}">Forgot password?</a>
            </div>
            <div style="position:relative">
                <input class="vy-input" type="password" id="password" name="password" autocomplete="current-password" required style="margin-top:7px;padding-right:62px">
                <button type="button" aria-label="Show password"
                        onclick="var p=document.getElementById('password');var s=p.type==='password';p.type=s?'text':'password';this.textContent=s?'Hide':'Show'"
                        style="position:absolute;right:10px;top:15px;background:none;border:none;color:var(--vy-accent);font-size:12px;font-weight:600;cursor:pointer">Show</button>
            </div>
        </div>
        <button type="submit" class="vy-btn">Sign in</button>
    </form>

    <div class="vy-foot">New here? <a class="vy-link" href="{{ route('guest.claim') }}">Access your bookings</a></div>
@endsection
