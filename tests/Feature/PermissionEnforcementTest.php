<?php

use App\Models\OwnerInvite;
use App\Models\Payment;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// FINE-GRAINED PERMISSION ENFORCEMENT (invariant #30) — the 44-permission catalog is now CONSULTED.
//
// Until 2026-07-17 the catalog was DECORATIVE: 44 permissions across 14 roles, and **not one was checked at
// any route, middleware or controller**. The coarse #26 door admitted all 11 back-office roles EQUALLY, so
// `payments.refund` — deliberately withheld from Hotel Manager and Front Desk — was held by everyone who
// could reach `/app` at all. A catalog that describes a policy the app does not enforce is worse than no
// catalog: it reads like protection.
//
// ⚠️ THE HEADLINE IS NOT THE REFUND — IT IS PRIVILEGE ESCALATION. `POST /app/owners/invite` assigns a REAL
// spatie role and was gated only by that same door. PROVEN before the fix, end-to-end:
//     Housekeeping (units.view, housekeeping.view, housekeeping.manage — cannot refund, cannot manage users)
//       → POST /app/owners/invite {role: accountant, email: attacker@controlled.test}
//       → ✗ INVITE MINTED role=accountant status=pending
//       → Accountant holds payments.refund => true
// Two requests, no operator involved. Gating the refund route ALONE would have been cosmetic — the attacker
// simply invites themselves an Accountant. That is why the invite route is in this gate.
//
// WHY ROUTE MIDDLEWARE AND NOT THE SERVICE (deliberately unlike #27/#29): a permission is a REQUEST-level
// concern about an ACTOR; those are DATA-level bounds about a ROW. `FolioService::refundPayment()` is called
// by tests and jobs with NO authenticated user — a `can()` in there would be meaningless or fatal. The bound
// stays in the service (every caller); the authorization sits on the route (every actor).

/** Ask a permission question the way a REQUEST does — inside the org's spatie team context. */
function canInOrg($user, string $organizationId, string $permission): bool
{
    $registrar = app(PermissionRegistrar::class);
    $previous = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($organizationId);

    try {
        return $user->fresh()->can($permission);
    } finally {
        $registrar->setPermissionsTeamId($previous);
    }
}

/** A settled payment on a real folio, so the refund route has something legitimate to refuse. */
function payableReservation(array $s): array
{
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));
    $folios->recordPayment($folio, moneyEur(30000));

    return [$reservation, Payment::query()->where('reservation_id', $reservation->id)->where('type', 'payment')->firstOrFail()];
}

// ── 1. 🔑 THE ESCALATION, CLOSED — the highest-value gate of the seven ───────────────────────────
it('stops a Housekeeping user minting themselves an Accountant', function () {
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    $attacker = operatorUser($s, ['name' => 'Hattie Housekeeper'], 'Housekeeping');

    // Preconditions: the weakest back-office role. It passes the #26 door but holds neither permission.
    expect(canInOrg($attacker, $org, 'users.manage'))->toBeFalse()
        ->and(canInOrg($attacker, $org, 'payments.refund'))->toBeFalse();

    $this->actingAs($attacker)->post('/app/owners/invite', [
        'name' => 'Totally Legit',
        'email' => 'attacker@controlled.test',
        'role' => 'accountant',
    ])->assertForbidden();

    // The whole point: NO invite row exists. Privilege escalation is unbounded — an attacker who can mint an
    // Accountant owns every money route the catalog protects, so this must not merely redirect.
    expect(OwnerInvite::query()->withoutGlobalScopes()->where('email', 'attacker@controlled.test')->exists())
        ->toBeFalse();
});

it('still lets a properly-permissioned admin invite staff', function () {
    $s = seedScenario(1);
    // Group Admin genuinely holds users.manage per the catalog — the check must let the real workflow through.
    $admin = operatorUser($s, ['name' => 'Ada Admin'], 'Group Admin');

    $this->actingAs($admin)->post('/app/owners/invite', [
        'name' => 'New Accountant',
        'email' => 'real.hire@example.test',
        'role' => 'accountant',
    ])->assertRedirect();

    expect(OwnerInvite::query()->withoutGlobalScopes()->where('email', 'real.hire@example.test')->exists())
        ->toBeTrue();
});

// ── 2. THE REFUND — the catalog withholds it from Front Desk; now the app does too ───────────────
it('refuses a refund to Front Desk, and writes no offsetting row', function () {
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    [$reservation, $payment] = payableReservation($s);

    $frontDesk = operatorUser($s, ['name' => 'Fred Front-Desk'], 'Front Desk');
    expect(canInOrg($frontDesk, $org, 'payments.refund'))->toBeFalse()
        // …but they DO hold payments.record — proof the team context resolves and this is a real refusal,
        // not a null-team false (the silent failure mode that fooled the audit's first probe).
        ->and(canInOrg($frontDesk, $org, 'payments.record'))->toBeTrue();

    $refundsBefore = Payment::query()->where('type', 'refund')->count();

    $this->actingAs($frontDesk)->post('/app/reservations/'.$reservation->id.'/refunds', [
        'payment' => $payment->id,
    ])->assertForbidden();

    // NO MONEY MOVED. A 403 that still wrote a refund row would be worse than no gate at all.
    expect(Payment::query()->where('type', 'refund')->count())->toBe($refundsBefore);
});

it('still lets an Accountant refund', function () {
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    [$reservation, $payment] = payableReservation($s);

    $accountant = operatorUser($s, ['name' => 'Ann Accountant'], 'Accountant');
    expect(canInOrg($accountant, $org, 'payments.refund'))->toBeTrue();

    $this->actingAs($accountant)->post('/app/reservations/'.$reservation->id.'/refunds', ['payment' => $payment->id])
        ->assertRedirect()
        ->assertSessionHas('folio_notice');

    // The real append-only offsetting row landed — the legitimate path is unregressed.
    expect(Payment::query()->where('type', 'refund')->where('reservation_id', $reservation->id)->exists())->toBeTrue();
});

// ── 3. PAYOUTS — real money out, and until now there was no permission to enforce at all ─────────
it('gates the payout run on payouts.manage, which Hotel Manager does not hold', function () {
    $s = seedScenario(1);
    $org = (string) $s['org']->id;

    $manager = operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager');
    $accountant = operatorUser($s, ['name' => 'Ann Accountant'], 'Accountant');

    // `payouts.manage` did not exist in the catalog before this gate — the payout run moved REAL money with
    // no permission to check. It mirrors payments.refund: money OUT is an accounting act.
    expect(canInOrg($manager, $org, 'payouts.manage'))->toBeFalse()
        ->and(canInOrg($accountant, $org, 'payouts.manage'))->toBeTrue();

    $this->actingAs($manager)->post('/app/payouts/create', ['statement' => 'x'])->assertForbidden();
    $this->actingAs($manager)->post('/app/payouts/pay', ['payout' => 'x'])->assertForbidden();

    // The permitted role gets PAST the gate (a 404/redirect from the bogus id is fine — not a 403).
    $response = $this->actingAs($accountant)->post('/app/payouts/create', ['statement' => 'nonexistent-id']);
    expect($response->getStatusCode())->not->toBe(403);
});

// ── 4. RECORD PAYMENT + CANCEL — gated to their real catalog permissions ─────────────────────────
it('gates record-payment on payments.record', function () {
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // Housekeeping holds no folio permission at all.
    $housekeeper = operatorUser($s, ['name' => 'Hattie'], 'Housekeeping');
    expect(canInOrg($housekeeper, $org, 'payments.record'))->toBeFalse();

    $this->actingAs($housekeeper)->post('/app/reservations/'.$reservation->id.'/payments', ['amount' => '50.00'])
        ->assertForbidden();

    expect(Payment::query()->where('reservation_id', $reservation->id)->count())->toBe(0);
});

it('gates cancel and no-show on reservations.cancel', function () {
    $s = seedScenario(2);
    $org = (string) $s['org']->id;

    $housekeeper = operatorUser($s, ['name' => 'Hattie'], 'Housekeeping');
    expect(canInOrg($housekeeper, $org, 'reservations.cancel'))->toBeFalse();

    $first = book($s, ['unit_id' => $s['units'][0]->id]);
    $this->actingAs($housekeeper)->post('/app/reservations/'.$first->id.'/cancel', ['reason' => 'nope'])->assertForbidden();
    expect((string) $first->fresh()->status)->toBe('confirmed');

    // Hotel Manager DOES hold reservations.cancel — enforcing it costs the real workflow nothing.
    $manager = operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager');
    expect(canInOrg($manager, $org, 'reservations.cancel'))->toBeTrue();

    $this->actingAs($manager)->post('/app/reservations/'.$first->id.'/cancel', ['reason' => 'Guest called'])->assertRedirect();
    expect((string) $first->fresh()->status)->toBe('cancelled');
});

// ── 5. #26 IS INTACT — the coarse door still runs FIRST. This is a layer ON TOP, not a replacement ──
it('keeps blocking an owner entirely, before any permission is ever consulted', function () {
    $s = seedScenario(1);
    [$reservation, $payment] = payableReservation($s);

    // An owner holds `External Owner` — not in back_office_roles, so #26 bounces them to their own portal.
    // They never reach the `can:` layer at all: the two gates are independent, in order.
    $this->actingAs(ownerRoleUser($s, 0))
        ->post('/app/reservations/'.$reservation->id.'/refunds', ['payment' => $payment->id])
        ->assertRedirect(route('owner.dashboard'));

    expect(Payment::query()->where('type', 'refund')->count())->toBe(0);
});

// ── 6. ⚠️ THE 404-vs-403 TRAP — a cross-org test must still prove TENANT SCOPING ─────────────────
// If the acting user lacked the permission, `can:` would 403 BEFORE the scoped findOrFail ever ran — and the
// isolation assertion would silently become vacuous, "proving" nothing about tenancy. The actor here HOLDS
// the permission on purpose, so the 404 can only come from the tenant scope.
it('still 404s a cross-org refund for a fully-permitted actor, so isolation is what is proven', function () {
    $foreign = seedScenario(1);
    [$foreignReservation, $foreignPayment] = payableReservation($foreign);

    $s = seedScenario(1);
    $accountant = operatorUser($s, ['name' => 'Ann Accountant'], 'Accountant');
    expect(canInOrg($accountant, (string) $s['org']->id, 'payments.refund'))->toBeTrue();

    $this->actingAs($accountant)
        ->post('/app/reservations/'.$foreignReservation->id.'/refunds', ['payment' => $foreignPayment->id])
        ->assertNotFound();   // NOT 403 — the tenant scope, exactly as before

    expect(Payment::query()->withoutGlobalScopes()->where('type', 'refund')->count())->toBe(0);
});

// ── 7. THE UI MUST NOT INVITE A REJECTION (#27's principle, applied to authorization) ────────────
it('hides the refund control from a role that may not refund, and shows it to one that may', function () {
    $s = seedScenario(1);
    [$reservation] = payableReservation($s);

    // Hotel Manager owns this screen but may not refund — the button must not be there to click.
    $this->actingAs(operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager'))
        ->get('/app/reservations/'.$reservation->id)
        ->assertOk()
        ->assertDontSee('Refund');

    // The Accountant sees it.
    $this->actingAs(operatorUser($s, ['name' => 'Ann Accountant'], 'Accountant'))
        ->get('/app/reservations/'.$reservation->id)
        ->assertOk()
        ->assertSee('Refund');
});
