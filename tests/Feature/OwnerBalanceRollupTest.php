<?php

declare(strict_types=1);

use App\Livewire\Owner\OwnerBalance;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// OWNER FIX 3 — /owner/balance activity as ROLLUP-WITH-EXPAND (owner audit, page 5 item 14).
//
// ⚠️⚠️ THE SAFETY PROPERTY IS "A GROUPING, NOT AN AGGREGATE", AND TEST 2 IS THE ONE THAT PROVES IT. The
// summary figure is a sum of the same `signed_minor` integers the lines render; nothing re-queries the
// ledger. So a summary cannot drift from the rows beneath it — which is exactly the failure a "friendlier
// rollup" invites, and the reason the audit filed this as a product call rather than a defect.
//
// ⚠️ AND THE ROLLUP MUST NOT SWALLOW THE TRUTH. The build's per-line provenance (`Unverified`,
// `Prior-period adjustment`, statement-provenance) is the honest part the wireframe never had; test 3
// asserts it survives expansion, and test 4 asserts the summary itself flags a month that contains it.

const OBR_CURRENCY = 'EUR';

/** The demo-shaped owner with real ledger rows — read through the component, exactly as the page does. */
function obrState(): array
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $owner = Owner::query()->where('user_id', $user->getKey())->firstOrFail();

    // Real ledger movements across TWO months, including both provenance flags — without them the
    // provenance assertions below would pass on data that never had any.
    $rows = [
        ['2026-06-04', 'room_revenue', 54000, false, false],
        ['2026-06-04', 'commission', -9720, false, false],
        ['2026-06-18', 'ota_cost', -1500, true, false],     // ⚠️ unverified
        ['2026-07-02', 'room_revenue', 63000, false, false],
        ['2026-07-02', 'commission', -11340, false, false],
        ['2026-07-09', 'expense', -8000, false, true],      // ⚠️ prior-period adjustment
    ];
    foreach ($rows as $i => [$date, $type, $minor, $unverified, $prior]) {
        DB::table('owner_ledger')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $s['org']->id,
            'owner_id' => $owner->getKey(),
            'unit_id' => $s['units'][0]->getKey(),
            'entry_type' => $type,
            'amount_minor' => $minor,
            'currency' => OBR_CURRENCY,
            'service_date' => $date,
            'description' => 'Ledger line '.$i,
            'unverified' => $unverified,
            'prior_period_adjustment' => $prior,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $view = Livewire::actingAs($user)->test(OwnerBalance::class);

    return [
        's' => $s,
        'user' => $user,
        'owner' => $owner,
        'entries' => $view->viewData('entries'),
        'groups' => $view->viewData('activityGroups'),
    ];
}

// ── 1. ⚠️⚠️ THE EXPANDED LINES ARE TODAY'S LIST — same rows, same order, nothing lost ──────────────────
it('keeps the expanded lines identical to the flat activity list', function () {
    $f = obrState();

    $flat = $f['entries'];
    $expanded = [];
    foreach ($f['groups'] as $group) {
        foreach ($group['lines'] as $line) {
            $expanded[] = $line;
        }
    }

    // ⚠️ THE PREMISE: there is real activity, so an equality of two empty lists cannot pass this.
    expect($flat)->not->toBeEmpty()
        ->and(count($expanded))->toBe(count($flat));

    // ⚠️ IDENTICAL, IN ORDER — ids, amounts and running balances. Grouping re-arranges nothing.
    expect(array_column($expanded, 'id'))->toBe(array_column($flat, 'id'))
        ->and(array_column($expanded, 'amount_display'))->toBe(array_column($flat, 'amount_display'))
        ->and(array_column($expanded, 'running_display'))->toBe(array_column($flat, 'running_display'));
});

// ── 2. ⚠️⚠️ EVERY GROUP RECONCILES TO ITS OWN LINES, AND ALL GROUPS TO THE LEDGER ─────────────────────
it('sums each group to exactly its lines, and all groups to the ledger total', function () {
    $f = obrState();

    expect($f['groups'])->not->toBeEmpty();

    $allGroups = 0;
    foreach ($f['groups'] as $group) {
        $lineSum = array_sum(array_map(fn (array $l): int => (int) $l['signed_minor'], $group['lines']));

        // ⚠️ TO THE CENT, group by group — the whole point of a grouping over an aggregate.
        expect((int) $group['net_minor'])->toBe($lineSum);

        $allGroups += (int) $group['net_minor'];
    }

    // ⚠️ …and the groups together equal the flat list, which equals the raw ledger.
    $flatSum = array_sum(array_map(fn (array $r): int => (int) $r['signed_minor'], $f['entries']));
    $ledgerSum = (int) DB::table('owner_ledger')->where('owner_id', $f['owner']->getKey())->sum('amount_minor');

    expect($allGroups)->toBe($flatSum)
        ->and($allGroups)->toBe($ledgerSum);
});

// ── 3. ⚠️⚠️ PROVENANCE SURVIVES THE ROLLUP — on the lines, and rendered ───────────────────────────────
it('preserves every provenance tag on the expanded lines and renders them', function () {
    $f = obrState();

    $lines = [];
    foreach ($f['groups'] as $group) {
        $lines = array_merge($lines, $group['lines']);
    }

    // ⚠️ THE PREMISE: the fixture really planted both flags, so their presence is a fact about the rollup.
    expect(array_filter($lines, fn (array $l): bool => (bool) $l['unverified']))->toHaveCount(1)
        ->and(array_filter($lines, fn (array $l): bool => (bool) $l['prior_period']))->toHaveCount(1);

    // …and the page genuinely renders them ON THE EXPANDED LINES.
    //
    // ⚠️ SCOPED TO THE LINE ROWS, AND A MUTATION IS WHY. The first version asserted the tags appeared
    // ANYWHERE in the HTML — but the SUMMARY row also flags a month containing unverified money, so deleting
    // the tag from every line still left the page containing the word and the test still passed. It proved
    // nothing about the thing it was named for. The line rows are the ones carrying `x-show="open"`, so the
    // assertion now reads only those.
    $html = test()->actingAs($f['user'])->get(route('owner.balance'))->assertOk()->getContent();

    preg_match_all('/<tr x-show="open".*?<\/tr>/s', $html, $matches);
    $lineRows = implode('', $matches[0]);

    expect($matches[0])->not->toBeEmpty()
        ->and($lineRows)->toContain('Unverified')
        ->and($lineRows)->toContain('Prior-period adjustment')
        ->and($lineRows)->toContain('not yet on a statement')
        // the running balance is on the line rows too — expanding must not lose the reconciling column
        ->and($lineRows)->toContain('vy-mono');
});

// ── 4. ⚠️ A MONTH CONTAINING PROVISIONAL MONEY SAYS SO WHILE STILL ROLLED UP ──────────────────────────
it('flags a group whose lines carry provisional or back-dated money', function () {
    $f = obrState();

    $flagged = array_values(array_filter(
        $f['groups'],
        fn (array $g): bool => $g['unverified'] || $g['prior_period'],
    ));

    // ⚠️ Rolling up must not HIDE that a month contains unverified or prior-period money — one group holds
    // the unverified line, another the prior-period one, so both must surface.
    expect($flagged)->toHaveCount(2);
});

// ── 5. ⚠️⚠️ THE BALANCE ON A SUMMARY IS A POSITION, NOT A SUM OF POSITIONS ────────────────────────────
it('shows each group the running balance of its most recent line', function () {
    $f = obrState();

    foreach ($f['groups'] as $group) {
        // Rows arrive newest-first, so the group's first line IS its most recent.
        expect($group['running_display'])->toBe($group['lines'][0]['running_display']);
    }

    // ⚠️ AND THE NEWEST GROUP CARRIES THE LEDGER'S CLOSING POSITION — the figure a reader takes away.
    expect($f['groups'][0]['running_display'])->toBe($f['entries'][0]['running_display']);
});

// ── 6. ⚠️ NO CROSS-CURRENCY GROUP — a month-only key would be a silent FX sum ─────────────────────────
it('never groups two currencies into one summary', function () {
    $f = obrState();

    DB::table('owner_ledger')->insert([
        'id' => (string) Str::ulid(),
        'organization_id' => $f['s']['org']->id,
        'owner_id' => $f['owner']->getKey(),
        'unit_id' => $f['s']['units'][0]->getKey(),
        'entry_type' => 'room_revenue',
        'amount_minor' => 20000,
        // ⚠️ THE SAME MONTH as an existing EUR group. A month-only key would fold these together and the
        // summary would be a cross-currency sum — the exact bug the per-currency running balance avoids.
        'currency' => 'GBP',
        'service_date' => '2026-07-15',
        'description' => 'A sterling movement',
        'unverified' => false,
        'prior_period_adjustment' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $groups = Livewire::actingAs($f['user'])->test(OwnerBalance::class)->viewData('activityGroups');

    foreach ($groups as $group) {
        $currencies = array_values(array_unique(array_map(fn (array $l): string => (string) $l['currency'], $group['lines'])));
        expect($currencies)->toHaveCount(1)
            ->and($currencies[0])->toBe($group['currency']);
    }

    // ⚠️ THE PREMISE: July really does hold both currencies now, so the assertion above had something to catch.
    $julyLabels = array_values(array_filter($groups, fn (array $g): bool => $g['label'] === 'July 2026'));
    expect(count($julyLabels))->toBe(2);
});
