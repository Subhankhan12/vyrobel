{{-- Maintenance costs report PDF (#89). PDF-SAFE: dompdf renders HTML4/CSS2.1 — table-based, inline CSS, the
     bundled DejaVu Sans (renders €). Every figure is REAL (resolved work orders); labor + parts === total, and
     the totals reconcile to what the owner is billed. --}}
@php $c = $report['currency']; $fmt = fn (int $m): string => \App\Support\Money\MoneyFormatter::display($m, $c); @endphp
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
        h2 { font-size: 11px; margin: 24px 0 6px; letter-spacing: .04em; text-transform: uppercase; color: #8A8079; }
        table { width: 100%; border-collapse: collapse; }
        table.kpi td { width: 25%; padding: 12px 14px; border: 1px solid #EFE3D9; vertical-align: top; }
        table.ledger td, table.ledger th { padding: 7px 8px; border-bottom: 1px solid #F2E8DF; font-size: 11px; text-align: left; }
        table.ledger th { color: #8A8079; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        .num { text-align: right; }
        .big { font-size: 18px; font-weight: bold; }
        .total-row td { border-top: 2px solid #2B2320; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <span class="brand-mark">V</span>
    <span class="eyebrow" style="margin-left: 8px;">Vyrobel · Maintenance</span>
    <h1>Maintenance costs</h1>
    <div class="muted" style="margin-top: 4px;">All resolved work orders · generated {{ now()->format('j M Y') }}</div>

    <table class="kpi" style="margin-top: 20px;">
        <tr>
            <td><div class="eyebrow">Total spend</div><div class="big">{{ $fmt($report['total_minor']) }}</div></td>
            <td><div class="eyebrow">Orders closed</div><div class="big">{{ $report['orders_closed'] }}</div></td>
            <td><div class="eyebrow">Avg / order</div><div class="big">{{ $report['avg_minor'] !== null ? $fmt($report['avg_minor']) : '—' }}</div></td>
            <td><div class="eyebrow">Parts on order</div><div class="big">{{ $report['parts_on_order'] }}</div></td>
        </tr>
    </table>

    <h2>Spend by category</h2>
    <table class="ledger">
        @forelse ($report['by_category'] as $cat)
            <tr><td>{{ ucfirst($cat['category']) }} <span class="muted">· {{ $cat['count'] }}</span></td><td class="num">{{ $fmt($cat['total_minor']) }}</td></tr>
        @empty
            <tr><td class="muted">No resolved work orders yet.</td></tr>
        @endforelse
    </table>

    <h2>Billable work orders</h2>
    <table class="ledger">
        <thead><tr><th>Date</th><th>Work order</th><th class="num">Labor</th><th class="num">Parts</th><th class="num">Total</th></tr></thead>
        <tbody>
            @foreach ($report['billable'] as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['title'] }} <span class="muted">· {{ $row['unit'] }} · {{ ucfirst($row['category']) }}</span></td>
                    <td class="num">{{ $fmt($row['labor_minor']) }}</td>
                    <td class="num">{{ $fmt($row['parts_minor']) }}</td>
                    <td class="num">{{ $fmt($row['total_minor']) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">Total</td>
                <td class="num">{{ $fmt($report['labor_minor']) }}</td>
                <td class="num">{{ $fmt($report['parts_minor']) }}</td>
                <td class="num">{{ $fmt($report['total_minor']) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="muted" style="margin-top: 18px; font-size: 10px;">Labour + parts reconcile to the total; owner-billed work orders match the amount charged on the owner statement.</div>
</div>
</body>
</html>
