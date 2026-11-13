<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\PlatformAdminAction;
use App\Support\Platform\PlatformCustomers;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

/**
 * DEC-5 (his ruling, 2026-11-13: "B now, C later") — the console SURFACES abandoned signups; it never removes one.
 *
 * Abandoned = created more than 30 days ago AND nobody confirmed an email address AND nobody ever signed in. Each of the
 * three is a separate way to be "not abandoned", so each has its own case below. Deleting stays the console's own
 * guarded action (suspend first, his password, the organisation's name typed) — this bucket is the list that leads there.
 */
function abandonedWorld(int $ageDays, bool $verified = false, bool $signedIn = false): Organization
{
    $s = seedScenario(1);
    $s['org']->forceFill(['created_at' => now()->subDays($ageDays)])->save();
    operatorUser($s);
    DB::table('users')->whereIn('id', DB::table('organization_user')->where('organization_id', $s['org']->id)->pluck('user_id'))->update([
        'email_verified_at' => $verified ? now()->subDays(2) : null,
        'last_login_at' => $signedIn ? now()->subDay() : null,
    ]);

    return $s['org'];
}

function inAbandonedBucket(Organization $org): bool
{
    $rows = app(TenantContext::class)->runAsSuperAdmin(
        fn () => app(PlatformCustomers::class)->list(['bucket' => 'abandoned', 'search' => $org->name])->items(),
    );

    return collect($rows)->pluck('id')->map(fn ($v) => (string) $v)->contains((string) $org->id);
}

it('lists an unverified, never-used signup once it is more than 30 days old', function () {
    expect(inAbandonedBucket(abandonedWorld(31)))->toBeTrue();
});

it('does not list a signup that is still inside the 30 days', function () {
    expect(inAbandonedBucket(abandonedWorld(29)))->toBeFalse();
});

it('does not list a signup where somebody confirmed an email address', function () {
    expect(inAbandonedBucket(abandonedWorld(45, verified: true)))->toBeFalse();
});

it('does not list a signup where somebody ever signed in', function () {
    expect(inAbandonedBucket(abandonedWorld(45, signedIn: true)))->toBeFalse();
});

it('the triage page counts the bucket and says in words that nothing is removed automatically', function () {
    abandonedWorld(40);

    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.triage', ['internal' => 'include']))->assertOk()->getContent();

    expect($html)->toContain('Abandoned signups (30+ days)')->toContain('Nothing is ever removed automatically');
});

it('only SURFACES: opening the bucket suspends, archives and deletes nothing', function () {
    $org = abandonedWorld(60);
    $before = [Organization::withTrashed()->count(), $org->fresh()->status, PlatformAdminAction::query()->count()];

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.triage', ['internal' => 'include']))->assertOk();
    $this->flushSession();
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.index', ['bucket' => 'abandoned', 'search' => $org->name]))->assertOk()->assertSee($org->slug);

    $now = [Organization::withTrashed()->count(), $org->fresh()->status, PlatformAdminAction::query()->where('action', 'not like', 'platform.customers.%')->count()];
    expect($now[0])->toBe($before[0])->and($now[1])->toBe($before[1]);
    expect(Organization::withTrashed()->whereKey($org->id)->exists())->toBeTrue();
});
