<?php

use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Services\PayoutRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// THE AUTOMATED PAYOUT ENGINE driving Stripe Connect — Phase 3 (TEST MODE).
// ⚠️ TEST-MODE ONLY: the FakeStripeClient simulates transfers (no real money); PayoutEngineMode = 'test'.
// The engine uses the config-bound rail — here `stripe_connect` — so pay() initiates a real test transfer
// (processing) and the Connect webhook confirms it → paid. Reuses the certified rail + webhook + guards.

beforeEach(function () {
    // Drive the engine through the Stripe Connect rail (the fake client stays bound — no real money).
    config(['services.payouts.rail' => 'stripe_connect']);
});

/** An owner with tax details + a connected, enabled Stripe account. */
function engineConnectedOwner(array $s, int $unitIndex, string $accountId): Owner
{
    $owner = ownUnit($s['units'][$unitIndex]);
    $owner->update(['tax_id' => 'PT500000000', 'stripe_connect_account_id' => $accountId, 'stripe_connect_payouts_enabled' => true]);

    return $owner->fresh();
}

/** A finalized statement (net 17000) for an owner. */
function engineFinalized(array $s, Owner $owner, int $unitIndex): void
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
}

/** POST a signed Stripe CONNECT webhook (the separate Connect secret). */
function postEngineConnectWebhook(string $type, array $object, ?string $eventId = null): TestResponse
{
    $event = ['id' => $eventId ?? 'evt_'.Str::lower(Str::random(18)), 'type' => $type, 'data' => ['object' => $object]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $sig = FakeStripeClient::signature($raw, (string) config('services.stripe.connect_webhook_secret'));

    return test()->call('POST', '/webhooks/stripe/connect', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw);
}

// ── 1. END-TO-END — a run initiates a test transfer → processing → webhook confirms → paid ─────────
it('drives a real test transfer through the engine, confirmed paid by the Connect webhook', function () {
    $s = seedScenario(1);
    $owner = engineConnectedOwner($s, 0, 'acct_engine_a');
    engineFinalized($s, $owner, 0);

    $run = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL);

    // The engine initiated a REAL (test-mode) transfer to THIS owner's account; the payout is processing.
    $payout = Payout::query()->where('owner_id', $owner->id)->firstOrFail();
    expect($payout->status)->toBe('processing')
        ->and($payout->external_ref)->toStartWith('tr_')
        ->and($payout->statement->status)->toBe('finalized')   // not paid until the webhook confirms
        ->and($run->mode)->toBe('test');                       // ⚠️ TEST MODE — no real money

    $transfers = app(StripeClientInterface::class)->transfers;
    expect($transfers)->toHaveCount(1)
        ->and($transfers[0]['destination'])->toBe('acct_engine_a')
        ->and($transfers[0]['amount_minor'])->toBe(17000);

    // The Connect webhook confirms processing → paid (+ statement paid).
    postEngineConnectWebhook('transfer.created', ['object' => 'transfer', 'id' => (string) $payout->external_ref, 'destination' => 'acct_engine_a'], 'evt_eng_1')->assertStatus(202);
    expect($payout->fresh()->status)->toBe('paid')
        ->and(OwnerStatement::query()->whereKey($payout->owner_statement_id)->value('status'))->toBe('paid');
});

// ── 2. FAILURE — an un-connected owner's payout fails (no transfer); retry-able once connected ─────
it('fails a payout for an un-connected owner (no money moves), then pays it once connected', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $owner->update(['tax_id' => 'PT500000000']);   // tax on file but NO connected account
    engineFinalized($s, $owner, 0);

    $run = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL, null, 'run:fail-1');

    expect($run->payouts_failed)->toBe(1)
        ->and($run->payouts_paid)->toBe(0)
        ->and(Payout::query()->where('owner_id', $owner->id)->where('status', 'failed')->count())->toBe(1)
        ->and(app(StripeClientInterface::class)->transfers)->toBeEmpty();   // 🔒 no money moved

    // Owner connects their account → a later run retries (a new payout) → a real transfer, processing.
    $owner->update(['stripe_connect_account_id' => 'acct_engine_a', 'stripe_connect_payouts_enabled' => true]);
    app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL, null, 'run:fail-2');

    expect(Payout::query()->where('owner_id', $owner->id)->where('status', 'processing')->count())->toBe(1)
        ->and(app(StripeClientInterface::class)->transfers)->toHaveCount(1);   // exactly one real transfer
});

// ── 3. IDEMPOTENT — a same-key re-run initiates no second transfer; webhook replay is safe ────────
it('never double-transfers: a same-key re-run and a webhook replay are both safe', function () {
    $s = seedScenario(1);
    $owner = engineConnectedOwner($s, 0, 'acct_engine_a');
    engineFinalized($s, $owner, 0);

    $engine = app(PayoutRunService::class);
    $engine->run(PayoutRun::TRIGGER_SCHEDULED, null, 'scheduled:2026-09-03');
    $engine->run(PayoutRun::TRIGGER_SCHEDULED, null, 'scheduled:2026-09-03');   // same key → resume, no-op

    expect(Payout::query()->where('owner_id', $owner->id)->count())->toBe(1)
        ->and(app(StripeClientInterface::class)->transfers)->toHaveCount(1);   // 🔒 exactly one transfer

    $payout = Payout::query()->where('owner_id', $owner->id)->firstOrFail();

    // Webhook replay (same event id) → confirmed once, still paid, nothing double-confirmed.
    postEngineConnectWebhook('transfer.created', ['object' => 'transfer', 'id' => (string) $payout->external_ref], 'evt_eng_dup')->assertStatus(202);
    postEngineConnectWebhook('transfer.created', ['object' => 'transfer', 'id' => (string) $payout->external_ref], 'evt_eng_dup')->assertStatus(202)->assertJson(['status' => 'duplicate']);
    expect($payout->fresh()->status)->toBe('paid');
});
