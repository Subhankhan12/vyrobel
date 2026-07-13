<?php

use App\Models\DomainEvent;
use App\Models\MaintenanceTicket;
use App\Services\AvailabilityService;
use App\Services\MaintenanceService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Maintenance module · Step 1 — the ticket LIFECYCLE only (no out-of-service / no owner-expense wiring
// yet). Guarded transitions, cost in minor units, tenant-scoped, morph-mapped; a plain ticket event does
// NOT touch availability/sold.

function openTicket(array $s, array $overrides = []): MaintenanceTicket
{
    return app(MaintenanceService::class)->open(array_merge([
        'unit_id' => $s['units'][0]->id,
        'title' => 'Leaking tap',
        'category' => 'plumbing',
        'priority' => 'high',
    ], $overrides));
}

// ── Full lifecycle: open → assign → start → resolve (with cost in minor units) ───────────────────
it('runs the ticket lifecycle through the service', function () {
    $s = seedScenario(1);
    $svc = app(MaintenanceService::class);

    $ticket = openTicket($s);
    expect($ticket->status)->toBe('open')->and($ticket->opened_at)->not->toBeNull();

    $svc->assign($ticket, $s['guest'] ? null : null);   // assign (nullable) — no user needed here
    $ticket = $svc->start($ticket);
    expect($ticket->status)->toBe('in_progress');

    $ticket = $svc->resolve($ticket, Money::of(15000, 'EUR'));
    expect($ticket->status)->toBe('resolved')
        ->and($ticket->cost_minor)->toBe(15000)       // integer minor units
        ->and($ticket->currency)->toBe('EUR')
        ->and($ticket->resolved_at)->not->toBeNull();
});

// ── Cancel path ──────────────────────────────────────────────────────────────────────────────────
it('cancels an open ticket', function () {
    $s = seedScenario(1);
    $ticket = app(MaintenanceService::class)->cancel(openTicket($s));

    expect($ticket->status)->toBe('cancelled');
});

// ── Guarded transitions: illegal jumps are rejected ──────────────────────────────────────────────
it('rejects illegal status transitions', function () {
    $s = seedScenario(1);
    $svc = app(MaintenanceService::class);

    // resolved is terminal — can't re-resolve, start, or cancel it.
    $resolved = $svc->resolve(openTicket($s));
    expect(fn () => $svc->resolve($resolved))->toThrow(RuntimeException::class);
    expect(fn () => $svc->start($resolved))->toThrow(RuntimeException::class);
    expect(fn () => $svc->cancel($resolved))->toThrow(RuntimeException::class);

    // cancelled is terminal — can't resolve or start it.
    $cancelled = $svc->cancel(openTicket($s));
    expect(fn () => $svc->resolve($cancelled))->toThrow(RuntimeException::class);
    expect(fn () => $svc->start($cancelled))->toThrow(RuntimeException::class);
});

// ── Idempotency where sensible ───────────────────────────────────────────────────────────────────
it('is idempotent on re-start and same-assignee assign', function () {
    $s = seedScenario(1);
    $svc = app(MaintenanceService::class);

    $ticket = $svc->start(openTicket($s));
    expect($svc->start($ticket)->status)->toBe('in_progress');   // re-start = no-op

    $svc->assign($ticket, null);
    expect($svc->assign($ticket, null)->assigned_to)->toBeNull();   // same assignee = no-op
});

// ── Tenant isolation: org B cannot see org A's ticket ────────────────────────────────────────────
it('scopes tickets to the tenant', function () {
    $sA = seedScenario(1);
    $ticket = openTicket($sA);

    seedScenario(1);   // switches context to a fresh org B
    expect(MaintenanceTicket::query()->find($ticket->id))->toBeNull();

    app(TenantContext::class)->set($sA['org']->id);
    expect(MaintenanceTicket::query()->find($ticket->id))->not->toBeNull();
});

// ── Morph map: the enforced alias, not an FQCN ───────────────────────────────────────────────────
it('uses the enforced morph alias', function () {
    $s = seedScenario(1);
    $ticket = openTicket($s);

    expect($ticket->getMorphClass())->toBe('maintenance_ticket')
        // the domain event stored the alias too (enforced morph map — an unmapped model would throw)
        ->and(DomainEvent::query()->where('event_type', 'maintenance_ticket.opened')->value('subject_type'))->toBe('maintenance_ticket');
});

// ── A plain ticket event does NOT touch availability/sold (no out-of-service wiring in Step 1) ───
it('does not affect availability or sold when a ticket is opened', function () {
    $s = seedScenario(3);   // 3 units, availability published 2026-09-01..10
    $availability = app(AvailabilityService::class);

    $availableBefore = $availability->availableOn($s['roomType']->id, '2026-09-05');
    $soldBefore = soldOn($s['roomType']->id, '2026-09-05');

    // Even a takes_out_of_service ticket does nothing yet — that wiring is Step 2.
    app(MaintenanceService::class)->start(openTicket($s, ['takes_out_of_service' => true]));

    expect($availability->availableOn($s['roomType']->id, '2026-09-05'))->toBe($availableBefore)
        ->and(soldOn($s['roomType']->id, '2026-09-05'))->toBe($soldBefore);
});
