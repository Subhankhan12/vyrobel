<?php

use App\Exceptions\Guest\WalletOverdrawException;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\GuestCreditEntry;
use App\Models\PromoCode;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Services\GuestWalletService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// GUEST WALLET (Phase 4c · Sub-gate 2) — the careful one. Proven: the wallet is an APPEND-ONLY ledger with the
// balance DERIVED (no balance column); it can't overdraw (a debit beyond the spendable balance is rejected, and
// expired credit doesn't count); a promo code is single-use; applying credit to a booking is a real debit that
// reduces what's owed; reversing a credit appends a debit (the original is untouched); isolation A≠B.

if (! function_exists('walGuest')) {
    function walGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

if (! function_exists('wal')) {
    function wal(): GuestWalletService
    {
        return app(GuestWalletService::class);
    }
}

if (! function_exists('walBook')) {
    function walBook(array $s, Guest $guest, Unit $unit, int $chargeMinor = 30000, int $payMinor = 20000): Reservation
    {
        $res = app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => $chargeMinor, 'currency' => 'EUR']],
        );
        $folio = app(FolioService::class)->openFolio($res);
        app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of($chargeMinor, 'EUR'));
        if ($payMinor > 0) {
            app(FolioService::class)->recordPayment($folio, Money::of($payMinor, 'EUR'));
        }

        return $res;
    }
}

// ── APPEND-ONLY + DERIVED balance (no balance column) ─────────────────────────────────────────────
it('derives the balance from an append-only ledger with no balance column', function () {
    $s = seedScenario(1);
    $g = walGuest($s['guest'], 'ada@example.test');

    expect(Schema::hasColumn('guest_credit_ledger', 'balance'))->toBeFalse();   // no mutable balance, ever

    wal()->credit($g, GuestCreditEntry::TYPE_REFUND, 'Refund', Money::of(2500, 'EUR'));
    wal()->credit($g, GuestCreditEntry::TYPE_BIRTHDAY, 'Birthday', Money::of(1000, 'EUR'));
    expect(wal()->spendableMinor($g))->toBe(3500);           // Σ credits

    wal()->debit($g, GuestCreditEntry::TYPE_ADJUSTMENT, 'spend', Money::of(500, 'EUR'));
    expect(wal()->spendableMinor($g))->toBe(3000);           // − debit
});

// ── NO OVERDRAW (the crux) ────────────────────────────────────────────────────────────────────────
it('refuses a debit that would overdraw the wallet', function () {
    $s = seedScenario(1);
    $g = walGuest($s['guest'], 'ada@example.test');
    wal()->credit($g, GuestCreditEntry::TYPE_REFUND, 'Refund', Money::of(2000, 'EUR'));

    wal()->debit($g, GuestCreditEntry::TYPE_ADJUSTMENT, 'exact', Money::of(2000, 'EUR'));   // exactly the balance → ok
    expect(wal()->spendableMinor($g))->toBe(0);

    expect(fn () => wal()->debit($g, GuestCreditEntry::TYPE_ADJUSTMENT, 'over', Money::of(1, 'EUR')))
        ->toThrow(WalletOverdrawException::class);
    expect(GuestCreditEntry::query()->where('guest_id', $g->getKey())->where('description', 'over')->count())->toBe(0);   // nothing written
});

// ── EXPIRED credit is excluded from the spendable balance ─────────────────────────────────────────
it('excludes expired credit from the spendable balance', function () {
    $s = seedScenario(1);
    $g = walGuest($s['guest'], 'ada@example.test');

    wal()->credit($g, GuestCreditEntry::TYPE_REFUND, 'live', Money::of(5000, 'EUR'));
    wal()->credit($g, GuestCreditEntry::TYPE_BIRTHDAY, 'expired', Money::of(3000, 'EUR'), expiresAt: CarbonImmutable::now()->subDay());

    expect(wal()->spendableMinor($g))->toBe(5000);   // the €30 expired credit does not count
});

// ── A promo code is SINGLE-USE; invalid / already-used are refused honestly ───────────────────────
it('redeems a promo code once and refuses reuse or an invalid code', function () {
    $s = seedScenario(1);
    $g = walGuest($s['guest'], 'ada@example.test');
    PromoCode::create(['code' => 'SAVE10', 'amount_minor' => 1000, 'currency' => 'EUR', 'max_redemptions' => 1, 'is_active' => true]);

    $first = wal()->redeemCode($g, 'SAVE10');
    expect($first['ok'])->toBeTrue();
    expect(wal()->spendableMinor($g))->toBe(1000);

    $again = wal()->redeemCode($g, 'SAVE10');
    expect($again['ok'])->toBeFalse();
    expect($again['message'])->toContain('already');
    expect(wal()->spendableMinor($g))->toBe(1000);          // no second credit

    $invalid = wal()->redeemCode($g, 'NOPE');
    expect($invalid['ok'])->toBeFalse();
    expect($invalid['message'])->toContain('valid');
});

// ── APPLY TO BOOKING is a real debit that reduces what's owed (integer money) ─────────────────────
it('applies credit to a booking as a debit that reduces the balance owed', function () {
    $s = seedScenario(1);
    $g = walGuest($s['guest'], 'ada@example.test');
    $res = walBook($s, $g, $s['units'][0], chargeMinor: 30000, payMinor: 20000);   // €100 owed
    wal()->credit($g, GuestCreditEntry::TYPE_REFUND, 'Refund', Money::of(5000, 'EUR'));   // €50

    $folio = Folio::query()->where('reservation_id', $res->getKey())->with(['items', 'payments'])->first();
    expect(FolioTotals::for($folio)['balance_minor'])->toBe(10000);

    wal()->applyToBooking($g, $res, Money::of(3000, 'EUR'));   // apply €30
    expect(wal()->spendableMinor($g))->toBe(2000);            // wallet €50 − €30
    $folio = $folio->fresh(['items', 'payments']);
    expect(FolioTotals::for($folio)['balance_minor'])->toBe(7000);   // owed dropped by €30

    // Can't apply more than owed, nor more than the wallet holds.
    expect(fn () => wal()->applyToBooking($g, $res, Money::of(999999, 'EUR')))->toThrow(WalletOverdrawException::class);
});

// ── CORRECT-BY-REVERSAL — reversing a credit appends a debit; the original is untouched ───────────
it('reverses a credit by appending a debit, leaving the original entry untouched', function () {
    $s = seedScenario(1);
    $g = walGuest($s['guest'], 'ada@example.test');
    $credit = wal()->credit($g, GuestCreditEntry::TYPE_REFUND, 'Refund', Money::of(4000, 'EUR'));
    expect(wal()->spendableMinor($g))->toBe(4000);

    $reversal = wal()->reverse($credit, 'Goodwill reversed');
    expect((string) $reversal->direction)->toBe('debit');
    expect((string) $reversal->reverses_id)->toBe((string) $credit->getKey());
    expect((int) $credit->fresh()->amount_minor)->toBe(4000);         // original credit unchanged
    expect((string) $credit->fresh()->direction)->toBe('credit');
    expect(wal()->spendableMinor($g))->toBe(0);
});

// ── ISOLATION — a guest sees + spends ONLY their own wallet ───────────────────────────────────────
it('never lets a guest see or spend another guest’s wallet', function () {
    $s = seedScenario(2);
    $a = walGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    wal()->credit($a, GuestCreditEntry::TYPE_REFUND, 'A credit', Money::of(1000, 'EUR'));   // €10
    wal()->credit($b, GuestCreditEntry::TYPE_REFUND, 'B credit', Money::of(9999, 'EUR'));   // €99.99 — B's
    $bRes = walBook($s, $b, $s['units'][1], chargeMinor: 30000, payMinor: 0);   // build BEFORE acting as a guest

    $this->actingAs($a, 'guest')->get(route('guest.wallet'))
        ->assertOk()
        ->assertSee('€10.00')        // A's own balance
        ->assertDontSee('€99.99');   // never B's

    // A can't apply credit to B's booking.
    $this->actingAs($a, 'guest')->post(route('guest.wallet.apply', $bRes->getKey()), ['amount' => '5.00'])->assertNotFound();
});

// ── G3 pixel-diff HONESTY: the available-credit hero + "How credits work"; NO fabricated refer-a-friend program ──
it('shows the available-credit hero + how-credits-work and refuses the fabricated referral invite program (#39)', function () {
    $s = seedScenario(1);
    $a = walGuest($s['guest'], 'ada@example.test');
    wal()->credit($a, GuestCreditEntry::TYPE_REFUND, 'Goodwill', Money::of(2500, 'EUR'));   // €25 credit

    $res = $this->actingAs($a, 'guest')->get(route('guest.wallet'))->assertOk();

    $res->assertSee('Available credit')
        ->assertSee('€25.00')                 // real derived spendable
        ->assertSee('How credits work');

    // #39: there is no invite/referral PROGRAM — no fabricated invite link / friend counter.
    $res->assertDontSee('Copy invite link')
        ->assertDontSee('friend joined')
        ->assertSee('coming soon');           // honestly deferred
});
