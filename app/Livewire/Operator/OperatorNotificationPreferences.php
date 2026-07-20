<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\NotificationPreferenceController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\User;
use App\Services\NotificationPreferences;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Notification Preferences (Phase 3 · Sub-gate 2) — the AUTHENTICATED user's own per-event, per-channel
 * toggles. Read side; the self-only write goes POST → {@see NotificationPreferenceController}.
 * These genuinely gate delivery (via {@see NotificationPreferences::allows()}); always-on events render locked.
 */
#[Layout('components.layouts.operator')]
class OperatorNotificationPreferences extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('livewire.operator.notification-preferences', [
            'viewer' => $this->viewer(),
            'groups' => app(NotificationPreferences::class)->groupedFor($user, 'operator'),
            'channels' => NotificationPreferences::CHANNELS,
        ]);
    }
}
