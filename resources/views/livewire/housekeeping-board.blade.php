{{-- UNSTYLED contract stub — housekeeping tasks grouped by status. --}}
<div>
    <h1>Housekeeping board — contract stub</h1>
    @foreach ($columns as $status => $tasks)
        <h2>{{ $status }} ({{ count($tasks) }})</h2>
        <ul>
            @foreach ($tasks as $task)
                <li>Unit {{ $task['unit'] }} — {{ $task['type'] }} — {{ $task['priority'] }} — {{ $task['date'] }}</li>
            @endforeach
        </ul>
    @endforeach
</div>
