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
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-accent)">{{ session('schedule_error') }}</div></div>
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
                    {{-- #104b GATE 3 — Publish week (finalize + notify the team; edits stay live). --}}
                    @if ($canManage && ! $readOnly && count($rows) > 0)
                        <form method="POST" action="{{ route('app.schedule.publish') }}" style="margin:0">
                            @csrf
                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding:5px 12px;font-size:12px">{{ $published ? 'Re-publish' : 'Publish week' }}</button>
                        </form>
                    @endif
                    @if ($published)
                        <span class="vy-tag tone-confirmed" style="font-size:11px" title="The team was notified">Published {{ $published }}</span>
                    @endif
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
                @php
                    // Department colour-coding (real: {@see StaffDepartment} from the staffer's spatie role) — the
                    // chip bar/tint by team, matching the design. ONE map, no fabricated attribute.
                    $deptC = [
                        'frontdesk' => ['bar' => '#E8703A', 'dot' => '#E8703A', 'bg' => 'rgba(217,102,46,.10)', 'color' => '#B4400F', 'border' => '1px solid rgba(232,112,58,.28)'],
                        'housekeeping' => ['bar' => '#D99A3D', 'dot' => '#D99A3D', 'bg' => 'rgba(217,154,61,.13)', 'color' => '#8F6414', 'border' => '1px solid rgba(217,154,61,.34)'],
                        'manager' => ['bar' => '#6B6058', 'dot' => '#6B6058', 'bg' => 'rgba(43,35,32,.06)', 'color' => '#5C5049', 'border' => '1px solid rgba(120,70,40,.18)'],
                    ];
                    $gridCols = '184px repeat(7, minmax(92px, 1fr))';
                @endphp

                {{-- GATE 9.1 — the interactive week rota: drag a shift chip to move it, or drop it on another
                     staffer's cell to SWAP. All drag data is the SERVER-rendered cell strings (staff id + Y-m-d
                     date) — never a client toISOString — so there is no timezone day-roll. Managers also click a
                     cell to add / change / clear a shift (the modal). Everything routes through the guarded POSTs. --}}
                <div x-data="scheduleGrid()">
                    {{-- hidden move/swap form — drop() fills these from server strings then requestSubmit()s --}}
                    <form x-ref="moveForm" method="POST" action="{{ route('app.schedule.move') }}" style="display:none">
                        @csrf
                        <input type="hidden" name="from_user" x-ref="mvFromUser">
                        <input type="hidden" name="from_date" x-ref="mvFromDate">
                        <input type="hidden" name="to_user" x-ref="mvToUser">
                        <input type="hidden" name="to_date" x-ref="mvToDate">
                        <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                    </form>

                    <div class="vy-card" style="padding:16px">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;flex-wrap:wrap">
                            <span class="vy-line-sub" style="font-size:11.5px;color:var(--vy-faint)">Drag a shift to move it — or drop it on another person's day to swap. @if ($canManage)Click a cell to add or change a shift.@endif</span>
                            @if ($canManage && ! $readOnly)
                                <button type="button" class="vy-btn vy-btn--accent" style="font-size:12px;padding:5px 14px" @click="openBlank()">+ Add shift</button>
                            @endif
                        </div>

                        <div style="overflow-x:auto">
                            <div style="min-width:880px">
                                {{-- header --}}
                                <div style="display:grid;grid-template-columns:{{ $gridCols }};gap:8px;margin-bottom:8px">
                                    <div style="display:flex;align-items:flex-end;padding:0 4px 4px;font-size:10.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint)">Team</div>
                                    @foreach ($days as $day)
                                        <a href="{{ route('app.schedule', ['week' => $week, 'dept' => $dept, 'view' => 'day', 'day' => $day['date']]) }}"
                                           style="display:flex;flex-direction:column;align-items:center;gap:1px;padding:7px 0;border-radius:12px;text-decoration:none;{{ $day['today'] ? 'background:rgba(217,102,46,.12);border:1px solid rgba(232,112,58,.4)' : 'border:1px solid transparent' }}" title="Open day view">
                                            <span style="font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:{{ $day['today'] ? '#B4400F' : 'var(--vy-faint)' }}">{{ $day['dow'] }}</span>
                                            <span style="font-family:var(--vy-display);font-weight:600;font-size:15px;color:{{ $day['today'] ? '#C2410C' : 'var(--vy-ink)' }}">{{ $day['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>

                                {{-- staff rows --}}
                                @foreach ($rows as $row)
                                    @php $dc = $deptC[$row['staff']['department']] ?? $deptC['manager']; @endphp
                                    <div style="display:grid;grid-template-columns:{{ $gridCols }};gap:8px;margin-bottom:8px">
                                        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:13px;background:var(--vy-wash);border:1px solid var(--vy-hairline);min-width:0">
                                            <span style="width:30px;height:30px;border-radius:50%;background:{{ $dc['bg'] }};border:{{ $dc['border'] }};color:{{ $dc['color'] }};font-size:10.5px;font-weight:600;display:grid;place-items:center;flex-shrink:0">{{ $row['staff']['initials'] }}</span>
                                            <span style="min-width:0;flex:1">
                                                <span style="display:block;font-size:12.5px;font-weight:600;color:var(--vy-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row['staff']['name'] }}</span>
                                                <span style="display:flex;align-items:center;gap:5px;margin-top:1px">
                                                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $dc['dot'] }};flex-shrink:0"></span>
                                                    <span style="font-size:10.5px;color:var(--vy-faint);white-space:nowrap">{{ $row['staff']['role_label'] }}</span>
                                                </span>
                                            </span>
                                        </div>

                                        @foreach ($row['cells'] as $cell)
                                            @php
                                                $sid = $row['staff']['id'];
                                                $key = $sid.'|'.$cell['date'];
                                                $pureLeave = $cell['on_leave'] && ! $cell['working'];
                                                $dropOk = $canManage && ! $readOnly && ! $pureLeave;
                                            @endphp
                                            <div style="min-height:58px;border-radius:13px;padding:3px;transition:box-shadow .12s,background .12s;{{ $cell['today'] ?? false ? 'background:rgba(217,102,46,.04)' : '' }}"
                                                 :style="overKey === '{{ $key }}' ? 'box-shadow:inset 0 0 0 2px rgba(232,112,58,.65);background:rgba(217,102,46,.12)' : ''"
                                                 @if ($dropOk)
                                                     @dragover="over('{{ $sid }}','{{ $cell['date'] }}', $event, {{ $pureLeave ? 'true' : 'false' }})"
                                                     @dragleave="leaveCell('{{ $sid }}','{{ $cell['date'] }}')"
                                                     @drop="drop('{{ $sid }}','{{ $cell['date'] }}', {{ $pureLeave ? 'true' : 'false' }})"
                                                 @endif>
                                                @if ($cell['working'])
                                                    <div data-shift-type="{{ $cell['type'] }}" @if ($canManage && ! $readOnly) draggable="true" @dragstart="start('{{ $sid }}','{{ $cell['date'] }}', $event)" @dragend="reset()" @click="openCell('{{ $sid }}','{{ $cell['date'] }}','{{ $cell['type'] }}')" title="Drag to move · click to change" @endif
                                                         style="position:relative;height:100%;box-sizing:border-box;border-radius:11px;background:{{ $cell['conflict'] ? 'rgba(176,50,42,.08)' : $dc['bg'] }};border:{{ $cell['conflict'] ? '1px solid rgba(176,50,42,.4)' : $dc['border'] }};padding:8px 9px 8px 12px;display:flex;flex-direction:column;justify-content:center;gap:2px;overflow:hidden;{{ $canManage && ! $readOnly ? 'cursor:grab' : '' }}">
                                                        <span style="position:absolute;top:0;left:0;bottom:0;width:3px;background:{{ $cell['conflict'] ? '#B0322A' : $dc['bar'] }}"></span>
                                                        <span style="font-size:11.5px;font-weight:600;color:{{ $cell['conflict'] ? '#B0322A' : $dc['color'] }};line-height:1.1">{{ $cell['label'] }}</span>
                                                        <span style="font-size:10px;color:{{ $cell['conflict'] ? '#B0322A' : $dc['color'] }};opacity:.75">{{ $cell['conflict'] ? '⚠ on leave' : $cell['hours'] }}</span>
                                                    </div>
                                                @elseif ($cell['on_leave'])
                                                    <div style="height:100%;box-sizing:border-box;border-radius:11px;background:rgba(120,70,40,.05);border:1px dashed rgba(120,70,40,.22);display:flex;align-items:center;justify-content:center" title="Approved leave">
                                                        <span style="font-size:10.5px;font-weight:600;color:#8A6E2F">On leave</span>
                                                    </div>
                                                @elseif ($canManage && ! $readOnly)
                                                    <button type="button" @click="openCell('{{ $sid }}','{{ $cell['date'] }}','off')" title="Add a shift"
                                                            style="width:100%;height:100%;border-radius:11px;background:transparent;border:1px dashed var(--vy-hairline);color:var(--vy-faint);font-size:16px;cursor:pointer;transition:background .14s" onmouseover="this.style.background='var(--vy-wash)'" onmouseout="this.style.background='transparent'">+</button>
                                                @else
                                                    <div style="height:100%;display:flex;align-items:center;justify-content:center"><span style="font-size:11px;color:var(--vy-faint)">—</span></div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <p class="vy-line-sub" style="font-size:11px;color:var(--vy-faint);margin-top:10px">Early {{ \App\Support\Schedule\ShiftType::hours('early') }} · Day {{ \App\Support\Schedule\ShiftType::hours('day') }} · Late {{ \App\Support\Schedule\ShiftType::hours('late') }}. On-leave days block shifts; a shift already set that later clashes with approved leave is flagged in red. Roster is per property.</p>
                    </div>

                    {{-- Add / change / clear a shift — one modal, opened blank (toolbar) or pre-targeted (a cell click).
                         Posts to the SAME guarded schedule.assign. The date picker offers this week's real days only. --}}
                    @if ($canManage)
                        <div class="vy-modal-backdrop" x-show="modal" x-cloak @click.self="closeModal()" style="z-index:50">
                            <form method="POST" action="{{ route('app.schedule.assign') }}" class="vy-card" style="width:min(420px,94vw);padding:22px" @click.stop>
                                @csrf
                                <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                <h2 class="vy-card-title" style="margin:0 0 14px" x-text="mType === 'off' ? 'Add shift' : 'Change shift'">Add shift</h2>

                                <div class="vy-field" style="margin-bottom:12px">
                                    <label class="vy-field-label" for="m_user">Team member</label>
                                    <select id="m_user" name="user_id" class="vy-input" required x-model="mUser">
                                        <option value="">Choose…</option>
                                        @foreach ($rows as $r)
                                            <option value="{{ $r['staff']['id'] }}">{{ $r['staff']['name'] }} · {{ $r['staff']['role_label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="vy-field" style="margin-bottom:14px">
                                    <label class="vy-field-label" for="m_date">Day</label>
                                    <select id="m_date" name="date" class="vy-input" required x-model="mDate">
                                        @foreach ($days as $day)
                                            <option value="{{ $day['date'] }}">{{ $day['dow'] }} {{ $day['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="vy-field" style="margin-bottom:18px">
                                    <label class="vy-field-label">Shift</label>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                                        @foreach ($shiftTypes as $t)
                                            <label style="flex:1;min-width:70px;text-align:center;cursor:pointer">
                                                <input type="radio" name="shift_type" value="{{ $t }}" x-model="mType" style="position:absolute;opacity:0;pointer-events:none">
                                                <span style="display:block;padding:8px 6px;border-radius:10px;font-size:12px;font-weight:600;border:1px solid var(--vy-hairline)"
                                                      :style="mType === '{{ $t }}' ? 'background:var(--vy-accent);color:#fff;border-color:var(--vy-accent)' : 'color:var(--vy-muted)'">
                                                    {{ \App\Support\Schedule\ShiftType::label($t) }}
                                                    @if ($t !== 'off')<span style="display:block;font-size:9.5px;font-weight:500;opacity:.7">{{ \App\Support\Schedule\ShiftType::hours($t) }}</span>@endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="vy-line-sub" style="font-size:11px;margin:8px 0 0;color:var(--vy-faint)">“Off” clears the shift. A working shift can’t land on an approved-leave day.</p>
                                </div>

                                <div style="display:flex;gap:8px;justify-content:flex-end">
                                    <button type="button" class="vy-btn vy-btn--ghost" style="font-size:12.5px" @click="closeModal()">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12.5px">Save shift</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            {{-- #104 GATE 3d — "Coverage this week" summary, derived from the SAME RosterWeek builder as the grid
                 (so it reconciles): team on rota · total shifts · rostered hours. --}}
            <div class="vy-card vy-fade" style="margin-top:18px;display:flex;gap:32px;flex-wrap:wrap;align-items:flex-end">
                <div><div class="vy-dk-label">Team on rota</div><div style="font-family:var(--vy-display);font-size:24px;font-weight:700">{{ $coverage['team'] }}</div></div>
                <div><div class="vy-dk-label">Shifts this week</div><div style="font-family:var(--vy-display);font-size:24px;font-weight:700">{{ $coverage['shifts'] }}</div></div>
                <div><div class="vy-dk-label">Rostered hours</div><div style="font-family:var(--vy-display);font-size:24px;font-weight:700">{{ $coverage['hours'] }}h</div></div>
            </div>

            {{-- #104b GATE 3 — OPEN SHIFTS: unassigned slots a manager posts, then assigns to a real staffer (fill
                 creates a real Shift via the certified service). --}}
            @if ($canManage)
                <div class="vy-card vy-fade" style="margin-top:18px" x-data="{ addOpen: false }">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                        <h2 class="vy-card-title" style="margin:0">Open shifts</h2>
                        @unless ($readOnly)
                            <button type="button" class="vy-btn vy-btn--ghost" style="font-size:12px;padding:5px 12px" @click="addOpen = !addOpen">Add open shift</button>
                        @endunless
                    </div>
                    @unless ($readOnly)
                        <form x-show="addOpen" x-cloak method="POST" action="{{ route('app.schedule.open.create') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-top:12px">
                            @csrf
                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                            <input type="date" name="date" class="vy-input" required aria-label="Date" style="min-width:150px">
                            <select name="shift_type" class="vy-input" required aria-label="Shift" style="min-width:120px"><option value="early">Early</option><option value="day">Day</option><option value="late">Late</option></select>
                            <input type="text" name="department" class="vy-input" maxlength="40" placeholder="Department (optional)" style="min-width:150px">
                            <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12.5px">Post open shift</button>
                        </form>
                    @endunless
                    @forelse ($openShifts as $o)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-top:1px solid var(--vy-hairline);font-size:13px">
                            <div><strong>{{ $o['shift_label'] }}</strong> · {{ $o['day_label'] }}@if ($o['department'])<span class="vy-muted"> · {{ $o['department'] }}</span>@endif<br><span class="vy-muted" style="font-size:11.5px">No one scheduled</span></div>
                            @unless ($readOnly)
                                <div style="display:flex;gap:6px;flex-shrink:0">
                                    <form method="POST" action="{{ route('app.schedule.open.fill', $o['id']) }}" style="margin:0;display:flex;gap:6px">
                                        @csrf
                                        <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                        <select name="user_id" required aria-label="Assign to" class="vy-input" style="font-size:12px;padding:4px 8px">
                                            <option value="">Assign…</option>
                                            @foreach ($rows as $r)
                                                <option value="{{ $r['staff']['id'] }}">{{ $r['staff']['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12px;padding:4px 10px">Assign</button>
                                    </form>
                                    <form method="POST" action="{{ route('app.schedule.open.remove', $o['id']) }}" style="margin:0" onsubmit="return confirm('Remove this open shift?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="font-size:12px;padding:4px 10px">Remove</button>
                                    </form>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="vy-empty" style="padding:12px 0;margin-top:6px">No open shifts — every slot is covered.</div>
                    @endforelse
                </div>
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

                        {{-- #104 GATE 3d — grant leave directly (manager). The route existed with no UI; a manager
                             can't grant their OWN leave (self-approval separation is enforced in LeaveService). --}}
                        <div style="margin-top:16px;border-top:1px solid var(--vy-hairline);padding-top:14px">
                            <h3 class="vy-card-title" style="font-size:13px;margin-bottom:8px">Add leave for a team member</h3>
                            <form method="POST" action="{{ route('app.schedule.leave.grant') }}">
                                @csrf
                                <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                <div class="vy-field" style="margin-bottom:8px">
                                    <select name="user_id" class="vy-input" required aria-label="Team member">
                                        <option value="">Choose a team member…</option>
                                        @foreach ($rows as $r)
                                            <option value="{{ $r['staff']['id'] }}">{{ $r['staff']['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="display:flex;gap:10px;flex-wrap:wrap">
                                    <input type="date" name="start_date" class="vy-input" required aria-label="From" style="flex:1;min-width:120px">
                                    <input type="date" name="end_date" class="vy-input" required aria-label="To" style="flex:1;min-width:120px">
                                </div>
                                <input type="text" name="reason" class="vy-input" maxlength="160" placeholder="Reason (optional)" style="margin-top:8px">
                                <button type="submit" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;margin-top:10px">Add leave</button>
                            </form>
                        </div>
                    </section>
                @endif
            </div>
        @endif
    </x-operator.shell>

    {{-- GATE 9.1 — the drag + modal controller. Drag state holds the SERVER-rendered staff id + Y-m-d string; the
         drop fills the hidden move form from those strings and submits (no client date math → no timezone roll). --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('scheduleGrid', () => ({
                dragUser: null, dragDate: null, overKey: '',
                modal: false, mUser: '', mDate: '', mType: 'off',

                start(u, d, e) {
                    this.dragUser = u; this.dragDate = d;
                    try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', u + '|' + d); } catch (_) {}
                },
                over(u, d, e, leave) {
                    if (this.dragUser === null || leave) return;
                    e.preventDefault();
                    this.overKey = u + '|' + d;
                },
                leaveCell(u, d) { if (this.overKey === u + '|' + d) this.overKey = ''; },
                drop(u, d, leave) {
                    const fu = this.dragUser, fd = this.dragDate;
                    this.reset();
                    if (fu === null || leave) return;
                    if (fu === u && fd === d) return;            // dropped on itself
                    this.$refs.mvFromUser.value = fu;
                    this.$refs.mvFromDate.value = fd;            // server Y-m-d string — never toISOString
                    this.$refs.mvToUser.value = u;
                    this.$refs.mvToDate.value = d;
                    this.$refs.moveForm.requestSubmit();
                },
                reset() { this.dragUser = null; this.dragDate = null; this.overKey = ''; },

                openCell(u, d, t) { this.mUser = u; this.mDate = d; this.mType = t || 'off'; this.modal = true; },
                openBlank() { this.mUser = ''; this.mDate = @js($days[0]['date'] ?? ''); this.mType = 'day'; this.modal = true; },
                closeModal() { this.modal = false; },
            }));
        });
    </script>
</div>
