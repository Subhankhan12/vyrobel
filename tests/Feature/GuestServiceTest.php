<?php

use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\Guest;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\GuestService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// THE GUEST CRM WRITE PATH (Wave 2 · Sub-gate 5) — GuestService, the single path for editing a guest.
//
// What is actually load-bearing here, and therefore what these tests defend:
//   • ⚠️ THE AUDIT TRAIL MUST NOT BECOME A SECOND, UNENCRYPTED PII STORE. `guests.email`/`phone` are
//     encrypted casts; `audit_events.before`/`after` are plain JSON that Guest::anonymize() does not clear.
//     Recording the old/new email there would re-retain the exact PII an erase destroys — silently undoing
//     encryption-at-rest. Test 3 asserts the values are ABSENT and only the field NAMES are recorded.
//   • The contact edit PERSISTS, ENCRYPTED — the design's Save fired a toast and wrote nothing.
//   • Notes are the guest's PII too, and anonymize() now redacts them (test 8). That landed WITH this
//     writer: until now nothing in the app had ever created a note, so the gap was unreachable rather than
//     benign — shipping the writer without the erase would have opened a real hole.
//   • Tags create the tenant's catalog on first use and are tenant-isolated.

function svcGuest(array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => 'Ada', 'last_name' => 'Lovelace'], $extra));
}

// ── 1. THE CONTACT EDIT PERSISTS, AND THE COLUMN HOLDS CIPHERTEXT ────────────────────────────────
it('persists a contact edit and writes the encrypted columns as ciphertext', function () {
    $s = seedScenario(1);
    $guest = svcGuest(['email' => 'old@example.test', 'phone' => '+353860000001']);

    app(GuestService::class)->updateProfile($guest, [
        'first_name' => 'Ada',
        'last_name' => 'King',
        'email' => 'ada.king@example.test',
        'phone' => '+353860000002',
    ]);

    // It survives a fresh read from the DB — not a toast, not in-memory only.
    $fresh = Guest::query()->findOrFail($guest->id);
    expect($fresh->last_name)->toBe('King')
        ->and($fresh->email)->toBe('ada.king@example.test')
        ->and($fresh->phone)->toBe('+353860000002');

    // The CAST did the encrypting — the raw column is ciphertext, and no manual encrypt call exists in the
    // path (one would double-encrypt).
    $raw = (string) DB::table('guests')->where('id', $guest->id)->value('email');
    expect($raw)->not->toBe('ada.king@example.test')->and($raw)->not->toContain('ada.king');
});

// ── 2. Only EDITABLE fields are writable through this path ───────────────────────────────────────
it('ignores any field outside the editable allow-list', function () {
    $s = seedScenario(1);
    $guest = svcGuest();
    $foreignOrg = (string) $guest->organization_id;

    app(GuestService::class)->updateProfile($guest, [
        'first_name' => 'Ada',
        // Neither is in GuestService::EDITABLE — a mass-assignment attempt must not move them.
        'organization_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        'anonymized_at' => now(),
    ]);

    $fresh = Guest::query()->findOrFail($guest->id);
    expect((string) $fresh->organization_id)->toBe($foreignOrg)
        ->and($fresh->isAnonymized())->toBeFalse();
});

// ── 3. ⚠️ THE AUDIT RECORDS *WHICH* FIELDS CHANGED — NEVER THEIR VALUES ──────────────────────────
// The whole point of encrypting email at rest is that it never sits in the clear. An audit row carrying
// the old address would put it in the clear, in a table anonymize() never touches.
it('never writes guest contact values into the audit or domain event payloads', function () {
    $s = seedScenario(1);
    $guest = svcGuest(['email' => 'secret.old@example.test', 'phone' => '+353860000001']);

    app(GuestService::class)->updateProfile($guest, [
        'email' => 'secret.new@example.test',
        'phone' => '+353860000009',
    ]);

    $audit = AuditEvent::query()->where('action', 'guest.profile_updated')->firstOrFail();
    $domain = DomainEvent::query()->where('event_type', 'guest.profile_updated')->firstOrFail();

    // The whole serialized row — before, after, payload, everything.
    $auditBlob = json_encode($audit->toArray());
    $domainBlob = json_encode($domain->toArray());

    foreach (['secret.old@example.test', 'secret.new@example.test', '+353860000001', '+353860000009'] as $pii) {
        Assert::assertStringNotContainsString($pii, $auditBlob, "The audit trail leaked PII: {$pii}");
        Assert::assertStringNotContainsString($pii, $domainBlob, "The domain event leaked PII: {$pii}");
    }

    // It records enough to answer "who touched this guest's email?" — just never enough to reconstruct it.
    expect($audit->after['fields'])->toContain('email')->toContain('phone');
});

// ── 4. A NO-OP EDIT WRITES NO EVENTS — re-submitting a form is not a state change ────────────────
it('records nothing when the submitted values are unchanged', function () {
    $s = seedScenario(1);
    $guest = svcGuest(['email' => 'ada@example.test', 'is_vip' => true]);

    $before = AuditEvent::query()->count();

    app(GuestService::class)->updateProfile($guest, [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.test',
        'is_vip' => true,
    ]);

    // An audit trail that logs "changed nothing" is noise that buries the real entries.
    expect(AuditEvent::query()->count())->toBe($before);
});

// ── 5. THE FLAGS ARE REAL COLUMNS AND TOGGLE BOTH WAYS ───────────────────────────────────────────
it('toggles the real vip, risk and marketing columns on and off', function () {
    $s = seedScenario(1);
    $guest = svcGuest();
    $service = app(GuestService::class);

    $service->updateProfile($guest, ['is_vip' => true, 'risk_flag' => true, 'marketing_consent' => true]);
    $fresh = Guest::query()->findOrFail($guest->id);
    expect($fresh->is_vip)->toBeTrue()->and($fresh->risk_flag)->toBeTrue()->and($fresh->marketing_consent)->toBeTrue();

    // OFF must work too — an unchecked box posts nothing, so a flag an operator cannot clear is the classic
    // checkbox bug (the controller coerces the missing key to false; this proves the service honours it).
    $service->updateProfile($fresh, ['is_vip' => false, 'risk_flag' => false, 'marketing_consent' => false]);
    $fresh = Guest::query()->findOrFail($guest->id);
    expect($fresh->is_vip)->toBeFalse()->and($fresh->risk_flag)->toBeFalse()->and($fresh->marketing_consent)->toBeFalse();
});

// ── 6. NOTES — the first real writer of the shared polymorphic table ─────────────────────────────
it('adds a real note with its real author, and records the event without the body', function () {
    $s = seedScenario(1);
    $guest = svcGuest();
    $author = User::factory()->create(['name' => 'Marco Staff']);

    $note = app(GuestService::class)->addNote($guest, 'Allergic to shellfish — flag the kitchen.', $author);

    // A real row on the shared table, morph-linked to the guest.
    $stored = Note::query()->findOrFail($note->id);
    expect($stored->body)->toBe('Allergic to shellfish — flag the kitchen.')
        ->and((string) $stored->notable_id)->toBe((string) $guest->id)
        ->and((string) $stored->notable_type)->toBe('guest')     // the morph ALIAS, not the FQCN
        ->and((string) $stored->author_id)->toBe((string) $author->id)
        ->and((string) $stored->visibility)->toBe('internal');

    // 'note' had to be registered in the morph map for this to work at all: both recorders call
    // getMorphClass() on the subject and enforceMorphMap THROWS for an unregistered model.
    $audit = AuditEvent::query()->where('action', 'guest.note_added')->firstOrFail();
    expect((string) $audit->subject_type)->toBe('note');

    // The note body is PII-bearing free text — it must not be copied into the plaintext event tables.
    expect(json_encode($audit->toArray()))->not->toContain('shellfish');
});

// ── 7. TAGS — the catalog is created on first use, reused on the second, isolated per tenant ─────
it('creates the tenant tag catalog on first use and reuses it thereafter', function () {
    $s = seedScenario(1);
    $one = svcGuest();
    $two = svcGuest(['first_name' => 'Rex', 'last_name' => 'Rogue']);
    $service = app(GuestService::class);

    $first = $service->attachTag($one, 'Corporate');
    $second = $service->attachTag($two, 'Corporate');

    // ONE catalog entry, shared — `tags` carries unique(organization_id, name), so a name IS the identity
    // within a tenant. A second row would be a duplicate-factory bug.
    expect((string) $second->getKey())->toBe((string) $first->getKey())
        ->and(Tag::query()->where('name', 'Corporate')->count())->toBe(1);

    // Re-tagging is a silent no-op, not a composite-PK violation.
    $service->attachTag($one, 'Corporate');
    expect($one->tags()->count())->toBe(1);

    // Detaching leaves the CATALOG alone — it is the workspace's vocabulary, not this guest's property.
    // Deleting it would silently strip the tag from every other guest carrying it.
    $service->detachTag($one, $first);
    expect($one->fresh()->tags()->count())->toBe(0)
        ->and($two->fresh()->tags()->count())->toBe(1)
        ->and(Tag::query()->where('name', 'Corporate')->count())->toBe(1);
});

it('never reuses another organisation’s identically-named tag', function () {
    $foreign = seedScenario(1);
    $foreignTag = Tag::create(['name' => 'Corporate', 'color' => '#000000']);

    $home = seedScenario(1);
    app(TenantContext::class)->set($home['org']->id);
    $guest = svcGuest();

    $mine = app(GuestService::class)->attachTag($guest, 'Corporate');

    // Same name, different tenant, different row — firstOrCreate matches within the global scope only.
    expect((string) $mine->getKey())->not->toBe((string) $foreignTag->getKey())
        ->and((string) $mine->organization_id)->toBe((string) $home['org']->id);
});

// ── 8. ⚠️ GDPR — anonymize() REDACTS NOTES, because a note about a person is their PII ───────────
// This landed WITH the note writer. Until Sub-gate 5 nothing in the app had ever created a note, so the
// gap was unreachable; shipping the writer without this would have put PII in a store the app promises to
// clear — breaking CLAUDE.md's erase invariant with brand-new code.
it('redacts note bodies when a guest is anonymized, keeping the row', function () {
    $s = seedScenario(1);
    $guest = svcGuest(['email' => 'ada@example.test']);
    $author = User::factory()->create(['name' => 'Marco Staff']);

    $note = app(GuestService::class)->addNote($guest, 'Travelling with her partner Charles — room 4 is theirs.', $author);

    $guest->anonymize();

    $stored = Note::query()->findOrFail($note->id);
    // The identifying content is GONE…
    expect($stored->body)->toBe('')
        ->and($stored->body)->not->toContain('Charles');
    // …but the row, its author and its timestamp survive: never soft-delete-and-keep-PII, and the audit
    // skeleton stays intact. Same treatment anonymize() already gives inbox message bodies.
    expect((string) $stored->author_id)->toBe((string) $author->id)
        ->and($stored->created_at)->not->toBeNull()
        ->and(Note::query()->where('notable_id', $guest->id)->count())->toBe(1);
});

it('never touches another guest’s notes when one guest is anonymized', function () {
    $s = seedScenario(1);
    $erased = svcGuest();
    $kept = svcGuest(['first_name' => 'Rex', 'last_name' => 'Rogue']);
    $service = app(GuestService::class);

    $service->addNote($erased, 'Erase me.');
    $keptNote = $service->addNote($kept, 'Keep me — a different guest entirely.');

    $erased->anonymize();

    expect(Note::query()->findOrFail($keptNote->id)->body)->toBe('Keep me — a different guest entirely.');
});
