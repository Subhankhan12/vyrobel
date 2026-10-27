{{-- Team Schedule (Phase 7d) — the weekly roster grid over the org's REAL staff, for the SELECTED property,
     with the real approved-leave overlay + computed conflicts, the time-off request/approval panel, and the
     schedule PDF. Week + department navigation ride GET links (so the /app property middleware re-runs).
     Managers (schedule.manage) get an inline shift picker + the approval queue; everyone can request own time
     off + view. Shell + design system from <x-operator.shell>.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="schedule" :title="__('livewire/operator/schedule.title')" :subtitle="$property?->name ?? __('livewire/operator/schedule.no_property')" :viewer="$viewer">

        @if (session('schedule_status'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ session('schedule_status') }}</div></div>
        @endif
        @if (session('schedule_error'))
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-accent)">{{ session('schedule_error') }}</div></div>
        @endif

        @if ($property === null)
            <div class="vy-card" style="padding:30px;text-align:center;color:var(--vy-muted);font-size:14px">{{ __('livewire/operator/schedule.no_property_body') }}</div>
        @else
            @php
                // GATE 9.2 — link bases. Week-nav preserves the view + department (a new week has different days, so
                // it drops ?day, defaulting the day view to today/first). Dept-filter stays on the same week + day.
                $focusDate = $days[$focusIdx]['date'] ?? ($days[0]['date'] ?? '');
                $navBase = ['dept' => $dept, 'view' => $view];
                $viewBase = ['week' => $week, 'dept' => $dept] + ($view === 'day' ? ['day' => $focusDate] : []);
            @endphp
            {{-- Toolbar — week navigation + Week/Day view toggle + department filter + PDF (GET links preserve each other's state) --}}
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:8px">
                    <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => $week - 1] + $navBase) }}">{{ __('livewire/operator/schedule.last_week') }}</a>
                    @unless ($isThisWeek)
                        <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => 0] + $navBase) }}">{{ __('livewire/operator/schedule.this_week') }}</a>
                    @endunless
                    <a class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px" href="{{ route('app.schedule', ['week' => $week + 1] + $navBase) }}">{{ __('livewire/operator/schedule.next_week') }}</a>
                    <span style="font-size:13px;font-weight:600;color:var(--vy-ink);margin-left:6px">{{ $weekLabel }}</span>
                    @if ($conflictCount > 0)
                        <span style="font-size:11.5px;color:#B0322A;font-weight:600">· {{ trans_choice('livewire/operator/schedule.conflicts', $conflictCount, ['count' => $conflictCount]) }}</span>
                    @endif
                </div>
                <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                    {{-- Week / Day view toggle --}}
                    <span style="display:inline-flex;gap:2px;padding:3px;border-radius:30px;background:var(--vy-wash);margin-right:4px">
                        <a href="{{ route('app.schedule', ['week' => $week, 'dept' => $dept]) }}" class="vy-btn {{ $view === 'week' ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:4px 14px;font-size:12px;border-radius:30px">{{ __('livewire/operator/schedule.view_week') }}</a>
                        <a href="{{ route('app.schedule', ['week' => $week, 'dept' => $dept, 'view' => 'day', 'day' => $focusDate]) }}" class="vy-btn {{ $view === 'day' ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:4px 14px;font-size:12px;border-radius:30px">{{ __('livewire/operator/schedule.view_day') }}</a>
                    </span>
                    @foreach ($departments as $d)
                        <a href="{{ route('app.schedule', ['dept' => $d] + $viewBase) }}" class="vy-btn {{ $dept === $d ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:5px 12px;font-size:12px">{{ \App\Support\Schedule\StaffDepartment::label($d) }}</a>
                    @endforeach
                    <a href="{{ route('app.schedule.pdf', ['week' => $week, 'dept' => $dept]) }}" class="vy-btn vy-btn--ghost" style="padding:5px 12px;font-size:12px" title="{{ __('livewire/operator/schedule.pdf_title') }}">{{ __('livewire/operator/schedule.pdf_link') }}</a>
                    {{-- #104b GATE 3 — Publish week (finalize + notify the team; edits stay live). --}}
                    @if ($canManage && ! $readOnly && count($rows) > 0)
                        <form method="POST" action="{{ route('app.schedule.publish') }}" style="margin:0">
                            @csrf
                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding:5px 12px;font-size:12px">{{ $published ? __('livewire/operator/schedule.republish') : __('livewire/operator/schedule.publish_week') }}</button>
                        </form>
                    @endif
                    @if ($published)
                        <span class="vy-tag tone-confirmed" style="font-size:11px" title="{{ __('livewire/operator/schedule.published_title') }}">{{ __('livewire/operator/schedule.published_at', ['when' => $published]) }}</span>
                    @endif
                </div>
            </div>

            {{-- The design's two-column shape: the roster grid, then a 224px rail carrying the at-a-glance
                 coverage + open shifts. The wrapper opens OUTSIDE the empty-roster branch on purpose —
                 coverage and open shifts render whether or not anyone is rostered. Stacks below 1100px. --}}
            <div class="vy-sch-cols">
            <div class="vy-sch-main">

            @if (count($rows) === 0)
                <div class="vy-card" style="padding:30px;text-align:center;color:var(--vy-muted);font-size:14px">
                    @if ($dept === 'all')
                        {!! __('livewire/operator/schedule.empty_team', ['url' => e(route('app.roles'))]) !!}
                    @else
                        {{ __('livewire/operator/schedule.empty_department', ['department' => \App\Support\Schedule\StaffDepartment::label($dept)]) }}
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
                    // The design's grid is 184px + 7 × minmax(92px, 1fr). Its 92px floor was drawn for a
                    // full-width grid; with the design's own 224px rail beside it the seven columns land at
                    // ~89px, so a 92px floor forced a permanent 10px horizontal scroll that clipped Sunday.
                    // Relaxing the floor to 88px costs nothing on desktop (the columns are 1fr there anyway)
                    // and keeps the scroll for genuinely narrow viewports, which is what it is for.
                    $gridCols = '184px repeat(7, minmax(88px, 1fr))';
                @endphp

                @if ($view === 'day')
                    {{-- GATE 9.2 — DAY VIEW: the focused day's staff grouped by working shift, plus who's off. A
                         read-only agenda derived from the SAME RosterWeek rows (reconciles with the grid). --}}
                    <div class="vy-card" style="padding:18px">
                        {{-- day picker (this week's 7 days) --}}
                        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:16px">
                            @foreach ($days as $day)
                                @php $active = $day['date'] === $dayView['date']; @endphp
                                <a href="{{ route('app.schedule', ['week' => $week, 'dept' => $dept, 'view' => 'day', 'day' => $day['date']]) }}"
                                   style="flex:1;min-width:74px;display:flex;flex-direction:column;align-items:center;gap:1px;padding:9px 0;border-radius:14px;text-decoration:none;{{ $active ? 'background:var(--vy-wash);border:1px solid rgba(232,112,58,.45)' : 'border:1px solid var(--vy-hairline)' }}">
                                    <span style="font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:{{ $day['today'] ? '#B4400F' : 'var(--vy-faint)' }}">{{ $day['dow'] }}</span>
                                    <span style="font-family:var(--vy-display);font-weight:600;font-size:16px;color:{{ $active ? '#C2410C' : ($day['today'] ? '#B4400F' : 'var(--vy-ink)') }}">{{ $day['label'] }}</span>
                                </a>
                            @endforeach
                        </div>

                        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:18px">
                            <h2 style="margin:0;font-family:var(--vy-display);font-weight:700;font-size:18px">{{ $dayView['label'] }}</h2>
                            <span style="font-size:12px;color:var(--vy-muted)">{{ trans_choice('livewire/operator/schedule.working_count', $dayView['working_count'], ['count' => $dayView['working_count']]) }}</span>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:16px">
                            @foreach ($dayView['groups'] as $g)
                                @php $gc = $deptC[$g['type'] === 'day' ? 'housekeeping' : 'frontdesk'] ?? $deptC['manager']; @endphp
                                <div>
                                    <div style="display:flex;align-items:center;gap:9px;margin-bottom:10px">
                                        <span style="width:3px;height:15px;border-radius:2px;background:{{ $gc['bar'] }}"></span>
                                        <span style="font-family:var(--vy-display);font-weight:600;font-size:13.5px">{{ $g['label'] }} · {{ $g['hours'] }}</span>
                                        <span style="font-size:11.5px;color:var(--vy-faint)">{{ trans_choice('livewire/operator/schedule.group_people', $g['count'], ['count' => $g['count']]) }}</span>
                                    </div>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px">
                                        @forelse ($g['people'] as $p)
                                            @php $pc = $deptC[$p['department']] ?? $deptC['manager']; @endphp
                                            <div style="display:flex;align-items:center;gap:9px;padding:8px 14px 8px 8px;border-radius:30px;background:{{ $pc['bg'] }};border:{{ $pc['border'] }}">
                                                <span style="width:26px;height:26px;border-radius:50%;background:{{ $pc['bg'] }};border:{{ $pc['border'] }};color:{{ $pc['color'] }};font-size:9.5px;font-weight:600;display:grid;place-items:center">{{ $p['init'] }}</span>
                                                <span>
                                                    <span style="display:block;font-size:12.5px;font-weight:600;color:var(--vy-ink);line-height:1.15">{{ $p['name'] }}@if ($p['conflict'])<span style="color:#B0322A" title="{{ __('livewire/operator/schedule.on_leave_conflict_title') }}"> ⚠</span>@endif</span>
                                                    <span style="display:block;font-size:10px;color:var(--vy-faint);line-height:1.15">{{ $p['role'] }}</span>
                                                </span>
                                            </div>
                                        @empty
                                            <span style="font-size:12px;color:var(--vy-faint);padding:8px 4px">{{ __('livewire/operator/schedule.no_one_scheduled') }}</span>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach

                            <div style="padding-top:14px;border-top:1px solid var(--vy-hairline)">
                                <div style="font-size:10.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:10px">{{ __('livewire/operator/schedule.not_working', ['number' => count($dayView['not_working'])]) }}</div>
                                <div style="display:flex;flex-wrap:wrap;gap:8px">
                                    @forelse ($dayView['not_working'] as $o)
                                        <div style="display:flex;align-items:center;gap:8px;padding:6px 13px 6px 6px;border-radius:30px;background:var(--vy-wash);border:1px solid var(--vy-hairline)">
                                            <span style="width:22px;height:22px;border-radius:50%;background:rgba(120,70,40,.10);color:var(--vy-faint);font-size:9px;font-weight:600;display:grid;place-items:center">{{ $o['init'] }}</span>
                                            <span style="font-size:11.5px;color:var(--vy-muted)">{{ $o['name'] }}</span>
                                            <span style="font-size:10px;color:var(--vy-faint)">· {{ $o['reason'] }}</span>
                                        </div>
                                    @empty
                                        <span style="font-size:12px;color:var(--vy-faint)">{{ __('livewire/operator/schedule.everyone_working') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @if ($canManage && ! $readOnly)
                            @php $weekViewUrl = route('app.schedule', ['week' => $week, 'dept' => $dept]); @endphp
                            <p class="vy-line-sub" style="font-size:11px;color:var(--vy-faint);margin-top:16px">{!! __('livewire/operator/schedule.switch_to_week', ['url' => e($weekViewUrl)]) !!}</p>
                        @endif
                    </div>
                @else
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
                            <span class="vy-line-sub" style="font-size:11.5px;color:var(--vy-faint)">{{ $canManage ? __('livewire/operator/schedule.drag_hint_manage') : __('livewire/operator/schedule.drag_hint') }}</span>
                            @if ($canManage && ! $readOnly)
                                <button type="button" class="vy-btn vy-btn--accent" style="font-size:12px;padding:5px 14px" @click="openBlank()">{{ __('livewire/operator/schedule.add_shift_button') }}</button>
                            @endif
                        </div>

                        <div style="overflow-x:auto">
                            {{-- Matches the relaxed column floor above (184 + 7×88 + 8×8 gaps = 864). --}}
                            <div style="min-width:864px">
                                {{-- header --}}
                                <div style="display:grid;grid-template-columns:{{ $gridCols }};gap:8px;margin-bottom:8px">
                                    <div style="display:flex;align-items:flex-end;padding:0 4px 4px;font-size:10.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint)">{{ __('livewire/operator/schedule.team_column') }}</div>
                                    @foreach ($days as $day)
                                        <a href="{{ route('app.schedule', ['week' => $week, 'dept' => $dept, 'view' => 'day', 'day' => $day['date']]) }}"
                                           style="display:flex;flex-direction:column;align-items:center;gap:1px;padding:7px 0;border-radius:12px;text-decoration:none;{{ $day['today'] ? 'background:rgba(217,102,46,.12);border:1px solid rgba(232,112,58,.4)' : 'border:1px solid transparent' }}" title="{{ __('livewire/operator/schedule.open_day_view') }}">
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
                                                    {{-- ⚠️⚠️ D5 — THE TELL, AND THE ASYMMETRY IT CREATED. The EMPTY cell below is a real <button>, so a
                                                         keyboard user could ADD a shift; this filled cell was a <div @click>, so
                                                         they could not CHANGE one. Add worked and edit did not, on the same grid.
                                                         ⚠️ A REAL <button>, not a div taught to behave: it is focusable, Enter and
                                                         Space already activate it, and it is announced as a button — none of which
                                                         a tabindex+keydown pair reproduces exactly. Drag still works; `draggable`
                                                         is valid on a button. When the actor cannot manage, it renders as a plain
                                                         <span> instead, because a disabled-looking button that does nothing is its
                                                         own defect. --}}
                                                    <button type="button" data-shift-type="{{ $cell['type'] }}" @if ($canManage && ! $readOnly) draggable="true" @dragstart="start('{{ $sid }}','{{ $cell['date'] }}', $event)" @dragend="reset()" @click="openCell('{{ $sid }}','{{ $cell['date'] }}','{{ $cell['type'] }}')" title="{{ __('livewire/operator/schedule.drag_title') }}" @endif
                                                         style="position:relative;height:100%;box-sizing:border-box;border-radius:11px;background:{{ $cell['conflict'] ? 'rgba(176,50,42,.08)' : $dc['bg'] }};border:{{ $cell['conflict'] ? '1px solid rgba(176,50,42,.4)' : $dc['border'] }};padding:8px 9px 8px 12px;display:flex;flex-direction:column;justify-content:center;gap:2px;overflow:hidden;{{ $canManage && ! $readOnly ? 'cursor:grab' : '' }}font:inherit;color:inherit;text-align:left;-webkit-appearance:none;appearance:none;width:100%;">
                                                        <span style="position:absolute;top:0;left:0;bottom:0;width:3px;background:{{ $cell['conflict'] ? '#B0322A' : $dc['bar'] }}"></span>
                                                        <span style="font-size:11.5px;font-weight:600;color:{{ $cell['conflict'] ? '#B0322A' : $dc['color'] }};line-height:1.1">{{ $cell['label'] }}</span>
                                                        <span style="font-size:10px;color:{{ $cell['conflict'] ? '#B0322A' : $dc['color'] }};opacity:.75">{{ $cell['conflict'] ? __('livewire/operator/schedule.on_leave_flag') : $cell['hours'] }}</span>
                                                    </button>
                                                @elseif ($cell['on_leave'])
                                                    <div style="height:100%;box-sizing:border-box;border-radius:11px;background:rgba(120,70,40,.05);border:1px dashed rgba(120,70,40,.22);display:flex;align-items:center;justify-content:center" title="{{ __('livewire/operator/schedule.approved_leave') }}">
                                                        <span style="font-size:10.5px;font-weight:600;color:#8A6E2F">{{ __('livewire/operator/schedule.on_leave') }}</span>
                                                    </div>
                                                @elseif ($canManage && ! $readOnly)
                                                    <button type="button" @click="openCell('{{ $sid }}','{{ $cell['date'] }}','off')" title="{{ __('livewire/operator/schedule.add_a_shift') }}"
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
                        <p class="vy-line-sub" style="font-size:11px;color:var(--vy-faint);margin-top:10px">{{ __('livewire/operator/schedule.shift_legend', ['early_hours' => \App\Support\Schedule\ShiftType::hours('early'), 'day_hours' => \App\Support\Schedule\ShiftType::hours('day'), 'late_hours' => \App\Support\Schedule\ShiftType::hours('late')]) }}</p>
                    </div>

                    {{-- Add / change / clear a shift — one modal, opened blank (toolbar) or pre-targeted (a cell click).
                         Posts to the SAME guarded schedule.assign. The date picker offers this week's real days only. --}}
                    @if ($canManage)
                        <div class="vy-modal-backdrop" x-show="modal" x-cloak @click.self="closeModal()" style="z-index:50" @keydown.escape.window="modal = false">
                            <form method="POST" action="{{ route('app.schedule.assign') }}" class="vy-card" style="width:min(420px,94vw);padding:22px" @click.stop>
                                @csrf
                                <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                <h2 class="vy-card-title" style="margin:0 0 14px" x-text="mType === 'off' ? @js(__('livewire/operator/schedule.modal_add_shift')) : @js(__('livewire/operator/schedule.modal_change_shift'))" id="vy-dlg-schedule-shift-t">{{ __('livewire/operator/schedule.modal_add_shift') }}</h2>

                                <div class="vy-field" style="margin-bottom:12px" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="modal" aria-labelledby="vy-dlg-schedule-shift-t">
                                    <label class="vy-field-label" for="m_user">{{ __('livewire/operator/schedule.team_member') }}</label>
                                    <select id="m_user" name="user_id" class="vy-input" required x-model="mUser">
                                        <option value="">{{ __('livewire/operator/schedule.choose') }}</option>
                                        @foreach ($rows as $r)
                                            <option value="{{ $r['staff']['id'] }}">{{ $r['staff']['name'] }} · {{ $r['staff']['role_label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="vy-field" style="margin-bottom:14px">
                                    <label class="vy-field-label" for="m_date">{{ __('livewire/operator/schedule.day_field') }}</label>
                                    <select id="m_date" name="date" class="vy-input" required x-model="mDate">
                                        @foreach ($days as $day)
                                            <option value="{{ $day['date'] }}">{{ $day['dow'] }} {{ $day['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="vy-field" style="margin-bottom:18px">
                                    <label class="vy-field-label">{{ __('livewire/operator/schedule.shift_field') }}</label>
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
                                    <p class="vy-line-sub" style="font-size:11px;margin:8px 0 0;color:var(--vy-faint)">{{ __('livewire/operator/schedule.off_hint') }}</p>
                                </div>

                                <div style="display:flex;gap:8px;justify-content:flex-end">
                                    <button type="button" class="vy-btn vy-btn--ghost" style="font-size:12.5px" @click="closeModal()">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12.5px">{{ __('livewire/operator/schedule.save_shift') }}</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
                @endif
            @endif

            </div>{{-- /.vy-sch-main --}}
            <div class="vy-sch-rail">

            {{-- GATE 9.2 — "Coverage this week": HONEST per-department bars = the number of days (of 7) with at least
                 one person from that team working (never an invented staffing target — the prototype's 14/16 was
                 fabricated). Footer keeps the real team / shifts / rostered-hours totals from the SAME builder. --}}
            <div class="vy-card vy-fade" style="margin-top:18px">
                <div style="font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:14px">{{ __('livewire/operator/schedule.coverage_heading') }}</div>
                @forelse ($coverageBars as $c)
                    <div style="margin-bottom:13px">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px">
                            <span style="font-size:12.5px;font-weight:600;color:var(--vy-ink)">{{ $c['label'] }}</span>
                            <span style="font-size:11.5px;font-weight:600;color:{{ $c['full'] ? '#1F7A52' : '#8F6414' }}">{{ trans_choice('livewire/operator/schedule.coverage_days', $c['total'], ['filled' => $c['filled'], 'total' => $c['total']]) }}</span>
                        </div>
                        <div style="height:7px;border-radius:5px;background:var(--vy-wash);overflow:hidden">
                            <div style="height:100%;width:{{ $c['pct'] }}%;border-radius:5px;background:{{ $c['full'] ? 'linear-gradient(90deg,#47A06E,#1F8A5B)' : 'linear-gradient(90deg,#E8B45A,#D99A3D)' }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty" style="padding:8px 0">{{ __('livewire/operator/schedule.no_teams') }}</div>
                @endforelse
                <p class="vy-line-sub" style="font-size:11px;color:var(--vy-faint);margin:6px 0 14px">{{ __('livewire/operator/schedule.coverage_hint') }}</p>
                <div style="display:flex;gap:28px;flex-wrap:wrap;align-items:flex-end;border-top:1px solid var(--vy-hairline);padding-top:14px">
                    <div><div class="vy-dk-label">{{ __('livewire/operator/schedule.team_on_rota') }}</div><div style="font-family:var(--vy-display);font-size:22px;font-weight:700">{{ $coverage['team'] }}</div></div>
                    <div><div class="vy-dk-label">{{ __('livewire/operator/schedule.shifts_this_week') }}</div><div style="font-family:var(--vy-display);font-size:22px;font-weight:700">{{ $coverage['shifts'] }}</div></div>
                    <div><div class="vy-dk-label">{{ __('livewire/operator/schedule.rostered_hours') }}</div><div style="font-family:var(--vy-display);font-size:22px;font-weight:700">{{ __('livewire/operator/schedule.hours_value', ['hours' => $coverage['hours']]) }}</div></div>
                </div>
            </div>

            {{-- #104b GATE 3 — OPEN SHIFTS: unassigned slots a manager posts, then assigns to a real staffer (fill
                 creates a real Shift via the certified service). --}}
            @if ($canManage)
                <div class="vy-card vy-fade" style="margin-top:18px" x-data="{ addOpen: false }">
                    {{-- The design's rail heading: a status dot + a small uppercase label, with the count in
                         the label itself. The count is the REAL number of open slots — the same $openShifts
                         the list below renders, so the heading can never promise a row that isn't there. --}}
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:13px">
                        <span style="width:9px;height:9px;border-radius:50%;background:var(--vy-hk-pending);flex-shrink:0"></span>
                        <span style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--vy-faint)">{{ __('livewire/operator/schedule.open_shifts', ['number' => count($openShifts)]) }}</span>
                    </div>
                    @unless ($readOnly)
                        <button type="button" class="vy-btn vy-btn--ghost" style="width:100%;font-size:11.5px;padding:7px 0;justify-content:center;margin-bottom:4px" @click="addOpen = !addOpen">{{ __('livewire/operator/schedule.add_open_shift') }}</button>
                    @endunless
                    @unless ($readOnly)
                        <form x-show="addOpen" x-cloak method="POST" action="{{ route('app.schedule.open.create') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-top:12px">
                            @csrf
                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                            <input type="date" name="date" class="vy-input" required aria-label="{{ __('common.date') }}" style="min-width:150px">
                            <select name="shift_type" class="vy-input" required aria-label="{{ __('livewire/operator/schedule.shift_field') }}" style="min-width:120px"><option value="early">{{ __('livewire/operator/schedule.shift_early') }}</option><option value="day">{{ __('livewire/operator/schedule.shift_day') }}</option><option value="late">{{ __('livewire/operator/schedule.shift_late') }}</option></select>
                            <label class="vy-sr-only" for="vy-lbl-schedule-department">{{ __('livewire/operator/schedule.department_optional') }}</label>
                            <input id="vy-lbl-schedule-department" type="text" name="department" class="vy-input" maxlength="40" placeholder="{{ __('livewire/operator/schedule.department_optional') }}" style="min-width:150px">
                            <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12.5px">{{ __('livewire/operator/schedule.post_open_shift') }}</button>
                        </form>
                    @endunless
                    @forelse ($openShifts as $o)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-top:1px solid var(--vy-hairline);font-size:13px">
                            <div><strong>{{ $o['shift_label'] }}</strong> · {{ $o['day_label'] }}@if ($o['department'])<span class="vy-muted"> · {{ $o['department'] }}</span>@endif<br><span class="vy-muted" style="font-size:11.5px">{{ __('livewire/operator/schedule.no_one_scheduled') }}</span></div>
                            @unless ($readOnly)
                                <div style="display:flex;gap:6px;flex-shrink:0">
                                    <form method="POST" action="{{ route('app.schedule.open.fill', $o['id']) }}" style="margin:0;display:flex;gap:6px">
                                        @csrf
                                        <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                        <select name="user_id" required aria-label="{{ __('livewire/operator/schedule.assign_to') }}" class="vy-input" style="font-size:12px;padding:4px 8px">
                                            <option value="">{{ __('livewire/operator/schedule.assign_placeholder') }}</option>
                                            @foreach ($rows as $r)
                                                <option value="{{ $r['staff']['id'] }}">{{ $r['staff']['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12px;padding:4px 10px">{{ __('livewire/operator/schedule.assign') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('app.schedule.open.remove', $o['id']) }}" style="margin:0" onsubmit="return confirm(@js(__('livewire/operator/schedule.remove_open_shift_confirm')));">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="font-size:12px;padding:4px 10px">{{ __('common.remove') }}</button>
                                    </form>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="vy-empty" style="padding:12px 0;margin-top:6px">{{ __('livewire/operator/schedule.no_open_shifts') }}</div>
                    @endforelse
                </div>
            @endif

            </div>{{-- /.vy-sch-rail --}}
            </div>{{-- /.vy-sch-cols --}}

            {{-- ── Time off — request (anyone) + the manager approval queue ─────────────────────────── --}}
            {{-- ⚠️ WAS AN INLINE `grid-template-columns: 1fr 1fr`, which is `minmax(auto, 1fr)` — a
                 MIN-CONTENT FLOOR the tracks could not shrink below, and 95px of sideways scroll on a
                 phone. A media query cannot live in a style attribute, so the rule moves to the shell
                 beside the `.vy-sch-*` family it belongs to. --}}
            <div class="vy-sch-panels @unless ($canManage) vy-sch-panels--single @endunless">
                <section class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:4px">{{ __('livewire/operator/schedule.request_time_off') }}</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">{{ __('livewire/operator/schedule.request_time_off_hint') }}</p>
                    <form method="POST" action="{{ route('app.schedule.timeoff') }}">
                        @csrf
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <div class="vy-field" style="flex:1;min-width:130px;margin-bottom:10px">
                                <label class="vy-field-label" for="to_start">{{ __('livewire/operator/schedule.from') }}</label>
                                <input id="to_start" type="date" name="start_date" class="vy-input" required>
                            </div>
                            <div class="vy-field" style="flex:1;min-width:130px;margin-bottom:10px">
                                <label class="vy-field-label" for="to_end">{{ __('livewire/operator/schedule.to') }}</label>
                                <input id="to_end" type="date" name="end_date" class="vy-input" required>
                            </div>
                        </div>
                        <div class="vy-field" style="margin-bottom:12px">
                            <label class="vy-field-label" for="to_reason">{!! __('livewire/operator/schedule.reason_optional_label') !!}</label>
                            <input id="to_reason" type="text" name="reason" class="vy-input" maxlength="160" placeholder="{{ __('livewire/operator/schedule.reason_example') }}">
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">{{ __('livewire/operator/schedule.submit_request') }}</button>
                    </form>
                </section>

                @if ($canManage)
                    <section class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom:10px">{{ __('livewire/operator/schedule.pending_time_off') }}</h2>
                        @forelse ($pending as $req)
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                                <div style="min-width:0">
                                    <div style="font-size:13px;font-weight:600;color:var(--vy-ink)">{{ $req['name'] }}</div>
                                    <div class="vy-line-sub" style="font-size:11.5px">{{ $req['range'] }}@if ($req['reason'] !== '') · {{ $req['reason'] }}@endif</div>
                                </div>
                                @if ($req['is_self'])
                                    <span class="vy-line-sub" style="font-size:11.5px;white-space:nowrap">{{ __('livewire/operator/schedule.awaiting_another_manager') }}</span>
                                @else
                                    <div style="display:flex;gap:6px;flex-shrink:0">
                                        <form method="POST" action="{{ route('app.schedule.leave.approve', $req['id']) }}" style="margin:0">
                                            @csrf
                                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                            <button type="submit" class="vy-btn vy-btn--accent" style="padding:5px 12px;font-size:12px">{{ __('livewire/operator/schedule.approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('app.schedule.leave.deny', $req['id']) }}" style="margin:0">
                                            @csrf
                                            <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                            <button type="submit" class="vy-btn vy-btn--ghost" style="padding:5px 12px;font-size:12px">{{ __('livewire/operator/schedule.decline') }}</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="vy-empty" style="margin-top:6px">{{ __('livewire/operator/schedule.no_pending') }}</div>
                        @endforelse

                        {{-- #104 GATE 3d — grant leave directly (manager). The route existed with no UI; a manager
                             can't grant their OWN leave (self-approval separation is enforced in LeaveService). --}}
                        <div style="margin-top:16px;border-top:1px solid var(--vy-hairline);padding-top:14px">
                            <h3 class="vy-card-title" style="font-size:13px;margin-bottom:8px">{{ __('livewire/operator/schedule.add_leave_heading') }}</h3>
                            <form method="POST" action="{{ route('app.schedule.leave.grant') }}">
                                @csrf
                                <input type="hidden" name="week" value="{{ $week }}"><input type="hidden" name="dept" value="{{ $dept }}">
                                <div class="vy-field" style="margin-bottom:8px">
                                    <select name="user_id" class="vy-input" required aria-label="{{ __('livewire/operator/schedule.team_member') }}">
                                        <option value="">{{ __('livewire/operator/schedule.choose_team_member') }}</option>
                                        @foreach ($rows as $r)
                                            <option value="{{ $r['staff']['id'] }}">{{ $r['staff']['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="display:flex;gap:10px;flex-wrap:wrap">
                                    <input type="date" name="start_date" class="vy-input" required aria-label="{{ __('livewire/operator/schedule.from') }}" style="flex:1;min-width:120px">
                                    <input type="date" name="end_date" class="vy-input" required aria-label="{{ __('livewire/operator/schedule.to') }}" style="flex:1;min-width:120px">
                                </div>
                                <label class="vy-sr-only" for="vy-lbl-schedule-reason">{{ __('livewire/operator/schedule.reason_optional') }}</label>
                                <input id="vy-lbl-schedule-reason" type="text" name="reason" class="vy-input" maxlength="160" placeholder="{{ __('livewire/operator/schedule.reason_optional') }}" style="margin-top:8px">
                                <button type="submit" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;margin-top:10px">{{ __('livewire/operator/schedule.add_leave') }}</button>
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
