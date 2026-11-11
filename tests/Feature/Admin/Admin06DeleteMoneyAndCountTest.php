<?php

declare(strict_types=1);

use App\Mail\OwnerInviteMail;
use App\Models\CheckoutSession;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentAuthorization;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\PlatformAdminAction;
use App\Models\Reservation;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\BillingService;
use App\Services\FolioService;
use App\Services\InviteNoLongerRedeemable;
use App\Services\OperatorConnectService;
use App\Services\OwnerInviteService;
use App\Services\OwnerPayoutService;
use App\Services\Platform\OrganizationDeletion;
use App\Services\Platform\OrganizationDeletionRefused;
use App\Services\TenantProvisioningService;
use App\Support\Money\Money;
use App\Support\Reconciliation\BillingReconciler;
use App\Support\Reconciliation\CheckResult;
use App\Support\Tenancy\TenantContext;
use App\Support\Webhooks\WebhookEventClaim;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

// ⚠️⚠️ ADMIN 06 (2026-09-27) — WHAT A PERMANENT DELETION MUST NEVER RUN OVER, AND WHAT IT MUST SAY BEFORE IT RUNS.
//   · #1 — it refuses while ANY Stripe object the organisation owns is active or pending: live billing, an open Checkout
//     page (B19 / KNOWN-LIMITS L19), a card payment, refund or hold still open, an open dispute, a payout in transit or
//     still owed, a payout run in progress, a webhook being processed. It NEVER calls Stripe.
//   · #2 — a HOLD pending the founder's lawyer: LIVE-mode money records are never deleted; test-mode ones never block.
//   · #3b — the confirmation counts EVERY row the deletion removes, and the count is measured against the database.
//   · late webhooks for an organisation that no longer exists are recorded and answered 2xx, on BOTH endpoints.

uses(DatabaseTransactions::class);

/** A realistic suspended organisation with a booking and a member of staff whose only organisation it is. */
function a06World(): array
{
    $s = seedScenario(2);
    book($s);
    $staff = operatorUser($s, ['email' => 'a06-staff-'.Str::lower(Str::random(6)).'@vyrobel.com'], 'Hotel Manager');
    a06Suspend((string) $s['org']->id);

    return $s + ['staff' => $staff];
}

function a06Suspend(string $orgId): void
{
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($orgId)->update(['status' => 'suspended']));
}

function a06Delete(string $orgId): array
{
    return app(TenantContext::class)->runAsSuperAdmin(fn () => app(OrganizationDeletion::class)->delete(Organization::withTrashed()->findOrFail($orgId)));
}

function a06Refusal(string $orgId): ?string
{
    return app(OrganizationDeletion::class)->refusal($orgId);
}

/** The refusal holds at BOTH layers: the console refuses before acting, and the service refuses a caller that skipped it. */
function a06RefusedEverywhere(object $test, Organization $org, string $says): void
{
    $orgId = (string) $org->id;
    // ONE admin per test: the console binds a session to the admin who opened it, and signs out a second one
    $test->actingAs(auth('admin')->user() ?? platformAdmin(), 'admin')->post(route('admin.customers.delete', $orgId), [
        'current_password' => 'password', 'confirm_name' => (string) $org->name, 'acknowledge_delete' => '1',
        'reason' => 'The customer asked us to erase their account and all their data.',
    ])->assertSessionHasErrors('delete');
    expect(fn () => a06Delete($orgId))->toThrow(OrganizationDeletionRefused::class, $says);
    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeTrue('refused means NOTHING was deleted')
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.delete')->where('target_id', $orgId)->exists())->toBeFalse();
}

function a06Payment(string $orgId, array $overrides = []): string
{
    $id = (string) Str::ulid();
    DB::table('payments')->insert($overrides + [
        'id' => $id, 'organization_id' => $orgId, 'gateway' => 'stripe', 'gateway_ref' => 'pi_a06_'.Str::lower(Str::random(10)),
        'type' => 'payment', 'amount_minor' => 36900, 'currency' => 'EUR', 'status' => 'succeeded', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return $id;
}

function a06Dispute(string $orgId, string $paymentId, string $status, array $overrides = []): string
{
    $id = 'dp_a06_'.Str::lower(Str::random(10));
    DB::table('payment_disputes')->insert($overrides + [
        'id' => (string) Str::ulid(), 'organization_id' => $orgId, 'payment_id' => $paymentId, 'stripe_dispute_id' => $id,
        'status' => $status, 'created_at' => now(), 'updated_at' => now(),
    ]);

    return $id;
}

/** A signed dispute event at a given Stripe event time — what orders redeliveries. */
function a06PostDispute(string $intent, string $disputeId, string $status, int $created, string $path = '/webhooks/stripe/connect'): TestResponse
{
    $raw = json_encode(['id' => 'evt_a06_'.Str::lower(Str::random(16)), 'type' => 'charge.dispute.updated', 'created' => $created, 'livemode' => false,
        'data' => ['object' => ['id' => $disputeId, 'object' => 'dispute', 'payment_intent' => $intent, 'status' => $status]]], JSON_THROW_ON_ERROR);
    $secret = (string) config($path === '/webhooks/stripe/connect' ? 'services.stripe.connect_webhook_secret' : 'services.stripe.webhook_secret');

    return test()->call('POST', $path, [], [], [], ['HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, $secret), 'CONTENT_TYPE' => 'application/json'], $raw);
}

function a06Payout(string $orgId, array $overrides = []): string
{
    $owner = (string) Str::ulid();
    $statement = (string) Str::ulid();
    DB::table('owners')->insert(['id' => $owner, 'organization_id' => $orgId, 'name' => 'A06 Owner', 'email' => 'owner@example.test', 'payout_currency' => 'EUR', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('owner_statements')->insert(['id' => $statement, 'organization_id' => $orgId, 'owner_id' => $owner, 'period_start' => '2026-08-01', 'period_end' => '2026-08-31', 'currency' => 'EUR', 'source_currency' => 'EUR', 'status' => 'finalized', 'created_at' => now(), 'updated_at' => now()]);
    $id = (string) Str::ulid();
    DB::table('payouts')->insert($overrides + [
        'id' => $id, 'organization_id' => $orgId, 'owner_id' => $owner, 'owner_statement_id' => $statement,
        'amount_minor' => 50000, 'currency' => 'EUR', 'status' => 'paid', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return $id;
}

/**
 * Every table's row count — read INDEPENDENTLY of the service under test (information_schema, this database only).
 *
 * @return array<string, int>
 */
function a06EveryTable(): array
{
    $counts = [];
    foreach (DB::select("SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'") as $row) {
        $counts[(string) $row->t] = DB::table((string) $row->t)->count();
    }
    expect(count($counts))->toBeGreaterThan(140, 'the independent read must really see the schema');

    return $counts;
}

/**
 * What went, table by table: before minus after, every table that changed (a table that GREW shows as negative).
 *
 * @param  array<string, int>  $before
 * @param  array<string, int>  $after
 * @return array<string, int>
 */
function a06Gone(array $before, array $after): array
{
    $gone = [];
    foreach ($before + $after as $table => $_) {
        if (($d = ($before[$table] ?? 0) - ($after[$table] ?? 0)) !== 0) {
            $gone[$table] = $d;
        }
    }
    ksort($gone);

    return $gone;
}

function a06PostStripe(string $path, string $type, array $object, bool $signed = true, bool $livemode = false, ?string $eventId = null): TestResponse
{
    $raw = json_encode(['id' => $eventId ?? 'evt_a06_'.Str::lower(Str::random(16)), 'type' => $type, 'livemode' => $livemode, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
    $secret = (string) config($path === '/webhooks/stripe/connect' ? 'services.stripe.connect_webhook_secret' : 'services.stripe.webhook_secret');

    return test()->call('POST', $path, [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signed ? FakeStripeClient::signature($raw, $secret) : 't=1,v1=deadbeef', 'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

// ══ #3b · THE PREVIEW COUNTS WHAT ACTUALLY GOES ═══════════════════════════════════════════════════════════════

it('⚠️⚠️ #3b — a fresh SIGNUP: the preview is every row the deletion removes, table for table, measured (production: 232, not 17)', function () {
    // 🔬 The shape of PW-SIGNUP PROBE on production (27 September): the preview said 17 records in 5 tables; the
    //    deletion removes 232 — 212 of them its roles' permissions, which carry no organisation column.
    $email = 'a06-signup-'.Str::lower(Str::random(8)).'@vyrobel.com';
    $made = app(TenantProvisioningService::class)->provision(
        organization: ['name' => 'A06 Signup '.Str::random(6), 'country' => null, 'currency' => 'EUR'],
        founder: ['name' => 'Ada', 'email' => $email, 'password' => null],
        property: ['name' => 'P '.Str::random(6), 'type' => 'villa', 'location' => null],
        identity: ['provider' => 'google', 'provider_user_id' => 'g-'.Str::random(12), 'email' => $email, 'name' => 'Ada'],
    );
    $orgId = (string) $made['organization']->id;
    a06Suspend($orgId);
    DB::table('login_activity')->insert(['id' => (string) Str::ulid(), 'event' => 'login', 'guard' => 'web', 'subject_type' => 'user', 'subject_id' => (string) $made['user']->id, 'identifier' => null, 'occurred_at' => now()]);

    $before = a06EveryTable();
    $preview = app(OrganizationDeletion::class)->preview($orgId);
    $result = a06Delete($orgId);
    $gone = a06Gone($before, a06EveryTable());
    $said = $preview['per_table'];
    ksort($said);

    expect($said)->toBe($gone, 'the preview must name every table the deletion empties, with the exact count')
        ->and($preview['rows'])->toBe(array_sum($gone))
        ->and($result['records'])->toBe($preview['rows'], 'the deletion reports the number the confirmation showed');
    // the fixture must really reach every way a row goes — or the equality above is proven over nothing
    expect($said)->toHaveKeys(['organizations', 'roles', 'role_has_permissions', 'users', 'user_identities', 'login_activity', 'properties', 'subscriptions'])
        ->and($said['role_has_permissions'])->toBeGreaterThan(100)
        ->and($preview['rows'])->toBeGreaterThan(200);
})->group('security');

it('⚠️⚠️ #3b — an organisation with bookings, guests, staff and every indirect link: preview == what goes, table for table', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $staffId = (string) $w['staff']->id;
    $guestId = (string) $w['guest']->id;
    $other = seedScenario(1);   // a bystander — its rows must not move, and must not be counted
    book($other);

    // every way a row goes that no organisation column shows
    $amenity = (string) Str::ulid();
    DB::table('amenities')->insert(['id' => $amenity, 'name' => 'A06 Pool', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('amenitiables')->insert([
        ['amenity_id' => $amenity, 'amenitiable_type' => 'property', 'amenitiable_id' => $w['property']->id],
        ['amenity_id' => $amenity, 'amenitiable_type' => 'property', 'amenitiable_id' => $other['property']->id],
    ]);
    $tag = (string) Str::ulid();
    DB::table('tags')->insert(['id' => $tag, 'organization_id' => $orgId, 'name' => 'a06', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('taggables')->insert(['tag_id' => $tag, 'taggable_type' => 'guest', 'taggable_id' => $guestId]);
    DB::table('guest_email_preferences')->insert(['id' => (string) Str::ulid(), 'guest_id' => $guestId, 'category' => 'marketing', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('user_identities')->insert(['id' => (string) Str::ulid(), 'user_id' => $staffId, 'provider' => 'google', 'provider_user_id' => 'g-a06-'.Str::random(8), 'created_at' => now(), 'updated_at' => now()]);
    DB::table('trusted_devices')->insert(['id' => (string) Str::ulid(), 'user_id' => $staffId, 'token_hash' => hash('sha256', Str::random(20)), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
    DB::table('notifications')->insert([
        ['id' => (string) Str::uuid(), 'type' => 'a06', 'notifiable_type' => 'guest', 'notifiable_id' => $guestId, 'data' => '{}', 'created_at' => now(), 'updated_at' => now()],
        ['id' => (string) Str::uuid(), 'type' => 'a06', 'notifiable_type' => 'user', 'notifiable_id' => $staffId, 'data' => '{}', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('login_activity')->insert([
        ['id' => (string) Str::ulid(), 'event' => 'login', 'guard' => 'guest', 'subject_type' => 'guest', 'subject_id' => $guestId, 'identifier' => null, 'occurred_at' => now()],
        ['id' => (string) Str::ulid(), 'event' => 'login', 'guard' => 'web', 'subject_type' => 'user', 'subject_id' => $staffId, 'identifier' => null, 'occurred_at' => now()],
        ['id' => (string) Str::ulid(), 'event' => 'failed', 'guard' => 'web', 'subject_type' => null, 'subject_id' => null, 'identifier' => (string) $w['staff']->email, 'occurred_at' => now()],
    ]);
    DB::table('sessions')->insert([
        ['id' => 'a06-'.Str::random(30), 'user_id' => $staffId, 'payload' => '', 'last_activity' => time()],
        ['id' => 'a06-'.Str::random(30), 'user_id' => $guestId, 'payload' => '', 'last_activity' => time()],
    ]);
    // ⚠️ REVIEW 2 (D-R2-6) — a count mutation is only caught for a table that HAS rows here. So every table the plan clears
    // directly gets one: the account's OAuth tokens (a refresh token through its access token), its AI usage, its
    // dismissed notices, a reset token by address — and a guest's reset token by the address's blind index.
    $client = (string) Str::uuid();
    $access = 'a06at'.Str::lower(Str::random(40));
    DB::table('oauth_access_tokens')->insert(['id' => $access, 'user_id' => $staffId, 'client_id' => $client, 'revoked' => 0, 'created_at' => now(), 'updated_at' => now(), 'expires_at' => now()->addDay()]);
    DB::table('oauth_refresh_tokens')->insert(['id' => 'a06rt'.Str::lower(Str::random(40)), 'access_token_id' => $access, 'revoked' => 0, 'expires_at' => now()->addDay()]);
    DB::table('oauth_auth_codes')->insert(['id' => 'a06ac'.Str::lower(Str::random(40)), 'user_id' => $staffId, 'client_id' => $client, 'revoked' => 0, 'expires_at' => now()->addMinutes(5)]);
    DB::table('oauth_device_codes')->insert(['id' => 'a06dc'.Str::lower(Str::random(40)), 'user_id' => $staffId, 'client_id' => $client, 'user_code' => Str::upper(Str::random(8)), 'scopes' => '[]', 'revoked' => 0, 'expires_at' => now()->addMinutes(5)]);
    DB::table('llm_usage')->insert(['id' => (string) Str::ulid(), 'owner_id' => $staffId, 'usage_date' => now()->toDateString(), 'request_count' => 1, 'token_count' => 10, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('announcement_dismissals')->insert(['id' => (string) Str::ulid(), 'announcement_id' => (string) Str::ulid(), 'user_id' => $staffId, 'dismissed_at' => now()]);
    DB::table('password_reset_tokens')->insert(['email' => strtolower((string) $w['staff']->email), 'token' => Hash::make(Str::random(20)), 'created_at' => now()]);
    $guestAddress = Guest::emailHash('a06-guest-'.Str::lower(Str::random(8)).'@example.test');
    DB::table('guests')->where('id', $guestId)->update(['email_hash' => $guestAddress]);
    DB::table('guest_password_reset_tokens')->insert(['email' => $guestAddress, 'token' => Hash::make(Str::random(20)), 'created_at' => now()]);
    // …and a person the deletion KEEPS (they also belong to the bystander): their notification about THIS organisation
    // goes, the one about the bystander stays — the only step that reads a kept person's rows
    $kept = operatorUser($other, ['email' => 'a06-kept-'.Str::lower(Str::random(6)).'@vyrobel.com'], 'Front Desk');
    DB::table('organization_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'user_id' => $kept->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    $theirs = (string) Str::uuid();
    DB::table('notifications')->insert([
        ['id' => (string) Str::uuid(), 'type' => 'a06', 'notifiable_type' => 'user', 'notifiable_id' => $kept->id, 'data' => json_encode(['organization_id' => $orgId]), 'created_at' => now(), 'updated_at' => now()],
        ['id' => $theirs, 'type' => 'a06', 'notifiable_type' => 'user', 'notifiable_id' => $kept->id, 'data' => json_encode(['organization_id' => (string) $other['org']->id]), 'created_at' => now(), 'updated_at' => now()],
    ]);

    $before = a06EveryTable();
    $preview = app(OrganizationDeletion::class)->preview($orgId);
    $result = a06Delete($orgId);
    $gone = a06Gone($before, a06EveryTable());
    $said = $preview['per_table'];
    ksort($said);

    expect($said)->toBe($gone)
        ->and($result['records'])->toBe(array_sum($gone))
        ->and($result['per_table'])->toEqual($preview['per_table'])
        ->and($preview['own'] + $preview['linked'] + $preview['accounts'])->toBe($preview['rows'], 'the groups add up to the whole');
    expect($said)->toHaveKeys(['amenitiables', 'taggables', 'guest_email_preferences', 'user_identities', 'trusted_devices', 'notifications', 'login_activity', 'sessions', 'role_has_permissions', 'reservations', 'guests', 'users', 'organizations'])
        ->and($said['amenitiables'])->toBe(1, 'the bystander keeps its link');
    // ⚠️ the fixture reaches EVERY table the plan clears directly — so dropping any one of them from the count is caught
    expect(array_values(array_diff(OrganizationDeletion::REACHED_THROUGH_A_PARENT, array_keys($said))))->toBe([])
        ->and($preview['users_kept'])->toBe(1)
        ->and(DB::table('users')->where('id', $kept->id)->exists())->toBeTrue('a person who belongs elsewhere keeps their account')
        ->and(DB::table('notifications')->where('id', $theirs)->exists())->toBeTrue('…and their notification about the bystander');
})->group('security');

it('⚠️ the confirmation screen shows the whole count, grouped in plain words, with every table it touches', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $preview = app(OrganizationDeletion::class)->preview($orgId);

    $page = $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $orgId))->assertOk();
    $page->assertSeeText(number_format($preview['rows']).' records in '.$preview['tables'].' tables')
        ->assertSeeText(number_format($preview['own']).' records of its own')
        ->assertSeeText(number_format($preview['linked']).' records linked to those')
        ->assertSeeText('role has permissions — '.number_format($preview['per_table']['role_has_permissions']));
    expect($preview['rows'])->toBeGreaterThan($preview['own'], 'the count reaches beyond the tables with an organisation column');
    assertNoRawBlade($page);
})->group('security');

it('⚠️ the count follows ONE level of cascade — and the live schema has no deeper chain; a deeper one refuses', function () {
    // pure: a graph where a cascaded row (no organisation column) is itself the parent of a cascade
    $deep = OrganizationDeletion::uncountedCascades(
        [['roles', 'organization_id', 'organizations', 'id'], ['role_has_permissions', 'role_id', 'roles', 'id'], ['grants', 'rhp_id', 'role_has_permissions', 'id']],
        ['roles'], [],
    );
    expect($deep)->toBe(['roles → role_has_permissions → grants']);
    // an organisation table whose column may be null can be reached by a cascade without carrying the id — the same gap
    expect(OrganizationDeletion::uncountedCascades(
        [['a', 'b_id', 'b', 'id'], ['c', 'a_id', 'a', 'id']], ['a', 'b'], ['a'],
    ))->toBe(['b → a → c']);
    expect(OrganizationDeletion::uncountedCascades([['a', 'b_id', 'b', 'id'], ['c', 'a_id', 'a', 'id']], ['a', 'b'], []))->toBe([]);
    // a table the plan clears DIRECTLY as the parent of a cascade: rows would go that no step counts
    expect(OrganizationDeletion::uncountedCascades([['grant_uses', 'session_id', 'sessions', 'id']], ['roles'], [], ['sessions']))
        ->toBe(['sessions (cleared directly) → grant_uses']);
    // one row reachable by TWO cascades from parents the deletion removes: counted twice, removed once
    expect(OrganizationDeletion::uncountedCascades([['taggables', 'tag_id', 'tags', 'id'], ['taggables', 'guest_id', 'guests', 'id']], ['tags', 'guests'], []))
        ->toBe(['taggables ← tags, guests']);
    // …but one of the two a GLOBAL parent (a role's permissions: roles are the organisation's, permissions are not): fine
    expect(OrganizationDeletion::uncountedCascades([['role_has_permissions', 'role_id', 'roles', 'id'], ['role_has_permissions', 'permission_id', 'permissions', 'id']], ['roles'], []))
        ->toBe([]);

    // the live schema holds the premise — so a live refusal never cites it
    $w = a06World();
    $orgId = (string) $w['org']->id;
    expect(a06Refusal($orgId))->toBeNull();

    // …and when the schema breaks it, the deletion refuses rather than confirm a number it cannot count
    $service = app(OrganizationDeletion::class);
    $edges = new ReflectionMethod($service, 'cascadeEdges');
    $cascades = new ReflectionProperty($service, 'cascades');
    $cascades->setValue($service, [...$edges->invoke($service), ['a06_grants', 'role_has_permission_id', 'role_has_permissions', 'id']]);
    expect($service->refusal($orgId))->toContain('cannot count exactly')->toContain('roles → role_has_permissions → a06_grants');

    // ⚠️ DRIFT: every table the plan clears directly (phases 1 and 3, the accounts themselves aside) is on the list the
    // premise reads — a new direct step that is not would slip past check 2
    $plan = (new ReflectionMethod($fresh = app(OrganizationDeletion::class), 'plan'))
        ->invoke($fresh, $orgId, $fresh->organizationTables(), [(string) $w['staff']->id], []);
    $direct = collect($plan)->filter(fn (array $step): bool => in_array($step['phase'], [1, 3], true) && $step['table'] !== 'users')->pluck('table')->unique()->sort()->values()->all();
    expect(array_values(array_diff($direct, OrganizationDeletion::REACHED_THROUGH_A_PARENT)))->toBe([])
        ->and(count($direct))->toBeGreaterThan(8, 'the plan must really be read');
})->group('security');

// ══ #1 · EVERY STRIPE OBJECT, ACTIVE OR PENDING ════════════════════════════════════════════════════════════════

it('⚠️⚠️ B19 — an open Stripe Checkout page refuses the deletion until it is paid or expires', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    app(BillingService::class)->ensurePlans();

    $session = app(TenantContext::class)->forOrganization($orgId, fn () => app(BillingService::class)->startCheckout($w['org'], 'essential', 'monthly'));
    $row = DB::table('stripe_checkout_sessions')->where('stripe_session_id', $session->id)->first();
    expect($row)->not->toBeNull('starting a checkout records the page')
        ->and($row->organization_id)->toBe($orgId)
        ->and($row->livemode)->not->toBeNull('Stripe\'s own livemode, stored — never left unknown')
        ->and((bool) $row->livemode)->toBeFalse();

    expect(a06Refusal($orgId))->toContain('payment page for its subscription');
    a06RefusedEverywhere($this, $w['org'], 'payment page');

    // Stripe says it expired → the page is closed, and the refusal lifts
    a06PostStripe('/webhooks/stripe', 'checkout.session.expired', ['id' => $session->id, 'object' => 'checkout.session', 'status' => 'expired'])->assertStatus(202)->assertJson(['status' => 'processed']);
    expect(DB::table('stripe_checkout_sessions')->where('stripe_session_id', $session->id)->value('expired_at'))->not->toBeNull()
        ->and(a06Refusal($orgId))->toBeNull();
})->group('security');

it('⚠️ B19 — a page past its expiry no longer refuses; one with no expiry refuses for Stripe\'s longest lifetime, 24 hours', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $insert = fn (array $row) => DB::table('stripe_checkout_sessions')->insert($row + ['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'stripe_session_id' => 'cs_a06_'.Str::random(12), 'mode' => 'payment', 'updated_at' => now()]);

    $insert(['expires_at' => now()->subMinute(), 'created_at' => now()->subDay()]);
    expect(a06Refusal($orgId))->toBeNull('an expired page cannot be paid');

    $insert(['expires_at' => null, 'created_at' => now()->subHours(23)]);
    expect(a06Refusal($orgId))->toContain('a guest’s card payment');
    DB::table('stripe_checkout_sessions')->where('organization_id', $orgId)->update(['created_at' => now()->subHours(25)]);
    expect(a06Refusal($orgId))->toBeNull();

    $insert(['expires_at' => now()->addMinutes(30), 'created_at' => now(), 'completed_at' => now()]);
    expect(a06Refusal($orgId))->toBeNull('a completed page is closed');
    expect(CheckoutSession::LONGEST_LIFETIME_HOURS)->toBe(24);
})->group('security');

it('⚠️⚠️ refuses while Stripe billing is live in ANY state but cancelled', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    app(BillingService::class)->ensurePlans();
    $sub = (string) Str::ulid();
    DB::table('subscriptions')->insert(['id' => $sub, 'organization_id' => $orgId, 'plan_id' => Plan::query()->firstOrFail()->getKey(), 'status' => 'past_due',
        'stripe_subscription_id' => 'sub_a06', 'stripe_customer_id' => 'cus_a06', 'livemode' => false, 'created_at' => now(), 'updated_at' => now()]);

    a06RefusedEverywhere($this, $w['org'], 'Stripe billing is live');
    foreach (['trialing', 'active', 'incomplete', 'paused'] as $state) {
        DB::table('subscriptions')->where('id', $sub)->update(['status' => $state]);
        expect(a06Refusal($orgId))->toContain('Stripe billing is live');   // Stripe still holds it — it can still charge
    }
    DB::table('subscriptions')->where('id', $sub)->update(['status' => 'canceled']);
    expect(a06Refusal($orgId))->toBeNull('cancelled at Stripe: nothing more can be charged');
})->group('security');

it('⚠️⚠️ refuses while a card payment, a card hold or a refund is still open at Stripe', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;

    $pending = a06Payment($orgId, ['status' => 'pending']);
    a06RefusedEverywhere($this, $w['org'], 'card payment or card hold is still open');
    DB::table('payments')->where('id', $pending)->update(['status' => 'succeeded']);
    expect(a06Refusal($orgId))->toBeNull();

    $held = a06Payment($orgId, ['type' => 'deposit_hold', 'status' => 'authorized']);
    a06RefusedEverywhere($this, $w['org'], 'card hold');
    // ⚠️ per record: an authorisation still open refuses on its own, whatever its payment row says
    DB::table('payments')->where('id', $held)->update(['status' => 'voided']);
    DB::table('payment_authorizations')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'payment_id' => $held, 'currency' => 'EUR', 'status' => 'authorized', 'created_at' => now(), 'updated_at' => now()]);
    a06RefusedEverywhere($this, $w['org'], 'card hold');
    DB::table('payment_authorizations')->where('payment_id', $held)->update(['status' => 'released']);
    expect(a06Refusal($orgId))->toBeNull();

    a06Payment($orgId, ['type' => 'refund', 'status' => 'pending']);
    a06RefusedEverywhere($this, $w['org'], 'card refund is still being processed');
})->group('security');

/** A card hold the way FolioService::authorizeDeposit writes it: a `deposit_hold` payment (never advanced) + its authorization. */
function a06Hold(string $orgId, string $authorizationStatus, string $gateway = 'stripe'): string
{
    $payment = a06Payment($orgId, ['type' => 'deposit_hold', 'status' => 'authorized', 'gateway' => $gateway]);
    DB::table('payment_authorizations')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'payment_id' => $payment, 'currency' => 'EUR',
        'status' => $authorizationStatus, 'created_at' => now(), 'updated_at' => now()]);

    return $payment;
}

it('⚠️⚠️ D-HOLD-STUCK — a hold is judged by its AUTHORIZATION: a closed one no longer blocks a delete for ever, an open one still does', function () {
    // 2026-11-11 (his "do all"). A hold's payment row is written `authorized` and never advanced (a capture is a NEW payment), so a CAPTURED
    // hold read "open" for ever and refused a delete with no way out. Refusing beats deleting wrongly where money is involved — so only a
    // hold the authorization still calls `authorized` at STRIPE refuses; one with no authorization row at all is unknown, and still does.
    $w = a06World();
    $orgId = (string) $w['org']->id;

    // still open at Stripe → refuses, by its OWN sentence (it says there is no button to release one — the truth)
    $payment = a06Hold($orgId, 'authorized');
    expect(a06Refusal($orgId))->toContain('card hold')->toContain('no button to release')->not->toContain('Let it settle');
    a06RefusedEverywhere($this, $w['org'], 'card hold');

    // closed by Vyrobel's own lifecycle → no longer blocks, whatever the never-advanced payment row still says
    foreach (['captured', 'released', 'voided', 'expired'] as $closed) {
        DB::table('payment_authorizations')->where('payment_id', $payment)->update(['status' => $closed]);
        expect(a06Refusal($orgId))->toBeNull("a $closed hold");
    }

    // a hold payment with NO authorization row is unknown → still refuses (the safe direction)
    DB::table('payments')->where('id', $payment)->delete();
    DB::table('payment_authorizations')->where('payment_id', $payment)->delete();
    $orphan = a06Payment($orgId, ['type' => 'deposit_hold', 'status' => 'authorized']);
    expect(a06Refusal($orgId))->toContain('card payment or card hold is still open');
    DB::table('payments')->where('id', $orphan)->delete();
    expect(a06Refusal($orgId))->toBeNull();

    // a MANUAL hold has no object at Stripe — it never blocks
    a06Hold($orgId, 'authorized', 'manual');
    expect(a06Refusal($orgId))->toBeNull();
})->group('security');

it('⚠️⚠️ refuses while ANY card dispute is open — a payment can carry more than one', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $payment = a06Payment($orgId);
    $chargeback = a06Dispute($orgId, $payment, 'needs_response');

    a06RefusedEverywhere($this, $w['org'], 'being disputed');
    // 📖 Stripe: rarely, a second dispute on the same payment — one finished dispute beside it never lifts the refusal
    a06Dispute($orgId, $payment, 'warning_closed');
    expect(a06Refusal($orgId))->toContain('being disputed');
    DB::table('payment_disputes')->where('stripe_dispute_id', $chargeback)->update(['status' => 'won']);
    expect(a06Refusal($orgId))->toBeNull();
})->group('security');

it('⚠️⚠️ refuses while a payout is in transit, owed or on hold — money an owner has not received', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;

    $payout = a06Payout($orgId, ['status' => 'processing']);
    a06RefusedEverywhere($this, $w['org'], 'in transit at Stripe');
    DB::table('payouts')->where('id', $payout)->update(['status' => 'pending']);
    a06RefusedEverywhere($this, $w['org'], 'still owed');
    DB::table('payouts')->where('id', $payout)->update(['status' => 'held']);
    a06RefusedEverywhere($this, $w['org'], 'still owed');
    DB::table('payouts')->where('id', $payout)->update(['status' => 'paid']);
    expect(a06Refusal($orgId))->toBeNull();
})->group('security');

it('⚠️ refuses while a payout run is in progress', function () {
    $w = a06World();
    $run = (string) Str::ulid();
    DB::table('payout_runs')->insert(['id' => $run, 'run_key' => 'a06-'.$run, 'status' => 'running', 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

    a06RefusedEverywhere($this, $w['org'], 'payout run has been running');
    DB::table('payout_runs')->where('id', $run)->update(['status' => 'completed']);
    expect(a06Refusal((string) $w['org']->id))->toBeNull();
})->group('security');

it('⚠️ refuses while a webhook for it is still being processed', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    DB::table('webhook_events')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'source' => 'stripe', 'external_event_id' => 'evt_a06_'.Str::random(10), 'event_type' => 'invoice.paid', 'status' => 'processing', 'received_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

    a06RefusedEverywhere($this, $w['org'], 'has not been processed yet');
})->group('security');

it('⚠️ refuses an organisation already deleted, and a schema read that misses a table every schema has', function () {
    $w = a06World();
    $org = Organization::withTrashed()->findOrFail($w['org']->id);
    $service = app(OrganizationDeletion::class);

    // a schema read that could not see `guests` would make the post-condition blind to them
    $blind = new ReflectionProperty($service, 'tables');
    $blind->setValue($service, array_values(array_diff($service->organizationTables(), ['guests'])));
    expect(fn () => app(TenantContext::class)->runAsSuperAdmin(fn () => $service->delete($org)))->toThrow(OrganizationDeletionRefused::class, 'could not be read in full');
    expect(DB::table('organizations')->where('id', $org->id)->exists())->toBeTrue();

    a06Delete((string) $org->id);
    expect(fn () => app(TenantContext::class)->runAsSuperAdmin(fn () => app(OrganizationDeletion::class)->delete($org)))->toThrow(OrganizationDeletionRefused::class, 'already been deleted');
})->group('security');

// ══ #2 · LIVE MONEY RECORDS — A HOLD PENDING THE FOUNDER'S LAWYER ═════════════════════════════════════════════

it('⚠️⚠️ #2 — LIVE-mode money records are never deleted (a hold pending his lawyer); TEST-mode ones never block', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;

    // test-mode and non-Stripe records: nothing held
    $charge = a06Payment($orgId, ['livemode' => false]);
    a06Payment($orgId, ['gateway' => 'manual', 'gateway_ref' => null, 'livemode' => null]);
    $payout = a06Payout($orgId, ['livemode' => false]);
    expect(a06Refusal($orgId))->toBeNull('test-mode records never block');

    DB::table('payments')->where('id', $charge)->update(['livemode' => true]);
    a06RefusedEverywhere($this, $w['org'], 'live money records (1 card payment)');
    DB::table('payments')->where('id', $charge)->update(['livemode' => false]);

    a06Payment($orgId, ['type' => 'refund', 'livemode' => true]);
    a06RefusedEverywhere($this, $w['org'], '1 refund');
    DB::table('payments')->where('organization_id', $orgId)->where('type', 'refund')->delete();

    DB::table('payouts')->where('id', $payout)->update(['livemode' => true]);
    a06RefusedEverywhere($this, $w['org'], '1 payout');
    DB::table('payouts')->where('id', $payout)->update(['livemode' => false]);

    app(BillingService::class)->ensurePlans();
    DB::table('subscriptions')->where('organization_id', $orgId)->delete();
    DB::table('subscriptions')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'plan_id' => Plan::query()->firstOrFail()->getKey(), 'status' => 'canceled', 'stripe_subscription_id' => 'sub_a06_live', 'livemode' => true, 'created_at' => now(), 'updated_at' => now()]);
    a06RefusedEverywhere($this, $w['org'], 'live Stripe billing, with its invoices');
    expect(a06Refusal($orgId))->toContain('on hold until the legal ruling');
})->group('security');

it('⚠️⚠️ #2 — livemode is STRIPE\'S OWN, stored as the record is written: a charge, its webhook settlement, a payout', function () {
    $s = seedScenario(1);
    $orgId = (string) $s['org']->id;
    $fake = app(StripeClientInterface::class);
    expect($fake)->toBeInstanceOf(FakeStripeClient::class);

    // a test-mode Checkout session, as Stripe reports it
    $fake->livemode = false;
    app(BillingService::class)->ensurePlans();
    $session = app(TenantContext::class)->forOrganization($orgId, fn () => app(BillingService::class)->startCheckout($s['org'], 'essential', 'monthly'));
    $mode = DB::table('stripe_checkout_sessions')->where('stripe_session_id', $session->id)->value('livemode');
    expect($mode)->not->toBeNull()->and((bool) $mode)->toBeFalse();

    // a payment settled by a signed event carries the event's livemode — never assumed
    $payment = a06Payment($orgId, ['status' => 'pending', 'livemode' => null]);
    $intent = (string) DB::table('payments')->where('id', $payment)->value('gateway_ref');
    a06PostStripe('/webhooks/stripe', 'payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent', 'status' => 'succeeded'], livemode: false)->assertSuccessful();
    expect(DB::table('payments')->where('id', $payment)->value('livemode'))->not->toBeNull('the event said test mode, and that is stored')
        ->and((bool) DB::table('payments')->where('id', $payment)->value('livemode'))->toBeFalse();
})->group('security');

it('⚠️⚠️ #2 — a REFUND takes its payment\'s mode (Stripe\'s Refund carries none): a refund of a live charge is live', function () {
    $s = seedScenario(1);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    $fake = app(StripeClientInterface::class);
    $fake->connectChargesEnabled[(string) $org->stripe_connect_account_id] = true;
    $res = Reservation::create([
        'property_id' => $s['property']->id, 'reference' => 'VYR-A06'.Str::random(4), 'status' => 'confirmed',
        'source' => 'direct', 'lead_guest_id' => $s['guest']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'currency' => 'EUR',
    ]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room', Money::of(50000, 'EUR'));
    $intent = 'pi_a06_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], (string) $org->stripe_connect_account_id);
    $payment = Payment::where('gateway_ref', $intent)->firstOrFail();
    $folios->settlePayment($payment, 'succeeded', true);   // the signed event said LIVE
    expect($payment->refresh()->livemode)->toBeTrue();

    config()->set('services.payments.gateway', 'stripe');
    app()->forgetInstance(FolioService::class);
    app(FolioService::class)->refundPayment($payment->refresh(), Money::of(10000, 'EUR'));

    $refund = Payment::where('organization_id', $org->id)->where('type', 'refund')->firstOrFail();
    expect($refund->livemode)->toBeTrue('a refund of a live charge is live — the hold must see it');
})->group('security');

// ══ #1 · LATE WEBHOOKS — RECORDED AND ANSWERED 2xx, NEVER A 5xx, NEVER DROPPED ═════════════════════════════════

it('⚠️⚠️ a signed event for an organisation that no longer exists is RECORDED and answered 2xx — on BOTH endpoints', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $stillThere = (string) a06World()['org']->id;   // another organisation, which stays
    $payment = a06Payment($orgId, ['status' => 'succeeded']);
    $intent = (string) DB::table('payments')->where('id', $payment)->value('gateway_ref');
    a06Delete($orgId);
    // the event's own row, read back: recorded (never dropped), with a reason, and in the right state
    $row = fn (): ?object => DB::table('webhook_events')->orderByDesc('created_at')->orderByDesc('id')->first(['event_type', 'status', 'error']);
    $posted = function (string $path, string $type, array $object, string $expect, bool $livemode = false) use ($row): void {
        $before = DB::table('webhook_events')->count();
        a06PostStripe($path, $type, $object, livemode: $livemode)->assertStatus(202)->assertJson(['status' => 'unresolved']);
        $r = $row();
        expect(DB::table('webhook_events')->count())->toBe($before + 1, "{$path} {$type}: written down, never dropped")
            ->and($r->event_type)->toBe($type)
            ->and($r->status)->toBe($expect, "{$path} {$type}")
            ->and((string) $r->error)->not->toBe('');
    };
    $ours = ['organization_id' => $orgId];   // every object the app creates at Stripe carries it

    foreach (['/webhooks/stripe', '/webhooks/stripe/connect'] as $path) {
        // a dispute months after the charge — its payment is gone with the organisation: someone's money, so `failed`
        $posted($path, 'charge.dispute.created', ['id' => 'dp_a06_'.Str::random(8), 'object' => 'dispute', 'payment_intent' => $intent, 'status' => 'needs_response'], 'failed');
        // ⚠️ D-R3-3 / DEC-g (his ruling, 2026-11-11): a TEST-MODE page's expiry arriving after its organisation was deleted moved no money —
        //    recorded (never dropped, with its reason) but `ignored`, so the reconciliation's list stays a list of real problems
        $posted($path, 'checkout.session.expired', ['id' => 'cs_a06_gone_'.Str::random(6), 'object' => 'checkout.session', 'metadata' => $ours], 'ignored');
        // …but only that: a LIVE-mode expiry, a page that was PAID after the deletion, and an expiry that names an organisation that still
        // exists (which has no record of this page — a real problem) all stay `failed`
        $posted($path, 'checkout.session.expired', ['id' => 'cs_a06_live_'.Str::random(6), 'object' => 'checkout.session', 'metadata' => $ours, 'livemode' => true], 'failed', livemode: true);
        $posted($path, 'checkout.session.completed', ['id' => 'cs_a06_paid_'.Str::random(6), 'object' => 'checkout.session', 'metadata' => $ours], 'failed');
        $posted($path, 'checkout.session.expired', ['id' => 'cs_a06_alive_'.Str::random(6), 'object' => 'checkout.session', 'metadata' => ['organization_id' => (string) $stillThere]], 'failed');
        // an object the app created (it names the organisation) for something that is gone: `failed`
        $posted($path, 'payment_intent.succeeded', ['id' => 'pi_a06_gone_'.Str::random(6), 'object' => 'payment_intent', 'status' => 'succeeded', 'metadata' => $ours], 'failed');
        $posted($path, 'payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent', 'status' => 'succeeded', 'metadata' => $ours], 'failed');
        // one that names no organisation of ours — a subscription invoice's intent, say: recorded `ignored`, not a failure
        $posted($path, 'payment_intent.succeeded', ['id' => 'pi_a06_notours_'.Str::random(6), 'object' => 'payment_intent', 'status' => 'succeeded'], 'ignored');
        $posted($path, 'refund.updated', ['id' => 're_a06_gone_'.Str::random(6), 'object' => 'refund', 'status' => 'succeeded'], 'ignored');

        // unsigned: refused, and nothing written
        $all = DB::table('webhook_events')->count();
        a06PostStripe($path, 'charge.dispute.created', ['id' => 'dp_a06_x', 'object' => 'dispute', 'payment_intent' => $intent], signed: false)->assertStatus(400);
        expect(DB::table('webhook_events')->count())->toBe($all);
    }

    // the Connect plane's own objects: a transfer to a payout that is gone, an account no owner here holds
    $posted('/webhooks/stripe/connect', 'transfer.created', ['id' => 'tr_a06_gone_'.Str::random(6), 'object' => 'transfer', 'metadata' => $ours], 'failed');
    $posted('/webhooks/stripe/connect', 'account.updated', ['id' => 'acct_a06_gone_'.Str::random(6), 'object' => 'account', 'charges_enabled' => true], 'ignored');

    // the billing path too: a subscription event for the deleted organisation (pre-existing: always `failed`)
    $posted('/webhooks/stripe', 'customer.subscription.updated', ['id' => 'sub_a06_gone', 'object' => 'subscription', 'status' => 'active', 'metadata' => $ours], 'failed');
})->group('security');

it('⚠️ an `ignored` event is still taken over and applied when Stripe resends it after its record exists', function () {
    $s = seedScenario(1);
    $orgId = (string) $s['org']->id;
    $intent = 'pi_a06_later_'.Str::random(8);
    $event = 'evt_a06_resend_'.Str::random(8);

    a06PostStripe('/webhooks/stripe/connect', 'payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent', 'status' => 'succeeded'], eventId: $event)->assertStatus(202);
    expect(DB::table('webhook_events')->where('external_event_id', $event)->value('status'))->toBe('ignored');

    $payment = a06Payment($orgId, ['gateway_ref' => $intent, 'status' => 'pending']);
    a06PostStripe('/webhooks/stripe/connect', 'payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent', 'status' => 'succeeded'], eventId: $event)->assertStatus(202);
    expect(DB::table('payments')->where('id', $payment)->value('status'))->toBe('succeeded', 'the resend is applied, as a `failed` one always was')
        ->and(DB::table('webhook_events')->where('external_event_id', $event)->value('status'))->toBe('processed');
})->group('security');

it('⚠️ a dispute on a payment that still exists is stored on it — the deletion then refuses', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $payment = a06Payment($orgId, ['livemode' => null]);
    $intent = (string) DB::table('payments')->where('id', $payment)->value('gateway_ref');

    $dp = 'dp_a06_'.Str::random(8);
    a06PostDispute($intent, $dp, 'warning_needs_response', time() - 60)->assertStatus(202)->assertJson(['status' => 'processed']);
    $row = DB::table('payment_disputes')->where('stripe_dispute_id', $dp)->first();
    expect($row)->not->toBeNull('the dispute is recorded by Stripe\'s own id')
        ->and($row->payment_id)->toBe($payment)
        ->and($row->organization_id)->toBe($orgId)
        ->and($row->status)->toBe('warning_needs_response')
        ->and($row->livemode)->not->toBeNull('the dispute event\'s livemode is stored');
    expect(a06Refusal($orgId))->toContain('being disputed');

    a06PostDispute($intent, $dp, 'won', time())->assertStatus(202)->assertJson(['status' => 'processed']);
    expect(DB::table('payment_disputes')->where('stripe_dispute_id', $dp)->value('status'))->toBe('won')
        ->and(a06Refusal($orgId))->toBeNull();
})->group('security');

it('⚠️⚠️ a late or redelivered dispute event never re-opens a finished dispute — and a newer one (a late win) applies', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $payment = a06Payment($orgId);
    $intent = (string) DB::table('payments')->where('id', $payment)->value('gateway_ref');
    $dp = 'dp_a06_'.Str::random(8);
    $t = time();

    a06PostDispute($intent, $dp, 'needs_response', $t - 300)->assertStatus(202);
    a06PostDispute($intent, $dp, 'lost', $t - 100)->assertStatus(202);
    // Stripe does not promise order: an OLDER "under review", delivered late after a park, must not re-open it
    a06PostDispute($intent, $dp, 'under_review', $t - 200)->assertStatus(202);
    expect(DB::table('payment_disputes')->where('stripe_dispute_id', $dp)->value('status'))->toBe('lost')
        ->and(a06Refusal($orgId))->toBeNull('a finished dispute re-opened by a stale event would refuse the deletion forever');
    // …and not at the same second either
    a06PostDispute($intent, $dp, 'needs_response', $t - 100)->assertStatus(202);
    expect(DB::table('payment_disputes')->where('stripe_dispute_id', $dp)->value('status'))->toBe('lost');

    // 📖 Stripe's "late win": lost → won, newer — it applies
    a06PostDispute($intent, $dp, 'won', $t)->assertStatus(202);
    expect(DB::table('payment_disputes')->where('stripe_dispute_id', $dp)->value('status'))->toBe('won');

    // an inquiry closing never hides a chargeback on the same payment, in either order
    $inquiry = 'dp_a06_inq_'.Str::random(6);
    $chargeback = 'dp_a06_cb_'.Str::random(6);
    a06PostDispute($intent, $chargeback, 'needs_response', $t + 10)->assertStatus(202);
    a06PostDispute($intent, $inquiry, 'warning_closed', $t + 5)->assertStatus(202);
    expect(a06Refusal($orgId))->toContain('being disputed');
})->group('security');

it('⚠️ a live billing event wins over a test-mode one stored on the same subscription (the hold must see it)', function () {
    $s = seedScenario(1);
    $orgId = (string) $s['org']->id;
    app(BillingService::class)->ensurePlans();
    $sub = (string) Str::ulid();
    DB::table('subscriptions')->insert(['id' => $sub, 'organization_id' => $orgId, 'plan_id' => Plan::query()->firstOrFail()->getKey(), 'status' => 'trialing',
        'livemode' => false, 'created_at' => now(), 'updated_at' => now()]);   // a test-mode event already stamped it

    a06PostStripe('/webhooks/stripe', 'customer.subscription.updated', ['id' => 'sub_a06_live', 'object' => 'subscription', 'status' => 'active', 'metadata' => ['organization_id' => $orgId]], livemode: true)->assertStatus(202);
    expect((bool) DB::table('subscriptions')->where('id', $sub)->value('livemode'))->toBeTrue('live wins: a test event before go-live never hides live billing');

    // …and a later test-mode event never turns live back into test
    a06PostStripe('/webhooks/stripe', 'customer.subscription.updated', ['id' => 'sub_a06_live', 'object' => 'subscription', 'status' => 'active', 'metadata' => ['organization_id' => $orgId]], livemode: false)->assertStatus(202);
    expect((bool) DB::table('subscriptions')->where('id', $sub)->value('livemode'))->toBeTrue();
})->group('security');

// ══ #3 · FILES — A REPLACED LOGO GOES ══════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ DEC-f — a REPLACED brand logo STAYS on disk (emails already sent link to it), is recorded as the property\'s, and is removed with the ORGANISATION', function () {
    // 2026-11-11 (his "do all") — this REVERSES the old "a replaced logo is removed": an email already sent carries the logo as a link to its
    // file, so removing the file on replacement broke the picture in every earlier email, for ever. The file is kept, and a `media` row records
    // it so an organisation's deletion still finds it (a kept file with no record would survive a deletion).
    Storage::fake('public');
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');
    $brand = fn () => $this->actingAs($owner)->post(route('app.settings.brand'), ['logo' => UploadedFile::fake()->image('logo.png', 120, 40)])->assertSessionHasNoErrors();
    $logo = fn (): string => (string) DB::table('properties')->where('id', $s['property']->id)->value('brand_logo_path');
    $history = fn (): array => DB::table('media')->where('organization_id', $s['org']->id)->where('collection', 'brand_logo_history')->pluck('path')->all();

    $brand();
    $first = $logo();
    expect($history())->toBe([]);                                       // nothing replaced yet
    $brand();
    $second = $logo();
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertExists($second);
    Storage::disk('public')->assertExists($first);                      // ← THE CHANGE: the replaced file is still there
    expect($history())->toBe([$first]);                                 // …and recorded, so it is still owned

    // the same replaced path is never recorded twice; a third replacement adds the second
    $brand();
    $third = $logo();
    expect($history())->toEqualCanonicalizing([$first, $second]);
    foreach ([$first, $second, $third] as $path) {
        Storage::disk('public')->assertExists($path);
    }

    // the old email's link still answers: the public media route serves a kept logo
    $this->get('/media/'.$first)->assertOk();

    // DELETING THE ORGANISATION removes the current logo AND every replaced one (the privacy side of keeping them)
    a06Suspend((string) $s['org']->id);   // only a suspended organisation can be deleted
    $report = a06Delete((string) $s['org']->id);
    foreach ([$first, $second, $third] as $path) {
        Storage::disk('public')->assertMissing($path);
    }
    expect($report)->toBeArray();
})->group('security');

it('⚠️ a logo file another organisation\'s rows still name is NOT removed by this organisation\'s deletion', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');
    $this->actingAs($owner)->post(route('app.settings.brand'), ['logo' => UploadedFile::fake()->image('logo.png', 120, 40)])->assertSessionHasNoErrors();
    $mine = (string) DB::table('properties')->where('id', $s['property']->id)->value('brand_logo_path');

    // a demo seed can share one file between tenants: another organisation's property names the same file
    $other = seedScenario(1);
    DB::table('properties')->where('id', $other['property']->id)->update(['brand_logo_path' => $mine]);

    a06Suspend((string) $s['org']->id);
    a06Delete((string) $s['org']->id);
    Storage::disk('public')->assertExists($mine);
})->group('security');

// ══ #5 · A SIGN-IN LINK IS A CREDENTIAL ═══════════════════════════════════════════════════════════════════════

it('⚠️⚠️ #5 — a founder\'s set-password link never REPLACES a password the founder already set another way', function () {
    $email = 'a06-founder-'.Str::lower(Str::random(8)).'@vyrobel.com';
    $made = app(TenantProvisioningService::class)->provision(
        organization: ['name' => 'A06 Founder '.Str::random(6), 'country' => null, 'currency' => 'EUR'],
        founder: ['name' => 'Founder', 'email' => $email, 'password' => null],
        property: ['name' => 'P '.Str::random(6), 'type' => 'villa', 'location' => null],
    );
    $invites = app(OwnerInviteService::class);
    [$invite, $token] = $invites->issueFounderActivation($made['user'], (string) $made['organization']->id);
    $this->get(route('invite.accept', $token))->assertOk()->assertViewIs('auth.set-password');

    // the founder sets a password another way (a reset) while the link is still inside its seven days
    DB::table('users')->where('id', $made['user']->id)->update(['password' => Hash::make('their-own-choice-2026')]);
    $hash = (string) DB::table('users')->where('id', $made['user']->id)->value('password');

    $this->get(route('invite.accept', $token))->assertOk()->assertViewIs('auth.invite-invalid');
    $this->post('/invite/'.$token, ['password' => 'taken-over-by-link-9z!', 'password_confirmation' => 'taken-over-by-link-9z!'])->assertViewIs('auth.invite-invalid');
    expect((string) DB::table('users')->where('id', $made['user']->id)->value('password'))->toBe($hash, 'the password they chose is untouched');

    // ⚠️ per layer: the service refuses under its own lock, whoever calls it
    expect(fn () => $invites->accept($invite, 'taken-over-by-link-9z!'))->toThrow(InviteNoLongerRedeemable::class);
    expect((string) DB::table('users')->where('id', $made['user']->id)->value('password'))->toBe($hash);
    $this->assertGuest('web');
})->group('security');

it('⚠️ a save that fails AFTER the property row names the new logo keeps that logo on disk (it is in use)', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');
    // the property row is written first; then its audit row fails
    DB::connection()->beforeExecuting(function (string $sql): void {
        if (str_starts_with(strtolower(ltrim($sql)), 'insert into `audit_events`')) {
            throw new RuntimeException('a06: the audit write failed after the brand was saved');
        }
    });

    $this->actingAs($owner)->post(route('app.settings.brand'), ['logo' => UploadedFile::fake()->image('logo.png', 120, 40)])->assertStatus(500);

    $path = (string) DB::table('properties')->where('id', $s['property']->id)->value('brand_logo_path');
    expect($path)->not->toBe('', 'the property row was saved before the failure');
    Storage::disk('public')->assertExists($path);
})->group('security');

// ══ REVIEW 2 (2026-09-29) · each finding, proved by a test that failed first ══════════════════════════════════════

/** An onboarded operator (a direct-charge Connect account, charges enabled) with the Stripe gateway selected. */
function a06StripeOperator(array $s): Organization
{
    config()->set('services.payments.gateway', 'stripe');
    app()->forgetInstance(FolioService::class);
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    app(StripeClientInterface::class)->connectChargesEnabled[(string) $org->stripe_connect_account_id] = true;

    return $org;
}

/** A booking with an open folio and one room charge — the guest owes its balance. */
function a06Folio(array $s, int $chargeMinor = 50000): Folio
{
    $folios = app(FolioService::class);
    $folio = $folios->openFolio(book($s));
    $folios->postCharge($folio, 'room', 'Room', Money::of($chargeMinor, 'EUR'));

    return $folio;
}

it('⚠️⚠️ R2-1 — an OPERATOR\'s own Connect account update is routine: recorded `ignored`, never a `failed` the reconciliation reports', function () {
    // 🔬 OperatorConnectService stamps `metadata.organization_id` on the operator's account, and `account.updated` for
    //    it finds no OWNER — so it was recorded `failed`, and `billing:reconcile` went red once any operator onboarded.
    $s = seedScenario(1);
    $org = a06StripeOperator($s);
    $event = 'evt_a06_op_'.Str::lower(Str::random(10));

    a06PostStripe('/webhooks/stripe/connect', 'account.updated', ['id' => (string) $org->stripe_connect_account_id, 'object' => 'account',
        'charges_enabled' => true, 'metadata' => ['organization_id' => (string) $org->id]], eventId: $event)->assertStatus(202);

    $row = DB::table('webhook_events')->where('external_event_id', $event)->first(['status', 'error']);
    expect($row)->not->toBeNull('recorded, never dropped')
        ->and($row->status)->toBe('ignored')
        ->and((string) $row->error)->not->toBe('');
    /** @var CheckResult $check */
    $check = app(TenantContext::class)->runAsSuperAdmin(fn () => (new ReflectionMethod(BillingReconciler::class, 'unprocessedWebhookEvents'))->invoke(app(BillingReconciler::class)));
    expect(collect($check->disagreements)->pluck('subject')->all())->not->toContain($event);

    // ⚠️ REVIEW 3 — but an account update naming an organisation that no longer EXISTS stays `failed`: a deleted customer's
    //    Connect account still attached to the platform is a real problem, and this row is the standing reminder of it
    $gone = 'evt_a06_gone_acct_'.Str::lower(Str::random(8));
    a06PostStripe('/webhooks/stripe/connect', 'account.updated', ['id' => 'acct_a06_deleted_'.Str::random(8), 'object' => 'account',
        'metadata' => ['organization_id' => (string) Str::ulid()]], eventId: $gone)->assertStatus(202);
    expect(DB::table('webhook_events')->where('external_event_id', $gone)->value('status'))->toBe('failed');
})->group('security');

it('⚠️⚠️ R2-2 — a LIVE payment page past its expiry that Stripe has not reported on still refuses: it may have been paid', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $insert = function (array $row) use ($orgId): string {
        $id = 'cs_a06_'.Str::random(12);
        DB::table('stripe_checkout_sessions')->insert($row + ['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'stripe_session_id' => $id, 'mode' => 'subscription', 'updated_at' => now()]);

        return $id;
    };

    // TEST mode past its expiry: no real money can have moved — unchanged, it does not refuse
    $insert(['livemode' => false, 'expires_at' => now()->subMinute(), 'created_at' => now()->subDay()]);
    expect(a06Refusal($orgId))->toBeNull();

    // LIVE, past its expiry, and neither `completed` nor `expired` reported: paid at the last minute, the news not yet here
    $live = $insert(['livemode' => true, 'expires_at' => now()->subMinute(), 'created_at' => now()->subDay()]);
    a06RefusedEverywhere($this, $w['org'], 'no report of whether it was paid has been applied');
    expect(a06Refusal($orgId))->toContain('by hand');

    // Stripe reports the outcome → the refusal lifts
    DB::table('stripe_checkout_sessions')->where('stripe_session_id', $live)->update(['expired_at' => now()]);
    expect(a06Refusal($orgId))->toBeNull();

    // a live page whose expiry Stripe never gave: past the longest lifetime, still waits for Stripe's word
    $insert(['livemode' => true, 'expires_at' => null, 'created_at' => now()->subHours(30)]);
    expect(a06Refusal($orgId))->toContain('no report of whether it was paid has been applied');
})->group('security');

it('⚠️⚠️ R2-3 — livemode is stored on a DESK card charge, a deposit HOLD and its authorisation, its CAPTURE, and a PAYOUT', function () {
    $s = seedScenario(1);
    $org = a06StripeOperator($s);
    $orgId = (string) $org->id;
    $folio = a06Folio($s);
    $folios = app(FolioService::class);
    $fake = app(StripeClientInterface::class);

    // what a LIVE key's objects say
    $fake->livemode = true;
    $charge = $folios->recordPayment($folio, Money::of(10000, 'EUR'), 'pm_card_visa');
    $hold = $folios->authorizeDeposit($folio, Money::of(5000, 'EUR'), 'pm_card_visa');
    $capture = $folios->captureDeposit($hold, Money::of(5000, 'EUR'));
    expect($charge->refresh()->livemode)->toBeTrue('a desk card charge')
        ->and(PaymentAuthorization::withoutGlobalScopes()->findOrFail($hold->getKey())->livemode)->toBeTrue('the card hold')
        ->and(Payment::withoutGlobalScopes()->findOrFail($hold->payment_id)->livemode)->toBeTrue('the hold\'s payment row')
        ->and($capture->refresh()->livemode)->toBeTrue('its capture');

    // …and a TEST key's objects are stored as test — never left unknown
    $fake->livemode = false;
    expect($folios->recordPayment($folio, Money::of(1000, 'EUR'), 'pm_card_visa')->refresh()->livemode)->toBeFalse();

    // a payout sent over the Connect rail carries the transfer's mode…
    $fake->livemode = true;
    $payout = a06Payout($orgId, ['status' => 'pending', 'livemode' => null]);
    // `email` and `tax_id` are ENCRYPTED columns (pay() reads the tax id) — stored exactly as the cast stores them
    DB::table('owners')->where('id', DB::table('payouts')->where('id', $payout)->value('owner_id'))->update([
        'email' => Crypt::encryptString('owner@example.test'), 'tax_id' => Crypt::encryptString('PT500000000'),
        'stripe_connect_account_id' => 'acct_a06_owner', 'stripe_connect_payouts_enabled' => true,
    ]);
    $paid = app(TenantContext::class)->forOrganization($orgId, fn () => app(OwnerPayoutService::class)->pay(Payout::query()->findOrFail($payout), app(StripeConnectPayoutRail::class)));
    expect($paid->status)->toBe('processing')
        ->and(DB::table('payouts')->where('id', $payout)->value('livemode'))->not->toBeNull()
        ->and((bool) DB::table('payouts')->where('id', $payout)->value('livemode'))->toBeTrue('a live transfer');

    // …and one confirmed by a signed event with no mode yet on file takes the event's
    DB::table('payouts')->where('id', $payout)->update(['livemode' => null]);
    a06PostStripe('/webhooks/stripe/connect', 'transfer.created', ['id' => (string) $paid->external_ref, 'object' => 'transfer'], livemode: true)->assertStatus(202);
    expect(DB::table('payouts')->where('id', $payout)->value('status'))->toBe('paid')
        ->and((bool) DB::table('payouts')->where('id', $payout)->value('livemode'))->toBeTrue();
})->group('security');

it('⚠️⚠️ R2-4 — B19, the GUEST half: a guest\'s card-payment page is recorded, refuses the deletion, and its Connect event closes it', function () {
    $s = seedScenario(1);
    $org = a06StripeOperator($s);
    $orgId = (string) $org->id;
    $folio = a06Folio($s);
    $guest = $s['guest'];
    $guest->forceFill(['email' => 'a06-guest-'.Str::lower(Str::random(8)).'@example.test', 'email_verified_at' => now()])->save();
    $guest->password = 'guest-password-1234';
    $guest->save();

    // the guest opens the payment page, exactly as they would
    $this->actingAs($guest->fresh(), 'guest')->post(route('guest.payments.pay', $folio->reservation_id))->assertRedirect();
    $page = DB::table('stripe_checkout_sessions')->where('organization_id', $orgId)->where('mode', 'payment')->first();
    expect($page)->not->toBeNull('the guest\'s page is recorded')
        ->and($page->livemode)->not->toBeNull()
        ->and($page->expires_at)->not->toBeNull('Stripe\'s own expiry, stored');

    a06Suspend($orgId);
    expect(a06Refusal($orgId))->toContain('a guest’s card payment');

    // a direct charge lives on the operator's account, so Stripe reports the page on the CONNECT plane
    a06PostStripe('/webhooks/stripe/connect', 'checkout.session.completed', ['id' => $page->stripe_session_id, 'object' => 'checkout.session', 'status' => 'complete'])
        ->assertStatus(202)->assertJson(['status' => 'processed']);
    expect(DB::table('stripe_checkout_sessions')->where('id', $page->id)->value('completed_at'))->not->toBeNull()
        ->and(a06Refusal($orgId))->not->toContain('payment page');
})->group('security');

it('⚠️⚠️ R2-5 — two disputes on ONE payment are two rows: the inquiry closing later never hides the chargeback still open', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $payment = a06Payment($orgId);
    $intent = (string) DB::table('payments')->where('id', $payment)->value('gateway_ref');
    $t = time();

    a06PostDispute($intent, 'dp_a06_cb_'.Str::random(6), 'needs_response', $t - 60)->assertStatus(202)->assertJson(['status' => 'processed']);
    a06PostDispute($intent, 'dp_a06_inq_'.Str::random(6), 'warning_closed', $t)->assertStatus(202)->assertJson(['status' => 'processed']);

    expect(DB::table('payment_disputes')->where('payment_id', $payment)->count())->toBe(2, 'one row per Stripe dispute')
        ->and(a06Refusal($orgId))->toContain('being disputed');
})->group('security');

it('⚠️ R2-7 — a dispute status this code does not know counts as OPEN: the refusal fails closed', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    $dispute = a06Dispute($orgId, a06Payment($orgId), 'a_status_stripe_adds_later');

    expect((string) a06Refusal($orgId))->toContain('being disputed');
    DB::table('payment_disputes')->where('stripe_dispute_id', $dispute)->update(['status' => 'lost']);
    expect(a06Refusal($orgId))->toBeNull('a status Stripe calls finished lifts it');
})->group('security');

it('⚠️ R2-8 — a dispute refusal says how to get out when Stripe\'s closing news never came: resolve it by hand', function () {
    $w = a06World();
    $orgId = (string) $w['org']->id;
    a06Dispute($orgId, a06Payment($orgId), 'under_review', ['created_at' => now()->subMonths(4)]);

    expect((string) a06Refusal($orgId))->toContain('being disputed')->toContain('by hand');
})->group('security');

it('⚠️⚠️ R2-9 — recording an event as unresolved never demotes one another delivery is processing, or has processed', function () {
    foreach (['processing', 'processed'] as $state) {
        $event = 'evt_a06_r29_'.Str::lower(Str::random(10));
        DB::table('webhook_events')->insert(['id' => (string) Str::ulid(), 'organization_id' => null, 'source' => 'stripe', 'external_event_id' => $event,
            'event_type' => 'payment_intent.succeeded', 'status' => $state, 'received_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        app(TenantContext::class)->runAsSuperAdmin(fn () => WebhookEventClaim::recordUnresolved('stripe', $event, 'payment_intent.succeeded', [], 'a06: could not be placed'));

        expect(DB::table('webhook_events')->where('external_event_id', $event)->value('status'))->toBe($state, "a `{$state}` claim is left exactly as it is");
    }
})->group('security');

it('⚠️ R2-10 — the premise refuses a table the plan clears DIRECTLY that a cascade also reaches (one row, counted twice)', function () {
    expect(OrganizationDeletion::uncountedCascades([['sessions', 'user_id', 'users', 'id']], ['roles'], [], ['sessions']))
        ->toBe(['sessions (cleared directly) ← users']);
    // a cascade into a directly-cleared table from a parent no deletion reaches is not a double count
    expect(OrganizationDeletion::uncountedCascades([['sessions', 'device_id', 'devices', 'id']], ['roles'], [], ['sessions']))->toBe([]);
    // …and today's schema holds it: a live refusal never cites it
    $w = a06World();
    expect(a06Refusal((string) $w['org']->id))->toBeNull();
})->group('security');

it('⚠️⚠️ R2-14 — the console never SHOWS the founder\'s set-password link, and never writes it to its ledger', function () {
    config(['mail.default' => 'smtp']);   // the console refuses to create while mail discards
    Mail::fake();
    $admin = platformAdmin();
    $name = 'A06 Link '.Str::random(6);
    $email = 'a06-link-'.Str::lower(Str::random(8)).'@vyrobel.com';

    $response = $this->actingAs($admin, 'admin')->post(route('admin.customers.store'), [
        'org_name' => $name, 'currency' => 'EUR', 'country' => 'PT', 'property_name' => 'A06 House', 'property_type' => 'hotel', 'location' => 'Lisbon',
        'founder_name' => 'Fiona Founder', 'founder_email' => $email, 'reason' => 'A customer signed a contract offline; creating their workspace.',
    ])->assertSessionHasNoErrors();

    $token = null;
    Mail::assertSent(OwnerInviteMail::class, function (OwnerInviteMail $m) use (&$token): bool {
        $token = $m->token;

        return true;
    });
    expect((string) $token)->not->toBe('');
    $orgId = (string) app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->where('name', $name)->value('id'));

    // what the admin sees: the notice after the create, and the organisation's page
    expect((string) session('admin_notice'))->not->toBe('')->not->toContain((string) $token);
    $page = $this->actingAs($admin, 'admin')->get((string) $response->headers->get('Location'))->assertOk();
    expect($page->getContent())->not->toContain((string) $token);

    // what is written down: the console's ledger, the tenant's audit trail and events, and the invite itself (a hash only)
    foreach (['platform_admin_actions', 'audit_events', 'domain_events', 'owner_invites'] as $table) {
        // (never a negated toContain with a message — the message becomes a second needle and it always passes)
        expect(str_contains((string) json_encode(DB::table($table)->where('created_at', '>=', now()->subMinutes(5))->get()->all()), (string) $token))->toBeFalse($table);
    }
    expect(DB::table('platform_admin_actions')->where('target_id', $orgId)->count())->toBeGreaterThan(1, 'the ledger was really read');
})->group('security');

it('⚠️ R2-15 — an event type neither plane acts on is recorded `ignored` with its reason, never dropped', function () {
    foreach (['/webhooks/stripe', '/webhooks/stripe/connect'] as $path) {
        $event = 'evt_a06_r215_'.Str::lower(Str::random(10));
        a06PostStripe($path, 'balance.available', ['object' => 'balance'], eventId: $event)->assertStatus(202)->assertJson(['status' => 'unresolved']);

        $row = DB::table('webhook_events')->where('external_event_id', $event)->first(['status', 'error']);
        expect($row)->not->toBeNull("{$path}: written down")
            ->and($row->status)->toBe('ignored')
            ->and((string) $row->error)->toContain('does not act on');
    }
})->group('security');

// ══ REVIEW 3 (2026-09-29) · a review of the review-2 fixes — each point verified by reading, proved by a test that failed first ══

it('⚠️⚠️ R3-1 — Stripe\'s reports for an ARCHIVED organisation are APPLIED: a paid page closes, a cancellation lifts the refusal', function () {
    // 🔬 The billing path checked `Organization::query()->…->exists()`, which skips a soft-deleted (archived) organisation:
    //    every billing report for one was written down and never applied — and a deletion may only run on a suspended or
    //    ARCHIVED organisation, so its refusals could never lift.
    $w = a06World();
    $orgId = (string) $w['org']->id;
    app(BillingService::class)->ensurePlans();
    DB::table('subscriptions')->where('organization_id', $orgId)->delete();
    $subId = 'sub_a06_arch_'.Str::lower(Str::random(8));
    $customer = 'cus_a06_arch_'.Str::lower(Str::random(8));
    DB::table('subscriptions')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'plan_id' => Plan::query()->firstOrFail()->getKey(), 'status' => 'trialing',
        'livemode' => false, 'created_at' => now(), 'updated_at' => now()]);
    $page = 'cs_a06_arch_'.Str::random(10);
    DB::table('stripe_checkout_sessions')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'stripe_session_id' => $page, 'mode' => 'subscription',
        'livemode' => false, 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);
    DB::table('organizations')->where('id', $orgId)->update(['deleted_at' => now()]);   // archived

    // the page is paid → the report is APPLIED to the archived organisation: the page closes, the subscription is live
    a06PostStripe('/webhooks/stripe', 'checkout.session.completed', ['id' => $page, 'object' => 'checkout.session', 'subscription' => $subId, 'customer' => $customer,
        'metadata' => ['organization_id' => $orgId]])->assertStatus(202)->assertJson(['status' => 'processed']);
    expect(DB::table('stripe_checkout_sessions')->where('stripe_session_id', $page)->value('completed_at'))->not->toBeNull()
        ->and(DB::table('subscriptions')->where('organization_id', $orgId)->value('stripe_subscription_id'))->toBe($subId);
    expect((string) a06Refusal($orgId))->toContain('Stripe billing is live')->toContain('resolve it by hand');

    // the founder cancels in Stripe, as the refusal says → Stripe's report is applied, and the refusal lifts
    a06PostStripe('/webhooks/stripe', 'customer.subscription.deleted', ['id' => $subId, 'object' => 'subscription', 'status' => 'canceled', 'customer' => $customer])
        ->assertStatus(202)->assertJson(['status' => 'processed']);
    expect(DB::table('subscriptions')->where('organization_id', $orgId)->value('status'))->toBe('canceled')
        ->and(a06Refusal($orgId))->toBeNull();
})->group('security');

it('⚠️ R3-1 — a paid page closes even for an organisation with no subscription row', function () {
    $s = seedScenario(1);
    $orgId = (string) $s['org']->id;
    DB::table('subscriptions')->where('organization_id', $orgId)->delete();
    $page = 'cs_a06_nosub_'.Str::random(10);
    DB::table('stripe_checkout_sessions')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'stripe_session_id' => $page, 'mode' => 'subscription',
        'livemode' => false, 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);

    a06PostStripe('/webhooks/stripe', 'checkout.session.completed', ['id' => $page, 'object' => 'checkout.session', 'metadata' => ['organization_id' => $orgId]])->assertStatus(202);
    expect(DB::table('stripe_checkout_sessions')->where('stripe_session_id', $page)->value('completed_at'))->not->toBeNull('the page is closed whatever else the event could do');
})->group('security');

it('⚠️ R3-4 — the premise refuses a row two keys to the SAME parent reach (counted twice, removed once)', function () {
    expect(OrganizationDeletion::uncountedCascades([['links', 'from_unit_id', 'units', 'id'], ['links', 'to_unit_id', 'units', 'id']], ['units'], []))
        ->toBe(['links ← units']);
})->group('security');

it('⚠️⚠️ R3-5 — a delivery that fails AFTER another took its stale claim over and finished it never marks the event unprocessed', function () {
    $orgId = (string) seedScenario(1)['org']->id;
    $event = 'evt_a06_r35_'.Str::lower(Str::random(10));

    expect(fn () => app(TenantContext::class)->forOrganization($orgId, fn () => WebhookEventClaim::once('stripe', $event, 'payment_intent.succeeded', [], function () use ($event): void {
        // meanwhile, the claim went stale and another delivery took it over and FINISHED it
        DB::table('webhook_events')->where('external_event_id', $event)->update(['status' => 'processed', 'processed_at' => now()]);
        throw new RuntimeException('a06: the first holder fails late');
    })))->toThrow(RuntimeException::class, 'fails late');

    expect(DB::table('webhook_events')->where('external_event_id', $event)->value('status'))->toBe('processed', 'a `failed` here would let the event be applied twice');
})->group('security');
