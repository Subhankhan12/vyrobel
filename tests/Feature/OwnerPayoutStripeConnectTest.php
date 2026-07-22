<?php

use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// PHASE 8 · live Stripe · SG3 — the OWNER PAYOUT RAIL (Stripe Connect), the HIGHEST-STAKES seam: real money
// LEAVING to an owner's account. Test-mode (FakeStripeClient — no real money). THE proof of this gate is NO
// DOUBLE-TRANSFER (the idempotency key + the DB row-lock), plus payout to the CORRECT owner only.

/** An owner with tax details + a connected + enabled Stripe account. */
function connectedOwner(array $s, int $unitIndex, string $accountId): Owner
{
    $owner = ownUnit($s['units'][$unitIndex]);
    $owner->update([
        'tax_id' => 'PT500000000',
        'stripe_connect_account_id' => $accountId,
        'stripe_connect_payouts_enabled' => true,
    ]);

    return $owner->fresh();
}

/** A finalized statement → a pending payout for the owner. */
function pendingPayout(array $s, Owner $owner, int $unitIndex): Payout
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return app(OwnerPayoutService::class)->createFromStatement($statement);
}

function connectRail(): StripeConnectPayoutRail
{
    return app(StripeConnectPayoutRail::class);
}

/** POST a signed Stripe CONNECT webhook. */
function postConnectWebhook(string $type, array $object, ?string $eventId = null): TestResponse
{
    $event = ['id' => $eventId ?? 'evt_'.Str::lower(Str::random(18)), 'type' => $type, 'data' => ['object' => $object]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $sig = FakeStripeClient::signature($raw, (string) config('services.stripe.connect_webhook_secret'));

    return test()->call('POST', '/webhooks/stripe/connect', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw);
}

// ── 1. 🔒 THE PAYOUT (test-mode) — a transfer to the owner's account; processing, webhook-confirmed ──
it('transfers a payout to the owner’s connected account and marks it processing', function () {
    $s = seedScenario(1);
    $owner = connectedOwner($s, 0, 'acct_owner_a');
    $payout = pendingPayout($s, $owner, 0);

    $paid = app(OwnerPayoutService::class)->pay($payout, connectRail());

    // A real Stripe transfer was created to THIS owner's account; the payout is processing (webhook confirms).
    expect($paid->status)->toBe('processing')->and($paid->external_ref)->toStartWith('tr_');
    $transfers = app(StripeClientInterface::class)->transfers;
    expect($transfers)->toHaveCount(1);
    expect($transfers[0]['destination'])->toBe('acct_owner_a')->and($transfers[0]['amount_minor'])->toBe(17000);
});

// ── 2. 🔒 NO DOUBLE-TRANSFER (the ROW-LOCK / status guard) — pay() twice → ONE transfer ─────────────
it('never transfers twice — a second pay() on the same payout is refused', function () {
    $s = seedScenario(1);
    $owner = connectedOwner($s, 0, 'acct_owner_a');
    $payout = pendingPayout($s, $owner, 0);

    app(OwnerPayoutService::class)->pay($payout, connectRail());   // → processing, one transfer

    // A second pay() (a double-click, a webhook racing a sync callback) sees a non-pending row → refuses.
    expect(fn () => app(OwnerPayoutService::class)->pay($payout->fresh(), connectRail()))
        ->toThrow(RuntimeException::class);

    expect(app(StripeClientInterface::class)->transfers)->toHaveCount(1);   // 🔒 exactly ONE transfer
});

// ── 3. 🔒 NO DOUBLE-TRANSFER (the IDEMPOTENCY-KEY layer) — proven to double WITHOUT, once WITH ───────
it('creates one transfer for a repeated idempotency key, two without one', function () {
    /** @var FakeStripeClient $stripe */
    $stripe = app(StripeClientInterface::class);

    // WITHOUT a key — two calls create TWO transfers (the guard is load-bearing).
    $stripe->createTransfer(17000, 'EUR', 'acct_x');
    $stripe->createTransfer(17000, 'EUR', 'acct_x');
    expect($stripe->transfers)->toHaveCount(2);

    // WITH the same deterministic key — a retry returns the ORIGINAL transfer (no second money movement).
    $key = StripeIdempotency::keyFor('owner_payout', 'payout_123');
    $first = $stripe->createTransfer(17000, 'EUR', 'acct_x', [], $key);
    $again = $stripe->createTransfer(17000, 'EUR', 'acct_x', [], $key);
    expect($again->id)->toBe($first->id);
    expect($stripe->transfers)->toHaveCount(3);   // 2 (no key) + 1 (keyed) — the retry added none
});

// ── 4. 🔒 CORRECT OWNER ONLY — a payout transfers to ITS owner's account, never another's ───────────
it('transfers only to the payout’s own owner, never another owner', function () {
    $s = seedScenario(2);
    $ownerA = connectedOwner($s, 0, 'acct_owner_a');
    connectedOwner($s, 1, 'acct_owner_b');
    $payoutA = pendingPayout($s, $ownerA, 0);

    app(OwnerPayoutService::class)->pay($payoutA, connectRail());

    $transfers = app(StripeClientInterface::class)->transfers;
    expect($transfers)->toHaveCount(1);
    expect($transfers[0]['destination'])->toBe('acct_owner_a')      // 🔒 A's account…
        ->and($transfers[0]['destination'])->not->toBe('acct_owner_b');   // …never B's
});

// ── 5. 🔒 THE CONNECT WEBHOOK confirms processing→paid, exactly once (idempotent), separate secret ──
it('confirms the payout paid on the transfer.created connect webhook, idempotently', function () {
    $s = seedScenario(1);
    $owner = connectedOwner($s, 0, 'acct_owner_a');
    $payout = pendingPayout($s, $owner, 0);
    $paid = app(OwnerPayoutService::class)->pay($payout, connectRail());
    $transferId = (string) $paid->external_ref;

    // The Connect webhook (separate secret) confirms the transfer → paid + statement paid.
    postConnectWebhook('transfer.created', ['object' => 'transfer', 'id' => $transferId, 'destination' => 'acct_owner_a'], eventId: 'evt_tr_1')->assertStatus(202);
    expect($payout->fresh()->status)->toBe('paid');
    expect(OwnerStatement::query()->whereKey($payout->owner_statement_id)->value('status'))->toBe('paid');

    // A re-delivered event → duplicate (processed once); still paid, nothing double-confirmed.
    postConnectWebhook('transfer.created', ['object' => 'transfer', 'id' => $transferId], eventId: 'evt_tr_1')
        ->assertStatus(202)->assertJson(['status' => 'duplicate']);
    expect($payout->fresh()->status)->toBe('paid');
});

// ── 6. ⛔ REFUSES to pay an owner who hasn't connected / been enabled (no transfer, honest) ──────────
it('refuses to transfer to an un-connected owner', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $owner->update(['tax_id' => 'PT500000000']);   // tax on file, but NO connected account
    $payout = pendingPayout($s, $owner, 0);

    $result = app(OwnerPayoutService::class)->pay($payout, connectRail());

    expect($result->status)->toBe('failed');
    expect(app(StripeClientInterface::class)->transfers)->toBeEmpty();   // 🔒 no money moved
});

// ── 7. 🔒 A FORGED connect webhook is rejected (verified against the SEPARATE Connect secret) ────────
it('rejects a forged connect webhook', function () {
    $raw = json_encode(['id' => 'evt_forged', 'type' => 'account.updated', 'data' => ['object' => ['id' => 'acct_x', 'payouts_enabled' => true]]], JSON_THROW_ON_ERROR);
    test()->call('POST', '/webhooks/stripe/connect', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef', 'CONTENT_TYPE' => 'application/json'], $raw)
        ->assertStatus(400);
});
