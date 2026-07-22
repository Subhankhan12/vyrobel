{{-- Operator Housekeeping — cleaning kanban (design pass · Operator Screen 3). Styled on the shared
     operator design system via <x-operator.shell>. Tasks + statuses are REAL and tenant-scoped; the
     4 columns ARE the real status enum. The board only READS; status advance posts to the real action
     (HousekeepingActionController → HousekeepingService) and persists. Presentation only otherwise. --}}
<div>
    <x-operator.shell active="housekeeping" title="Housekeeping" subtitle="Cleaning board" :viewer="$viewer">

        @if (session('hk_notice'))
            <div class="vy-toast vy-fade" role="status">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('hk_notice') }}
            </div>
        @endif

        {{-- KPI band — DERIVED real counts as uniform light tiles (the shared .vy-dash-kpis--3 band,
             matching the prototype's status pills + the Maintenance/Inbox precedent). --}}
        <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade">
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Tasks</div>
                <div class="vy-dk-val">{{ $summary['total'] }}</div>
                <div class="vy-dk-sub">cleaning tasks in view</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">High priority</div>
                <div class="vy-dk-val">{{ $summary['high'] }}</div>
                <div class="vy-dk-sub">flagged urgent</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Open</div>
                <div class="vy-dk-val">{{ $summary['pending'] + $summary['in_progress'] }}</div>
                <div class="vy-dk-sub">pending + in progress</div>
            </div>
        </div>

        {{-- Slim toolbar — honest "Soon" affordances (the summary moved up into the KPI band). --}}
        <div class="vy-rack-toolbar vy-fade" style="justify-content: flex-end; margin-top: 14px;">
            <div class="vy-right">
                <span class="vy-pill is-soon" aria-disabled="true">Filters <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-pill is-soon" aria-disabled="true">Export <span class="vy-soon-tag">Soon</span></span>
            </div>
        </div>

        @if ($summary['total'] === 0)
            <div class="vy-card vy-fade"><div class="vy-empty">No housekeeping tasks yet — they’re generated on check-out.</div></div>
        @else
            <div class="vy-kanban vy-fade">
                @foreach ($columns as $col)
                    <div class="vy-col tone-{{ $col['tone'] }}">
                        <div class="vy-col-head">
                            <span class="vy-col-title"><span class="vy-col-dot"></span>{{ $col['label'] }}</span>
                            <span class="vy-col-count">{{ count($col['tasks']) }}</span>
                        </div>
                        <div class="vy-col-body">
                            @forelse ($col['tasks'] as $task)
                                <div class="vy-hk-card">
                                    <div class="vy-hk-top">
                                        <span class="vy-hk-unit">Unit {{ $task['unit'] }}</span>
                                        <span class="vy-hk-date">{{ $task['date'] }}</span>
                                    </div>
                                    <div class="vy-hk-type">{{ $task['type'] }}</div>
                                    <div class="vy-hk-meta">
                                        <span class="vy-prio tone-{{ $task['priority_tone'] }}">{{ $task['priority_label'] }}</span>
                                        @if ($task['assignee'])
                                            <span class="vy-hk-assignee">
                                                <span class="vy-mini-avatar">{{ strtoupper(mb_substr($task['assignee'], 0, 1)) }}</span>
                                                {{ $task['assignee'] }}
                                            </span>
                                        @else
                                            {{-- DEFERRED: no HousekeepingService assign method — display real state, never fake an assignment. --}}
                                            <span class="vy-hk-unassigned">Unassigned</span>
                                            <span class="vy-soon-tag" title="Assigning a housekeeper isn’t wired yet">Assign · Soon</span>
                                        @endif
                                    </div>

                                    @if (count($task['next']) > 0)
                                        <div class="vy-hk-actions">
                                            @foreach ($task['next'] as $action)
                                                <form method="POST" action="{{ route('app.housekeeping.status', $task['id']) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $action['to'] }}">
                                                    <button type="submit" class="vy-act {{ $loop->first ? 'vy-act--accent' : 'vy-act--ghost' }}">{{ $action['label'] }}</button>
                                                </form>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="vy-hk-col-empty">None</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="vy-deferred-note" style="margin-top: 18px;">
                <span class="vy-soon-tag">Not yet available</span>
                Drag-to-move &amp; housekeeper assignment are planned — advance a task with its status button
                (Start / Mark clean / Mark inspected), which persists through the real cleaning action.
            </div>
        @endif

    </x-operator.shell>
</div>
