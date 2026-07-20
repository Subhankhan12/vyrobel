<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\NotificationPreferenceController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\User;
use App\Services\NotificationPreferences;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Notification Preferences (Phase 3 · Sub-gate 2) — the owner's own per-event, per-channel toggles
 * (Statement ready, Payout sent, Performance digest, Terms changed, and the always-on Account & security). Read
 * side; the self-only write goes POST → {@see NotificationPreferenceController}.
 */
#[Layout('components.layouts.owner')]
class OwnerNotificationPreferences extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('livewire.owner.notification-preferences', [
            'viewer' => $this->viewer(),
            'groups' => app(NotificationPreferences::class)->groupedFor($user, 'owner'),
            'channels' => NotificationPreferences::CHANNELS,
        ]);
    }
}
