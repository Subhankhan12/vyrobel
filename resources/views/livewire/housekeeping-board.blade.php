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

        {{-- Toolbar: DERIVED summary + honest NO-BACKEND affordances --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left vy-summary" style="margin: 0;">
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['total'] }}</span><span class="vy-sc-label">Tasks</span></div>
                <div class="vy-summary-chip @if ($summary['high'] > 0) is-due @endif"><span class="vy-sc-num">{{ $summary['high'] }}</span><span class="vy-sc-label">High priority</span></div>
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['pending'] + $summary['in_progress'] }}</span><span class="vy-sc-label">Open</span></div>
            </div>
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
