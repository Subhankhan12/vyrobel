{{-- Owner booking CONFIRMATION PDF (#78). PDF-SAFE: dompdf renders HTML4/CSS2.1 → a table-based, inline-CSS
     branded document (no flex/grid/backdrop-filter, no external assets). DejaVu Sans = dompdf's bundled Unicode
     font (renders € + the U+2212 minus). Earnings are REAL attributed owner_ledger rows via the shared
     OwnerLedgerAttribution, so this reconciles to the on-screen Booking Detail to the cent; the Commission line
     shows the REAL rate (18%). #28 — GUEST NAME ONLY (no email/phone/country). All strings are built in
     OwnerBookingConfirmationDocument, so this view stays logic-free. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 36px 40px; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; }
        .muted { color: #8A8079; }
        .eyebrow { color: #8A8079; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 12px 0 0; }
        .subtitle { color: #8A8079; font-size: 12px; margin-top: 5px; }
        h2 { font-size: 12px; margin: 24px 0 0; letter-spacing: .04em; text-transform: uppercase; color: #8A8079; }
        .hdr td { vertical-align: top; }
        .lead-co { font-size: 17px; font-weight: bold; }
        .lead-addr { color: #8A8079; margin-top: 5px; font-size: 10.5px; }
        .doc-ref { font-size: 13px; font-weight: bold; margin-top: 3px; }

        table.facts { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.facts td { width: 25%; padding: 12px 14px; border: 1px solid #EFE3D9; vertical-align: top; }
        table.facts .k { color: #8A8079; font-size: 9px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
        table.facts .v { font-size: 15px; font-weight: bold; padding-top: 5px; }

        table.lines { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #8A8079; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 8px 4px; font-size: 12px; border-bottom: 1px solid #F2E9E1; vertical-align: top; }
        table.lines tr.gross td { border-top: 1px solid #EFE3D9; font-weight: bold; }
        table.lines tr.net td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; font-size: 14px; padding-top: 11px; }
        table.lines tr.net td.hl { color: #C2410C; }
        .empty { margin-top: 12px; background-color: #FBF6F1; border: 1px solid #EFE3D9; padding: 14px 16px; font-size: 11px; color: #8A8079; }
        .foot { margin-top: 28px; padding-top: 12px; border-top: 1px solid #EFE3D9; }
        .foot td { font-size: 10px; color: #A69A92; }
    </style>
</head>
<body>
    <div class="wrap">
        {{-- Letterhead — the operator's REAL registered identity. --}}
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span class="lead-co" style="padding-left:6px;">{{ $operator }}</span>
                    <div class="lead-addr">{{ $operator_addr }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Booking confirmation</div>
                    <div class="doc-ref">{{ $reference }}</div>
                    <div class="muted" style="font-size:10.5px;margin-top:3px;">{{ $status }}</div>
                </td>
            </tr>
        </table>

        <h1>Booking confirmation</h1>
        <div class="subtitle">{{ $guest }} · {{ $units }} · {{ $nights }} {{ \Illuminate\Support\Str::plural('night', $nights) }}</div>

        <h2>Reservation</h2>
        <table class="facts">
            <tr>
                <td><div class="k">Check-in</div><div class="v">{{ $check_in }}</div></td>
                <td><div class="k">Check-out</div><div class="v">{{ $check_out }}</div></td>
                <td><div class="k">Nights</div><div class="v">{{ $nights }}</div></td>
                <td><div class="k">Guests</div><div class="v">{{ $guests }}</div></td>
            </tr>
            <tr>
                <td><div class="k">Guest</div><div class="v" style="font-size:13px;">{{ $guest }}</div></td>
                <td><div class="k">Unit</div><div class="v" style="font-size:13px;">{{ $units }}</div></td>
                <td><div class="k">Source</div><div class="v" style="font-size:13px;">{{ $source_label }}</div></td>
                <td><div class="k">Booked on</div><div class="v" style="font-size:13px;">{{ $booked_on }}</div></td>
            </tr>
        </table>

        <h2>Your earnings — gross &#8594; net</h2>
        @php $isZero = fn ($d) => (int) preg_replace('/\D/', '', (string) $d) === 0; @endphp
        @forelse ($earnings as $block)
            <table class="lines">
                @if (count($earnings) > 1)
                    <tr><td colspan="2" class="eyebrow" style="border-bottom:none;padding-bottom:0;">{{ $block['currency'] }}</td></tr>
                @endif
                @foreach ($block['credits'] as $line)
                    @unless ($isZero($line['display']))
                        <tr><td>{{ $line['label'] }}</td><td class="r">{{ $line['display'] }}</td></tr>
                    @endunless
                @endforeach
                <tr class="gross"><td>Gross booking value</td><td class="r">{{ $block['credited_display'] }}</td></tr>
                @foreach ($block['deductions'] as $line)
                    @unless ($isZero($line['display']))
                        <tr><td>{{ $line['label'] }}@if ($line['label'] === 'Commission' && $commission_rate) &#183; {{ $commission_rate }}%@endif</td><td class="r">{{ $line['display'] }}</td></tr>
                    @endunless
                @endforeach
                <tr class="net"><td>Net to you</td><td class="r hl">{{ $block['net_display'] }}</td></tr>
            </table>
        @empty
            <div class="empty">Not yet attributed — your earnings appear once this booking is attributed to your ledger, usually after check-out.</div>
        @endforelse

        <table class="foot" width="100%">
            <tr>
                <td>Prepared for the property owner on {{ $prepared_on }}. Figures are computed from your owner ledger and reconcile to your statement.</td>
                <td style="text-align:right;">Vyrobel</td>
            </tr>
        </table>
    </div>
</body>
</html>
