{{-- Operator folio PDF (Demo-readiness · Part B · Step B2). PDF-SAFE (dompdf HTML4/CSS2.1): table-based,
     inline CSS, DejaVu Sans for € / U+2212. Every row is a real folio_item or payment (append-only) —
     charges +, settled payments −, refunds their own + line; totals reconcile to the cent. No invented
     line items. Same branded document language as the owner-statement PDF. --}}
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
        .meta { width: 100%; border-collapse: collapse; margin: 24px 0 6px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #5F5853; width: 120px; }
        .flag { display: inline-block; font-size: 8.5px; font-weight: bold; text-transform: uppercase; color: #B26A08; background-color: #FBEFD8; border-radius: 8px; padding: 1px 7px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines th.c, table.lines td.c { text-align: center; width: 60px; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        .neg { color: #5F5853; }
        table.totals { width: 46%; border-collapse: collapse; margin-top: 18px; margin-left: 54%; }
        table.totals td { padding: 6px 4px; font-size: 12.5px; }
        table.totals td.r { text-align: right; }
        table.totals tr.bal td { border-top: 2px solid #C2410C; font-weight: bold; font-size: 14px; padding-top: 10px; }
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
                    <div class="eyebrow">{{ __('pdfs/folio.eyebrow') }}</div>
                    <h1>{{ $reference }}</h1>
                    <div class="muted" style="margin-top:4px; font-size:11px;">{{ $currency }}</div>
                </td>
            </tr>
        </table>

        <table class="meta">
            @if ($guest)<tr><td class="k">{{ __('pdfs/folio.guest') }}</td><td>{{ $guest }}</td></tr>@endif
            <tr><td class="k">{{ __('pdfs/folio.stay') }}</td><td>{{ $stay }}</td></tr>
        </table>

        @if (! $has_folio || count($lines) === 0)
            <p class="muted" style="margin-top:20px;">{{ __('pdfs/folio.no_activity') }}</p>
        @else
            <table class="lines">
                <thead>
                    <tr><th>{{ __('pdfs/folio.col_item') }}</th><th class="c">{{ __('common.date') }}</th><th class="r">{{ __('pdfs/folio.col_amount') }}</th></tr>
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

            <table class="totals">
                <tr><td class="muted">{{ __('pdfs/folio.charges') }}</td><td class="r">{{ $charges }}</td></tr>
                <tr><td class="muted">{{ __('pdfs/folio.paid') }}</td><td class="r">{{ $paid }}</td></tr>
                <tr class="bal"><td>{{ __('pdfs/folio.balance_due') }}</td><td class="r">{{ $balance }}</td></tr>
            </table>
        @endif

        <div class="foot">
            {{ __('pdfs/folio.footer', ['operator' => $operator]) }}
        </div>
    </div>
</body>
</html>
