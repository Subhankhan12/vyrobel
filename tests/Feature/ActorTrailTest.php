<?php

use App\Models\AuditEvent;
use App\Models\HousekeepingTask;
use App\Models\RateSeason;
use App\Models\User;
use App\Services\HousekeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── F-17 · F-22 · F-9 — THREE WRITES THAT HAPPENED AND NOBODY COULD SAY WHO ──────────────────────
// Each defect was proven by finding ZERO matching audit rows. Each fix is proven the same way, in
// reverse: perform the action, QUERY THE TRAIL, and RESOLVE THE ACTOR TO A NAMED PERSON — exactly as
// Phase 10 resolved `maintenance_ticket.started`'s actor_id to a real user.
//
// ⚠️ A ROW WITH A NULL ACTOR CLOSES NOTHING, so every assertion below resolves the id to a User and
// reads their name. Asserting "the recorder was called" would be strictly weaker.

/** The trail row for an action, with its actor resolved to a real person — or null if unresolvable. */
function atrail(string $action): ?array
{
    $row = AuditEvent::query()->where('action', $action)->latest('created_at')->first();
    if ($row === null) {
        return null;
    }

    $actor = $row->actor_id !== null ? User::query()->find($row->actor_id) : null;

    return [
        'action' => (string) $row->action,
        'actor_type' => (string) $row->actor_type,
        'actor_id' => $row->actor_id,
        'actor_name' => $actor?->name,
        'subject_type' => $row->subject_type,
        'subject_id' => $row->subject_id,
        'before' => $row->before,
        'after' => $row->after,
    ];
}

it('⚠️ F-17 — a housekeeping status change now NAMES the person who made it', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Housekeeping');
    $actor->forceFill(['name' => 'Rosa Delacroix'])->save();

    $task = HousekeepingTask::create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id, 'date' => '2026-09-03',
        'type' => 'departure', 'status' => 'pending', 'priority' => 'normal',
    ]);

    // NON-VACUITY: the trail is empty for this action before the act.
    expect(atrail('housekeeping.task_updated'))->toBeNull();

    $this->actingAs($actor);
    app(HousekeepingService::class)->updateTaskStatus($task, 'in_progress', $actor);

    $row = atrail('housekeeping.task_updated');
    expect($row)->not->toBeNull()
        ->and($row['actor_name'])->toBe('Rosa Delacroix')      // ⚠️ RESOLVED TO A NAMED PERSON
        ->and($row['actor_type'])->toBe('user')
        ->and($row['subject_id'])->toBe((string) $task->getKey())
        ->and($row['before'])->toBe(['status' => 'pending'])
        ->and($row['after'])->toBe(['status' => 'in_progress']);
});

it('⚠️ F-22 — season create, edit and delete each name their actor, and the edit records what it changed FROM', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');
    $actor->forceFill(['name' => 'Paulo Nunes'])->save();
    $plan = $s['ratePlan'];

    expect(atrail('rate_season.created'))->toBeNull();

    // CREATE
    $this->actingAs($actor)->post(route('app.seasons.store'), [
        'rate_plan_id' => $plan->id, 'name' => 'Peak', 'starts_on' => '2026-12-20',
        'ends_on' => '2026-12-27', 'price' => '333.00',
    ]);
    $created = atrail('rate_season.created');
    expect($created['actor_name'])->toBe('Paulo Nunes')
        ->and($created['before'])->toBeNull()                       // honest null — it did not exist
        ->and($created['after']['price_minor'])->toBe(33300);

    $season = RateSeason::query()->latest('created_at')->firstOrFail();

    // EDIT — ⚠️ the `before` must be real, not null (Phase 11's 11-of-11 finding)
    $this->actingAs($actor)->put(route('app.seasons.update', $season->id), [
        'name' => 'Peak revised', 'starts_on' => '2026-12-20', 'ends_on' => '2026-12-28', 'price' => '444.00',
    ]);
    $edited = atrail('rate_season.updated');
    expect($edited['actor_name'])->toBe('Paulo Nunes')
        ->and($edited['before']['price_minor'])->toBe(33300)        // ⚠️ WHAT IT CHANGED FROM
        ->and($edited['after']['price_minor'])->toBe(44400)
        ->and($edited['before']['name'])->toBe('Peak');

    // DELETE — the audit row is the only surviving record (hard delete)
    $this->actingAs($actor)->delete(route('app.seasons.destroy', $season->id));
    $deleted = atrail('rate_season.deleted');
    expect($deleted['actor_name'])->toBe('Paulo Nunes')
        ->and($deleted['before']['price_minor'])->toBe(44400)
        ->and($deleted['after'])->toBeNull()
        ->and(RateSeason::query()->whereKey($season->id)->exists())->toBeFalse();
});

it('⚠️⚠️ F-9 — a password change is recorded, names the account, and stores NO secret', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');
    $actor->forceFill(['name' => 'Mara Silva'])->save();

    expect(atrail('user.password_changed'))->toBeNull();

    $this->actingAs($actor)->post(route('app.security.password'), [
        'current_password' => 'password',
        'password' => 'a-brand-new-secret-99',
        'password_confirmation' => 'a-brand-new-secret-99',
    ]);

    $row = atrail('user.password_changed');
    expect($row)->not->toBeNull()
        ->and($row['actor_name'])->toBe('Mara Silva')
        ->and($row['subject_id'])->toBe((string) $actor->getKey())
        ->and($row['after']['via'])->toBe('authenticated_session')
        ->and($row['after']['first_set'])->toBeFalse();

    // ⚠️⚠️ THE SECRET MUST NOT BE THERE — not the new password, not the old, not a hash of either.
    $blob = json_encode([$row['before'], $row['after']]);
    expect($blob)->not->toContain('a-brand-new-secret-99')
        ->and($blob)->not->toContain('password')
        ->and($blob)->not->toContain('$2y$')                        // no bcrypt hash
        ->and($row['before'])->toBeNull();
    expect((string) $actor->fresh()->password)->not->toBe((string) $row['after']['via']);
});

it('⚠️ F-9 — the RESET door records too, and names the account rather than leaving the actor null', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Hotel Manager');
    $user->forceFill(['name' => 'Tomas Reis'])->save();

    // Nobody is authenticated on a reset — the exact case where a naive recorder writes a NULL actor.
    auth()->logout();
    $user->changePasswordTo('another-new-secret-77');

    $row = atrail('user.password_changed');
    expect($row)->not->toBeNull()
        ->and($row['actor_id'])->not->toBeNull()                    // ⚠️ a null actor would close nothing
        ->and($row['actor_name'])->toBe('Tomas Reis')
        ->and($row['after']['via'])->toBe('reset_token');
});
