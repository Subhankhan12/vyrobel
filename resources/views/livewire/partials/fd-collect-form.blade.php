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
        <input type="text" name="amount" x-model="collectAmt" inputmode="decimal" class="vy-input vy-fdm-amt" placeholder="0.00" aria-label="Amount to collect">
        <select name="method" class="vy-input vy-fdm-method" aria-label="Payment method">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank transfer</option>
        </select>
        <button type="submit" class="vy-act vy-act--accent">Collect</button>
    </div>
    <div class="vy-fdm-hint">Records against the real folio · card terminal is the deferred Stripe seam — take cash or a bank transfer now.</div>
</form>
