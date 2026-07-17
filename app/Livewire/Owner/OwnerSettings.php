<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerProfileController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner SETTINGS (Wave 4 · Sub-gate 3) — the owner's OWN account details, on the owner design system via
 * <x-owner.shell>. READ view; the only write is the guarded POST →
 * {@see OwnerProfileController}.
 *
 * ⚠️ Owner is `BelongsToOrganization`, NOT `BelongsToOwner` — OwnerScope does not protect it. The owner is
 * resolved by the EXPLICIT `where('user_id', auth id)` filter (their own row, from their own login), the
 * same guard the write uses. Showing an owner their OWN email/phone (decrypted) is correct — the PII
 * perimeter is about OTHER people's contact details, not one's own.
 *
 * Honesty (invariant #19): only the BACKED self-profile (name + encrypted email/phone) is editable.
 * Everything the "Owner Settings" design draws over no backend — the payout account / IBAN (a locked schema
 * omission), notification preferences, an owner sub-team, and 2FA — is stubbed "Soon" as a net-new
 * SUBSYSTEM, never a fake save (the design's saveGuard:()=>{} trap).
 */
#[Layout('components.layouts.owner')]
class OwnerSettings extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $owner = Owner::query()->where('user_id', auth()->id())->first();

        return view('livewire.owner.owner-settings', [
            'viewer' => $this->viewer(),
            'hasOwner' => $owner !== null,
            'profile' => $owner !== null ? [
                'name' => (string) $owner->name,
                'email' => (string) ($owner->email ?? ''),
                'phone' => (string) ($owner->phone ?? ''),
            ] : ['name' => '', 'email' => '', 'phone' => ''],
        ]);
    }
}
