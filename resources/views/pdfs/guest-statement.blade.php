{{-- Guest STATEMENT OF ACCOUNT PDF (Phase 4c · SG1 · rebuilt to the prototype 2026-08-07).

     It shares FolioDocument::data with the folio receipt — the SAME real append-only ledger — but it is no
     longer the receipt relabelled (the audit's parity gap): it opens with the prototype's 4-tile KPI band
     (Charges · Paid · Credited · Balance due) over the activity table, which is what makes it a statement.

     ⚠️ ALL FOUR TILES COME FROM THE ONE FolioTotals PASS, including the real "Credited" figure (Σ succeeded
     refunds) — never a second walk over the payments, which is how two documents come to state different
     money (#17). "Paid" stays NET, exactly as the receipt and the on-screen folio show it, so nothing that
     already shipped moved. PDF-SAFE (dompdf): table-based, inline CSS, DejaVu Sans for € / U+2212. Every
     row is a real folio_item or payment. No invented lines. --}}
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
        .flag { display: inline-block; font-size: 8.5px; font-weight: bold; text-transform: uppercase; color: #B26A08; background-color: #FBEFD8; border-radius: 8px; padding: 1px 7px; }
        /* The 4-tile KPI band. dompdf has no flex/grid, so it is a real table with fixed-width cells. */
        table.kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 20px -8px 4px; }
        table.kpis td { width: 25%; vertical-align: top; padding: 11px 12px; background-color: #FDF8F4; border: 1px solid #F2E4D9; border-radius: 9px; }
        table.kpis td.kpi-due { background-color: #FBEFE7; border-color: #EBCDB9; }
        .kpi-k { font-size: 8.5px; font-weight: bold; letter-spacing: .07em; text-transform: uppercase; color: #5F5853; }
        .kpi-v { font-size: 16px; font-weight: bold; margin-top: 4px; }
        td.kpi-due .kpi-v { color: #C2410C; }
        .kpi-s { font-size: 8.5px; color: #6C645F; margin-top: 3px; }
        .section-k { font-size: 9px; font-weight: bold; letter-spacing: .07em; text-transform: uppercase; color: #5F5853; margin-top: 22px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines th.c, table.lines td.c { text-align: center; width: 60px; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        .neg { color: #5F5853; }
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
                    <div class="eyebrow">Statement of account</div>
                    <h1>{{ $reference }}</h1>
                    <div class="muted" style="margin-top:4px; font-size:11px;">{{ $currency }}</div>
                </td>
            </tr>
        </table>

        <table class="meta">
            @if ($guest)<tr><td class="k">Guest</td><td>{{ $guest }}</td></tr>@endif
            <tr><td class="k">Stay</td><td>{{ $stay }}</td></tr>
        </table>

        @if (! $has_folio || count($lines) === 0)
            <p class="muted" style="margin-top:20px;">No account activity yet — no charges or payments have been posted.</p>
        @else
            {{-- The KPI band (audit 2026-08-07 · parity — the statement was the folio receipt relabelled).
                 All four tiles come from the ONE FolioTotals pass, so they cannot disagree with the receipt,
                 the on-screen folio or each other: Charges − Paid = Balance due, exactly (#17).

                 ⚠️ "Credited" is a REAL figure — the sum of succeeded refunds on this folio, not a new
                 quantity invented for a fourth tile. It is ALREADY inside Paid (which is net), and the
                 caption says so rather than leaving the guest to reconcile two numbers that look like they
                 should add up but don't. A zero is shown honestly; the tile never disappears to hide it. --}}
            <table class="kpis">
                <tr>
                    <td>
                        <div class="kpi-k">Charges</div>
                        <div class="kpi-v">{{ $charges }}</div>
                        <div class="kpi-s">posted to your account</div>
                    </td>
                    <td>
                        <div class="kpi-k">Paid</div>
                        <div class="kpi-v">{{ $paid }}</div>
                        <div class="kpi-s">net of any credit</div>
                    </td>
                    <td>
                        <div class="kpi-k">Credited</div>
                        <div class="kpi-v">{{ $credited }}</div>
                        <div class="kpi-s">refunds issued to you</div>
                    </td>
                    <td class="kpi-due">
                        <div class="kpi-k">Balance due</div>
                        <div class="kpi-v">{{ $balance }}</div>
                        <div class="kpi-s">charges less paid</div>
                    </td>
                </tr>
            </table>

            <div class="section-k">Account activity</div>
            <table class="lines">
                <thead>
                    <tr><th>Item</th><th class="c">Date</th><th class="r">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($lines as $line)
                        <tr>
                            <td>
                                {{ $line['label'] }}
                                @if ($line['flag'])<span class="flag">{{ $line['flag'] }}</span>@endif
                                <div class="muted" style="font-size:10.5px;">{{ $line['desc'] }}</div>
                            </td>
                            <td class="c muted">{{ $line['date'] }}</td>
                            <td class="r {{ $line['is_negative'] ? 'neg' : '' }}">{{ $line['amount'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="foot">
            A statement of account for your booking, generated from Vyrobel's append-only ledger; charges, payments
            and refunds are shown exactly as recorded and reconcile to the figures in your account. Vyrobel · {{ $operator }}.
        </div>
    </div>
</body>
</html>
