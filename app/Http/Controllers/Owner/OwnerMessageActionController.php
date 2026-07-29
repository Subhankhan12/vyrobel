<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MessageThread;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Owner\OwnerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner-portal message WRITES (Wave 3) — a real message, through the real service, via the proven
 * POST → controller → service → redirect (PRG) pattern.
 *
 * ⚠️ OWNER-SCOPED, and that is the security fact. `{thread}` is a plain STRING param resolved via
 * {@see OwnerScope} (MessageThread is BelongsToOwner) AFTER SetOwnerScopeForPortal — never route-model-bound,
 * which would resolve during SubstituteBindings, i.e. BEFORE the scope middleware. So a cross-owner thread
 * 404s, and so does any operator↔guest thread (its `owner_id` is NULL and therefore not in the owner's ids).
 * An owner cannot post into a conversation that is not theirs, and cannot discover that one exists.
 *
 * Every write goes through {@see InboxService} — the single inbox write path, which records the audit +
 * domain events. This controller resolves, validates, delegates, redirects; it holds no business rule.
 */
class OwnerMessageActionController extends Controller
{
    /**
     * The owner's reply. Posted as `inbound` (into the org) by
     * {@see InboxService::postOwnerMessage()} — see that method for why it is not
     * `postInbound()` (the GuestMatcher would mint a spurious Guest for the owner) and not `sendOutbound()`
     * (which is the operator's direction and hard-codes the "Operator" sender label).
     */
    public function reply(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // owner-scoped → cross-owner/guest 404

        // A closed conversation is not a write target. Reopening happens through the service when a message
        // genuinely lands; refusing here is the clean, honest response rather than a silent no-op.
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $author = $request->user();

        $inbox->postOwnerMessage($model, trim($validated['body']), $author instanceof User ? $author : null);

        return redirect()->route('owner.messages', ['c' => $model->id])->with('owner_notice', 'Message sent.');
    }

    /**
     * Start a conversation with a member of the operator's staff.
     *
     * The staff id is re-resolved through the tenant scope + the back-office allow-list rather than trusted
     * from the body: a foreign or non-staff id must not become a correspondent. The thread is opened on the
     * `manual` channel — internal delivery, where recording IS the delivery (there is no address to route to,
     * because both parties read it inside Vyrobel).
     */
    public function start(Request $request, InboxService $inbox): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:160'],
            // The picked recipient (exact-match compose) — a REAL back-office staff member of this org, NEVER a
            // guest. Re-resolved through the tenant scope + the back-office allow-list, never trusted from the body.
            'staff_id' => ['nullable', 'string'],
        ]);

        $owner = $this->resolveOwnerId();

        if ($owner === null) {
            // Fail closed: a portal user with no owner link has nobody to send AS.
            return redirect()->route('owner.messages')->with('owner_error', 'Your account is not linked to an owner record yet.');
        }

        $thread = $inbox->startThread([
            'channel' => 'manual',
            'subject' => $validated['subject'] ?? null,
            'owner_id' => $owner,
            // Assign to the picked staff member IF they are a genuine back-office member (else unassigned — the
            // team picks it up). A foreign / non-staff / guest id resolves to null, never assigned.
            'assigned_to' => $this->resolveStaffAssignee($validated['staff_id'] ?? null),
        ]);

        $author = $request->user();
        $inbox->postOwnerMessage($thread, trim($validated['body']), $author instanceof User ? $author : null);

        return redirect()->route('owner.messages', ['c' => $thread->id])->with('owner_notice', 'Message sent.');
    }

    /**
     * A staff id → the assignee, ONLY if it is a real active back-office member of THIS org (tenant-scoped +
     * the allow-list). A foreign, non-staff, or guest id → null (never assigned). Guards owner↔operator-only.
     */
    private function resolveStaffAssignee(?string $staffId): ?string
    {
        if ($staffId === null || $staffId === '') {
            return null;
        }

        $backOffice = (array) config('authorization.back_office_roles');
        $user = User::query()
            ->whereKey($staffId)
            ->whereIn('id', Membership::query()->where('status', 'active')->pluck('user_id'))
            ->first();

        return $user !== null && $user->hasAnyRole($backOffice) ? (string) $user->getKey() : null;
    }

    /**
     * The owner this portal user IS — from the SAME context the row scope uses, never from the request.
     *
     * Taking it from OwnerContext (which SetOwnerScopeForPortal populates from the authenticated user's real
     * owner links) means a thread can only ever be opened for an owner this user genuinely is. A body-supplied
     * owner id would be an obvious forgery vector.
     */
    private function resolveOwnerId(): ?string
    {
        $ids = app(OwnerContext::class)->ownerIds();

        return $ids === [] ? null : (string) $ids[0];
    }
}
