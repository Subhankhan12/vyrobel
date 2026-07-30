<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 4 (Operator Payout Run, /app/payouts/run) — a NEW TENANT-scoped operator screen over
// the CERTIFIED OwnerPayoutService, and the first operator surface that writes REAL MONEY STATE. Locks:
//   • the ready-to-pay list is REAL and its derived totals reconcile TO THE CENT against the DB;
//   • the create write really inserts a pending Payout for the statement's exact net (asserted against the
//     DB, never the page), and it is IDEMPOTENT — twice yields ONE payout (the service returns the existing);
//   • the pay write really pays: the payout is genuinely `paid` with `paid_at`, AND the source statement
//     flips to `paid` — the service's real, certified side effect;
//   • GUARDED — paying an already-paid payout is a clean flash error, never a 500, and never mutates;
//   • TENANT ISOLATION — a foreign org's statement_id/payout_id 404s on BOTH writes, and its rows survive;
//   • the design's fictions are never rendered ("Scheduled"/"On hold" are in neither real enum; no IBAN
//     column exists; no SEPA export exists), and no raw Blade leaks.

/**
 * An operator staff member of the given scenario's org: an active Membership PLUS a real back-office
 * role, as a genuine staff invite produces (OwnerInviteService::assignStaffRole). The role is what
 * EnsureBackOfficeAccess allow-lists on /app — a Membership alone is not operator access, since an
 * owner has one too.
 */
function payoutRunStaff(array $scenario, string $name = 'Marcus Bellweather'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // Accountant: the payout run is gated on `payouts.manage` (added 2026-07-17 — it had no permission at
    // all), held by Property Owner / Group Admin / Accountant. Paying an owner out is an accounting act.
    return grantBackOfficeRole($user, $scenario['org'], 'Accountant');
}

/**
 * An owner with a REAL finalized statement, built entirely through the certified services — a real unit,
 * a real agreement, an attributed booking → owner_ledger, then generateDraft + finalize. Never hand-rolled.
 * With a 15% commission, a $roomMinor room charge nets $roomMinor * 0.85.
 */
function payoutRunOwner(array $s, int $unitIndex, int $roomMinor, string $name): array
{
    $owner = Owner::factory()->create(['name' => $name, 'payout_currency' => 'EUR', 'status' => 'active']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);

    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id,
        'unit_id' => null,
        'commission_pct' => 15.00,
        'effective_from' => '2026-01-01',
        'effective_to' => null,
    ]);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return compact('owner', 'unit', 'statement');
}

// ── 1. The ready-to-pay list is REAL and the derived totals reconcile TO THE CENT ────────────────
it('lists real finalized statements as ready to pay and reconciles the derived total to the cent', function () {
    $s = seedScenario(2);
    $staff = payoutRunStaff($s);

    $a = payoutRunOwner($s, 0, 20000, 'Amara Okafor');       // net €170.00
    $b = payoutRunOwner($s, 1, 50000, 'Bruno Castellane');   // net €425.00

    // The certified backend's own numbers — the page must match THESE, not the other way round.
    expect((int) $a['statement']->net_payable_minor)->toBe(17000)
        ->and((int) $b['statement']->net_payable_minor)->toBe(42500)
        ->and($a['statement']->status)->toBe('finalized')
        ->and($a['statement']->isLocked())->toBeTrue();

    $response = $this->actingAs($staff)->get('/app/payouts/run');

    $response->assertOk()
        ->assertSee('Ready to pay')
        ->assertSee('Amara Okafor')
        ->assertSee('€170.00')
        ->assertSee('Bruno Castellane')
        ->assertSee('€425.00')
        ->assertSee('€595.00')                  // the DERIVED ready total: 17000 + 42500 = 59500
        ->assertSee('Create payout')
        ->assertSee('2 statements ready · 0 transfers pending');

    // …and that total is Σ the real rows in the DB, to the cent.
    expect((int) OwnerStatement::query()
        ->where('status', 'finalized')->where('locked', true)
        ->sum('net_payable_minor'))->toBe(59500);

    assertNoRawBlade($response);
});

// ── 2. THE REAL CREATE WRITE — asserted against the DB, not the page ─────────────────────────────
it('creates a REAL pending payout for the statement’s exact net', function () {
    $s = seedScenario(1);
    $staff = payoutRunStaff($s);
    $a = payoutRunOwner($s, 0, 20000, 'Amara Okafor');

    $this->actingAs($staff)
        ->post('/app/payouts/create', ['statement_id' => $a['statement']->id])
        ->assertRedirect('/app/payouts/run')
        ->assertSessionHas('payout_notice');

    $payout = Payout::query()->where('owner_statement_id', $a['statement']->id)->sole();

    expect($payout->status)->toBe('pending')
        ->and((int) $payout->amount_minor)->toBe((int) $a['statement']->net_payable_minor)
        ->and((int) $payout->amount_minor)->toBe(17000)
        ->and((string) $payout->currency)->toBe('EUR')
        ->and($payout->rail)->toBe('manual')      // the real bound rail stamped its own name()
        ->and($payout->paid_at)->toBeNull();
});

// ── 3. THE REAL PAY WRITE — the payout pays AND the source statement flips (the service's behaviour) ──
it('pays a pending payout for real and flips its source statement to paid', function () {
    $s = seedScenario(1);
    $staff = payoutRunStaff($s);
    $a = payoutRunOwner($s, 0, 20000, 'Amara Okafor');

    $this->actingAs($staff)->post('/app/payouts/create', ['statement_id' => $a['statement']->id]);
    $payout = Payout::query()->where('owner_statement_id', $a['statement']->id)->sole();

    $this->actingAs($staff)
        ->post('/app/payouts/pay', ['payout_id' => $payout->id])
        ->assertRedirect('/app/payouts/run')
        ->assertSessionHas('payout_notice');

    expect($payout->fresh()->status)->toBe('paid')
        ->and($payout->fresh()->paid_at)->not->toBeNull()
        // the certified side effect: the statement the money came from is now paid too.
        ->and($a['statement']->fresh()->status)->toBe('paid');

    // Having been paid, the statement is no longer offered — the page mirrors the service exactly.
    $this->actingAs($staff)->get('/app/payouts/run')
        ->assertOk()
        ->assertSee('No finalized statement is waiting for a payout.');
});

// ── 4. GUARDED — paying an already-paid payout is a clean flash error, never a 500, never a mutation ──
it('refuses to pay an already-paid payout with a clean flash error and leaves the row untouched', function () {
    $s = seedScenario(1);
    $staff = payoutRunStaff($s);
    $a = payoutRunOwner($s, 0, 20000, 'Amara Okafor');

    $this->actingAs($staff)->post('/app/payouts/create', ['statement_id' => $a['statement']->id]);
    $payout = Payout::query()->where('owner_statement_id', $a['statement']->id)->sole();
    $this->actingAs($staff)->post('/app/payouts/pay', ['payout_id' => $payout->id]);

    $paidAt = $payout->fresh()->paid_at;
    expect($payout->fresh()->status)->toBe('paid');

    // The service THROWS on a non-pending payout; the controller must surface that, not 500 on it.
    $this->actingAs($staff)
        ->post('/app/payouts/pay', ['payout_id' => $payout->id])
        ->assertRedirect('/app/payouts/run')
        ->assertSessionHas('payout_error');

    expect($payout->fresh()->status)->toBe('paid')
        ->and($payout->fresh()->paid_at->eq($paidAt))->toBeTrue()   // genuinely untouched
        ->and(Payout::query()->where('owner_statement_id', $a['statement']->id)->count())->toBe(1);
});

// ── 5. IDEMPOTENT — creating twice for one statement yields exactly ONE payout ───────────────────
it('is idempotent — creating twice for one statement yields exactly one payout', function () {
    $s = seedScenario(1);
    $staff = payoutRunStaff($s);
    $a = payoutRunOwner($s, 0, 20000, 'Amara Okafor');

    $this->actingAs($staff)->post('/app/payouts/create', ['statement_id' => $a['statement']->id]);
    $first = Payout::query()->where('owner_statement_id', $a['statement']->id)->sole();

    // The service hands the EXISTING payout back; the second attempt must not insert, and must not claim to.
    $this->actingAs($staff)
        ->post('/app/payouts/create', ['statement_id' => $a['statement']->id])
        ->assertRedirect('/app/payouts/run')
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, 'already has a payout'));

    expect(Payout::query()->where('owner_statement_id', $a['statement']->id)->count())->toBe(1)
        ->and(Payout::query()->where('owner_statement_id', $a['statement']->id)->sole()->id)->toBe($first->id);
});

// ── 6. TENANT ISOLATION — another org's statement/payout 404s on BOTH writes and survives untouched ──
it('isolates tenants — another organization’s statement or payout 404s on both writes', function () {
    $other = seedScenario(1);
    $foreign = payoutRunOwner($other, 0, 30000, 'Foreign Owner');
    $foreignPayout = app(OwnerPayoutService::class)->createFromStatement($foreign['statement']);
    expect($foreignPayout)->not->toBeNull();

    $s = seedScenario(1);   // a second org — the tenant context now points here
    $staff = payoutRunStaff($s);

    // Every model here is BelongsToOrganization, so the scoped findOrFail resolves nothing across the line.
    $this->actingAs($staff)
        ->post('/app/payouts/create', ['statement_id' => $foreign['statement']->id])
        ->assertNotFound();

    $this->actingAs($staff)
        ->post('/app/payouts/pay', ['payout_id' => $foreignPayout->id])
        ->assertNotFound();

    // …an unknown id 404s the same way, never a 500…
    $this->actingAs($staff)->post('/app/payouts/pay', ['payout_id' => '01JQZZZZZZZZZZZZZZZZZZZZZZ'])->assertNotFound();

    // …the foreign owner never appears on this org's run…
    $this->actingAs($staff)->get('/app/payouts/run')
        ->assertOk()
        ->assertDontSee('Foreign Owner')
        ->assertDontSee('€255.00');

    // …and the foreign rows are genuinely untouched (still pending, still exactly one).
    app(TenantContext::class)->set($other['org']->id);
    expect($foreignPayout->fresh()->status)->toBe('pending')
        ->and($foreignPayout->fresh()->paid_at)->toBeNull()
        ->and($foreign['statement']->fresh()->status)->toBe('finalized')
        ->and(Payout::query()->where('owner_statement_id', $foreign['statement']->id)->count())->toBe(1);
});

// ── 7. REAL ENUMS ONLY — the design's invented lifecycle + unbacked bank UI are never rendered ───
it('renders the real payout lifecycle only — no invented statuses, no IBAN, no SEPA claim', function () {
    $s = seedScenario(1);
    $staff = payoutRunStaff($s);
    $a = payoutRunOwner($s, 0, 20000, 'Amara Okafor');

    $this->actingAs($staff)->post('/app/payouts/create', ['statement_id' => $a['statement']->id]);

    $response = $this->actingAs($staff)->get('/app/payouts/run');

    $response->assertOk()
        // the REAL lifecycle vocabulary
        ->assertSee('Pending')
        ->assertSee('Bank transfer')            // rail=manual via the SHARED RailLabel map
        // ⚠️ the design's states — in NEITHER real enum, so they can never be rendered
        ->assertDontSee('Scheduled')
        ->assertDontSee('In review')
        // ⚠️ SEPA export is HONEST-DISABLED (#93 founder decision) — a "Soon", never a scheme claim that it works.
        ->assertSee('Export SEPA · Soon')
        // …and the deferred rail is stated honestly, never as something that can send
        ->assertSee('Stripe Connect disbursement is not connected');

    assertNoRawBlade($response);
});

// ── 8. THE BATCH WRITES — create-all then pay-all, over the REAL ready/pending sets ──────────────
it('creates every ready payout and pays every pending one in a batch, with honest counts', function () {
    $s = seedScenario(2);
    $staff = payoutRunStaff($s);

    $a = payoutRunOwner($s, 0, 20000, 'Amara Okafor');       // net €170.00
    $b = payoutRunOwner($s, 1, 50000, 'Bruno Castellane');   // net €425.00

    // Create all ready — no statement_id.
    $this->actingAs($staff)->post('/app/payouts/create')
        ->assertRedirect('/app/payouts/run')
        ->assertSessionHas('payout_notice', '2 payouts created.');

    expect(Payout::query()->where('status', 'pending')->count())->toBe(2)
        ->and((int) Payout::query()->where('status', 'pending')->sum('amount_minor'))->toBe(59500);

    // Re-running must NOT double-create — the service is idempotent and the count must stay honest.
    $this->actingAs($staff)->post('/app/payouts/create')
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, 'No new payouts'));
    expect(Payout::query()->count())->toBe(2);

    // Pay all pending — no payout_id.
    $this->actingAs($staff)->post('/app/payouts/pay')
        ->assertRedirect('/app/payouts/run')
        ->assertSessionHas('payout_notice', '2 payouts marked paid.');

    expect(Payout::query()->where('status', 'paid')->count())->toBe(2)
        ->and((int) Payout::query()->where('status', 'paid')->sum('amount_minor'))->toBe(59500)
        // the certified side effect on BOTH source statements
        ->and($a['statement']->fresh()->status)->toBe('paid')
        ->and($b['statement']->fresh()->status)->toBe('paid');

    // With nothing left pending, the batch says so honestly rather than reporting a phantom success.
    $this->actingAs($staff)->post('/app/payouts/pay')
        ->assertSessionHas('payout_notice', 'No pending payouts to pay.');
});

// ── 9. NOT PAYABLE — a zero/negative net is never offered a Create button (the service returns null) ──
it('never offers a payout for a statement with no positive net, and explains the real reason', function () {
    $s = seedScenario(1);
    $staff = payoutRunStaff($s);

    // A real owner with a real agreement but NO attributed revenue → a genuine zero-net statement.
    $owner = Owner::factory()->create(['name' => 'Zero Netowner', 'payout_currency' => 'EUR', 'status' => 'active']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00,
        'effective_from' => '2026-01-01', 'effective_to' => null,
    ]);

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    expect((int) $statement->net_payable_minor)->toBe(0);

    $this->actingAs($staff)->get('/app/payouts/run')
        ->assertOk()
        ->assertSee('Not payable')
        ->assertSee('Zero Netowner')
        ->assertSee('Nothing payable — the net for this period is zero.')
        ->assertSee('No finalized statement is waiting for a payout.');   // it is NOT in the ready list

    // …and the service agrees: creating from it is a no-op, reported honestly, with no row inserted.
    $this->actingAs($staff)
        ->post('/app/payouts/create', ['statement_id' => $statement->id])
        ->assertRedirect('/app/payouts/run')
        ->assertSessionHas('payout_notice', fn (string $m): bool => str_contains($m, 'Nothing payable'));

    expect(Payout::query()->count())->toBe(0);
});
