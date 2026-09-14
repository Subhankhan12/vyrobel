<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\OwnerUser;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Support\Pdf\OwnerStatementDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// DO24-1 — the owner's full statement archive as one ZIP.
//
// ⚠️ THIS IS BULK EGRESS, SO EVERY ASSERTION OPENS THE REAL BYTES. A 200 proves nothing about what a
// download contains: the whole risk here is that one request emits many documents, so a scoping slip stops
// being one wrong page and becomes a bulk leak. Tests 2 and 3 unzip the response and read the entries.
//
// ⚠️ The contents rule travels INTO the zip: each PDF is built by the same document builder and the same
// per-owner payout check the single-statement download uses, so an accountant's archive carries no IBAN.

/**
 * An owner with N finalized statements, plus the user linked to them. ⚠️ Named distinctly because
 * `OwnerArchiveViewTest` already declares an `archiveOwner()` — Pest helpers are global once their file
 * loads, so a duplicate name is a FATAL in the full suite while a single-file run passes happily. (Which is
 * why the suite is run whole, not just the file under test.)
 *
 * ⚠️ `$periods` exists because `sequence_no` RESTARTS PER OWNER: two owners' first statements for the same
 * month produce the byte-identical filename `vyrobel-statement-1-2026-09.pdf`. That is harmless in
 * production (an archive only ever holds one owner's documents) but it makes "B's file is absent from A's
 * zip" unprovable by name — so the second owner is given different periods and the isolation is asserted on
 * names that genuinely differ.
 */
function archiveZipOwner(array $s, int $unitIndex = 0, int $months = 2, array $periods = [['2026-09-01', '2026-09-30'], ['2026-10-01', '2026-10-31']]): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statements = [];
    // September carries the real activity; the extra period is an empty but genuinely finalized statement.
    foreach (array_slice($periods, 0, $months) as [$from, $to]) {
        $statements[] = $svc->finalize($svc->generateDraft($owner->id, $from, $to));
    }

    return compact('user', 'owner', 'unit', 'statements');
}

/**
 * Download the archive and OPEN it. Returns the zip's entry names → byte length.
 *
 * @return array<string, int>
 */
function openArchiveZip(TestResponse $response): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'vy-test-zip-');
    file_put_contents($tmp, $response->streamedContent());

    $zip = new ZipArchive;
    expect($zip->open($tmp))->toBeTrue();   // ⚠️ it is a REAL zip, not just bytes with a header

    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $entries[$stat['name']] = (int) $stat['size'];
    }
    $zip->close();
    @unlink($tmp);

    return $entries;
}

// ── 1. The archive downloads as a real ZIP, one PDF per archived statement ───────────────────────
it('bundles one PDF per archived statement', function () {
    $s = seedScenario(1);
    $a = archiveZipOwner($s, 0, 2);

    $response = $this->actingAs($a['user'])->get(route('owner.archive.zip'));
    $response->assertOk()->assertHeader('content-type', 'application/zip');

    $entries = openArchiveZip($response);

    // ⚠️ Exactly the statements the Archive page lists — same shared set.
    $archived = OwnerStatement::query()->archived()->get();
    expect($entries)->toHaveCount($archived->count())
        ->and($archived->count())->toBe(2);

    foreach ($entries as $name => $size) {
        expect($name)->toEndWith('.pdf')
            ->and($size)->toBeGreaterThan(1000);   // a real rendered document, not an empty placeholder
    }
});

// ── 2. ⚠️⚠️ THE LEAK BOUNDARY — another owner's statements are not in this archive ────────────────
it('never bundles another owner’s statements', function () {
    $s = seedScenario(2);
    $a = archiveZipOwner($s, 0, 2);
    // ⚠️ Different periods, so B's filenames genuinely differ from A's (see the fixture note).
    $b = archiveZipOwner($s, 1, 2, [['2026-11-01', '2026-11-30'], ['2026-12-01', '2026-12-31']]);

    $entries = openArchiveZip($this->actingAs($a['user'])->get(route('owner.archive.zip'))->assertOk());

    // ⚠️ Read the BYTES, not a status code: A's own statements are present …
    foreach ($a['statements'] as $statement) {
        expect($entries)->toHaveKey('vyrobel-statement-'.$statement->sequence_no.'-'.$statement->period_start->format('Y-m').'.pdf');
    }

    // … and B's are absent, by name AND by count.
    foreach ($b['statements'] as $statement) {
        expect($entries)->not->toHaveKey('vyrobel-statement-'.$statement->sequence_no.'-'.$statement->period_start->format('Y-m').'.pdf');
    }
    expect($entries)->toHaveCount(count($a['statements']));

    // Raw-query the ground truth: B genuinely has statements that simply did not travel.
    expect(DB::table('owner_statements')->where('owner_id', $b['owner']->id)->count())->toBe(2);
});

// ── 3. ⚠️ #28 TRAVELS INTO THE ZIP — an accountant's archive is unchanged by a payout account ─────
// ⚠️ THE FIRST VERSION OF THIS TEST WAS VACUOUS, AND THE MUTATION PROVED IT. It searched the zip bytes for
// the IBAN digits — but a PDF compresses its streams and the zip compresses again, so that needle can never
// match whether or not the number is there. Forcing `canSeePayout = true` passed it happily.
//
// The observable, deterministic form: hold EVERYTHING constant (same accountant, same statement, same
// watermark) and vary only whether a payout account exists. If the payout check is honoured, an account the
// accountant may not see cannot change a single byte of their archive. If it is not, the IBAN appears and
// the document grows.
it('gives an accountant the same archive whether or not a payout account exists', function () {
    $s = seedScenario(1);
    $a = archiveZipOwner($s, 0, 1);

    // An ACCOUNTANT may export (CAP_EXPORTS) but holds no CAP_PAYOUT.
    $accountant = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $accountant->id, 'status' => 'active']);
    OwnerUser::create([
        'organization_id' => $s['org']->id, 'owner_id' => $a['owner']->id,
        'user_id' => $accountant->id, 'role' => 'accountant', 'status' => 'active',
    ]);

    $withoutAccount = openArchiveZip($this->actingAs($accountant)->get(route('owner.archive.zip'))->assertOk());

    // The owner adds a real payout account — an IBAN now exists to leak.
    $this->actingAs($a['user'])->post(route('owner.payout-method'), [
        // ⚠️ FIXTURE ONLY — payout-destination re-auth. The ZIP-parity claim below is untouched.
        'current_password' => 'password',
        'label' => 'Main', 'account_holder' => 'Amara Okafor',
        'iban' => 'PT50000201231234567890154', 'currency' => 'EUR',
    ]);
    expect(DB::table('payout_methods')->whereNotNull('iban_last4')->count())->toBe(1);   // the fixture built its case

    $withAccount = openArchiveZip($this->actingAs($accountant)->get(route('owner.archive.zip'))->assertOk());

    // ⚠️ Byte-for-byte the same document: the account number never reached this role's bundle.
    expect($withAccount)->toBe($withoutAccount);

    // …and the rule it relies on is real: the same builder DOES carry the IBAN for a payout-capable role.
    $statement = OwnerStatement::query()->archived()->first();
    expect(OwnerStatementDocument::data($statement, true)['iban_masked'] ?? null)->not->toBeNull()
        ->and(OwnerStatementDocument::data($statement, false)['iban_masked'] ?? null)->toBeNull();
});

// ── 4. 🔒 THE GATE — a view-only sub-user cannot bundle what they cannot download ─────────────────
it('refuses the archive to a role without the exports capability', function () {
    $s = seedScenario(1);
    $a = archiveZipOwner($s, 0, 1);

    $viewer = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $viewer->id, 'status' => 'active']);
    OwnerUser::create([
        'organization_id' => $s['org']->id, 'owner_id' => $a['owner']->id,
        'user_id' => $viewer->id, 'role' => 'view_only', 'status' => 'active',
    ]);

    $this->actingAs($viewer)->get(route('owner.archive.zip'))->assertForbidden();

    // …and the page does not dangle the control in front of them either.
    $this->actingAs($viewer)->get(route('owner.archive'))
        ->assertOk()
        ->assertDontSee(route('owner.archive.zip'), false);
});

// ── 5. EMPTY — a clear message, not an empty file, not a crash ────────────────────────────────────
it('says there is nothing to archive rather than sending an empty zip', function () {
    $s = seedScenario(1);
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    $this->actingAs($user)->get(route('owner.archive.zip'))
        ->assertRedirect()
        ->assertSessionHas('archive_zip_notice');
});

// ── 6. ⚠️ READ-ONLY — building an archive changes nothing it read ─────────────────────────────────
it('mutates no statement while building the archive', function () {
    $s = seedScenario(1);
    $a = archiveZipOwner($s, 0, 2);

    $before = DB::table('owner_statements')->orderBy('id')->get()->toJson();
    $linesBefore = DB::table('owner_statement_lines')->orderBy('id')->get()->toJson();

    $this->actingAs($a['user'])->get(route('owner.archive.zip'))->assertOk()->streamedContent();

    expect(DB::table('owner_statements')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('owner_statement_lines')->orderBy('id')->get()->toJson())->toBe($linesBefore);
});

// ── 7. ⚠️ CLEANUP — the temp file goes with the response ─────────────────────────────────────────
it('leaves no temporary archive behind', function () {
    $s = seedScenario(1);
    $a = archiveZipOwner($s, 0, 1);

    $before = glob(sys_get_temp_dir().'/vy-archive-*') ?: [];

    $response = $this->actingAs($a['user'])->get(route('owner.archive.zip'))->assertOk();
    $response->streamedContent();   // sending the response is what triggers the delete

    $after = glob(sys_get_temp_dir().'/vy-archive-*') ?: [];

    expect($after)->toBe($before);
});

// ── 8. The download is audited, once, as one bundle ──────────────────────────────────────────────
it('records one export-audit row for the whole archive', function () {
    $s = seedScenario(1);
    $a = archiveZipOwner($s, 0, 2);

    $this->actingAs($a['user'])->get(route('owner.archive.zip'))->assertOk()->streamedContent();

    // Two PDFs, ONE audit row — the bundle is the artefact that left, not each page inside it.
    expect(DB::table('export_downloads')->where('kind', 'statement_archive_zip')->count())->toBe(1);
});

// ── 9. The bundled set is the SAME definition the page lists (§N.8f) ─────────────────────────────
it('bundles exactly the archived-status set', function () {
    expect(OwnerStatement::ARCHIVED_STATUSES)->toBe(['finalized', 'paid'])
        ->and(OwnerStatement::ARCHIVED_STATUSES)->not->toContain('draft');

    // Both readers go through the one scope rather than writing their own status list.
    expect(file_get_contents(app_path('Livewire/Owner/OwnerArchive.php')))->toContain('->archived()');
    expect(file_get_contents(app_path('Http/Controllers/Owner/OwnerArchiveZipController.php')))->toContain('->archived()');
});
