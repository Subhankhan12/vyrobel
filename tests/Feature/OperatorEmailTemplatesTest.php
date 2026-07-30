<?php

use App\Actions\Reservations\CheckOutReservation;
use App\Mail\ReviewRequestMail;
use App\Models\EmailTemplate;
use App\Models\Reservation;
use App\Services\EmailTemplateService;
use App\Support\Email\EmailTemplateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// OPERATOR EMAIL TEMPLATES (#86) — the customization manager GENUINELY drives the real #83 guest Mailables:
// a saved subject/heading/message/button changes the real rendered email; a disabled block is absent from the
// send; an email switched off does not send on its real trigger; the merge chips resolve from the real guest;
// the write is a guarded, audited controller POST. The defaults equal the #83 strings — un-customized output
// is unchanged (GuestEmailTest stays green).

/** A completed-stay reservation whose lead guest is reachable, in the scenario's org. */
function emailTplBooking(array $s, string $email = 'ada@example.test'): Reservation
{
    $s['guest']->update(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => $email]);

    return book($s, ['unit_id' => $s['units'][0]->id]);
}

// ── THE CORE: a saved heading changes the REAL rendered email ──────────────────────────────────────
it('renders the operator-saved heading in the real email, replacing the default', function () {
    $s = seedScenario(1);
    $res = emailTplBooking($s);

    expect((new ReviewRequestMail($res))->render())->toContain('Thanks for staying with us');

    app(EmailTemplateService::class)->save((string) $s['org']->id, 'review-request', ['heading' => 'We loved having you'], null);

    $html = (new ReviewRequestMail($res))->render();
    expect($html)->toContain('We loved having you')
        ->and($html)->not->toContain('Thanks for staying with us');
});

// ── Merge chips resolve from the real guest (here in the subject line) ─────────────────────────────
it('resolves merge variables from the real reservation/guest', function () {
    $s = seedScenario(1);
    $res = emailTplBooking($s);

    app(EmailTemplateService::class)->save((string) $s['org']->id, 'review-request', ['subject' => 'Hey {first_name}, rate your stay'], null);

    expect((new ReviewRequestMail($res))->envelope()->subject)->toBe('Hey Ada, rate your stay');
});

// ── A disabled content block is genuinely ABSENT from the sent email ───────────────────────────────
it('omits a content block that the operator switched off', function () {
    $s = seedScenario(1);
    $res = emailTplBooking($s);
    $url = route('guest.review', $res->getKey());

    expect((new ReviewRequestMail($res, $url))->render())->toContain('How would you rate your stay?');

    app(EmailTemplateService::class)->save((string) $s['org']->id, 'review-request', [
        'blocks' => ['tap_a_star' => false, 'stay_recap' => true, 'signature' => true],
    ], null);

    expect((new ReviewRequestMail($res, $url))->render())->not->toContain('How would you rate your stay?');
});

// ── An email switched OFF does not send on its REAL trigger (checkout → review-request) ────────────
it('does not send an email switched off on its real trigger, but sends when on', function () {
    // ON (default, no row): checkout fires the review-request.
    Mail::fake();
    $s = seedScenario(1);
    $res = emailTplBooking($s);
    app(CheckOutReservation::class)->handle($res);
    Mail::assertSent(ReviewRequestMail::class);

    // OFF: a second stay whose review-request is disabled does not send.
    Mail::fake();
    app(EmailTemplateService::class)->setEnabled((string) $s['org']->id, 'review-request', false, null);
    $res2 = book($s, ['unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-07']);
    app(CheckOutReservation::class)->handle($res2);
    Mail::assertNotSent(ReviewRequestMail::class);
});

// ── SAVE is a guarded, audited controller POST (#20/#30) ──────────────────────────────────────────
it('saves through the guarded controller and records an audit event', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');   // holds properties.manage

    $this->actingAs($admin)
        ->post(route('app.email-templates.save', 'review-request'), ['heading' => 'Custom heading here'])
        ->assertRedirect(route('app.email-templates.edit', 'review-request'));

    $row = EmailTemplate::withoutGlobalScopes()->where('template_key', 'review-request')->first();
    expect($row)->not->toBeNull()
        ->and($row->heading)->toBe('Custom heading here')
        ->and((string) $row->updated_by)->toBe((string) $admin->id);

    $this->assertDatabaseHas('audit_events', ['action' => 'email_template.updated']);
});

// ── The write is gated: a Front Desk user (no properties.manage) is refused ────────────────────────
it('refuses a save from a user without properties.manage', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, [], 'Front Desk');

    $this->actingAs($frontDesk)
        ->post(route('app.email-templates.save', 'review-request'), ['heading' => 'nope'])
        ->assertForbidden();

    expect(EmailTemplate::withoutGlobalScopes()->where('template_key', 'review-request')->exists())->toBeFalse();
});

// ── The toggle route flips the real enabled flag ──────────────────────────────────────────────────
it('toggles an email off through the guarded controller', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)
        ->post(route('app.email-templates.toggle', 'review-request'), ['enabled' => '0'])
        ->assertRedirect(route('app.email-templates'));

    expect(EmailTemplateResolver::enabled((string) $s['org']->id, 'review-request'))->toBeFalse();
});

// ── The list + editor render for an operator-admin ────────────────────────────────────────────────
it('renders the templates list and the single-template editor', function () {
    $s = seedScenario(1);
    emailTplBooking($s);   // a sample reservation so the editor preview has real data
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->get(route('app.email-templates'))
        ->assertOk()->assertSee('Guest emails')->assertSee('Review request');

    $this->actingAs($admin)->get(route('app.email-templates.edit', 'review-request'))
        ->assertOk()->assertSee('Live preview')->assertSee('Subject line');
});

// ── An unknown template key 404s (not a silent default) ───────────────────────────────────────────
it('404s on an unknown template key', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->get(route('app.email-templates.edit', 'not-a-template'))->assertNotFound();
});
