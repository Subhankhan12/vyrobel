<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\NotificationController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator in-app notification feed (Phase 3 · Sub-gate 1). Renders the AUTHENTICATED user's OWN notifications
 * (via {@see NotificationFeed} → the per-user Notifiable relation) — never another user's. `?filter=` (a
 * category) rides GET. Mark-read writes go POST → {@see NotificationController}.
 */
#[Layout('components.layouts.operator')]
class OperatorNotifications extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $filter = request()->query('filter');
        $filter = is_string($filter) ? $filter : 'all';

        return view('livewire.operator.notifications', [
            'viewer' => $this->viewer(),
            'feed' => NotificationFeed::forUser($user, $filter),
            'filter' => $filter,
        ]);
    }
}
