<?php

declare(strict_types=1);

use App\Exceptions\Folio\OutstandingBalanceException;
use App\Models\CompanyAccount;
use App\Models\Folio;
use App\Services\CompanyAccountService;
use App\Services\FolioService;
use App\Services\FrontDeskService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Owner\OwnerCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️ CHECKOUT IS REFUSED WHILE MONEY IS OWED — guarded at the single write path, reading the SAME
 * `FolioTotals` the folio screen renders, with ONE exemption: a folio billed to a company account.
 *
 * ⚠️⚠️ That exemption is the only thing that makes this a data-quality guard rather than a ban on
 * corporate business.
 */
function blockStay(int $chargeMinor, int $paidMinor): array
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, $chargeMinor);

    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    if ($paidMinor > 0) {
        app(FolioService::class)->recordPayment($folio, Money::of($paidMinor, 'EUR'));
    }

    $reservation->forceFill(['status' => 'checked_in'])->save();

    return [$s, $reservation->refresh(), $folio->refresh()];
}

it('⚠️ BALANCE OWED → checkout is REFUSED and nothing moves', function () {
    [, $reservation] = blockStay(20000, 5000);

    expect(fn () => app(FrontDeskService::class)->checkOut($reservation))
        ->toThrow(OutstandingBalanceException::class);

    expect($reservation->refresh()->status)->toBe('checked_in');
});

it('ZERO balance checks out cleanly', function () {
    [, $reservation] = blockStay(20000, 20000);
    app(FrontDeskService::class)->checkOut($reservation);
    expect($reservation->refresh()->status)->toBe('checked_out');
});

it('⚠️ A CREDIT balance checks out cleanly — the guest overpaid, they are not at fault', function () {
    [, $reservation] = blockStay(20000, 25000);
    app(FrontDeskService::class)->checkOut($reservation);
    expect($reservation->refresh()->status)->toBe('checked_out');
});

it('⚠️⚠️ THE EXEMPTION — a folio BILLED to a company account checks out with the balance STILL OWED', function () {
    [, $reservation, $folio] = blockStay(20000, 0);

    $account = CompanyAccount::query()->create([
        'organization_id' => $reservation->organization_id,
        'name' => 'Northwind Ltd',
    ]);
    app(CompanyAccountService::class)->bill($folio, $account);

    app(FrontDeskService::class)->checkOut($reservation->refresh());

    expect($reservation->refresh()->status)->toBe('checked_out')
        // ⚠️ The debt did NOT vanish, and it is STILL not collected — which is what keeps the payout
        //    engine from releasing owner money for cash nobody has received.
        ->and(OwnerCollection::reservationIsCollected((string) $reservation->getKey()))->toBeFalse();
});

it('⚠️ THE SAME-COMPUTATION PROOF — the guard follows the screen’s balance', function () {
    // A folio the screen would read as owed is refused; the guard has no independent notion of "owed".
    [, $reservation, $folio] = blockStay(20000, 19999);   // 1 cent owed

    expect(fn () => app(FrontDeskService::class)->checkOut($reservation))
        ->toThrow(OutstandingBalanceException::class);

    // Settle that last cent through the ordinary path and the same guard now passes.
    app(FolioService::class)->recordPayment($folio->refresh(), Money::of(1, 'EUR'));
    app(FrontDeskService::class)->checkOut($reservation->refresh());

    expect($reservation->refresh()->status)->toBe('checked_out');
});

it('⚠️ THE MESSAGE names the amount AND billing to an account as a way forward', function () {
    [, $reservation] = blockStay(20000, 5000);

    try {
        app(FrontDeskService::class)->checkOut($reservation);
        $this->fail('checkout should have been refused');
    } catch (OutstandingBalanceException $e) {
        expect($e->getMessage())->toContain('150.00')
            ->and($e->getMessage())->toContain('company account')
            ->and($e->balanceMinor)->toBe(15000);
    }
});

it('the operator route surfaces the refusal rather than 500ing', function () {
    [$s, $reservation] = blockStay(20000, 5000);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post(route('app.front-desk.check-out', $reservation->id))
        ->assertRedirect();

    expect($reservation->refresh()->status)->toBe('checked_in');
});

it('⚠️ THE SURFACE — billing is refused at the boundary for a role without folios.manage', function () {
    [$s, $reservation] = blockStay(20000, 0);
    $account = CompanyAccount::query()->create([
        'organization_id' => $reservation->organization_id, 'name' => 'Northwind Ltd',
    ]);

    $this->actingAs(operatorUser($s, [], 'Housekeeping'))
        ->post(route('app.reservations.bill-to-account', $reservation->id), [
            'company_account_id' => (string) $account->getKey(),
        ])->assertForbidden();

    expect(Folio::query()->where('reservation_id', $reservation->getKey())->value('billed_to_account_id'))
        ->toBeNull();
});

it('⚠️ THE SURFACE — a permitted role bills, and the balance is untouched', function () {
    [$s, $reservation, $folio] = blockStay(20000, 0);
    $account = CompanyAccount::query()->create([
        'organization_id' => $reservation->organization_id, 'name' => 'Northwind Ltd',
    ]);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post(route('app.reservations.bill-to-account', $reservation->id), [
            'company_account_id' => (string) $account->getKey(),
        ])->assertRedirect();

    $folio = $folio->refresh()->load(['items', 'payments']);
    expect($folio->billed_to_account_id)->toBe((string) $account->getKey())
        ->and((int) FolioTotals::for($folio)['balance_minor'])->toBe(20000);
});
