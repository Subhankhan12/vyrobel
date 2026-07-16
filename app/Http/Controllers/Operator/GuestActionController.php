<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Tag;
use App\Services\GuestService;
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

        // An anonymized guest must not be re-populated with PII: the row is kept ONLY so the financial
        // history that references it stays intact, and writing a name back would quietly un-erase them.
        if ($model->isAnonymized()) {
            return $this->back($model->id, 'guest_error', 'That guest has been anonymized — their details cannot be edited.');
        }

        $validated = $request->validate([
            // Nullable because they genuinely are: an OTA import may carry no name, and Guest::anonymize()
            // nulls them. `max` matches the real string columns.
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'is_vip' => ['sometimes', 'boolean'],
            'risk_flag' => ['sometimes', 'boolean'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        // An unchecked checkbox posts NOTHING, so a missing key means false — not "leave as-is". Without
        // this, a toggle could be switched on but never off: the classic checkbox bug, and on `risk_flag`
        // it would mean a flag an operator cannot clear.
        foreach (['is_vip', 'risk_flag', 'marketing_consent'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        $guests->updateProfile($model, $validated, $request->user());

        return $this->back($model->id, 'guest_notice', 'Profile updated.');
    }

    /** Add an internal note — the first real writer of the shared polymorphic `notes` table. */
    public function addNote(Request $request, GuestService $guests, string $guest): RedirectResponse
    {
        $model = Guest::query()->findOrFail($guest);

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

        // Re-resolved through the tenant scope rather than trusted from the body — a foreign tag id 404s.
        $tag = Tag::query()->findOrFail((string) $request->input('tag'));

        $guests->detachTag($model, $tag, $request->user());

        return $this->back($model->id, 'guest_notice', 'Tag removed.');
    }

    /** PRG back to the profile, using the two flash keys that view renders. */
    private function back(string $guestId, string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.guests.show', $guestId)->with($key, $message);
    }
}
