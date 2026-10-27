{{-- R3 (founder, 2026-09-19) — the confirmation surface for reversing a mis-clicked checkout.
     ⚠️ THE WHOLE POINT IS THAT THE CONSEQUENCES APPEAR BEFORE THE PRESS, NOT AFTER IT. The three blocks below
     are the action's own preview() — what will be reversed, what will NOT be, and what the guest has already
     received. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator :title="__('operator/reverse-checkout.page_title')">
<x-operator.shell active="front-desk" :title="__('operator/reverse-checkout.heading')" subtitle="{{ $reservation->reference }}">

    <div class="vy-card">
        <h2 class="vy-card-title">{{ __('operator/reverse-checkout.will_reverse') }}</h2>
        <ul>
            @foreach ($disclosure['reversible'] as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>

    <div class="vy-card">
        <h2 class="vy-card-title">{{ __('operator/reverse-checkout.will_not_reverse') }}</h2>
        <ul>
            @foreach ($disclosure['not_reversed'] as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>

    {{-- ⚠️⚠️ THE THREE STATES. An unknown record renders as its own state, never as "nothing was sent" — a
         stay that checked out before this product started recording genuinely cannot be reported either way. --}}
    <div class="vy-card {{ $disclosure['review_email']['known'] ? '' : 'is-warn' }}">
        <h2 class="vy-card-title">
            @if (! $disclosure['review_email']['known'])
                {{ __('operator/reverse-checkout.review_request_unknown') }}
            @elseif ($disclosure['review_email']['sent'])
                {{ __('operator/reverse-checkout.review_request_sent') }}
            @else
                {{ __('operator/reverse-checkout.review_request_not_sent') }}
            @endif
        </h2>
        <p>{{ $disclosure['review_email']['message'] }}</p>
    </div>

    <form method="POST" action="{{ route('app.front-desk.reverse-checkout.store', $reservation->getKey()) }}">
        @csrf
        <button type="submit" class="vy-btn is-primary">{{ __('operator/reverse-checkout.reverse_button') }}</button>
        <a class="vy-btn" href="{{ route('app.front-desk') }}">{{ __('common.cancel') }}</a>
    </form>

</x-operator.shell>
</x-layouts.operator>
