<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Models\Channel;
use App\Models\CompanyAccount;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\User;
use App\Notifications\Operator\BalanceDueNotification;
use App\Notifications\Operator\ChannelSyncErrorNotification;
use App\Notifications\Operator\OwnerPayoutSentNotification;
use App\Notifications\Operator\StatementFinalizedNotification;
use App\Notifications\Operator\UnitOutOfOrderNotification;
use App\Notifications\Owner\AccountSecurityNotification;
use App\Notifications\Owner\PayoutSentNotification;
use App\Notifications\Owner\StatementReadyNotification;
use App\Services\AssignmentService;
use App\Services\ChannelConnectionService;
use App\Services\CompanyAccountService;
use App\Services\FolioService;
use App\Services\MaintenanceService;
use App\Services\NotificationPreferences;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutMethodService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// NOTIFICATION WIRING (Phase 3 · Sub-gate 3) — every notification fires on a REAL domain event that already
// exists today, to the correct ISOLATION-scoped recipient (operator = the org's back-office staff; owner = the
// ONE owner via their linked user), and delivery honours the SG2 preferences. Notifications with no backing
// event (reviews, digests, sign-in detection, terms changes) are NOT wired — proven here by firing a real but
// UNMAPPED event and getting nothing (we defer honestly; we never invent an event).

/** An owner with a linked portal user + a unit they own + an agreement — the owner side of the isolation tests. */
if (! function_exists('ownerPortalUser')) {
    function ownerPortalUser(array $s, int $unitIndex, string $name): array
    {
        $owner = Owner::factory()->create(['name' => $name, 'payout_currency' => 'EUR', 'status' => 'active']);
        $unit = $s['units'][$unitIndex];
        $unit->update(['owner_id' => $owner->id]);
        ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01', 'effective_to' => null]);

        $user = User::factory()->create(['name' => $name, 'status' => 'active']);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['owner' => $owner, 'user' => $user, 'unit' => $unit];
    }
}

/** Book → charge → attribute → finalize a statement for an owner (fires the REAL `owner_statement.finalized`). */
if (! function_exists('finalizeStatementFor')) {
    function finalizeStatementFor(array $s, array $o): OwnerStatement
    {
        $reservation = book($s, ['unit_id' => $o['unit']->id]);
        postRoom($reservation, 20000);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
        $svc = app(OwnerStatementService::class);

        return $svc->finalize($svc->generateDraft($o['owner']->id, '2026-09-01', '2026-09-30'));
    }
}

/** Count a user's notifications of one concrete type. */
if (! function_exists('notifsOfType')) {
    function notifsOfType(User $user, string $type): int
    {
        return $user->fresh()->notifications()->where('type', $type)->count();
    }
}

// ── balance_due: fires on checkout WITH a balance; silent when settled (the > 0 guard) ─────────────
it('notifies operators of a balance still due at check-out', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Front Desk']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);                       // a €200 charge, no payment → balance due

    // ⚠️ FIXTURE ONLY — the assertion below is unchanged. An outstanding balance now BLOCKS checkout, and
    //    the one exemption is a folio billed to a company account. Billing posts NOTHING, so the balance is
    //    still €200 owed and the notification behaves exactly as it always did. BILLED is not PAID.
    $account = CompanyAccount::query()->create([
        'organization_id' => $reservation->organization_id, 'name' => 'Northwind Ltd',
    ]);
    app(CompanyAccountService::class)
        ->bill(app(FolioService::class)->openFolio($reservation), $account);
    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(CheckInReservation::class)->handle($reservation);
    app(CheckOutReservation::class)->handle($reservation);   // fires reservation.checked_out

    expect(notifsOfType($operator, BalanceDueNotification::class))->toBe(1);
});

it('does NOT notify a balance due when the folio is settled in full', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Front Desk']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));   // paid in full → balance 0
    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(CheckInReservation::class)->handle($reservation);
    app(CheckOutReservation::class)->handle($reservation);

    expect(notifsOfType($operator, BalanceDueNotification::class))->toBe(0);
});

// ── unit_out_of_order: fires on the transition INTO out_of_service ─────────────────────────────────
it('notifies operators when a unit is taken out of service', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    // A normal-priority OOS ticket → unit.condition_changed (to out_of_service); NOT urgent, so only this fires.
    app(MaintenanceService::class)->open([
        'unit_id' => $s['units'][0]->id, 'title' => 'Burst pipe', 'priority' => 'normal', 'takes_out_of_service' => true,
    ]);

    expect(notifsOfType($operator, UnitOutOfOrderNotification::class))->toBe(1);
});

// ── payout.paid: BOTH the one owner AND the operators (two distinct notifications, isolation-scoped) ─
it('notifies the owner and the operators when a payout is paid, isolation-scoped', function () {
    $s = seedScenario(2);
    $operator = operatorUser($s, ['name' => 'Accountant']);
    $a = ownerPortalUser($s, 0, 'Amara Okafor');
    $b = ownerPortalUser($s, 1, 'Bruno Vale');   // a SECOND owner who must hear nothing

    $statement = finalizeStatementFor($s, $a);
    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);
    expect($payout)->not->toBeNull();
    app(OwnerPayoutService::class)->pay($payout);   // fires payout.paid

    expect(notifsOfType($a['user'], PayoutSentNotification::class))->toBe(1);          // the owner
    expect(notifsOfType($operator, OwnerPayoutSentNotification::class))->toBe(1);      // the operators
    expect(notifsOfType($b['user'], PayoutSentNotification::class))->toBe(0);          // NOT the other owner
});

// ── owner_statement.finalized: BOTH the one owner AND the operators ────────────────────────────────
it('notifies the owner and the operators when a statement is finalized', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Accountant']);
    $a = ownerPortalUser($s, 0, 'Amara Okafor');

    finalizeStatementFor($s, $a);   // fires owner_statement.finalized

    expect(notifsOfType($a['user'], StatementReadyNotification::class))->toBe(1);
    expect(notifsOfType($operator, StatementFinalizedNotification::class))->toBe(1);
});

// ── channel.error: the operators ───────────────────────────────────────────────────────────────────
it('notifies operators when a channel connection errors', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);
    $channel = Channel::factory()->create(['property_id' => $s['property']->id]);

    app(ChannelConnectionService::class)->markError($channel, 'HMAC handshake failed');   // fires channel.error

    expect(notifsOfType($operator, ChannelSyncErrorNotification::class))->toBe(1);
});

// ── account_security (ALWAYS-ON): fires on a real payout-method change, to the one owner ───────────
it('notifies the owner of a payout-method change (always-on account & security)', function () {
    $s = seedScenario(1);
    $a = ownerPortalUser($s, 0, 'Bank Owner');

    // Even with EVERY preference turned off, an always-on event is delivered.
    app(NotificationPreferences::class)->save($a['user'], 'owner', []);

    app(OwnerPayoutMethodService::class)->add($a['user'], [
        'account_holder' => 'Bank Owner', 'iban' => 'GB82WEST12345698765432', 'currency' => 'EUR',
    ]);   // fires owner.payout_method_changed

    expect(notifsOfType($a['user'], AccountSecurityNotification::class))->toBe(1);
});

// ── SG2 gate applies to the SG3 wiring: an off toggle suppresses a newly-wired notification ────────
it('respects the preference toggle for a newly-wired event (balance_due off → suppressed)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Front Desk']);
    app(NotificationPreferences::class)->save($operator, 'operator', ['balance_due' => ['in_app' => false, 'email' => false]]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    // ⚠️ FIXTURE ONLY — the assertion below is unchanged. An outstanding balance now BLOCKS checkout, and
    //    the one exemption is a folio billed to a company account. Billing posts NOTHING, so the balance is
    //    still €200 owed and the notification behaves exactly as it always did. BILLED is not PAID.
    $account = CompanyAccount::query()->create([
        'organization_id' => $reservation->organization_id, 'name' => 'Northwind Ltd',
    ]);
    app(CompanyAccountService::class)
        ->bill(app(FolioService::class)->openFolio($reservation), $account);
    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(CheckInReservation::class)->handle($reservation);
    app(CheckOutReservation::class)->handle($reservation);

    expect(notifsOfType($operator, BalanceDueNotification::class))->toBe(0);   // gated off — not delivered
});

// ── HONESTY: a real but UNMAPPED event produces NOTHING (we defer, we never invent) ────────────────
it('creates no notification for a real event that is not wired', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Front Desk']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // new_booking fires (wired)
    $baseline = $operator->fresh()->notifications()->count();

    app(AssignmentService::class)->cancelReservation($reservation, 'guest cancelled');   // reservation.cancelled — NOT mapped

    expect($operator->fresh()->notifications()->count())->toBe($baseline);   // unchanged: cancellation isn't wired
});
