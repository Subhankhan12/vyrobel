<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdminAction;
use App\Models\PlatformAnnouncement;
use App\Models\StatusIncident;
use App\Models\Subscription;
use App\Support\Platform\ActionLabel;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ADMIN 03 — the quality pass's behaviour fixes, each found by walking the live console. Copy, spacing and font fixes
// carry no test here by design; these are the ones where the console told the admin something untrue.

uses(DatabaseTransactions::class);

/** A customer organisation whose account status is `active` and whose subscription is a free trial — exactly what signup makes. */
function aqTrialOrg(string $name): Organization
{
    $org = Organization::create(['name' => $name, 'slug' => 'aq-'.Str::lower(Str::random(10)), 'status' => 'active', 'default_currency' => 'EUR']);
    $plan = Plan::create(['code' => 'aq_'.Str::lower(Str::random(8)), 'name' => 'AQ Plan', 'price_minor' => 4900, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => false]);
    app(TenantContext::class)->forOrganization((string) $org->id, fn () => Subscription::create(['organization_id' => $org->id, 'plan_id' => $plan->id, 'status' => 'trialing', 'trial_ends_at' => now()->addDays(14)]));

    return $org;
}

it('⚠️⚠️ F2 — a free trial is read from the SUBSCRIPTION everywhere: the directory filter finds it, the plan column says it, the overview never says "0 on trial"', function () {
    $tag = 'Zaq'.Str::random(5);
    $org = aqTrialOrg($tag.' Trial Hotel');
    $admin = platformAdmin();

    // The "On a free trial" filter finds an organisation whose ACCOUNT status is "active" — because its subscription is trialing.
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['status' => 'trialing', 'search' => $tag]))
        ->assertOk()->assertSee($org->slug)->assertSee('AQ Plan · free trial');

    // The overview no longer prints an account-status "on trial" figure that is always 0 beside the trial count.
    $this->flushSession();
    $html = (string) $this->actingAs($admin, 'admin')->get(route('admin.overview', ['internal' => 'include']))->assertOk()->getContent();
    expect($html)->not->toContain(' on trial</div>')->toContain('A free trial is a subscription state')->toContain('Account status');

    // …and the detail page labels the two states apart, in words.
    $this->flushSession();
    $this->actingAs($admin, 'admin')->get(route('admin.customers.show', $org->id))->assertOk()
        ->assertSee('Account status')->assertSee('On a free trial')->assertDontSee('>Trialing<', false);
})->group('security');

it('⚠️ F3 — with nothing due, the privacy desk says so; it never prints a timezone with no date', function () {
    DB::table('guests')->whereNotNull('scheduled_deletion_at')->whereNull('anonymized_at')->update(['scheduled_deletion_at' => null]);

    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.privacy.index'))->assertOk()->getContent();

    expect($html)->toContain('Nothing scheduled')
        ->and(preg_match('/Next due<\/span><span class="ad-row-v">\s*UTC\s*</', $html))->toBe(0);
})->group('security');

it('⚠️ F12 — a form with several problems names EVERY one, once, from the shell — not just the first', function () {
    $html = (string) $this->actingAs(platformAdmin(), 'admin')
        ->from(route('admin.announcements.index'))->followingRedirects()
        ->post(route('admin.announcements.store'), ['reason' => 'ADMIN 03 test — empty form'])
        ->assertOk()->getContent();

    // Title, message and start time are all missing — all three are named, not only the first.
    expect($html)->toContain('The title field is required.')
        ->toContain('The body field is required.')
        ->toContain('The starts at field is required.')
        // …and exactly once: the per-page copies that duplicated the shell's are gone.
        ->and(substr_count($html, 'The title field is required.'))->toBe(1);
})->group('security');

it('⚠️⚠️ F13 — the ledger row for a published announcement and a posted incident NAMES the row it created', function () {
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.announcements.store'), [
        'title' => 'AQ ledger test', 'body' => 'Body.', 'level' => array_key_first(PlatformAnnouncement::LEVELS), 'audience' => 'chosen',
        'organization_ids' => [aqTrialOrg('Zaq Ledger Org')->id], 'starts_at' => now()->addYear()->format('Y-m-d\TH:i'), 'reason' => 'ADMIN 03 ledger target test',
    ])->assertSessionHasNoErrors();
    $announcement = PlatformAnnouncement::query()->where('title', 'AQ ledger test')->sole();
    $publish = PlatformAdminAction::query()->where('action', 'platform.announcement.publish')->where('platform_admin_id', $admin->id)->sole();
    expect($publish->target_type)->toBe('announcement')->and($publish->target_id)->toBe((string) $announcement->id);

    $this->actingAs($admin, 'admin')->post(route('admin.status.store'), [
        'title' => 'AQ incident', 'impact' => array_key_first(StatusIncident::IMPACTS), 'status' => 'investigating', 'message' => 'Test.', 'reason' => 'ADMIN 03 ledger target test',
    ])->assertSessionHasNoErrors();
    $incident = StatusIncident::query()->where('title', 'AQ incident')->sole();
    $open = PlatformAdminAction::query()->where('action', 'platform.status.incident_open')->where('platform_admin_id', $admin->id)->sole();
    expect($open->target_type)->toBe('status_incident')->and($open->target_id)->toBe((string) $incident->id);
})->group('security');

it('⚠️ F6 · F7 — console pages print words for actions and absolute UTC times; the raw key stays in reach', function () {
    $org = aqTrialOrg('Zaq Words Org');
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.customers.internal', $org->id), ['internal' => '1', 'reason' => 'ADMIN 03 words test'])->assertSessionHasNoErrors();

    // Organisation detail: the console action reads as words, the key is its title, and the time is absolute UTC.
    $this->flushSession();
    $html = (string) $this->actingAs($admin, 'admin')->get(route('admin.customers.show', $org->id))->assertOk()->getContent();
    expect($html)->toContain('title="platform.customer.mark_internal">Marked as internal</strong>')
        ->and(preg_match('/<time class="ad-time" datetime="[^"]+" title="[^"]+ UTC · [^"]+">\d{1,2} [A-Z][a-z]{2} \d{4}, \d{2}:\d{2} UTC<\/time>/', $html))->toBe(1)
        ->and(preg_match('/>\s*\d+ (second|minute|hour|day)s? ago\s*</', $html))->toBe(0);

    // The audit log: the words, with the key beneath them for the investigator.
    $this->flushSession();
    $this->actingAs($admin, 'admin')->get(route('admin.audit.index', ['kind' => 'all']))->assertOk()
        ->assertSee('Marked as internal')->assertSee('platform.customer.mark_internal');

    // The label rule itself: a failed action says so; a tenant event key becomes a sentence, British.
    expect(ActionLabel::platform('platform.customer.suspend.failed'))->toBe('Suspended the organisation — failed')
        ->and(ActionLabel::platform('platform.privacy.request_in_progress'))->toBe('Marked a privacy request as in progress')
        ->and(ActionLabel::event('organization.settings_updated'))->toBe('Organisation settings updated');
})->group('security');
