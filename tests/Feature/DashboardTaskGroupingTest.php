<?php

use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\OperatorDashboardPreference;
use App\Models\OperatorTaskState;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// D1-2 — grouping the derived task rows by category, with a personal persisted preference.
//
// ⚠️ THE INVARIANT THIS FILE EXISTS FOR. Grouped by PRIORITY, urgency is the section heading. Grouped by
// CATEGORY it is not — so unless the urgency travels with the rows, simply re-arranging the list would bury
// which work is urgent. That is the same harm D1-1 guarded against (a preference must never hide real work),
// in the shape this feature can actually take. Tests 4–6 pin it.
//
// ⚠️ Needles are scoped to the task card's own prose/markup: D1-1 learned twice that the layout's CSS ships
// (so a bare class token is never absent) and that "All caught up" also comes from the notifications bell.

/** An operator whose dashboard has tasks in TWO categories — work orders (urgent) and housekeeping. */
function groupingFixture(): array
{
    $s = seedScenario(1);
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);

    MaintenanceTicket::create([
        'organization_id' => $s['org']->id, 'unit_id' => $s['units'][0]->id, 'title' => 'Burst pipe',
        'category' => 'plumbing', 'priority' => 'urgent', 'status' => 'open', 'opened_at' => now(),
    ]);

    HousekeepingTask::create([
        'property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id,
        'date' => now()->toDateString(), 'type' => 'departure', 'status' => 'pending',
    ]);

    return ['s' => $s, 'operator' => $operator];
}

// ── 1. Default grouping is PRIORITY, and the switch is offered ───────────────────────────────────
it('groups by priority by default and offers the switch', function () {
    $f = groupingFixture();

    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Group by')
        ->assertSee('vy-task-group-sw', false)
        ->assertSee('Resolve urgent work orders')
        ->assertSee('Rooms to clean')
        // Priority headings, not category ones.
        ->assertSee('>Urgent<', false)
        ->assertDontSee('>Work orders<', false);
});

// ── 2. Choosing CATEGORY re-groups the SAME rows, and persists ───────────────────────────────────
it('re-groups by category and remembers the choice', function () {
    $f = groupingFixture();

    $this->actingAs($f['operator'])
        ->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => 'category'])
        ->assertRedirect();

    expect(DB::table('operator_dashboard_preferences')->where('user_id', $f['operator']->id)->value('task_grouping'))
        ->toBe('category');

    // A fresh request — the preference is read from the store, not from the request that set it.
    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertSee('>Work orders<', false)          // category headings now
        ->assertSee('>Housekeeping<', false)
        // ⚠️ THE SAME ROWS. Grouping re-arranges; it never filters.
        ->assertSee('Resolve urgent work orders')
        ->assertSee('Rooms to clean')
        ->assertSee('1 urgent')                       // the real counts, unchanged
        ->assertSee('1 to clean');                    // ⚠️ this fixture's count, not the demo's
});

// ── 3. Switching back to priority persists too ───────────────────────────────────────────────────
it('switches back to priority', function () {
    $f = groupingFixture();
    $route = route('app.dashboard.tasks.grouping');

    $this->actingAs($f['operator'])->post($route, ['task_grouping' => 'category'])->assertRedirect();
    $this->actingAs($f['operator'])->post($route, ['task_grouping' => 'priority'])->assertRedirect();

    expect(DB::table('operator_dashboard_preferences')->count())->toBe(1)   // updated, not appended
        ->and(DB::table('operator_dashboard_preferences')->value('task_grouping'))->toBe('priority');

    $this->actingAs($f['operator'])->get('/app/dashboard')->assertOk()->assertSee('>Urgent<', false);
});

// ── 4. ⚠️⚠️ THE INVARIANT — re-grouping by category keeps the urgent signal ───────────────────────
it('keeps the urgent signal visible when grouped by category', function () {
    $f = groupingFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => 'category'])->assertRedirect();

    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        // The heading no longer says "Urgent", so the urgency rides the section as a real count …
        // ⚠️ THE CLASS ATTRIBUTE, not the bare token: this pass's own CSS rule `.vy-task-sec-urgent` ships
        // in the layout (§N.15.3, again), so a bare needle would match the stylesheet and prove nothing.
        ->assertSee('class="vy-task-sec-urgent"', false)
        ->assertSee('1 urgent')
        // … and each row keeps its own priority mark, with a screen-reader name.
        ->assertSee('vy-task-row-dot is-urgent', false)
        ->assertSee('Urgent priority');
});

// ── 5. ⚠️ THE SIGNAL IS GOVERNED BY THE REAL COUNT, NOT THE PREFERENCE ───────────────────────────
it('clears the urgent signal when the real work is resolved, whatever the grouping', function () {
    $f = groupingFixture();
    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => 'category'])->assertRedirect();

    // The urgent work is genuinely done.
    MaintenanceTicket::query()->update(['status' => 'resolved']);

    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertDontSee('class="vy-task-sec-urgent"', false)   // no urgent signal (markup, not the CSS rule) …
        ->assertDontSee('Resolve urgent work orders')    // … because there is no urgent work
        ->assertSee('Rooms to clean');                   // the rest of the list is untouched

    // The preference is still 'category' — the signal cleared because the WORK cleared, not the setting.
    expect(DB::table('operator_dashboard_preferences')->value('task_grouping'))->toBe('category');
});

// ── 6. ⚠️ A preference can never remove a row: both groupings render the identical set ────────────
it('renders the same tasks under either grouping', function () {
    $f = groupingFixture();
    $rows = ['Resolve urgent work orders', 'Rooms to clean'];

    foreach (['priority', 'category'] as $grouping) {
        $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => $grouping])->assertRedirect();

        $response = $this->actingAs($f['operator'])->get('/app/dashboard')->assertOk();
        foreach ($rows as $row) {
            $response->assertSee($row);
        }
    }
});

// ── 7. 🔒 PERSONAL — one operator's grouping never changes another's ──────────────────────────────
it('keeps the grouping personal to the operator who chose it', function () {
    $f = groupingFixture();
    $other = operatorUser($f['s']);

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => 'category'])->assertRedirect();

    // The other operator still sees the default — priority headings, no category ones.
    $this->actingAs($other)->get('/app/dashboard')
        ->assertOk()
        ->assertSee('>Urgent<', false)
        ->assertDontSee('>Work orders<', false);

    expect(DB::table('operator_dashboard_preferences')->where('user_id', $other->id)->count())->toBe(0);
});

// ── 8. 🔒 The preference is written for the AUTHENTICATED user — a forged id is ignored ───────────
it('ignores a user id supplied in the request', function () {
    $f = groupingFixture();
    $victim = operatorUser($f['s']);

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), [
        'task_grouping' => 'category', 'user_id' => $victim->id,
    ])->assertRedirect();

    expect(DB::table('operator_dashboard_preferences')->where('user_id', $victim->id)->count())->toBe(0)
        ->and(DB::table('operator_dashboard_preferences')->where('user_id', $f['operator']->id)->count())->toBe(1);
});

// ── 9. ⚠️ IDEMPOTENT — repeating the same choice keeps ONE row (and the DB enforces it) ───────────
it('keeps exactly one preference row however often it is set', function () {
    $f = groupingFixture();
    $route = route('app.dashboard.tasks.grouping');

    foreach (['category', 'category', 'priority', 'category'] as $grouping) {
        $this->actingAs($f['operator'])->post($route, ['task_grouping' => $grouping])->assertRedirect();
    }

    expect(DB::table('operator_dashboard_preferences')->count())->toBe(1);

    // And the unique index is the other layer (§N.4p — a test per layer, not one that trusts the caller).
    expect(fn () => DB::table('operator_dashboard_preferences')->insert([
        'id' => (string) Str::ulid(),
        'organization_id' => $f['s']['org']->id,
        'user_id' => $f['operator']->id,
        'task_grouping' => 'priority',
        'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

// ── 10. ⚠️ The grouping value is a CLOSED set — junk is rejected, not stored ──────────────────────
it('rejects a grouping the view cannot render', function () {
    $f = groupingFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => 'by_vibes'])
        ->assertSessionHasErrors('task_grouping');

    expect(DB::table('operator_dashboard_preferences')->count())->toBe(0);
});

// ── 11. An unreadable stored value falls back to the default rather than rendering nothing ───────
it('falls back to the default grouping for an unknown stored value', function () {
    $f = groupingFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => 'category'])->assertRedirect();
    // Something wrote a value the view has no branch for (a bad migration, a manual edit).
    DB::table('operator_dashboard_preferences')->update(['task_grouping' => 'nonsense']);

    expect(OperatorDashboardPreference::groupingFor((string) $f['operator']->id))->toBe('priority');

    $this->actingAs($f['operator'])->get('/app/dashboard')->assertOk()->assertSee('>Urgent<', false);
});

// ── 12. The category comes from D1-1's task key, so the two cannot drift ─────────────────────────
it('derives every category from the task key set', function () {
    expect(OperatorTaskState::categoryFor('work_orders.urgent'))->toBe('work_orders')
        ->and(OperatorTaskState::categoryLabelFor('housekeeping.to_clean'))->toBe('Housekeeping')
        ->and(OperatorTaskState::categoryLabelFor('arrivals.to_check_in'))->toBe('Arrivals');

    // ⚠️ Every key the dashboard can emit must resolve to a NAMED category — an unlabelled one would render
    // a heading like "Work_orders".
    foreach (OperatorTaskState::KEYS as $key) {
        expect(OperatorTaskState::CATEGORY_LABELS)->toHaveKey(OperatorTaskState::categoryFor($key));
    }
});

// ── 13. D1-1's ticks still work under the new grouping (no regression) ───────────────────────────
it('keeps the personal tick working when grouped by category', function () {
    $f = groupingFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.grouping'), ['task_grouping' => 'category'])->assertRedirect();
    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.toggle'), [
        'task_key' => 'work_orders.urgent', 'ticked' => '1',
    ])->assertRedirect();

    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertSee('vy-task-line is-ticked', false)
        ->assertSee('1 urgent')                        // still true, still visible
        ->assertSee('class="vy-task-sec-urgent"', false);   // and still signalled on the category heading
});
