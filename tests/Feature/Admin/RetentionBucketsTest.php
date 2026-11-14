<?php

declare(strict_types=1);

use App\Support\Privacy\RetentionCoverage;
use App\Support\Retention\RetentionDecisions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

/**
 * DEC-4 (his ruling, 2026-11-13: "C") — the retention card shows THREE BUCKETS instead of one ratio.
 *
 *   · pruned by a timer — every store the category names is deleted by code on a schedule;
 *   · kept on purpose   — every store is pruned OR has a written reason + period + date on record (the registry);
 *   · no rule yet       — everything else (at least one store has neither).
 *
 * The count rises only when someone decides: a half-filled decision counts for nothing.
 */
function bucketInventory(array $categories): string
{
    $path = tempnam(sys_get_temp_dir(), 'inv');
    file_put_contents($path, json_encode(['categories' => $categories]));

    return $path;
}

function bucketsFor(array $categories, ?array $decisions = []): array
{
    $path = bucketInventory($categories);
    $out = (new RetentionCoverage)->compute($path, $decisions);
    unlink($path);

    return $out;
}

$decision = ['reason' => 'Evidence for owner disputes.', 'period' => 'Seven years from the end of the stay.', 'decided_on' => '2026-11-20'];

it('puts a category in "pruned by a timer" only when EVERY store it names is pruned by code', function () {
    $c = bucketsFor([
        ['category' => 'A', 'tables' => ['failed_jobs']],
        ['category' => 'B', 'tables' => ['failed_jobs', 'notifications']],
        ['category' => 'C', 'tables' => ['failed_jobs', 'users']],   // one store pruned, one not
    ]);

    expect($c['buckets']['pruned'])->toBe(2)->and($c['buckets']['none'])->toBe(1);
});

it('counts a category with a store that has neither a timer nor a decision as "no rule yet" — and says how many are PARTLY pruned', function () {
    $c = bucketsFor([
        ['category' => 'A', 'tables' => ['users']],
        ['category' => 'B', 'tables' => ['failed_jobs', 'users']],
        ['category' => 'C', 'tables' => []],   // data that is not in a table
    ]);

    expect($c['buckets']['none'])->toBe(3)->and($c['buckets']['partly'])->toBe(1)->and($c['buckets']['pruned'])->toBe(0)->and($c['buckets']['kept'])->toBe(0);
});

it('puts a category in "kept on purpose" when every unpruned store has a COMPLETE decision on record', function () use ($decision) {
    $c = bucketsFor([
        ['category' => 'A', 'tables' => ['audit_events']],
        ['category' => 'B', 'tables' => ['failed_jobs', 'audit_events']],   // pruned + decided
        ['category' => 'C', 'tables' => ['audit_events', 'users']],        // 'users' has no decision
    ], ['audit_events' => $decision]);

    expect($c['buckets']['kept'])->toBe(2)->and($c['buckets']['none'])->toBe(1);
});

it('a half-filled decision counts for nothing — reason, period and date are all required', function () use ($decision) {
    foreach (['reason', 'period', 'decided_on'] as $missing) {
        $partial = $decision;
        $partial[$missing] = '';
        $c = bucketsFor([['category' => 'A', 'tables' => ['audit_events']]], ['audit_events' => $partial]);

        expect($c['buckets']['kept'])->toBe(0, "a decision without its {$missing} was counted")->and($c['buckets']['none'])->toBe(1);
    }
});

it('the three buckets add up to every category counted — nothing is dropped, nothing is typed', function () use ($decision) {
    $c = bucketsFor([
        ['category' => 'A', 'tables' => ['failed_jobs']],
        ['category' => 'B', 'tables' => ['audit_events']],
        ['category' => 'C', 'tables' => ['users']],
        ['category' => 'D', 'tables' => []],
        ['category' => 'E', 'tables' => ['not_a_real_table_anywhere']],
    ], ['audit_events' => $decision]);

    expect($c['categories'])->toBe(5)->and($c['buckets']['pruned'] + $c['buckets']['kept'] + $c['buckets']['none'])->toBe(5);
});

it('every decision the registry ships is complete, dated, and says it was the founder\'s own call without legal advice', function () {
    // ⚠️ This used to pin the registry EMPTY ("the lawyer's periods are not invented"). On 2026-11-14 the founder decided the money records' period
    // himself, without a lawyer; what the test guards now is that nothing half-filled or unattributed ships, and that the entries stay honest about
    // who decided and on what basis.
    expect(RetentionDecisions::DECISIONS)->not->toBe([]);

    foreach (RetentionDecisions::DECISIONS as $table => $decision) {
        expect(RetentionDecisions::isComplete($decision))->toBeTrue("$table: the decision is not complete")
            ->and($decision['decided_on'])->toBe('2026-11-14')
            ->and($decision['reason'])->toContain('DECIDED BY THE FOUNDER')->toContain('WITHOUT legal advice')
            ->and($decision['period'])->toBe(RetentionDecisions::FINANCIAL_RECORDS_YEARS.' years');
    }

    expect(RetentionDecisions::isComplete(['reason' => 'r', 'period' => 'p', 'decided_on' => '2026-01-01']))->toBeTrue()
        ->and(RetentionDecisions::isComplete(['reason' => 'r', 'period' => '', 'decided_on' => '2026-01-01']))->toBeFalse()
        ->and(RetentionDecisions::isComplete(null))->toBeFalse();
});

it('names only tables that exist — a typo would silently count for nothing', function () {
    foreach (array_keys(RetentionDecisions::DECISIONS) as $table) {
        expect(Schema::hasTable($table))->toBeTrue("$table is not a table");
    }
});

it('does not decide what is not a money record (the audit and event logs hold staff IP addresses — a different question)', function () {
    expect(RetentionDecisions::DECISIONS)->not->toHaveKey('audit_events')->not->toHaveKey('domain_events')->not->toHaveKey('guest_documents');
});

it('the Delete-my-account card promises the same number of years the registry records', function () {
    $card = (string) (require base_path('lang/en/components/security/deletion-card.php'))['sub'];

    expect($card)->toContain('kept for '.RetentionDecisions::FINANCIAL_RECORDS_YEARS.' years')
        ->not->toContain('may have to be kept');
});

it('an unreadable or missing inventory still says so and gives no buckets', function () {
    $gone = (new RetentionCoverage)->compute('/nonexistent/data-inventory.json');

    expect($gone['measurable'])->toBeFalse()->and($gone['buckets'])->toBeNull();
});

it('the privacy desk shows the three buckets, each counted, and no longer says it awaits a ruling', function () {
    $res = $this->actingAs(platformAdmin(), 'admin')->get(route('admin.privacy.index'))->assertOk();
    $c = (new RetentionCoverage)->compute();

    $res->assertSee('Pruned by a timer')->assertSee('Kept on purpose')->assertSee('No rule yet')
        ->assertSee($c['buckets']['pruned'].' of '.$c['categories'])
        ->assertSee($c['buckets']['none'].' of '.$c['categories'])
        ->assertDontSee('Strict reading')->assertDontSee('Lenient reading')->assertDontSee('Awaiting the founder');
    assertNoRawBlade($res);
});
