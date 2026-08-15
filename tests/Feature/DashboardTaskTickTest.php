<?php

use App\Models\MaintenanceTicket;
use App\Models\OperatorTaskState;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// D1-1 — ticking a dashboard task.
//
// ⚠️ THE SHAPE THIS FEATURE ACTUALLY HAS. The dashboard's task rows are DERIVED live from real counts and
// disappear on their own when the count hits zero — there is no task record to complete. So a tick is a
// PERSONAL, PER-DAY note ("I've been through this"), never a completion, and the load-bearing guarantee is
// the honesty one: a ticked row still shows its real, non-zero count, and nobody else's dashboard changes.
//
// Counts are read raw so a scoping bug cannot hide the row an assertion is meant to catch.

/** An operator whose dashboard has at least one REAL derived task (an urgent work order). */
function tickFixture(): array
{
    $s = seedScenario(1);
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);

    // A genuine urgent work order → the "Resolve urgent work orders" row renders with a live count.
    MaintenanceTicket::create([
        'organization_id' => $s['org']->id,
        'unit_id' => $s['units'][0]->id,        // the property is derived from the unit
        'title' => 'Burst pipe',
        'category' => 'plumbing',
        'priority' => 'urgent',
        'status' => 'open',
        'opened_at' => now(),
    ]);

    return ['s' => $s, 'operator' => $operator];
}

function rawTickCount(?string $userId = null, ?string $taskKey = null): int
{
    $q = DB::table('operator_task_states');
    if ($userId !== null) {
        $q->where('user_id', $userId);
    }
    if ($taskKey !== null) {
        $q->where('task_key', $taskKey);
    }

    return (int) $q->count();
}

// ── 1. The task renders with a real count, un-ticked, and offers the control ─────────────────────
it('renders a real derived task with a tick control', function () {
    $f = tickFixture();

    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Resolve urgent work orders')
        ->assertSee('1 urgent')                       // the REAL count
        ->assertSee('vy-task-tick', false)
        ->assertSee('0 of 1 ticked off');
});

// ── 2. Ticking persists and reflects — and ⚠️ the row and its real count STAY ─────────────────────
it('ticks a task, keeps the row and its real count, and never calls it done', function () {
    $f = tickFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.toggle'), [
        'task_key' => 'work_orders.urgent', 'ticked' => '1',
    ])->assertRedirect();

    expect(rawTickCount((string) $f['operator']->id, 'work_orders.urgent'))->toBe(1);

    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertSee('vy-task-line is-ticked', false)
        // ⚠️ THE HONESTY ASSERTION: the work is NOT finished, so the row and its live count remain, and
        // nothing on the page claims completion.
        ->assertSee('Resolve urgent work orders')
        ->assertSee('1 urgent')
        ->assertSee('the work itself is still open')
        // ⚠️ NEEDLE SCOPED TO THE TASK CARD. "All caught up" also comes from the NOTIFICATIONS BELL in the
        // shell, on every page — a page-wide assertion here passes or fails for reasons that have nothing
        // to do with tasks. This sentence exists only in the task list's own empty branch.
        ->assertDontSee('nothing needs attention right now');
});

// ── 3. Un-ticking removes the note ───────────────────────────────────────────────────────────────
it('un-ticks a task', function () {
    $f = tickFixture();
    $route = route('app.dashboard.tasks.toggle');

    $this->actingAs($f['operator'])->post($route, ['task_key' => 'work_orders.urgent', 'ticked' => '1'])->assertRedirect();
    expect(rawTickCount())->toBe(1);

    $this->actingAs($f['operator'])->post($route, ['task_key' => 'work_orders.urgent', 'ticked' => '0'])->assertRedirect();
    expect(rawTickCount())->toBe(0);

    $this->actingAs($f['operator'])->get('/app/dashboard')->assertOk()->assertSee('0 of 1 ticked off');
});

// ── 4. ⚠️ IDEMPOTENT — a double-submit writes ONE row, and a double un-tick is a no-op ────────────
it('writes exactly one row when the tick is submitted twice', function () {
    $f = tickFixture();
    $route = route('app.dashboard.tasks.toggle');

    $this->actingAs($f['operator'])->post($route, ['task_key' => 'work_orders.urgent', 'ticked' => '1'])->assertRedirect();
    $this->actingAs($f['operator'])->post($route, ['task_key' => 'work_orders.urgent', 'ticked' => '1'])->assertRedirect();

    expect(rawTickCount())->toBe(1);

    // Un-ticking twice is equally harmless.
    $this->actingAs($f['operator'])->post($route, ['task_key' => 'work_orders.urgent', 'ticked' => '0'])->assertRedirect();
    $this->actingAs($f['operator'])->post($route, ['task_key' => 'work_orders.urgent', 'ticked' => '0'])->assertRedirect();

    expect(rawTickCount())->toBe(0);
});

// ── 5. ⚠️ The unique index enforces it at the DATABASE, not just in the controller ────────────────
// The controller's firstOrCreate is one layer; this proves the other, so a future caller that forgets to
// guard still cannot double-write (§N.4p — defence in depth needs a test per layer).
it('refuses a duplicate tick at the database level', function () {
    $f = tickFixture();
    $key = [
        'organization_id' => $f['s']['org']->id,
        'user_id' => $f['operator']->id,
        'scope_key' => (string) $f['s']['property']->id,
        'task_key' => 'work_orders.urgent',
        'task_date' => now()->toDateString(),
    ];

    DB::table('operator_task_states')->insert($key + ['id' => (string) Str::ulid(), 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('operator_task_states')->insert($key + ['id' => (string) Str::ulid(), 'created_at' => now(), 'updated_at' => now()]))
        ->toThrow(QueryException::class);

    expect(rawTickCount())->toBe(1);
});

// ── 6. 🔒 PERSONAL — one operator's tick does not touch another's dashboard ───────────────────────
it('keeps a tick personal to the operator who made it', function () {
    $f = tickFixture();
    $other = operatorUser($f['s']);

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.toggle'), [
        'task_key' => 'work_orders.urgent', 'ticked' => '1',
    ])->assertRedirect();

    // ⚠️ The other operator still sees the task un-ticked — hiding urgent work from a teammate because
    // someone else glanced at it would be worse than having no tick at all.
    $this->actingAs($other)->get('/app/dashboard')
        ->assertOk()
        ->assertSee('0 of 1 ticked off')
        // ⚠️ THE RENDERED CLASS ATTRIBUTE, not the bare token: the layout's CSS rule `.vy-task-line.is-ticked`
        // SHIPS (§N.15.3), so assertDontSee('is-ticked') can never pass. The space-separated form only ever
        // appears in markup.
        ->assertDontSee('vy-task-line is-ticked', false);

    expect(rawTickCount((string) $other->id))->toBe(0);
});

// ── 7. 🔒 The tick is written for the AUTHENTICATED user — a forged user_id is ignored ────────────
it('ignores a user id supplied in the request', function () {
    $f = tickFixture();
    $victim = operatorUser($f['s']);

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.toggle'), [
        'task_key' => 'work_orders.urgent', 'ticked' => '1',
        'user_id' => $victim->id,          // forged
    ])->assertRedirect();

    expect(rawTickCount((string) $victim->id))->toBe(0)
        ->and(rawTickCount((string) $f['operator']->id))->toBe(1);
});

// ── 8. ⚠️ The key set is CLOSED — an unknown task_key is rejected, not stored ─────────────────────
it('rejects a task key the dashboard never produces', function () {
    $f = tickFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.toggle'), [
        'task_key' => 'whatever.i.like', 'ticked' => '1',
    ])->assertSessionHasErrors('task_key');

    expect(rawTickCount())->toBe(0);
});

// ── 9. ⚠️ PER-DAY — yesterday's tick says nothing about today ─────────────────────────────────────
it('does not let yesterday’s tick suppress today’s task', function () {
    $f = tickFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.toggle'), [
        'task_key' => 'work_orders.urgent', 'ticked' => '1',
        'task_date' => now()->subDay()->toDateString(),
    ])->assertRedirect();

    expect(rawTickCount())->toBe(1);

    // Today's dashboard is untouched by it.
    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertSee('0 of 1 ticked off')
        ->assertDontSee('vy-task-line is-ticked', false);   // the markup form; the CSS rule always ships
});

// ── 10. ⚠️ A tick NEVER survives the work clearing — the row is derived, so it just goes ──────────
// This is the property that makes the whole feature honest: the tick is a note ON a derived row, never a
// substitute for the row. Resolve the real work and the task disappears regardless of the tick.
it('drops the task entirely once the real work clears, tick or no tick', function () {
    $f = tickFixture();

    $this->actingAs($f['operator'])->post(route('app.dashboard.tasks.toggle'), [
        'task_key' => 'work_orders.urgent', 'ticked' => '1',
    ])->assertRedirect();

    // The real work is done — the ticket is resolved.
    MaintenanceTicket::query()->update(['status' => 'resolved']);

    $this->actingAs($f['operator'])->get('/app/dashboard')
        ->assertOk()
        ->assertDontSee('Resolve urgent work orders')
        // ⚠️ Task-card-specific: the shell's notification bell says "All caught up" too, so the generic
        // string would have made this pass whether or not the task list had cleared.
        ->assertSee('nothing needs attention right now');   // now genuinely true

    // The stale note may linger in the table; it can never resurrect a row that has no real work behind it.
    expect(rawTickCount())->toBe(1);
});

// ── 11. The task keys are stable strings, and the store only accepts those ───────────────────────
it('pins the closed task-key set', function () {
    expect(OperatorTaskState::KEYS)->toBe([
        'work_orders.urgent', 'work_orders.open', 'housekeeping.to_clean', 'arrivals.to_check_in',
    ]);

    // ⚠️ Every key the dashboard can emit must be accepted by the store, or a real row would be un-tickable.
    $source = file_get_contents(app_path('Livewire/Operator/OperatorDashboard.php'));
    foreach (OperatorTaskState::KEYS as $key) {
        expect($source)->toContain("'key' => '".$key."'");
    }
});
