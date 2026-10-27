{{-- Guest forgot-password (Phase 4a · SG3) — neutral response, no enumeration.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/auth/forgot-password.page_title'))

@section('card')
    <h1 class="vy-title">{{ __('guest/auth/forgot-password.heading') }}</h1>
    <p class="vy-sub">{{ __('guest/auth/forgot-password.subheading') }}</p>

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.password.email') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="email">{{ __('common.email') }}</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <button type="submit" class="vy-btn">{{ __('guest/auth/forgot-password.send_reset_link') }}</button>
    </form>

    <div class="vy-foot"><a class="vy-link" href="{{ route('guest.login') }}">{{ __('guest/auth/forgot-password.back_to_sign_in') }}</a></div>
@endsection
