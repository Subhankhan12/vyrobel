<?php

declare(strict_types=1);

use App\Models\OwnerInvite;
use App\Support\Mail\MailSafetyGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(DatabaseTransactions::class);

// ⚠️⚠️ D-MAIL-CLAIMS (found 2026-10-05 by the Guests review; fixed 2026-11-11, his "do all").
// Screens and flashes said an email had gone out with nothing asking whether mail is sent — and production's mailer is `log`, so every
// one of them was untrue. They now ask the one question every screen asks — MailSafetyGuard::sendingNow() — and say what is true:
//   · an action that IS an email (the costs report, a test send, a resend, an address change) is refused, nothing written or sent;
//   · an invite is still saved, and says so — "saved", never "sent".
// The sent-path of each is pinned by the tests that already existed, now declared with emailIsBeingSent() (tests/Support/mail_helpers.php).

const MC_OFF = 'Email isn’t being sent from Vyrobel yet — nothing was sent.';

it('⚠️⚠️ the suite starts with email NOT sent (the premise of every test below)', function () {
    expect(MailSafetyGuard::sendingNow())->toBeFalse();
    emailIsBeingSent();
    expect(MailSafetyGuard::sendingNow())->toBeTrue();
});

it('⚠️⚠️ an owner invite is SAVED and says so — never "sent" — while email is not sent; the dialog says nobody has been told', function () {
    Mail::fake();
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->post(route('app.owners.invite'), ['current_password' => 'password', 'name' => 'Marisol Vega', 'email' => 'marisol@example.test',
        'role' => 'owner', 'commission_pct' => '18', 'unit_ids' => [$s['units'][0]->id]])
        ->assertRedirect(route('app.owners'))
        ->assertSessionHas('owners_notice', 'Invite saved for marisol@example.test. Email isn’t being sent from Vyrobel yet, so they haven’t been told.')
        ->assertSessionHas('owner_invite_sent', fn (array $sent): bool => $sent['mail_sends'] === false && $sent['email'] === 'marisol@example.test');
    expect(OwnerInvite::query()->where('email', 'marisol@example.test')->exists())->toBeTrue();   // the invite itself is made

    $page = $this->actingAs($admin)->get(route('app.owners'))->assertOk()->getContent();
    expect($page)->toContain('Invite saved')->toContain('haven’t been told')->toContain('Nothing has been sent')
        ->not->toContain('We emailed')->not->toContain('The link works until');
});

it('⚠️⚠️ a RESEND is refused while email is not sent — the old link stays valid and nothing is minted', function () {
    emailIsBeingSent();
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');
    $this->actingAs($admin)->post(route('app.owners.invite'), ['current_password' => 'password', 'name' => 'Marisol Vaz', 'email' => 'marisol@owner.test',
        'role' => 'owner', 'commission_pct' => '15'])->assertSessionHasNoErrors();
    $invite = OwnerInvite::query()->where('email', 'marisol@owner.test')->firstOrFail();
    $hash = (string) $invite->token_hash;

    config(['mail.default' => 'log']);   // email is now NOT sent
    $this->actingAs($admin)->post(route('app.owners.invites.resend', $invite->id), ['current_password' => 'password'])
        ->assertSessionHas('owners_error', MC_OFF)->assertSessionMissing('owners_notice');

    expect((string) $invite->fresh()->token_hash)->toBe($hash)                                  // no fresh link was minted
        ->and(DB::table('audit_events')->where('action', 'owner_invite.resent')->count())->toBe(0);
});

it('⚠️⚠️ the costs report, a template test send and a preview test send are refused while email is not sent — nothing is sent', function () {
    Mail::fake();
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, ['email' => 'boss@demo.test'], 'Property Owner');
    $session = ['current_property_id' => $s['property']->id];

    $this->actingAs($manager)->withSession($session)->post(route('app.maintenance.costs.email'))
        ->assertRedirect(route('app.maintenance.costs'))->assertSessionHas('mt_error', MC_OFF)->assertSessionMissing('mt_notice');
    $this->actingAs($manager)->withSession($session)->post(route('app.email-templates.test', 'review-request'))
        ->assertRedirect()->assertSessionHas('email_template_notice', MC_OFF);
    $this->actingAs($manager)->withSession($session)->post(route('app.email-previews.test', 'booking-confirmation'))
        ->assertRedirect(route('app.email-previews'))->assertSessionHas('email_preview_notice', MC_OFF);

    Mail::assertNothingSent();
    Mail::assertNothingQueued();
});

it('⚠️⚠️ an owner or admin address change is refused while email is not sent — nothing parked, the login email unchanged', function () {
    Mail::fake();
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->changePasswordTo('secret-Passw0rd!');
    $user = $user->fresh();

    $this->actingAs($user)->from(route('owner.settings'))->post(route('owner.account.email'), ['current_password' => 'secret-Passw0rd!', 'email' => 'new@example.test'])
        ->assertSessionHasErrors(['email' => 'Email isn’t being sent from Vyrobel yet, so we can’t send the link that confirms a new address. Nothing was changed.']);
    expect($user->fresh()->pending_email)->toBeNull();

    $admin = platformAdmin(['email' => 'old@x.test']);
    $this->actingAs($admin, 'admin')->post(route('admin.account.email.request'), ['current_password' => 'password', 'email' => 'new@x.test'])
        ->assertSessionHasErrors('email');
    expect($admin->fresh()->pending_email)->toBeNull();

    Mail::assertNothingSent();

    // and a WRONG password is still the password's refusal, not this one (the re-authentication comes first)
    $this->actingAs($user)->from(route('owner.settings'))->post(route('owner.account.email'), ['current_password' => 'nope', 'email' => 'new@example.test'])
        ->assertSessionHasErrors('current_password');
});

it('⚠️ D-MAIL-CLAIMS — the refund page does not say the guest is emailed while email is not sent (and keeps its test-mode sentence)', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, [], 'Property Owner');

    $off = $this->actingAs($manager)->get(route('app.reservations.refund-screen', $res->id))->assertOk()->getContent();
    expect($off)->toContain('won’t get a refund confirmation')->toContain('Card refunds run in test mode')->not->toContain('The guest is emailed a refund confirmation automatically');

    emailIsBeingSent();
    $on = $this->actingAs($manager)->get(route('app.reservations.refund-screen', $res->id))->assertOk()->getContent();
    expect($on)->toContain('The guest is emailed a refund confirmation automatically')->not->toContain('won’t get a refund confirmation');
});

it('⚠️ D-MAIL-CLAIMS — the owner page no longer says a link "sent to" an address for an invite that may never have been mailed', function () {
    expect(__('livewire/operator/owner-detail.cancel_invite_sub', ['email' => 'a@b.test']))->toBe('The invite for a@b.test stops working.');
    expect(__('php/http/controllers/owner/owner_team_controller.invite_saved_no_mail', ['email' => 'a@b.test']))
        ->toBe('Invite saved for a@b.test. Email isn’t being sent from Vyrobel yet, so they haven’t been told.');
});
