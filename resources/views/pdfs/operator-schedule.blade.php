{{-- Team Schedule PDF (task #102 — matched to `Operator Schedule PDF.html`). PDF-SAFE: dompdf renders
     HTML4/CSS2.1 — table-based, inline CSS, DejaVu Sans, no flex/grid/external assets. Built from the SAME
     RosterWeek as the on-screen grid, so it reconciles character-for-character: real staff, real shifts, real
     approved leave, and a summary DERIVED from the same builder. No fabricated entries.
     HONEST (#102): "open shifts" is omitted (no unassigned-shift model — every shift is bound to a person);
     the footer states the roster is live (there is no publish/provisional state). Property + week are the
     SELECTED context (no id in the URL). --}}
@php $brand = $brand ?? \App\Support\Branding\PropertyBrand::default(); $address = is_array($property?->address) ? $property->address : []; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 11px; }
        .wrap { padding: 30px 34px; }
        .brand-mark { display: inline-block; width: 28px; height: 28px; background-color: {{ $brand->accent }}; color: #fff; text-align: center; font-size: 16px; font-weight: bold; border-radius: 6px; }
        .eyebrow { color: {{ $brand->accent }}; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 18px; margin: 3px 0 0; }
        .doc-title { font-size: 20px; margin: 16px 0 2px; }
        .doc-sub { color: #5F5853; font-size: 10px; }
        .muted { color: #5F5853; }
        .addr { color: #6C645F; font-size: 9.5px; margin-top: 2px; }
        table.sum { width: 100%; border-collapse: collapse; margin: 14px 0 4px; }
        table.sum td { border: 1px solid #EFE3D9; padding: 8px 10px; text-align: center; width: 25%; }
        .sum-n { font-size: 17px; font-weight: bold; color: #2B2320; }
        .sum-l { font-size: 8.5px; letter-spacing: .04em; text-transform: uppercase; color: #5F5853; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.grid th, table.grid td { border: 1px solid #EFE3D9; padding: 6px 5px; font-size: 10px; text-align: center; }
        table.grid th { background-color: #FBF5EF; color: #5F5853; font-size: 8.5px; letter-spacing: .04em; text-transform: uppercase; }
        table.grid td.staff, table.grid th.staff { text-align: left; width: 150px; }
        table.grid td.work { color: {{ $brand->accent }}; font-weight: bold; }
        table.grid td.leave { color: #8A6E2F; font-weight: bold; background-color: #FBF3E4; }
        table.grid td.conflict { color: #B0322A; font-weight: bold; background-color: #FBE9E7; }
        table.grid td.off { color: #6C645F; }
        .role { color: #6C645F; font-size: 9px; }
        .legend { margin-top: 12px; font-size: 9.5px; color: #5F5853; }
        h2 { font-size: 12px; margin: 20px 0 6px; }
        table.off-list { width: 100%; border-collapse: collapse; }
        table.off-list td { border-bottom: 1px solid #F2E8DF; padding: 6px 4px; font-size: 10px; }
        .foot { margin-top: 20px; padding-top: 10px; border-top: 1px solid #EFE3D9; font-size: 9.5px; color: #6C645F; }
    </style>
</head>
<body>
<div class="wrap">
    <table style="width:100%;border-collapse:collapse"><tr>
        <td style="vertical-align:top"><span class="brand-mark">{{ $brand->initial }}</span></td>
        <td style="vertical-align:top;padding-left:10px">
            <div class="eyebrow">Team schedule</div>
            <h1>{{ $brand->displayName }}</h1>
            @if (($address['line1'] ?? $address['city'] ?? '') !== '')
                <div class="addr">{{ trim(implode(', ', array_filter([$address['line1'] ?? null, $address['postal_code'] ?? null, $address['city'] ?? null, $address['country'] ?? null]))) }}</div>
            @endif
        </td>
        <td style="vertical-align:top;text-align:right" class="muted">
            <div>Week of {{ $roster->weekLabel }}</div>
            <div>{{ $deptLabel }}</div>
        </td>
    </tr></table>

    {{-- The design's document title + provenance line. `generatedAt` is the REAL production time in the
         property's timezone — a rota printed and pinned up needs to say how old it is. --}}
    <h1 class="doc-title">Weekly rota</h1>
    <div class="doc-sub">{{ $brand->displayName }} · week of {{ $roster->weekLabel }} · generated {{ $generatedAt }}</div>

    {{-- Summary — DERIVED from the same RosterWeek builder, so it reconciles to the grid below. "Open" is
         omitted honestly (no unassigned-shift model exists). --}}
    <table class="sum"><tr>
        <td><div class="sum-n">{{ $roster->teamSize() }}</div><div class="sum-l">Team</div></td>
        <td><div class="sum-n">{{ $roster->shiftCount() }}</div><div class="sum-l">Shifts</div></td>
        <td><div class="sum-n">{{ $roster->hours() }}</div><div class="sum-l">Hours</div></td>
        <td><div class="sum-n">{{ $roster->conflictCount }}</div><div class="sum-l">Conflicts</div></td>
    </tr></table>

    @if (count($roster->rows) === 0)
        <p class="muted" style="margin-top:14px">No staff on this roster for the selected week.</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th class="staff">Team member</th>
                    @foreach ($roster->days as $day)
                        <th>{{ $day['dow'] }}<br>{{ $day['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($roster->rows as $row)
                    <tr>
                        <td class="staff">{{ $row['staff']['name'] }}<br><span class="role">{{ $row['staff']['role_label'] }}</span></td>
                        @foreach ($row['cells'] as $cell)
                            <td class="{{ $cell['conflict'] ? 'conflict' : ($cell['on_leave'] ? 'leave' : ($cell['working'] ? 'work' : 'off')) }}">
                                @if ($cell['conflict'])
                                    {{ $cell['label'] }} / Leave!
                                @elseif ($cell['on_leave'])
                                    Leave
                                @elseif ($cell['working'])
                                    {{ $cell['label'] }}<br><span style="font-weight:normal;color:#6C645F">{{ $cell['hours'] }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="legend">
            <strong>Reading this rota:</strong> a cell shows the shift name and its times · <strong>Leave</strong> = approved time off ·
            <strong>—</strong> = not rostered · <strong>“/ Leave!”</strong> = a shift on an approved-leave day.
            Every state is written out, so this prints correctly in black and white.
            <br>Early {{ \App\Support\Schedule\ShiftType::hours('early') }} · Day {{ \App\Support\Schedule\ShiftType::hours('day') }} · Late {{ \App\Support\Schedule\ShiftType::hours('late') }} · shift times shown per cell.
            @if ($roster->conflictCount > 0) <strong style="color:#B0322A">{{ $roster->conflictCount }} conflict{{ $roster->conflictCount === 1 ? '' : 's' }}</strong> — a shift on an approved-leave day. @endif
        </div>
    @endif

    {{-- Time off — real LeaveRequest rows overlapping this week --}}
    @if (count($timeOff) > 0)
        <h2>Time off</h2>
        <table class="off-list">
            @foreach ($timeOff as $off)
                <tr>
                    <td>{{ $off['name'] }} <span class="muted">· {{ $off['kind'] }}</span></td>
                    <td style="text-align:right" class="muted">{{ $off['days'] }} · {{ $off['status'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="foot">
        <table style="width:100%;border-collapse:collapse"><tr>
            <td style="text-align:left">{{ $brand->displayName }} · generated {{ $generatedAt }}</td>
            <td style="text-align:right">Week schedule · {{ $roster->weekLabel }}</td>
        </tr></table>
        <div style="margin-top:6px">This rota reflects the LIVE roster — there is no draft or published state, so what you see here is what the team sees. Shift-swap requests go through the Vyrobel app or the front desk; statutory rest periods are observed between late and early shifts.</div>
    </div>
</div>
</body>
</html>
