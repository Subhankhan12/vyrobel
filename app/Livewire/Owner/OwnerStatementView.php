<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CONTRACT STUB — a single owner statement with its line breakdown (unstyled, read-only).
 *
 * The statement id is a plain string prop; the model is resolved in render() via an owner+tenant
 * scoped findOrFail (NOT a typed route-model-bound prop, which Livewire would resolve during
 * SubstituteBindings, BEFORE the tenant/owner middleware). So another OWNER's statement — like
 * another org's — 404s rather than leaking.
 *
 * The view receives:
 *   statement: array{ id, sequence_no, period_start, period_end, status, currency, source_currency,
 *                     net_source_minor: int, net_payable_minor: int, fx_rate: string|null, fx_source: string|null }
 *   lines: list<array{ line_type, description, amount_minor: int, currency, unverified: bool }>
 */
#[Layout('components.layouts.app')]
class OwnerStatementView extends Component
{
    public string $statementId;

    public function mount(string $statement): void
    {
        $this->statementId = $statement;
    }

    public function render(): View
    {
        // Owner + tenant scoped: a cross-owner (or cross-tenant) id resolves to nothing → 404.
        $statement = OwnerStatement::query()->findOrFail($this->statementId);

        $lines = OwnerStatementLine::query()
            ->where('owner_statement_id', $statement->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OwnerStatementLine $l): array => [
                'line_type' => $l->line_type,
                'description' => $l->description,
                'amount_minor' => (int) $l->amount_minor,
                'currency' => $l->currency,
                'unverified' => (bool) $l->unverified,
            ])->all();

        return view('livewire.owner.statement', [
            'statement' => [
                'id' => $statement->id,
                'sequence_no' => $statement->sequence_no,
                'period_start' => $statement->period_start->toDateString(),
                'period_end' => $statement->period_end->toDateString(),
                'status' => $statement->status,
                'currency' => $statement->currency,
                'source_currency' => $statement->source_currency,
                'net_source_minor' => (int) $statement->net_source_minor,
                'net_payable_minor' => (int) $statement->net_payable_minor,
                'fx_rate' => $statement->fx_rate,
                'fx_source' => $statement->fx_source,
            ],
            'lines' => $lines,
        ]);
    }
}
