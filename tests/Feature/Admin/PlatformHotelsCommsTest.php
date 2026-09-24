<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\PlatformAdminAction;
use App\Models\PlatformAnnouncement;
use App\Models\StatusIncident;
use App\Support\Platform\PlatformHotelStats;
use App\Support\Platform\PlatformQuery;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 5 — hotel statistics (aggregates only) · module adoption (real events) · cohorts (honest when
// thin) · announcements (audited, dismissible, escaped) · Channex "not connected" · the PUBLIC status page (manual
// incidents only, never an internal).

uses(DatabaseTransactions::class);

function hcStats(bool $internal = false): array
{
    test()->actingAs(platformAdmin(), 'admin');

    return app(PlatformQuery::class)->read('test.hotels', fn () => app(PlatformHotelStats::class)->compute($internal));
}

function hcEvent(string $orgId, string $type, string $at): void
{
    DB::table('domain_events')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'event_type' => $type, 'occurred_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
}

function hcReason(): string
{
    return 'Phase 5 test — a written reason.';
}

// ══ HOTEL STATISTICS ═════════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ hotel statistics are AGGREGATES of customers — rooms, bookings, money per currency — and never a guest', function () {
    $before = hcStats();
    $s = seedScenario(2);
    $s['guest']->forceFill(['first_name' => 'Xylophonia', 'last_name' => 'Guestwright'])->save();
    $r = book($s);
    app(TenantContext::class)->forOrganization((string) $s['org']->id, fn () => DB::table('payments')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'reservation_id' => $r->id, 'gateway' => 'manual', 'type' => 'payment',
        'amount_minor' => 12345, 'currency' => 'EUR', 'status' => 'succeeded', 'created_at' => now(), 'updated_at' => now(),
    ]));
    // A FAILED payment moved no money — it must not count as processed.
    app(TenantContext::class)->forOrganization((string) $s['org']->id, fn () => DB::table('payments')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'reservation_id' => $r->id, 'gateway' => 'manual', 'type' => 'payment',
        'amount_minor' => 99999, 'currency' => 'EUR', 'status' => 'failed', 'created_at' => now(), 'updated_at' => now(),
    ]));
    $mine = seedScenario(3);   // internal — must not count
    $mine['org']->forceFill(['is_internal' => true])->save();

    $after = hcStats();
    expect($after['rooms'] - $before['rooms'])->toBe(2)
        ->and(array_sum($after['bookings_per_day']) - array_sum($before['bookings_per_day']))->toBe(1)
        ->and(($after['money']['EUR']['processed_minor'] ?? 0) - ($before['money']['EUR']['processed_minor'] ?? 0))->toBe(12345)
        ->and(hcStats(true)['rooms'] - $after['rooms'])->toBeGreaterThanOrEqual(3);

    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.hotels'))->assertOk()->getContent();
    expect(str_contains($html, 'Xylophonia'))->toBeFalse()->and(str_contains($html, 'Guestwright'))->toBeFalse();
})->group('security');

it('module adoption counts organisations with a REAL usage event in the last 30 days — an older one does not count', function () {
    $before = hcStats()['adoption'];
    $s = seedScenario(1);
    hcEvent((string) $s['org']->id, 'reservation.checked_in', now()->subDays(3)->toDateTimeString());
    hcEvent((string) $s['org']->id, 'housekeeping.task_assigned', now()->subDays(45)->toDateTimeString());

    $after = hcStats()['adoption'];
    expect($after['Front desk'] - $before['Front desk'])->toBe(1)->and($after['Housekeeping'] - $before['Housekeeping'])->toBe(0);
})->group('security');

it('⚠️ cohorts never count a month that has not ended as a zero — "not yet" — and read activity from real events', function () {
    $old = Organization::create(['name' => 'Cohort Old', 'slug' => 'co-'.Str::lower(Str::random(8)), 'status' => 'active', 'default_currency' => 'EUR']);
    $old->forceFill(['created_at' => '2024-03-10 12:00:00'])->save();
    hcEvent((string) $old->id, 'reservation.created', '2024-04-20 10:00:00');   // inside month 1 ([Apr 10, May 10))
    $new = Organization::create(['name' => 'Cohort New', 'slug' => 'cn-'.Str::lower(Str::random(8)), 'status' => 'active', 'default_currency' => 'EUR']);
    $new->forceFill(['created_at' => '2026-07-15 12:00:00'])->save();

    $cohorts = collect(hcStats()['cohorts'])->keyBy('month');
    expect($cohorts['2024-03']['active'][1])->toBe(1)->and($cohorts['2024-03']['active'][3])->toBe(0)
        ->and($cohorts['2026-07']['active'][1])->toBeNull();   // its month 1 ends 15 Sep — not over on 1 Aug

    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.hotels'))->assertOk()->getContent();
    $customers = Organization::query()->customers()->count();
    expect(str_contains($html, 'describe individual hotels, not a trend'))->toBe($customers > 0 && $customers < PlatformHotelStats::TREND_FLOOR);
})->group('security');

// ══ ANNOUNCEMENTS ════════════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ an announcement reaches the chosen tenants only, is ESCAPED, dismisses per person, and withdrawing ends it', function () {
    $a = seedScenario(1);
    $a['manager'] = operatorUser($a);
    $a['colleague'] = operatorUser($a);
    $b = seedScenario(1);
    $b['manager'] = operatorUser($b);
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.announcements.store'), [
        'title' => 'Maintenance <script>alert(1)</script>', 'body' => 'Tonight 22:00 UTC.', 'level' => 'info', 'audience' => 'chosen',
        'organization_ids' => [(string) $a['org']->id], 'starts_at' => now()->subMinute()->format('Y-m-d H:i'), 'reason' => hcReason(),
    ])->assertSessionHasNoErrors();
    $ann = PlatformAnnouncement::query()->latest('created_at')->firstOrFail();
    app('auth')->shouldUse('web');

    $this->actingAs($a['manager'], 'web')->get('/app/dashboard')->assertOk()
        ->assertSee('From Vyrobel')->assertSee('Maintenance &lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->actingAs($b['manager'], 'web')->get('/app/dashboard')->assertOk()->assertDontSee('Tonight 22:00 UTC.');

    // Dismissed for ONE person — still there for their colleague.
    $this->actingAs($a['manager'], 'web')->post(route('announcements.dismiss', $ann->id))->assertRedirect();
    $this->actingAs($a['manager'], 'web')->get('/app/dashboard')->assertDontSee('Tonight 22:00 UTC.');
    $this->actingAs($a['colleague'], 'web')->get('/app/dashboard')->assertSee('Tonight 22:00 UTC.');

    // Someone it was never aimed at cannot even dismiss it.
    $this->actingAs($b['manager'], 'web')->post(route('announcements.dismiss', $ann->id))->assertNotFound();

    $this->actingAs($admin, 'admin')->post(route('admin.announcements.withdraw', $ann->id), ['reason' => hcReason()])->assertSessionHasNoErrors();
    app('auth')->shouldUse('web');
    $this->actingAs($a['colleague'], 'web')->get('/app/dashboard')->assertDontSee('Tonight 22:00 UTC.');

    expect(PlatformAdminAction::query()->whereIn('action', ['platform.announcement.publish', 'platform.announcement.withdraw'])->where('reason', hcReason())->count())->toBe(2);
})->group('security');

it('a scheduled announcement does not show before its start, nor after its end', function () {
    $s = seedScenario(1);
    $user = operatorUser($s);
    PlatformAnnouncement::create(['title' => 'Future notice', 'body' => 'Not yet.', 'level' => 'info', 'audience' => 'all', 'starts_at' => now()->addDay(), 'created_by' => (string) Str::ulid()]);
    PlatformAnnouncement::create(['title' => 'Past notice', 'body' => 'Over.', 'level' => 'info', 'audience' => 'all', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDay(), 'created_by' => (string) Str::ulid()]);

    $this->actingAs($user, 'web')->get('/app/dashboard')->assertOk()->assertDontSee('Future notice')->assertDontSee('Past notice');
})->group('security');

// ══ CHANNEX · STATUS PAGE ════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ the Channex card says NOT CONNECTED, names the deferred cutover, and never shows a key', function () {
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.dashboard'))->assertOk()
        ->assertSee('Channel manager (Channex)')->assertSee('Not connected')->assertSee('does not build, start or simulate');

    config(['services.channex.fake' => false, 'services.channex.api_key' => 'chx-SECRET-NEEDLE']);
    $this->flushSession();
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.dashboard'))->assertOk()
        ->assertSee('sync health is not measured here')->assertDontSee('SECRET-NEEDLE');
})->group('security');

it('⚠️⚠️ the PUBLIC status page shows only what is posted — and with nothing posted never claims "operational"', function () {
    StatusIncident::query()->delete();
    $html = (string) $this->get('/status')->assertOk()->assertSee('No incidents have been posted.')->getContent();
    foreach (['operational', 'uptime', 'queue', 'release', 'heartbeat', config('app.env')] as $internal) {
        expect(str_contains(strtolower($html), strtolower((string) $internal)))->toBeFalse("the public status page mentions [{$internal}]");
    }
})->group('security');

it('an incident posted by hand appears publicly, moves to resolved, and leaves after 14 days — each step audited', function () {
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.status.store'), ['title' => 'Slow sign-in', 'impact' => 'minor', 'status' => 'investigating', 'message' => 'Some sign-ins are slow.', 'reason' => hcReason()])->assertSessionHasNoErrors();
    $incident = StatusIncident::query()->where('title', 'Slow sign-in')->sole();
    $this->get('/status')->assertOk()->assertSee('Slow sign-in')->assertSee('Some sign-ins are slow.')->assertSee('Investigating');

    $this->actingAs($admin, 'admin')->post(route('admin.status.update', $incident->id), ['status' => 'resolved', 'message' => 'Fixed.', 'reason' => hcReason()])->assertSessionHasNoErrors();
    $this->get('/status')->assertOk()->assertSee('Resolved')->assertSee('Fixed.');

    $this->travel(15)->days();
    $this->get('/status')->assertOk()->assertDontSee('Slow sign-in');

    expect(PlatformAdminAction::query()->whereIn('action', ['platform.status.incident_open', 'platform.status.incident_resolved'])->where('target_type', 'status_incident')->count())->toBe(2);
})->group('security');

it('speaking for Vyrobel is Owner and Support — Billing and Analyst cannot publish or post', function () {
    foreach (['billing', 'analyst'] as $role) {
        $this->flushSession();
        $this->actingAs(platformAdmin(['role' => $role]), 'admin')->post(route('admin.status.store'), ['title' => 'x', 'impact' => 'minor', 'status' => 'investigating', 'message' => 'x', 'reason' => hcReason()])->assertForbidden();
        $this->flushSession();
        $this->actingAs(platformAdmin(['role' => $role]), 'admin')->post(route('admin.announcements.store'), ['title' => 'x', 'body' => 'x', 'level' => 'info', 'audience' => 'all', 'starts_at' => now()->format('Y-m-d H:i'), 'reason' => hcReason()])->assertForbidden();
    }
})->group('security');
