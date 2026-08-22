<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatementLine;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Screen 1 (Owner Statement). These lock the PRESENTATION guarantees of the styled
// OwnerStatementView: it renders LIVE, owner-scoped data; the breakdown is the real owner_statement_lines
// and reconciles to the cent; there is NO invented "payment / card-processing fee" line; deferred and
// no-backend regions are honestly stubbed (never faked); and owner isolation still holds (cross-owner 404,
// no figure leak). The backend projection itself is proven by OwnerStatementTest — this is the view.

/** A linked owner (user + membership + owner + agreement + booking + attribution + finalized statement). */
function statementOwner(array $s, int $unitIndex, int $roomMinor): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
    ]);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);

    return compact('user', 'owner', 'unit', 'statement', 'payout');
}

/**
 * A linked owner who owns TWO units, both attributed into ONE finalized statement — the fixture for the
 * by-unit split (Batch B · GATE 1). Returns the owner's user + the statement + the two units.
 *
 * @return array{user: User, owner: Owner, units: array<int, mixed>, statement: OwnerStatement}
 */
function twoUnitStatement(array $s): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
    ]);

    $units = [];
    foreach ([[0, 20000], [1, 15000]] as [$idx, $roomMinor]) {
        $unit = $s['units'][$idx];
        $unit->update(['owner_id' => $owner->id]);
        $units[] = $unit;
        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, $roomMinor);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    }

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return compact('user', 'owner', 'units', 'statement');
}

// ── 1. Renders real data + a to-the-cent breakdown + NO invented fee line ────────────────────────
it('renders the real statement with a to-the-cent breakdown and never an invented fee line', function () {
    $s = seedScenario(1);
    // room €200.00, 15% commission → −€30.00, net €170.00 (direct booking → no OTA cost).
    $a = statementOwner($s, 0, 20000);

    $response = $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}");

    $response->assertOk()
        // Real hero + real breakdown descriptions (labels come from OwnerStatementService).
        ->assertSee('Net payable')
        ->assertSee('€170.00')                       // net payable (hero + net line + payout card)
        ->assertSee('Gross room revenue')
        ->assertSee('+€200.00')                      // gross, signed
        ->assertSee('Management commission')
        ->assertSee('15% of gross room revenue')     // derived from the statement's OWN real figures
        ->assertSee('is-neg')                        // the commission amount rendered as a deduction
        // The count-up animates the REAL value, not a decorative one.
        ->assertSee('data-countup="17000"', false)
        // ⚠️ The design's invented card-processing line must NOT appear — the engine computes no such thing.
        ->assertDontSee('Payment & ops fees')
        ->assertDontSee('Card processing')
        ->assertDontSee('card-processing')
        // The occupancy card is now REAL (the "Not yet available" stub is gone — exact-match pass), and the
        // statement-history CSV/PDF exports are now REAL links (were disabled "Soon").
        ->assertDontSee('Not yet available')
        ->assertDontSee('Per-period occupancy trend')
        ->assertSee('Occupancy')                     // the real occupancy card (eyebrow)
        ->assertSee('Export CSV')                    // now a real export link…
        ->assertSee(route('owner.statements.history.csv'))   // …to the real owner-scoped history CSV route
        ->assertSee(route('owner.statements.history.pdf'))
        // The booking-summary + remittance PDFs are REAL exports (were "Soon" tags). This statement has a
        // matched payout, so the remittance links to that payout's real advice (not the "once paid" state).
        ->assertSee(route('owner.statements.bookings', $a['statement']->id))
        ->assertSee(route('owner.payouts.remittance', $a['payout']->id));

    // Reconciliation to the cent, at the presentation boundary: Σ component lines == net line == source net.
    $lines = OwnerStatementLine::query()->where('owner_statement_id', $a['statement']->id)->get();
    $components = (int) $lines->where('line_type', '!=', 'net')->sum('amount_minor');
    $net = (int) $lines->firstWhere('line_type', 'net')->amount_minor;

    expect($components)->toBe(17000)
        ->and($net)->toBe(17000)
        ->and((int) $a['statement']->net_source_minor)->toBe(17000);
});

// ── 1b. The remittance PDF is offered ONLY when a payout is matched — else an honest empty state ──
it('offers the remittance PDF only once a payout is matched, otherwise an honest "once paid"', function () {
    $s = seedScenario(1);
    $a = statementOwner($s, 0, 20000);

    // Remove the matched payout: there is no remittance to download yet, so the honest "Once paid" state
    // shows and NO remittance link is offered (a link would 404). The booking-summary PDF stays real.
    $a['payout']->delete();

    // ⚠️ The COPY changed at the Owner-2 design gate ("Once paid" → the fuller sentence below) when the
    // documents list was rebuilt to the wireframe's icon rows. What this test encodes is unchanged and
    // still load-bearing: the honest state is stated in words, and NO remittance link is offered (a link
    // would 404). Re-aimed at the new wording, not weakened — the assertDontSee is the real guard.
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}")
        ->assertOk()
        ->assertSee('Available once your payout is issued')
        ->assertDontSee(route('owner.payouts.remittance', $a['payout']->id))
        ->assertSee(route('owner.statements.bookings', $a['statement']->id));
});

// ── 2. Owner isolation holds — cross-owner 404 and no figure leak into the styled page ───────────
it('404s a cross-owner statement and never leaks another owner’s figures', function () {
    $s = seedScenario(2);
    $a = statementOwner($s, 0, 20000);   // A → net €170.00
    $b = statementOwner($s, 1, 50000);   // B → net €425.00 (distinctive)

    // A opens A's statement → 200; B's distinctive figure never appears (history/rail are owner-scoped).
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}")
        ->assertOk()
        ->assertSee('€170.00')
        ->assertDontSee('€425.00');

    // A opening B's statement → 404 (owner boundary, like a cross-tenant id).
    $this->actingAs($a['user'])->get("/owner/statements/{$b['statement']->id}")
        ->assertNotFound();
});

// ── 3. By-unit split — REAL per-unit net/gross/occupancy that RECONCILES to the statement net (#17) ──
it('splits the statement by unit and reconciles to the net to the cent (#17)', function () {
    $s = seedScenario(2);
    $x = twoUnitStatement($s);   // one owner, two units, both attributed into one finalized statement

    // #17 — the exact ledger lines this statement CLAIMED (statement_id), grouped by unit_id, sum to the
    // statement's source net to the cent. This is the anti-drift guarantee: same ledger, so it cannot drift.
    $entries = OwnerLedgerEntry::query()->where('statement_id', $x['statement']->id)->get();
    $perUnitNet = $entries->groupBy('unit_id')->map(
        fn ($g) => (int) $g->sum(fn ($e) => $e->signedMinor())
    );
    expect((int) $perUnitNet->sum())->toBe((int) $x['statement']->net_source_minor)
        ->and($entries->whereNull('unit_id')->count())->toBe(0);   // every demo line is unit-attributed

    // …and it RENDERS the real split: the section, each unit's real NET, and the reconciled total.
    $resp = $this->actingAs($x['user'])->get("/owner/statements/{$x['statement']->id}")
        ->assertOk()
        ->assertSee('By unit')
        ->assertSee('% of net')
        ->assertSee('Total net earnings')
        ->assertSee('occupancy');   // the real per-unit occupancy sub-label (the deferred stub is gone)

    foreach ($perUnitNet as $net) {
        $resp->assertSee(MoneyFormatter::display($net, 'EUR'));   // each real per-unit net
    }
    // the total net earnings display equals the statement's own net (reconciliation, on-screen)
    $resp->assertSee(MoneyFormatter::display((int) $x['statement']->net_source_minor, 'EUR'));
});

// ── 4. By-unit is owner-scoped (#28) — another owner's unit never appears ─────────────────────────
it('shows only the acting owner’s own units in the by-unit split (#28)', function () {
    $s = seedScenario(3);
    $x = twoUnitStatement($s);                      // owner A → units 0 + 1
    $b = statementOwner($s, 2, 50000);              // owner B → unit 2 (distinctive €425.00)
    $b['unit']->update(['name' => 'Zzz Secret Villa']);

    $this->actingAs($x['user'])->get("/owner/statements/{$x['statement']->id}")
        ->assertOk()
        ->assertSee('By unit')
        ->assertDontSee('Zzz Secret Villa')   // B's unit name never leaks into A's split
        ->assertDontSee('€425.00');           // nor B's figure
});

// ── 5. The occupancy card is REAL + the month switcher + gross sublabel render (exact-match pass) ──
it('renders the real occupancy card, the month switcher and the gross nights·units sublabel', function () {
    $s = seedScenario(1);
    $a = statementOwner($s, 0, 20000);

    $response = $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}");

    $response->assertOk()
        ->assertSee('Occupancy')          // the real card (the "Not yet available" stub is gone — asserted in #1)
        ->assertSee('Jump to month')      // the header month switcher popover
        ->assertSee('nights ·')           // the gross line's real "N nights · M units" sublabel
        ->assertSee('% occupancy');       // the by-unit real occupancy sublabel

    assertNoRawBlade($response);          // the word@directive trap — no raw Blade leaked (CLAUDE.md)
});

// ── 6. The masked payout IBAN shows ONLY to a payout-capable role (CAP_PAYOUT = co-owner) ──────────
it('shows the masked payout IBAN only to a payout-capable role (CAP_PAYOUT)', function () {
    $s = seedScenario(2);
    $a = statementOwner($s, 0, 20000);

    PayoutMethod::create([
        'organization_id' => $s['org']->id,
        'owner_id' => $a['owner']->id,
        'account_holder' => 'Demo Owner',
        'iban' => 'GB29NWBK60161331926819',
        'iban_last4' => '6819',
        'iban_country' => 'GB',
        'currency' => 'EUR',
        'is_default' => true,
    ]);

    // The PRIMARY owner (co-owner caps, incl. CAP_PAYOUT) sees the masked IBAN — never the full number.
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}")
        ->assertOk()
        ->assertSee('···· 6819')
        ->assertDontSee('GB29NWBK60161331926819');

    // An ACCOUNTANT (CAP_EXPORTS but NOT CAP_PAYOUT) and a VIEW-ONLY sub-user must NOT see the IBAN.
    foreach (['accountant', 'view_only'] as $role) {
        $sub = ownerSubUser($s, $a['owner']->id, $role);
        $this->actingAs($sub)->get("/owner/statements/{$a['statement']->id}")
            ->assertOk()
            ->assertDontSee('···· 6819');
    }
});

// ── 6b. The by-unit name links to Unit Detail ONLY for a CALENDAR-capable role (never a 403 invite) ──
it('links a by-unit name to Unit Detail only for a CALENDAR-capable role (#30 gate-the-control)', function () {
    $s = seedScenario(2);
    $a = statementOwner($s, 0, 20000);
    $unitShow = route('owner.units.show', $a['unit']->id);

    // The PRIMARY owner (co-owner, has CAP_CALENDAR) gets the real Unit-Detail link.
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}")
        ->assertOk()->assertSee($unitShow);

    // An ACCOUNTANT can VIEW the statement but lacks CAP_CALENDAR → the unit name is plain text, NOT a link that
    // would 403 (CLAUDE.md #30 "gate the control too, so the UI never invites a 403"). The by-unit section still renders.
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->get("/owner/statements/{$a['statement']->id}")
        ->assertOk()->assertSee('By unit')->assertDontSee($unitShow);
});

// ── 7. Statement-history CSV — owner-scoped, EXPORTS-gated, reconciling, NO guest data ─────────────
it('exports the statement-history CSV owner-scoped and EXPORTS-gated, never another owner’s figures', function () {
    $s = seedScenario(2);
    $a = statementOwner($s, 0, 20000);   // A → net €170.00
    statementOwner($s, 1, 50000);        // B → net €425.00 (distinctive)

    // A's export = A's rows only; B's distinctive figure never appears (owner scope IS the boundary).
    $csv = $this->actingAs($a['user'])->get(route('owner.statements.history.csv'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->streamedContent();

    expect($csv)->toContain('Net payable')     // header row
        ->toContain('€170.00')                 // A's real net
        ->not->toContain('€425.00');           // never B's

    // A year with no statements → an owner-scoped, headers-only file (the filter clips, never widens).
    $empty = $this->actingAs($a['user'])->get(route('owner.statements.history.csv', ['year' => 2020]))
        ->assertOk()->streamedContent();
    expect($empty)->toContain('Month')->not->toContain('€170.00');

    // EXPORTS capability: accountant may download; view-only may NOT (co-owner + accountant only).
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->get(route('owner.statements.history.csv'))->assertOk();
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'view_only'))->get(route('owner.statements.history.csv'))->assertForbidden();
});

// ── 8. Statement-history PDF — generates (application/pdf) + EXPORTS-gated ─────────────────────────
it('exports the statement-history PDF and is EXPORTS-gated', function () {
    $s = seedScenario(1);
    $a = statementOwner($s, 0, 20000);

    $this->actingAs($a['user'])->get(route('owner.statements.history.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'view_only'))
        ->get(route('owner.statements.history.pdf'))->assertForbidden();
});
