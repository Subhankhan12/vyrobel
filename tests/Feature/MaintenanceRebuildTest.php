<?php

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Mail\MaintenanceCostReportMail;
use App\Models\AuditEvent;
use App\Models\Availability;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Media;
use App\Models\OwnerExpense;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\MaintenanceCostService;
use App\Services\MaintenanceService;
use App\Services\MaintenanceSettingsService;
use App\Support\Maintenance\MaintenanceSla;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// OPERATOR MAINTENANCE exact-match (#89) — the builds on top of the certified lifecycle: auto-assignment
// wiring, the real labor/parts split (reconciling to the owner expense), the awaiting-parts state, proof
// photos, the real SLA breach, read-only, and mark-out-of-order genuinely blocking selling.

function mtManager(array $s): User
{
    return operatorUser($s, [], 'Hotel Manager');   // holds maintenance.manage
}

// ── Auto-assignment: a new ticket routes to the category's configured technician ─────────────────
it('auto-assigns a new ticket to the category technician when auto-assign is on', function () {
    $s = seedScenario(1);
    $tech = mtManager($s);

    MaintenanceSetting::create(['organization_id' => $s['org']->id, 'auto_assign_enabled' => true, 'assignee_plumbing_id' => $tech->id]);

    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leak', 'category' => 'plumbing']);
    expect((string) $ticket->assigned_to)->toBe((string) $tech->id);

    MaintenanceSetting::query()->first()->update(['auto_assign_enabled' => false]);
    $ticket2 = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leak 2', 'category' => 'plumbing']);
    expect($ticket2->assigned_to)->toBeNull();
});

// ── The real labor/parts split reconciles + ties to the owner expense ────────────────────────────
it('resolves with a real labor/parts split that reconciles and ties to the owner expense', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0]);   // owner + active agreement; unit now owned (shared helper)

    $svc = app(MaintenanceService::class);
    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Water heater']);
    $svc->start($ticket->refresh());
    $svc->resolve($ticket->refresh(), null, Money::of(9500, 'EUR'), Money::of(4500, 'EUR'));   // labor 95 + parts 45

    $fresh = $ticket->fresh();
    expect((int) $fresh->labor_minor)->toBe(9500)
        ->and((int) $fresh->parts_minor)->toBe(4500)
        ->and((int) $fresh->cost_minor)->toBe(14000)
        ->and((int) $fresh->labor_minor + (int) $fresh->parts_minor)->toBe((int) $fresh->cost_minor);

    $expense = OwnerExpense::withoutGlobalScopes()->where('source_type', $fresh->getMorphClass())->where('source_id', $fresh->id)->first();
    expect($expense)->not->toBeNull()->and((int) $expense->amount_minor)->toBe(14000);
});

// ── The awaiting-parts state is real ─────────────────────────────────────────────────────────────
it('flags and clears the awaiting-parts state', function () {
    $s = seedScenario(1);
    $svc = app(MaintenanceService::class);
    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Fridge']);

    $svc->setAwaitingParts($ticket, true, 'Compressor', '2026-09-20');
    expect($ticket->fresh()->awaiting_parts)->toBeTrue()->and((string) $ticket->fresh()->part_name)->toBe('Compressor');

    $svc->start($ticket->refresh());
    $svc->resolve($ticket->refresh(), Money::of(5000, 'EUR'));
    expect($ticket->fresh()->awaiting_parts)->toBeFalse();
});

// ── The SLA breach is a REAL computation ─────────────────────────────────────────────────────────
it('computes a real SLA breach from the priority target', function () {
    $s = seedScenario(1);
    $setting = MaintenanceSetting::create(['organization_id' => $s['org']->id, 'sla_urgent_hours' => 4]);

    $breaching = MaintenanceTicket::create(['organization_id' => $s['org']->id, 'unit_id' => $s['units'][0]->id, 'title' => 'Urgent leak', 'category' => 'plumbing', 'priority' => 'urgent', 'status' => 'open', 'opened_at' => now()->subHours(10)]);
    $sla = MaintenanceSla::evaluate($breaching, $setting);
    expect($sla['breached'])->toBeTrue()->and($sla['state'])->toBe('breached');

    $fresh = MaintenanceTicket::create(['organization_id' => $s['org']->id, 'unit_id' => $s['units'][0]->id, 'title' => 'New leak', 'category' => 'plumbing', 'priority' => 'urgent', 'status' => 'open', 'opened_at' => now()]);
    expect(MaintenanceSla::evaluate($fresh, $setting)['breached'])->toBeFalse();
});

// ── Proof photo upload attaches a real Media row ─────────────────────────────────────────────────
it('attaches a proof photo to a work order', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Crack']);

    $this->actingAs(mtManager($s))
        ->post(route('app.maintenance.photos.store', $ticket->id), ['photo' => UploadedFile::fake()->image('proof.jpg', 400, 300)])
        ->assertRedirect();

    $media = Media::query()->where('mediable_type', $ticket->getMorphClass())->where('mediable_id', $ticket->id)->where('collection', 'mt_photo')->first();
    expect($media)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $media->path);
});

// ── Read-only refuses maintenance writes ─────────────────────────────────────────────────────────
it('refuses opening a work order while the workspace is paused', function () {
    $s = seedScenario(1);
    $s['org']->forceFill(['paused_until' => now()->addMonth()])->save();

    $this->actingAs(mtManager($s))
        ->post(route('app.maintenance.store'), ['unit_id' => $s['units'][0]->id, 'title' => 'Nope', 'category' => 'other', 'priority' => 'normal'])
        ->assertRedirect();

    expect(MaintenanceTicket::query()->where('title', 'Nope')->exists())->toBeFalse();
});

// ── ⚠️ MARK-OUT-OF-ORDER genuinely BLOCKS SELLING (mutation-proven) ──────────────────────────────
it('blocks a unit from selling when a work order marks it out of order', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];

    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-09-01', '2026-09-10');
    expect((int) Availability::query()->where('room_type_id', $s['roomType']->id)->whereDate('date', '2026-09-01')->value('out_of_order'))->toBe(0);

    app(MaintenanceService::class)->open(['unit_id' => $unit->id, 'title' => 'Flood', 'takes_out_of_service' => true]);

    expect((string) $unit->refresh()->condition_status)->toBe('out_of_service')
        ->and((int) Availability::query()->where('room_type_id', $s['roomType']->id)->whereDate('date', '2026-09-01')->value('out_of_order'))->toBeGreaterThan(0);

    expect(fn () => book($s, ['unit_id' => $unit->id]))->toThrow(InsufficientAvailabilityException::class);
});

// ═══ GATE 2 · COSTS REPORT ═══════════════════════════════════════════════════════════════════════

// ── The cost report's labor/parts split reconciles to the total (#17) ─────────────────────────────
it('reports a labor/parts split that reconciles to total spend', function () {
    $s = seedScenario(1);
    $svc = app(MaintenanceService::class);

    $t1 = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'A']);
    $svc->start($t1->refresh());
    $svc->resolve($t1->refresh(), null, Money::of(6000, 'EUR'), Money::of(2000, 'EUR'));   // 60 + 20

    $t2 = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'B']);
    $svc->start($t2->refresh());
    $svc->resolve($t2->refresh(), Money::of(5000, 'EUR'));   // legacy single-cost path → all labour

    $report = app(MaintenanceCostService::class)->report([(string) $s['property']->id]);

    expect($report['total_minor'])->toBe(13000)
        ->and($report['labor_minor'] + $report['parts_minor'])->toBe($report['total_minor'])
        ->and($report['labor_minor'])->toBe(11000)   // 60 + 50 legacy
        ->and($report['parts_minor'])->toBe(2000)
        ->and($report['orders_closed'])->toBe(2);
});

// ── The billable line items tie to what the owner is billed ───────────────────────────────────────
it('billable line items equal the owner-billed cost', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0]);

    $svc = app(MaintenanceService::class);
    $t = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Boiler']);
    $svc->start($t->refresh());
    $svc->resolve($t->refresh(), null, Money::of(8000, 'EUR'), Money::of(3000, 'EUR'));

    $report = app(MaintenanceCostService::class)->report([(string) $s['property']->id]);
    $line = collect($report['billable'])->firstWhere('id', $t->id);

    $expense = OwnerExpense::withoutGlobalScopes()->where('source_type', $t->fresh()->getMorphClass())->where('source_id', $t->id)->first();

    expect($line)->not->toBeNull()
        ->and($line['labor_minor'] + $line['parts_minor'])->toBe($line['total_minor'])
        ->and($line['total_minor'])->toBe(11000)
        ->and((int) $expense->amount_minor)->toBe($line['total_minor']);   // ties to the owner statement
});

// ── Exports are gated on maintenance.manage ──────────────────────────────────────────────────────
it('refuses the CSV/PDF exports without maintenance.manage', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, [], 'Front Desk');   // no maintenance.manage

    $this->actingAs($frontDesk)->get(route('app.maintenance.costs.csv'))->assertForbidden();
    $this->actingAs($frontDesk)->get(route('app.maintenance.costs.pdf'))->assertForbidden();
});

// ── The CSV export is real (streams billable rows) + audited ──────────────────────────────────────
it('streams a real CSV of billable line items and audits the export', function () {
    $s = seedScenario(1);
    $svc = app(MaintenanceService::class);
    $t = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Tap swap']);
    $svc->start($t->refresh());
    $svc->resolve($t->refresh(), null, Money::of(4000, 'EUR'), Money::of(1000, 'EUR'));

    $res = $this->actingAs(mtManager($s))->get(route('app.maintenance.costs.csv'));
    $res->assertOk();
    $body = $res->streamedContent();

    expect($body)->toContain('Tap swap')->toContain('Total');
    expect(AuditEvent::withoutGlobalScopes()->where('action', 'maintenance.costs_exported')->exists())->toBeTrue();
});

// ── Email-to-me sends the PDF report to the operator's OWN address ────────────────────────────────
it('emails the costs report to the acting operator', function () {
    emailIsBeingSent();   // D-MAIL-CLAIMS: this path SAYS an email was sent, so email must be sent
    $s = seedScenario(1);
    $manager = mtManager($s);

    $this->actingAs($manager)->post(route('app.maintenance.costs.email'))->assertRedirect(route('app.maintenance.costs'));

    Mail::assertSent(MaintenanceCostReportMail::class, fn ($m) => $m->hasTo($manager->email));
});

// ═══ GATE 3 · SETTINGS ═══════════════════════════════════════════════════════════════════════════

// ── The auto-assign master toggle + category map persist through the controller ───────────────────
it('persists the auto-assign master toggle and category map via the settings controller', function () {
    $s = seedScenario(1);
    $tech = mtManager($s);

    $this->actingAs($tech)->post(route('app.maintenance.settings.update'), [
        'auto_assign_enabled' => '1',
        'assignee_plumbing_id' => $tech->id,
        'sla_urgent_hours' => 4,
    ])->assertRedirect(route('app.maintenance.settings'));

    $setting = MaintenanceSetting::query()->first();
    expect((bool) $setting->auto_assign_enabled)->toBeTrue()
        ->and((string) $setting->assignee_plumbing_id)->toBe((string) $tech->id)
        ->and((int) $setting->sla_urgent_hours)->toBe(4);

    // With the toggle ON, a new plumbing order really routes to the configured tech.
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leak', 'category' => 'plumbing']);
    expect((string) $ticket->assigned_to)->toBe((string) $tech->id);
});

// ── The master toggle OFF stops routing even with a configured assignee ───────────────────────────
it('does not auto-assign when the master toggle is off', function () {
    $s = seedScenario(1);
    $tech = mtManager($s);

    $this->actingAs($tech)->post(route('app.maintenance.settings.update'), [
        'assignee_plumbing_id' => $tech->id,   // auto_assign_enabled absent → false
    ])->assertRedirect();

    expect((bool) MaintenanceSetting::query()->first()->auto_assign_enabled)->toBeFalse();

    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leak', 'category' => 'plumbing']);
    expect($ticket->assigned_to)->toBeNull();
});

// ── The Technicians panel reports a REAL open-order load ──────────────────────────────────────────
it('reports a real open-order load in the technicians panel', function () {
    $s = seedScenario(1);
    $tech = mtManager($s);
    MaintenanceSetting::create(['organization_id' => $s['org']->id, 'assignee_plumbing_id' => $tech->id]);

    $svc = app(MaintenanceService::class);
    $svc->assign($svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'A'])->refresh(), $tech->id);
    $svc->assign($svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'B'])->refresh(), $tech->id);

    $panel = collect(app(MaintenanceSettingsService::class)->technicianPanel())->firstWhere('id', (string) $tech->id);
    expect($panel)->not->toBeNull()
        ->and($panel['load'])->toBe(2)
        ->and($panel['categories'])->toContain('plumbing');
});

// ── Settings are gated on maintenance.manage ─────────────────────────────────────────────────────
it('refuses the settings save without maintenance.manage', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, [], 'Front Desk');

    $this->actingAs($frontDesk)->post(route('app.maintenance.settings.update'), ['sla_urgent_hours' => 2])->assertForbidden();
    expect(MaintenanceSetting::query()->where('sla_urgent_hours', 2)->exists())->toBeFalse();
});
