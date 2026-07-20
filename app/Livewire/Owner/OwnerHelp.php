<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Help & Support (Phase 2 · Sub-gate 6) — a STATIC help surface: FAQ answers that link to the real owner
 * screens, plus the ONE real contact route — "Message your manager" → the existing owner ↔ operator inbox
 * (Wave 3). The design's AI Q&A, support-TICKET queue, per-topic article counts and live system-status board are
 * unbuilt subsystems and are NOT rendered (no faked tickets/chat/status). Nothing here needs a backend.
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
