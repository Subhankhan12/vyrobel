{{-- Shared on-desk COLLECT form (GATE 2) — a REAL folio payment (cash / bank transfer) via the certified
     app.reservations.payment route: idempotent (payKey), tenant-scoped, returning to the board. Card stays the
     deferred Stripe seam (never faked, #45). Reads Alpine `row` / `collectAmt` / `payKey`. Amount only — no
     card data ever touches this form (GUARD 3 / SAQ A). NOT nested in another <form> (the B.1 lesson). --}}
<form method="POST" x-bind:action="'/app/reservations/' + row.id + '/payments'" class="vy-fdm-collect">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="idempotency_key" x-bind:value="payKey">
    <input type="hidden" name="return_to" value="front-desk">
    <input type="hidden" name="return_date" value="{{ $day['date'] }}">
    <div class="vy-fdm-collect-row">
        <input type="text" name="amount" x-model="collectAmt" inputmode="decimal" class="vy-input vy-fdm-amt" placeholder="0.00" aria-label="{{ __('livewire/partials/fd-collect-form.amount_label') }}">
        <select name="method" class="vy-input vy-fdm-method" aria-label="{{ __('livewire/partials/fd-collect-form.method_label') }}">
            <option value="cash">{{ __('livewire/partials/fd-collect-form.method_cash') }}</option>
            <option value="bank_transfer">{{ __('livewire/partials/fd-collect-form.method_bank_transfer') }}</option>
        </select>
        <button type="submit" class="vy-act vy-act--accent">{{ __('livewire/partials/fd-collect-form.collect_button') }}</button>
    </div>
    {{-- D-MONEY-COMMA — what the server will record, as typed, by the server's own rule (LocalNumber's script copy) --}}
    <p class="vy-money-readout" aria-live="polite"
       x-text="window.vyLocalNumber ? window.vyLocalNumber.readout(collectAmt, row.currency_symbol || '', @js(__('components/money-readout.records', ['amount' => ':amount'])), @js(__('components/money-readout.write_like', ['example' => \App\Support\I18n\LocalNumber::example()]))) : ''"></p>
@once @include('partials.local-number-script') @endonce
    <div class="vy-fdm-hint">{{ __('livewire/partials/fd-collect-form.hint') }}</div>
</form>
