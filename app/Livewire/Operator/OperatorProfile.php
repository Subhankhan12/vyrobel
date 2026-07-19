<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\OperatorProfileController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator PROFILE (Phase 1 · Sub-gate 5) — the logged-in operator's OWN profile: name + work email (editable),
 * with their role, status and member-since shown read-only. Behind `auth` + #26 (no fine-grained permission —
 * every back-office user edits their own profile, like `/app/security`). The save is a self-only POST →
 * {@see OperatorProfileController} (auth-resolved, never a request id).
 *
 * Honesty: only the genuinely-backed fields are editable (name, email — real `users` columns). The design's
 * phone / preferred-name / timezone / density fields have no column, so they are OMITTED rather than shown as a
 * fake save.
 */
#[Layout('components.layouts.operator')]
class OperatorProfile extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.operator.operator-profile', [
            'viewer' => $this->viewer(),
            'user' => $user,
            'roles' => $user instanceof User ? $user->getRoleNames()->implode(' · ') : '',   // team set by the request middleware
            'memberSince' => $user instanceof User ? $user->created_at?->format('M Y') : null,
        ]);
    }
}
