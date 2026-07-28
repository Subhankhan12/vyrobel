<?php

use App\Models\Membership;
use App\Models\MessageThread;
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Models\User;
use App\Services\OwnerExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// EXPENSE APPROVAL (Wave C · GATE 2 · ⚠️ CHANGES THE DEDUCTION MODEL). A deductible expense is created PENDING and
// does NOT reduce the owner's net until APPROVED (the ledger line posts then); QUERY holds it out of net + opens
// the relay. ⚠️ RECONCILIATION (#17) MUST hold in BOTH states: a pending/queried expense doesn't reduce net; an
// approved one does, to the cent. (ownUnit/ownerNet/lineFor are shared helpers.)

/** A linked owner-portal user (primary owner → CAP_ACTIONS) who owns a unit with a 15% agreement. */
function expenseApprovalOwner(array $s, int $unitIndex = 0): array
{
    $owner = ownUnit($s['units'][$unitIndex]);   // owner + 15% agreement + unit->owner_id
    $user = User::factory()->create(['name' => 'Olive Owner']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    return ['owner' => $owner, 'user' => $user, 'unit' => $s['units'][$unitIndex]];
}

function recordPendingExpense(array $c, int $minor = 6000): OwnerExpense
{
    return app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => (string) $c['unit']->id, 'category' => 'cleaning', 'description' => 'Turnover clean',
        'amount_minor' => $minor, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
    ]);   // no approve_now → PENDING
}

// ── 1. ⚠️ A pending deductible expense does NOT reduce net (#17 — pending state reconciles) ─────────
it('creates a deductible expense pending and does NOT charge it until approved', function () {
    $s = seedScenario(1);
    $c = expenseApprovalOwner($s);

    $expense = recordPendingExpense($c);

    // Pending: deductible=true, approval_state=pending, NO ledger line, net UNCHANGED (still 0).
    expect($expense->deductible)->toBeTrue()
        ->and($expense->approval_state)->toBe('pending')
        ->and(lineFor($c['owner']->id, 'expense'))->toBeNull()
        ->and(ownerNet($c['owner']->id))->toBe(0);
});

// ── 2. ⚠️ Owner APPROVES → charged: the ledger line posts + net drops (#17 — approved state reconciles) ──
it('charges the expense to the ledger and drops net when the owner approves', function () {
    $s = seedScenario(1);
    $c = expenseApprovalOwner($s);
    $expense = recordPendingExpense($c, 6000);

    $this->actingAs($c['user'])->post(route('owner.expenses.approve', $expense->id))->assertRedirect();

    // ⚠️ NOW it's charged: a real deduction line + net down by exactly €60.00, reconciling to the cent.
    $line = lineFor($c['owner']->id, 'expense');
    expect($expense->fresh()->approval_state)->toBe('approved')
        ->and($line)->not->toBeNull()
        ->and($line->direction)->toBe('debit')
        ->and($line->amount_minor)->toBe(6000)
        ->and(ownerNet($c['owner']->id))->toBe(-6000);
});

// ── 3. Owner QUERIES → held out of net + a real relay thread opens ────────────────────────────────
it('holds the expense out of net and opens the relay when the owner queries', function () {
    $s = seedScenario(1);
    $c = expenseApprovalOwner($s);
    $expense = recordPendingExpense($c);

    $this->actingAs($c['user'])->post(route('owner.expenses.query', $expense->id), ['note' => 'Why this cost?'])->assertRedirect();

    // Queried: still no ledger line, net unchanged, and a real owner↔operator thread was opened.
    expect($expense->fresh()->approval_state)->toBe('queried')
        ->and(lineFor($c['owner']->id, 'expense'))->toBeNull()
        ->and(ownerNet($c['owner']->id))->toBe(0)
        ->and(MessageThread::query()->where('owner_id', $c['owner']->id)->where('subject', 'like', 'Query on expense:%')->exists())->toBeTrue();
});

// ── 4. State guard + owner isolation ──────────────────────────────────────────────────────────────
it('guards the state machine and owner isolation', function () {
    $s = seedScenario(2);
    $a = expenseApprovalOwner($s, 0);
    $expense = recordPendingExpense($a);

    // Owner B cannot decide A's expense (owner-scoped → 404).
    $b = expenseApprovalOwner($s, 1);
    $this->actingAs($b['user'])->post(route('owner.expenses.approve', $expense->id))->assertNotFound();
    expect($expense->fresh()->approval_state)->toBe('pending');

    // A approves; a second decision is a no-op (guarded) — and it's charged exactly once.
    $this->actingAs($a['user'])->post(route('owner.expenses.approve', $expense->id))->assertRedirect();
    $this->actingAs($a['user'])->post(route('owner.expenses.query', $expense->id), [])->assertRedirect();
    expect($expense->fresh()->approval_state)->toBe('approved')   // unchanged by the late query
        ->and(OwnerLedgerEntry::query()->where('source_id', $expense->id)->where('entry_type', 'expense')->count())->toBe(1);
});

// ── 5. A pending expense surfaces as a REAL Approve/Query attention item ────────────────────────────
it('surfaces a pending expense as an attention item on My Units and Overview', function () {
    $s = seedScenario(1);
    $c = expenseApprovalOwner($s);
    $expense = recordPendingExpense($c, 8400);

    foreach (['/owner/units', '/owner', '/owner/expenses'] as $url) {
        $this->actingAs($c['user'])->get($url)->assertOk()
            ->assertSee('approval');   // "Expense awaiting your approval" / "Needs your approval" surfaces
    }
    // The Expenses page carries the real Approve/Query actions.
    $this->actingAs($c['user'])->get('/owner/expenses')->assertOk()
        ->assertSee(route('owner.expenses.approve', $expense->id), false)
        ->assertSee(route('owner.expenses.query', $expense->id), false)
        ->assertSee('Needs your approval');
});
