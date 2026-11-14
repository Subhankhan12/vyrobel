<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerUser;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Models\Unit;
use App\Models\User;
use App\Services\InviteNoLongerRedeemable;
use App\Services\OwnerAttributionService;
use App\Services\OwnerInviteService;
use App\Services\OwnerStatementService;
use App\Services\OwnerTeamInviteService;
use App\Services\PayoutRunService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerRows;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;

uses(DatabaseTransactions::class);

// ══ DEC-o (2026-11-12) — AN OPERATOR CAN SWITCH AN OWNER'S PORTAL ACCESS OFF AND ON ══════════════════════════════════════════════════
// The founder's rulings, each pinned below: the owner's TEAM goes with them and comes back with them; a person who ALSO works for the
// operator keeps their login; OFF asks for a typed reason (kept only in the audit row), ON asks for the operator's password; gated
// `users.manage` and nothing else; reversible and non-destructive (no login, team row or membership is ever written); the owner is still
// paid and statemented; nobody is emailed, and the page says so.
//
// ⚠️ THE STATE IS ONE DATE ON THE OWNER (`owners.portal_disabled_at`). Every guard below has its own test — the request gate, the owner
// scope (the second layer, reached by a Livewire update that runs no route middleware), the API, sign-in routing and the two public
// invite doors — and `tests/Mutations/sets/dec-o-portal-access.php` removes each one in turn.
// Each test's tag (dop01…) is what that set's filters name.

/** An owner with a login, a unit on a 15% agreement, and a co-owner on their team; the operator is a Property Owner (users.manage, password "password"). */
function dopWorld(int $units = 1): array
{
    $s = seedScenario($units);
    $admin = owpAdmin($s);
    $owner = ownUnit($s['units'][0]);
    $owner->forceFill(['name' => 'Amara Okafor'])->save();
    $login = ownerActor($owner, $s);
    $team = ownerSubUser($s, (string) $owner->id, OwnerTeamRole::CO_OWNER);

    return ['s' => $s, 'admin' => $admin, 'owner' => $owner->fresh(), 'login' => $login, 'team' => $team];
}

function dopOff(User $actor, Owner $owner, string $reason = 'Agreement on hold while the terms are renegotiated')
{
    return test()->actingAs($actor)->from(route('app.owners.show', $owner->id))
        ->post(route('app.owners.portal-access', $owner->id), ['_form' => 'portal', 'portal' => 'off', 'reason' => $reason]);
}

function dopOn(User $actor, Owner $owner, ?string $password = 'password')
{
    return test()->actingAs($actor)->from(route('app.owners.show', $owner->id))
        ->post(route('app.owners.portal-access', $owner->id), array_filter(['_form' => 'portal', 'portal' => 'on', 'current_password' => $password], fn ($v): bool => $v !== null));
}

/** Switch the portal off WITHOUT the route (a fixture: the state the route writes). */
function dopOffNow(Owner $owner): void
{
    Owner::withoutGlobalScopes()->whereKey($owner->id)->update(['portal_disabled_at' => now()]);
}

function dopOnNow(Owner $owner): void
{
    Owner::withoutGlobalScopes()->whereKey($owner->id)->update(['portal_disabled_at' => null]);
}

function dopIsOff(Owner $owner): bool
{
    return Owner::withoutGlobalScopes()->findOrFail($owner->id)->portal_disabled_at !== null;
}

/** A JSON column comes back with its keys in the database's order, not ours — compare by key. @param array<string, mixed> $row @return array<string, mixed> */
function dopSorted(array $row): array
{
    ksort($row);

    return $row;
}

function dopAudits(Owner $owner)
{
    return AuditEvent::query()->where('action', 'owner.portal_access_changed')->where('subject_id', (string) $owner->id)->orderBy('created_at')->get();
}

/** Money and people the switch must never touch — every row that belongs to the owner, their login and their team, as JSON. */
function dopSnapshot(array $w): string
{
    $ownerId = (string) $w['owner']->id;
    $userIds = [(string) $w['login']->id, (string) $w['team']->id];
    $rows = [];
    foreach (['owner_statements', 'payouts', 'owner_ledger', 'owner_users', 'management_agreements', 'payout_methods', 'owner_payout_preferences', 'units'] as $table) {
        $rows[$table] = DB::table($table)->where('owner_id', $ownerId)->orderBy('id')->get()->map(fn ($r): array => (array) $r)->all();
    }
    $rows['users'] = DB::table('users')->whereIn('id', $userIds)->orderBy('id')->get()->map(fn ($r): array => (array) $r)->all();
    $rows['memberships'] = DB::table('organization_user')->whereIn('user_id', $userIds)->orderBy('user_id')->get()->map(fn ($r): array => (array) $r)->all();
    $rows['roles'] = DB::table('model_has_roles')->whereIn('model_id', $userIds)->orderBy('model_id')->get()->map(fn ($r): array => (array) $r)->all();
    // the owner's own row differs ONLY in the switch (and the touch that comes with saving it)
    $rows['owner'] = collect((array) DB::table('owners')->where('id', $ownerId)->first())->except(['portal_disabled_at', 'updated_at'])->all();

    return (string) json_encode($rows);
}

/** A closed month, a waiting payout and a ledger line for the owner — written straight, a DISPLAY/DATA fixture. */
function dopMoney(array $w): void
{
    $statement = owpStatement($w['owner'], '2026-08', 123456);
    Payout::create(['organization_id' => $w['s']['org']->id, 'owner_id' => $w['owner']->id, 'owner_statement_id' => $statement->id,
        'amount_minor' => 123456, 'currency' => 'EUR', 'status' => 'pending']);
    OwnerLedgerEntry::factory()->create(['owner_id' => $w['owner']->id]);
}

// ── 1. THE GATE — users.manage and nothing else ────────────────────────────────────────────────────

it('dop01 gates the switch on users.manage — a Hotel Manager and a Front Desk are refused and the owner is unchanged', function () {
    $w = dopWorld();
    $hotelManager = operatorUser($w['s'], [], 'Hotel Manager');
    $frontDesk = operatorUser($w['s'], [], 'Front Desk');

    foreach ([$hotelManager, $frontDesk] as $actor) {
        dopOff($actor, $w['owner'])->assertForbidden();
        dopOn($actor, $w['owner'])->assertForbidden();
    }
    expect(dopIsOff($w['owner']))->toBeFalse()->and(dopAudits($w['owner']))->toHaveCount(0);

    // …and the permitted actor, beside them, does switch it
    dopOff($w['admin'], $w['owner'])->assertRedirect()->assertSessionHas('owners_notice');
    expect(dopIsOff($w['owner']))->toBeTrue();
});

it('dop02 gives a PERMITTED actor in another organisation a 404 — nothing is written, in either direction', function () {
    $w = dopWorld();
    $other = seedScenario(1);
    $otherAdmin = owpAdmin($other);   // a Property Owner of THEIR organisation: users.manage, so the 404 is the tenant line, not a masked 403

    dopOff($otherAdmin, $w['owner'])->assertNotFound();
    expect(dopIsOff($w['owner']))->toBeFalse();

    dopOffNow($w['owner']);
    dopOn($otherAdmin, $w['owner'])->assertNotFound();
    expect(dopIsOff($w['owner']))->toBeTrue()->and(dopAudits($w['owner']))->toHaveCount(0);
});

it('dop03 refuses the switch while the workspace is paused — nothing is written', function () {
    $w = dopWorld();
    pauseWorkspace($w['s']);

    dopOff($w['admin'], $w['owner'])->assertRedirect()->assertSessionHas('owners_error');
    expect(dopIsOff($w['owner']))->toBeFalse()->and(dopAudits($w['owner']))->toHaveCount(0);
});

// ── 2. OFF — a typed reason, kept only in the audit row ────────────────────────────────────────────

it('dop04 needs a typed reason of at least 5 characters to switch off — and keeps it in the audit row only, never the owner’s details (#28)', function () {
    $w = dopWorld();

    foreach (['', '   ', 'abc', '  ab  '] as $short) {
        dopOff($w['admin'], $w['owner'], $short)->assertSessionHasErrors('reason');
    }
    dopOff($w['admin'], $w['owner'], str_repeat('x', 301))->assertSessionHasErrors('reason');
    expect(dopIsOff($w['owner']))->toBeFalse()->and(dopAudits($w['owner']))->toHaveCount(0);

    dopOff($w['admin'], $w['owner'], '  Contract on hold  ')->assertRedirect()->assertSessionHasNoErrors();
    expect(dopIsOff($w['owner']))->toBeTrue();

    $audit = dopAudits($w['owner']);
    expect($audit)->toHaveCount(1);
    $row = $audit->first();
    expect(dopSorted($row->after))->toBe(['portal' => 'off', 'reason' => 'Contract on hold', 'team' => 1])   // trimmed, typed, the team's size
        ->and($row->before)->toBe(['portal' => 'on']);

    // #28 — the row says WHICH owner and WHAT happened; the owner's and their team's contact details are never copied into it
    $encoded = json_encode($row->toArray());
    foreach ([(string) $w['owner']->email, (string) $w['owner']->phone, (string) $w['login']->email, (string) $w['team']->email] as $secret) {
        expect($secret)->not->toBe('')->and(str_contains((string) $encoded, $secret))->toBeFalse();
    }
    // the reason lives nowhere else — not on the owner row
    expect(str_contains((string) json_encode(DB::table('owners')->where('id', $w['owner']->id)->first()), 'Contract on hold'))->toBeFalse();
});

it('dop05 refuses a switch-off for an owner whose login is not active — there is nothing to switch off', function () {
    $w = dopWorld();
    $w['login']->forceFill(['status' => 'invited'])->save();

    dopOff($w['admin'], $w['owner'])->assertRedirect()->assertSessionHas('owners_error');
    expect(dopIsOff($w['owner']))->toBeFalse()->and(dopAudits($w['owner']))->toHaveCount(0);

    // the page does not offer it either
    $page = (string) $this->actingAs($w['admin'])->get(route('app.owners.show', $w['owner']->id))->assertOk()->getContent();
    expect($page)->not->toContain('Switch off portal access');
});

it('dop06 treats a second switch-off as nothing — one audit row, no second write', function () {
    $w = dopWorld();
    dopOff($w['admin'], $w['owner'])->assertRedirect();
    $since = Owner::withoutGlobalScopes()->findOrFail($w['owner']->id)->portal_disabled_at;

    dopOff($w['admin'], $w['owner'], 'Another reason entirely')->assertRedirect()->assertSessionHas('owners_notice');
    expect(dopAudits($w['owner']))->toHaveCount(1)
        ->and(Owner::withoutGlobalScopes()->findOrFail($w['owner']->id)->portal_disabled_at?->equalTo($since))->toBeTrue();

    // a hand-made request with neither choice is refused
    $this->actingAs($w['admin'])->post(route('app.owners.portal-access', $w['owner']->id), ['portal' => 'maybe'])->assertSessionHasErrors('portal');
});

// ── 3. ON — the operator's password ────────────────────────────────────────────────────────────────

it('dop07 asks for the operator’s password to switch back on — a wrong or missing one changes nothing', function () {
    $w = dopWorld();
    dopOffNow($w['owner']);

    dopOn($w['admin'], $w['owner'], 'not-my-password')->assertSessionHasErrors('current_password');
    dopOn($w['admin'], $w['owner'], null)->assertSessionHasErrors('current_password');
    expect(dopIsOff($w['owner']))->toBeTrue()->and(dopAudits($w['owner']))->toHaveCount(0);

    dopOn($w['admin'], $w['owner'])->assertRedirect()->assertSessionHasNoErrors();
    expect(dopIsOff($w['owner']))->toBeFalse();
    expect(dopSorted(dopAudits($w['owner'])->first()->after))->toBe(['portal' => 'on', 'team' => 1]);
});

// ── 4. WHAT OFF DOES — and who it does not touch ───────────────────────────────────────────────────

it('dop08 refuses the owner’s web portal with plain words once it is off — nothing of the portal behind it', function () {
    $w = dopWorld();
    // ⚠️ A LEAK-PROOF NEEDLE, NOT `U1` (found 2026-11-14: this test failed one MariaDB run on a page that leaked NOTHING). `seedScenario` codes the first
    // unit `U1`, and the refusal page legitimately prints the org's RANDOM name ("Org XU1lNd" that run) and a random CSRF token — either can contain
    // "U1" by chance, so `assertDontSee('U1')` tested the needle, not the behaviour. This repo's own remedy (OperatorPortfolioTest,
    // OperatorReservationsListTest, BackOfficeAccessTest): a code that could ONLY appear by leaking — and the positive first, so the needle is shown to be
    // capable of appearing at all.
    $w['s']['units'][0]->update(['code' => 'OWNERUNIT9']);
    $this->actingAs($w['login'])->get('/owner/units')->assertOk()->assertSee('OWNERUNIT9');

    dopOff($w['admin'], $w['owner'])->assertRedirect();

    $this->actingAs($w['login'])->get(route('owner.dashboard'))
        ->assertForbidden()
        ->assertSee('Portal access is switched off')
        ->assertSee($w['s']['org']->name)
        ->assertSee('Nothing has been deleted');
    $this->actingAs($w['login'])->get('/owner/units')->assertForbidden()->assertDontSee('OWNERUNIT9');
    $this->actingAs($w['login'])->getJson('/owner')->assertForbidden()->assertJsonPath('message', 'Portal access for this owner account is switched off.');
});

it('dop09 takes the owner’s team with them — a co-owner is refused too, and is back when it is switched on', function () {
    $w = dopWorld();
    $this->actingAs($w['team'])->get('/owner/units')->assertOk();   // before: in

    dopOff($w['admin'], $w['owner'])->assertRedirect();
    $this->actingAs($w['team'])->get('/owner')->assertForbidden()->assertSee('Portal access is switched off');
    $this->actingAs($w['team'])->get('/owner/units')->assertForbidden();

    dopOn($w['admin'], $w['owner'])->assertRedirect();
    $this->actingAs($w['team'])->get('/owner/units')->assertOk();
});

it('dop10 gives the owner’s API token, and their team’s, a plain 403 while it is off — and the same token works again when it is on', function () {
    $w = dopWorld();
    foreach (['login', 'team'] as $who) {
        Passport::actingAs($w[$who], ['read:bookings'], 'api');
        $this->getJson('/api/owner/bookings')->assertOk();
    }

    dopOffNow($w['owner']);
    foreach (['login', 'team'] as $who) {
        Passport::actingAs($w[$who], ['read:bookings'], 'api');
        $this->getJson('/api/owner/bookings')->assertForbidden()
            ->assertJsonPath('message', __('php/http/middleware/resolve_api_owner_context.portal_off'));
    }

    dopOnNow($w['owner']);
    foreach (['login', 'team'] as $who) {
        Passport::actingAs($w[$who], ['read:bookings'], 'api');
        $this->getJson('/api/owner/bookings')->assertOk();
    }
});

it('dop11 lets a person who also works for the operator keep their login and their back office — only the owner portal closes', function () {
    $w = dopWorld();
    $staff = operatorUser($w['s'], ['name' => 'Sam Staff'], 'Hotel Manager');
    Owner::withoutGlobalScopes()->whereKey($w['owner']->id)->update(['user_id' => $staff->id]);   // the owner IS someone who works here
    $before = dopSnapshot(['owner' => $w['owner'], 'login' => $staff, 'team' => $w['team']]);

    dopOff($w['admin'], $w['owner'])->assertRedirect()->assertSessionHas('owners_notice');
    expect($staff->fresh()->status)->toBe('active')
        ->and(dopSnapshot(['owner' => $w['owner'], 'login' => $staff, 'team' => $w['team']]))->toBe($before);   // not one row of theirs moved

    // …they still sign in — to the back office, not the owner portal…
    auth()->guard('web')->logout();
    $this->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('app.dashboard'));
    // …and /app still opens, while /owner sends them home to it
    $this->actingAs($staff)->get(route('app.dashboard'))->assertOk();
    $this->actingAs($staff)->get('/owner')->assertRedirect(route('app.dashboard'));
    $this->actingAs($staff)->get('/owner/units')->assertRedirect(route('app.dashboard'));

    // switched on: their owner portal is back, and sign-in lands there again
    dopOn($w['admin'], $w['owner'])->assertRedirect();
    $this->actingAs($staff)->get('/owner/units')->assertOk();
    auth()->guard('web')->logout();
    $this->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('owner.dashboard'));
});

it('dop12 sends an owner with no back office to /app, which sends them on to the plain page — never into the portal', function () {
    $w = dopWorld();
    dopOffNow($w['owner']);

    $this->actingAs($w['login'])->get(route('app.dashboard'))->assertRedirect(route('owner.dashboard'));
    $this->actingAs($w['login'])->get(route('owner.dashboard'))->assertForbidden()->assertSee('Portal access is switched off');
});

// ── 5. NON-DESTRUCTIVE — and the owner is still paid ───────────────────────────────────────────────

it('dop13 leaves every row of the owner’s money, login, team and membership byte-identical across off and on', function () {
    $w = dopWorld();
    dopMoney($w);
    $before = dopSnapshot($w);

    dopOff($w['admin'], $w['owner'])->assertRedirect();
    expect(dopSnapshot($w))->toBe($before);   // while off: nothing but the switch moved

    dopOn($w['admin'], $w['owner'])->assertRedirect();
    expect(dopSnapshot($w))->toBe($before);   // and back on: still nothing
    expect($w['login']->fresh()->status)->toBe('active')->and($w['team']->fresh()->status)->toBe('active')
        ->and(OwnerUser::query()->where('owner_id', $w['owner']->id)->value('status'))->toBe('active');
});

it('dop14 still statements and pays an owner whose portal is off — the payout run does not read the switch', function () {
    $w = dopWorld();
    $s = $w['s'];
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);
    $statements = app(OwnerStatementService::class);

    dopOff($w['admin'], $w['owner'])->assertRedirect();
    $statements->finalize($statements->generateDraft($w['owner']->id, '2026-09-01', '2026-09-30'));   // a period closes while it is off
    app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL);                                      // and the run pays it

    $payout = Payout::query()->where('owner_id', $w['owner']->id)->firstOrFail();
    expect($payout->amount_minor)->toBe(17000)->and($payout->status)->toBe('paid')->and(ownerNet((string) $w['owner']->id))->toBe(17000)
        ->and(dopIsOff($w['owner']))->toBeTrue();
});

it('dop15 restores everything on switch-on — the owner, their team, their token and their page', function () {
    $w = dopWorld();
    dopOff($w['admin'], $w['owner'])->assertRedirect();
    dopOn($w['admin'], $w['owner'])->assertRedirect();

    $this->actingAs($w['login'])->get('/owner/units')->assertOk()->assertSee('U1');
    $this->actingAs($w['team'])->get('/owner/units')->assertOk();
    Passport::actingAs($w['login'], ['read:bookings'], 'api');
    $this->getJson('/api/owner/bookings')->assertOk();
    expect(dopAudits($w['owner']))->toHaveCount(2);
});

// ── 6. THE OWNER SCOPE — the second layer (a Livewire update runs no route middleware) ──────────────

it('dop16 makes the owner scope resolve to NOTHING for an off owner — never to every owner — while they stay "linked"', function () {
    $s = seedScenario(2);
    $a = ownUnit($s['units'][0]);
    $loginA = ownerActor($a, $s);
    $b = ownUnit($s['units'][1]);   // another owner of the same organisation, whose unit must stay out of sight
    $context = app(OwnerContext::class);

    // on: sees exactly their own unit
    $this->actingAs($loginA);
    $context->deactivate();
    expect(Unit::query()->count())->toBe(1);

    dopOffNow($a);
    $context->deactivate();   // a fresh request: the scope resolves itself, no middleware ran
    // ⚠️ the owner is STILL linked — if this answered false the scope would fall OPEN and the next count would be 2
    expect($context->actorIsOwnerLinked())->toBeTrue()
        ->and(Unit::query()->count())->toBe(0);

    $context->resolveForActor($loginA);
    expect($context->ownerIds())->toBe([])->and($context->isPrimaryOwner())->toBeFalse();
    expect((string) $b->id)->not->toBe('');   // (b exists and was never reachable)
});

it('dop17 makes the scope resolve to nothing for an off owner’s team member too', function () {
    $s = seedScenario(2);
    $a = ownUnit($s['units'][0]);
    ownUnit($s['units'][1]);
    $member = ownerSubUser($s, (string) $a->id, OwnerTeamRole::VIEW_ONLY);
    $context = app(OwnerContext::class);

    $this->actingAs($member);
    $context->deactivate();
    expect(Unit::query()->count())->toBe(1);

    dopOffNow($a);
    $context->deactivate();
    expect($context->actorIsOwnerLinked())->toBeTrue()->and(Unit::query()->count())->toBe(0);

    dopOnNow($a);
    $context->deactivate();
    expect(Unit::query()->count())->toBe(1);
});

// ── 7. THE TWO PUBLIC DOORS BACK IN ────────────────────────────────────────────────────────────────

it('dop18 refuses an owner-team invite while the owner is off — the link, the submit and the service itself — and writes nothing', function () {
    Mail::fake();
    $w = dopWorld();
    $made = app(OwnerTeamInviteService::class)->invite((string) $w['owner']->id, 'Nia New', 'nia.new@owner.test', OwnerTeamRole::VIEW_ONLY);
    $token = $made['token'];
    $newcomer = User::query()->where('email', 'nia.new@owner.test')->firstOrFail();

    $this->get('/team-invite/'.$token)->assertOk()->assertSee('Set your password');   // on: a valid link

    dopOffNow($w['owner']);
    $this->get('/team-invite/'.$token)->assertOk()->assertSee('This invitation link isn’t valid')->assertDontSee('Set your password');
    $this->post('/team-invite/'.$token, ['password' => 'Sup3r-secret-pw!', 'password_confirmation' => 'Sup3r-secret-pw!'])
        ->assertOk()->assertSee('This invitation link isn’t valid');
    expect(fn () => app(OwnerTeamInviteService::class)->accept($made['invite'], 'Sup3r-secret-pw!'))->toThrow(InviteNoLongerRedeemable::class);

    expect($newcomer->fresh()->status)->toBe('invited')
        ->and(OwnerUser::query()->where('user_id', $newcomer->id)->exists())->toBeFalse()
        ->and($made['invite']->fresh()->status)->toBe('pending');   // the invite itself is left as it was

    // switched back on, the same link works
    dopOnNow($w['owner']);
    $this->get('/team-invite/'.$token)->assertOk()->assertSee('Set your password');
});

it('dop19 refuses an owner invite to an off owner — the link, the submit and the service itself — and writes nothing', function () {
    Mail::fake();
    $s = seedScenario(1);
    $made = app(OwnerInviteService::class)->invite('Rui Monteiro', 'rui.monteiro@owner.test', [], 18.0, 'owner');
    $token = $made['token'];
    $login = User::query()->where('email', 'rui.monteiro@owner.test')->firstOrFail();
    $owner = Owner::query()->where('user_id', $login->id)->firstOrFail();

    dopOffNow($owner);
    $this->get('/invite/'.$token)->assertOk()->assertSee('This invitation link isn’t valid');
    $this->post('/invite/'.$token, ['password' => 'Sup3r-secret-pw!', 'password_confirmation' => 'Sup3r-secret-pw!'])->assertOk()->assertSee('This invitation link isn’t valid');
    expect(fn () => app(OwnerInviteService::class)->accept($made['invite'], 'Sup3r-secret-pw!'))->toThrow(InviteNoLongerRedeemable::class);

    expect($login->fresh()->status)->toBe('invited')->and($made['invite']->fresh()->status)->toBe('pending')
        ->and((string) $s['org']->id)->not->toBe('');

    dopOnNow($owner);
    $this->get('/invite/'.$token)->assertOk()->assertSee('Set your password');
});

it('dop20 refuses a fresh invite to the address of an off owner — in words, nothing written — and accepts it once they are on', function () {
    Mail::fake();
    $w = dopWorld();
    $w['login']->forceFill(['status' => 'disabled'])->save();   // e.g. they paused their own account — an invite would otherwise re-open it
    dopOffNow($w['owner']);
    $invite = ['current_password' => 'password', 'name' => 'Amara Okafor', 'email' => $w['login']->email, 'role' => 'owner'];

    $this->actingAs($w['admin'])->post(route('app.owners.invite'), $invite)->assertRedirect()
        ->assertSessionHas('owners_error', 'Their portal access is switched off, so no invite can be sent. Switch it back on from their page first.');
    expect(OwnerInvite::query()->where('email', $w['login']->email)->count())->toBe(0);

    dopOnNow($w['owner']);
    $this->actingAs($w['admin'])->post(route('app.owners.invite'), $invite)->assertRedirect()->assertSessionMissing('owners_error');
    expect(OwnerInvite::query()->where('email', $w['login']->email)->count())->toBe(1);
});

// ── 8. WHERE THE OPERATOR SEES IT ──────────────────────────────────────────────────────────────────

it('dop21 shows the off state on the Owners list and keeps the owner’s pay status exactly as it was', function () {
    $w = dopWorld();
    dopMoney($w);
    $row = fn (): array => collect(app(OwnerRows::class)->load())->firstWhere('id', (string) $w['owner']->id);
    $before = $row();
    expect($before['portal_off'])->toBeFalse();
    $this->actingAs($w['admin'])->get(route('app.owners'))->assertOk()->assertDontSee('Portal off');

    dopOffNow($w['owner']);
    $after = $row();
    expect($after['portal_off'])->toBeTrue()
        ->and([$after['pay_key'], $after['pay_label'], $after['net'], $after['reason']])->toBe([$before['pay_key'], $before['pay_label'], $before['net'], $before['reason']]);
    $this->actingAs($w['admin'])->get(route('app.owners'))->assertOk()->assertSee('Portal off');
});

it('dop22 disables the owner in the Inbox compose list with its own reason, and refuses to start a conversation — nothing created', function () {
    $w = dopWorld();
    $page = fn (): string => (string) $this->actingAs($w['admin'])->get(route('app.inbox'))->assertOk()->getContent();
    expect($page())->not->toContain('Portal access switched off');

    dopOffNow($w['owner']);
    expect($page())->toContain('Portal access switched off');

    $this->actingAs($w['admin'])->post(route('app.inbox.start'), ['owner_id' => $w['owner']->id, 'body' => 'Hello'])->assertRedirect()
        ->assertSessionHas('inbox_error', 'Amara Okafor’s portal access is switched off — switch it back on from their page before you message them.')
        ->assertSessionMissing('inbox_notice');
    expect(MessageThread::query()->where('owner_id', $w['owner']->id)->count())->toBe(0)->and(Message::query()->count())->toBe(0);

    dopOnNow($w['owner']);
    $this->actingAs($w['admin'])->post(route('app.inbox.start'), ['owner_id' => $w['owner']->id, 'body' => 'Hello'])->assertSessionHas('inbox_notice');
});

it('dop23 shows the owner’s page its off state, the switch for the role that may use it, and says plainly that nobody is emailed', function () {
    $w = dopWorld();
    $page = fn (User $actor): string => (string) $this->actingAs($actor)->get(route('app.owners.show', $w['owner']->id))->assertOk()->getContent();
    $hotelManager = operatorUser($w['s'], [], 'Hotel Manager');

    // on: the switch is offered (with the team's size and the email sentence), the Hotel Manager is not offered it
    $on = $page($w['admin']);
    expect($on)->toContain('Switch off portal access')->toContain(route('app.owners.portal-access', $w['owner']->id))
        ->toContain('and the 1 person on their team can’t sign in')
        ->toContain('Anyone who also works for you keeps their login and the back office')
        ->toContain('Email isn’t being sent from Vyrobel yet, so they won’t be told by email.')
        ->toContain('Portal access active')->toContain('Amara Okafor can see the statement in their owner portal')
        ->not->toContain('They aren’t emailed about this change');
    expect($page($hotelManager))->not->toContain('Switch off portal access')->not->toContain(route('app.owners.portal-access', $w['owner']->id));

    // off: the state, the switch back on, and no copy that claims they can see anything in the portal
    dopOff($w['admin'], $w['owner'])->assertRedirect();
    $off = $page($w['admin']);
    expect($off)->toContain('Portal access off')->toContain('Switched off on '.now()->format('j M Y'))
        ->toContain('Switch portal access back on')->toContain('Switch portal access back on for Amara Okafor?')
        ->toContain('Switching access back on lets them in again, so it asks for your password.')
        ->toContain('Amara Okafor’s portal access is switched off, so they can’t see the statement there until you switch it back on')
        ->toContain('Amara Okafor’s portal access is switched off, so they can’t answer until you switch it back on.')
        ->not->toContain('can see the statement in their owner portal')->not->toContain('approves, counters or declines it in their portal')
        ->not->toContain('Portal access active');
    assertNoRawBlade($this->actingAs($w['admin'])->get(route('app.owners.show', $w['owner']->id)));

    // the sentence follows the mailer: while email IS sent it says nobody is emailed about this change, still
    emailIsBeingSent();
    expect($page($w['admin']))->toContain('They aren’t emailed about this change')->not->toContain('Email isn’t being sent from Vyrobel yet, so they won’t be told by email.');
});

it('dop24 says what did NOT happen in the notice — nobody was emailed — in both mail states, and counts the team', function () {
    $w = dopWorld();
    dopOff($w['admin'], $w['owner'])->assertRedirect()->assertSessionHas('owners_notice',
        'Portal access is switched off for Amara Okafor and the 1 person on their team. Their records, statements and payouts are unchanged. Email isn’t being sent from Vyrobel yet, so nobody was told by email.');

    emailIsBeingSent();
    dopOn($w['admin'], $w['owner'])->assertRedirect()->assertSessionHas('owners_notice',
        'Portal access is back on for Amara Okafor and the 1 person on their team. Nobody is emailed about this change — tell them yourself if they need to know.');
});
