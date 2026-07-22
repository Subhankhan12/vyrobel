{{-- Team Schedule (Phase 7d) — the weekly roster grid over the org's REAL staff, for the SELECTED property,
     with the real approved-leave overlay + computed conflicts, the time-off request/approval panel, and the
     schedule PDF. Week + department navigation ride GET links (so the /app property middleware re-runs).
     Managers (schedule.manage) get an inline shift picker + the approval queue; everyone can request own time
     off + view. Shell + design system from <x-operator.shell>.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="schedule" title="Team schedule" :subtitle="$property?->name ?? 'No property'" :viewer="$viewer">

        @if (session('schedule_status'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ session('schedule_status') }}</div></div>
        @endif
        @if (session('schedule_error'))
            <div class="vy-card vy-fade" style="border-left:3px solid #C2453A;margin-bottom:14px"><div style="font-size:13.5px;color:#8A2F27">{{ session('schedule_error') }}</div></div>
        @endif

        @if ($property === null)
            <div class="vy-card" style="padding:30px;text-align:center;color:var(--vy-muted);font-size:14px">Add a property to start building a team roster.</div>
        @else
            {{-- Toolbar — week navigation + department filter + PDF (GET links preserve each other's state) --}}
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:8px">
                    <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => $week - 1, 'dept' => $dept]) }}">‹ Last week</a>
                    @unless ($isThisWeek)
                        <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => 0, 'dept' => $dept]) }}">This week</a>
                    @endunless
                    <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => $week + 1, 'dept' => $dept]) }}">Next week ›</a>
                    <span style="font-size:13px;font-weight:600;color:var(--vy-ink);margin-left:6px">{{ $weekLabel }}</span>
                    @if ($conflictCount > 0)
                        <span style="font-size:11.5px;color:#B0322A;font-weight:600">· {{ $conflictCount }} on leave &amp; shifted</span>
                    @endif
                </div>
                <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                    @foreach ($departments as $d)
                        <a href="{{ route('app.schedule', ['week' => $week, 'dept' => $d]) }}" class="vy-btn {{ $dept === $d ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:5px 12px;font-size:12px">{{ \App\Support\Schedule\StaffDepartment::label($d) }}</a>
                    @endforeach
                    <a href="{{ route('app.schedule.pdf', ['week' => $week, 'dept' => $dept]) }}" class="vy-btn vy-btn--ghost" style="padding:5px 12px;font-size:12px" title="Download this week as a PDF">PDF</a>
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
                                    <th style="padding:10px 8px;font-weight:600;color:{{ $day['today'] ? 'var(--vy-accent)' : 'var(--vy-muted)' }};text-align:center;min-width:100px">
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
                                        <td style="padding:6px;text-align:center;{{ $cell['conflict'] ? 'background:#FBE9E7' : ($cell['on_leave'] ? 'background:#FBF3E4' : '') }}">
                                            @if ($canManage && ! ($cell['on_leave'] && ! $cell['conflict']))
                                                {{-- Manager picker. On a pure-leave day the select is hidden (assigning is blocked); a
                                                     conflict day keeps it so the shift can be cleared to off. --}}
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
                                                    @if ($cell['conflict'])
                                                        <div style="font-size:10px;color:#B0322A;font-weight:600;margin-top:2px" title="This staff member is on approved leave — clear the shift">⚠ on leave</div>
                                                    @endif
                                                </form>
                                            @elseif ($cell['on_leave'])
                                                <span style="display:inline-block;padding:4px 10px;border-radius:12px;background:#F4E7CC;font-size:11.5px;font-weight:600;color:#8A6E2F" title="Approved leave">On leave</span>
                                            @elseif ($cell['working'])
                                                <span style="display:inline-block;padding:4px 10px;border-radius:12px;background:var(--vy-wash,#F6EBE2);font-size:11.5px;font-weight:600;color:var(--vy-accent)" title="{{ $cell['hours'] }}">{{ $cell['label'] }}</span>
                                            @else
                                                <span style="font-size:12px;color:var(--vy-faint)">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="vy-line-sub" style="font-size:11.5px;color:var(--vy-faint);margin-top:10px">Early {{ \App\Support\Schedule\ShiftType::hours('early') }} · Day {{ \App\Support\Schedule\ShiftType::hours('day') }} · Late {{ \App\Support\Schedule\ShiftType::hours('late') }}. On-leave days block shifts; a shift already set that later clashes with approved leave is flagged. Roster is per property.</p>
            @endif

            {{-- ── Time off — request (anyone) + the manager approval queue ─────────────────────────── --}}
            <div style="display:grid;grid-template-columns:{{ $canManage ? '1fr 1fr' : '1fr' }};gap:16px;margin-top:18px">
                <section class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:4px">Request time off</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">Your own request goes to a manager to approve. Approved leave shows on the roster and blocks shifts.</p>
                    <form method="POST" action="{{ route('app.schedule.timeoff') }}">
                        @csrf
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <div class="vy-field" style="flex:1;min-width:130px;margin-bottom:10px">
                                <label class="vy-field-label" for="to_start">From</label>
                                <input id="to_start" type="date" name="start_date" class="vy-input" required>
                            </div>
                            <div class="vy-field" style="flex:1;min-width:130px;margin-bottom:10px">
                                <label class="vy-field-label" for="to_end">To</label>
                                <input id="to_end" type="date" name="end_date" class="vy-input" required>
                            </div>
                        </div>
                        <div class="vy-field" style="margin-bottom:12px">
                            <label class="vy-field-label" for="to_reason">Reason <span style="color:var(--vy-faint)">(optional)</span></label>
                            <input id="to_reason" type="text" name="reason" class="vy-input" maxlength="160" placeholder="Annual leave">
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Submit request</button>
                    </form>
                </section>

                @if ($canManage)
                    <section class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom:10px">Pending time-off</h2>
                        @forelse ($pending as $req)
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                                <div style="min-width:0">
                                    <div style="font-size:13px;font-weight:600;color:var(--vy-ink)">{{ $req['name'] }}</div>
                                    <div class="vy-line-sub" style="font-size:11.5px">{{ $req['range'] }}@if ($req['reason'] !== '') · {{ $req['reason'] }}@endif</div>
                                </div>
                                @if ($req['is_self'])
                                    <span class="vy-line-sub" style="font-size:11.5px;white-space:nowrap">Awaiting another manager</span>
                                @else
                                    <div style="display:flex;gap:6px;flex-shrink:0">
                                        <form method="POST" action="{{ route('app.schedule.leave.approve', $req['id']) }}" style="margin:0">
                                            @csrf
                                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                            <button type="submit" class="vy-btn vy-btn--accent" style="padding:5px 12px;font-size:12px">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('app.schedule.leave.deny', $req['id']) }}" style="margin:0">
                                            @csrf
                                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                            <button type="submit" class="vy-btn vy-btn--ghost" style="padding:5px 12px;font-size:12px">Decline</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="vy-empty" style="margin-top:6px">No pending requests.</div>
                        @endforelse
                    </section>
                @endif
            </div>
        @endif
    </x-operator.shell>
</div>
