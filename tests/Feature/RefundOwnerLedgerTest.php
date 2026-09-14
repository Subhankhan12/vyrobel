<?php

use App\Events\DomainEventRecorded;
use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Listeners\AdjustOwnerLedgerOnRefund;
use App\Listeners\DispatchNotificationsForDomainEvent;
use App\Listeners\EnqueueAriPushOnInventoryChange;
use App\Listeners\ReattributeOwnerOnUnitMove;
use App\Listeners\RecomputeAvailabilityOnInventoryChange;
use App\Listeners\SendGuestTransactionalEmails;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;

uses(DatabaseTransactions::class);

/**
 * ⚠️⚠️ THE REFUND PATH — EXERCISED, NOT READ.
 *
 * Every pass before FIX 18 correctly refused to run a refund: it posts an owner-ledger adjustment that
 * NOTHING in the product removes, and the only tenant available was the one every pinned figure belongs to.
 * `docs/AUDIT-SUMMARY.md` §5 carried the 17 money-write routes as "established by reading" for the whole
 * programme. FIX 17's second tenant made this safe to drive.
 *
 * ⚠️⚠️ WHAT THIS FILE DEFENDS — AND WHAT IT DOES NOT, MEASURED RATHER THAN ASSUMED.
 *
 * It defends that a refund DEBITS THE OWNER LEDGER, with the right direction and the right amount, and that
 * an operator-absorbed one posts nothing at all. ⚠️ It does **NOT** defend the listener ORDER, and that was
 * established the hard way: removing `Event::listen(DomainEventRecorded::class, AdjustOwnerLedgerOnRefund::class)`
 * from `AppServiceProvider` leaves every case here GREEN (🔬 verified — registrations 1 → 0, adjustment still
 * written). The reason is F-40: every domain-event listener is registered TWICE — once explicitly and once by
 * Laravel's event DISCOVERY as `Class@handle` — so deleting the explicit registration changes nothing.
 *
 * ⚠️ THE WRONG-FIX MUTATION IS STILL THE POINT: asserting only that a REFUND ROW exists passes while the
 * owner's adjustment is missing. Every case below asserts THE LEDGER, not the refund row.
 */
function f18RefundScenario(): array
{
    $s = seedScenario(1);

    $owner = Owner::create([
        'type' => 'company', 'name' => 'Ledger Probe Ltd',
        'email' => 'owner+'.uniqid().'@probe.test',
        'payout_currency' => 'EUR', 'status' => 'active',
    ]);
    ManagementAgreement::create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 20.00,
        'payout_currency' => 'EUR', 'payout_schedule' => 'monthly', 'term_months' => 12,
        'notice_days' => 60, 'auto_renews' => true, 'effective_from' => '2026-01-01', 'status' => 'active',
    ]);
    $s['units'][0]->forceFill(['owner_id' => $owner->id])->save();

    // ⚠️ PIN THE UNIT. `book()` lets AssignmentService auto-assign, and attribution follows the UNIT —
    // an auto-assigned unit is not a fixture guarantee, and a 0 net would look like a missing adjustment.
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room', Money::of(10000, 'EUR'));
    $folio->refresh();
    $payment = $folios->recordPayment($folio, Money::of(10000, 'EUR'), 'tok_probe');

    app(OwnerAttributionService::class)->attributeReservation($res);

    return ['scenario' => $s, 'reservation' => $res, 'folio' => $folio, 'payment' => $payment, 'owner' => $owner];
}

function f18AdjustmentsFor(string $reservationId): Collection
{
    return OwnerLedgerEntry::query()
        ->where('reservation_id', $reservationId)
        ->where('entry_type', 'adjustment')
        ->where('description', 'Guest refund adjustment')
        ->get();
}

it('⚠️⚠️ a refund debits the OWNER LEDGER — not merely writes a refund row', function () {
    $c = f18RefundScenario();

    // CONTROL: attribution really happened, or the adjustment could not post for an innocent reason and
    // "no adjustment" would prove nothing.
    $net = OwnerLedgerEntry::query()->where('reservation_id', $c['reservation']->id)
        ->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
    expect($net)->toBe(8000);           // 10000 room revenue − 20% commission

    expect(f18AdjustmentsFor($c['reservation']->id))->toHaveCount(0);

    app(FolioService::class)
        ->refundPayment($c['payment'], Money::of(10000, 'EUR'), 'probe', null, 'owner');

    // ⚠️ THE WEAK ASSERTION — a refund row exists. It passes even when the ledger was never touched.
    expect(Payment::query()->where('reservation_id', $c['reservation']->id)->where('type', 'refund')->count())->toBe(1);

    // ⚠️⚠️ THE ONE THAT MATTERS: the owner's adjustment, its DIRECTION and its AMOUNT.
    $adj = f18AdjustmentsFor($c['reservation']->id);
    expect($adj)->toHaveCount(1);
    expect((string) $adj->first()->direction)->toBe('debit')            // reduces what the owner is owed
        ->and((int) $adj->first()->amount_minor)->toBe(8000)            // the owner's NET share, not the gross
        ->and($adj->first()->signedMinor())->toBe(-8000);
});

it('⚠️ an OPERATOR-ABSORBED refund posts NO owner row at all — not a zero row', function () {
    $c = f18RefundScenario();

    app(FolioService::class)
        ->refundPayment($c['payment'], Money::of(10000, 'EUR'), 'absorbed', null, 'operator');

    // The refund happened…
    expect(Payment::query()->where('reservation_id', $c['reservation']->id)->where('type', 'refund')->count())->toBe(1);

    // …and the owner's ledger is byte-identical to one where it never did.
    expect(f18AdjustmentsFor($c['reservation']->id))->toHaveCount(0);
});

it('⚠️ the cumulative bound refuses an over-refund, and a refused refund writes NOTHING', function () {
    $c = f18RefundScenario();

    app(FolioService::class)->refundPayment($c['payment'], Money::of(6000, 'EUR'), 'part', null, 'owner');

    expect(fn () => app(FolioService::class)
        ->refundPayment($c['payment'], Money::of(6000, 'EUR'), 'over', null, 'owner'))
        ->toThrow(RefundExceedsPaymentException::class);

    // ⚠️ THE REFUSAL WROTE NOTHING: one refund row, one adjustment, and the sum never exceeds the payment.
    $refunds = Payment::query()->where('reservation_id', $c['reservation']->id)->where('type', 'refund')->get();
    expect($refunds)->toHaveCount(1)
        ->and((int) $refunds->sum('amount_minor'))->toBeLessThanOrEqual(10000)
        ->and(f18AdjustmentsFor($c['reservation']->id))->toHaveCount(1);
});

// ══ F-40 · ONE REGISTRATION PER LISTENER, AND THE DOCUMENTED ORDER IS THE ORDER THAT RUNS ═══════════
//
// 🔬 Laravel 13 ships `EventServiceProvider::$shouldDiscoverEvents = true`, so every class in `app/Listeners`
// was registered a SECOND time as `Class@handle` on top of the explicit `Event::listen` calls — 14 entries on
// `DomainEventRecorded` where 7 were intended, in two DIFFERENT orders. FIX 19 turned discovery off
// (`bootstrap/app.php`, `->withEvents(discover: false)`), after measuring that every discovered listener also
// had an explicit registration, so nothing lost its listener.
//
// ⚠️⚠️ BOTH ASSERTIONS ARE NEEDED AND THE SECOND IS THE POINT. A count-only test passes a deduplication that
// does not preserve order — which is the plausible bad fix here, and order is the load-bearing property: the
// money listeners precede the best-effort ones because a ledger debit was once LOST behind a notification
// that threw. ⚠️ Asserted against the RESOLVED listener list the dispatcher holds, never against the source.

it('⚠️⚠️ F-40 — exactly one registration per listener, AND money before best-effort', function () {
    $raw = (new ReflectionProperty(app('events'), 'listeners'))->getValue(app('events'));
    $entries = array_values(array_map(
        fn ($l): string => is_string($l) ? $l : 'closure',
        $raw[DomainEventRecorded::class] ?? [],
    ));

    // NON-VACUITY: the event really has its listeners, or every assertion below is meaningless.
    expect($entries)->not->toBeEmpty();

    // ── ① COUNT — no listener appears twice, under either spelling.
    $names = array_map(fn (string $l): string => explode('@', $l)[0], $entries);
    foreach (array_count_values($names) as $listener => $times) {
        expect($times)->toBe(1, "{$listener} is registered {$times} times on DomainEventRecorded");
    }

    // …and none is registered by discovery's `Class@handle` spelling any more.
    expect(array_filter($entries, fn (string $l): bool => str_contains($l, '@handle')))->toBe([]);

    // ── ② ORDER — the property a count cannot express. MONEY FIRST, TELLING PEOPLE AFTER.
    $pos = array_flip($names);
    $money = [AdjustOwnerLedgerOnRefund::class, ReattributeOwnerOnUnitMove::class];
    $bestEffort = [DispatchNotificationsForDomainEvent::class, SendGuestTransactionalEmails::class];

    foreach ($money as $m) {
        expect($pos)->toHaveKey($m);
        foreach ($bestEffort as $b) {
            expect($pos)->toHaveKey($b);
            expect($pos[$m])->toBeLessThan(
                $pos[$b],
                class_basename($m).' must run before '.class_basename($b).' — a throw in a best-effort listener'
                .' must not be able to cost the owner their debit',
            );
        }
    }

    // ── ③ THE SECOND ORDERING CLAIM ON THIS EVENT, found by sweeping for the SHAPE rather than the wording.
    //    `AppServiceProvider` states that the ARI push is registered after the availability recompute so it
    //    reads availability AFTER any recompute has been applied. Discovery broke that one too.
    expect($pos[RecomputeAvailabilityOnInventoryChange::class])
        ->toBeLessThan(
            $pos[EnqueueAriPushOnInventoryChange::class],
            'the ARI push must read availability AFTER the recompute has applied',
        );
});

it('⚠️ and no listener anywhere is registered twice — the sweep, not just the one event', function () {
    $raw = (new ReflectionProperty(app('events'), 'listeners'))->getValue(app('events'));

    $duplicates = [];
    foreach ($raw as $event => $listeners) {
        $names = array_map(
            fn ($l): string => is_string($l) ? explode('@', $l)[0] : 'closure',
            $listeners,
        );
        foreach (array_count_values($names) as $listener => $times) {
            if ($times > 1 && $listener !== 'closure') {
                $duplicates[] = class_basename((string) $event).' <- '.class_basename($listener)." x{$times}";
            }
        }
    }

    // ⚠️ `AuditOAuthTokenGranted` was the dangerous one: it has NO idempotence guard, so being registered
    //    twice wrote TWO audit rows for a single token issuance (proven by dispatching the event).
    expect($duplicates)->toBe([]);
});
