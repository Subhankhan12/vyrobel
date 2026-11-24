{{-- VY-03 (audit of 2026-11-24) — the out-of-order confirmation. Taking this unit out of order would leave bookings with no room.
     ⚠️⚠️ NOTHING HAS BEEN SAVED WHEN THIS PAGE SHOWS. It states the real figures (nights, bookings at the worst, the first night) and
     re-posts the SAME work order with `acknowledge_oversell` if the operator goes ahead — a broken room is a fact; the page exists so
     nobody finds out at the front desk. The figures come from App\Support\Inventory\RoomCapacity, counted under the booking lock.
     ⚠️ RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator :title="__('operator/out-of-order-confirm.page_title')">
<x-operator.shell active="maintenance" :title="__('operator/out-of-order-confirm.heading')" subtitle="{{ $unit->code }}">

    <div class="vy-card is-warn">
        <h2 class="vy-card-title">{{ trans_choice('operator/out-of-order-confirm.peak_line', $peak, ['count' => $peak]) }}</h2>
        <p>
            {!! trans_choice('operator/out-of-order-confirm.nights_line', $nights, [
                'count' => $nights,
                'room_type' => e($roomTypeName),
                'date' => e(\Carbon\CarbonImmutable::parse((string) $firstNight)->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M Y'))),
            ]) !!}
        </p>
    </div>

    <div class="vy-card">
        <p>{{ __('operator/out-of-order-confirm.nothing_saved') }}</p>
    </div>

    <form method="POST" action="{{ route('app.maintenance.store') }}">
        @csrf
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <input type="hidden" name="acknowledge_oversell" value="1">
        <button type="submit" class="vy-btn is-primary">{{ __('operator/out-of-order-confirm.confirm') }}</button>
        <a class="vy-btn" href="{{ route('app.maintenance') }}">{{ __('common.back') }}</a>
    </form>

</x-operator.shell>
</x-layouts.operator>
