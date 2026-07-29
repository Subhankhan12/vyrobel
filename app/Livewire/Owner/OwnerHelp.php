<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Services\AskVyrobelService;
use App\Support\Help\HelpKnowledgeBase;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Help & Support (Phase 2 · Sub-gate 6 → exact-match #66; "Ask Vyrobel" made REAL in Wave D) — matched to
 * the decoded "Owner Help" wireframe, HONEST throughout. The ONE real contact channel is "Message your manager"
 * → the owner↔operator inbox (Wave 3); the FAQ answers link to the real owner screens (rendered from the ONE
 * shared {@see HelpKnowledgeBase}); typing in the hero also filters the FAQ live client-side.
 *
 * "Ask Vyrobel" is now a REAL grounded assistant (Wave D): the hero posts to owner.help.ask →
 * {@see AskVyrobelService}, which answers ONLY from the real Help KB (retrieval gate), escalates to
 * the real relay when it has no answer (out-of-scope / account-specific), and degrades honestly on cost/provider
 * limits — never a fabricated answer (#39). The render stays a READ-ONLY component (every write is the controller
 * <form> to owner.help.ask / owner.messages.start), so the mutation boundary is intact.
 *
 * STILL REFUSED as fabrication (#39) — the wireframe draws these over unbuilt subsystems:
 *   • the support-TICKET queue (create/track/resolve, "N tracked") → messaging IS the tracked channel; no fake ticket.
 *   • the live SYSTEM-STATUS board (per-service health) → an honest STATIC "all systems operational" line, not a check.
 *   • the fabricated owner-desk phone / email / WhatsApp numbers → NOT invented; the real channel is the relay, with
 *     an honest note that any direct line is on the welcome pack / statement.
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
