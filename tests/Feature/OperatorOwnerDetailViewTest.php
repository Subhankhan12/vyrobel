<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 3 (Operator Owner Detail, /app/owners/{owner}) — a NEW TENANT-scoped operator page
// over the CERTIFIED owner-accounting backend. Locks its PRESENTATION guarantees:
//   • the owner's REAL record / units / agreement / statements / payouts render, and the DERIVED balance
//     reconciles TO THE CENT against Σ signedMinor() over the real owner_ledger rows;
//   • TENANT ISOLATION — an owner in another org 404s (Owner is BelongsToOrganization);
//   • NO CROSS-OWNER MIX — ⚠️ this is the isolation headline for THIS surface. OwnerScope is INACTIVE on
//     /app (by design: operators see the owners they manage), so every non-Owner read is protected ONLY
//     by an explicit ->where('owner_id', …). Two owners in the SAME org prove that filter is really
//     there: drop it and owner B's units/statements/payouts appear on owner A's page;
//   • the invite/portal state is the REAL one — and specifically that "Portal access active" is NOT
//     claimed for an owner who merely has a pending invite (OwnerInviteService links owners.user_id to
//     an INACTIVE 'invited' login the moment the invite is sent, so user_id alone proves nothing);
//   • the design's fabrications (IBAN / "Pause payouts" / "Remove owner" / "Workspace paused" /
//     "Scheduled") are never invented, encrypted tax_id is never rendered, and no raw Blade leaks.

/**
 * An operator staff member of the given scenario's org: an active Membership PLUS a real back-office
 * role, as a genuine staff invite produces (OwnerInviteService::assignStaffRole). The role is what
 * EnsureBackOfficeAccess allow-lists on /app — a Membership alone is not operator access, since an
 * owner has one too. This user is deliberately NOT owner-linked: an owner would be bounced to /owner.
 */
function ownerDetailStaff(array $scenario, string $name = 'Marcus Bellweather'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org']);
}

/**
 * A fully-loaded owner: a real unit, a real agreement, an attributed booking → owner_ledger, a finalized
 * statement and a payout. Everything is built through the CERTIFIED services, never hand-inserted.
 */
function detailOwner(array $s, int $unitIndex, string $unitCode, int $roomMinor, array $ownerAttrs = [], bool $pay = false): array
{
    $owner = Owner::factory()->create(array_merge([
        'name' => 'Amara Okafor',
        'payout_currency' => 'EUR',
        'status' => 'active',
    ], $ownerAttrs));

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id, 'code' => $unitCode]);

    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id,
        'unit_id' => null,
        'commission_pct' => 15.00,
        'payout_schedule' => 'monthly',
        'effective_from' => '2026-01-01',
        'effective_to' => null,
    ]);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    $payoutSvc = app(OwnerPayoutService::class);
    $payout = $payoutSvc->createFromStatement($statement);
    if ($pay && $payout !== null) {
        $payout = $payoutSvc->pay($payout);   // manual rail (default)
    }

    return compact('owner', 'unit', 'statement', 'payout');
}

// ── 1. Real owner data + a balance that reconciles TO THE CENT + no fabrications ─────────────────
it('renders the owner’s real record, units, agreement, statements and payouts, and reconciles the balance to the cent', function () {
    $s = seedScenario(1);
    $a = detailOwner($s, 0, 'AMARAUNIT', 20000, [
        'type' => 'company',
        'legal_name' => 'Seaside Villas Ltd',
        'email' => 'amara.okafor@seasidevillas.example',
        'phone' => '+353 87 555 4407',
        'tax_id' => 'IE9876543Z',                       // encrypted PII — must NEVER be rendered
        'notes' => 'Refurb of the dune-side unit planned.',
    ], pay: true);

    // The DERIVED figure's SOURCE OF TRUTH: Σ signedMinor() over the owner's real ledger rows —
    // €200.00 room revenue − €30.00 commission (15%) − €0.00 unverified OTA = €170.00.
    $netMinor = ownerNet($a['owner']->id);
    expect($netMinor)->toBe(17000);

    $response = $this->actingAs(ownerDetailStaff($s))->get("/app/owners/{$a['owner']->id}");

    $response->assertOk()
        // — the real owner record
        ->assertSee('Amara Okafor')
        ->assertSee('Seaside Villas Ltd')
        ->assertSee('Company')                                   // real `type` enum
        ->assertSee('Active')                                    // real `status` enum
        ->assertSee('amara.okafor@seasidevillas.example')        // operator's own CRM contact (allowed)
        ->assertSee('+353 87 555 4407')
        ->assertSee('Refurb of the dune-side unit planned.')     // real `owners.notes`, read-only
        // — the real unit
        ->assertSee('AMARAUNIT')
        // — the real agreement terms
        ->assertSee('15%')                                    // real commission_pct — never "0%"
        ->assertSee('Portfolio-wide')                            // unit_id null = portfolio scope
        ->assertSee('Monthly')                                   // real payout_schedule
        ->assertSee('Open-ended')                                // effective_to null, honestly
        // — the real statement row (the figures are the CERTIFIED backend's own, asserted verbatim:
        //   OwnerStatementTest locks gross_minor = 20000 and commission_minor = −3000 for this shape)
        ->assertSee('Finalized')                                 // real `owner_statements.status`
        ->assertSee(MoneyFormatter::display(20000, 'EUR'))       // real gross — €200.00
        ->assertSee(MoneyFormatter::display(-3000, 'EUR'))       // real commission — −€30.00 (U+2212)
        ->assertSee('Unverified lines')                          // REAL has_unverified flag (a data-driven
        ->assertSee('Locked')                                    // badge, not decoration) + the real lock
        // — the real payout row
        ->assertSee('Bank transfer')                             // rail=manual, via the shared RailLabel
        ->assertSee('Paid')                                      // real `payouts.status`
        // — the DERIVED balance IS that ledger sum, reconciled to the cent (not a coincidence): the
        //   needle is FORMATTED FROM THE DB FIGURE, so a drifting KPI cannot silently still pass.
        ->assertSee(MoneyFormatter::display($netMinor, 'EUR'));       // €170.00

    // ⚠️ the design's fabrications must never appear. (The fake payout account is asserted by NAME rather
    // than by the design's "····4821" digits: a random ULID could in principle contain "4821", so the
    // digits would be a flaky needle — "IBAN"/"Revolut" cannot appear by accident. Likewise the design's
    // "verified" is NOT a needle: it is a substring of the REAL "Unverified lines" statement flag.)
    $response
        ->assertDontSee('IBAN')
        ->assertDontSee('Revolut')
        ->assertDontSee('Pause payouts')
        ->assertDontSee('Remove owner')
        ->assertDontSee('Workspace paused')
        ->assertDontSee('Scheduled')
        ->assertDontSee('Statement PDF by email')
        ->assertDontSee('est. 3 Nov')
        // ⚠️ encrypted tax_id is NOT rendered even though the owner genuinely has one
        ->assertDontSee('IE9876543Z');

    assertNoRawBlade($response);
});

// ── 2. THE TENANT-ISOLATION HEADLINE — an owner in ANOTHER org 404s ──────────────────────────────
it('isolates tenants — an owner in another organization 404s', function () {
    $other = seedScenario(1);
    $foreign = detailOwner($other, 0, 'FOREIGNUNIT', 50000, ['name' => 'Foreign Owner']);

    $s = seedScenario(1);                    // a second org — the tenant context now points here
    $staff = ownerDetailStaff($s);

    // Owner is BelongsToOrganization, so the scoped findOrFail resolves nothing across the tenant line.
    $this->actingAs($staff)->get("/app/owners/{$foreign['owner']->id}")->assertNotFound();

    // …and an unknown id 404s the same way (never a 500, never an empty shell).
    $this->actingAs($staff)->get('/app/owners/01JQZZZZZZZZZZZZZZZZZZZZZZ')->assertNotFound();
});

// ── 3. NO CROSS-OWNER MIX — the ->where('owner_id') proof (OwnerScope is INACTIVE here) ──────────
it('shows only this owner’s units, statements and payouts — never another owner of the same org', function () {
    $s = seedScenario(2);   // ONE org, two units

    $a = detailOwner($s, 0, 'AUNIT', 20000, ['name' => 'Amara Okafor'], pay: true);       // net €170.00
    $b = detailOwner($s, 1, 'BUNIT', 50000, ['name' => 'Bruno Castellane'], pay: true);   // net €425.00

    app(TenantContext::class)->set($s['org']->id);
    expect(ownerNet($a['owner']->id))->toBe(17000)
        ->and(ownerNet($b['owner']->id))->toBe(42500);

    // Both owners are in the SAME org, so the tenant scope does NOT separate them and OwnerScope is
    // inactive on /app — only the explicit ->where('owner_id', …) filters do. If any read drops it,
    // owner B's distinctive figures land on owner A's page and these assertions fail.
    $this->actingAs(ownerDetailStaff($s))->get("/app/owners/{$a['owner']->id}")
        ->assertOk()
        ->assertSee('Amara Okafor')
        ->assertSee('AUNIT')
        ->assertSee('€170.00')       // A's balance + payout
        ->assertSee('€200.00')       // A's gross
        ->assertDontSee('Bruno Castellane')
        ->assertDontSee('BUNIT')     // B's unit
        ->assertDontSee('€425.00')   // B's balance + payout
        ->assertDontSee('€500.00');  // B's gross

    // …and symmetrically from B's page, so the test cannot pass by accident of ordering.
    $this->actingAs(ownerDetailStaff($s, 'Rosa Delacroix'))->get("/app/owners/{$b['owner']->id}")
        ->assertOk()
        ->assertSee('BUNIT')
        ->assertSee('€425.00')
        ->assertDontSee('AUNIT')
        ->assertDontSee('€170.00');
});

// ── 4. Portal / invite state is the REAL state (never a claimed access that does not exist) ──────
it('renders the real portal-access state for a linked owner and for a pending invite', function () {
    $s = seedScenario(1);
    $staff = ownerDetailStaff($s);

    // (a) A genuinely linked owner with an ACTIVE login → portal access active.
    $linkedUser = User::factory()->create(['name' => 'Amara Okafor', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $linkedUser->id, 'status' => 'active']);
    $linked = Owner::factory()->create(['name' => 'Amara Okafor', 'user_id' => $linkedUser->id, 'payout_currency' => 'EUR']);

    $this->actingAs($staff)->get("/app/owners/{$linked->id}")
        ->assertOk()
        ->assertSee('Portal access active')
        ->assertDontSee('No portal access');

    // (b) An INVITED owner — exactly what OwnerInviteService builds: an `inactive` owner already linked
    //     to an `invited` (not yet usable) login, plus a real pending invite. `user_id` is set, so a
    //     naive read would wrongly claim access; the real state is the pending invite + its real expiry.
    $invitedUser = User::factory()->create(['name' => 'Bruno Castellane', 'status' => 'invited']);
    $invitedOwner = Owner::factory()->create([
        'name' => 'Bruno Castellane',
        'user_id' => $invitedUser->id,
        'status' => 'inactive',
        'payout_currency' => 'EUR',
    ]);
    OwnerInvite::create([
        'organization_id' => $s['org']->id,
        'owner_id' => $invitedOwner->id,
        'email' => 'bruno.castellane@example.test',
        'token_hash' => hash('sha256', 'a-token-that-is-never-stored-in-plaintext'),
        'role' => 'owner',
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($staff)->get("/app/owners/{$invitedOwner->id}")
        ->assertOk()
        ->assertSee('Invite pending')                                  // the REAL invite state
        ->assertSee('Expires '.now()->addDays(7)->format('j M Y'))     // the REAL expires_at
        ->assertSee('Inactive')                                        // the REAL owners.status
        ->assertDontSee('Portal access active');                       // never claimed on a live invite

    // (c) No login and no invite at all → the honest empty state.
    $bare = Owner::factory()->create(['name' => 'Clara Nwosu', 'user_id' => null, 'payout_currency' => 'EUR']);

    $this->actingAs($staff)->get("/app/owners/{$bare->id}")
        ->assertOk()
        ->assertSee('No portal access')
        ->assertDontSee('Invite pending');
});

// ── 5. Honest empty states — a bare owner shows real zeros, not invented activity ────────────────
it('shows honest zeros and empty states for an owner with no units, ledger, statements or payouts', function () {
    $s = seedScenario(1);
    $bare = Owner::factory()->create(['name' => 'Clara Nwosu', 'payout_currency' => 'EUR']);

    // There genuinely is nothing to show — Σ over zero rows is a real €0.00 in the owner's own currency.
    expect(ownerNet($bare->id))->toBe(0)
        ->and(OwnerStatement::query()->where('owner_id', $bare->id)->count())->toBe(0)
        ->and(Payout::query()->where('owner_id', $bare->id)->count())->toBe(0)
        ->and(OwnerLedgerEntry::query()->where('owner_id', $bare->id)->count())->toBe(0);

    $response = $this->actingAs(ownerDetailStaff($s))->get("/app/owners/{$bare->id}");

    $response->assertOk()
        ->assertSee('€0.00')                                                       // the honest zero
        ->assertSee('No units are assigned to this owner yet.')
        ->assertSee('No management agreement is in effect for this owner today.')  // never a fake "0%"
        ->assertSee('No statements for this owner yet.')
        ->assertSee('No payouts for this owner yet.')
        ->assertDontSee('Scheduled');

    assertNoRawBlade($response);
});

// ── 6. The Owners list links each row to the real detail route ───────────────────────────────────
it('links each owner row on the operator Owners list to that owner’s detail page', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['name' => 'Amara Okafor', 'payout_currency' => 'EUR']);

    $this->actingAs(ownerDetailStaff($s))->get('/app/owners')
        ->assertOk()
        ->assertSee('Amara Okafor')
        ->assertSee(route('app.owners.show', $owner->id), false);
});
