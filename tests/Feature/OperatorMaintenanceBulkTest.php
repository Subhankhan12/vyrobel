<?php

use App\Models\MaintenanceTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GATE 6.2 — the Maintenance board BULK actions (select mode → bulk assign / resolve / export). Each loops the
// certified MaintenanceService per ticket (never a raw write); tenant-scoped (a foreign id is fail-safe-excluded);
// read-only + maintenance.manage gated; a ticket that can't transition is SKIPPED, not fatal.

function bulkOpenTicket(array $s, int $unitIdx, string $title): MaintenanceTicket
{
    return MaintenanceTicket::create([
        'unit_id' => $s['units'][$unitIdx]->id,
        'title' => $title,
        'category' => 'plumbing',
        'priority' => 'normal',
        'status' => 'open',
        'currency' => 'EUR',
        'opened_at' => now()->subHours(3),
    ]);
}

it('bulk-assigns the selected tickets to one member, skipping closed ones', function () {
    $s = seedScenario(3);
    $manager = operatorUser($s, ['name' => 'Manny Ager'], 'Hotel Manager');   // holds maintenance.manage + is a member
    $a = bulkOpenTicket($s, 0, 'Leaky tap');
    $b = bulkOpenTicket($s, 1, 'Broken blind');
    $closed = MaintenanceTicket::create(['unit_id' => $s['units'][2]->id, 'title' => 'Old one', 'category' => 'other',
        'priority' => 'low', 'status' => 'resolved', 'currency' => 'EUR', 'opened_at' => now()->subDay(), 'resolved_at' => now()]);

    $this->actingAs($manager)
        ->post(route('app.maintenance.bulk.assign'), ['ticket_ids' => [$a->id, $b->id, $closed->id], 'user_id' => $manager->id])
        ->assertRedirect();

    expect((string) $a->refresh()->assigned_to)->toBe((string) $manager->id)
        ->and((string) $b->refresh()->assigned_to)->toBe((string) $manager->id)
        ->and($closed->refresh()->assigned_to)->toBeNull();   // resolved → skipped, unchanged
});

it('bulk-resolves the selected active tickets (starting open ones first), skipping terminal ones', function () {
    $s = seedScenario(3);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $open = bulkOpenTicket($s, 0, 'Open one');
    $already = MaintenanceTicket::create(['unit_id' => $s['units'][1]->id, 'title' => 'Done', 'category' => 'other',
        'priority' => 'low', 'status' => 'resolved', 'currency' => 'EUR', 'opened_at' => now()->subDay(), 'resolved_at' => now()]);

    $this->actingAs($manager)
        ->post(route('app.maintenance.bulk.resolve'), ['ticket_ids' => [$open->id, $already->id]])
        ->assertRedirect();

    expect($open->refresh()->status)->toBe('resolved')       // open → started → resolved
        ->and($already->refresh()->status)->toBe('resolved'); // unchanged (skipped)
});

it('bulk-exports the selected tickets as a CSV (no cross-org leak, no guest PII)', function () {
    $s = seedScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $a = bulkOpenTicket($s, 0, 'Export me');

    // A DIFFERENT org's ticket id must not appear (tenant-scoped whereIn).
    $other = seedScenario(1);
    $foreign = bulkOpenTicket($other, 0, 'Foreign secret');

    $res = $this->actingAs($manager)
        ->post(route('app.maintenance.bulk.export'), ['ticket_ids' => [$a->id, $foreign->id]]);

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('text/csv');
    $body = $res->streamedContent();
    expect($body)->toContain('Export me')          // this org's ticket is exported
        ->and($body)->not->toContain('Foreign secret');   // the other org's ticket is NOT
});

it('neutralises a CSV formula-injection payload in a bulk-exported title', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $evil = bulkOpenTicket($s, 0, "=cmd|'/c calc'!A1");   // a formula/DDE payload as a work-order title

    $res = $this->actingAs($manager)->post(route('app.maintenance.bulk.export'), ['ticket_ids' => [$evil->id]]);

    $res->assertOk();
    // The leading '=' is quote-prefixed so a spreadsheet treats it as literal text, not a formula.
    expect($res->streamedContent())->toContain("'=cmd|'/c calc'!A1")
        ->and($res->streamedContent())->not->toContain("\n=cmd");   // never a raw formula at a field start
});

it('refuses bulk actions when the workspace is read-only (paused)', function () {
    $s = seedScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $a = bulkOpenTicket($s, 0, 'Leaky tap');
    $s['org']->update(['paused_until' => now()->addDays(5)]);

    $this->actingAs($manager)
        ->post(route('app.maintenance.bulk.assign'), ['ticket_ids' => [$a->id], 'user_id' => $manager->id])
        ->assertRedirect();

    expect($a->refresh()->assigned_to)->toBeNull();   // nothing changed
});

it('forbids bulk actions without maintenance.manage', function () {
    $s = seedScenario(2);
    $frontDesk = operatorUser($s, [], 'Front Desk');   // does NOT hold maintenance.manage
    $a = bulkOpenTicket($s, 0, 'Leaky tap');

    $this->actingAs($frontDesk)
        ->post(route('app.maintenance.bulk.resolve'), ['ticket_ids' => [$a->id]])
        ->assertForbidden();

    expect($a->refresh()->status)->toBe('open');
});
