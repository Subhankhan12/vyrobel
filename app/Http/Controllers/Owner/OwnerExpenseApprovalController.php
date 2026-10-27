<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerExpense;
use App\Models\User;
use App\Services\InboxService;
use App\Services\OwnerExpenseService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * OWNER expense-approval decisions (Wave C · GATE 2 · ⚠️ CHANGES THE DEDUCTION MODEL). The owner APPROVES a
 * pending expense (it is NOW charged to the payout/ledger, reducing net — #17) or QUERIES it (held out of the
 * net; a real owner↔operator relay is opened). OWNER-SCOPED ({@see OwnerExpense} is BelongsToOwner → `findOrFail`
 * 404s a cross-owner expense); CAP_ACTIONS-gated at the route; controller POSTs only; every decision audited.
 */
class OwnerExpenseApprovalController extends Controller
{
    public function __construct(private readonly OwnerExpenseService $expenses, private readonly AuditEventRecorder $audit) {}

    public function approve(Request $request, string $expense): RedirectResponse
    {
        $model = OwnerExpense::query()->findOrFail($expense);   // owner-scoped
        // 🔒 P1-1: CAP_ACTIONS is per-owner — a user who only has actions on ANOTHER of their owners (the route
        // gate's coarse pass) must not approve THIS expense's owner. Fails closed on the target owner's role.
        abort_unless(app(OwnerContext::class)->canOn((string) $model->owner_id, OwnerTeamRole::CAP_ACTIONS), 403);

        try {
            $this->expenses->approve($model, (string) $request->user()?->getKey());
        } catch (\RuntimeException) {
            return back()->with('owner_notice', __('php/http/controllers/owner/owner_expense_approval_controller.already_decided'));
        }

        $this->audit->record('owner.expense_approved', $model, null, ['amount_minor' => (int) $model->amount_minor]);

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_expense_approval_controller.approved'));
    }

    public function query(Request $request, InboxService $inbox, string $expense): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $model = OwnerExpense::query()->findOrFail($expense);
        abort_unless(app(OwnerContext::class)->canOn((string) $model->owner_id, OwnerTeamRole::CAP_ACTIONS), 403);   // 🔒 P1-1 per-owner

        try {
            $this->expenses->query($model, $data['note'] ?? null, (string) $request->user()?->getKey());
        } catch (\RuntimeException) {
            return back()->with('owner_notice', __('php/http/controllers/owner/owner_expense_approval_controller.already_decided'));
        }

        $this->audit->record('owner.expense_queried', $model, null, []);

        // Open the REAL owner↔operator relay about this expense (name-free — an expense carries no guest data, #28).
        $ownerId = app(OwnerContext::class)->ownerIds()[0] ?? null;
        if ($ownerId !== null) {
            $thread = $inbox->startThread([
                'channel' => 'manual',
                'subject' => 'Query on expense: '.$model->description,
                'owner_id' => $ownerId,
            ]);
            $body = trim(($data['note'] ?? '')) !== ''
                ? (string) $data['note']
                : 'I have a question about this expense before approving it.';
            $author = $request->user();
            $inbox->postOwnerMessage($thread, $body, $author instanceof User ? $author : null);
        }

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_expense_approval_controller.query_sent'));
    }
}
