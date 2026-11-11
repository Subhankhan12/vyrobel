<?php

declare(strict_types=1);

use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RateProposal;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Support\Money\Money;
use App\Support\Property\PropertyScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ⚠️⚠️ DEC-i / DEC-j / DEC-q (2026-11-11, his "do all") — a write addressed by ID reaches only the properties the person can reach.
//
// Staff limited to some properties by the `property_user` allow-list (#38) could change ANOTHER property's records by posting an id,
// because these writers found their row through the TENANT scope only: the Portfolio's Edit, every maintenance write and the work
// order page, every housekeeping write, and the rate-proposal writes (which can apply a rate). Each now resolves through
// `App\Support\Property\PropertyScope`. A row outside the allow-list is a 404 — never a 403 that would confirm it exists.
//
// ⚠️ EVERY test here keeps a PERMITTED actor beside the refused one (CLAUDE.md #30): without it a 404 could be a masked 403 or a
// broken fixture, and "refused" would prove nothing.

uses(DatabaseTransactions::class);

/** A person scoped (by the allow-list) to ONE property — the first — of an organisation that has two. */
function pswScopedTo(array $s, string $propertyId, string $role = 'Hotel Manager'): User
{
    $user = operatorUser($s, [], $role);
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $user->id,
        'property_id' => $propertyId, 'created_at' => now(), 'updated_at' => now()]);

    return $user;
}

/** A unit on the SECOND property, with a work order and a housekeeping task on it. */
function pswFar(array $s): array
{
    $second = secondProperty($s);
    $rt = RoomType::create(['property_id' => $second->id, 'name' => 'Far Std', 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $second->id, 'room_type_id' => $rt->id, 'code' => 'FAR'.random_int(10, 99)]);
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $unit->id, 'title' => 'Far away', 'category' => 'other', 'priority' => 'low']);
    $task = HousekeepingTask::create(['property_id' => $second->id, 'unit_id' => $unit->id, 'date' => now()->toDateString(), 'type' => 'departure']);

    return compact('second', 'unit', 'ticket', 'task');
}

/** An owner with an exclusively-owned room type on `$property`, a linked portal user, a €180 rate today, and an operator. */
function pswProposalWorld(array $s, Property $property): array
{
    $rt = RoomType::create(['property_id' => $property->id, 'name' => 'Suite '.Str::random(4), 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'code' => 'U-'.Str::upper(Str::random(4))]);
    $plan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    $rt->update(['default_rate_plan_id' => $plan->id]);
    $owner = ownUnit($unit);
    $ownerUser = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $ownerUser->id, 'status' => 'active']);
    $owner->update(['user_id' => $ownerUser->id]);
    RateCalendar::create(['rate_plan_id' => $plan->id, 'date' => now()->toDateString(), 'price' => Money::of(18000, 'EUR')]);

    return ['owner' => $owner, 'ownerUser' => $ownerUser, 'operator' => operatorUser($s), 'rt' => $rt, 'plan' => $plan];
}

function pswRateMinor(string $planId): ?int
{
    $row = RateCalendar::query()->where('rate_plan_id', $planId)->whereDate('date', now()->toDateString())->first();

    return $row === null ? null : (int) $row->price_minor;
}

// ── DEC-i · the Portfolio's Edit ─────────────────────────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ DEC-i: Portfolio Edit changes only a property the person can reach (a 404 elsewhere, nothing written)', function () {
    $s = seedScenario(1);
    $far = secondProperty($s, 'Far House');
    $farName = (string) DB::table('properties')->where('id', $far->id)->value('name');   // the fixture adds a suffix — read what is stored
    $scoped = pswScopedTo($s, (string) $s['property']->id, 'Property Owner');

    $payload = fn (Property $p, string $name): array => ['property_id' => $p->id, 'name' => $name, 'type' => 'hotel', 'timezone' => 'UTC', 'status' => 'active'];

    // refused: the other property — and NOTHING changed on it
    $this->actingAs($scoped)->post(route('app.portfolio.update'), $payload($far, 'Renamed from afar'))->assertNotFound();
    expect(DB::table('properties')->where('id', $far->id)->value('name'))->toBe($farName);

    // PERMITTED CONTROL: the same person edits their own property
    $this->actingAs($scoped)->post(route('app.portfolio.update'), $payload($s['property'], 'My renamed house'))->assertRedirect(route('app.portfolio'));
    expect(DB::table('properties')->where('id', $s['property']->id)->value('name'))->toBe('My renamed house');

    // an organisation-wide manager (no pivot rows) still edits either
    $all = operatorUser($s, [], 'Property Owner');
    $this->actingAs($all)->post(route('app.portfolio.update'), $payload($far, 'Far House, renamed'))->assertRedirect(route('app.portfolio'));
    expect(DB::table('properties')->where('id', $far->id)->value('name'))->toBe('Far House, renamed');
})->group('security');

// ── DEC-j · maintenance ──────────────────────────────────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ DEC-j: every maintenance write, and the work order page, stays inside the person\'s properties', function () {
    $s = seedScenario(1);
    $far = pswFar($s);
    $scoped = pswScopedTo($s, (string) $s['property']->id);
    $ticket = $far['ticket'];
    $url = fn (string $name): string => route($name, $ticket->id);

    // WRITES: each one a 404 …
    $this->actingAs($scoped)->post($url('app.maintenance.start'))->assertNotFound();
    $this->actingAs($scoped)->post($url('app.maintenance.cancel'))->assertNotFound();
    $this->actingAs($scoped)->post($url('app.maintenance.resolve'), ['resolution' => 'Done'])->assertNotFound();
    $this->actingAs($scoped)->post($url('app.maintenance.assign'), ['user_id' => $scoped->id])->assertNotFound();
    $this->actingAs($scoped)->post($url('app.maintenance.parts'), ['parts' => 'a part'])->assertNotFound();
    $this->actingAs($scoped)->post($url('app.maintenance.notes.store'), ['body' => 'A note'])->assertNotFound();
    $this->actingAs($scoped)->post(route('app.maintenance.store'), ['unit_id' => $far['unit']->id, 'title' => 'New one', 'category' => 'other', 'priority' => 'low'])->assertNotFound();

    // … and nothing moved
    $fresh = MaintenanceTicket::query()->findOrFail($ticket->id);
    expect($fresh->status)->toBe('open')->and(MaintenanceTicket::query()->where('title', 'New one')->exists())->toBeFalse();

    // BULK: a mixed list acts on the permitted one only; the far one is silently absent (as a foreign id already was)
    $near = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Near', 'category' => 'other', 'priority' => 'low']);
    $this->actingAs($scoped)->post(route('app.maintenance.bulk.assign'), ['ticket_ids' => [$near->id, $ticket->id], 'user_id' => $scoped->id])->assertRedirect();
    expect((string) MaintenanceTicket::query()->findOrFail($near->id)->assigned_to)->toBe((string) $scoped->id)
        ->and(MaintenanceTicket::query()->findOrFail($ticket->id)->assigned_to)->toBeNull();

    // the EXPORT is a read of a ticket's title, unit, assignee and cost — it must not leak the far one either
    $csv = $this->actingAs($scoped)->post(route('app.maintenance.bulk.export'), ['ticket_ids' => [$near->id, $ticket->id]])->streamedContent();
    expect($csv)->toContain('Near')->not->toContain('Far away');

    // PERMITTED CONTROL: the same person starts their own
    $this->actingAs($scoped)->post(route('app.maintenance.start', $near->id))->assertRedirect();
    expect(MaintenanceTicket::query()->findOrFail($near->id)->status)->toBe('in_progress');

    // and an organisation-wide manager still reaches the far one
    $all = operatorUser($s);
    $this->actingAs($all)->post(route('app.maintenance.start', $ticket->id))->assertRedirect();
    expect(MaintenanceTicket::query()->findOrFail($ticket->id)->status)->toBe('in_progress');
})->group('security');

// (its own test: once a Livewire page has rendered, Livewire swaps the app's `redirect()` helper for the rest of that test, which
//  would turn the controller redirects above into a different class)
it('⚠️⚠️ DEC-j: the work order PAGE opens only for a ticket inside the person\'s properties', function () {
    $s = seedScenario(1);
    $far = pswFar($s);
    $near = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Near', 'category' => 'other', 'priority' => 'low']);
    $scoped = pswScopedTo($s, (string) $s['property']->id);

    $this->actingAs($scoped)->get(route('app.maintenance.show', $far['ticket']->id))->assertNotFound();
    $this->actingAs($scoped)->get(route('app.maintenance.show', $near->id))->assertOk();                  // PERMITTED CONTROL
    $this->actingAs(operatorUser($s))->get(route('app.maintenance.show', $far['ticket']->id))->assertOk();   // organisation-wide
})->group('security');

// ── DEC-j · housekeeping ─────────────────────────────────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ DEC-j: every housekeeping write stays inside the person\'s properties', function () {
    $s = seedScenario(1);
    $far = pswFar($s);
    $near = HousekeepingTask::create(['property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id, 'date' => now()->toDateString(), 'type' => 'departure']);
    $scoped = pswScopedTo($s, (string) $s['property']->id);
    $task = $far['task'];
    // a REAL housekeeper (the assign action validates the person first, so only then is the 404 the thing under test)
    $cleaner = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $cleaner->id, 'status' => 'active']);
    grantBackOfficeRole($cleaner, $s['org'], 'Housekeeping');

    $this->actingAs($scoped)->post(route('app.housekeeping.status', $task->id), ['status' => 'in_progress'])->assertNotFound();
    $this->actingAs($scoped)->post(route('app.housekeeping.assign', $task->id), ['user_id' => $cleaner->id])->assertNotFound();
    $this->actingAs($scoped)->post(route('app.housekeeping.unassign', $task->id))->assertNotFound();
    expect(HousekeepingTask::query()->findOrFail($task->id)->status)->toBe('pending');

    // BULK unassign: a far id is silently absent
    DB::table('housekeeping_tasks')->whereIn('id', [$near->id, $task->id])->update(['assigned_to' => $scoped->id]);
    $this->actingAs($scoped)->post(route('app.housekeeping.bulk-unassign'), ['task_ids' => [$near->id, $task->id]])->assertRedirect();
    expect(HousekeepingTask::query()->findOrFail($near->id)->assigned_to)->toBeNull()
        ->and((string) HousekeepingTask::query()->findOrFail($task->id)->assigned_to)->toBe((string) $scoped->id);

    // PERMITTED CONTROL: the near task moves, and can be assigned
    $this->actingAs($scoped)->post(route('app.housekeeping.status', $near->id), ['status' => 'in_progress'])->assertRedirect();
    expect(HousekeepingTask::query()->findOrFail($near->id)->status)->toBe('in_progress');
    $this->actingAs($scoped)->post(route('app.housekeeping.assign', $near->id), ['user_id' => $cleaner->id])->assertRedirect();
    expect((string) HousekeepingTask::query()->findOrFail($near->id)->assigned_to)->toBe((string) $cleaner->id);
})->group('security');

// ── DEC-q · rate proposals ───────────────────────────────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ DEC-q: a rate proposal — and accepting a counter, which applies the rate — stays inside the person\'s properties', function () {
    $s = seedScenario(1);
    $a = pswProposalWorld($s, $s['property']);                                                     // an owner's room type on the first property
    $b = pswProposalWorld($s, secondProperty($s));    // another owner's room type on the SECOND
    $scoped = pswScopedTo($s, (string) $s['property']->id);                        // a manager limited to the first
    $today = now()->toDateString();
    $baseline = pswRateMinor((string) $b['plan']->id);

    // refused: a proposal on the other property — no row, no rate change
    $this->actingAs($scoped)->post(route('app.rate-proposals.store'), [
        'owner_id' => $b['owner']->id, 'room_type_id' => $b['rt']->id,
        'rate' => '250', 'from_date' => $today, 'to_date' => now()->addDays(5)->toDateString(),
    ])->assertNotFound();
    expect(RateProposal::query()->where('room_type_id', $b['rt']->id)->exists())->toBeFalse()
        ->and(pswRateMinor((string) $b['plan']->id))->toBe($baseline);

    // a countered proposal on the other property: accepting it (which would apply the rate) and withdrawing it are both 404s
    $this->actingAs($b['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $b['owner']->id, 'room_type_id' => $b['rt']->id,
        'rate' => '185', 'from_date' => $today, 'to_date' => now()->addDays(5)->toDateString(),
    ])->assertRedirect();
    $far = RateProposal::query()->where('room_type_id', $b['rt']->id)->firstOrFail();
    $this->actingAs($b['ownerUser'])->post(route('owner.rate-proposals.counter', $far->id), ['counter_rate' => '195'])->assertRedirect();

    $this->actingAs($scoped)->post(route('app.rate-proposals.accept-counter', $far->id))->assertNotFound();
    $this->actingAs($scoped)->post(route('app.rate-proposals.withdraw', $far->id))->assertNotFound();
    expect($far->refresh()->state)->toBe('countered')->and(pswRateMinor((string) $b['plan']->id))->toBe($baseline);

    // PERMITTED CONTROL: the same person proposes on THEIR property
    $this->actingAs($scoped)->post(route('app.rate-proposals.store'), [
        'owner_id' => $a['owner']->id, 'room_type_id' => $a['rt']->id,
        'rate' => '185', 'from_date' => $today, 'to_date' => now()->addDays(5)->toDateString(),
    ])->assertRedirect();
    expect(RateProposal::query()->where('room_type_id', $a['rt']->id)->where('state', 'pending')->exists())->toBeTrue();

    // and an organisation-wide manager still accepts the far counter — the rate really changes
    $this->actingAs($b['operator'])->post(route('app.rate-proposals.accept-counter', $far->id))->assertRedirect();
    expect($far->refresh()->state)->toBe('approved')->and(pswRateMinor((string) $b['plan']->id))->toBe(19500);
})->group('security');

it('PropertyScope fails CLOSED: with no allow-list it finds nothing', function () {
    $s = seedScenario(1);
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'T', 'category' => 'other', 'priority' => 'low']);

    // outside a web request no property context is active → an empty allow-list, never "everything"
    expect(fn () => app(PropertyScope::class)->ticket((string) $ticket->id))
        ->toThrow(ModelNotFoundException::class);
});
