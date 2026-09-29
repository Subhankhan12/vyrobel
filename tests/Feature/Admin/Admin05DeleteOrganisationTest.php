<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdminAction;
use App\Notifications\FeedNotification;
use App\Services\BillingService;
use App\Services\Platform\OrganizationDeletion;
use App\Services\Platform\OrganizationDeletionRefused;
use App\Support\Tenancy\TenantContext;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;

// ⚠️⚠️ ADMIN 05 (2026-09-26) — PERMANENT DELETION of an organisation, by the founder's ruling ("delete everything,
// always"). What these tests hold it to: nothing of the organisation survives in ANY table (read from the schema, not
// a typed list); a person who also belongs elsewhere keeps their account; another organisation is untouched, row for
// row; the files go; every refusal refuses BEFORE anything is touched; and a failure part-way deletes NOTHING.

uses(DatabaseTransactions::class);

/** A realistic organisation: property, room type, units, rate plan, guest, availability, a booking, staff. */
function a05World(): array
{
    $s = seedScenario(2);
    book($s);
    $staff = operatorUser($s, ['email' => 'a05-staff-'.Str::lower(Str::random(6)).'@vyrobel.com'], 'Hotel Manager');
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['status' => 'suspended']));

    return $s + ['staff' => $staff];
}

/**
 * Every organisation-owned table — read INDEPENDENTLY of the service under test (information_schema, this database only;
 * the alias keeps the key's case on MySQL 8). ⚠️ Counting through the service's own list would make "nothing survived"
 * blind to exactly the table that list missed.
 *
 * @return list<string>
 */
function a05Tables(): array
{
    $tables = array_map(fn ($r): string => (string) $r->t, DB::select(
        "SELECT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'organization_id' AND TABLE_NAME <> 'organizations'"
    ));
    expect(count($tables))->toBeGreaterThan(90, 'the independent read must really see the schema, or every count below is zero by blindness');
    sort($tables);

    return $tables;
}

/** Every row any organisation-owned table holds for this organisation. */
function a05Rows(string $organizationId): int
{
    $n = 0;
    foreach (a05Tables() as $table) {
        $n += DB::table($table)->where('organization_id', $organizationId)->count();
    }

    return $n;
}

/** The first error flashed under this key — the session holds the bag as an object or, once serialised, as an array. */
function a05Error(string $key): string
{
    $errors = session('errors');

    return $errors instanceof ViewErrorBag ? (string) $errors->first($key) : (string) ($errors['default']['messages'][$key][0] ?? '');
}

function a05Delete(object $test, Organization $org, array $overrides = []): TestResponse
{
    return $test->post(route('admin.customers.delete', $org->id), array_merge([
        'current_password' => 'password', 'confirm_name' => (string) $org->name, 'acknowledge_delete' => '1',
        'reason' => 'The customer asked us to erase their account and all their data.',
    ], $overrides));
}

it('⚠️⚠️ deletes the organisation and EVERYTHING it owns — no row survives in any organisation table', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    $staffId = (string) $w['staff']->id;
    expect(a05Rows($orgId))->toBeGreaterThan(20, 'the fixture must really own rows across many tables');
    $other = seedScenario(1);   // a bystander organisation, which must come out byte-for-byte untouched
    book($other);
    $otherBefore = a05Rows((string) $other['org']->id);

    // ⚠️ rows no `organization_id` reaches — they belong to the organisation only THROUGH a parent (a swept schema: these
    // are every such table without a cascading foreign key). Each is planted, and each must go; the bystander's stay.
    $amenity = (string) Str::ulid();
    DB::table('amenities')->insert(['id' => $amenity, 'name' => 'A05 Pool', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('amenitiables')->insert([
        ['amenity_id' => $amenity, 'amenitiable_type' => 'property', 'amenitiable_id' => $w['property']->id],
        ['amenity_id' => $amenity, 'amenitiable_type' => 'room_type', 'amenitiable_id' => $w['roomType']->id],
        ['amenity_id' => $amenity, 'amenitiable_type' => 'property', 'amenitiable_id' => $other['property']->id],
    ]);
    $guestId = (string) $w['guest']->id;
    DB::table('notifications')->insert([
        ['id' => (string) Str::uuid(), 'type' => 'a05', 'notifiable_type' => 'guest', 'notifiable_id' => $guestId, 'data' => '{}', 'created_at' => now(), 'updated_at' => now()],
        ['id' => (string) Str::uuid(), 'type' => 'a05', 'notifiable_type' => 'user', 'notifiable_id' => $staffId, 'data' => '{}', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('login_activity')->insert([
        ['id' => (string) Str::ulid(), 'event' => 'login', 'guard' => 'guest', 'subject_type' => 'guest', 'subject_id' => $guestId, 'identifier' => null, 'occurred_at' => now()],
        ['id' => (string) Str::ulid(), 'event' => 'login', 'guard' => 'web', 'subject_type' => 'user', 'subject_id' => $staffId, 'identifier' => null, 'occurred_at' => now()],
        ['id' => (string) Str::ulid(), 'event' => 'failed', 'guard' => 'web', 'subject_type' => null, 'subject_id' => null, 'identifier' => (string) $w['staff']->email, 'occurred_at' => now()],
    ]);
    DB::table('sessions')->insert(['id' => 'a05-'.Str::random(30), 'user_id' => $staffId, 'payload' => '', 'last_activity' => time()]);

    $admin = platformAdmin();
    a05Delete($this->actingAs($admin, 'admin'), $w['org'])->assertRedirect(route('admin.customers.index'))->assertSessionHasNoErrors();

    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeFalse()
        ->and(a05Rows($orgId))->toBe(0)
        ->and(DB::table('users')->where('id', $staffId)->exists())->toBeFalse('a person whose only organisation it was is deleted too')
        ->and(a05Rows((string) $other['org']->id))->toBe($otherBefore, 'another organisation must be untouched');

    expect(DB::table('amenitiables')->whereIn('amenitiable_id', [$w['property']->id, $w['roomType']->id])->count())->toBe(0)
        ->and(DB::table('amenitiables')->where('amenitiable_id', $other['property']->id)->count())->toBe(1, 'the bystander keeps its amenity link')
        ->and(DB::table('amenities')->where('id', $amenity)->exists())->toBeTrue('an amenity is shared, never the organisation\'s')
        ->and(DB::table('notifications')->whereIn('notifiable_id', [$guestId, $staffId])->count())->toBe(0)
        ->and(DB::table('login_activity')->whereIn('subject_id', [$guestId, $staffId])->count())->toBe(0)
        ->and(DB::table('login_activity')->where('identifier', (string) $w['staff']->email)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $staffId)->count())->toBe(0);

    // the console's own record of it is KEPT: the intent (with the reason) and the result
    expect(PlatformAdminAction::query()->where('action', 'platform.customer.delete')->where('target_id', $orgId)->exists())->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.deleted')->where('target_id', $orgId)->value('meta')['name'])->toBe((string) $w['org']->name);
})->group('security');

it('⚠️⚠️ a person who ALSO belongs to another organisation keeps their account — they only lose this membership', function () {
    $w = a05World();
    $elsewhere = seedScenario(1);
    DB::table('organization_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $elsewhere['org']->id, 'user_id' => $w['staff']->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    expect(DB::table('users')->where('id', $w['staff']->id)->exists())->toBeTrue()
        ->and(DB::table('organization_user')->where('user_id', $w['staff']->id)->pluck('organization_id')->all())->toBe([(string) $elsewhere['org']->id]);
})->group('security');

it('⚠️⚠️ a FORMER member of another organisation keeps their account — its history still names them', function () {
    $w = a05World();
    $b = seedScenario(1);
    // no membership in B any more — but B's leave record for them stands, and it CASCADES from `users`
    $leave = (string) Str::ulid();
    app(TenantContext::class)->forOrganization((string) $b['org']->id, fn () => DB::table('leave_requests')->insert([
        'id' => $leave, 'organization_id' => $b['org']->id, 'user_id' => $w['staff']->id,
        'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->subMonth()->addDays(2)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
    ]));
    $bBefore = a05Rows((string) $b['org']->id);

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    expect(DB::table('users')->where('id', $w['staff']->id)->exists())->toBeTrue('another organisation\'s records name them')
        ->and(DB::table('leave_requests')->where('id', $leave)->exists())->toBeTrue('the other organisation\'s history is untouched')
        ->and(a05Rows((string) $b['org']->id))->toBe($bBefore)
        ->and(DB::table('organizations')->where('id', $w['org']->id)->exists())->toBeFalse();
})->group('security');

it('⚠️ a former member named only in another organisation\'s AUDIT TRAIL keeps their account too', function () {
    $w = a05World();
    $b = seedScenario(1);
    // no foreign key reaches this column — it is read from OrganizationDeletion::UNKEYED_USER_COLUMNS
    DB::table('audit_events')->insert(['id' => (string) Str::ulid(), 'organization_id' => $b['org']->id, 'actor_type' => 'user', 'actor_id' => $w['staff']->id, 'action' => 'reservation.updated', 'created_at' => now()]);

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    expect(DB::table('users')->where('id', $w['staff']->id)->exists())->toBeTrue('the other organisation\'s history still names them');
})->group('security');

it('every notification names the organisation it is about — so a deletion can find it in a kept person\'s feed', function () {
    $notification = new class extends FeedNotification
    {
        public function eventKey(): string
        {
            return 'a05';
        }

        public function category(): string
        {
            return 'account';
        }

        public function data(object $notifiable): array
        {
            return ['title' => 'A05', 'context' => 'c', 'url' => '/app', 'category' => 'account'];
        }
    };
    $orgId = (string) seedScenario(1)['org']->id;

    expect(app(TenantContext::class)->forOrganization($orgId, fn () => $notification->toArray(new stdClass)))->toMatchArray(['organization_id' => $orgId]);
    app(TenantContext::class)->forget();
    expect($notification->toArray(new stdClass))->not->toHaveKey('organization_id');
});

it('removes the organisation\'s uploaded files once the rows are gone', function () {
    Storage::fake('public');
    $w = a05World();
    Storage::disk('public')->put('room-photos/'.$w['roomType']->id.'/a05.jpg', 'photo');
    app(TenantContext::class)->forOrganization((string) $w['org']->id, fn () => DB::table('media')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $w['org']->id, 'mediable_type' => 'room_type', 'mediable_id' => $w['roomType']->id,
        'collection' => 'photos', 'disk' => 'public', 'path' => 'room-photos/'.$w['roomType']->id.'/a05.jpg', 'mime' => 'image/jpeg', 'size' => 5,
        'created_at' => now(), 'updated_at' => now(),
    ]));

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing('room-photos/'.$w['roomType']->id.'/a05.jpg');
})->group('security');

it('⚠️⚠️ refuses — and deletes NOTHING — without every confirmation, or while it is not suspended', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    $before = a05Rows($orgId);
    $admin = platformAdmin();
    $post = fn (array $overrides) => a05Delete($this->actingAs($admin, 'admin'), $w['org'], $overrides);

    $post(['confirm_name' => 'Not The Name'])->assertSessionHasErrors('confirm_name');
    $post(['current_password' => 'wrong-password'])->assertSessionHasErrors('current_password');
    $post(['acknowledge_delete' => ''])->assertSessionHasErrors('acknowledge_delete');
    $post(['reason' => ''])->assertSessionHasErrors('reason');
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($orgId)->update(['status' => 'active']));
    $post([])->assertSessionHasErrors('status');

    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeTrue()->and(a05Rows($orgId))->toBe($before);
})->group('security');

it('⚠️⚠️ refuses while money is moving at a third party — live Stripe billing, a payout in transit', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    app(BillingService::class)->ensurePlans();
    $admin = platformAdmin();

    app(TenantContext::class)->forOrganization($orgId, fn () => DB::table('subscriptions')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $orgId, 'plan_id' => Plan::query()->firstOrFail()->getKey(), 'status' => 'active',
        'stripe_subscription_id' => 'sub_a05_live', 'stripe_customer_id' => 'cus_a05', 'current_period_end' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now(),
    ]));
    a05Delete($this->actingAs($admin, 'admin'), $w['org'])->assertSessionHasErrors('delete');
    expect(a05Error('delete'))->toContain('Stripe billing is live');
    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeTrue()
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.delete')->where('target_id', $orgId)->exists())
        ->toBeFalse('refused before anything was attempted: the ledger holds the check, not a failed deletion');

    // ⚠️ per layer: the SERVICE refuses too — a caller that skips the console's check still cannot delete
    expect(fn () => app(TenantContext::class)->runAsSuperAdmin(fn () => app(OrganizationDeletion::class)->delete(Organization::withTrashed()->findOrFail($orgId))))
        ->toThrow(OrganizationDeletionRefused::class, 'Stripe billing is live');
    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeTrue();
})->group('security');

it('⚠️⚠️ refuses when another organisation\'s records point into this one — a cascade would delete them too', function () {
    $w = a05World();
    $other = seedScenario(1);
    // a unit of the OTHER organisation that (wrongly) points at THIS organisation's property
    DB::table('units')->where('id', $other['units'][0]->id)->update(['property_id' => $w['property']->id, 'code' => 'A05-CROSS']);

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasErrors('delete');

    expect(DB::table('organizations')->where('id', $w['org']->id)->exists())->toBeTrue()
        ->and(DB::table('units')->where('id', $other['units'][0]->id)->exists())->toBeTrue();
})->group('security');

it('⚠️⚠️ is all-or-nothing — a failure part-way deletes NOTHING', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    $before = a05Rows($orgId);
    DB::connection()->beforeExecuting(function (string $sql): void {
        if (str_starts_with(strtolower(ltrim($sql)), 'delete from `organizations`')) {
            throw new RuntimeException('simulated failure half-way through');
        }
    });

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasErrors('delete');

    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeTrue()
        ->and(a05Rows($orgId))->toBe($before, 'every table cleared before the failure must be back')
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.delete.failed')->where('target_id', $orgId)->exists())->toBeTrue();
})->group('security');

it('⚠️⚠️ the service reads every organisation table of THIS database — each once, none missed, none from another', function () {
    // Laravel 13's getTables() with no argument lists every database the connection can see; the service once did, and
    // counted each table once per copy on the confirmation screen (225 names for 100 tables on the dev machine).
    expect(app(OrganizationDeletion::class)->organizationTables())->toBe(a05Tables());
})->group('security');

it('⚠️⚠️ the one-query foreign-key read is the framework\'s own answer, table for table', function () {
    $service = app(OrganizationDeletion::class);
    $shape = fn (array $keys): array => collect($keys)
        ->map(fn (array $k): array => ['name' => (string) $k['name'], 'columns' => array_values($k['columns']), 'foreign_table' => (string) $k['foreign_table'], 'foreign_columns' => array_values($k['foreign_columns'])])
        ->sortBy('name')->values()->all();
    $compared = 0;
    foreach (a05Tables() as $table) {
        expect($shape($service->foreignKeysOf($table)))->toBe($shape(Schema::getForeignKeys($table)), "the foreign keys of [{$table}] differ");
        $compared += count(Schema::getForeignKeys($table));
    }
    expect($compared)->toBeGreaterThan(150, 'the comparison must really see the schema\'s keys');
})->group('security');

it('⚠️⚠️ a preview reads the one schema view MariaDB rebuilds on every read ONCE — never once per table (118 s on production)', function () {
    // ⚠️ ADMIN 06: the full count needs each key's delete rule, which lives ONLY in REFERENTIAL_CONSTRAINTS. 🔬 On
    // production one whole-schema read of it takes 7 ms; reading it once per table (as Schema::getForeignKeys does) took
    // 1.1 s a table. The console's detail page builds ONE service and asks it for the preview and the refusal.
    $w = a05World();
    $slow = [];
    DB::listen(function ($query) use (&$slow): void {
        if (stripos($query->sql, 'referential_constraints') !== false) {
            $slow[] = $query->sql;
        }
    });

    $service = app(OrganizationDeletion::class);
    $service->preview((string) $w['org']->id);
    $service->refusal((string) $w['org']->id);

    expect($slow)->toHaveCount(1);
    expect(stripos($slow[0], 'table_name =') === false)->toBeTrue('one read for the whole schema, never a read per table');
})->group('security');

it('⚠️⚠️ refuses — and rolls EVERYTHING back — if a row of the organisation survives the sweep', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    $before = a05Rows($orgId);
    $planted = false;
    // the moment the organisation row goes, a row for it appears in a table no foreign key guards — as a write racing the
    // deletion would. The post-condition must see it and undo the lot.
    DB::listen(function ($query) use ($orgId, &$planted): void {
        if (! $planted && str_starts_with(strtolower(ltrim($query->sql)), 'delete from `organizations`')) {
            $planted = true;
            DB::table('audit_events')->insert(['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'action' => 'a05.race', 'created_at' => now()]);
        }
    });

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasErrors('delete');

    expect($planted)->toBeTrue('the race must really have been staged')
        ->and(a05Error('delete'))->toContain('survived the deletion (in audit_events)')->toContain('Nothing was deleted')
        ->and(DB::table('organizations')->where('id', $orgId)->exists())->toBeTrue()
        ->and(a05Rows($orgId))->toBe($before);
})->group('security');

it('a guest\'s reset token goes with the organisation — unless another organisation\'s guest shares the address', function () {
    $w = a05World();
    $other = seedScenario(1);
    [$mine, $shared] = ['a05-'.Str::lower(Str::random(16)), 'a05-'.Str::lower(Str::random(16))];
    $second = app(TenantContext::class)->forOrganization((string) $w['org']->id, fn () => Guest::create(['first_name' => 'A05', 'last_name' => 'Shared']));
    DB::table('guests')->where('id', $w['guest']->id)->update(['email_hash' => $mine]);
    DB::table('guests')->whereIn('id', [$second->id, $other['guest']->id])->update(['email_hash' => $shared]);
    DB::table('guest_password_reset_tokens')->insert([
        ['email' => $mine, 'token' => 'a05-mine', 'created_at' => now()],
        ['email' => $shared, 'token' => 'a05-shared', 'created_at' => now()],
    ]);

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    expect(DB::table('guest_password_reset_tokens')->where('email', $mine)->exists())->toBeFalse()
        ->and(DB::table('guest_password_reset_tokens')->where('email', $shared)->exists())->toBeTrue('the other organisation\'s guest still needs it');
})->group('security');

/** The result the console wrote for this deletion — its own record of what went. */
function a05Result(string $organizationId): array
{
    return (array) PlatformAdminAction::query()->where('action', 'platform.customer.deleted')->where('target_id', $organizationId)->value('meta');
}

it('⚠️⚠️ a failure AFTER the commit is told as a deletion — never "nothing was deleted", never ledgered as failed', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    // the permission cache is cleared after the commit — make exactly that step fail
    $this->app->instance(PermissionRegistrar::class, new class(app(CacheManager::class)) extends PermissionRegistrar
    {
        public function forgetCachedPermissions(): bool
        {
            throw new RuntimeException('cache store unavailable');
        }
    });

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])
        ->assertRedirect(route('admin.customers.index'))->assertSessionHasNoErrors()
        ->assertSessionHas('admin_notice', fn (string $n): bool => str_contains($n, 'Deleted') && str_contains($n, 'permission cache could not be cleared'));

    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeFalse()
        ->and(a05Result($orgId)['warnings'] ?? [])->toHaveCount(1)
        ->and(PlatformAdminAction::query()->where('action', 'platform.customer.delete.failed')->where('target_id', $orgId)->exists())->toBeFalse();
})->group('security');

it('counts a file already gone as MISSING, and keeps one another organisation still points at', function () {
    Storage::fake('local');
    $w = a05World();
    $other = seedScenario(1);
    $media = fn (array $s, string $path) => app(TenantContext::class)->forOrganization((string) $s['org']->id, fn () => DB::table('media')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'mediable_type' => 'room_type', 'mediable_id' => $s['roomType']->id,
        'collection' => 'photos', 'disk' => 'local', 'path' => $path, 'mime' => 'image/jpeg', 'size' => 5, 'created_at' => now(), 'updated_at' => now(),
    ]));
    $media($w, 'a05/never-written.jpg');                 // a row whose file was never there
    Storage::disk('local')->put('demo-photos/a05-shared.jpg', 'photo');
    $media($w, 'demo-photos/a05-shared.jpg');            // two tenants seeded from the same demo photo
    $media($other, 'demo-photos/a05-shared.jpg');

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    $result = a05Result((string) $w['org']->id);
    expect($result['files_missing'])->toBe(1)
        ->and($result['files_shared'])->toBe(1)
        ->and($result['files_deleted'])->toBe(0, 'a file that was never there is not "removed"');
    Storage::disk('local')->assertExists('demo-photos/a05-shared.jpg');
})->group('security');

it('⚠️ a file the disk will not remove is counted, logged WITH its path, and written to the ledger — never "removed"', function () {
    Storage::fake('local');
    $w = a05World();
    $path = 'room-photos/'.$w['roomType']->id.'/a05-stuck.jpg';
    Storage::disk('local')->put($path, 'photo');
    app(TenantContext::class)->forOrganization((string) $w['org']->id, fn () => DB::table('media')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $w['org']->id, 'mediable_type' => 'room_type', 'mediable_id' => $w['roomType']->id,
        'collection' => 'photos', 'disk' => 'local', 'path' => $path, 'mime' => 'image/jpeg', 'size' => 5, 'created_at' => now(), 'updated_at' => now(),
    ]));
    $dir = dirname(Storage::disk('local')->path($path));
    chmod($dir, 0555);   // the disk refuses: the directory is read-only
    Log::spy();

    try {
        a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors()
            ->assertSessionHas('admin_notice', fn (string $n): bool => str_contains($n, '1 file could not be removed'));
    } finally {
        chmod($dir, 0755);
    }

    $result = a05Result((string) $w['org']->id);
    expect($result['files_failed'])->toBe(1)
        ->and($result['files_failed_paths'])->toBe(['local:'.$path])
        ->and($result['files_deleted'])->toBe(0);
    Storage::disk('local')->assertExists($path);
    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context = []): bool => ($context['path'] ?? null) === $path);
})->group('security');

it('⚠️⚠️ a KEPT person\'s notifications about this organisation go — their notifications about anywhere else stay', function () {
    $w = a05World();
    $b = seedScenario(1);
    $bBooking = book($b);
    DB::table('organization_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $b['org']->id, 'user_id' => $w['staff']->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    $aBooking = (string) DB::table('reservations')->where('organization_id', $w['org']->id)->value('id');
    $note = function (array $data) use ($w): string {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert(['id' => $id, 'type' => 'a05', 'notifiable_type' => 'user', 'notifiable_id' => $w['staff']->id, 'data' => json_encode($data), 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    };
    $linksHere = $note(['title' => 'New booking', 'context' => 'Ana Guest · 2 nights', 'url' => '/app/reservations/'.$aBooking, 'category' => 'front_desk']);
    $namesHere = $note(['title' => 'Assigned to you', 'context' => 'Ana Guest · in the shared inbox', 'url' => '/app/inbox', 'category' => 'front_desk', 'organization_id' => (string) $w['org']->id]);
    $elsewhere = $note(['title' => 'New booking', 'context' => 'Other Guest', 'url' => '/app/reservations/'.$bBooking->id, 'category' => 'front_desk']);
    $general = $note(['title' => 'Your password was changed', 'context' => 'Just now', 'url' => '/app/profile', 'category' => 'account']);

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    $left = DB::table('notifications')->where('notifiable_id', $w['staff']->id)->pluck('id')->all();
    expect($left)->not->toContain($linksHere)->not->toContain($namesHere)
        ->and($left)->toContain($elsewhere)->toContain($general);
})->group('security');

it('⚠️ a platform admin\'s own sign-in history stays — even at the address of a person the deletion removes', function () {
    $w = a05World();
    $email = (string) $w['staff']->email;
    $admin = platformAdmin(['email' => $email]);
    DB::table('login_activity')->insert([
        ['id' => (string) Str::ulid(), 'event' => 'failed', 'guard' => 'admin', 'subject_type' => null, 'subject_id' => null, 'identifier' => $email, 'occurred_at' => now()],
        ['id' => (string) Str::ulid(), 'event' => 'login', 'guard' => 'admin', 'subject_type' => 'platform_admin', 'subject_id' => $admin->id, 'identifier' => $email, 'occurred_at' => now()],
        ['id' => (string) Str::ulid(), 'event' => 'failed', 'guard' => 'web', 'subject_type' => null, 'subject_id' => null, 'identifier' => $email, 'occurred_at' => now()],
    ]);

    a05Delete($this->actingAs($admin, 'admin'), $w['org'])->assertSessionHasNoErrors();

    expect(DB::table('login_activity')->where('identifier', $email)->where('guard', 'admin')->count())->toBe(2)
        ->and(DB::table('login_activity')->where('identifier', $email)->where('guard', 'web')->count())->toBe(0);
})->group('security');

it('⚠️ the SERVICE holds its own bounds — whoever calls it, an active or a protected organisation is refused', function () {
    $s = seedScenario(1);   // active
    $orgId = (string) $s['org']->id;
    $delete = fn () => app(TenantContext::class)->runAsSuperAdmin(fn () => app(OrganizationDeletion::class)->delete(Organization::withTrashed()->findOrFail($orgId)));

    expect($delete)->toThrow(OrganizationDeletionRefused::class, 'Suspend it first');
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($orgId)->update(['status' => 'suspended']));
    config(['security.baseline_tenant_guard' => $orgId]);
    expect($delete)->toThrow(OrganizationDeletionRefused::class, 'protected');
    expect(DB::table('organizations')->where('id', $orgId)->exists())->toBeTrue();
})->group('security');

it('⚠️ every column that names a person without a foreign key is one the deletion reads', function () {
    $keyed = [];
    foreach (a05Tables() as $table) {
        foreach (Schema::getForeignKeys($table) as $fk) {
            foreach ($fk['columns'] as $column) {
                $keyed[$table.'.'.$column] = true;
            }
        }
    }
    $notPeople = ['payments.refund_borne_by', 'properties.brand_powered_by'];   // words, not people
    $found = [];
    foreach (a05Tables() as $table) {
        foreach (Schema::getColumnListing($table) as $column) {
            $ref = $table.'.'.$column;
            if (! isset($keyed[$ref]) && ! in_array($ref, $notPeople, true) && preg_match('/(^|_)user_id$|^actor_id$|_by$|^author_id$|_by_id$|^assigned_to$/', $column) === 1) {
                $found[] = $ref;
            }
        }
    }
    sort($found);
    $listed = array_map(fn (array $c): string => $c[0].'.'.$c[1], OrganizationDeletion::UNKEYED_USER_COLUMNS);
    sort($listed);

    expect($found)->toBe($listed, 'a new column names a person without a foreign key — add it to OrganizationDeletion::UNKEYED_USER_COLUMNS');
})->group('security');

it('⚠️⚠️ a tenant with thousands of guests is deleted in bounded statements — never one list of every guest', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    $rows = [];
    for ($i = 0; $i < 1200; $i++) {
        $rows[] = ['id' => (string) Str::ulid(), 'organization_id' => $orgId, 'first_name' => 'A05', 'last_name' => 'Guest '.$i, 'created_at' => now(), 'updated_at' => now()];
    }
    foreach (array_chunk($rows, 300) as $chunk) {
        DB::table('guests')->insert($chunk);
    }
    $most = 0;
    DB::listen(function ($query) use (&$most): void {
        $most = max($most, count($query->bindings));
    });

    a05Delete($this->actingAs(platformAdmin(), 'admin'), $w['org'])->assertSessionHasNoErrors();

    expect(DB::table('guests')->where('organization_id', $orgId)->count())->toBe(0)
        ->and($most)->toBeLessThan(1100, 'one statement bound '.$most.' values — a list of every guest caps a deletable tenant at 65,535');
})->group('security');

it('a refusal says whether to wait or to act — a notification stuck for hours is not "try again in a few minutes"', function () {
    $w = a05World();
    $orgId = (string) $w['org']->id;
    $id = (string) Str::ulid();
    DB::table('webhook_events')->insert(['id' => $id, 'organization_id' => $orgId, 'source' => 'channex', 'external_event_id' => 'a05-'.$id, 'event_type' => 'booking', 'status' => 'received', 'received_at' => now()->subHours(3), 'created_at' => now()->subHours(3), 'updated_at' => now()]);

    expect(app(OrganizationDeletion::class)->refusal($orgId))->toContain('Channex notification')->toContain('looks stuck');

    DB::table('webhook_events')->where('id', $id)->update(['received_at' => now(), 'created_at' => now()]);
    expect(app(OrganizationDeletion::class)->refusal($orgId))->toContain('Try again in a few minutes');
})->group('security');

it('the protected organisations can never be deleted, and only an Owner may delete at all', function () {
    $w = a05World();
    $admin = platformAdmin();

    config(['security.baseline_tenant_guard' => (string) $w['org']->id]);
    a05Delete($this->actingAs($admin, 'admin'), $w['org'])->assertSessionHasErrors('lock');
    config(['security.baseline_tenant_guard' => '']);
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($w['org']->id)->update(['lifecycle_lock' => 'confirm']));
    a05Delete($this->actingAs($admin, 'admin'), $w['org'])->assertSessionHasErrors('lock');
    expect(DB::table('organizations')->where('id', $w['org']->id)->exists())->toBeTrue();

    foreach (['support', 'billing', 'analyst'] as $role) {
        $this->flushSession();
        a05Delete($this->actingAs(platformAdmin(['role' => $role]), 'admin'), $w['org'])->assertForbidden();
    }
})->group('security');

// ⚠️ KNOWN-LIMITS L19 — CLOSED BY ADMIN 06 (2026-09-27). The pin that said "starting a checkout writes nothing" turned
// red exactly as it was built to: a checkout now RECORDS its page, and a deletion refuses while one is open — proven in
// Admin06DeleteMoneyAndCountTest ("B19 — an open Stripe Checkout page refuses the deletion…").
