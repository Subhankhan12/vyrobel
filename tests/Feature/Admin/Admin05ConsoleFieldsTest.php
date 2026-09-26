<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\PlatformAnnouncement;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️ ADMIN 05 (2026-09-26) — THE CONSOLE'S FIELD SYSTEM (the founder's ruling: every console form). What these pin:
// the shared field partial is on every console page EXACTLY once (both layouts), every organisation list and the country
// list are searchable, and every enhanced control is still the NATIVE element the form posts — the enhancement is
// progressive, and the posted names are the behaviour contract every other console test reads. The look itself was
// checked in a browser (Playwright, 2026-09-26); a test cannot see a rendered page.

uses(DatabaseTransactions::class);

/** A <select> tag carrying every one of these attributes, in any order. */
function a05Select(string $html, string ...$attributes): bool
{
    $lookaheads = implode('', array_map(fn (string $a): string => '(?=[^>]*\s'.preg_quote($a, '/').'(?=[\s=>]))', $attributes));

    return preg_match('/<select'.$lookaheads.'[^>]*>/', $html) === 1;
}

it('every console page carries the field system exactly once — the signed-in shell', function () {
    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.index'))->assertOk()->getContent();

    expect(substr_count($html, 'window.__vyAdminFields = true'))->toBe(1)
        ->and(substr_count($html, '.ad-combo-panel {'))->toBe(1);
});

it('the sign-in page carries it too — the other layout', function () {
    $html = (string) $this->get(route('admin.login'))->assertOk()->getContent();

    expect(substr_count($html, 'window.__vyAdminFields = true'))->toBe(1);
});

it('every organisation list and the country list is searchable — and each still posts its native field', function () {
    config(['mail.default' => 'smtp']);   // the create form renders only while mail really goes out
    $s = seedScenario(1);
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['status' => 'suspended']));
    $admin = platformAdmin();
    $get = fn (string $name, array $params = []): string => (string) $this->actingAs($admin, 'admin')->get(route($name, $params))->assertOk()->getContent();

    $create = $get('admin.customers.create');
    expect(a05Select($create, 'name="country"', 'data-searchable', 'data-flags'))->toBeTrue()
        ->and(substr_count($create, 'type="radio" name="currency"'))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($create, 'type="radio" name="property_type"'))->toBeGreaterThanOrEqual(2)
        ->and($create)->toContain('type="checkbox" class="ad-switch" name="internal"');

    $show = $get('admin.customers.show', ['organization' => $s['org']->id]);
    expect(a05Select($show, 'name="country"', 'data-searchable', 'data-flags'))->toBeTrue()
        ->and(substr_count($show, 'type="radio" name="default_locale"'))->toBeGreaterThanOrEqual(2)
        // the name to type is shown exactly as stored — the label is uppercase and the check is exact
        ->and($show)->toContain('class="ad-confirm-name">'.e((string) $s['org']->name).'</strong>');

    expect(a05Select($get('admin.audit.index'), 'name="tenant"', 'data-searchable'))->toBeTrue()
        ->and(a05Select($get('admin.privacy.index'), 'name="organization_id"', 'data-searchable'))->toBeTrue()
        ->and(a05Select($get('admin.break-glass.index'), 'name="organization_id"', 'data-searchable'))->toBeTrue()
        // the checklist: still a native multi-select posting organization_ids[]
        ->and(a05Select($get('admin.announcements.index'), 'name="organization_ids[]"', 'multiple', 'data-searchable'))->toBeTrue();
});

it('⚠️ an announcement aimed at chosen organisations is never quietly widened to every organisation', function () {
    $admin = platformAdmin();
    $org = seedScenario(1)['org'];
    $base = ['title' => 'A05 aim', 'body' => 'Body.', 'level' => array_key_first(PlatformAnnouncement::LEVELS),
        'starts_at' => now()->addYear()->format('Y-m-d\TH:i'), 'reason' => 'ADMIN 05 audience test'];

    // "Every organisation" with organisations ticked is contradictory: refused, nothing published
    $this->actingAs($admin, 'admin')->post(route('admin.announcements.store'), $base + ['audience' => 'all', 'organization_ids' => [(string) $org->id]])
        ->assertSessionHasErrors('audience');
    expect(PlatformAnnouncement::query()->where('title', 'A05 aim')->exists())->toBeFalse();

    // after a failed submit the choices come back as they were — nothing resets to "every organisation"
    $this->actingAs($admin, 'admin')->post(route('admin.announcements.store'), ['title' => ''] + $base + ['audience' => 'chosen', 'organization_ids' => [(string) $org->id]])
        ->assertSessionHasErrors('title');
    $html = (string) $this->actingAs($admin, 'admin')->get(route('admin.announcements.index'))->assertOk()->getContent();
    expect($html)->toMatch('/<option value="chosen" selected>/')
        ->and($html)->toMatch('/<option value="'.preg_quote((string) $org->id, '/').'" selected>/');

    // and the notice says who it went to
    $this->actingAs($admin, 'admin')->post(route('admin.announcements.store'), $base + ['audience' => 'chosen', 'organization_ids' => [(string) $org->id]])
        ->assertSessionHasNoErrors()->assertSessionHas('admin_notice', fn (string $n): bool => str_contains($n, 'to 1 chosen organisation'));
});
