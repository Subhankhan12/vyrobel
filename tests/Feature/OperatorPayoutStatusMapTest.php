<?php

use App\Livewire\Operator\OperatorOwnerDetail;
use App\Livewire\Operator\PayoutRun;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use Illuminate\Support\Facades\DB;

// The two operator payout maps, unified — "one map, not two", literally.
//
// ⚠️ WHY THIS IS MONEY AND NOT STYLE. A payout badge is a claim about where an owner's money is. Two copies
// of one status map drift at the edges, and a drift here means the same payout reads "Processing" on one
// operator screen and something else on the other. That is not hypothetical: the OWNER realm's copy of this
// same concept badged `processing` and `held` as "Payout pending" — money already in transit reading as not
// yet started — until it was corrected. The operator copies never had that bug, but they had the condition
// that causes it.
//
// ⚠️ THE UNIFICATION CHANGED THE SOURCE, NOT THE OUTPUT. The expectations below are the exact labels and
// tones both private copies produced before the merge, so a drift in either direction fails.

/** Every state the enum declares, plus an undeclared one — the `default` arm is real behaviour, not a stub. */
const OPSM_EXPECTED = [
    'pending' => ['Pending', 'pending'],
    'paid' => ['Paid', 'confirmed'],
    'failed' => ['Failed', 'no-show'],
    'reversed' => ['Reversed', 'cancelled'],
    // ⚠️ The payout engine added these two AFTER the map was written; they reach `default` and echo
    // accurately. Pinned explicitly so a future edit cannot quietly re-badge live money states.
    'processing' => ['Processing', 'departed'],
    'held' => ['Held', 'departed'],
    'a_state_nobody_declared' => ['A_state_nobody_declared', 'departed'],
];

/** Call the map on a component without booting Livewire. */
function opsmMap(string $class, string $status): array
{
    $reflection = new ReflectionClass($class);
    $method = $reflection->getMethod('payoutStatusMeta');
    $method->setAccessible(true);

    return $method->invoke($reflection->newInstanceWithoutConstructor(), $status);
}

// ── 1. ⚠️⚠️ ONE SOURCE — both operator screens resolve to the SAME definition ─────────────────────
it('resolves both operator payout maps to a single definition', function () {
    $a = (new ReflectionClass(OperatorOwnerDetail::class))->getMethod('payoutStatusMeta');
    $b = (new ReflectionClass(PayoutRun::class))->getMethod('payoutStatusMeta');

    // ⚠️ PHP flattens a trait into the using class, so `getDeclaringClass()` names the component either way
    // and proves nothing. The FILE and LINE are what actually distinguish one shared definition from two
    // identical copies.
    expect($a->getFileName())->toBe($b->getFileName())
        ->and($a->getStartLine())->toBe($b->getStartLine())
        ->and(basename((string) $a->getFileName()))->toBe('FormatsOperatorData.php');
});

// ── 2. ⚠️⚠️ EVERY STATE, AT BOTH SITES — byte-identical to the pre-merge behaviour ────────────────
it('maps every payout state identically at both operator sites', function () {
    foreach ([OperatorOwnerDetail::class, PayoutRun::class] as $class) {
        foreach (OPSM_EXPECTED as $status => [$label, $tone]) {
            $meta = opsmMap($class, $status);

            expect($meta['label'])->toBe($label)
                ->and($meta['tone'])->toBe($tone)
                // ⚠️ The map is DISPLAY ONLY — it must never carry or alter an amount (§G.1).
                ->and(array_keys($meta))->toBe(['label', 'tone']);
        }
    }
});

// ── 3. ⚠️ THE DECLARED ENUM IS COVERED — read from the DATABASE, never a pinned list ──────────────
it('covers every status the payouts column can actually hold', function () {
    // ⚠️ THE SOURCE OF TRUTH IS THE COLUMN. A first cut of this test read a `Payout::STATUSES` constant that
    // does not exist, so it SKIPPED — a hole wearing a green tick. The enum is declared on the column, so
    // that is what gets read: if the payout engine gains a seventh state tomorrow, this fails loudly instead
    // of letting the new state slip through `default` unnoticed on both operator screens.
    // ⚠️ SHOW COLUMNS does not accept a bound parameter for LIKE — the literal is required here.
    $column = DB::selectOne("SHOW COLUMNS FROM payouts LIKE 'status'");
    expect($column)->not->toBeNull();

    preg_match_all("/'([a-z_]+)'/", (string) $column->Type, $m);
    $declared = $m[1];

    expect($declared)->toContain('pending', 'processing', 'paid', 'failed', 'reversed', 'held')
        ->and(count($declared))->toBe(6);

    foreach ($declared as $status) {
        expect(OPSM_EXPECTED)->toHaveKey($status);
        expect(opsmMap(PayoutRun::class, $status)['label'])->toBe(OPSM_EXPECTED[$status][0]);
        expect(opsmMap(OperatorOwnerDetail::class, $status)['label'])->toBe(OPSM_EXPECTED[$status][0]);
    }
});

// ── 4. ⚠️⚠️ THE REALMS STAY SEPARATE — a "helpful" cross-realm merge must fail here ───────────────
it('keeps the owner realm map deliberately separate', function () {
    // ⚠️ THIS IS A RATIFIED DIVERGENCE, NOT DRIFT. The owner shell defines three tones (paid/pending/draft);
    // the operator shell defines fifteen and defines neither `paid` nor `draft`. Collapsing the maps would
    // emit tone strings the receiving shell has no CSS for — the badge would lose its colour. The realms
    // also speak differently on purpose: an owner reads "In transit" about their own money.
    $owner = new class
    {
        use FormatsOwnerData;

        public function meta(string $s): array
        {
            return $this->payoutStatusMeta($s);
        }
    };

    // Same state, deliberately different words and tones per realm.
    expect($owner->meta('processing')['label'])->toBe('In transit')
        ->and(opsmMap(PayoutRun::class, 'processing')['label'])->toBe('Processing')
        ->and($owner->meta('held')['label'])->toBe('On hold')
        ->and(opsmMap(PayoutRun::class, 'held')['label'])->toBe('Held');

    // ⚠️ And the owner realm's own historic defect stays fixed: `processing`/`held` are NOT "Payout pending".
    expect($owner->meta('processing')['label'])->not->toBe('Payout pending')
        ->and($owner->meta('held')['label'])->not->toBe('Payout pending');
});
