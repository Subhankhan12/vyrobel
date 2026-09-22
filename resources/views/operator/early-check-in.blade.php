{{-- APPLY 01 #2 (founder, 2026-09-22) — the early check-in confirmation.
     ⚠️⚠️ A WARNING, NOT A CONTROL. This page REFUSES NOTHING: the primary button completes the check-in, and
     no permission gates it. It exists because `checked_in` is expensive to reach by mistake, not because
     early check-in is wrong — a front desk meets real early arrivals and this product must not argue with them.
     ⚠️ RAW-BLADE RULE: every `@` follows a space, newline or tag (a directive after a word character ships
     raw source to the user and assertSee sails past it). --}}
<x-layouts.operator title="Check in early?">
<x-operator.shell active="front-desk" title="This check-in is early" subtitle="{{ $reservation->reference }}">

    <div class="vy-card is-warn">
        <h2 class="vy-card-title">Arrival is {{ $daysEarly }} {{ $daysEarly === 1 ? 'day' : 'days' }} away</h2>
        <p>
            {{ $reservation->reference }} is booked to arrive on
            <strong>{{ \Carbon\CarbonImmutable::parse((string) $reservation->check_in)->format('D j M Y') }}</strong>.
            Checking in now records the guest as being in the room from today.
        </p>
        <p>
            If you meant a different booking, go back — the front desk board lists arrivals by date.
        </p>
    </div>

    {{-- ⚠️⚠️ CORRECTED 2026-09-22 IN THE SAME CHANGE THAT BUILT R9 — the previous version of this card said
         "there is no way to reverse a check-in in the product today", which was true when it was written and
         became false the moment ReverseCheckIn shipped. ⚠️ The comment above it had said exactly this must
         happen in the same change; it did. **A warning that outlives the limit it describes is how a screen
         starts lying**, and this card existed to tell the truth about a cost, not to discourage the action. --}}
    <div class="vy-card">
        <h2 class="vy-card-title">If this is the wrong booking</h2>
        <p>
            A checked-in stay cannot be cancelled directly. If you check in the wrong row you can
            <strong>reverse the check-in</strong> to put it back to confirmed, and cancel from there —
            two deliberate steps rather than one silent one.
        </p>
    </div>

    <form method="POST" action="{{ route('app.front-desk.check-in', $reservation->getKey()) }}">
        @csrf
        <input type="hidden" name="confirm_early" value="1">
        <button type="submit" class="vy-btn is-primary">Check in anyway</button>
        <a class="vy-btn" href="{{ route('app.front-desk') }}">Go back</a>
    </form>

</x-operator.shell>
</x-layouts.operator>
