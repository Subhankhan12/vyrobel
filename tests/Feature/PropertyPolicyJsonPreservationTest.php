<?php

declare(strict_types=1);

use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Support\Reservations\CancellationPolicy;
use Carbon\CarbonImmutable;

/**
 * REGRESSION — the wireframe-parity sweep's HIGH-1.
 *
 * `SettingsActionController::updatePolicies()` built `['tier' => …]` and handed it to
 * `SettingsService::updatePolicies()`, which did `fill()->save()`. On an array-cast column `fill()` REPLACES,
 * so one save of the Policies card destroyed `cancellation.free_until_days` — the only key any business logic
 * reads. `CancellationPolicy::freeUntil()` then returned null and `isRefundable()` returned FALSE for every
 * reservation at that property, and the free-cancellation promise vanished from the confirmation email, the
 * guest reservation page and the storefront.
 *
 * The card's tier radio carries a checked default, so the payload was submitted on EVERY save — the loss did
 * not require the operator to touch cancellation at all.
 *
 * The blind spot: the pre-existing test asserted the tier persisted and never that its siblings survived.
 * These tests assert the SURVIVAL of the sibling and the CONSEQUENCE (refund eligibility), not just the write.
 */
it('keeps free_until_days when the policies card is saved without touching it', function () {
    $s = seedScenario(1);

    PropertyPolicy::query()->updateOrCreate(
        ['property_id' => $s['property']->id],
        ['cancellation' => ['type' => 'flexible', 'free_until_days' => 2]],
    );

    // The exact payload the card posts when the operator edits only pets — the tier radio always submits.
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post('/app/settings/policies', [
        'pet' => 'allowed',
        'smoking' => 'not_allowed',
        'min_stay' => '1',
        'cancellation_type' => 'flexible',
    ])->assertRedirect();

    $cancellation = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail()->cancellation;

    expect($cancellation['free_until_days'] ?? null)->toBe(2)   // the sibling SURVIVES
        ->and($cancellation['type'] ?? null)->toBe('flexible');
});

it('keeps a reservation refundable across a policies save', function () {
    $s = seedScenario(1);

    PropertyPolicy::query()->updateOrCreate(
        ['property_id' => $s['property']->id],
        ['cancellation' => ['type' => 'flexible', 'free_until_days' => 2]],
    );

    /** @var Reservation $reservation */
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $wellBefore = CarbonImmutable::parse($reservation->check_in)->subDays(10);

    expect(CancellationPolicy::isRefundable($reservation, $wellBefore))->toBeTrue();

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post('/app/settings/policies', [
        'pet' => 'allowed',
        'smoking' => 'not_allowed',
        'min_stay' => '1',
        'cancellation_type' => 'strict',
    ])->assertRedirect();

    // The consequence, not just the column: a save must not silently make every booking non-refundable.
    expect(CancellationPolicy::isRefundable($reservation->fresh(), $wellBefore))->toBeTrue();
});

it('lets the operator set and clear the free-cancellation window', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Property Owner');

    $this->actingAs($user)->post('/app/settings/policies', [
        'pet' => 'allowed',
        'smoking' => 'not_allowed',
        'min_stay' => '1',
        'cancellation_type' => 'flexible',
        'free_until_days' => '5',
    ])->assertRedirect();

    expect(PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail()->cancellation['free_until_days'])->toBe(5);

    // Blank means "no free-cancellation window" — an honest null, never a defaulted number.
    $this->actingAs($user)->post('/app/settings/policies', [
        'pet' => 'allowed',
        'smoking' => 'not_allowed',
        'min_stay' => '1',
        'cancellation_type' => 'flexible',
        'free_until_days' => '',
    ])->assertRedirect();

    $cancellation = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail()->cancellation;

    expect($cancellation['free_until_days'])->toBeNull()
        ->and($cancellation['type'])->toBe('flexible');   // clearing one key still preserves the other
});

it('merges the deposit column instead of replacing it', function () {
    $s = seedScenario(1);

    // A sibling key on the OTHER json policy column — the same class of loss.
    PropertyPolicy::query()->updateOrCreate(
        ['property_id' => $s['property']->id],
        ['deposit' => ['required' => false, 'percent' => 30]],
    );

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post('/app/settings/policies', [
        'pet' => 'allowed',
        'smoking' => 'not_allowed',
        'min_stay' => '1',
        'deposit_required' => '1',
    ])->assertRedirect();

    $deposit = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail()->deposit;

    expect($deposit['required'])->toBeTrue()
        ->and($deposit['percent'] ?? null)->toBe(30);
});
