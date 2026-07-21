{{-- Team Schedule (Phase 7d · SG1) — the weekly roster grid over the org's REAL staff, for the SELECTED
     property. Week + department navigation ride GET links (so the /app property middleware re-runs). Managers
     (schedule.manage) get an inline shift picker per cell (a real POST → ScheduleController); everyone else
     sees a read-only roster. Shell + design system from <x-operator.shell>.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="schedule" title="Team schedule" :subtitle="$property?->name ?? 'No property'" :viewer="$viewer">

        @if (session('schedule_status'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px">
                <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('schedule_status') }}</div>
            </div>
        @endif

        @if ($property === null)
            <div class="vy-card" style="padding:30px;text-align:center;color:var(--vy-muted);font-size:14px">Add a property to start building a team roster.</div>
        @else
            {{-- Toolbar — week navigation + department filter (GET links preserve each other's state) --}}
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:8px">
                    <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => $week - 1, 'dept' => $dept]) }}">‹ Last week</a>
                    @unless ($isThisWeek)
                        <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => 0, 'dept' => $dept]) }}">This week</a>
                    @endunless
                    <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => $week + 1, 'dept' => $dept]) }}">Next week ›</a>
                    <span style="font-size:13px;font-weight:600;color:var(--vy-ink);margin-left:6px">{{ $weekLabel }}</span>
                </div>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    @foreach ($departments as $d)
                        <a href="{{ route('app.schedule', ['week' => $week, 'dept' => $d]) }}"
                           class="vy-btn {{ $dept === $d ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:5px 12px;font-size:12px">{{ \App\Support\Schedule\StaffDepartment::label($d) }}</a>
                    @endforeach
                </div>
            </div>

            @if (count($rows) === 0)
                <div class="vy-card" style="padding:30px;text-align:center;color:var(--vy-muted);font-size:14px">
                    @if ($dept === 'all')
                        No staff on your team yet — invite your team in <a href="{{ route('app.roles') }}" style="color:var(--vy-accent)">Roles &amp; access</a>.
                    @else
                        No staff in {{ \App\Support\Schedule\StaffDepartment::label($dept) }} for this week.
                    @endif
                </div>
            @else
                <div class="vy-card" style="overflow-x:auto;padding:0">
                    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
                        <thead>
                            <tr style="border-bottom:1px solid var(--vy-hairline)">
                                <th style="text-align:left;padding:12px 14px;font-weight:600;color:var(--vy-muted);position:sticky;left:0;background:var(--vy-card,#fff)">Staff</th>
                                @foreach ($days as $day)
                                    <th style="padding:10px 8px;font-weight:600;color:{{ $day['today'] ? 'var(--vy-accent)' : 'var(--vy-muted)' }};text-align:center;min-width:96px">
                                        <div style="font-size:11px;letter-spacing:.04em;text-transform:uppercase">{{ $day['dow'] }}</div>
                                        <div style="font-size:12px;font-weight:500;color:var(--vy-faint)">{{ $day['label'] }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr style="border-bottom:1px solid var(--vy-hairline)">
                                    <td style="padding:10px 14px;position:sticky;left:0;background:var(--vy-card,#fff)">
                                        <div style="display:flex;align-items:center;gap:9px">
                                            <span style="width:28px;height:28px;border-radius:50%;background:var(--vy-wash,#F6EBE2);display:grid;place-items:center;font-size:11px;font-weight:600;color:var(--vy-muted)">{{ $row['staff']['initials'] }}</span>
                                            <div>
                                                <div style="font-weight:600;color:var(--vy-ink)">{{ $row['staff']['name'] }}</div>
                                                <div style="font-size:11px;color:var(--vy-faint)">{{ $row['staff']['role_label'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    @foreach ($row['cells'] as $cell)
                                        <td style="padding:6px;text-align:center">
                                            @if ($canManage)
                                                <form method="POST" action="{{ route('app.schedule.assign') }}" style="margin:0">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $row['staff']['id'] }}">
                                                    <input type="hidden" name="date" value="{{ $cell['date'] }}">
                                                    <input type="hidden" name="week" value="{{ $week }}">
                                                    <input type="hidden" name="dept" value="{{ $dept }}">
                                                    <select name="shift_type" onchange="this.form.requestSubmit()" aria-label="Shift for {{ $row['staff']['name'] }} on {{ $cell['date'] }}"
                                                            class="vy-input" style="padding:5px 6px;font-size:12px;text-align:center;{{ $cell['working'] ? 'font-weight:600;color:var(--vy-accent)' : 'color:var(--vy-faint)' }}">
                                                        @foreach ($shiftTypes as $t)
                                                            <option value="{{ $t }}" @selected($cell['type'] === $t)>{{ \App\Support\Schedule\ShiftType::label($t) }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            @else
                                                @if ($cell['working'])
                                                    <span style="display:inline-block;padding:4px 10px;border-radius:12px;background:var(--vy-wash,#F6EBE2);font-size:11.5px;font-weight:600;color:var(--vy-accent)" title="{{ $cell['hours'] }}">{{ $cell['label'] }}</span>
                                                @else
                                                    <span style="font-size:12px;color:var(--vy-faint)">—</span>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="vy-line-sub" style="font-size:11.5px;color:var(--vy-faint);margin-top:10px">Early {{ \App\Support\Schedule\ShiftType::hours('early') }} · Day {{ \App\Support\Schedule\ShiftType::hours('day') }} · Late {{ \App\Support\Schedule\ShiftType::hours('late') }}. Roster is per property — switch property in the top bar to roster another.</p>
            @endif
        @endif
    </x-operator.shell>
</div>
