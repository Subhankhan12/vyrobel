<?php

use App\Models\HousekeepingTask;
use App\Models\Media;
use App\Models\Membership;
use App\Models\User;
use App\Services\HousekeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// OPERATOR HOUSEKEEPING actions (#88) — the real management the wireframe rebuild adds: started_at (real
// elapsed), assign + priority, assign-all auto-balance, proof-photo upload, read-only. The owner read-only
// cleaning view uses the SAME HousekeepingTask and must stay undisturbed (proven by the green owner suite).

function hkActor(array $s, string $role = 'Hotel Manager'): User
{
    return operatorUser($s, [], $role);
}

/** A real housekeeper: an active member holding the Housekeeping role (StaffRoster::housekeepers). */
function hkCleaner(array $s, string $name): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $s['org'], 'Housekeeping');
}

function hkT(array $s, int $unitIndex, array $overrides = []): HousekeepingTask
{
    return HousekeepingTask::create(array_merge([
        'organization_id' => $s['org']->id,
        'property_id' => $s['property']->id,
        'unit_id' => $s['units'][$unitIndex]->id,
        'date' => '2026-09-03',
        'type' => 'departure',
        'status' => 'pending',
        'priority' => 'normal',
    ], $overrides));
}

// ── started_at is set on the in_progress transition → elapsed becomes REAL ───────────────────────
it('stamps started_at when a task is marked in progress', function () {
    $s = seedScenario(1);
    $task = hkT($s, 0);

    expect($task->started_at)->toBeNull();

    app(HousekeepingService::class)->updateTaskStatus($task, 'in_progress');

    expect($task->fresh()->started_at)->not->toBeNull();
});

// ── Assign + "clean next" priority through the guarded controller ────────────────────────────────
it('assigns a real housekeeper and bumps priority through the controller', function () {
    $s = seedScenario(1);
    $cleaner = hkCleaner($s, 'Rosa Delacroix');
    $task = hkT($s, 0);

    $this->actingAs(hkActor($s))
        ->post(route('app.housekeeping.assign', $task->id), ['user_id' => $cleaner->id, 'priority' => '1'])
        ->assertRedirect(route('app.housekeeping'));

    $fresh = $task->fresh();
    expect((string) $fresh->assigned_to)->toBe((string) $cleaner->id)
        ->and((string) $fresh->priority)->toBe('high');
});

// ── Assign-all auto-balances the unassigned rooms across housekeepers by load ────────────────────
it('assign-all balances unassigned rooms across housekeepers by least load', function () {
    $s = seedScenario(4);
    $a = hkCleaner($s, 'Ana Cleaner');
    $b = hkCleaner($s, 'Bo Cleaner');

    // 4 unassigned rooms, 2 housekeepers, both starting at load 0 → a balanced split (2 + 2).
    foreach (range(0, 3) as $i) {
        hkT($s, $i);
    }

    $this->actingAs(hkActor($s, 'Property Owner'))
        ->post(route('app.housekeeping.assign-all'))
        ->assertRedirect(route('app.housekeeping'));

    $loadA = HousekeepingTask::query()->where('assigned_to', $a->id)->count();
    $loadB = HousekeepingTask::query()->where('assigned_to', $b->id)->count();

    expect(HousekeepingTask::query()->whereNull('assigned_to')->count())->toBe(0)   // everything assigned
        ->and(abs($loadA - $loadB))->toBeLessThanOrEqual(1);                         // genuinely balanced
});

// ── Proof-photo upload attaches a real Media row on the task ─────────────────────────────────────
it('attaches a proof photo to the task', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $task = hkT($s, 0);

    $this->actingAs(hkActor($s))
        ->post(route('app.housekeeping.photos.store', $task->id), ['photo' => UploadedFile::fake()->image('clean.jpg', 400, 300)])
        ->assertRedirect();

    $media = Media::query()->where('mediable_type', $task->getMorphClass())->where('mediable_id', $task->id)->where('collection', 'hk_proof')->first();
    expect($media)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $media->path);
});

// ── The assign routes are gated (housekeeping.manage) ────────────────────────────────────────────
it('refuses assign-all from a user without housekeeping.manage', function () {
    $s = seedScenario(1);
    hkCleaner($s, 'Ana Cleaner');
    hkT($s, 0);

    // Front Desk holds housekeeping.view but not housekeeping.manage.
    $this->actingAs(hkActor($s, 'Front Desk'))
        ->post(route('app.housekeeping.assign-all'))
        ->assertForbidden();
});

// ── Read-only (#84) refuses housekeeping writes ──────────────────────────────────────────────────
it('refuses housekeeping writes while the workspace is paused', function () {
    $s = seedScenario(1);
    $s['org']->forceFill(['paused_until' => now()->addMonth()])->save();
    $cleaner = hkCleaner($s, 'Ana Cleaner');
    $task = hkT($s, 0);

    $this->actingAs(hkActor($s, 'Property Owner'))
        ->post(route('app.housekeeping.status', $task->id), ['status' => 'in_progress'])
        ->assertRedirect();

    expect($task->fresh()->status)->toBe('pending');   // untouched
});
