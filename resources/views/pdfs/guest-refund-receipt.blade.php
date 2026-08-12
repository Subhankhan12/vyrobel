{{-- Guest REFUND RECEIPT PDF (Phase 4c · SG1). Documents the REAL succeeded refund(s) on the booking (correct-
     by-reversal — each refund is its own folio payment row). Rendered only when a real refund exists. PDF-SAFE
     (dompdf): table-based, inline CSS, DejaVu Sans. No invented amounts. --}}
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
        .meta { width: 100%; border-collapse: collapse; margin: 24px 0 6px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #5F5853; width: 120px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        table.totals { width: 46%; border-collapse: collapse; margin-top: 18px; margin-left: 54%; }
        table.totals td { padding: 6px 4px; font-size: 12.5px; }
        table.totals td.r { text-align: right; }
        table.totals tr.tot td { border-top: 2px solid #C2410C; font-weight: bold; font-size: 14px; padding-top: 10px; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #6C645F; }
    </style>
</head>
<body>
    <div class="wrap">
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span style="font-size:16px; font-weight:bold; padding-left:6px;">Vyrobel</span>
                    <div class="muted" style="margin-top:6px; font-size:11px;">{{ $operator }}@if ($property) · {{ $property }}@endif</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Refund receipt</div>
                    <h1>{{ $refund_no }}</h1>
                    <div class="muted" style="margin-top:4px; font-size:11px;">Issued {{ $issued_on }} · {{ $currency }}</div>
                </td>
            </tr>
        </table>

        <table class="meta">
            @if ($guest)<tr><td class="k">Guest</td><td>{{ $guest }}</td></tr>@endif
            <tr><td class="k">Booking</td><td>{{ $reference }} · {{ $stay }}</td></tr>
        </table>

        <table class="lines">
            <thead>
                <tr><th>Refund</th><th class="r">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($refunds as $refund)
                    <tr>
                        <td>Refund to {{ $refund['method'] }}<div class="muted" style="font-size:10.5px;">{{ $refund['date'] }}</div></td>
                        <td class="r">{{ $refund['amount'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr class="tot"><td>Total refunded</td><td class="r">{{ $total }}</td></tr>
        </table>

        <div class="foot">
            A receipt for money returned to you, generated from Vyrobel's append-only folio ledger; each refund is
            recorded as its own reversal and reconciles to your account. Vyrobel · {{ $operator }}.
        </div>
    </div>
</body>
</html>
