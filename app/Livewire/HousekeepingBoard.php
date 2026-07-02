<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\HousekeepingTask;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CONTRACT STUB — Housekeeping board (unstyled). Read-only.
 *
 * The view receives `columns`: array<string, list<array{
 *   id: string, unit: string, type: string, status: string, priority: string, date: string,
 * }>> keyed by task status (pending | in_progress | clean | inspected).
 */
#[Layout('components.layouts.app')]
class HousekeepingBoard extends Component
{
    public function render(): View
    {
        /** @var array<string, list<array<string, string>>> $columns */
        $columns = ['pending' => [], 'in_progress' => [], 'clean' => [], 'inspected' => []];

        HousekeepingTask::query()
            ->with('unit')
            ->orderBy('date')
            ->get()
            ->each(function (HousekeepingTask $task) use (&$columns): void {
                $columns[$task->status][] = [
                    'id' => $task->id,
                    'unit' => $task->unit->code,
                    'type' => $task->type,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'date' => CarbonImmutable::parse($task->date)->toDateString(),
                ];
            });

        return view('livewire.housekeeping-board', ['columns' => $columns]);
    }
}
