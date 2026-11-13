<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\Organization;
use App\Models\PlatformAdminAction;
use App\Models\PrivacyRequest;
use App\Models\User;
use App\Support\Platform\PlatformFunnel;
use App\Support\Platform\PlatformQuery;
use App\Support\Privacy\RetentionCoverage;
use App\Support\Privacy\SubProcessors;
use App\Support\Retention\RetentionPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 4 — THE FUNNEL (recorded facts only, by source, unmeasured stages named) and THE PRIVACY DESK
// (the existing erasure queue watched · a register with status · retention coverage awaiting a ruling · sub-processors).

uses(DatabaseTransactions::class);

function fpFunnel(bool $internal = false): array
{
    test()->actingAs(platformAdmin(), 'admin');

    return app(PlatformQuery::class)->read('test.funnel', fn () => app(PlatformFunnel::class)->compute($internal));
}

// ══ FUNNEL ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ the funnel counts each stage from what is RECORDED, split by source — internal left out', function () {
    $before = fpFunnel();

    // Google: verified, a reservation that checked in, and a paid invoice.
    $g = seedScenario(1);
    $g['org']->forceFill(['signup_source' => 'google'])->save();
    operatorUser($g);
    $r = book($g);
    app(TenantContext::class)->forOrganization((string) $g['org']->id, fn () => DB::table('reservations')->where('id', $r->id)->update(['status' => 'checked_in']));
    DB::table('webhook_events')->insert(['id' => (string) Str::ulid(), 'organization_id' => $g['org']->id, 'source' => 'stripe', 'external_event_id' => 'evt_'.Str::random(14),
        'event_type' => 'invoice.paid', 'signature_verified' => true, 'status' => 'processed', 'received_at' => now(), 'payload' => '{}', 'created_at' => now(), 'updated_at' => now()]);
    // Email: signed up, never verified.
    $e = seedScenario(1);
    $e['org']->forceFill(['signup_source' => 'email'])->save();
    operatorUser($e, ['email_verified_at' => null]);
    // Unknown source: verified, with a reservation that has NOT been checked in — a first reservation, no check-in.
    $u = seedScenario(1);
    operatorUser($u);
    book($u);
    // Internal X signup — must not appear.
    $x = seedScenario(1);
    $x['org']->forceFill(['signup_source' => 'twitter', 'is_internal' => true])->save();

    $after = fpFunnel();
    $delta = fn (string $src, string $stage) => $after['sources'][$src][$stage] - $before['sources'][$src][$stage];

    expect([$delta('google', 'signed_up'), $delta('google', 'verified'), $delta('google', 'first_reservation'), $delta('google', 'first_check_in'), $delta('google', 'paid')])->toBe([1, 1, 1, 1, 1])
        ->and([$delta('email', 'signed_up'), $delta('email', 'verified'), $delta('email', 'first_reservation')])->toBe([1, 0, 0])
        ->and([$delta('unknown', 'signed_up'), $delta('unknown', 'verified'), $delta('unknown', 'first_reservation'), $delta('unknown', 'first_check_in')])->toBe([1, 1, 1, 0])
        ->and($delta('twitter', 'signed_up'))->toBe(0)
        ->and(fpFunnel(true)['sources']['twitter']['signed_up'] - $before['sources']['twitter']['signed_up'])->toBeGreaterThanOrEqual(1);
})->group('security');

it('the funnel page NAMES what it cannot measure, and measures — never assumes — that sign-up creates a property', function () {
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.funnel'))->assertOk()
        ->assertSee('Visited the site')->assertSee('Reached /app')->assertSee('Unmeasured')
        ->assertSee('Organisations with no property');
    expect(array_keys(PlatformFunnel::STAGES))->toBe(['signed_up', 'verified', 'first_reservation', 'first_check_in', 'paid']);
})->group('security');

// ══ PRIVACY DESK ═════════════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ the erasure queue is watched — an OVERDUE request is red — and no guest is ever shown', function () {
    $s = seedScenario(1);
    $tenant = app(TenantContext::class);
    // ⚠️ forceFill: `scheduled_deletion_at` is not fillable (only GuestDeletionService sets it) — a mass-assign
    //    would be dropped silently and the fixture would schedule nobody.
    $tenant->forOrganization((string) $s['org']->id, function (): void {
        Guest::create(['first_name' => 'Zorbalina', 'last_name' => 'Pendingham'])->forceFill(['scheduled_deletion_at' => now()->addDays(5)])->save();
        Guest::create(['first_name' => 'Quillon', 'last_name' => 'Overduewright'])->forceFill(['scheduled_deletion_at' => now()->subDay()])->save();
    });

    $html = $this->actingAs(platformAdmin(), 'admin')->get(route('admin.privacy.index'))->assertOk()
        ->assertSee('the daily erasure job is not running')->getContent();

    foreach (['Zorbalina', 'Pendingham', 'Quillon', 'Overduewright'] as $name) {
        expect(str_contains((string) $html, $name))->toBeFalse("a guest's name reached the privacy desk: {$name}");
    }
})->group('security');

it('⚠️⚠️ the RETENTION METER counts from the inventory, reads enforcement from code, and follows the founder’s ruling (three buckets, 2026-11-13)', function () {
    expect(RetentionCoverage::RULING)->toBe('three_buckets');

    $c = (new RetentionCoverage)->compute();
    // ⚠️ M is the AUDITED DOC's own row count — read from the doc, never typed — even though the meter reads the
    //    shipped data file generated from it (TONIGHT 01).
    $rows = count(array_filter(file(RetentionCoverage::sourcePath()) ?: [], fn ($l) => str_starts_with($l, '| **')));
    expect($c['measurable'])->toBeTrue()
        ->and($c['categories'])->toBe($rows)   // read, never typed
        ->and($c['strict'])->toBeLessThanOrEqual($c['lenient'])
        ->and($c['lenient'])->toBeLessThanOrEqual($c['categories'])
        ->and(array_keys($c['enforced']))->toContain(...array_keys(RetentionPolicy::windows()))
        ->and(array_keys($c['enforced']))->toContain('login_activity', 'guest_documents');

    // Where the inventory is not deployed, it says so — it never falls back to a remembered number.
    $gone = (new RetentionCoverage)->compute('/nonexistent/data-inventory.json');
    expect($gone['measurable'])->toBeFalse()->and($gone['categories'])->toBeNull()->and($gone['reason'])->toContain('not deployed');

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.privacy.index'))->assertOk()
        ->assertSee('Pruned by a timer')->assertSee('Kept on purpose')->assertSee('No rule yet')
        ->assertDontSee('Awaiting the founder’s ruling on what counts')->assertDontSee('Neither reading is the ruling');
})->group('security');

// ⚠️⚠️ TONIGHT 01 (2026-09-25) — the meter reads a SHIPPED data file generated from the doc, because docs/ is not
// deployed. These three tests are what make "one source of truth" true rather than hoped for.
it('⚠️⚠️ the SHIPPED inventory data file is exactly what the audited doc produces — the two cannot drift', function () {
    $fresh = RetentionCoverage::render(RetentionCoverage::categoriesFrom(
        (string) file_get_contents(RetentionCoverage::sourcePath()),
        RetentionCoverage::liveTables(),
    ));

    expect((string) file_get_contents(RetentionCoverage::inventoryPath()))->toBe($fresh,
        RetentionCoverage::SOURCE_DOC.' and '.RetentionCoverage::DATA_FILE.' differ — run `php artisan privacy:inventory-data` and commit both');

    // it holds categories and table names — no address, nothing personal
    expect((string) file_get_contents(RetentionCoverage::inventoryPath()))->not->toContain('@');
})->group('security');

// ⚠️⚠️ ADMIN 05 (2026-09-26) — the drift test above regenerates through liveTables() itself, so it could not see
// liveTables() reading the WRONG databases: Laravel 13's getTables() with no argument lists every database the
// connection can see, and the shipped file named six tables (`bookings`, `tickets`, …) only other projects hold.
// This reads the answer independently (information_schema, DATABASE()). ⚠️ It can only catch the regression on a server
// where more than one database is visible — the dev machine has five.
it('⚠️⚠️ the meter reads THIS database\'s tables only — never another database the connection can see', function () {
    $mine = array_map(fn ($r): string => strtolower((string) $r->t), DB::select(
        "SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE IN ('BASE TABLE', 'SYSTEM VERSIONED')"
    ));
    sort($mine);
    $live = RetentionCoverage::liveTables();
    sort($live);

    expect(count($mine))->toBeGreaterThan(100, 'the independent read must really see the schema')
        ->and($live)->toBe($mine);
})->group('security');

it('⚠️⚠️ the inventory the meter reads is one the release script SHIPS — never docs/, tests/ or a .md file', function () {
    $file = RetentionCoverage::DATA_FILE;

    // ⚠️ ops/release.sh's own exclusion and refusal, READ from the script rather than remembered, then applied.
    $script = (string) file_get_contents(base_path('ops/release.sh'));
    expect($script)->toContain('grep -vE \'^(docs/|tests/|\.github/)|\.md$\'')
        ->and($script)->toContain('resources/css/*|resources/js/*');

    expect(RetentionCoverage::inventoryPath())->toBe(base_path($file))
        ->and(preg_match('#^(docs/|tests/|\.github/)|\.md$#', $file))->toBe(0)
        ->and(str_starts_with($file, 'resources/css/') || str_starts_with($file, 'resources/js/'))->toBeFalse();
})->group('security');

it('an unreadable inventory data file is reported, never counted', function () {
    $path = tempnam(sys_get_temp_dir(), 'inv');
    file_put_contents($path, '{"not": "an inventory"');

    $c = (new RetentionCoverage)->compute($path);
    unlink($path);

    expect($c['measurable'])->toBeFalse()->and($c['categories'])->toBeNull()->and($c['reason'])->toContain('could not be read');
})->group('security');

it('the sub-processor register claims NO agreement, and shows states — never a config value', function () {
    config(['services.llm.api_key' => 'sk-ant-SECRET-NEEDLE-123', 'services.llm.fake' => false]);

    foreach (SubProcessors::register() as $row) {
        expect($row['agreement'])->toBe('None on file');
    }
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.privacy.index'))->assertOk()
        ->assertSee('Hostinger (email)')->assertSee('Stripe (payments &amp; billing)', false)->assertSee('No agreement is on file with any of them yet.')
        ->assertDontSee('SECRET-NEEDLE');
})->group('security');

it('⚠️⚠️ the REGISTER records a request by user id — the email is NOT stored — with a one-month due date, and every step is audited', function () {
    $user = User::factory()->create(['email' => 'pr-'.Str::lower(Str::random(10)).'@example.net']);
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.privacy.store'), [
        'kind' => 'delete', 'subject_kind' => 'account_holder', 'email' => $user->email, 'channel' => 'email',
        'received_on' => '2026-07-20', 'reason' => 'Email from the account holder asking to be deleted.',
    ])->assertSessionHasNoErrors();

    $req = PrivacyRequest::query()->where('subject_user_id', $user->id)->sole();
    expect($req->status)->toBe('received')->and($req->due_at->toDateString())->toBe('2026-08-20')
        ->and(json_encode($req->getAttributes()))->not->toContain($user->email)
        ->and(json_encode(PlatformAdminAction::query()->where('action', 'platform.privacy.request_recorded')->where('target_id', $user->id)->value('meta')))->not->toContain('@');

    $this->actingAs($admin, 'admin')->post(route('admin.privacy.update', $req->id), ['status' => 'refused', 'reason' => 'No account deletion exists yet — awaiting the founder ruling.'])->assertSessionHasNoErrors();
    expect($req->fresh()->status)->toBe('refused')->and($req->fresh()->closed_at)->not->toBeNull();

    // A closed request cannot be moved again.
    $this->actingAs($admin, 'admin')->post(route('admin.privacy.update', $req->id), ['status' => 'completed', 'reason' => 'Trying to reopen a closed request.']);
    expect(sessionErrorKeys())->toContain('status')->and($req->fresh()->status)->toBe('refused');
})->group('security');

it('an unknown email is refused, a guest request is recorded by ORGANISATION only, and Analyst/Billing cannot record', function () {
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.privacy.store'), ['kind' => 'export', 'subject_kind' => 'account_holder', 'email' => 'nobody-'.Str::random(8).'@example.net', 'channel' => 'email', 'received_on' => '2026-07-20', 'reason' => 'Email received today.']);
    expect(sessionErrorKeys())->toContain('email');

    $org = Organization::create(['name' => 'PR Hotel', 'slug' => 'pr-'.Str::lower(Str::random(8)), 'status' => 'active', 'default_currency' => 'EUR']);
    $this->flushSession();
    $this->actingAs($admin, 'admin')->post(route('admin.privacy.store'), ['kind' => 'export', 'subject_kind' => 'relayed_guest', 'organization_id' => $org->id, 'channel' => 'hotel', 'received_on' => '2026-07-20', 'reason' => 'Hotel forwarded a guest request.'])->assertSessionHasNoErrors();
    expect(PrivacyRequest::query()->where('related_organization_id', $org->id)->sole()->subject_user_id)->toBeNull();

    foreach (['analyst', 'billing'] as $role) {
        $this->flushSession();
        $this->actingAs(platformAdmin(['role' => $role]), 'admin')->post(route('admin.privacy.store'), ['kind' => 'export', 'subject_kind' => 'relayed_guest', 'organization_id' => $org->id, 'channel' => 'hotel', 'received_on' => '2026-07-20', 'reason' => 'Should be refused.'])->assertForbidden();
    }
})->group('security');
