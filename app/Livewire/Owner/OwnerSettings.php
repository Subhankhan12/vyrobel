<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerAccountController;
use App\Http\Controllers\Owner\OwnerProfileController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner "PROFILE & ACCOUNT" (owner account area · Gate 1) — the acting login's OWN account, on the owner
 * design system via <x-owner.shell>. READ view; every write is a guarded controller POST (never a Livewire
 * action) so route middleware always runs (the /livewire/update boundary lesson).
 *
 * TWO identities, deliberately separated:
 *   • MY PROFILE (per-USER, auth()-resolved) — name / login-email / language / avatar. Works for EVERY
 *     owner-realm user, including accountant / view-only SUB-USERS. This fixes the old sub-user 404: the page
 *     no longer needs an `Owner` row to show a profile, so a sub-user (who has no `Owner` row, only an
 *     `owner_users` pivot) gets a full, editable profile — via {@see OwnerAccountController}.
 *   • OWNER / BUSINESS DETAILS (the party the management team pays out to) — 🔒 PRIMARY-OWNER-ONLY (#42):
 *     shown + editable only when `isPrimaryOwner`; via {@see OwnerProfileController}, route-gated by
 *     EnsurePrimaryOwner. Tax/VAT + bank live on their own screens (Tax Center / Payout Method) — linked, not
 *     duplicated. Showing an owner their OWN details (decrypted) is correct; #28 is about OTHER people's PII.
 */
#[Layout('components.layouts.owner')]
class OwnerSettings extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $user = auth()->user();
        $ownerCtx = app(OwnerContext::class);
        $isPrimaryOwner = $ownerCtx->isPrimaryOwner();

        // Owner-party row: only the PRIMARY owner has one keyed to their login. Sub-users don't (null) — and
        // don't see the business card at all.
        $ownerParty = $isPrimaryOwner
            ? Owner::query()->where('user_id', $user->getKey())->first()
            : null;

        return view('livewire.owner.owner-settings', [
            'viewer' => $this->viewer(),
            'isPrimaryOwner' => $isPrimaryOwner,
            'canPayout' => $ownerCtx->can(OwnerTeamRole::CAP_PAYOUT),
            'account' => [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'pending_email' => (string) ($user->pending_email ?? ''),
                'locale' => (string) ($user->locale ?: 'en'),
                'avatar_url' => $user->avatarUrl(),
                'initials' => $this->viewer()['initials'],
            ],
            'localeOptions' => [
                'en' => 'English',
                'pt' => 'Português',
                'es' => 'Español',
                'fr' => 'Français',
            ],
            'ownerParty' => $ownerParty !== null ? [
                'name' => (string) $ownerParty->name,
                'email' => (string) ($ownerParty->email ?? ''),
                'phone' => (string) ($ownerParty->phone ?? ''),
                'type' => (string) $ownerParty->type,
            ] : null,
        ]);
    }
}
