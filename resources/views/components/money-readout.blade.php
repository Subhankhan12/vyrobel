@props(['for', 'currency' => 'EUR', 'verb' => 'records', 'maxMinor' => null])
{{-- D-MONEY-COMMA (ruled 2026-10-28) — under a payment / refund / charge amount: what the server WILL record, as the person types,
     read by the same rule as the server (LocalNumber; its script copy is fuzz-checked against it, ops/i18n/number-parity.php).
     "Records €150.50" — or, when the amount cannot be read (an ambiguous "1,234" on a decimal-comma page, letters, three decimals),
     how to write it. The server still reads and checks every amount itself; this only lets the page say it first.
     `verb`: records (a payment) · refunds (a refund) · charges (a charge).
     `maxMinor`: a REAL cap (the remaining refundable): above it the line says so and the form is not sent — the server refuses
     it too (#27), this only stops it before the round trip, as the number field's `max` did. --}}
@php
    $vyReadoutId = 'vy-mro-'.$for;
    $vyReadoutSaid = match ($verb) {
        'refunds' => __('components/money-readout.refunds', ['amount' => ':amount']),
        'charges' => __('components/money-readout.charges', ['amount' => ':amount']),
        default => __('components/money-readout.records', ['amount' => ':amount']),
    };
@endphp
@once @include('partials.local-number-script') @endonce
<p id="{{ $vyReadoutId }}" class="vy-money-readout" aria-live="polite" @if ($maxMinor !== null) data-max-minor="{{ (int) $maxMinor }}" @endif></p>
<script>
    (function () {
        var input = document.getElementById(@js($for)), out = document.getElementById(@js($vyReadoutId));
        if (! input || ! out || ! window.vyLocalNumber) return;
        var said = @js($vyReadoutSaid), hint = @js(__('components/money-readout.write_like', ['example' => \App\Support\I18n\LocalNumber::example()])), symbol = @js(\App\Support\Money\MoneyFormatter::symbol((string) $currency));
        var maxMinor = @js($maxMinor !== null ? (int) $maxMinor : null);
        var over = @js($maxMinor !== null ? __('components/money-readout.at_most', ['amount' => \App\Support\Money\MoneyFormatter::display((int) $maxMinor, (string) $currency)]) : '');
        function tooMuch() { var m = window.vyLocalNumber.toMinor(input.value); return maxMinor !== null && m !== null && m > maxMinor; }
        function show() {
            out.textContent = tooMuch() ? over : window.vyLocalNumber.readout(input.value, symbol, said, hint);
            out.style.color = (out.textContent === hint || out.textContent === over) ? 'var(--vy-amber-text, #9a5b00)' : 'var(--vy-muted)';
        }
        input.addEventListener('input', show);
        input.addEventListener('change', show);
        if (input.form && maxMinor !== null) {
            input.form.addEventListener('submit', function (e) { if (tooMuch()) { e.preventDefault(); show(); input.focus(); } });
        }
        show();
    })();
</script>
