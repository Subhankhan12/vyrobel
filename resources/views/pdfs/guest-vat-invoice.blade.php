{{-- Guest VAT INVOICE PDF (Phase 4c · SG1). Real folio lines split into NET + VAT (type='tax' items) + GROSS —
     net + vat = the folio's charges, reconciling to the on-screen figures. PDF-SAFE (dompdf): table-based, inline
     CSS, DejaVu Sans for € / U+2212. No invented lines. --}}
<!DOCTYPE html>
<html {!! \App\Support\I18n\Locales::htmlAttributes() !!}>
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
        .parties { width: 100%; border-collapse: collapse; margin: 24px 0 6px; }
        .parties td { padding: 4px 0; font-size: 12px; vertical-align: top; width: 50%; }
        .parties .k { color: #5F5853; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines th.c, table.lines td.c { text-align: center; width: 60px; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        table.totals { width: 46%; border-collapse: collapse; margin-top: 18px; margin-left: 54%; }
        table.totals td { padding: 6px 4px; font-size: 12.5px; }
        table.totals td.r { text-align: right; }
        table.totals tr.gross td { border-top: 2px solid #C2410C; font-weight: bold; font-size: 14px; padding-top: 10px; }
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
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">{{ $doc_title }}</div>
                    <h1>{{ $invoice_no }}</h1>
                    <div class="muted" style="margin-top:4px; font-size:11px;">{{ $invoice_date }} · {{ $currency }}</div>
                </td>
            </tr>
        </table>

        <table class="parties">
            <tr>
                <td>
                    {{-- ⚠️ THE SUPPLIER BLOCK — the statutory identifiers this tax document was missing.
                         Every line is a REAL stored org value and appears ONLY when genuinely set; an
                         operator who has not filled them in simply gets fewer lines (#39 — never a
                         placeholder VAT number, which on a tax document is worse than none at all). --}}
                    <div class="k">{{ __('pdfs/guest-vat-invoice.from') }}</div>
                    <strong>{{ $seller }}</strong>
                    @if ($seller_is_registered)
                        <div class="muted" style="font-size:10px;">{{ __('pdfs/guest-vat-invoice.registered_legal_name') }}</div>
                    @endif
                    @if ($property)<div class="muted">{{ $property }}</div>@endif
                    @if ($seller_city || $seller_country)
                        <div class="muted">{{ collect([$seller_city, $seller_country])->filter()->implode(', ') }}</div>
                    @endif
                    @if ($seller_vat_id)
                        <div style="margin-top:3px;">{!! __('pdfs/guest-vat-invoice.vat_reg_no', ['vat_id' => e($seller_vat_id)]) !!}</div>
                    @endif
                </td>
                <td>
                    <div class="k">{{ __('pdfs/guest-vat-invoice.billed_to') }}</div>
                    {{ $buyer ?? '—' }}<div class="muted">{{ __('pdfs/guest-vat-invoice.booking_line', ['reference' => $reference, 'stay' => $stay]) }}</div>
                </td>
            </tr>
        </table>

        <table class="lines">
            <thead>
                <tr><th>{{ __('pdfs/guest-vat-invoice.description') }}</th><th class="c">{{ __('common.date') }}</th><th class="r">{{ __('pdfs/guest-vat-invoice.net') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($lines as $line)
                    <tr>
                        <td>{{ $line['label'] }}<div class="muted" style="font-size:10.5px;">{{ $line['desc'] }}</div></td>
                        <td class="c muted">{{ $line['date'] }}</td>
                        <td class="r">{{ $line['amount'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">{{ __('pdfs/guest-vat-invoice.no_charges') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr><td class="muted">{{ __('pdfs/guest-vat-invoice.net') }}</td><td class="r">{{ $net }}</td></tr>
            <tr><td class="muted">{{ __('pdfs/guest-vat-invoice.vat') }}</td><td class="r">{{ $vat }}</td></tr>
            <tr class="gross"><td>{{ __('common.total') }}</td><td class="r">{{ $gross }}</td></tr>
            <tr><td class="muted" style="padding-top:10px;">{{ __('pdfs/guest-vat-invoice.paid') }}</td><td class="r" style="padding-top:10px;">{{ $paid }}</td></tr>
            <tr><td class="muted">{{ __('pdfs/guest-vat-invoice.balance_due') }}</td><td class="r">{{ $balance }}</td></tr>
        </table>

        {{-- I18N-01: this footer stays English on purpose — legal wording about the tax document, on the lawyer list. --}}
        <div class="foot">
            {{ $doc_title }} for your booking, generated from Vyrobel's append-only folio ledger; the net{{ $is_vat_document ? ', VAT' : '' }}
            and total reconcile to the figures in your account. Issued by {{ $seller }}.
        </div>
    </div>
</body>
</html>
