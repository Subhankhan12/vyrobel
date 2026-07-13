<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Owners (Auth Phase B · Step 4) — the org's owners + an "Invite owner" action. Read-only
 * list; the invite itself is a CSRF-protected POST → InviteOwnerController → OwnerInviteService → PRG
 * (never a Livewire wire-action for a scoped mutation). TENANT-scoped throughout.
 *
 * Honesty: the modal offers Owner / Manager / Account role options, but only OWNER is enabled — Manager
 * and Account are disabled "Coming soon" (the service rejects them and the controller re-validates).
 */
#[Layout('components.layouts.operator')]
class OperatorOwners extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $owners = Owner::query()
            ->withCount('units')
            ->orderBy('name')
            ->get()
            ->map(fn (Owner $o): array => [
                'id' => $o->id,
                'name' => (string) $o->name,
                'status' => (string) $o->status,
                'units' => (int) $o->units_count,
                'active' => $o->status === 'active',
            ])->all();

        $pending = OwnerInvite::query()
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OwnerInvite $i): array => [
                'email' => (string) $i->email,
                'expires' => $i->expires_at?->format('j M Y'),
            ])->all();

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Unit $u): array => [
                'id' => $u->id,
                'code' => (string) $u->code,
                'name' => $u->name !== null ? (string) $u->name : null,
                'owned' => $u->owner_id !== null,
            ])->all();

        return view('livewire.operator.owners', [
            'viewer' => $this->viewer(),
            'owners' => $owners,
            'pending' => $pending,
            'units' => $units,
        ]);
    }
}
