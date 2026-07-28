<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerExpenseService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 4 (Owner Documents) — the owner's document library. This screen is an AGGREGATION
// over PDFs that are rendered on demand, not a document store, so its one load-bearing guarantee is:
// EVERY LISTED ROW ACTUALLY GENERATES. These tests lock that (each listed link is followed and must
// return real %PDF bytes), plus: the real sequence_no / real routes render; a DRAFT contributes NO
// document row; counts + spans are DERIVED, never the design's static figures; owner isolation holds
// (another owner's documents + links invisible, a non-linked user fails closed); no raw Blade leaks.
// (seedScenario/book from tests/Support/booking_helpers.php; postRoom/assertNoRawBlade from
// owner_helpers.php. Local helpers are named docs* so they never collide with a sibling test file.)

/** A linked owner (user + membership + Owner + one owner-wide agreement) owning the given scenario units. */
function docsOwner(array $s, array $unitIndexes, string $name): array
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
function docsStay(array $s, int $unitIndex, int $roomMinor): void
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
}

/**
 * Owner A: TWO finalized statements — September (#1, gross €200.00 → net €170.00, PAID via the manual
 * rail) and October (#2, net €754.80, payout still PENDING) — PLUS a November DRAFT (a post-lock
 * expense). Owner B: one finalized+paid statement with a distinctive net. All via the certified
 * services, so every document below is one the real generators can actually produce.
 */
function docsScenario(): array
{
    $s = seedScenario(3);

    $a = docsOwner($s, [0, 1], 'Amara Okafor');
    $b = docsOwner($s, [2], 'Bruno Sala');

    $svc = app(OwnerStatementService::class);
    $payoutSvc = app(OwnerPayoutService::class);

    // ── Owner A · September: a real €200.00 stay → gross €200.00, net €170.00 after the 15% commission.
    docsStay($s, 0, 20000);
    $sept = $svc->finalize($svc->generateDraft($a['owner']->id, '2026-09-01', '2026-09-30'));
    $septPayout = $payoutSvc->createFromStatement($sept);
    expect($septPayout)->not->toBeNull();
    $septPayout = $payoutSvc->pay($septPayout);   // manual rail (the real default path) → PAID

    // ── Owner A · October: a stay attributed AFTER the September lock → prior-period carry-forward.
    //    Its payout is left PENDING — the remittance advice renders at that status too.
    docsStay($s, 1, 88800);
    $oct = $svc->finalize($svc->generateDraft($a['owner']->id, '2026-10-01', '2026-10-31'));
    $octPayout = $payoutSvc->createFromStatement($oct);
    expect($octPayout)->not->toBeNull();

    // ── Owner A · November: a post-lock expense → an open DRAFT statement. A draft is NOT a document.
    app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 4321, 'currency' => 'EUR', 'expense_date' => '2026-09-05',
        'approve_now' => true,
    ]);
    $draft = $svc->generateDraft($a['owner']->id, '2026-11-01', '2026-11-30');

    // ── Owner B: a distinctive €500.00 stay → net €425.00, finalized + paid.
    docsStay($s, 2, 50000);
    $bStatement = $svc->finalize($svc->generateDraft($b['owner']->id, '2026-09-01', '2026-09-30'));
    $bPayout = $payoutSvc->createFromStatement($bStatement);
    expect($bPayout)->not->toBeNull();
    $bPayout = $payoutSvc->pay($bPayout);

    return compact('s', 'a', 'b', 'sept', 'oct', 'draft', 'septPayout', 'octPayout', 'bStatement', 'bPayout');
}

// ── 1. The three REAL documents, with the REAL routes + the REAL statement number ─────────────────
it('lists the statement PDF, booking summary and remittance advice with the real routes and real statement numbers', function () {
    $x = docsScenario();

    // The real source figures behind the rows the page must show.
    expect($x['sept']->fresh()->sequence_no)->toBe(1)                 // the REAL per-owner, gap-free number
        ->and($x['oct']->fresh()->sequence_no)->toBe(2)
        ->and((int) $x['sept']->fresh()->gross_minor)->toBe(20000)    // booking summary → gross, source currency
        ->and((int) $x['sept']->fresh()->net_payable_minor)->toBe(17000)
        ->and((int) $x['oct']->fresh()->net_payable_minor)->toBe(75480)
        ->and((int) $x['septPayout']->fresh()->amount_minor)->toBe(17000)
        ->and($x['septPayout']->fresh()->status)->toBe('paid')        // BOTH real payout states are present…
        ->and($x['octPayout']->fresh()->status)->toBe('pending');     // …and both must yield a remittance

    $this->actingAs($x['a']['user'])->get('/owner/documents')
        ->assertOk()
        ->assertSee('Documents')
        // the three REAL document types — nothing else is listed
        ->assertSee('Owner statement')
        ->assertSee('Booking summary')
        ->assertSee('Remittance advice')
        // the REAL statement numbers + the REAL periods
        ->assertSee('No. 1')
        ->assertSee('No. 2')
        ->assertSee('September 2026')
        ->assertSee('October 2026')
        // the REAL figures: net payable (payout currency) + gross (source currency)
        ->assertSee('€170.00')
        ->assertSee('€200.00')
        ->assertSee('€754.80')
        // the REAL payout states — a remittance is a real document at PENDING as well as PAID
        ->assertSee('Paid')
        ->assertSee('Payout pending')
        ->assertSee('Bank transfer')          // rail=manual, via the SHARED RailLabel map
        // the REAL owner-scoped routes — all three generators, for both finalized statements
        ->assertSee(route('owner.statements.pdf', $x['sept']->id))
        ->assertSee(route('owner.statements.bookings', $x['sept']->id))
        ->assertSee(route('owner.payouts.remittance', $x['septPayout']->id))
        ->assertSee(route('owner.statements.pdf', $x['oct']->id))
        ->assertSee(route('owner.statements.bookings', $x['oct']->id))
        ->assertSee(route('owner.payouts.remittance', $x['octPayout']->id));
});

// ── 2. THE POINT OF THE SCREEN — every listed link actually generates a PDF ───────────────────────
it('generates a real PDF for every link it lists', function () {
    $x = docsScenario();

    $links = [
        route('owner.statements.pdf', $x['sept']->id),
        route('owner.statements.bookings', $x['sept']->id),
        route('owner.payouts.remittance', $x['septPayout']->id),
        route('owner.statements.pdf', $x['oct']->id),
        route('owner.statements.bookings', $x['oct']->id),
        route('owner.payouts.remittance', $x['octPayout']->id),   // PENDING payout → still a real document
    ];

    // Every link the page lists must be present on it…
    $page = $this->actingAs($x['a']['user'])->get('/owner/documents')->assertOk();
    foreach ($links as $link) {
        $page->assertSee($link);
    }

    // …and every one of them must actually download real PDF bytes. A listed document that does not
    // generate would be the one lie this screen is able to tell.
    foreach ($links as $link) {
        $response = $this->actingAs($x['a']['user'])->get($link);

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        expect(str_starts_with((string) $response->getContent(), '%PDF'))->toBeTrue("Link did not generate a PDF: {$link}");
    }
});

// ── 3. A DRAFT is NOT a document — it contributes no row and no link ──────────────────────────────
it('excludes a DRAFT statement — a draft has no number, is not locked, and is not a document', function () {
    $x = docsScenario();
    $draft = $x['draft'];
    $draftDisplay = MoneyFormatter::display((int) $draft->net_payable_minor, (string) $draft->currency);

    // A real, open draft exists for this owner: no sequence number, and a distinctive figure of its own.
    expect($draft->status)->toBe('draft')
        ->and($draft->sequence_no)->toBeNull()
        ->and((int) $draft->net_payable_minor)->not->toBe(0)   // …so the assertDontSee below is meaningful
        ->and($draftDisplay)->not->toBe('€170.00')
        ->and($draftDisplay)->not->toBe('€200.00')
        ->and($draftDisplay)->not->toBe('€754.80');

    $this->actingAs($x['a']['user'])->get('/owner/documents')
        ->assertOk()
        ->assertSee('€170.00')                 // the finalized periods are here…
        ->assertSee('€754.80')
        ->assertDontSee($draftDisplay)         // …but the draft's distinctive net is NOT
        ->assertDontSee('November 2026')       // nor its period
        // …and crucially neither of its would-be document links is offered
        ->assertDontSee(route('owner.statements.pdf', $draft->id))
        ->assertDontSee(route('owner.statements.bookings', $draft->id));

    // The derived count counts the REAL documents only: 2 finalized periods × (statement + bookings +
    // one remittance each) = 6. The draft adds nothing.
    $this->actingAs($x['a']['user'])->get('/owner/documents')->assertOk()->assertSee('6 documents');
});

// ── 4. Counts + spans are DERIVED; the design's fabrications are NOT ported (invariant #19) ───────
it('derives its counts and span from real rows and ports none of the design\'s fabrications', function () {
    $x = docsScenario();

    $this->actingAs($x['a']['user'])->get('/owner/documents')
        ->assertOk()
        // the design hard-codes "3 years · 34 documents" and "since June 2023" — static fiction
        ->assertDontSee('34 documents')
        ->assertDontSee('3 years')
        ->assertDontSee('June 2023')
        // the design's invented reference formats — the real reference is the statement's sequence_no
        ->assertDontSee('OS-2025-09')
        ->assertDontSee('RA-2025-09')
        ->assertDontSee('BS-2025-09')
        ->assertDontSee('18 bookings')          // an invented booking count — none is claimed here
        // the design's fabricated "Owner Annual Tax Summary" figure is never claimed (the real PDF states none)
        ->assertDontSee('Owner Annual Tax Summary')
        ->assertDontSee('€98,710')
        ->assertDontSee('href="#"', false)
        // …the REAL derived values are shown instead
        ->assertSee('6 documents')
        ->assertSee('since September 2026')
        ->assertSee('2026')                     // the real year section, derived from the real periods
        // the full-archive ZIP is NOT ported (the wireframe has none) — dropped, not a dead "Soon"
        ->assertDontSee('Download ZIP')
        ->assertDontSee('Download everything')
        // the annual tax summary is a REAL export — the dark card links owner.tax.annual for the real year
        ->assertSee('annual tax summary')
        ->assertSee('All 12 months')
        ->assertSee(route('owner.tax.annual', 2026));

    // The annual card's "net X" is the REAL Σ net_source_minor for the year (the annual PDF's own basis),
    // reconciling to that PDF to the cent (#17) — never a fabricated total.
    $annualNet = (int) $x['sept']->fresh()->net_source_minor + (int) $x['oct']->fresh()->net_source_minor;
    $this->actingAs($x['a']['user'])->get('/owner/documents')->assertOk()
        ->assertSee(MoneyFormatter::display($annualNet, 'EUR'));
});

// ── 5. Owner isolation — A sees only A's documents; a non-linked user fails closed ────────────────
it('scopes documents to the owner, hides another owner\'s documents and links, and fails closed for a non-linked user', function () {
    $x = docsScenario();

    expect((int) $x['bStatement']->fresh()->net_payable_minor)->toBe(42500);   // B's distinctive net

    $this->actingAs($x['a']['user'])->get('/owner/documents')
        ->assertOk()
        ->assertSee('€170.00')
        ->assertDontSee('€425.00')                                              // ⚠️ owner B's net must never leak
        ->assertDontSee(route('owner.statements.pdf', $x['bStatement']->id))    // ⚠️ nor B's document links
        ->assertDontSee(route('owner.statements.bookings', $x['bStatement']->id))
        ->assertDontSee(route('owner.payouts.remittance', $x['bPayout']->id));

    $this->actingAs($x['b']['user'])->get('/owner/documents')
        ->assertOk()
        ->assertSee('€425.00')
        ->assertDontSee('€170.00')
        ->assertDontSee('€754.80')
        ->assertDontSee(route('owner.statements.pdf', $x['sept']->id));

    // A cross-owner document link is not merely hidden — the generator itself refuses it (fail-closed).
    $this->actingAs($x['b']['user'])->get(route('owner.statements.pdf', $x['sept']->id))->assertNotFound();
    $this->actingAs($x['b']['user'])->get(route('owner.payouts.remittance', $x['septPayout']->id))->assertNotFound();

    // A non-linked authenticated member resolves to an EMPTY owner scope → nothing, never all owners.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $x['s']['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/documents')
        ->assertOk()
        ->assertSee('No documents yet')
        ->assertDontSee('€170.00')
        ->assertDontSee('€425.00')
        ->assertDontSee('€754.80')
        ->assertDontSee('No. 1');
});

// ── No unrendered Blade leaks to the owner ───────────────────────────────────────────────────────
// See assertNoRawBlade(): a directive whose `@` follows a word character ships raw source to the page
// as ordinary text, invisible to assertSee/assertDontSee. Real bug found in the browser on the
// Expenses screen (Wave 1 · Sub-gate 1); guarded here too.
it('renders no raw Blade directives', function () {
    $x = docsScenario();

    assertNoRawBlade($this->actingAs($x['a']['user'])->get('/owner/documents')->assertOk());
});
