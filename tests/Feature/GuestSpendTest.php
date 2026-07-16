<?php

use App\Models\Folio;
use App\Models\Guest;
use App\Services\FolioService;
use App\Support\Folio\FolioTotals;
use App\Support\Guest\GuestSpend;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE SHARED BILLED-SPEND DEFINITION — "Total billed" + the open balance beside it, consumed by the Guests
// CRM list AND the Guest profile (Wave 2; locked decision 1, docs/DECISIONS.md 2026-07-16).
//
// The load-bearing property is that the headline number RECONCILES TO THE LEDGER: the helper reads the
// cached folio columns (a list cannot load every folio's items + payments), and those columns are only
// trustworthy because FolioService::recompute() writes them FROM the same append-only rows FolioTotals
// reads. Test 1 asserts that identity, so a future divergence fails loudly instead of quietly.
//
// The design's "lifetime value" was self-contradictory — it netted refunds off (collected) while including
// unpaid charges (billed). BILLED is the decision; these tests pin it.

/** A guest with a real billed stay: folio opened, charge posted, payment recorded — all through FolioService. */
function spendGuest(array $s, int $unitIndex, int $chargeMinor, int $payMinor = 0): array
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur($chargeMinor));

    if ($payMinor > 0) {
        $folios->recordPayment($folio, moneyEur($payMinor));
    }

    return ['reservation' => $reservation, 'folio' => $folio->refresh(), 'guest' => $s['guest']];
}

// ── 1. THE HEADLINE — the cached source the helper reads IS the ledger FolioTotals computes ──────
it('reconciles billed and balance to the append-only ledger, not to a second opinion', function () {
    $s = seedScenario(2);
    spendGuest($s, 0, 30000, 10000);   // billed 300.00, paid 100.00 → balance 200.00
    spendGuest($s, 1, 20000);          // billed 200.00, unpaid      → balance 200.00

    $guest = Guest::query()->findOrFail($s['guest']->id);
    $buckets = GuestSpend::for($guest);

    // The helper's figures…
    expect($buckets)->toHaveKey('EUR')
        ->and($buckets['EUR']['billed_minor'])->toBe(50000)    // 30000 + 20000
        ->and($buckets['EUR']['balance_minor'])->toBe(40000)   // 20000 + 20000
        ->and($buckets['EUR']['stays'])->toBe(2);

    // …must equal what FolioTotals derives from the REAL rows, folio by folio. This is the identity that
    // makes reading the cached column legitimate; if recompute() ever drifts, this fails.
    $ledgerBilled = 0;
    $ledgerBalance = 0;
    foreach (Folio::query()->with(['items', 'payments'])->get() as $folio) {
        $totals = FolioTotals::for($folio);
        $ledgerBilled += $totals['charges_minor'];
        $ledgerBalance += $totals['balance_minor'];
    }

    expect($buckets['EUR']['billed_minor'])->toBe($ledgerBilled)
        ->and($buckets['EUR']['balance_minor'])->toBe($ledgerBalance);
});

// ── 2. BILLED, not collected — a refund does NOT rewrite what was billed ─────────────────────────
it('keeps billed unchanged when a payment is refunded, and shows it in the balance instead', function () {
    $s = seedScenario(1);
    ['folio' => $folio] = spendGuest($s, 0, 30000, 30000);   // billed 300, paid 300 → balance 0

    $guest = Guest::query()->findOrFail($s['guest']->id);
    expect(GuestSpend::for($guest)['EUR'])
        ->toMatchArray(['billed_minor' => 30000, 'balance_minor' => 0]);

    // Refund 100.00 — an append-only offsetting row. BILLED must not move (we still billed 300);
    // the balance rises, because a refund reverses a PAYMENT, not the charges.
    $payment = $folio->payments()->where('type', 'payment')->firstOrFail();
    app(FolioService::class)->refundPayment($payment, moneyEur(10000));

    expect(GuestSpend::for($guest->fresh())['EUR'])
        ->toMatchArray(['billed_minor' => 30000, 'balance_minor' => 10000]);
});

// ── 3. PER CURRENCY — never summed across (no invented FX) ───────────────────────────────────────
it('buckets by currency and never sums across them', function () {
    $s = seedScenario(2);
    ['folio' => $eur] = spendGuest($s, 0, 30000);
    ['folio' => $usd] = spendGuest($s, 1, 20000);

    // Force the second folio into another currency — the helper must not add it to the EUR bucket.
    Folio::query()->whereKey($usd->getKey())->update(['currency' => 'USD']);

    $buckets = GuestSpend::for(Guest::query()->findOrFail($s['guest']->id));

    expect($buckets)->toHaveKeys(['EUR', 'USD'])
        ->and($buckets['EUR']['billed_minor'])->toBe(30000)
        ->and($buckets['USD']['billed_minor'])->toBe(20000);

    // The headline is the biggest-billed bucket, named — not a meaningless 50000 total.
    expect(GuestSpend::primary($buckets))
        ->toMatchArray(['currency' => 'EUR', 'billed_minor' => 30000]);
});

// ── 4. No billing history → NULL, so the caller renders "—" and not a fabricated €0.00 ───────────
it('returns no bucket for a guest who has never been billed', function () {
    $s = seedScenario(1);
    $guest = Guest::query()->findOrFail($s['guest']->id);   // booked nothing

    expect(GuestSpend::for($guest))->toBe([])
        ->and(GuestSpend::primary([]))->toBeNull();
});

// ── 5. TENANT ISOLATION — another org's folios can never enter the sum ───────────────────────────
it('never counts another organisation folios toward a guest', function () {
    $home = seedScenario(1);
    spendGuest($home, 0, 30000);
    $homeGuestId = $home['guest']->id;

    // A second org, billed heavily — its folios must be invisible here.
    $foreign = seedScenario(1);
    spendGuest($foreign, 0, 99900);

    // Act back in the home org.
    app(TenantContext::class)->set($home['org']->id);
    $buckets = GuestSpend::for(Guest::query()->findOrFail($homeGuestId));

    expect($buckets['EUR']['billed_minor'])->toBe(30000)
        ->and($buckets['EUR']['billed_minor'])->not->toBe(129900);   // never the cross-org total
});

// ── 6. forGuestIds() and for() are the SAME definition — the list and the profile cannot disagree ─
it('gives a guest the identical figures whether asked one-by-one or in a batch', function () {
    $s = seedScenario(2);
    spendGuest($s, 0, 30000, 10000);
    spendGuest($s, 1, 20000);

    $guest = Guest::query()->findOrFail($s['guest']->id);

    // The profile's call…
    $single = GuestSpend::for($guest);
    // …and the list's call.
    $batch = GuestSpend::forGuestIds([(string) $guest->getKey()])[(string) $guest->getKey()];

    expect($single)->toBe($batch);   // one definition, two callers
});

// ── 7. Guest::reservations() — the approved relation, keyed on the lead guest ────────────────────
it('relates a guest to the stays they lead', function () {
    $s = seedScenario(2);
    ['reservation' => $first] = spendGuest($s, 0, 30000);
    ['reservation' => $second] = spendGuest($s, 1, 20000);

    $guest = Guest::query()->findOrFail($s['guest']->id);

    expect($guest->reservations()->count())->toBe(2)
        ->and($guest->reservations()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$first->id, $second->id])->sort()->values()->all());
});
