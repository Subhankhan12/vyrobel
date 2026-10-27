{{-- Guest claim / booking-tied sign-up (Phase 4a · SG2). Enter the email from a real booking; the response is
     always neutral (no enumeration). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/auth/claim.page_title'))

@section('card')
    <h1 class="vy-title">{{ __('guest/auth/claim.heading') }}</h1>
    <p class="vy-sub">{{ __('guest/auth/claim.subheading') }}</p>

    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.claim.store') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="email">{{ __('guest/auth/claim.booking_email') }}</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <button type="submit" class="vy-btn">{{ __('guest/auth/claim.send_link') }}</button>
    </form>

    <div class="vy-foot">{!! __('guest/auth/claim.already_set_up', ['url' => e(route('guest.login'))]) !!}</div>
@endsection
