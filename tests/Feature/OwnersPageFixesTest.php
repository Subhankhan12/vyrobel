<?php

declare(strict_types=1);

use App\Mail\OwnerInviteMail;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerPayoutPreference;
use App\Models\Payout;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\InviteNoLongerRedeemable;
use App\Services\OwnerInviteService;
use App\Support\Owner\OwnerListParams;
use App\Support\Owner\OwnerRows;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE OWNERS PAGE — WHAT ITS WRITES AND FIGURES DO (2026-10-01, the founder: "modal design and buttons are mess … confirm
// with your password and its box is not well aligned … make it more creative and operational and easy to use and fully
// functional"). Each test below failed first against the code as it stood; the page's look is OwnersPageRedesignTest.

function opfInvite(array $s, $actor, array $payload)
{
    return test()->actingAs($actor)->post(route('app.owners.invite'), $payload + ['current_password' => 'password']);
}

// ── A unit's owner is never changed by an invite ─────────────────────────────────────────────────

it('⚠️⚠️ never gives an invited owner a unit that already has an owner — refused at the invite, untouched at acceptance', function () {
    Mail::fake();
    $s = seedScenario(2);
    $admin = owpAdmin($s);
    $amara = owpOwner($s);
    $s['units'][0]->update(['owner_id' => $amara->id]);

    // 🖥 before: the chip was offered and, on acceptance, the unit silently moved to the new owner — and with it the money
    opfInvite($s, $admin, ['name' => 'New Owner', 'email' => 'new@owner.test', 'role' => 'owner', 'unit_ids' => [$s['units'][0]->id]])
        ->assertSessionHasErrors('unit_ids');
    expect(OwnerInvite::query()->where('email', 'new@owner.test')->exists())->toBeFalse();

    // a free unit may be given — but if it gains an owner before the invite is accepted, it stays with that owner
    opfInvite($s, $admin, ['name' => 'New Owner', 'email' => 'new@owner.test', 'role' => 'owner', 'unit_ids' => [$s['units'][1]->id]])
        ->assertSessionHasNoErrors();
    $invite = OwnerInvite::query()->where('email', 'new@owner.test')->firstOrFail();
    $s['units'][1]->update(['owner_id' => $amara->id]);
    app(OwnerInviteService::class)->accept($invite, 'a-strong-passphrase-1');

    expect((string) Unit::query()->find($s['units'][1]->id)->owner_id)->toBe((string) $amara->id);
})->group('money');

it('⚠️⚠️ gives an invite only units inside the properties the person may access (as DEC-k and DEC-l)', function () {
    Mail::fake();
    $s = seedScenario(1);
    $far = secondProperty($s);
    $farType = RoomType::create(['property_id' => $far->id, 'name' => 'Far Suite', 'kind' => 'room']);
    $farUnit = Unit::create(['property_id' => $far->id, 'room_type_id' => $farType->id, 'code' => 'F1']);
    $admin = owpAdmin($s);
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $admin->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);

    opfInvite($s, $admin, ['name' => 'Scoped Owner', 'email' => 'scoped@owner.test', 'role' => 'owner',
        'unit_ids' => [$farUnit->id, $s['units'][0]->id]])->assertSessionHasNoErrors();

    expect(OwnerInvite::query()->where('email', 'scoped@owner.test')->firstOrFail()->assigned_unit_ids)->toBe([(string) $s['units'][0]->id]);
})->group('security');

// ── Invites can be resent and cancelled, and every one leaves a trail ────────────────────────────

it('⚠️ resends a waiting invite with a fresh link — only with your password — and records it', function () {
    emailIsBeingSent();   // D-MAIL-CLAIMS: this path SAYS an email was sent, so email must be sent
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    opfInvite($s, $admin, ['name' => 'Marisol Vaz', 'email' => 'marisol@owner.test', 'role' => 'owner', 'commission_pct' => '15'])
        ->assertSessionHasNoErrors();
    $invite = OwnerInvite::query()->where('email', 'marisol@owner.test')->firstOrFail();
    $firstHash = (string) $invite->token_hash;

    $this->actingAs($admin)->post(route('app.owners.invites.resend', $invite->id))->assertSessionHasErrors('current_password');
    expect((string) $invite->fresh()->token_hash)->toBe($firstHash);

    $this->actingAs($admin)->post(route('app.owners.invites.resend', $invite->id), ['current_password' => 'password'])
        ->assertSessionHas('owners_notice', fn (string $m): bool => str_contains($m, 'marisol@owner.test'));

    $fresh = $invite->fresh();
    expect((string) $fresh->token_hash)->not->toBe($firstHash)
        ->and($fresh->status)->toBe('pending')
        ->and((float) $fresh->commission_pct)->toBe(15.0)
        ->and(DB::table('audit_events')->where('action', 'owner_invite.sent')->count())->toBe(1)
        ->and(DB::table('audit_events')->where('action', 'owner_invite.resent')->count())->toBe(1);
    Mail::assertSent(OwnerInviteMail::class, 2);

    // ⚠️ the trail records which invite and what it carries — never the address (contact values stay off audit_events)
    $trail = DB::table('audit_events')->where('action', 'like', 'owner_invite.%')->get(['before', 'after'])
        ->map(fn ($e): string => (string) $e->before.' '.(string) $e->after)->implode(' ');
    expect($trail)->toContain('commission_pct')->not->toContain('marisol');
});

it('⚠️ cancels a waiting invite — the link stops working and the never-activated owner leaves the list', function () {
    Mail::fake();
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    $token = app(OwnerInviteService::class)->invite('Leon Hart', 'leon@owner.test', [], null, 'owner')['token'];
    $invite = OwnerInvite::query()->where('email', 'leon@owner.test')->firstOrFail();
    $ownerId = (string) $invite->owner_id;

    $this->actingAs($admin)->post(route('app.owners.invites.cancel', $invite->id))
        ->assertSessionHas('owners_notice', fn (string $m): bool => str_contains($m, 'cancelled'));

    expect($invite->fresh()->status)->toBe('revoked')
        ->and(app(OwnerInviteService::class)->findRedeemable($token))->toBeNull()
        ->and(Owner::query()->find($ownerId))->toBeNull()
        ->and(DB::table('audit_events')->where('action', 'owner_invite.cancelled')->count())->toBe(1);

    // a used invite cannot be cancelled
    $this->actingAs($admin)->post(route('app.owners.invites.cancel', $invite->id))->assertSessionHas('owners_error');
});

it('⚠️⚠️ resends without a unit that gained an owner since — the second layer behind acceptance', function () {
    emailIsBeingSent();   // D-MAIL-CLAIMS: this path SAYS an email was sent, so email must be sent
    $s = seedScenario(2);
    $admin = owpAdmin($s);
    opfInvite($s, $admin, ['name' => 'Two Units', 'email' => 'two@owner.test', 'role' => 'owner',
        'unit_ids' => [$s['units'][0]->id, $s['units'][1]->id]])->assertSessionHasNoErrors();
    $s['units'][0]->update(['owner_id' => owpOwner($s)->id]);

    $invite = OwnerInvite::query()->where('email', 'two@owner.test')->firstOrFail();
    $this->actingAs($admin)->post(route('app.owners.invites.resend', $invite->id), ['current_password' => 'password'])
        ->assertSessionHas('owners_notice');

    expect(OwnerInvite::query()->where('email', 'two@owner.test')->where('status', 'pending')->firstOrFail()->assigned_unit_ids)
        ->toBe([(string) $s['units'][1]->id]);
})->group('money');

it('⚠️ resends only an invite still waiting — a used one says so and sends nothing', function () {
    emailIsBeingSent();   // D-MAIL-CLAIMS: this path SAYS an email was sent, so email must be sent
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    app(OwnerInviteService::class)->invite('Used Invite', 'used@owner.test', [], null, 'owner');
    $invite = OwnerInvite::query()->where('email', 'used@owner.test')->firstOrFail();
    app(OwnerInviteService::class)->accept($invite, 'a-strong-passphrase-1');

    $this->actingAs($admin)->post(route('app.owners.invites.resend', $invite->id), ['current_password' => 'password'])
        ->assertSessionHas('owners_error', fn (string $m): bool => str_contains($m, 'already been used or cancelled'));
    Mail::assertSent(OwnerInviteMail::class, 1);   // the original only
});

it('⚠️ cancelling never removes an owner who is active — only one made for the invite and never activated', function () {
    Mail::fake();
    $s = seedScenario(1);
    app(OwnerInviteService::class)->invite('Already Active', 'active@owner.test', [], null, 'owner');
    $invite = OwnerInvite::query()->where('email', 'active@owner.test')->firstOrFail();
    Owner::query()->whereKey($invite->owner_id)->update(['status' => 'active']);   // an inconsistency: leave it alone

    $this->actingAs(owpAdmin($s))->post(route('app.owners.invites.cancel', $invite->id))->assertSessionHas('owners_notice');

    expect($invite->fresh()->status)->toBe('revoked')
        ->and(Owner::query()->find($invite->owner_id))->not->toBeNull();
})->group('money');

it('⚠️⚠️ leaves a founder’s set-password link alone — not this page’s to resend or cancel (ADMIN 04)', function () {
    Mail::fake();
    $s = seedScenario(1);
    $founder = OwnerInvite::create(['organization_id' => $s['org']->id, 'email' => 'founder@new-org.test', 'token_hash' => hash('sha256', 'x'),
        'role' => OwnerInviteService::FOUNDER_ROLE, 'status' => 'pending', 'expires_at' => now()->addDays(7)]);
    $admin = owpAdmin($s);

    $this->actingAs($admin)->post(route('app.owners.invites.resend', $founder->id), ['current_password' => 'password'])->assertNotFound();
    $this->actingAs($admin)->post(route('app.owners.invites.cancel', $founder->id))->assertNotFound();
    expect($founder->fresh()->status)->toBe('pending');
    Mail::assertNothingSent();

    // ⚠️ and the service refuses it on its own — the route's 404 is one layer, this is the other
    expect(fn () => app(OwnerInviteService::class)->resend($founder))->toThrow(InviteNoLongerRedeemable::class)
        ->and(app(OwnerInviteService::class)->cancel($founder))->toBeFalse()
        ->and($founder->fresh()->status)->toBe('pending');
})->group('security');

it('⚠️⚠️ lets only a person who may manage users resend or cancel an invite — and only their own organisation’s', function () {
    Mail::fake();
    $s = seedScenario(1);
    app(OwnerInviteService::class)->invite('Leon Hart', 'leon@owner.test', [], null, 'owner');
    $invite = OwnerInvite::query()->where('email', 'leon@owner.test')->firstOrFail();

    $manager = operatorUser($s, [], 'Hotel Manager');   // reads the page, may not manage users
    $this->actingAs($manager)->post(route('app.owners.invites.cancel', $invite->id))->assertForbidden();
    $this->actingAs($manager)->post(route('app.owners.invites.resend', $invite->id), ['current_password' => 'password'])->assertForbidden();

    $other = seedScenario(1);
    $this->actingAs(owpAdmin($other))->post(route('app.owners.invites.cancel', $invite->id))->assertNotFound();
    // read past the tenant scope — the second scenario switched the context to the other organisation
    expect(OwnerInvite::withoutGlobalScopes()->find($invite->id)->status)->toBe('pending');
})->group('security');

it('⚠️ refuses every invite write while the workspace is paused — and offers none of them', function () {
    Mail::fake();
    $s = seedScenario(1);
    $admin = owpAdmin($s);
    app(OwnerInviteService::class)->invite('Leon Hart', 'leon@owner.test', [], null, 'owner');
    $invite = OwnerInvite::query()->where('email', 'leon@owner.test')->firstOrFail();
    $hash = (string) $invite->token_hash;
    pauseWorkspace($s);

    // the waiting invite is still shown — read-only, with nothing on it that would be refused
    $html = (string) $this->actingAs($admin)->get(route('app.owners'))->assertOk()->getContent();
    expect($html)->toContain('leon@owner.test')
        ->and($html)->not->toContain('aria-labelledby="vy-invite-title"')
        ->and($html)->not->toContain('>Resend<')
        ->and($html)->not->toContain('>Cancel invite<');

    $paused = fn (string $m): bool => str_contains($m, 'Workspace paused');
    opfInvite($s, $admin, ['name' => 'New Owner', 'email' => 'new@owner.test', 'role' => 'owner'])->assertSessionHas('owners_error', $paused);
    $this->actingAs($admin)->post(route('app.owners.invites.resend', $invite->id), ['current_password' => 'password'])
        ->assertSessionHas('owners_error', $paused);
    $this->actingAs($admin)->post(route('app.owners.invites.cancel', $invite->id))->assertSessionHas('owners_error', $paused);

    expect(OwnerInvite::query()->where('email', 'new@owner.test')->exists())->toBeFalse()
        ->and($invite->fresh()->status)->toBe('pending')
        ->and((string) $invite->fresh()->token_hash)->toBe($hash);
    Mail::assertSent(OwnerInviteMail::class, 1);   // the one sent before the pause
});

// ── The figures and statuses agree with what the payout run will do ──────────────────────────────

it('⚠️ counts an owner whose payouts are paused as paused — never as ready to pay', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s);
    owpStatement($owner, '2026-09', 44280);
    OwnerPayoutPreference::query()->updateOrCreate(['owner_id' => $owner->id], ['paused' => true]);

    $html = (string) $this->actingAs(owpAdmin($s))->get(route('app.owners'))->assertOk()->getContent();

    expect($html)->toContain('Payouts paused')
        ->and(app(OwnerRows::class)->load()[0]['pay_key'])->toBe('paused')
        // and its money is not in "Ready to pay" — the figure says what it left out, and so does the payout card
        ->and($html)->toContain('0 owners ready · 1 paused, not counted')
        ->and(preg_match('/class="vyo-payout-sub">([^<]*)</', $html, $card) === 1 ? trim($card[1]) : null)->toBe('0 owners ready · 1 paused');
});

it('⚠️ offers Resume — never a second Pause — for a paused owner with nothing due yet, and finds them under Payouts paused', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s, 'Quiet Owner');   // active, no statement yet
    OwnerPayoutPreference::query()->updateOrCreate(['owner_id' => $owner->id], ['paused' => true]);

    $html = (string) $this->actingAs(owpAdmin($s))->get(route('app.owners'))->assertOk()->getContent();
    // the rows the row menu reads — the JSON beside the table
    preg_match('/<script type="application\/json" data-ow-rows>(.*?)<\/script>/s', $html, $island);
    $row = collect(json_decode($island[1] ?? '[]', true))->first();
    preg_match('/data-gl-key="chip-paused"[^>]*>Payouts paused<span class="vy-gl-count">(\d+)</', $html, $chip);

    // 🖥 before: the menu read Pause/Resume off the pay status, which says "paused" only while money is due
    expect($row['actions'])->toContain('resume')->not->toContain('pause')
        ->and($row['reason'])->toBe('Payouts paused by your team')
        ->and($chip[1] ?? null)->toBe('1');
});

it('⚠️ says why each owner on hold is on hold — in the owner’s terms, not a bare chip', function () {
    $s = seedScenario(1);
    $noTax = owpOwner($s, 'No Tax', ['tax_id' => null]);
    owpStatement($noTax, '2026-09', 10000);
    $noAccount = Owner::factory()->create(['name' => 'No Account', 'status' => 'active', 'tax_id' => 'IE1', 'payout_currency' => 'EUR']);
    owpStatement($noAccount, '2026-09', 10000);
    $held = owpOwner($s, 'Held Payout');
    $stmt = owpStatement($held, '2026-09', 10000);
    Payout::create(['organization_id' => $s['org']->id, 'owner_id' => $held->id, 'owner_statement_id' => $stmt->id, 'amount_minor' => 10000,
        'currency' => 'EUR', 'status' => 'held', 'held_at' => now()]);

    $rows = collect(app(OwnerRows::class)->load())->keyBy('name');

    expect($rows['No Tax']['pay_key'])->toBe('hold')->and($rows['No Tax']['reason'])->toBe('Waiting for the owner’s tax details')
        ->and($rows['No Account']['reason'])->toBe('No payout account on file')
        ->and($rows['Held Payout']['reason'])->toBe('Payout held until the owner adds tax details');
});

it('⚠️ says how statements close and how payouts go out — and nothing the system does not do', function () {
    $s = seedScenario(1);
    $admin = owpAdmin($s);

    // ⚠️ read the PANEL, not the page (2026-10-02): the pause dialog's own sentence "…paid again from the payout run…" made a
    //    page-wide search pass whatever the panel said — mutation OW-M32 survived the full run until this was scoped
    $panel = function (string $html): string {
        preg_match('/<section class="vy-card vyo-howpaid[^"]*"[^>]*>(.*?)<\/section>/s', $html, $m);

        return $m[1] ?? '';
    };

    config(['services.payouts.engine_enabled' => false]);
    $off = $panel((string) $this->actingAs($admin)->get(route('app.owners'))->assertOk()->getContent());
    // 🔬 nothing closes a statement on the 1st: OwnerManageController::closePeriod is the only caller of finalize()
    expect($off)->not->toContain('1st of the month')
        ->and($off)->toContain('Close period')
        ->and($off)->toContain('Sent by your team from the payout run')
        ->and($off)->not->toContain('Sent automatically');

    config(['services.payouts.engine_enabled' => true]);
    $on = $panel((string) $this->actingAs($admin)->get(route('app.owners'))->assertOk()->getContent());
    expect($on)->toContain('Sent automatically')->toContain('3rd business day');
});

// ── The list's address, its export, and the way back ─────────────────────────────────────────────

it('reads the list’s settings from the address through an allow-list — and its way back is only ever this page', function () {
    expect(OwnerListParams::clean(['filter' => 'hold', 'q' => '  amara ', 'sort' => 'net', 'dir' => 'asc', 'per' => '50', 'page' => '2']))
        ->toBe(['filter' => 'hold', 'q' => 'amara', 'sort' => 'net', 'dir' => 'asc', 'per' => 50, 'page' => 2])
        ->and(OwnerListParams::clean(['filter' => 'evil', 'sort' => 'password', 'dir' => 'sideways', 'per' => '7', 'page' => '-1']))->toBe([])
        ->and(OwnerListParams::returnUrl('filter=hold&next=https://evil.example'))->toBe(route('app.owners', ['filter' => 'hold']))
        ->and(parse_url(OwnerListParams::returnUrl('//evil.example/x'), PHP_URL_HOST))->toBe(parse_url(route('app.owners'), PHP_URL_HOST));
});

it('⚠️ exports the owners in view as a CSV — formula-safe, recorded, and only for a role that may read owner finances', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s, '=HYPERLINK("http://evil.example")');
    owpStatement($owner, '2026-09', 44280);

    $response = $this->actingAs(owpAdmin($s))->get(route('app.owners.export'))->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toContain('Owner,Email,Status,Units,Commission,Statement,"Net payable",Reason')
        ->and($csv)->toContain("'=HYPERLINK")
        ->and($csv)->toContain('442.80')
        ->and(DB::table('audit_events')->where('action', 'owner.list_exported')->count())->toBe(1);

    $this->actingAs(operatorUser($s, [], 'Housekeeping'))->get(route('app.owners.export'))->assertForbidden();
})->group('security');

it('brings pausing payouts from the list back to the list as it was', function () {
    $s = seedScenario(1);
    $owner = owpOwner($s);

    $this->actingAs(owpAdmin($s))->post(route('app.owners.pause-payouts', $owner->id), ['paused' => '1', 'return' => 'list',
        'return_query' => 'filter=hold&q=amara&evil=1'])
        ->assertRedirect(route('app.owners', ['filter' => 'hold', 'q' => 'amara']));

    expect((bool) OwnerPayoutPreference::query()->where('owner_id', $owner->id)->value('paused'))->toBeTrue();
});
