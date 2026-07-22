<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerPayoutMethodController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Models\PayoutMethod;
use App\Services\OwnerPayoutMethodService;
use App\Support\Payouts\Iban;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Payout Method (Phase 2 · Sub-gate 5) — the owner captures/manages the bank account(s) their payouts
 * settle to. READ side; the writes go POST/DELETE → {@see OwnerPayoutMethodController}
 * → {@see OwnerPayoutMethodService}.
 *
 * ISOLATION — {@see PayoutMethod} carries the owner scope, so the list is the authenticated owner's own methods
 * and a cross-owner id never resolves on a write. Self-only throughout (the owner is resolved by user_id).
 *
 * ⚠️ NO RAW SENSITIVE DATA: the full IBAN is encrypted at rest and NEVER rendered — the list shows only its
 * masked form (···· 4821) + detected country, built from the cleartext last4/country columns. ⚠️ LIVE RAIL
 * DEFERRED: capturing an account does NOT enable an automated transfer (Stripe Connect is account-gated); the
 * screen says so honestly and never shows a fake "Verified" / micro-deposit / schedule flow. Tax + remittance
 * details are NOT duplicated here — they live on the Tax Center (Sub-gate 4), linked below.
 */
#[Layout('components.layouts.owner')]
class OwnerPayoutMethod extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $service = app(OwnerPayoutMethodService::class);
        $user = auth()->user();

        try {
            $owner = $user === null ? throw new ModelNotFoundException : $service->owner($user);
        } catch (ModelNotFoundException) {
            return view('livewire.owner.payout-method', [
                'viewer' => $this->viewer(),
                'linked' => false,
                'methods' => [],
                'currency' => 'EUR',
                'connect_started' => false,
                'connect_enabled' => false,
            ]);
        }

        return view('livewire.owner.payout-method', [
            'viewer' => $this->viewer(),
            'linked' => true,
            'methods' => $service->methods()->map(fn (PayoutMethod $m): array => [
                'id' => (string) $m->id,
                'label' => ((string) $m->label) !== '' ? (string) $m->label : 'Bank account',
                'holder' => (string) $m->account_holder,
                'masked' => Iban::masked((string) $m->iban_last4),
                'country' => Iban::nameForCode((string) $m->iban_country),
                'currency' => (string) $m->currency,
                'is_default' => (bool) $m->is_default,
            ])->all(),
            'currency' => (string) ($owner->payout_currency ?? 'EUR'),
            // Phase 8 · SG3 — the live Stripe Connect rail status.
            'connect_started' => is_string($owner->stripe_connect_account_id) && $owner->stripe_connect_account_id !== '',
            'connect_enabled' => (bool) $owner->stripe_connect_payouts_enabled,
        ]);
    }
}
