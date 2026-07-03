<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CONTRACT STUB — owner portal dashboard (unstyled, read-only). Every read goes through the
 * owner-scoped models (via {@see OwnerPortalService}), so the authenticated owner sees ONLY their own
 * units / statements / payouts — the {@see OwnerScope} enforces it, and a
 * non-linked user fails closed (empty). No CSS / design system — the styled owner PWA is the design pass.
 *
 * The view receives:
 *   units:      list<array{ id, code, room_type: string|null, occupancy: array<string,int> }>
 *   statements: list<array{ id, sequence_no: int|null, period_start, period_end, status,
 *                           net_source_minor: int, net_payable_minor: int, currency, has_unverified: bool }>
 *   payouts:    list<array{ id, amount_minor: int, currency, rail, status }>
 *   totals:     array{ statements_net_minor: int, paid_minor: int, currency: string|null }
 */
#[Layout('components.layouts.app')]
class OwnerDashboard extends Component
{
    public string $windowStart = '2026-09-01';

    public string $windowEnd = '2026-09-08';

    public function render(): View
    {
        $portal = app(OwnerPortalService::class);

        $units = $portal->units()->map(fn (Unit $unit): array => [
            'id' => $unit->id,
            'code' => $unit->code,
            'room_type' => $unit->roomType?->name,
            'occupancy' => $portal->occupancy((string) $unit->room_type_id, $this->windowStart, $this->windowEnd),
        ])->all();

        $statements = $portal->statements();
        $payouts = $portal->payouts();

        $currency = $statements->first()?->currency;

        return view('livewire.owner.dashboard', [
            'units' => $units,
            'statements' => $statements->map(fn (OwnerStatement $s): array => [
                'id' => $s->id,
                'sequence_no' => $s->sequence_no !== null ? (int) $s->sequence_no : null,
                'period_start' => $s->period_start->toDateString(),
                'period_end' => $s->period_end->toDateString(),
                'status' => $s->status,
                'net_source_minor' => (int) $s->net_source_minor,
                'net_payable_minor' => (int) $s->net_payable_minor,
                'currency' => $s->currency,
                'has_unverified' => (bool) $s->has_unverified,
            ])->all(),
            'payouts' => $payouts->map(fn (Payout $p): array => [
                'id' => $p->id,
                'amount_minor' => (int) $p->amount_minor,
                'currency' => $p->currency,
                'rail' => $p->rail,
                'status' => $p->status,
            ])->all(),
            'totals' => [
                'statements_net_minor' => (int) $statements->sum('net_payable_minor'),
                'paid_minor' => (int) $payouts->where('status', 'paid')->sum('amount_minor'),
                'currency' => $currency,
            ],
        ]);
    }
}
