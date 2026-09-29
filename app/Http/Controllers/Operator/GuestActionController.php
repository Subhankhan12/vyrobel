<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Tag;
use App\Services\GuestService;
use App\Services\InboxService;
use App\Support\Guest\GuestListParams;
use App\Support\Operator\OperatorWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Guest CRM writes from the styled guest profile (Wave 2 · Sub-gate 5) — REAL, persisted, via the proven
 * POST → controller → service → redirect (PRG) pattern.
 *
 * A thin adapter, deliberately: every write goes through {@see GuestService}, the single path that records
 * the audit + domain events. This controller resolves, validates, delegates, redirects — it contains no
 * business rule of its own and writes no event (no controller in this codebase does; that is the service's
 * job).
 *
 * TENANT-scoped, three stacked facts: a normal POST re-runs the `web` group (so SetTenantForWebRequest sets
 * the context); `{guest}` is a plain STRING param resolved via the tenant-scoped global scope AFTER that
 * middleware (never route-model-bound — RMB resolves during SubstituteBindings, i.e. BEFORE the scope
 * middleware, and would leak); and `/app` carries EnsureBackOfficeAccess (invariant #26), so an owner-role
 * user never reaches any of it. A cross-org id 404s.
 *
 * ⚠️ THIS WRITES PII. The email/phone fields are encrypted casts — assigning them writes ciphertext, and no
 * manual encrypt call appears here or in the service (one would double-encrypt). Validation is `validate()`
 * rather than the hand-checked style of the sibling reservation controllers BECAUSE the profile view renders
 * the `$errors` bag: the rule in this codebase is "validate() only if your view renders $errors" — otherwise
 * a ValidationException redirects with an error bag nothing displays and the operator sees a silent no-op.
 * This view displays them, per-field, so a bad email address says so instead of vanishing.
 */
class GuestActionController extends Controller
{
    /**
     * Persist the profile edit — the design's "Save changes" button, which in the design fired a toast and
     * saved NOTHING (docs/WAVE2-INVENTORY.md: "No persistence, no validation, no request… A design-level
     * toast is a mock, not a backend"). This is the real write.
     *
     * The design had ONE "Guest name" input against the schema's TWO columns; splitting on the last space is
     * lossy for real names ("van Dijk", "de la Cruz"), so the form asks for both — decided, not guessed.
     */
    public function updateProfile(Request $request, GuestService $guests, string $guest): RedirectResponse
    {
        $model = Guest::query()->findOrFail($guest);   // tenant-scoped → cross-org 404

        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->done($request, $model->id, 'guest_error', $this->pausedMessage());
        }

        // An anonymized guest must not be re-populated with PII: the row is kept ONLY so the financial
        // history that references it stays intact, and writing a name back would quietly un-erase them.
        if ($model->isAnonymized()) {
            return $this->done($request, $model->id, 'guest_error', 'That guest has been anonymized — their details cannot be edited.');
        }

        $validated = $request->validate([
            // Nullable because they genuinely are: an OTA import may carry no name, and Guest::anonymize()
            // nulls them. `max` matches the real string columns.
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'size:2'],
            'is_vip' => ['sometimes', 'boolean'],
            'risk_flag' => ['sometimes', 'boolean'],
            'prefers_email' => ['sometimes', 'boolean'],
            'marketing_consent' => ['sometimes', 'boolean'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string'],
        ]);

        // An unchecked checkbox posts NOTHING, so a missing key means false — not "leave as-is". Without
        // this, a toggle could be switched on but never off: the classic checkbox bug, and on `risk_flag`
        // it would mean a flag an operator cannot clear.
        foreach (['is_vip', 'risk_flag', 'prefers_email', 'marketing_consent'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }
        // A form with no preference chips ticked posts no `preferences` key — read it as "clear them all".
        // ⚠️ EXCEPT from the Guests LIST (2026-09-29), whose Edit does not show the preferences at all and says so with
        //    `keep_preferences`. Without that marker, every edit from the list would silently wipe the guest's stay
        //    preferences — the profile's form carries every chip, so "none posted" means "none ticked" only there.
        if ($request->boolean('keep_preferences')) {
            unset($validated['preferences']);
        } else {
            $validated['preferences'] = (array) $request->input('preferences', []);
        }

        $guests->updateProfile($model, $validated, $request->user());

        return $this->done($request, $model->id, 'guest_notice', 'Profile updated.');
    }

    /**
     * Add a new guest (#87 — the list's "Add guest"). A real create through {@see GuestService::create}
     * (audited); the org is auto-filled, never a request id. Read-only respected. Redirects to the new profile.
     */
    public function store(Request $request, GuestService $guests): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return redirect()->route('app.guests')->with('guest_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'size:2'],
            'is_vip' => ['sometimes', 'boolean'],
            'prefers_email' => ['sometimes', 'boolean'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        foreach (['is_vip', 'prefers_email', 'marketing_consent'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        $guest = $guests->create($validated, $request->user());

        return redirect()->route('app.guests.show', $guest->id)->with('guest_notice', 'Guest added.');
    }

    /**
     * The profile "Message" button (#87) — open the real guest↔operator channel. Reuses the guest's latest
     * thread if one exists (a plain read → allowed even while paused); otherwise starts a manual thread via the
     * certified {@see InboxService} (a write → respects read-only). Redirects to the inbox conversation.
     */
    public function startThread(Request $request, InboxService $inbox, string $guest): RedirectResponse
    {
        $model = Guest::query()->findOrFail($guest);

        $existing = MessageThread::query()
            ->where('guest_id', $model->getKey())
            ->orderByDesc('last_message_at')->orderByDesc('id')->first();

        if ($existing !== null) {
            return redirect()->route('app.inbox.show', $existing->getKey());
        }

        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($model->id, 'guest_error', $this->pausedMessage());
        }
        if ($model->isAnonymized()) {
            return $this->back($model->id, 'guest_error', 'That guest has been anonymized — no new conversation can be started.');
        }

        $thread = $inbox->startThread([
            'channel' => 'manual',
            'guest_id' => (string) $model->getKey(),
            'subject' => 'Conversation',
            'assigned_to' => $request->user()?->getKey(),
        ]);

        return redirect()->route('app.inbox.show', $thread->getKey());
    }

    /** Add an internal note — the first real writer of the shared polymorphic `notes` table. */
    public function addNote(Request $request, GuestService $guests, string $guest): RedirectResponse
    {
        $model = Guest::query()->findOrFail($guest);

        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($model->id, 'guest_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $guests->addNote($model, trim($validated['body']), $request->user());

        return $this->back($model->id, 'guest_notice', 'Note added.');
    }

    /**
     * Tag a guest, creating the tenant's catalog entry on first use (see GuestService::attachTag() for why
     * a pick-list would be empty forever).
     */
    public function addTag(Request $request, GuestService $guests, string $guest): RedirectResponse
    {
        $model = Guest::query()->findOrFail($guest);

        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($model->id, 'guest_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            // `max:255` matches the real `tags.name` string column.
            'name' => ['required', 'string', 'max:255'],
        ]);

        $guests->attachTag($model, $validated['name'], $request->user());

        return $this->back($model->id, 'guest_notice', 'Tag added.');
    }

    /** Untag a guest. The tenant's catalog entry survives — it is the workspace's vocabulary, not this row's. */
    public function removeTag(Request $request, GuestService $guests, string $guest): RedirectResponse
    {
        $model = Guest::query()->findOrFail($guest);

        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($model->id, 'guest_error', $this->pausedMessage());
        }

        // Re-resolved through the tenant scope rather than trusted from the body — a foreign tag id 404s.
        $tag = Tag::query()->findOrFail((string) $request->input('tag'));

        $guests->detachTag($model, $tag, $request->user());

        return $this->back($model->id, 'guest_notice', 'Tag removed.');
    }

    /**
     * ⚠️ GDPR ERASURE — irreversible, permission-gated, server-confirmed.
     *
     * THE POINT OF THIS METHOD IS THAT IT IS SHORT. `Guest::anonymize()` already does the work — it nulls the
     * PII columns, HARD-deletes the ID documents AND their stored bytes, redacts the guest's inbox message
     * bodies + the thread's encrypted participant identity, and redacts staff NOTE bodies (Wave 2). This
     * controller adds authorization, intent and a redirect, and re-implements NONE of it: a second copy of
     * that list would drift and quietly miss a store, which for an erasure means keeping personal data you
     * told a data subject you had destroyed.
     *
     * Until 2026-07-17 `anonymize()` was reachable from NOWHERE — no route, no controller, no component. The
     * erase was correct and tested, and no operator could actually run it. That is why this exists.
     *
     * `can:guests.erase` on the route (Property Owner / Group Admin only — invariant #30): running the house
     * does not include erasing a person. #26 still refuses an owner before any of this is consulted.
     *
     * NOT ONE-CLICK, AND THE SERVER IS THE ONE THAT CHECKS. The view renders a typed confirmation; a
     * client-side `confirm()` is bypassable by anyone who can POST, so the typed word is validated HERE.
     * `validate()` is used because this view renders `$errors` (see updateProfile()'s note).
     */
    public function anonymize(Request $request, string $guest): RedirectResponse
    {
        $model = Guest::query()->findOrFail($guest);   // tenant-scoped → cross-org 404

        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->done($request, $model->id, 'guest_error', $this->pausedMessage());
        }

        // Idempotence with a clean flash, never a 500: re-erasing is harmless (the fields are already null)
        // but it would restamp `anonymized_at` and re-audit, implying a second erasure that did not happen.
        if ($model->isAnonymized()) {
            return $this->done($request, $model->id, 'guest_error', 'That guest has already been anonymized.');
        }

        $request->validate([
            // The literal word the UI asks for. Rejecting anything else is the intent check — an erasure is
            // permanent, so "did you mean it?" must survive a bookmarked POST, not just a dialog.
            'confirm' => ['required', 'string', 'in:ERASE'],
        ], [
            'confirm.required' => 'Type ERASE to confirm this permanent erasure.',
            'confirm.in' => 'Type ERASE exactly to confirm this permanent erasure.',
        ]);

        // The REAL erase — one call, the existing method, its own transaction + audit/domain events.
        $model->anonymize();

        return $this->done($request, $model->id, 'guest_notice', 'Guest erased. Their personal data is gone; the financial history remains.');
    }

    /** PRG back to the profile, using the two flash keys that view renders. */
    private function back(string $guestId, string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.guests.show', $guestId)->with($key, $message);
    }

    /**
     * Where an edit or an erase comes back to (2026-09-29). The Guests LIST edits and erases from a guest's own row
     * (`return=list`), and lands back on the list with the same filter, sort, page size and page — not on the profile.
     *
     * ⚠️ The way back is REBUILT by {@see GuestListParams::returnUrl()} from the list's own allow-listed settings, never taken
     *    from the request as a URL, so it can only ever be the Guests list — not an open redirect. Anything else in
     *    `return` is the profile's own behaviour.
     */
    private function done(Request $request, string $guestId, string $key, string $message): RedirectResponse
    {
        if ($request->input('return') === 'list') {
            return redirect()->to(GuestListParams::returnUrl($request->input('return_query')))->with($key, $message);
        }

        return $this->back($guestId, $key, $message);
    }

    /** The read-only refusal message — the workspace is paused (#84), so no guest write lands. */
    private function pausedMessage(): string
    {
        return 'Your workspace is paused (read-only) — changes are off until you resume. Resume from Billing.';
    }
}
