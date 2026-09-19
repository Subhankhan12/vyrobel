<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/**
 * ⚠️⚠️ F-44 — A CURRENCY IS NEVER A HARDCODED LITERAL BEHIND AN ABSENT OBJECT (founder ruling, 2026-09-19).
 *
 * ⚠️⚠️ THE GUARD IS THE DELIVERABLE, NOT THE EDITS. This class has been closed TWICE and returned TWICE, and
 * one of the sites was written BY THE PASS THAT WAS CLOSING IT. Nine mechanical fixes without a guard buys a
 * tenth site next month — so the fixes are the cheap half and this file is the point.
 *
 * ⚠️⚠️ WHY IT ASKS ABOUT AN ABSENT OBJECT AND NOT ABOUT HARDCODED CURRENCIES IN GENERAL. 🔬 `app/` holds
 * roughly thirty-nine `?? 'XXX'` literals, and FIX 22 proved FORTY-FIVE of that family UNREACHABLE by reading
 * the schema: every `currency` column is NOT NULL, so the branch cannot fire. **That evidence was correct and
 * blind to exactly one case** — a fallback keyed on an object that is not there (`->first()` on an empty
 * collection, or a query that matches nothing) rather than on a null column. **No schema can see an absent
 * row.** That blind spot IS F-44. ⚠️ Forbidding all thirty-nine would need a thirty-nine-entry allow-list,
 * and an allow-list is how a guard dies quietly.
 *
 * ⚠️⚠️ THE GUARD FOUND THREE SITES THE REGISTER NEVER RECORDED. F-44 listed nine; `MaintenanceList`,
 * `OperatorInsights` and `OperatorReports` each held the same defect one shape over — `Property::query()
 * ->whereKey($id)->first()` returning null when no property is accessible. **Twelve, not nine.** That is the
 * argument for a guard over a checklist, made by the guard on its first run.
 *
 * ⚠️ IT REUSES FIX 42's STRIPPER ({@see vyPermissionStrip()}) — comments, heredocs, `<style>` and `<script>`
 * removed before the scan. **A THIRD SCANNER WOULD BE THE DEFECT FIX 42 SPENT A PASS REMOVING**, committed by
 * the very pass closing a recurrence.
 *
 * ⚠️⚠️ AND IT IS A NEGATIVE CLAIM PROVEN BY SOURCE SCAN — the needle-reads-its-own-list trap, which has fired
 * about ten times here. It ships because both halves hold: the scan's root is **`app/` ONLY**, so `tests/`
 * (this file, and the allow-list below) and `docs/` are never read — **asserted below, not claimed in a
 * comment** — and 🔬 **a fallback planted in a real file makes it RED**, proven in FIX 46.
 */

/**
 * ⚠️⚠️ ONE ENTRY, AND IT IS A FALSE POSITIVE OF THE DETECTOR RATHER THAN AN ACCEPTED DEFECT.
 *
 * The line coalesces on `$owner->payout_currency`, and 🔬 `owners.payout_currency` is `char(3)` **NOT NULL** —
 * so that branch is provably dead, exactly as FIX 22 established for forty-five of its siblings. It is matched
 * only because the same line reads `$latest`, which IS assigned from `->first()` two lines above.
 *
 * ⚠️ IF THIS LIST EVER NEEDS A SECOND ENTRY, READ IT AS A SIGNAL: either a real defect is being waved through,
 * or the detector is asking the wrong question. Do not grow it quietly.
 */
const VY_CURRENCY_FALLBACK_ALLOWED = [
    'app/Livewire/Operator/OperatorOwners.php' => 'Coalesces on `owners.payout_currency`, which is char(3) NOT NULL — provably dead (FIX 22). Matched only because `$latest` from ->first() appears on the same line.',
];

// ── 1. ⚠️⚠️ THE CLASS, PINNED ──────────────────────────────────────────────────────────────────────
it('⚠️⚠️ F-44 — no currency falls back to a literal behind an object that is not there', function () {
    $offenders = [];

    foreach (vyCurrencyEmptyCollectionFallbacks() as $hit) {
        $path = explode(':', $hit)[0];

        if (! array_key_exists($path, VY_CURRENCY_FALLBACK_ALLOWED)) {
            $offenders[] = $hit;
        }
    }

    expect($offenders)->toBe([],
        'A currency falls back to a hardcoded literal where the object it reads may be absent — an empty '
        .'collection, or a query that matched nothing. ⚠️ The schema cannot see this: the column is NOT NULL, '
        ."but a row that does not exist has no column. Derive it from something that IS in scope (the owner's "
        ."`payout_currency`, the property), or use `config('billing.default_currency')` when genuinely nothing "
        ."is:\n".implode("\n", $offenders));
});

// ── 2. ⚠️⚠️ THE SCAN CANNOT READ ITSELF — ASSERTED, NOT CLAIMED ────────────────────────────────────
/**
 * ⚠️⚠️ This file names `'EUR'` in prose, in its allow-list and in its own planted-reader mutation. If the scan
 * ever widened to `tests/`, every one of those would register as a defect and the guard would be red for
 * reasons that have nothing to do with the product — or, worse, its allow-list would be grown until it was.
 */
it('⚠️⚠️ reads only app/ — never tests/, never docs/, never config/', function () {
    foreach (vyCurrencyEmptyCollectionFallbacks() as $hit) {
        $path = explode(':', $hit)[0];

        expect(str_starts_with($path, 'app/'))->toBeTrue("the scan read outside app/: {$path}");
    }

    // ⚠️ AND IT IS NOT VACUOUS BY EMPTINESS — the scanner really walks a substantial tree.
    $files = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('app'), FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
            $files++;
        }
    }

    expect($files)->toBeGreaterThan(200, 'the scanner is walking almost nothing; it would pass on anything');
});

// ── 3. ⚠️ THE ALLOW-LIST IS SHORT, AND EVERY ENTRY CARRIES ITS REASON ──────────────────────────────
it('⚠️ keeps the allow-list short and justified', function () {
    expect(count(VY_CURRENCY_FALLBACK_ALLOWED))->toBeLessThanOrEqual(3,
        'the allow-list is growing — either a real defect is being waved through, or the detector is asking '
        .'the wrong question. Both are worth stopping for.');

    foreach (VY_CURRENCY_FALLBACK_ALLOWED as $path => $reason) {
        expect(strlen($reason))->toBeGreaterThan(40, "the entry for {$path} has no real reason recorded");
        expect(file_exists(base_path($path)))->toBeTrue("the allow-list names a file that no longer exists: {$path}");
    }
});

// ── 4. ⚠️⚠️ THE OWNER'S OWN CURRENCY IS THE ANSWER WHERE AN OWNER IS IN SCOPE ──────────────────────
/**
 * ⚠️⚠️ THE FOUR OWNER SURFACES ARE A REAL FIX, NOT A RELABELLED DEFAULT. 🔬 `owners.payout_currency` is
 * char(3) NOT NULL — the currency the owner is actually PAID in — which is exactly what F-46 used to close
 * this class the first time. The other eight sites have no owner and no property in scope when the collection
 * is empty, so they carry the one product default instead.
 *
 * ⚠️ ASSERTED ON THE SOURCE because the branch fires only on an empty collection, which is precisely the state
 * these screens are hardest to drive into — and a test that never reaches the branch proves nothing.
 */
it('⚠️⚠️ the four owner surfaces derive from the owner, not from a default', function () {
    $owned = [
        'app/Livewire/Owner/OwnerRates.php',
        'app/Livewire/Owner/OwnerMyUnits.php',
        'app/Livewire/Owner/OwnerReports.php',
        'app/Livewire/Owner/OwnerInsights.php',
    ];

    foreach ($owned as $path) {
        $source = vyPermissionStrip((string) file_get_contents(base_path($path)), false);

        expect(str_contains($source, 'ownerPayoutCurrency()'))->toBeTrue(
            "{$path} no longer derives its currency from the owner — F-44 has returned to an owner surface, "
            .'which is the exact shape F-46 closed.');
    }
});
