<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerExpenseService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 1 (Owner Archive) — the owner's FINALIZED statement history. Locks its
// PRESENTATION guarantees: the real finalized/paid rows render with their REAL per-owner sequence_no +
// net; a DRAFT is NOT archive and never appears; the lifetime total reconciles TO THE CENT against
// Σ net_payable_minor of the finalized+paid rows; the statement + PDF links are the real owner-scoped
// routes; owner isolation holds (another owner's net invisible, a non-linked user fails closed); and
// the design's fabricated counts / span / retention promise / reference format are NOT ported.
// (seedScenario/book from tests/Support/booking_helpers.php; postRoom from owner_helpers.php.)

/** A linked owner (user + membership + Owner + one owner-wide agreement) owning the given scenario units. */
function archiveOwner(array $s, array $unitIndexes, string $name): array
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    foreach ($unitIndexes as $i) {
        $s['units'][$i]->update(['owner_id' => $owner->id]);
    }

    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
    ]);

    return compact('user', 'owner');
}

/** A real stay on one of the owner's units, through the real book → post → attribute path. */
function archiveStay(array $s, int $unitIndex, int $roomMinor): void
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
}

/**
 * Owner A: TWO finalized statements — September (#1, PAID via the manual rail) and October (#2, still
 * `finalized`, built from a post-lock straggler carried forward) — PLUS a November DRAFT (a post-lock
 * expense). Owner B: one finalized statement with a distinctive net. All via the certified services.
 */
function archiveScenario(): array
{
    $s = seedScenario(3);

    $a = archiveOwner($s, [0, 1], 'Amara Okafor');
    $b = archiveOwner($s, [2], 'Bruno Sala');

    $svc = app(OwnerStatementService::class);

    // ── Owner A · September: a real €200.00 stay → net €170.00 after the 15% commission.
    archiveStay($s, 0, 20000);
    $sept = $svc->finalize($svc->generateDraft($a['owner']->id, '2026-09-01', '2026-09-30'));

    // Pay it: OwnerPayoutService::pay() flips the STATEMENT to `paid` — the archive's other real status.
    $payoutSvc = app(OwnerPayoutService::class);
    $septPayout = $payoutSvc->createFromStatement($sept);
    expect($septPayout)->not->toBeNull();
    $payoutSvc->pay($septPayout);   // manual rail (the real default path)

    // ── Owner A · October: a stay attributed AFTER the September lock → prior-period carry-forward.
    archiveStay($s, 1, 88800);
    $oct = $svc->finalize($svc->generateDraft($a['owner']->id, '2026-10-01', '2026-10-31'));

    // ── Owner A · November: a post-lock expense → an open DRAFT statement. A draft is NEVER archive.
    app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 4321, 'currency' => 'EUR', 'expense_date' => '2026-09-05',
        'approve_now' => true,
    ]);
    $draft = $svc->generateDraft($a['owner']->id, '2026-11-01', '2026-11-30');

    // ── Owner B: a distinctive €500.00 stay → net €425.00.
    archiveStay($s, 2, 50000);
    $bStatement = $svc->finalize($svc->generateDraft($b['owner']->id, '2026-09-01', '2026-09-30'));

    return compact('s', 'a', 'b', 'sept', 'oct', 'draft', 'bStatement');
}

// ── 1. Real finalized rows + real statement numbers + lifetime reconciles TO THE CENT ────────────
it('renders the real finalized archive with real statement numbers and reconciles the lifetime total to the cent', function () {
    $x = archiveScenario();

    // The figure the page shows MUST equal Σ net_payable_minor of the REAL finalized+paid source rows.
    $lifetimeMinor = (int) OwnerStatement::query()
        ->where('owner_id', $x['a']['owner']->id)
        ->whereIn('status', ['finalized', 'paid'])
        ->sum('net_payable_minor');

    expect($lifetimeMinor)->toBe(92480)                                   // 17000 (Sept) + 75480 (Oct)
        ->and((int) $x['sept']->fresh()->net_payable_minor)->toBe(17000)
        ->and((int) $x['oct']->fresh()->net_payable_minor)->toBe(75480)
        ->and($x['sept']->fresh()->sequence_no)->toBe(1)                  // the REAL per-owner, gap-free number
        ->and($x['oct']->fresh()->sequence_no)->toBe(2)
        ->and($x['sept']->fresh()->status)->toBe('paid')                  // both REAL archive statuses present
        ->and($x['oct']->fresh()->status)->toBe('finalized');

    $this->actingAs($x['a']['user'])->get('/owner/archive')
        ->assertOk()
        ->assertSee('Statements archive')
        // REAL rows — the real statement number, the real period, the real net
        ->assertSee('No. 1')
        ->assertSee('No. 2')
        ->assertSee('September 2026')
        ->assertSee('October 2026')
        ->assertSee('€170.00')
        ->assertSee('€754.80')
        // REAL statuses — the enum's only two archive values (paid / finalized), never an invented one
        ->assertSee('Paid')
        ->assertSee('Payout pending')
        // DERIVED lifetime — reconciles to the cent against the DB sum above
        ->assertSee(MoneyFormatter::display($lifetimeMinor, 'EUR'))
        ->assertSee('data-countup="'.$lifetimeMinor.'"', false)
        // DERIVED counts + span — from the real rows, never the design's static figures
        ->assertSee('1 year')
        ->assertSee('2 statements')
        ->assertSee('since September 2026')
        // REAL links — the statement screen + the EXISTING owner-scoped statement PDF route
        ->assertSee(route('owner.statements.show', $x['sept']->id))
        ->assertSee(route('owner.statements.pdf', $x['sept']->id))
        ->assertSee(route('owner.statements.show', $x['oct']->id))
        ->assertSee(route('owner.statements.pdf', $x['oct']->id))
        // the full-archive ZIP is still a disabled affordance (no builder); the per-year annual tax
        // summary is now a REAL export link (owner.tax.annual), the same document the Tax Center offers
        ->assertSee('Download ZIP')
        ->assertSee(route('owner.tax.annual', 2026));
});

// ── 2. A DRAFT is NOT archive — it must never appear ─────────────────────────────────────────────
it('excludes a DRAFT statement — the archive is finalized history only', function () {
    $x = archiveScenario();
    $draft = $x['draft'];
    $draftDisplay = MoneyFormatter::display((int) $draft->net_payable_minor, (string) $draft->currency);

    // A real, open draft exists for this owner: no sequence number, and a distinctive figure of its own.
    expect($draft->status)->toBe('draft')
        ->and($draft->sequence_no)->toBeNull()
        ->and((int) $draft->net_payable_minor)->not->toBe(0)   // …so the assertDontSee below is meaningful
        ->and($draftDisplay)->not->toBe('€170.00')
        ->and($draftDisplay)->not->toBe('€754.80')
        ->and($draftDisplay)->not->toBe('€924.80');

    $this->actingAs($x['a']['user'])->get('/owner/archive')
        ->assertOk()
        ->assertSee('€170.00')            // the finalized rows are here…
        ->assertSee('€754.80')
        ->assertDontSee($draftDisplay)    // …but the draft's distinctive net is NOT
        ->assertDontSee('November 2026')  // nor its period
        ->assertDontSee('Draft');         // nor a draft badge — the archive has no such status

    // The archive total ignores the draft: Σ over EVERY status ≠ Σ over the archive.
    $everyStatus = (int) OwnerStatement::query()->where('owner_id', $x['a']['owner']->id)->sum('net_payable_minor');
    expect($everyStatus)->toBe(92480 + (int) $draft->net_payable_minor)
        ->and($everyStatus)->not->toBe(92480);
});

// ── 3. Owner isolation — A sees only A's archive; a non-linked user fails closed ─────────────────
it('scopes the archive to the owner, hides other owners, and fails closed for a non-linked user', function () {
    $x = archiveScenario();

    expect((int) $x['bStatement']->fresh()->net_payable_minor)->toBe(42500);   // B's distinctive net

    $this->actingAs($x['a']['user'])->get('/owner/archive')
        ->assertOk()
        ->assertSee('€170.00')
        ->assertSee('€924.80')       // A's lifetime
        ->assertDontSee('€425.00');  // ⚠️ owner B's net must never leak

    $this->actingAs($x['b']['user'])->get('/owner/archive')
        ->assertOk()
        ->assertSee('€425.00')
        ->assertDontSee('€170.00')
        ->assertDontSee('€754.80')
        ->assertDontSee('€924.80');

    // A non-linked authenticated member resolves to an EMPTY owner scope → nothing, never all owners.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $x['s']['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    // ⚠️ R-GATE (founder, 2026-09-19): this asserted the portal RENDERS EMPTY for a non-owner — the
    //    behaviour F-23 recorded as the defect. The refusal is now the intended answer: a non-owner is
    //    turned away at the realm door instead of being shown an owner's portal that is not theirs.
    $this->actingAs($stranger)->get('/owner/archive')->assertForbidden();
});

// ── 4. The design's fabrications are NOT ported (honesty rule · invariant #19) ────────────────────
it('does not port the design\'s fabricated counts, span, retention promise or reference format', function () {
    $x = archiveScenario();

    $this->actingAs($x['a']['user'])->get('/owner/archive')
        ->assertOk()
        // the design hard-codes "3 years · 34 documents" and "since June 2023" — static fiction
        ->assertDontSee('34 documents')
        ->assertDontSee('3 years')
        ->assertDontSee('June 2023')
        // no fabricated retention policy, no fabricated reference format (the real one is sequence_no)
        ->assertDontSee('kept for 7 years')
        ->assertDontSee('OS-2025-09')
        // The design's static "Owner Annual Tax Summary" HEADING is not ported. ⚠️ This comment used to say
        // the real PDF "states none" — stale since the tax engine (#73): the document is real and the page
        // links it per year as "Tax summary" (CAP_EXPORTS-gated). What stays unported is the design's
        // fabricated per-year FIGURE, not the document.
        ->assertDontSee('Owner Annual Tax Summary')
        ->assertDontSee('href="#"', false)
        // …the REAL derived values are shown instead
        ->assertSee('1 year')
        ->assertSee('2 statements')
        ->assertSee('since September 2026')
        ->assertSee('No. 1');
});

// ── No unrendered Blade leaks to the owner ───────────────────────────────────────────────────────
// See assertNoRawBlade(): a directive whose `@` follows a word character ships raw source to the page
// as ordinary text, invisible to assertSee/assertDontSee. Real bug found in the browser on the
// Expenses screen (Wave 1 · Sub-gate 1); guarded here too.
it('renders no raw Blade directives', function () {
    $x = archiveScenario();

    assertNoRawBlade($this->actingAs($x['a']['user'])->get('/owner/archive')->assertOk());
});
