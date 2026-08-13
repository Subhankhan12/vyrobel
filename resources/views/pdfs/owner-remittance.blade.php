{{-- Owner remittance advice PDF (Wave 1 · Sub-gate 2). PDF-SAFE: dompdf renders HTML4/CSS2.1, so this is
     a table-based, inline-CSS branded document (NOT the app's frosted glass) — no flexbox, grid,
     backdrop-filter, webfonts or external assets. DejaVu Sans is dompdf's bundled Unicode font: it renders
     € and the U+2212 minus correctly. Same visual language as owner-statement.blade.php.

     Every figure is a certified read: the amount is the payout's own, the breakdown is the source
     statement's REAL owner_statement_lines (which sum to its net), all through the shared MoneyFormatter —
     so this advice reconciles to the owner portal to the cent. The design's fiction (IBAN / "···· 4821" /
     "Revolut Business" / "est. 3 Nov" / "Scheduled" / "SEPA" / RA-2025- + OS-2025- refs / operator VAT +
     postal address) has NO backing field and is OMITTED rather than invented — a document has no "Soon".
     Optional rows (statement no. / period / paid-on / reference / failure / FX) render ONLY when the real
     field is set. No guest names ever appear on an owner surface. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 36px 40px; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; }
        .muted { color: #5F5853; }
        .eyebrow { color: #C2410C; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 20px; margin: 2px 0 0; }
        .hdr td { vertical-align: top; }
        .sum { width: 100%; border-collapse: collapse; margin-top: 24px; background-color: #FDF7F3; border: 1px solid #EFE3D9; border-radius: 8px; }
        .sum td { padding: 14px 16px; vertical-align: top; }
        .figure { font-size: 22px; font-weight: bold; margin-top: 3px; }
        .pill { display: inline-block; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; border-radius: 8px; padding: 2px 8px; }
        .pill--paid { color: #1B6B48; background-color: #E4F2EA; }
        .pill--pending,
        .pill--processing,
        .pill--held { color: #704204; background-color: #FBEFD8; }
        .pill--failed { color: #A33227; background-color: #FBE6E2; }
        .pill--reversed { color: #6B6058; background-color: #F0E9E3; }
        .meta { width: 100%; border-collapse: collapse; margin: 18px 0 6px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #5F5853; width: 120px; }
        .prov { display: inline-block; font-size: 8.5px; font-weight: bold; text-transform: uppercase; color: #704204; background-color: #FBEFD8; border-radius: 8px; padding: 1px 7px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        table.lines tr.net td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; font-size: 14px; padding-top: 12px; }
        .neg { color: #5F5853; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #6C645F; }
        .reconcile { margin-top: 6px; font-size: 11px; color: #4A4038; }
    </style>
</head>
<body>
    <div class="wrap">
        {{-- Letterhead — the Vyrobel brand-mark + the operator's REAL registered identity (company · country ·
             VAT, when set) + the period-derived remittance reference --}}
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span style="font-size:16px; font-weight:bold; padding-left:6px;">{{ $operator }}</span>
                    <div class="muted" style="margin-top:6px; font-size:10.5px;">{{ $operator_addr }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Remittance advice</div>
                    <h1>Payout remittance</h1>
                    @if ($doc_ref)<div style="font-size:13px; font-weight:bold; margin-top:3px;">{{ $doc_ref }}</div>@endif
                    @if ($period_label)<div class="muted" style="margin-top:2px; font-size:11px;">{{ $period_label }}{{ $owner_name ? ' · '.$owner_name : '' }}</div>@endif
                </td>
            </tr>
        </table>

        {{-- Payout summary — the REAL amount + the status word straight from the payouts enum + the bank line
             (masked IBAN, CAP_PAYOUT-gated; the settle date is real only once paid — never an estimate) --}}
        <table class="sum">
            <tr>
                <td>
                    <div class="eyebrow">{{ $amount_label }}</div>
                    <div class="figure">{{ $amount_display }}</div>
                    <div class="muted" style="margin-top:6px; font-size:10.5px;">{{ $method }}@if ($iban_masked) · IBAN {{ $iban_masked }}@endif@if ($paid_on) · paid {{ $paid_on }}@endif</div>
                </td>
                <td style="text-align:right;">
                    <span class="pill pill--{{ $status_tone }}">{{ $status_label }}</span>
                    @if ($sequence_no)<div class="muted" style="margin-top:8px; font-size:10.5px;">Statement #{{ $sequence_no }}</div>@endif
                    @if ($period_range)<div class="muted" style="margin-top:2px; font-size:10.5px;">{{ $period_range }}</div>@endif
                </td>
            </tr>
        </table>

        {{-- How it's calculated — the source statement's REAL lines; the components sum to the net --}}
        @if (! empty($lines))
            <table class="lines">
                <thead>
                    <tr><th>How it's calculated</th><th class="r">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($lines as $line)
                        <tr class="{{ $line['is_net'] ? 'net' : '' }}">
                            <td>
                                {{ $line['description'] }}
                                @if ($line['unverified'])<span class="prov">Provisional</span>@endif
                            </td>
                            <td class="r {{ $line['is_negative'] ? 'neg' : '' }}">{{ $line['amount_display'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="reconcile">{{ $amount_label }}: <strong>{{ $amount_display }}</strong>@if ($has_unverified) <span class="muted">— includes provisional lines pending verification.</span>@endif</div>

        {{-- Payment details — every row is a real field; an unset field OMITS its row (no fake IBAN, no
             estimated settlement date, no invented reference format). Masked IBAN only when CAP_PAYOUT (#28). --}}
        <h2 style="font-size:12px; margin:24px 0 0; letter-spacing:.04em; text-transform:uppercase; color:#5F5853;">Payment details</h2>
        <table class="meta">
            <tr><td class="k">Beneficiary</td><td>{{ $owner_name }}</td></tr>
            @if ($iban_masked)
                <tr><td class="k">Account</td><td>IBAN {{ $iban_masked }}</td></tr>
            @endif
            <tr><td class="k">Method</td><td>{{ $method }}</td></tr>
            @if ($paid_on)
                <tr><td class="k">Paid on</td><td>{{ $paid_on }}</td></tr>
            @endif
            @if ($reference)
                <tr><td class="k">Reference</td><td>{{ $reference }}</td></tr>
            @endif
            <tr><td class="k">Currency</td><td>{{ $currency }}</td></tr>
            @if ($fx)
                <tr><td class="k">FX</td><td>1 {{ $fx['source'] }} = {{ $fx['rate'] }} {{ $currency }} <span class="muted">(source: {{ $fx['src'] }})</span></td></tr>
            @endif
            @if ($failure_reason)
                <tr><td class="k">Failure</td><td>{{ $failure_reason }}</td></tr>
            @endif
        </table>

        @if ($sequence_no)
            <div class="foot">
                This remittance advice accompanies statement #{{ $sequence_no }} and reconciles to the figures shown in the owner portal.
                Vyrobel · {{ $operator }}.
            </div>
        @else
            <div class="foot">
                This remittance advice reconciles to the figures shown in the owner portal.
                Vyrobel · {{ $operator }}.
            </div>
        @endif
    </div>
</body>
</html>
