<?php

declare(strict_types=1);

use App\Models\CompanyAccount;
use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Services\CompanyAccountService;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Owner\OwnerCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * ⚠️ COMPANY ACCOUNTS — the debt MOVES, it does not vanish; and billing is NOT collection.
 *
 * ⚠️ The second is the one that matters most: `OwnerCollection::reservationIsCollected()` is literally
 * `FolioTotals::balance_minor <= 0`, so anything that zeroed the folio would make the owner payable for
 * money nobody has received.
 */
function billedStay(int $chargeMinor = 20000, int $paidMinor = 0): array
{
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, $chargeMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    if ($paidMinor > 0) {
        app(FolioService::class)->recordPayment($folio, Money::of($paidMinor, 'EUR'));
    }

    $account = CompanyAccount::query()->create([
        'organization_id' => $reservation->organization_id,
        'name' => 'Northwind Ltd',
    ]);

    return [$s, $owner, $reservation, $folio->refresh(), $account];
}

function ledgerSumAll(): int
{
    return (int) OwnerLedgerEntry::query()->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
}

it('⚠️ THE DEBT DOES NOT VANISH — the folio still shows the balance as owed', function () {
    [, , , $folio, $account] = billedStay();

    $before = (int) FolioTotals::for($folio->load(['items', 'payments']))['balance_minor'];
    expect($before)->toBe(20000);

    $folio = app(CompanyAccountService::class)->bill($folio, $account);

    // ⚠️ IDENTICAL. Nothing was posted; only the attribution changed.
    expect((int) FolioTotals::for($folio->load(['items', 'payments']))['balance_minor'])->toBe($before)
        ->and($folio->billed_to_account_id)->toBe((string) $account->getKey());
});

it('⚠️⚠️ BILLING IS NOT COLLECTION — the gate still says uncollected', function () {
    [, , $reservation, $folio, $account] = billedStay();

    expect(OwnerCollection::reservationIsCollected((string) $reservation->getKey()))->toBeFalse();

    app(CompanyAccountService::class)->bill($folio, $account);

    // ⚠️ Still false. If this flipped, the payout engine would release money nobody has received.
    expect(OwnerCollection::reservationIsCollected((string) $reservation->getKey()))->toBeFalse();
});

it('⚠️⚠️ THE OWNER LEDGER MOVES BY EXACTLY ZERO', function () {
    [, , , $folio, $account] = billedStay();

    $rowsBefore = OwnerLedgerEntry::query()->count();
    $sumBefore = ledgerSumAll();

    app(CompanyAccountService::class)->bill($folio, $account);

    expect(OwnerLedgerEntry::query()->count())->toBe($rowsBefore)
        ->and(ledgerSumAll())->toBe($sumBefore);
});

it('when the company PAYS, the ordinary path makes the owner payable — no new mechanism', function () {
    [, , $reservation, $folio, $account] = billedStay();
    app(CompanyAccountService::class)->bill($folio, $account);

    app(FolioService::class)->recordPayment($folio->refresh(), Money::of(20000, 'EUR'));

    expect(OwnerCollection::reservationIsCollected((string) $reservation->getKey()))->toBeTrue();
});

it('⚠️ WHAT DOES THIS COMPANY OWE US — answered from the real folio balances', function () {
    [, , , $folio, $account] = billedStay();
    app(CompanyAccountService::class)->bill($folio, $account);

    $out = app(CompanyAccountService::class)->outstandingFor($account->refresh());

    expect($out['outstanding_minor'])->toBe(20000)
        ->and($out['folios'])->toHaveCount(1)
        ->and($out['folios'][0]['balance_minor'])->toBe(20000);
});

it('a settled folio cannot be billed — an account owes only REAL balances', function () {
    [, , , $folio, $account] = billedStay(20000, 20000);

    expect(fn () => app(CompanyAccountService::class)->bill($folio, $account))
        ->toThrow(InvalidArgumentException::class);
});

it('⚠️ CROSS-ORG is refused at the boundary', function () {
    [, , , $folio] = billedStay();
    $other = CompanyAccount::query()->create([
        'organization_id' => (string) Str::ulid(),
        'name' => 'Someone else',
    ]);

    expect(fn () => app(CompanyAccountService::class)->bill($folio, $other))
        ->toThrow(InvalidArgumentException::class);
});

it('UN-BILLING is an audited transition that returns the debt to the guest', function () {
    [, , , $folio, $account] = billedStay();
    $svc = app(CompanyAccountService::class);
    $folio = $svc->bill($folio, $account);

    $folio = $svc->unbill($folio);

    expect($folio->billed_to_account_id)->toBeNull()
        ->and((int) FolioTotals::for($folio->load(['items', 'payments']))['balance_minor'])->toBe(20000)
        ->and(app(CompanyAccountService::class)->outstandingFor($account->refresh())['outstanding_minor'])->toBe(0);
});
