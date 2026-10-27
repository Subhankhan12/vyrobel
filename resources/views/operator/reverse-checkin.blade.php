{{-- R9 (founder, 2026-09-22) — the confirmation surface for reversing a mis-clicked check-in.
     ⚠️ THE CONSEQUENCES APPEAR BEFORE THE PRESS, NOT AFTER IT — the same rule as reverse-checkout, and the
     same markup shape so a front desk does not learn two dialects for one idea. Both blocks are the action's
     own preview(); nothing here describes a consequence the action does not produce.
     ⚠️ RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator :title="__('operator/reverse-checkin.page_title')">
<x-operator.shell active="front-desk" :title="__('operator/reverse-checkin.heading')" subtitle="{{ $reservation->reference }}">

    <div class="vy-card">
        <h2 class="vy-card-title">{{ __('operator/reverse-checkin.will_reverse') }}</h2>
        <ul>
            @foreach ($disclosure['reversible'] as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>

    {{-- ⚠️ ALWAYS RENDERED, because it is never empty: the history line is unconditional. The money line only
         appears when there are REAL charges (#39) — a fabricated "charges may remain" on an empty folio
         teaches an operator to stop reading this panel. --}}
    <div class="vy-card">
        <h2 class="vy-card-title">{{ __('operator/reverse-checkin.will_not_reverse') }}</h2>
        <ul>
            @foreach ($disclosure['not_reversed'] as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>

    <form method="POST" action="{{ route('app.front-desk.reverse-checkin.store', $reservation->getKey()) }}" data-guard-submit>
        @csrf
        <button type="submit" class="vy-btn is-primary">{{ __('operator/reverse-checkin.reverse_button') }}</button>
        <a class="vy-btn" href="{{ route('app.front-desk') }}">{{ __('common.cancel') }}</a>
    </form>

</x-operator.shell>
</x-layouts.operator>
