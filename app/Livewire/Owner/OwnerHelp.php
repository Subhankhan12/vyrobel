<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Help & Support (Phase 2 · Sub-gate 6 → exact-match #66) — matched to the decoded "Owner Help" wireframe,
 * HONEST throughout. The ONE real contact channel is "Message your manager" → the owner↔operator inbox (Wave 3);
 * the FAQ answers link to the real owner screens; the hero search filters the real FAQ CLIENT-SIDE (no backend).
 *
 * REFUSED as fabrication (#39) — the wireframe draws these over unbuilt subsystems:
 *   • the "Ask Vyrobel" AI Q&A (typing bubbles + generated answers) → NO fake AI; the hero is an FAQ search + the
 *     real message relay, never a fabricated instant answer.
 *   • the support-TICKET queue (create/track/resolve, "N tracked") → messaging IS the tracked channel; no fake ticket.
 *   • the live SYSTEM-STATUS board (per-service health) → an honest STATIC "all systems operational" line, not a check.
 *   • the fabricated owner-desk phone / email / WhatsApp numbers → NOT invented; the real channel is the relay, with
 *     an honest note that any direct line is on the welcome pack / statement.
 * Nothing here needs a backend.
 */
#[Layout('components.layouts.owner')]
class OwnerHelp extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        return view('livewire.owner.help', [
            'viewer' => $this->viewer(),
        ]);
    }
}
