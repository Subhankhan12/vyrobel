{{-- Team Schedule PDF (task #102 — matched to `Operator Schedule PDF.html`). PDF-SAFE: dompdf renders
     HTML4/CSS2.1 — table-based, inline CSS, DejaVu Sans, no flex/grid/external assets. Built from the SAME
     RosterWeek as the on-screen grid, so it reconciles character-for-character: real staff, real shifts, real
     approved leave, and a summary DERIVED from the same builder. No fabricated entries.
     ⚠️ THIS COMMENT WAS STALE AND ITS STALENESS REACHED THE PAGE (DO-CORRECTNESS-1). It said open shifts were
     omitted because "no unassigned-shift model" existed and that there was "no publish/provisional state".
     BOTH became false when `open_shifts` + `schedule_publications` landed — and the footer sentence built on
     them was being printed to operators. The false footer is now gone.
     🏁 D34-1 CLOSED THE LOOP (2026-08-15). The document went silent because `RosterWeek` had no accessor;
     it now has `publicationState()` + `openShifts()`, so both are RENDERED — read from the SHARED BUILDER,
     never queried here. ⚠️ THE ANTI-DRIFT RULE IS WHY THAT ORDER MATTERED: querying the models from this
     template would have been quicker and would have let the PDF and the grid disagree the first time either
     read changed. Property + week are the SELECTED context (no id in the URL). --}}
@php $brand = $brand ?? \App\Support\Branding\PropertyBrand::default(); $address = is_array($property?->address) ? $property->address : []; @endphp
<!DOCTYPE html>
<html {!! \App\Support\I18n\Locales::htmlAttributes() !!}>
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
        {{-- 20%, not 25% — D34-1 added the "Open" tile, making it five across. dompdf honours the declared
             width, so a stale 25% would have left the row 125% wide and pushed the last tile off. --}}
        table.sum td { border: 1px solid #EFE3D9; padding: 8px 10px; text-align: center; width: 20%; }
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
            <div class="eyebrow">{{ __('pdfs/operator-schedule.eyebrow') }}</div>
            <h1>{{ $brand->displayName }}</h1>
            @if (($address['line1'] ?? $address['city'] ?? '') !== '')
                <div class="addr">{{ trim(implode(', ', array_filter([$address['line1'] ?? null, $address['postal_code'] ?? null, $address['city'] ?? null, $address['country'] ?? null]))) }}</div>
            @endif
        </td>
        <td style="vertical-align:top;text-align:right" class="muted">
            <div>{{ __('pdfs/operator-schedule.week_of', ['week' => $roster->weekLabel]) }}</div>
            <div>{{ $deptLabel }}</div>
        </td>
    </tr></table>

    {{-- The design's document title + provenance line. `generatedAt` is the REAL production time in the
         property's timezone — a rota printed and pinned up needs to say how old it is. --}}
    <h1 class="doc-title">{{ __('pdfs/operator-schedule.doc_title') }}</h1>
    <div class="doc-sub">{{ __('pdfs/operator-schedule.doc_sub', ['property' => $brand->displayName, 'week' => $roster->weekLabel, 'generated' => $generatedAt]) }}</div>

    {{-- ⚠️ PUBLICATION STATE (D34-1). Read from the SHARED BUILDER — this template never touches
         `SchedulePublication`; that is the anti-drift rule, and it is why the accessor exists at all.
         ⚠️ AN ABSOLUTE DATE, NEVER A RELATIVE ONE. The grid renders this as "2 hours ago"
         (`diffForHumans()`), which is right for a screen and would be a LIE on paper — a rota printed Monday
         and read Thursday would still claim "2 hours ago". So `publicationState()` hands back the real
         timestamp and each surface formats for its own medium. Both branches say only what is true: a
         published week names when and (when recorded) by whom; an unpublished one says the roster is live,
         which it always is — publishing notifies the team, it does not freeze anything. --}}
    @php $publication = $roster->publicationState(); @endphp
    <div class="doc-sub" style="margin-top:2px">
        @if ($publication['published'])
            @php $publishedAt = $publication['published_at']->timezone($property?->timezone ?? 'UTC')->translatedFormat('D j M Y · H:i'); @endphp
            @if ($publication['published_by'])
                {{ __('pdfs/operator-schedule.published_by', ['date' => $publishedAt, 'name' => $publication['published_by']]) }}
            @else
                {{ __('pdfs/operator-schedule.published', ['date' => $publishedAt]) }}
            @endif
        @else
            {{ __('pdfs/operator-schedule.not_published') }}
        @endif
    </div>

    {{-- Summary — DERIVED from the same RosterWeek builder, so it reconciles to the grid below. The "Open"
         tile is now real (D34-1): `openShiftCount()` counts the same rows `openShifts()` lists, so the tile
         and the section below can never disagree. --}}
    <table class="sum"><tr>
        <td><div class="sum-n">{{ $roster->teamSize() }}</div><div class="sum-l">{{ __('pdfs/operator-schedule.team') }}</div></td>
        <td><div class="sum-n">{{ $roster->shiftCount() }}</div><div class="sum-l">{{ __('pdfs/operator-schedule.shifts') }}</div></td>
        <td><div class="sum-n">{{ $roster->hours() }}</div><div class="sum-l">{{ __('pdfs/operator-schedule.hours') }}</div></td>
        <td><div class="sum-n">{{ $roster->openShiftCount() }}</div><div class="sum-l">{{ __('pdfs/operator-schedule.open') }}</div></td>
        <td><div class="sum-n">{{ $roster->conflictCount }}</div><div class="sum-l">{{ __('pdfs/operator-schedule.conflicts') }}</div></td>
    </tr></table>

    @if (count($roster->rows) === 0)
        <p class="muted" style="margin-top:14px">{{ __('pdfs/operator-schedule.no_staff') }}</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th class="staff">{{ __('pdfs/operator-schedule.team_member') }}</th>
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
                                    {{ __('pdfs/operator-schedule.cell_conflict', ['shift' => $cell['label']]) }}
                                @elseif ($cell['on_leave'])
                                    {{ __('pdfs/operator-schedule.cell_leave') }}
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
            {!! __('pdfs/operator-schedule.legend') !!}
            <br>{{ __('pdfs/operator-schedule.shift_hours', [
                'early' => \App\Support\Schedule\ShiftType::label('early'), 'early_hours' => \App\Support\Schedule\ShiftType::hours('early'),
                'day' => \App\Support\Schedule\ShiftType::label('day'), 'day_hours' => \App\Support\Schedule\ShiftType::hours('day'),
                'late' => \App\Support\Schedule\ShiftType::label('late'), 'late_hours' => \App\Support\Schedule\ShiftType::hours('late'),
            ]) }}
            @if ($roster->conflictCount > 0) {!! trans_choice('pdfs/operator-schedule.conflict_count', (int) $roster->conflictCount, ['count' => (int) $roster->conflictCount]) !!} @endif
        </div>
    @endif

    {{-- ⚠️ OPEN SHIFTS (D34-1) — the unfilled slots on this week's rota, from the SHARED BUILDER. The empty
         case prints a positive statement rather than an empty header, because on a printed rota "nothing
         here" and "this section failed to render" look identical otherwise. ⚠️ Not department-filtered —
         `openShifts()` matches the grid exactly (see its docblock); a PDF that filtered differently would
         disagree with the screen, which is the drift this builder exists to prevent. --}}
    @php $openShifts = $roster->openShifts(); @endphp
    <h2>{{ __('pdfs/operator-schedule.open_shifts') }}</h2>
    @if (count($openShifts) === 0)
        <p class="muted" style="margin-top:4px">{{ __('pdfs/operator-schedule.all_assigned') }}</p>
    @else
        <table class="off-list">
            @foreach ($openShifts as $open)
                <tr>
                    <td>{{ $open['day_label'] }} <span class="muted">· {{ $open['shift_label'] }}</span></td>
                    <td style="text-align:right" class="muted">{{ $open['department'] ? \App\Support\Schedule\StaffDepartment::label($open['department']) : __('pdfs/operator-schedule.any_department') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Time off — real LeaveRequest rows overlapping this week --}}
    @if (count($timeOff) > 0)
        <h2>{{ __('pdfs/operator-schedule.time_off') }}</h2>
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
            <td style="text-align:left">{{ __('pdfs/operator-schedule.foot_generated', ['property' => $brand->displayName, 'generated' => $generatedAt]) }}</td>
            <td style="text-align:right">{{ __('pdfs/operator-schedule.foot_week', ['week' => $roster->weekLabel]) }}</td>
        </tr></table>
        {{-- ⚠️ THIS SENTENCE CARRIED THREE ASSERTIONS AND TWO OF THEM WERE FALSE (§N.8d, rendered to a user).
             It read: "This rota reflects the LIVE roster — there is no draft or published state, so what you
             see here is what the team sees. Shift-swap requests go through the Vyrobel app or the front desk;
             statutory rest periods are observed between late and early shifts."

             1. "there is no draft or published state" — FALSE since `schedule_publications` landed. The screen
                has a working Publish button, the table is real, and `ScheduleController::publishWeek()` really
                writes it. The document was denying a feature the product ships.
             2. "statutory rest periods are observed between late and early shifts" — FALSE, and the most
                consequential of the three: NOTHING computes or enforces a rest period anywhere in the
                codebase. `conflictCount` is leave-vs-shift only. A rota pinned to a staff-room wall was
                making a labour-law assurance the software does not stand behind. ⚠️ Not in the register —
                found by reading the sentence the register's D34-1 entry pointed near (§N.13.1 again).
             3. "what you see here is what the team sees" — still technically true (publishing stamps a date
                and notifies; it does NOT snapshot, and `publishWeek()`'s own comment says "edits stay live
                afterwards"). Dropped anyway: on a PRINTOUT the honest risk is staleness, not divergence.

             What replaces them is the one thing a printed rota can always verify about itself — WHEN it was
             taken. `$generatedAt` is already computed in the property's timezone for the header. --}}
        <div style="margin-top:6px">{{ __('pdfs/operator-schedule.snapshot_note', ['generated' => $generatedAt]) }}</div>
    </div>
</div>
</body>
</html>
