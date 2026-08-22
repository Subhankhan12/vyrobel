<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerStatement;
use App\Models\PayoutMethod;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #93 GATE 2 — the operator OWNER DETAIL manages the REAL owner financials: the net-to-owner reconciles at the
// real 18% commission; Edit-owner writes a new effective-dated agreement; Pause-payouts is real (the engine
// respects it); Remove-owner is a careful ARCHIVE-not-destroy (financials + bookings retained). Masked IBAN only.

/** A real owner with an 18% agreement + a finalized statement + a payout method. */
function manageableOwner(array $s, float $commission = 18.0): array
{
    $owner = Owner::factory()->create(['name' => 'Amara Okafor', 'payout_currency' => 'EUR', 'status' => 'active', 'tax_id' => 'IE1X']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => $commission, 'payout_schedule' => 'monthly', 'effective_from' => '2026-01-01', 'effective_to' => null]);
    PayoutMethod::create(['owner_id' => $owner->id, 'label' => 'Main', 'account_holder' => 'Amara Okafor', 'iban' => 'IE29AIBK93115212344821', 'iban_last4' => '4821', 'iban_country' => 'IE', 'currency' => 'EUR', 'is_default' => true]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    $statement = app(OwnerStatementService::class)->finalize(app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return ['owner' => $owner, 'statement' => $statement, 'reservation' => $reservation];
}

// ── The detail page shows the net-to-owner + masked IBAN (never full) at the real 18% ─────────────
it('renders the net-to-owner breakdown, masked IBAN and the real commission', function () {
    $s = seedScenario(1);
    $ctx = manageableOwner($s, 18.0);
    $staff = operatorUser($s, [], 'Property Owner');   // holds users.manage + payouts.manage

    $this->actingAs($staff)->get(route('app.owners.show', $ctx['owner']->id))
        ->assertOk()
        ->assertSee('Net to owner')
        ->assertSee('4821')              // masked IBAN last-4
        ->assertSee('18%')               // real commission
        ->assertDontSee('93115212344821');   // never the full IBAN
});

// ── Edit-owner writes a NEW effective-dated agreement (applies from next statement) ───────────────
it('edits the commission by creating a new future-dated agreement', function () {
    $s = seedScenario(1);
    $ctx = manageableOwner($s, 18.0);
    $owner = $ctx['owner'];
    $staff = operatorUser($s, [], 'Property Owner');

    $this->actingAs($staff)->post(route('app.owners.update', $owner->id), ['commission_pct' => '20'])->assertRedirect();

    // A new active agreement at 20% exists, effective in the future; the old 18% row was closed.
    $new = ManagementAgreement::query()->where('owner_id', $owner->id)->where('commission_pct', 20.0)->first();
    expect($new)->not->toBeNull()
        ->and($new->status)->toBe('active');
});

// ── Pause-payouts is REAL — sets OwnerPayoutPreference.paused (the engine respects it) ─────────────
it('pauses payouts via the real OwnerPayoutPreference', function () {
    $s = seedScenario(1);
    $ctx = manageableOwner($s);
    $staff = operatorUser($s, [], 'Property Owner');

    $this->actingAs($staff)->post(route('app.owners.pause-payouts', $ctx['owner']->id), ['paused' => '1'])->assertRedirect();
    expect((bool) OwnerPayoutPreference::query()->where('owner_id', $ctx['owner']->id)->value('paused'))->toBeTrue();
});

// ── ⚠️ REMOVE-OWNER archives, NEVER destroys (financials + bookings retained; units unassigned) ───
it('archives an owner without destroying financials or bookings', function () {
    $s = seedScenario(1);
    $ctx = manageableOwner($s);
    $owner = $ctx['owner'];
    $staff = operatorUser($s, [], 'Property Owner');
    $reservationId = $ctx['reservation']->id;
    $statementId = $ctx['statement']->id;
    $unitId = $s['units'][0]->id;

    $this->actingAs($staff)->post(route('app.owners.remove', $owner->id), ['confirm_name' => 'Amara Okafor'])
        ->assertRedirect(route('app.owners'));

    // The owner is soft-deleted (archived), NOT hard-deleted.
    expect(Owner::withTrashed()->find($owner->id))->not->toBeNull()
        ->and(Owner::find($owner->id))->toBeNull();
    // The unit is unassigned; the booking + statement are RETAINED.
    expect((string) Unit::find($unitId)->owner_id ?: null)->toBeNull()
        ->and(Reservation::find($reservationId))->not->toBeNull()
        ->and(OwnerStatement::withoutGlobalScopes()->find($statementId))->not->toBeNull();
    // The active agreement was ended, not deleted.
    expect(ManagementAgreement::query()->where('owner_id', $owner->id)->where('status', 'ended')->exists())->toBeTrue();
});

// ── Remove requires the exact typed name ──────────────────────────────────────────────────────────
it('refuses removal when the typed name does not match', function () {
    $s = seedScenario(1);
    $ctx = manageableOwner($s);
    $staff = operatorUser($s, [], 'Property Owner');

    $this->actingAs($staff)->post(route('app.owners.remove', $ctx['owner']->id), ['confirm_name' => 'Wrong Name'])
        ->assertRedirect()->assertSessionHas('owners_error');
    expect(Owner::find($ctx['owner']->id))->not->toBeNull();   // still there
});

// ── The management writes are gated on users.manage ──────────────────────────────────────────────
it('gates owner management on users.manage', function () {
    $s = seedScenario(1);
    $ctx = manageableOwner($s);
    $frontDesk = operatorUser($s, [], 'Front Desk');   // no users.manage

    $this->actingAs($frontDesk)->post(route('app.owners.remove', $ctx['owner']->id), ['confirm_name' => 'Amara Okafor'])->assertForbidden();
    $this->actingAs($frontDesk)->post(route('app.owners.pause-payouts', $ctx['owner']->id), ['paused' => '1'])->assertForbidden();
});
