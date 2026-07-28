<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\PayoutMethod;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * OWNER WELCOME / onboarding (Wave B · GATE 6) — the honest THIN welcome flow (the design's onboarding wizard,
 * minus the un-buildable-honestly IBAN capture + micro-deposit centrepiece). Everything here is the owner's REAL
 * data: their units, the REAL management-fee rate ({@see OwnerCommissionRate} — never the wireframe's hardcoded
 * 15%), and a real hand-off to the certified Payout Method page (which owns IBAN collection). No fake bank step,
 * no fake "two micro-deposits on the way", no fabricated figure (#39). Read-only; every next step is a real link.
 *
 * SELF-ONLY: units + commission + payout resolve through the active {@see OwnerContext}/OwnerScope — never a
 * request id; a non-linked user sees the honest empty state.
 */
#[Layout('components.layouts.owner')]
class OwnerWelcome extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $context = app(OwnerContext::class);
        $units = app(OwnerPortalService::class)->units();

        return view('livewire.owner.welcome', [
            'viewer' => $this->viewer(),
            'firstName' => $this->firstName(),
            'units' => $units->map(fn (Unit $u): string => (string) ($u->name ?? $u->code))->values()->all(),
            'unitCount' => $units->count(),
            // The REAL agreement commission rate (e.g. "18%"), or null when no agreement is in effect (honest).
            'commission' => OwnerCommissionRate::percent($context),
            // Real payout-method status — a hand-off to the certified page, never an IBAN field here.
            'hasPayout' => $units->isNotEmpty() && PayoutMethod::query()->exists(),
        ]);
    }

    /** The owner's first name for the greeting, or a neutral fallback — never invented. */
    private function firstName(): string
    {
        $name = trim($this->viewer()['name']);
        $first = $name === '' ? '' : (string) preg_split('/\s+/', $name)[0];

        return $first === '' || $first === 'Owner' ? '' : $first;
    }
}
