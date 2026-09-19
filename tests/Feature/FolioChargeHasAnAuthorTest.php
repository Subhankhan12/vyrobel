<?php

use App\Models\AuditEvent;
use App\Models\Tax;
use App\Models\User;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/**
 * F-45 — A FOLIO LINE RECORDS WHO WROTE IT.
 *
 * ⚠️⚠️ THE DEFECT THIS PINS. `postCharge()` and `applyWalletCredit()` wrote a domain event and NOTHING ELSE,
 * while every payment method in the same service ({@see FolioService::recordPayment()},
 * `recordCheckoutIntent()`, `settlePayment()`) wrote BOTH a domain event and an audit row. So a write that
 * INCREASES WHAT A GUEST OWES had no recorded author anywhere.
 *
 * ⚠️ A DOMAIN EVENT IS NOT AN ANSWER TO "WHO". 🔬 `domain_events` has no actor column — measured, not
 * assumed. The business outbox records that a charge happened; only `audit_events` records who made it
 * happen. A test that asserted "an event was recorded" would pass over this defect completely, which is why
 * the assertion below resolves the actor to a NAMED PERSON.
 *
 * ⚠️ ONE GUARD, EVERY CALLER: `postAddOn()` / `applyTax()` / `applyFee()` all funnel through `postCharge()`,
 * so the third case below covers the entrances without a recorder on each.
 *
 * ⚠️ THE WRONG-FIX MUTATION (FIX 05's): record the event and leave the actor null → the row exists and this
 * test still goes RED, because it asserts the actor resolves, not that a row appeared.
 */
function fchaFolio(): array
{
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Hotel Manager');
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);

    return ['scenario' => $s, 'user' => $user, 'folio' => $folio];
}

/** Audit rows for one action, unscoped — the trail is the subject, so never read it through a scope. */
function fchaAudit(string $action): Collection
{
    return AuditEvent::withoutGlobalScopes()->where('action', $action)->get();
}

it('records a NAMED author when a charge is posted', function () {
    $c = fchaFolio();

    $this->actingAs($c['user']);
    app(FolioService::class)->postCharge($c['folio'], 'manual', 'Minibar', moneyEur(2500));

    $rows = fchaAudit('folio.charge_posted');
    expect($rows)->toHaveCount(1, 'a posted charge must leave exactly one audit row');

    // ⚠️ THE ASSERTION THAT MATTERS — the row must resolve to a person, not merely exist.
    $row = $rows->first();
    expect($row->actor_id)->not->toBeNull('the charge must name who posted it');
    expect((string) $row->actor_type)->toBe('user');
    expect(User::withoutGlobalScopes()->whereKey($row->actor_id)->value('email'))
        ->toBe($c['user']->email, 'the actor must resolve to the real operator');

    // ⚠️ `before` IS NULL BY FACT: a charge is an APPEND — there is no previous value for a line that did
    // not exist. `after` carries identifiers and integers only, never guest PII (#28).
    expect($row->before)->toBeNull();
    expect($row->after)->toBe(['type' => 'manual', 'amount_minor' => 2500]);
});

it('records a NAMED author when a wallet credit is applied', function () {
    $c = fchaFolio();

    $this->actingAs($c['user']);
    app(FolioService::class)->applyWalletCredit($c['folio'], moneyEur(1000));

    $rows = fchaAudit('folio.wallet_credit_applied');
    expect($rows)->toHaveCount(1);
    expect($rows->first()->actor_id)->not->toBeNull('a wallet credit moves a guest\'s money — it must name an author');
});

/**
 * ⚠️ EVERY ENTRANCE, PROVEN RATHER THAN ASSUMED. A recorder on one entrance is the same defect in a new
 * coat — so this drives a DIFFERENT public method and requires the trail to appear anyway.
 */
it('covers the delegating entrances, because they funnel through postCharge', function () {
    $c = fchaFolio();
    $tax = Tax::query()->create([
        'property_id' => $c['scenario']['property']->id,
        'name' => 'City tax', 'mode' => 'flat', 'rate' => 0, 'amount_minor' => 150, 'currency' => 'EUR',
    ]);

    $this->actingAs($c['user']);
    app(FolioService::class)->applyTax($c['folio'], $tax, moneyEur(10000));

    expect(fchaAudit('folio.charge_posted'))->toHaveCount(1, 'applyTax() must inherit the trail from postCharge()');
});
