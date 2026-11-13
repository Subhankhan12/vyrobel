<?php

declare(strict_types=1);

use App\Exceptions\PlatformWriteRefused;
use App\Http\Middleware\EnsurePlatformAdminTwoFactor;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use App\Models\PlatformAnnouncement;
use App\Models\PrivacyRequest;
use App\Models\StatusIncident;
use App\Models\User;
use App\Models\UserIdentity;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 1 — THE ADMIN AUDIT LEDGER: append-only in fact (not in a docblock), and every admin WRITE
// carries a written reason. Each property has a test that goes red when it is removed.

uses(DatabaseTransactions::class);

function ledgerRow(): PlatformAdminAction
{
    return app(PlatformAuditLogger::class)->record('test.ledger', ['n' => 1]);
}

// ══ 1 · ⚠️⚠️ APPEND-ONLY — refused at the model AND at the connection ═════════════════════════════════════

it('⚠️⚠️ a ledger row cannot be UPDATED through the model', function () {
    $row = ledgerRow();

    expect(fn () => $row->update(['action' => 'rewritten']))->toThrow(PlatformWriteRefused::class);
    expect(DB::table('platform_admin_actions')->where('id', $row->id)->value('action'))->toBe('test.ledger');
})->group('security');

it('⚠️⚠️ a ledger row cannot be DELETED through the model', function () {
    $row = ledgerRow();

    expect(fn () => $row->delete())->toThrow(PlatformWriteRefused::class);
    expect(DB::table('platform_admin_actions')->where('id', $row->id)->exists())->toBeTrue();
})->group('security');

it('⚠️⚠️ and not through the paths a model event never sees — query builder, DB::table, raw SQL', function () {
    $row = ledgerRow();

    expect(fn () => PlatformAdminAction::query()->whereKey($row->id)->update(['action' => 'x']))->toThrow(PlatformWriteRefused::class);
    expect(fn () => PlatformAdminAction::query()->whereKey($row->id)->delete())->toThrow(PlatformWriteRefused::class);
    expect(fn () => DB::table('platform_admin_actions')->where('id', $row->id)->update(['reason' => 'x']))->toThrow(PlatformWriteRefused::class);
    expect(fn () => DB::table('platform_admin_actions')->where('id', $row->id)->delete())->toThrow(PlatformWriteRefused::class);
    expect(fn () => DB::statement('DELETE FROM `platform_admin_actions` WHERE id = ?', [$row->id]))->toThrow(PlatformWriteRefused::class);

    expect(DB::table('platform_admin_actions')->where('id', $row->id)->value('action'))->toBe('test.ledger');
})->group('security');

// ⚠️⚠️ PER-LAYER. Both layers throw the same exception, so a test that exercises them TOGETHER stays green when
//    either one is deleted — the other catches it. Each layer is therefore proved ON ITS OWN below.

it('⚠️ LAYER 1 alone — the MODEL refuses: its update/delete listeners are registered', function () {
    new PlatformAdminAction;   // a model's booted() runs on first instantiation — boot it before asking
    $dispatcher = PlatformAdminAction::getEventDispatcher();
    expect($dispatcher->hasListeners('eloquent.updating: '.PlatformAdminAction::class))->toBeTrue()
        ->and($dispatcher->hasListeners('eloquent.deleting: '.PlatformAdminAction::class))->toBeTrue();
})->group('security');

it('⚠️ LAYER 2 alone — the CONNECTION refuses even with every model event switched off', function () {
    $row = ledgerRow();

    expect(fn () => PlatformAdminAction::withoutEvents(fn () => $row->update(['action' => 'rewritten'])))
        ->toThrow(PlatformWriteRefused::class);
    expect(fn () => PlatformAdminAction::withoutEvents(fn () => $row->delete()))
        ->toThrow(PlatformWriteRefused::class);
    expect(DB::table('platform_admin_actions')->where('id', $row->id)->value('action'))->toBe('test.ledger');
})->group('security');

it('appending is untouched — the ledger still accepts new rows', function () {
    $before = DB::table('platform_admin_actions')->count();
    ledgerRow();
    expect(DB::table('platform_admin_actions')->count())->toBe($before + 1);
})->group('security');

it('the refusal is scoped to the ledger — other tables still update normally', function () {
    $org = Organization::create(['name' => 'Ledger Scope Org', 'slug' => 'ledger-'.Str::lower(Str::random(8)), 'status' => 'active', 'default_currency' => 'EUR']);
    DB::table('organizations')->where('id', $org->id)->update(['name' => 'Renamed']);
    expect($org->fresh()->name)->toBe('Renamed');
})->group('security');

// ══ 2 · ⚠️⚠️ EVERY ADMIN WRITE CARRIES A REASON ═══════════════════════════════════════════════════════════

/**
 * A payload that is VALID for each console write route EXCEPT that it carries no reason — so the reason is
 * provably the only thing refusing it. ⚠️ A NEW console write route has no entry here and fails the structural
 * test below until someone decides what it needs — which is the point.
 *
 * @return array<string, array<string, mixed>>
 */
function writePayloadsWithoutReason(array $ids): array
{
    return [
        'admin.customers.suspend' => ['current_password' => 'password'],
        'admin.customers.reactivate' => [],
        'admin.customers.archive' => ['current_password' => 'password'],
        'admin.customers.restore' => [],
        'admin.customers.internal' => ['internal' => '1'],
        'admin.customers.assign-plan' => ['plan_id' => $ids['plan']],
        'admin.plans.store' => ['code' => 'rsn_'.Str::lower(Str::random(8)), 'name' => 'Reason Probe', 'pricing_type' => Plan::PRICING_FLAT, 'interval' => 'monthly', 'currency' => 'EUR', 'price' => '49'],
        'admin.plans.update' => ['code' => $ids['plan_code'], 'name' => 'Reason Probe 2', 'pricing_type' => Plan::PRICING_FLAT, 'interval' => 'monthly', 'currency' => 'EUR', 'price' => '49'],
        'admin.plans.toggle' => [],
        'admin.switches.update' => ['on' => '1'],
        'admin.integrations.update' => ['api_key' => 'k_'.Str::random(8)],
        'admin.integrations.test' => [],
        'admin.admins.invite' => ['name' => 'New Person', 'email' => 'invitee-'.Str::lower(Str::random(8)).'@example.net', 'role' => 'analyst', 'current_password' => 'password'],
        'admin.admins.reissue' => [],
        'admin.admins.role' => ['role' => 'support', 'current_password' => 'password'],
        'admin.admins.revoke' => ['current_password' => 'password'],
        'admin.admins.restore' => ['current_password' => 'password'],
        'admin.admins.sign-out' => [],
        'admin.support.verify-email' => [],
        'admin.support.resend-verification' => [],
        'admin.support.password-reset' => [],
        'admin.support.unlink' => [],
        'admin.break-glass.start' => ['organization_id' => $ids['organization'], 'code' => '000000'],
        'admin.privacy.store' => ['kind' => 'export', 'subject_kind' => 'relayed_guest', 'organization_id' => $ids['organization'], 'channel' => 'hotel', 'received_on' => '2026-07-20'],
        'admin.privacy.update' => ['status' => 'in_progress'],
        'admin.announcements.store' => ['title' => 'Reason probe', 'body' => 'Reason probe', 'level' => 'info', 'audience' => 'all', 'starts_at' => '2030-01-01 00:00'],
        'admin.announcements.withdraw' => [],
        'admin.status.store' => ['title' => 'Reason probe', 'impact' => 'minor', 'status' => 'investigating', 'message' => 'Reason probe'],
        'admin.status.update' => ['status' => 'monitoring', 'message' => 'Reason probe'],
        // ADMIN 04 — a valid payload in every respect but the reason
        'admin.customers.update-details' => ['name' => 'Reason Org Renamed', 'default_locale' => 'en'],
        'admin.customers.store' => ['org_name' => 'Reason Probe Org', 'currency' => 'EUR', 'property_name' => 'Reason House', 'property_type' => 'hotel', 'founder_name' => 'Reason Founder', 'founder_email' => 'rsn-founder@vyrobel.com'],
        // DEC-7 — the days / the end date and the password pass; only the reason is missing
        'admin.customers.extend-trial' => ['days' => 7, 'current_password' => 'password'],
        'admin.customers.complimentary' => ['until' => now()->addDays(30)->toDateString(), 'current_password' => 'password'],
        'admin.customers.complimentary.end' => ['current_password' => 'password'],
        // ADMIN 05 — the password and the acknowledgement pass; only the reason is missing
        'admin.customers.delete' => ['current_password' => 'password', 'confirm_name' => 'Reason Org', 'acknowledge_delete' => '1'],
    ];
}

/** Console write routes — everything behind the 2FA gate that is not a GET and not your own account. */
function consoleWriteRoutes(): array
{
    return collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $r): bool => in_array(EnsurePlatformAdminTwoFactor::class, $r->gatherMiddleware(), true))
        ->filter(fn (Route $r): bool => array_diff($r->methods(), ['GET', 'HEAD']) !== [])
        ->reject(fn (Route $r): bool => str_starts_with((string) $r->getName(), 'admin.account.'))
        // ⚠️ ADMIN 01 · PHASE 3 — ENDING a break-glass session needs no reason, deliberately: making it harder to STOP
        //    looking at a tenant's data would be backwards. Starting one needs a reason, and is in the list below.
        ->reject(fn (Route $r): bool => $r->getName() === 'admin.break-glass.end')
        ->values()
        ->all();
}

it('⚠️⚠️ EVERY console write route REFUSES a request with no reason — and writes nothing to the ledger', function () {
    $org = Organization::create(['name' => 'Reason Org', 'slug' => 'rsn-'.Str::lower(Str::random(8)), 'status' => 'active', 'default_currency' => 'EUR']);
    $plan = Plan::create(['code' => 'rsnp_'.Str::lower(Str::random(6)), 'name' => 'RSN', 'price_minor' => 4900, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => false]);
    $target = platformAdmin(['role' => 'analyst']);
    $account = User::factory()->create(['email' => 'rsn-'.Str::lower(Str::random(8)).'@example.net', 'email_verified_at' => null]);
    $identity = UserIdentity::create(['user_id' => $account->id, 'provider' => 'google', 'provider_user_id' => Str::random(12), 'provider_email' => $account->email]);
    $privacy = PrivacyRequest::create(['kind' => 'export', 'subject_kind' => 'relayed_guest', 'related_organization_id' => $org->id, 'channel' => 'hotel', 'status' => 'received', 'received_at' => now(), 'due_at' => now()->addMonth(), 'recorded_by' => $target->id]);
    $announcement = PlatformAnnouncement::create(['title' => 'Gate', 'body' => 'Gate', 'level' => 'info', 'audience' => 'chosen', 'organization_ids' => [], 'starts_at' => now()->addYear(), 'created_by' => $target->id]);
    $incident = StatusIncident::create(['title' => 'Gate', 'impact' => 'minor', 'status' => 'investigating', 'message' => 'Gate', 'started_at' => now(), 'created_by' => $target->id]);
    $ids = ['organization' => $org->id, 'plan' => $plan->id, 'plan_code' => $plan->code, 'channel' => 'channex', 'switch' => 'signup.enabled', 'admin' => $target->id, 'user' => $account->id, 'identity' => $identity->id, 'privacyRequest' => $privacy->id, 'announcement' => $announcement->id, 'incident' => $incident->id];
    // ⚠️ Break-glass ships DORMANT (a 404 while off) — switched on so its START is held to the reason rule too.
    config(['platform.break_glass.enabled' => true]);
    $payloads = writePayloadsWithoutReason($ids);

    $routes = consoleWriteRoutes();
    $names = collect($routes)->map(fn (Route $r) => $r->getName())->all();
    expect(array_diff($names, array_keys($payloads)))->toBe([], 'a console write route with no reason fixture — decide what it needs');
    expect(count($routes))->toBeGreaterThanOrEqual(16);   // the instrument sees the console's writes, not nothing

    foreach ($routes as $route) {
        $owner = platformAdmin();
        $this->flushSession();
        $params = collect($route->parameterNames())->mapWithKeys(fn ($n) => [$n => $ids[$n]])->all();
        $method = collect($route->methods())->first(fn ($m) => $m !== 'HEAD');
        $ledgerBefore = DB::table('platform_admin_actions')->where('platform_admin_id', $owner->id)->count();

        $response = $this->actingAs($owner, 'admin')->call($method, route($route->getName(), $params), $payloads[$route->getName()]);

        $keys = sessionErrorKeys();
        expect(in_array('reason', $keys, true))->toBeTrue("[{$route->getName()}] was not refused for its missing reason (status {$response->status()}; errors: ".implode(', ', $keys).')');
        expect(DB::table('platform_admin_actions')->where('platform_admin_id', $owner->id)->count())->toBe($ledgerBefore, "[{$route->getName()}] wrote a ledger row without a reason");
    }
})->group('security');

it('a write that carries a reason records it on the ledger row', function () {
    $owner = platformAdmin();
    $org = Organization::create(['name' => 'Reason Kept', 'slug' => 'rk-'.Str::lower(Str::random(8)), 'status' => 'active', 'default_currency' => 'EUR']);

    $this->actingAs($owner, 'admin')
        ->post(route('admin.customers.internal', $org->id), ['internal' => '1', 'reason' => 'This is our own demo organisation.'])
        ->assertRedirect(route('admin.customers.show', $org->id));

    $row = PlatformAdminAction::query()->where('action', 'platform.customer.mark_internal')->where('target_id', $org->id)->first();
    expect($row?->reason)->toBe('This is our own demo organisation.')
        ->and($row?->platform_admin_id)->toBe($owner->id)
        ->and($org->fresh()->isInternal())->toBeTrue();
})->group('security');

it('self-service account actions are the ONE deliberate exception — your own password needs no reason', function () {
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->put(route('admin.account.profile'), ['name' => 'Renamed Admin'])
        ->assertSessionHasNoErrors();

    expect(PlatformAdmin::query()->find($admin->id)?->name)->toBe('Renamed Admin');
})->group('security');
