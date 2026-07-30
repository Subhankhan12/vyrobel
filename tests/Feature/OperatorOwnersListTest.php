<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #93 GATE 1 — the operator OWNERS list shows REAL owner financials that reconcile with what the owner sees
// (net-payable from the certified statement; the REAL 18% commission, never the wireframe's 15%; the payout-run
// summary counts real due/held owners). The Notifications feed gains a real "Unread" filter.

function ownersListStaff(array $s): User
{
    $user = User::factory()->create(['name' => 'List Staff', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $s['org']);
}

/** A real owner with an 18% agreement + a finalized statement (through the certified services). */
function listedOwner(array $s, float $commission = 18.0): array
{
    $owner = Owner::factory()->create(['name' => 'Amara Okafor', 'payout_currency' => 'EUR', 'status' => 'active', 'tax_id' => 'IE1234567X']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => $commission, 'payout_schedule' => 'monthly', 'effective_from' => '2026-01-01', 'effective_to' => null]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    $statement = app(OwnerStatementService::class)->finalize(app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return ['owner' => $owner, 'statement' => $statement];
}

// ── The list shows the owner's REAL net + the REAL 18% commission (never 15%) ─────────────────────
it('shows the real net-payable and the real 18% commission, not 15%', function () {
    $s = seedScenario(1);
    $ctx = listedOwner($s, 18.0);
    $net = (int) $ctx['statement']->net_payable_minor;

    $this->actingAs(ownersListStaff($s))->get(route('app.owners'))
        ->assertOk()
        ->assertSee('Amara Okafor')
        ->assertSee(MoneyFormatter::display($net, 'EUR'))   // the real statement net
        ->assertSee('18% of gross')                          // the REAL commission
        ->assertDontSee('15% of gross');                     // never the wireframe hardcode
});

// ── An owner with NO payout method is held (honest no-IBAN gate) ──────────────────────────────────
it('marks an owner with no payout method as on hold', function () {
    $s = seedScenario(1);
    listedOwner($s, 18.0);   // no PayoutMethod created → can't be paid → held

    $this->actingAs(ownersListStaff($s))->get(route('app.owners'))
        ->assertOk()
        ->assertSee('On hold');   // the honest hold state (no payout method on file)
});

// ── The commission reflects a per-owner override (not a hardcode) ─────────────────────────────────
it('reflects the real agreement commission for the org', function () {
    $s = seedScenario(1);
    listedOwner($s, 22.0);

    $this->actingAs(ownersListStaff($s))->get(route('app.owners'))
        ->assertOk()
        ->assertSee('22% of gross')
        ->assertDontSee('15% of gross');
});

// ── The Notifications feed "Unread" filter shows only unread rows ─────────────────────────────────
it('filters the notifications feed to unread rows', function () {
    $s = seedScenario(1);
    $user = ownersListStaff($s);

    book($s);   // fires reservation.confirmed → a real NewBookingNotification to the operator (in-app)
    $user = $user->fresh();
    expect(count(NotificationFeed::forUser($user, 'unread')['items']))->toBeGreaterThan(0);

    // The Unread chip renders + is honoured (the feed's 'unread' filter shows unread rows).
    $this->actingAs($user)->get(route('app.notifications', ['filter' => 'unread']))->assertOk()->assertSee('Unread');

    // Mark everything read → the 'unread' filter is now empty (a real read-state filter, not cosmetic).
    $user->unreadNotifications->markAsRead();
    expect(count(NotificationFeed::forUser($user->fresh(), 'unread')['items']))->toBe(0);
});
