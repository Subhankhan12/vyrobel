<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Membership;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Inbox\MessageAttachments;
use App\Support\Owner\OwnerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
    /** Attachment limits — mirror the Add-Unit upload posture (generated ULID name, capped size + count). */
    private const ATTACH_MAX_KB = 10240;   // 10 MB per file

    private const ATTACH_MAX = 5;

    /** ⚠️ The PRIVATE disk since 2026-09-30 (DEC-m) — attachments open only through the signed-in routes. */
    private const ATTACH_DISK = MessageAttachments::DISK;

    /**
     * The owner's reply. Posted as `inbound` (into the org) by
     * {@see InboxService::postOwnerMessage()} — see that method for why it is not
     * `postInbound()` (the GuestMatcher would mint a spurious Guest for the owner) and not `sendOutbound()`
     * (which is the operator's direction and hard-codes the "Operator" sender label).
     *
     * GATE 2: may QUOTE an earlier message (`reply_to_message_id`, re-validated to belong to THIS thread) and
     * carry ATTACHMENTS (stored as polymorphic Media on the created message, the Add-Unit upload pattern).
     */
    public function reply(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // owner-scoped → cross-owner/guest 404

        $validated = $request->validate([
            // Body OR at least one attachment — a message can be a lone file. Guarded by the after-hook below.
            'body' => ['nullable', 'string', 'max:5000'],
            // The quoted message must be a REAL message on THIS thread (never a cross-thread/owner id).
            'reply_to_message_id' => ['nullable', 'string', Rule::exists('messages', 'id')->where('thread_id', $model->getKey())],
            'attachments' => ['nullable', 'array', 'max:'.self::ATTACH_MAX],
            'attachments.*' => ['file', 'max:'.self::ATTACH_MAX_KB, 'mimes:jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt'],
        ]);

        $files = $request->file('attachments', []);
        $body = trim((string) ($validated['body'] ?? ''));
        if ($body === '' && $files === []) {
            return back()->with('owner_error', 'Type a message or attach a file.');
        }

        $author = $request->user();

        $message = $inbox->postOwnerMessage(
            $model,
            $body,
            $author instanceof User ? $author : null,
            $validated['reply_to_message_id'] ?? null,
        );
        $this->storeAttachments($message, $files);

        return redirect()->route('owner.messages', ['c' => $model->id])->with('owner_notice', 'Message sent.');
    }

    /** Toggle an emoji reaction on a message in the owner's thread (GATE 2). Owner-scoped + allow-list. */
    public function react(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // owner-scoped → cross-owner/guest 404

        $validated = $request->validate([
            'message_id' => ['required', 'string', Rule::exists('messages', 'id')->where('thread_id', $model->getKey())],
            'emoji' => ['required', 'string', Rule::in(InboxService::REACTIONS)],
        ]);

        $message = Message::query()->where('thread_id', $model->getKey())->findOrFail($validated['message_id']);
        $author = $request->user();
        if ($author instanceof User) {
            $inbox->toggleReaction($message, $author, $validated['emoji']);
        }

        return redirect()->route('owner.messages', ['c' => $model->id]);
    }

    /** Toggle the current user's star (private bookmark) on a message in the owner's thread (GATE 2). */
    public function star(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // owner-scoped → cross-owner/guest 404

        $validated = $request->validate([
            'message_id' => ['required', 'string', Rule::exists('messages', 'id')->where('thread_id', $model->getKey())],
        ]);

        $message = Message::query()->where('thread_id', $model->getKey())->findOrFail($validated['message_id']);
        $author = $request->user();
        if ($author instanceof User) {
            $inbox->toggleStar($message, $author);
        }

        return redirect()->route('owner.messages', ['c' => $model->id]);
    }

    /** Pin / unpin the one highlighted message of the owner's thread (GATE 2). */
    public function pin(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // owner-scoped → cross-owner/guest 404

        $validated = $request->validate([
            'message_id' => ['required', 'string', Rule::exists('messages', 'id')->where('thread_id', $model->getKey())],
        ]);

        $inbox->setPin($model, $validated['message_id']);

        return redirect()->route('owner.messages', ['c' => $model->id]);
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
            'attachments' => ['nullable', 'array', 'max:'.self::ATTACH_MAX],
            'attachments.*' => ['file', 'max:'.self::ATTACH_MAX_KB, 'mimes:jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt'],
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
        $message = $inbox->postOwnerMessage($thread, trim($validated['body']), $author instanceof User ? $author : null);
        $this->storeAttachments($message, $request->file('attachments', []));

        return redirect()->route('owner.messages', ['c' => $thread->id])->with('owner_notice', 'Message sent.');
    }

    /**
     * Store each uploaded file as a polymorphic `media` row on the message (GATE 2) — the Add-Unit upload
     * security posture: a GENERATED ULID filename (never the client's → no path traversal), the extension
     * from the validated file — on the PRIVATE disk since 2026-09-30 (DEC-m: only the people in that conversation, signed
     * in, may open one — {@see MessageAttachments}). organization_id is auto-filled by BelongsToOrganization.
     *
     * @param  array<int, UploadedFile|null>  $files
     */
    private function storeAttachments(Message $message, array $files): void
    {
        $order = 0;
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $ext = $file->extension() ?: 'bin';
            $name = (string) Str::ulid().'.'.$ext;
            $path = $file->storeAs('message-attachments/'.$message->getKey(), $name, self::ATTACH_DISK);

            Media::create([
                'mediable_type' => $message->getMorphClass(),
                'mediable_id' => $message->getKey(),
                'collection' => 'attachments',
                'disk' => self::ATTACH_DISK,
                'path' => $path,
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'sort_order' => $order,
                'is_cover' => false,
                'alt' => (string) $file->getClientOriginalName(),
            ]);
            $order++;
        }
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
