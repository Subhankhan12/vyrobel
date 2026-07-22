<?php

use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
use App\Payments\Stripe\StripeSafetyGuard;

// PHASE 8 · live Stripe · SG0 — the shared foundation, proven BEFORE any money path: no live key in the build,
// a deterministic sending-side idempotency key (a repeated key returns the original — no double-charge), and
// the two-layer idempotency. (The webhook signature-verify + receiving-side event.id idempotency are already
// proven in StripePaymentTest: a forged signature → 400, N redeliveries → processed once.)

// ── 1. 🔒 NO LIVE KEY IN THE BUILD — a live key outside production forces the fake client ────────────
it('refuses a live Stripe key outside production and honours it only in production', function () {
    // A LIVE key is recognised…
    expect(StripeSafetyGuard::isLiveKey('sk_live_abc'))->toBeTrue();
    expect(StripeSafetyGuard::isLiveKey('rk_live_abc'))->toBeTrue();
    expect(StripeSafetyGuard::isLiveKey('sk_test_abc'))->toBeFalse();
    expect(StripeSafetyGuard::isLiveKey(null))->toBeFalse();

    // Outside production a live key is REFUSED → the fake client is forced (no real charge possible).
    expect(StripeSafetyGuard::forceFakeOutsideProduction('local', false, 'sk_live_abc'))->toBeTrue();
    expect(StripeSafetyGuard::forceFakeOutsideProduction('testing', false, 'sk_live_abc'))->toBeTrue();

    // A TEST-mode key outside production is allowed (charges nothing real — the manual live-test checkpoint).
    expect(StripeSafetyGuard::forceFakeOutsideProduction('local', false, 'sk_test_abc'))->toBeFalse();

    // Only production may use a live key.
    expect(StripeSafetyGuard::forceFakeOutsideProduction('production', false, 'sk_live_abc'))->toBeFalse();

    // Already faking → always fake, regardless of key/env.
    expect(StripeSafetyGuard::forceFakeOutsideProduction('production', true, 'sk_live_abc'))->toBeTrue();
});

// ── 2. 🔒 THE RUNNING TEST SUITE USES THE FAKE CLIENT (no network, no key can charge) ────────────────
it('binds the fake Stripe client in the test suite', function () {
    expect((bool) config('services.stripe.fake'))->toBeTrue();   // STRIPE_FAKE stays on in the test env
    // The real proof: the container binds the NON-networking fake — no test can reach live Stripe.
    expect(app(StripeClientInterface::class))->toBeInstanceOf(FakeStripeClient::class);
});

// ── 3. 🔒 THE SENDING-SIDE IDEMPOTENCY KEY IS PURE + DETERMINISTIC ──────────────────────────────────
it('generates a deterministic idempotency key for a logical operation', function () {
    // Same operation + ref → same key (a retry returns the original object).
    expect(StripeIdempotency::keyFor('folio_charge', 'pay_123'))->toBe('folio_charge:pay_123');
    expect(StripeIdempotency::keyFor('folio_charge', 'pay_123'))
        ->toBe(StripeIdempotency::keyFor('folio_charge', 'pay_123'));

    // Different operation OR ref → different key (two legitimate ops don't collide).
    expect(StripeIdempotency::keyFor('folio_charge', 'pay_123'))
        ->not->toBe(StripeIdempotency::keyFor('folio_charge', 'pay_456'));
    expect(StripeIdempotency::keyFor('owner_payout', 'pay_123'))
        ->not->toBe(StripeIdempotency::keyFor('folio_charge', 'pay_123'));
});

// ── 4. 🔒 A REPEATED IDEMPOTENCY KEY RETURNS THE ORIGINAL CHARGE — no double-charge (structural) ─────
it('returns the original intent for a repeated idempotency key — never a second charge', function () {
    $stripe = new FakeStripeClient('whsec_test');
    $key = StripeIdempotency::keyFor('folio_charge', 'pay_123');

    $first = $stripe->createPaymentIntent(10000, 'EUR', 'pm_card_visa', capture: true, idempotencyKey: $key);
    $second = $stripe->createPaymentIntent(10000, 'EUR', 'pm_card_visa', capture: true, idempotencyKey: $key);

    // Same key → the SAME intent, and only ONE intent was ever created.
    expect($second->id)->toBe($first->id);
    expect($stripe->intents)->toHaveCount(1);

    // A DIFFERENT key (a genuinely separate payment) creates a second intent.
    $other = $stripe->createPaymentIntent(10000, 'EUR', 'pm_card_visa', capture: true, idempotencyKey: StripeIdempotency::keyFor('folio_charge', 'pay_999'));
    expect($other->id)->not->toBe($first->id);
    expect($stripe->intents)->toHaveCount(2);
});
