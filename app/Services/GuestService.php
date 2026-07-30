<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guest;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Guest CRM writes — the single path for editing a guest profile (Wave 2 · Sub-gate 5).
 *
 * WHY A SERVICE AND NOT PLAIN ELOQUENT IN A CONTROLLER. docs/WAVE2-INVENTORY.md §A9 proposed "plain
 * Eloquent + a controller"; that is wrong against this codebase's own conventions. CLAUDE.md requires every
 * meaningful state change to write BOTH an `audit_events` row (who did what) and a `domain_events` row, and
 * every existing writer records those INSIDE the service — no controller in the repo calls either recorder.
 * Editing a guest's contact details is exactly the change an operator will one day need to answer "who
 * changed this, and when?" about. So the writes live here, once, and the controller stays a thin adapter.
 *
 * ⚠️ THE AUDIT TRAIL MUST NOT BECOME A SECOND, UNENCRYPTED PII STORE. `guests.email`/`phone` are encrypted
 * casts; `audit_events.before`/`after` are NOT. Recording the old and new email would therefore copy the
 * plaintext into a table {@see Guest::anonymize()} does not clear — re-retaining the very PII an erase is
 * meant to destroy, and silently undoing the encryption-at-rest decision. {@see Guest::anonymize()} already
 * states the rule for itself ("never put the erased PII into the audit before/after"); it holds for every
 * write, not just the erase. So the audit records WHICH FIELDS CHANGED, never their values — enough to
 * answer "who touched this guest's email on Tuesday?", never enough to reconstruct it.
 *
 * TENANT-scoped by construction: every model here is BelongsToOrganization, and each method takes an
 * already-resolved model (the controller resolves it through the global scope, so a cross-org id 404s
 * before this service is reached).
 */
class GuestService
{
    /** The profile fields an operator may edit. Anything not named here cannot be written through this path. */
    public const EDITABLE = ['first_name', 'last_name', 'email', 'phone', 'city', 'nationality', 'prefers_email', 'is_vip', 'risk_flag', 'marketing_consent', 'preferences'];

    /** The subset whose VALUES must never reach the audit trail (encrypted at rest — see the class docblock). */
    public const SENSITIVE = ['email', 'phone'];

    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Create a new guest profile (#87 — the list's "Add guest"). Only {@see EDITABLE} keys are honoured; the
     * organization is auto-filled by BelongsToOrganization (never a request-supplied id). The email cast
     * encrypts + the saving() hook writes the blind index. Audited + domain-evented, like every write here.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $by = null): Guest
    {
        $attributes = array_intersect_key($attributes, array_flip(self::EDITABLE));
        if (array_key_exists('preferences', $attributes)) {
            $attributes['preferences'] = self::normalizePreferences($attributes['preferences']);
        }

        $guest = DB::transaction(fn (): Guest => Guest::query()->create($attributes));

        // WHICH fields were set — never the encrypted contact VALUES (audit/domain tables are plaintext and
        // anonymize() does not clear them).
        $fields = array_values(array_diff(array_keys($attributes), self::SENSITIVE));
        $this->domainEvents->record('guest.created', $guest, ['fields' => $fields]);
        $this->audit->record('guest.created', $guest, null, ['by' => (string) ($by?->getKey() ?? Auth::id() ?? '')]);

        return $guest;
    }

    /**
     * Keep only real {@see Guest::PREFERENCE_CATALOG} keys, in catalog order — a foreign key is dropped, so the
     * stored set can never drift from the catalog, and the order is stable (so a no-op edit compares equal).
     *
     * @param  mixed  $raw
     * @return list<string>
     */
    public static function normalizePreferences($raw): array
    {
        $selected = is_array($raw) ? $raw : [];
        $out = [];
        foreach (array_keys(Guest::PREFERENCE_CATALOG) as $key) {
            if (in_array($key, $selected, true) || ($selected[$key] ?? false)) {
                $out[] = $key;
            }
        }

        return $out;
    }

    /**
     * Update a guest's profile. Only {@see EDITABLE} keys are honoured; the caller has already validated
     * them. Returns the fresh guest.
     *
     * Encryption is the CAST's job, not this method's: assigning `email` writes ciphertext because
     * `Guest::casts()` declares `'email' => 'encrypted'`. There is deliberately no manual encrypt call —
     * one would double-encrypt and diverge from every other reader.
     *
     * A no-op edit writes NO events: re-submitting an unchanged form is not a state change, and an audit
     * trail that logs "changed nothing" is noise that makes the real entries harder to find.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateProfile(Guest $guest, array $attributes, ?User $by = null): Guest
    {
        $attributes = array_intersect_key($attributes, array_flip(self::EDITABLE));
        if (array_key_exists('preferences', $attributes)) {
            // Whitelist against the catalog, in catalog order — so a re-submitted unchanged set compares equal.
            $attributes['preferences'] = self::normalizePreferences($attributes['preferences']);
        }

        // Compare BEFORE writing, so the event payload reports what actually moved. Loose-typed compare via
        // the model's own attribute (already cast: a bool column returns bool, an encrypted column returns
        // plaintext) — so "same value re-submitted" is correctly seen as no change.
        $changed = [];
        foreach ($attributes as $field => $value) {
            if ($guest->getAttribute($field) !== $value) {
                $changed[] = $field;
            }
        }

        if ($changed === []) {
            return $guest;
        }

        DB::transaction(function () use ($guest, $attributes): void {
            $guest->fill($attributes)->save();
        });

        // WHICH fields changed — never the values. See the class docblock: `before`/`after` are plaintext
        // columns, and two of these fields are encrypted precisely so their values never sit in the clear.
        $this->domainEvents->record('guest.profile_updated', $guest, ['fields' => $changed]);
        $this->audit->record(
            'guest.profile_updated',
            $guest,
            null,
            ['fields' => $changed, 'by' => (string) ($by?->getKey() ?? Auth::id() ?? '')],
        );

        return $guest->fresh() ?? $guest;
    }

    /**
     * Attach a note to a guest — the FIRST writer of the shared polymorphic `notes` table (nothing in the
     * app had ever created one).
     *
     * ⚠️ A NOTE IS THE GUEST'S PII TOO. `notes.body` is plain text and an operator will write identifying
     * detail into it ("allergic to X", "partner's name is Y"). {@see Guest::anonymize()} redacts note bodies
     * for this reason — added WITH this writer, because until now the gap was unreachable (no notes existed
     * to leak). Shipping the writer without the erase would have opened a real GDPR hole in a store the app
     * already promises to clear.
     *
     * `visibility` is left at the column's own `internal` default: `shared` implies a guest-facing surface
     * that does not exist, and nothing reads the column yet.
     */
    public function addNote(Guest $guest, string $body, ?User $author = null): Note
    {
        $note = DB::transaction(fn (): Note => $guest->notes()->create([
            'body' => $body,
            // Nullable by design (`nullOnDelete`) — a note outlives the staff member who wrote it.
            'author_id' => $author?->getKey() ?? Auth::id(),
        ]));

        // The note BODY is deliberately absent from both payloads — same reason as the contact fields: these
        // tables are plaintext and are not cleared by anonymize().
        $this->domainEvents->record('guest.note_added', $guest, ['note_id' => (string) $note->getKey()]);
        $this->audit->record('guest.note_added', $note, null, ['guest_id' => (string) $guest->getKey()]);

        return $note;
    }

    /**
     * Tag a guest, creating the tenant's catalog entry on first use.
     *
     * WHY firstOrCreate AND NOT A PICK-LIST. `tags`/`taggables`/`HasTags` are real and have been since
     * Phase 1, but NOTHING has ever written a tag — so every tenant's catalog is empty and a pick-list would
     * offer nothing, forever. `tags` carries `unique(organization_id, name)` precisely so a name IS the
     * identity within a tenant, which makes create-on-first-use the safe, intended shape rather than a
     * duplicate factory. (The design's eight chips — "Sea view", "Crib" … — are a designer's guess hard-coded
     * in its script; docs/WAVE2-INVENTORY.md's call is to "make it the tenant's real tag catalog", so the
     * catalog grows from what operators actually type.)
     *
     * Idempotent: `taggables` has a composite primary key over (tag, taggable), so a double-attach would
     * violate it — syncWithoutDetaching is used rather than attach, and re-tagging is a silent no-op.
     */
    public function attachTag(Guest $guest, string $name, ?User $by = null): Tag
    {
        $name = trim($name);

        return DB::transaction(function () use ($guest, $name, $by): Tag {
            // Tenant-scoped by BelongsToOrganization: firstOrCreate matches within THIS org only, and the
            // trait auto-fills organization_id on create. Another tenant's identically-named tag is neither
            // found nor reused.
            $tag = Tag::query()->firstOrCreate(['name' => $name]);

            if (! $guest->tags()->whereKey($tag->getKey())->exists()) {
                $guest->tags()->syncWithoutDetaching([$tag->getKey()]);

                $this->domainEvents->record('guest.tagged', $guest, ['tag' => $name]);
                $this->audit->record('guest.tagged', $guest, null, [
                    'tag' => $name,
                    'by' => (string) ($by?->getKey() ?? Auth::id() ?? ''),
                ]);
            }

            return $tag;
        });
    }

    /**
     * Untag a guest. The catalog entry SURVIVES — it is the tenant's vocabulary, not this guest's property,
     * and deleting it would silently strip the tag from every other guest carrying it.
     */
    public function detachTag(Guest $guest, Tag $tag, ?User $by = null): void
    {
        if (! $guest->tags()->whereKey($tag->getKey())->exists()) {
            return;
        }

        DB::transaction(function () use ($guest, $tag): void {
            $guest->tags()->detach($tag->getKey());
        });

        $this->domainEvents->record('guest.untagged', $guest, ['tag' => (string) $tag->name]);
        $this->audit->record('guest.untagged', $guest, null, [
            'tag' => (string) $tag->name,
            'by' => (string) ($by?->getKey() ?? Auth::id() ?? ''),
        ]);
    }
}
