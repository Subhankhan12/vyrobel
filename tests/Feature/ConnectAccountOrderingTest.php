<?php

declare(strict_types=1);

use App\Models\Owner;
use App\Models\WebhookEvent;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ A LATE `account.updated` MUST NOT RE-ENABLE PAYOUTS STRIPE HAS DISABLED. THIS IS MONEY LEAVING.
 *
 * `owners.stripe_connect_payouts_enabled` used to be written straight from the event payload, so an
 * out-of-order delivery restored a flag Stripe had already turned off — and Stripe turns it off for failed
 * verification, a dispute, sanctions screening. The account is now READ BACK from Stripe, so two events in
 * any order write the same current answer and ordering stops mattering.
 *
 * ⚠️ THE TWO DIRECTIONS ARE NOT SYMMETRIC. An ENABLE needs a confirmed live read; a DISABLE may apply from
 * the payload, which is Stripe's own signed statement, because that errs in the recoverable direction.
 *
 * ⚠️ Proven with two DISTINCT correctly-signed events delivered BACKWARDS — never a duplicate, which the
 * `webhook_events` unique constraint already handles and which would prove the wrong thing. Nothing here
 * touches `artisan serve`, the network, or real money.
 */
uses(RefreshDatabase::class);

/** POST a signed Stripe CONNECT webhook. Self-contained so this file runs alone as well as in company. */
function caWebhook(string $type, array $object, ?string $eventId = null): TestResponse
{
    $event = ['id' => $eventId ?? 'evt_'.Str::lower(Str::random(18)), 'type' => $type, 'data' => ['object' => $object]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/stripe/connect', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.connect_webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

/** An owner connected to `acct_ordering`, with payouts CURRENTLY ENABLED in our database. */
function caOwner(array $s): Owner
{
    $owner = ownUnit($s['units'][0]);
    $owner->update(['stripe_connect_account_id' => 'acct_ordering', 'stripe_connect_payouts_enabled' => true]);
    $owner = $owner->fresh();

    Assert::assertTrue((bool) $owner->stripe_connect_payouts_enabled, 'the fixture did not start ENABLED');

    return $owner;
}

/** What Stripe reports for the account right now. */
function caStripeSays(bool $payoutsEnabled): void
{
    app(StripeClientInterface::class)->connectAccounts['acct_ordering'] = $payoutsEnabled;
}

it('⚠️⚠️ keeps payouts DISABLED when two events arrive BACKWARDS and Stripe says disabled', function () {
    $s = seedScenario(1);
    $owner = caOwner($s);

    // Stripe has since DISABLED the account — the current truth.
    caStripeSays(false);

    // ⚠️ TWO DISTINCT EVENTS, DELIVERED IN THE WRONG ORDER: the NEWER (disabled) first, then the OLDER
    //    (enabled) arriving late. Not a duplicate — that case is already handled and would prove nothing.
    caWebhook('account.updated', ['id' => 'acct_ordering', 'payouts_enabled' => false], 'evt_ca_newer')->assertSuccessful();
    caWebhook('account.updated', ['id' => 'acct_ordering', 'payouts_enabled' => true], 'evt_ca_older')->assertSuccessful();

    // Without the read-back the LATE event wins and payouts are re-enabled on a rail Stripe has closed.
    expect((bool) $owner->fresh()->stripe_connect_payouts_enabled)->toBeFalse();

    // ⚠️ NON-VACUOUS: the flag genuinely MOVED from its starting true, and BOTH events were processed rather
    //    than one being quietly dropped.
    $rows = WebhookEvent::query()->whereIn('external_event_id', ['evt_ca_newer', 'evt_ca_older'])->get();
    expect($rows)->toHaveCount(2)->and($rows->pluck('status')->unique()->all())->toBe(['processed']);
});

it('⚠️⚠️ REFUSES to enable from the payload when the account cannot be read — and lets Stripe RETRY', function () {
    $s = seedScenario(1);
    $owner = caOwner($s);
    $owner->update(['stripe_connect_payouts_enabled' => false]);   // Stripe disabled it; our row agrees

    // The account cannot be read back (an outage, or Stripe no longer knows it).
    $throwing = Mockery::mock(app(StripeClientInterface::class))->makePartial();
    $throwing->shouldReceive('retrieveConnectAccount')->andThrow(new RuntimeException('stripe unreachable'));
    app()->instance(StripeClientInterface::class, $throwing);

    Log::spy();

    // ⚠️ A payload claiming ENABLED, with no way to confirm it. Enabling here would send money at a rail
    //    Stripe may have closed — so the delivery is allowed to FAIL and Stripe retries.
    caWebhook('account.updated', ['id' => 'acct_ordering', 'payouts_enabled' => true], 'evt_ca_unverified')
        ->assertStatus(500);

    expect((bool) $owner->fresh()->stripe_connect_payouts_enabled)->toBeFalse();

    // ⚠️⚠️ AND THE EVENT IS NOT CONSUMED. `once()` claims the `webhook_events` row BEFORE running its
    //    closure, so a throw inside it would leave the row claimed and Stripe's retry would read
    //    "duplicate" — losing the event permanently. Failing BEFORE the claim is what keeps the retry alive.
    expect(WebhookEvent::query()->where('external_event_id', 'evt_ca_unverified')->count())->toBe(0);

    Log::shouldHaveReceived('warning')->once()
        ->withArgs(fn (string $m): bool => str_contains($m, 'refusing to ENABLE'));
});

it('⚠️ APPLIES a payload DISABLE when the account cannot be read — the recoverable direction', function () {
    $s = seedScenario(1);
    $owner = caOwner($s);

    $throwing = Mockery::mock(app(StripeClientInterface::class))->makePartial();
    $throwing->shouldReceive('retrieveConnectAccount')->andThrow(new RuntimeException('stripe unreachable'));
    app()->instance(StripeClientInterface::class, $throwing);

    Log::spy();

    // ⚠️ Stripe's own SIGNED statement says disabled. Acting on it early errs towards NOT sending money,
    //    which delays an owner at worst — and the next confirmed read corrects it either way.
    caWebhook('account.updated', ['id' => 'acct_ordering', 'payouts_enabled' => false], 'evt_ca_disable')
        ->assertSuccessful();

    expect((bool) $owner->fresh()->stripe_connect_payouts_enabled)->toBeFalse();

    Log::shouldHaveReceived('warning')->once()
        ->withArgs(fn (string $m): bool => str_contains($m, 'applying the payload'));
});

it('⚠️ the fake cannot invent an ENABLED account — an unknown account reads as disabled', function () {
    $s = seedScenario(1);
    $owner = caOwner($s);

    // ⚠️ `acct_ordering` is deliberately NOT registered. `FakeChannexClient` defaults to ON because that is
    //    right for a channel manager; on a money rail the same default would be catastrophic, so this fake
    //    answers `false` for everything it has not been told about.
    caWebhook('account.updated', ['id' => 'acct_ordering', 'payouts_enabled' => true])->assertSuccessful();

    expect((bool) $owner->fresh()->stripe_connect_payouts_enabled)->toBeFalse();
});

it('⚠️ re-delivery of the SAME connect event is still idempotent', function () {
    $s = seedScenario(1);
    $owner = caOwner($s);
    caStripeSays(false);

    caWebhook('account.updated', ['id' => 'acct_ordering', 'payouts_enabled' => false], 'evt_ca_same')->assertSuccessful();
    caWebhook('account.updated', ['id' => 'acct_ordering', 'payouts_enabled' => false], 'evt_ca_same')->assertSuccessful();

    expect(WebhookEvent::query()->where('external_event_id', 'evt_ca_same')->count())->toBe(1)
        ->and((bool) $owner->fresh()->stripe_connect_payouts_enabled)->toBeFalse();
});
