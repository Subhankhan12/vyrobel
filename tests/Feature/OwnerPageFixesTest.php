<?php

declare(strict_types=1);

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE OWNER'S PAGE — WHAT ITS WRITES DO (2026-10-03, the founder: "modal design and buttons are mess … confirm with your
// password and its box is not well aligned … all the sort of work you have done in the previous pages … in this module …
// make sure everything works very well"). Each test below failed first against the code as it stood; how the page looks is
// OwnerPageRedesignTest. ⚠️ Every date is taken from the clock (`VY_TEST_NOW` can move it): a change saved now starts on the first
// of next month — `opgNext()` — for an owner with an agreement in effect, and today for one without.

/** The first of the month `$months` from now. */
function opgNext(int $months = 1): CarbonImmutable
{
    return CarbonImmutable::today()->startOfMonth()->addMonths($months);
}

/** Inês Carvalho, owner of unit 0, on an 18% portfolio-wide agreement since a year ago. */
function opgOwner(array $s, float $pct = 18.0): Owner
{
    $owner = owpOwner($s, 'Inês Carvalho');
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => $pct,
        'payout_schedule' => 'monthly', 'effective_from' => CarbonImmutable::today()->subYear()->toDateString(), 'effective_to' => null]);

    return $owner;
}

function opgCommission(User $actor, Owner $owner, string $pct)
{
    return test()->actingAs($actor)->from(route('app.owners.show', $owner->id))
        ->post(route('app.owners.update', $owner->id), ['_form' => 'commission', 'commission_pct' => $pct]);
}

/** @return Collection<int, ManagementAgreement> the portfolio-wide agreements that would govern a day */
function opgGoverning(Owner $owner, CarbonImmutable|string $date)
{
    return ManagementAgreement::query()->where('owner_id', $owner->id)->whereNull('unit_id')->effectiveOn($date)->get();
}

function opgRate(Owner $owner, CarbonImmutable|string $date): ?float
{
    $a = ManagementAgreement::resolveFor((string) $owner->id, null, $date);

    return $a === null ? null : (float) $a->commission_pct;
}

function opgPage(User $actor, Owner $owner): string
{
    return (string) test()->actingAs($actor)->get(route('app.owners.show', $owner->id))->assertOk()->getContent();
}

// ── The commission — the latest change is the one that applies ──────────────────────────────────

it('⚠️⚠️ lets the latest commission change win — two changes in one month never leave two agreements on one day', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    opgCommission($admin, $owner, '20')->assertRedirect()
        ->assertSessionHas('owners_notice', 'Inês Carvalho’s commission is 20% from '.opgNext()->format('j M Y')
            .' — for bookings worked out from now on. Bookings already worked out keep their rate.');
    opgCommission($admin, $owner, '22')->assertRedirect();

    // 🔬 before: 20% AND 22% both started on the 1st, open-ended — and the payout maths takes whichever the database returns first
    expect(opgGoverning($owner, opgNext()))->toHaveCount(1)
        ->and(opgRate($owner, opgNext()))->toBe(22.0)
        // today keeps the rate it had
        ->and(opgRate($owner, CarbonImmutable::today()))->toBe(18.0);
});

it('⚠️⚠️ undoes a waiting change when the commission is set back to today’s rate', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    opgCommission($admin, $owner, '20')->assertRedirect();
    opgCommission($admin, $owner, '18')->assertRedirect()
        ->assertSessionHas('owners_notice', 'Inês Carvalho stays on 18% — the change waiting for '.opgNext()->format('j M Y').' won’t happen.');

    // 🔬 before: "18" matched today's agreement, so nothing was written — and the 20% waiting for the 1st still came into force
    expect(opgGoverning($owner, opgNext()))->toHaveCount(1)
        ->and(opgRate($owner, opgNext()))->toBe(18.0)
        // the replaced change is ENDED, not left active with its dates running backwards, and the page says so in words
        ->and(ManagementAgreement::query()->where('owner_id', $owner->id)->where('commission_pct', 20.0)->value('status'))->toBe('ended');

    $html = opgPage($admin, $owner);
    expect($html)->toContain('Replaced before it started')->toContain('Never applied');
});

it('⚠️⚠️ collapses agreements an older write left on the same day into one — whichever rate is saved', function () {
    // what the old write left behind: 20% and then 22% both from the 1st, open-ended, the 22% made later. 🔬 The review found
    // the first version of the rule ended only the newest, so the 20% stayed active beside the new rate.
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);
    ManagementAgreement::query()->where('owner_id', $owner->id)->update(['effective_to' => opgNext()->subDay()->toDateString()]);
    $older = ManagementAgreement::factory()->withManagementFee(1000)->create(['owner_id' => $owner->id, 'unit_id' => null,
        'commission_pct' => 20.0, 'effective_from' => opgNext()->toDateString(), 'effective_to' => null]);
    $newer = ManagementAgreement::factory()->withManagementFee(3000)->create(['owner_id' => $owner->id, 'unit_id' => null,
        'commission_pct' => 22.0, 'effective_from' => opgNext()->toDateString(), 'effective_to' => null]);
    ManagementAgreement::query()->whereKey($older->id)->update(['created_at' => CarbonImmutable::today()->subDays(2)]);
    ManagementAgreement::query()->whereKey($newer->id)->update(['created_at' => CarbonImmutable::today()->subDay()]);

    // the rate the newest already says: still one agreement afterwards, not two
    opgCommission($admin, $owner, '22')->assertRedirect();
    expect(opgGoverning($owner, opgNext()))->toHaveCount(1)
        ->and(opgRate($owner, opgNext()))->toBe(22.0);

    // and another rate: one agreement, that rate — carrying the NEWEST agreement's other terms (its €30 fee, not the €10)
    opgCommission($admin, $owner, '25')->assertRedirect();
    expect(opgGoverning($owner, opgNext()))->toHaveCount(1)
        ->and(opgRate($owner, opgNext()))->toBe(25.0)
        ->and((int) ManagementAgreement::resolveFor((string) $owner->id, null, opgNext())->management_fee_minor)->toBe(3000);
});

it('writes nothing when the rate set is the one already coming', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    opgCommission($admin, $owner, '20')->assertRedirect();
    $before = ManagementAgreement::query()->where('owner_id', $owner->id)->count();

    opgCommission($admin, $owner, '20')->assertRedirect();

    // 🔬 before: a second, identical 20% agreement for the 1st
    expect(ManagementAgreement::query()->where('owner_id', $owner->id)->count())->toBe($before)
        ->and(opgGoverning($owner, opgNext()))->toHaveCount(1);
});

it('⚠️⚠️ starts today for an owner with no agreement in effect — never a gap before the first of the month', function () {
    // 🔬 the review: an owner with no agreement (an invited owner, say) got one from the 1st only, so stays until then were
    //    worked out with no agreement — a zero for the owner, kept for good.
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = owpOwner($s, 'Inês Carvalho');
    $s['units'][0]->update(['owner_id' => $owner->id]);

    // the dialog says so before the save — and that it is a NEW agreement, not "every other term stays"
    expect(opgPage($admin, $owner))->toContain('It starts today — Inês Carvalho has no agreement in effect.')
        ->toContain('It is a new agreement, paid monthly in EUR.')
        ->not->toContain('Every other term of the agreement stays as it is.');

    opgCommission($admin, $owner, '20')->assertRedirect()
        ->assertSessionHas('owners_notice', 'Inês Carvalho’s commission is 20% from today — they had no agreement in effect.');

    expect(opgRate($owner, CarbonImmutable::today()))->toBe(20.0)
        ->and(opgRate($owner, opgNext()))->toBe(20.0);
});

it('⚠️⚠️ keeps every other term when only the commission changes — the management fee went missing from the new agreement', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = owpOwner($s, 'Inês Carvalho');
    ManagementAgreement::factory()->withManagementFee(5000, 'EUR', 'monthly')->create(['owner_id' => $owner->id, 'unit_id' => null,
        'commission_pct' => 18.0, 'payout_schedule' => 'quarterly', 'effective_from' => CarbonImmutable::today()->subYear()->toDateString(),
        'effective_to' => null, 'term_months' => 12, 'notice_days' => 60, 'auto_renews' => true, 'attribution_rules' => ['cleaning_fee' => 'operator']]);

    opgCommission($admin, $owner, '20')->assertRedirect();

    $new = ManagementAgreement::resolveFor((string) $owner->id, null, opgNext());
    // 🔬 before: only the payout schedule and currency were copied — the €50 monthly fee, the term and the rules were dropped
    expect((float) $new->commission_pct)->toBe(20.0)
        ->and((int) $new->management_fee_minor)->toBe(5000)
        ->and($new->management_fee_interval)->toBe('monthly')
        ->and($new->payout_schedule)->toBe('quarterly')
        ->and((int) $new->term_months)->toBe(12)
        ->and((int) $new->notice_days)->toBe(60)
        ->and($new->attribution_rules)->toBe(['cleaning_fee' => 'operator']);
});

it('⚠️ keeps a fixed end date — a change never runs past the term the agreement had', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);
    $termEnds = opgNext(5)->subDay();
    ManagementAgreement::query()->where('owner_id', $owner->id)->update(['effective_to' => $termEnds->toDateString()]);

    opgCommission($admin, $owner, '20')->assertRedirect();

    // 🔬 the review: the new agreement was open-ended, so a change quietly extended a term that had an end
    expect(opgRate($owner, $termEnds))->toBe(20.0)
        ->and(opgRate($owner, $termEnds->addDay()))->toBeNull();
});

it('⚠️ fits a change in before one already planned for later, never on top of it', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);
    // an amendment the owner already agreed: 15% four months on — today's agreement ends the day before it
    ManagementAgreement::query()->where('owner_id', $owner->id)->update(['effective_to' => opgNext(4)->subDay()->toDateString()]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.0,
        'effective_from' => opgNext(4)->toDateString(), 'effective_to' => null]);

    opgCommission($admin, $owner, '20')->assertRedirect();

    expect(opgRate($owner, opgNext()))->toBe(20.0)
        ->and(opgRate($owner, opgNext(4)))->toBe(15.0)
        ->and(opgGoverning($owner, opgNext(4)))->toHaveCount(1)
        ->and(opgGoverning($owner, opgNext()->subDay()))->toHaveCount(1);
});

it('⚠️ fits a change in before a planned one even over a row an older write left open-ended', function () {
    // the old write's shape: an open-ended change from the 1st running over an amendment planned four months on. Only the
    // planned change can bound the new one here — the row it replaces has no end of its own (OM-M5).
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);
    ManagementAgreement::query()->where('owner_id', $owner->id)->update(['effective_to' => opgNext()->subDay()->toDateString()]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 20.0,
        'effective_from' => opgNext()->toDateString(), 'effective_to' => null]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.0,
        'effective_from' => opgNext(4)->toDateString(), 'effective_to' => null]);

    opgCommission($admin, $owner, '22')->assertRedirect();

    expect(opgRate($owner, opgNext()))->toBe(22.0)
        ->and(opgGoverning($owner, opgNext(4)->subDay()))->toHaveCount(1)
        ->and(opgRate($owner, opgNext(4)->subDay()))->toBe(22.0);
    // the 15% planned for later still governs its own start alone — the new 22% stops the day before it
    expect(ManagementAgreement::query()->where('owner_id', $owner->id)->where('commission_pct', 22.0)->value('effective_to')?->toDateString())
        ->toBe(opgNext(4)->subDay()->toDateString());
});

it('says on the page what the commission is now, and the change waiting to start', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    opgCommission($admin, $owner, '20')->assertRedirect();

    expect(opgPage($admin, $owner))->toContain('Changes to 20% on '.opgNext()->format('j M Y'));
});

// ── Portal access — Edit owner no longer switches a login on or off ─────────────────────────────

it('⚠️⚠️ never switches a login off from Edit owner — a hand-made request did, even for someone who also works here', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);
    $login = ownerActor($owner, $s);
    grantBackOfficeRole($login, $s['org'], 'Hotel Manager');   // they also work for this organisation

    // ⚠️ with a valid commission, so the request passes validation and reaches the write (the review: without it these were
    //    refused before any write, and would have stayed green with the old block restored after `validate()`)
    test()->actingAs($admin)->post(route('app.owners.update', $owner->id), ['commission_pct' => '18', 'portal_active' => '0'])
        ->assertRedirect()->assertSessionHasNoErrors();

    // 🔬 before: 'inactive' — a users.manage actor could lock out a colleague the removal rule (OwnerLoginLifecycle) spares
    expect($login->fresh()->status)->toBe('active');
});

it('never turns an invited login on from Edit owner — only accepting the invite does', function () {
    Mail::fake();
    $s = seedScenario(1);
    $admin = owpAdmin($s);

    test()->actingAs($admin)->post(route('app.owners.invite'), ['role' => 'owner', 'name' => 'Rui Monteiro',
        'email' => 'rui.monteiro@owner.test', 'current_password' => 'password'])->assertRedirect();
    // an owner's email is stored encrypted, so the owner is found through the login the invite made
    $login = User::query()->where('email', 'rui.monteiro@owner.test')->firstOrFail();
    $owner = Owner::query()->where('user_id', $login->id)->firstOrFail();
    expect($login->status)->toBe('invited');

    test()->actingAs($admin)->post(route('app.owners.update', $owner->id), ['commission_pct' => '18', 'portal_active' => '1'])
        ->assertRedirect()->assertSessionHasNoErrors();

    // 🔬 before: 'active' — a login nobody had set a password for, opened without the invite being accepted
    expect($login->fresh()->status)->toBe('invited');
});

it('offers Send invite again and Cancel invite on the page of an owner whose invite is waiting, and comes back here after a resend', function () {
    Mail::fake();
    $s = seedScenario(1);
    $admin = owpAdmin($s);

    test()->actingAs($admin)->post(route('app.owners.invite'), ['role' => 'owner', 'name' => 'Rui Monteiro',
        'email' => 'rui.waiting@owner.test', 'current_password' => 'password'])->assertRedirect();
    $owner = Owner::query()->where('user_id', User::query()->where('email', 'rui.waiting@owner.test')->value('id'))->firstOrFail();
    $invite = OwnerInvite::query()->where('owner_id', $owner->id)->firstOrFail();

    $html = opgPage($admin, $owner);
    // ⚠️ the card's own buttons — the dialogs carry the same addresses, so the addresses alone pass without them (OM-M19)
    expect($html)->toContain('x-on:click="resendOpen = true">Send invite again</button>')
        ->toContain('x-on:click="cancelOpen = true">Cancel invite</button>')
        ->toContain(route('app.owners.invites.resend', $invite->id))
        ->toContain(route('app.owners.invites.cancel', $invite->id))
        // cancelling removes an owner who never accepted (OwnerInviteService::cancel) — the dialog says so first
        ->toContain('Rui Monteiro never accepted, so they leave your owners list too.');

    test()->actingAs($admin)->from(route('app.owners.show', $owner->id))
        ->post(route('app.owners.invites.resend', $invite->id), ['_form' => 'resend', 'return' => 'owner', 'current_password' => 'password'])
        ->assertRedirect(route('app.owners.show', $owner->id));
});

// ── A refusal reopens its dialog and says why — every one of these closed and said nothing ─────

it('⚠️ reopens Remove owner with its refusal beside the password when the password is wrong', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    $html = (string) test()->actingAs($admin)->from(route('app.owners.show', $owner->id))->followingRedirects()
        ->post(route('app.owners.remove', $owner->id), ['_form' => 'remove', 'confirm_name' => 'Inês Carvalho', 'current_password' => 'wrong'])
        ->assertOk()->getContent();

    expect(Owner::query()->find($owner->id))->not->toBeNull()
        ->and($html)->toContain('removeOpen: true')
        ->and($html)->toContain('That password isn’t right — enter the one you sign in with.');
});

it('reopens Remove owner when the typed name does not match, with what was typed', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    $html = (string) test()->actingAs($admin)->from(route('app.owners.show', $owner->id))->followingRedirects()
        ->post(route('app.owners.remove', $owner->id), ['_form' => 'remove', 'confirm_name' => 'Ines Carvalo', 'current_password' => 'password'])
        ->assertOk()->getContent();

    expect(Owner::query()->find($owner->id))->not->toBeNull()
        ->and($html)->toContain('removeOpen: true')
        ->and($html)->toContain('value="Ines Carvalo"');
});

it('⚠️ reopens Close a period when the period ends before it starts, and says so', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    $html = (string) test()->actingAs($admin)->from(route('app.owners.show', $owner->id))->followingRedirects()
        ->post(route('app.owners.close-period', $owner->id), ['_form' => 'close', 'period_start' => '2026-07-31', 'period_end' => '2026-07-01'])
        ->assertOk()->getContent();

    // ⚠️ the dialog's own field error — the page's script carries the same sentence for its live period line, so a page-wide
    //    search would pass without the server's refusal (OM-M12)
    expect($html)->toContain('closeOpen: true')
        ->and($html)->toContain('<div class="vyo-fielderr">The period has to end on or after the day it starts.</div>');
});

it('reopens the commission dialog when the rate is out of range', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    opgCommission($admin, $owner, '150')->assertRedirect(route('app.owners.show', $owner->id));
    $html = opgPage($admin, $owner);

    expect($html)->toContain('commissionOpen: true')
        ->and($html)->toContain('The commission must be a number between 0 and 100.');
});

it('reopens Propose a rate change when its dates are the wrong way round', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);

    $html = (string) test()->actingAs($admin)->from(route('app.owners.show', $owner->id))->followingRedirects()
        ->post(route('app.rate-proposals.store'), ['_form' => 'propose', 'owner_id' => $owner->id,
            'room_type_id' => (string) $s['units'][0]->room_type_id, 'rate' => '185', 'from_date' => '2026-09-30', 'to_date' => '2026-09-01'])
        ->assertOk()->getContent();

    expect($html)->toContain('proposeOpen: true')
        ->and($html)->toContain('The last night has to be on or after the first.');
});

// ── Close a period — dates in words ─────────────────────────────────────────────────────────────

it('says how far the owner is already closed in words, not as a database date', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $owner = opgOwner($s);
    owpStatement($owner, '2026-07', 10000);

    $html = opgPage($admin, $owner);

    expect($html)->toContain('Closed up to 31 Jul 2026')
        ->and($html)->not->toContain('>2026-07-31<');
});
