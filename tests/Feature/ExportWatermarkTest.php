<?php

use App\Models\ExportDownload;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// EXPORT WATERMARK + DOWNLOAD LOG (Wave B · GATE 5) — the Team security note's "financial exports are watermarked
// with who downloaded them" claim, made TRUE. Every financial export download is recorded (who / what / when) AND
// the downloader identity is stamped on the file; the primary owner reviews the log on the Team page.

/** A linked owner (portal user) with ONE finalized statement, for exercising the exports. */
function watermarkOwner(array $s): array
{
    $user = User::factory()->create(['name' => 'Wendy Watermark']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $unit = $s['units'][0];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return compact('user', 'owner', 'unit', 'statement');
}

// ── 1. A PDF export is RECORDED (who / what / when) — the audit row is written on download ─────────
it('records a download when the owner exports a statement PDF', function () {
    $s = seedScenario(1);
    $a = watermarkOwner($s);

    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}/pdf")
        ->assertOk()->assertHeader('content-type', 'application/pdf');

    $row = ExportDownload::query()->where('kind', 'statement_pdf')->firstOrFail();
    expect((string) $row->downloader_name)->toBe('Wendy Watermark')
        ->and((string) $row->user_id)->toBe((string) $a['user']->id)
        ->and((string) $row->owner_id)->toBe((string) $a['owner']->id)
        ->and((string) $row->subject_id)->toBe((string) $a['statement']->id);
});

// ── 2. A CSV export carries the downloader WATERMARK comment + records the download ────────────────
it('watermarks and records a CSV export', function () {
    $s = seedScenario(1);
    $a = watermarkOwner($s);

    $csv = $this->actingAs($a['user'])->get(route('owner.bookings.export'))
        ->assertOk()->streamedContent();

    // The very first line is the downloader watermark comment (who + when).
    expect($csv)->toContain('# Downloaded by Wendy Watermark');
    expect(ExportDownload::query()->where('kind', 'bookings_csv')->exists())->toBeTrue();
});

// ── 3. The Team page shows the REAL download log to the primary owner, owner-scoped ────────────────
it('shows the export download log on the Team page', function () {
    $s = seedScenario(1);
    $a = watermarkOwner($s);

    // Make a couple of real downloads.
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}/pdf")->assertOk();
    $this->actingAs($a['user'])->get(route('owner.bookings.export'))->assertOk();

    $this->actingAs($a['user'])->get('/owner/team')->assertOk()
        ->assertSee('Export download log')
        ->assertSee('watermarked with who downloaded them')
        ->assertSee('Wendy Watermark')
        ->assertSee('Bookings export');
});

// ── 4. Owner-scoping — one owner's log never shows another owner's downloads ───────────────────────
it('scopes the download log to the owner', function () {
    $s = seedScenario(2);
    $a = watermarkOwner($s);

    // A SECOND owner + a download of their own.
    $userB = User::factory()->create(['name' => 'Bruno Bystander']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $userB->id, 'status' => 'active']);
    $ownerB = Owner::factory()->create(['user_id' => $userB->id, 'payout_currency' => 'EUR']);
    $s['units'][1]->update(['owner_id' => $ownerB->id]);
    ExportDownload::create([
        'organization_id' => $s['org']->id, 'owner_id' => $ownerB->id, 'user_id' => $userB->id,
        'downloader_name' => 'Bruno Bystander', 'kind' => 'bookings_csv', 'label' => 'Bookings export',
    ]);

    // Owner A downloads their own, then views the log — sees only their own downloader, never B's.
    $this->actingAs($a['user'])->get(route('owner.bookings.export'))->assertOk();
    $this->actingAs($a['user'])->get('/owner/team')->assertOk()
        ->assertSee('Wendy Watermark')
        ->assertDontSee('Bruno Bystander');
});
