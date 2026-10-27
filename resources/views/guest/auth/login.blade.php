{{-- Guest login (Phase 4a · SG3) — authenticates on the GUEST guard. Static claim/forgot links (no enumeration).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/auth/login.page_title'))

@section('card')
    {{-- ⚠️ F-27 — FIRST CONTACT. The booking-confirmation email carries five deep links into the guest
         portal. All five sit behind `auth:guest`, so a guest who has PAID but never set up access is
         bounced here — and was greeted "Welcome back" and told to "Sign in to manage your stays", with the
         only route to actually getting in being a footer link. Money had moved and no login existed.

         ⚠️ THE SIGNED-LINK FIX DOES NOT APPLY HERE, and that was measured rather than assumed: 🔬 none of
         the five routes carries the `signed` middleware, so `signedRoute()` would be REDIRECTED BY
         `Authenticate` before its signature was ever checked. Swapping route() for signedRoute() would
         change the URL and nothing else. Granting a session from an emailed link is a NEW AUTHENTICATION
         DOOR and a founder decision — reported, not built.

         ⚠️ So this fixes the LANDING, which is where the defect actually shows. We cannot know whether the
         arriving person is new or returning — only that they came from a booking link — so the page serves
         BOTH honestly instead of guessing: a heading true either way, and the claim path promoted from a
         footer link to a real control. The claim FLOW itself is untouched. --}}
    @php
        $vyIntended = (string) session('url.intended', '');
        $vyFromBooking = $vyIntended !== '' && str_contains($vyIntended, '/guest/');
    @endphp

    @if ($vyFromBooking)
        <h1 class="vy-title">{{ __('guest/auth/login.booking_heading') }}</h1>
        <p class="vy-sub">{{ __('guest/auth/login.booking_subheading') }}</p>

        <a class="vy-btn" href="{{ route('guest.claim') }}" style="margin-bottom:18px;text-decoration:none">{{ __('guest/auth/login.booking_claim_button') }}</a>
    @else
        <h1 class="vy-title">{{ __('guest/auth/login.heading') }}</h1>
        <p class="vy-sub">{{ __('guest/auth/login.subheading') }}</p>
    @endif

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.login') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="email">{{ __('common.email') }}</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <div class="vy-field">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <label class="vy-label" for="password" style="margin-bottom:0">{{ __('auth/common.password') }}</label>
                <a class="vy-link" href="{{ route('guest.password.request') }}">{{ __('guest/auth/login.forgot_password') }}</a>
            </div>
            <div style="position:relative">
                <input class="vy-input" type="password" id="password" name="password" autocomplete="current-password" required style="margin-top:7px;padding-right:62px">
                <button type="button" aria-label="{{ __('auth/common.show_password') }}"
                        onclick="var p=document.getElementById('password');var s=p.type==='password';p.type=s?'text':'password';this.textContent=s?@js(__('guest/auth/login.hide')):@js(__('guest/auth/login.show'))"
                        style="position:absolute;right:10px;top:15px;background:none;border:none;color:var(--vy-accent-text);font-size:12px;font-weight:600;cursor:pointer">{{ __('guest/auth/login.show') }}</button>
            </div>
        </div>
        <button type="submit" class="vy-btn">{{ __('guest/auth/login.sign_in') }}</button>
    </form>

    <div class="vy-foot">{!! __('guest/auth/login.new_here', ['url' => e(route('guest.claim'))]) !!}</div>
@endsection
