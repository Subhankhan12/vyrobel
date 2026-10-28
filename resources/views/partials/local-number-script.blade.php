{{-- D-MONEY-COMMA — the SCRIPT copy of App\Support\I18n\LocalNumber, for what a page shows BEFORE it posts (a readout, a total).
     The server reads every amount itself with LocalNumber; this copy only lets the page say the same thing first.
     ⚠️ ONE rule, two languages: ops/i18n/number-parity.php fuzzes both with thousands of inputs and fails on any difference.
     Include with @include inside @once — it defines window.vyLocalNumber once per page. --}}
<script>
    window.vyLocalNumber = window.vyLocalNumber || (function (decimalMark) {
        var DIGITS = { '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
            '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9', '٫': '.', '٬': ',' };
        function count(s, ch) { return s.split(ch).length - 1; }
        function validGroups(groups) {
            var last = groups.length - 1;
            for (var i = 0; i < groups.length; i++) {
                var n = groups[i].length;
                var ok = i === 0 ? (n >= 1 && n <= 3) : (i === last ? n === 3 : (n === 2 || n === 3));
                if (! ok) return false;
            }
            return true;
        }
        function normalize(input, maxDecimals) {
            if (maxDecimals === undefined) maxDecimals = 2;
            var s = String(input === null || input === undefined ? '' : input).trim().replace(/[٠-٩۰-۹٫٬]/g, function (c) { return DIGITS[c]; });
            s = s.replace(/[€£$¥₺₽₹₩₫฿₸₾\s   '’]/g, '');
            if (s === '' || ! /^[0-9.,]+$/.test(s) || ! /[0-9]/.test(s)) return null;
            var dots = count(s, '.'), commas = count(s, ','), decimal = null;
            if (dots > 0 && commas > 0) {
                decimal = s.lastIndexOf('.') > s.lastIndexOf(',') ? '.' : ',';
            } else if (dots + commas === 1) {
                var mark = dots === 1 ? '.' : ',';
                var after = s.length - s.indexOf(mark) - 1;
                if (after >= 1 && after <= 2) { decimal = mark; }
                else if (after === 3) { if (mark === decimalMark) return null; }
                else { return null; }
            }
            var group = decimal === null ? (dots > 0 ? '.' : (commas > 0 ? ',' : null)) : (decimal === '.' ? ',' : '.');
            var whole = s, fraction = '';
            if (decimal !== null) {
                if (count(s, decimal) !== 1) return null;
                var parts = s.split(decimal); whole = parts[0]; fraction = parts[1] || '';
                if (fraction === '') return null;
            }
            if (fraction !== '' && (fraction.length > maxDecimals || (group !== null && fraction.indexOf(group) !== -1))) return null;
            if (group !== null && whole.indexOf(group) !== -1 && ! validGroups(whole.split(group))) return null;
            var digits = group !== null ? whole.split(group).join('') : whole;
            if (digits === '' || ! /^[0-9]+$/.test(digits)) return null;
            digits = digits.replace(/^0+/, '') || '0';
            return fraction === '' ? digits : digits + '.' + fraction;
        }
        function toMinor(input, maxMinor) {
            var c = normalize(input, 2);
            if (c === null || ! /^\d+(\.\d{1,2})?$/.test(c)) return null;
            var parts = c.split('.');
            var m = parseInt(parts[0], 10) * 100 + parseInt(((parts[1] || '') + '00').slice(0, 2), 10);
            if (m <= 0 || (maxMinor !== undefined && m > maxMinor)) return null;
            return m;
        }
        // the readout line under an amount: what will be recorded ("Records €150.50"), or how to write it — '' when empty
        function readout(raw, symbol, said, hint) {
            raw = String(raw === null || raw === undefined ? '' : raw).trim();
            if (raw === '') return '';
            var minor = toMinor(raw);
            if (minor === null) return hint;
            return said.split(':amount').join(symbol + (minor / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }
        return { normalize: normalize, toMinor: toMinor, readout: readout, example: @js(\App\Support\I18n\LocalNumber::example()) };
    })(@js(\App\Support\I18n\LocalNumber::decimalMark()));
</script>
