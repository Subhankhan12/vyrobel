<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner in-app notification feed (Phase 3 · Sub-gate 1). The owner's OWN notifications (payout sent, statement
 * ready, …), via the per-user Notifiable relation — never another user's, and never another owner's (a
 * notification belongs to a User, and the owner-facing ones are dispatched only to that owner's linked user).
 * `?filter=` rides GET; mark-read goes POST.
 */
#[Layout('components.layouts.owner')]
class OwnerNotifications extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $filter = request()->query('filter');
        $filter = is_string($filter) ? $filter : 'all';

        return view('livewire.owner.notifications', [
            'viewer' => $this->viewer(),
            'feed' => NotificationFeed::forUser($user, $filter),
            'filter' => $filter,
        ]);
    }
}
