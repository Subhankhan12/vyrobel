<?php

use App\Actions\Reservations\CheckOutReservation;
use App\Mail\ReviewRequestMail;
use App\Models\EmailTemplate;
use App\Models\Reservation;
use App\Services\EmailTemplateService;
use App\Support\Email\EmailTemplateCatalog;
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

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P31 (design-parity) — the manager page. The design puts an engagement stat on every card; no open or
// click tracking is collected anywhere, so each one would be invented. And the new breadcrumb points at
// a route this page's own viewers may not hold.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('shows no fabricated engagement stat on any template card (#39)', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');

    $html = $this->actingAs($manager)->get(route('app.email-templates'))->assertOk()
        ->assertSee('Booking confirmation')          // the real templates render
        ->getContent();

    // ⚠️ The design's per-card figures: "72% opened", "81% opened", "38% → review", "64% settle early".
    // Nothing tracks opens or clicks, so any of these would be a number with no source.
    // ⚠️ Scoped to the CLAIM, not to bare words: a previous attempt asserted `not->toContain('click-through')`
    // and failed against a CSS COMMENT in the layout's <style> block about the drag-to-reschedule drop layer.
    // CSS comments render; Blade comments do not. Same trap as the Channels guard at 638e67a.
    expect($html)->not->toContain('% opened')
        ->and($html)->not->toContain('opened')
        ->and($html)->not->toContain('settle early')
        ->and($html)->not->toContain('→ review');
    // …and the page says WHY there are none, rather than silently omitting them.
    expect($html)->toContain('Open/click tracking isn’t collected');
});

it('links the Settings breadcrumb only for a viewer the Settings route would admit (#30)', function () {
    $s = seedScenario(1);

    // Group Admin holds properties.manage → the crumb links and the target opens.
    $admin = operatorUser($s, [], 'Group Admin');
    $this->actingAs($admin)->get(route('app.email-templates'))->assertOk()
        ->assertSee(route('app.settings'), false);
    $this->actingAs($admin)->get(route('app.settings'))->assertOk();

    // ⚠️ Front Desk can VIEW this page (it carries no permission gate) but cannot open Settings.
    $desk = operatorUser($s, [], 'Front Desk');
    $this->actingAs($desk)->get(route('app.email-templates'))->assertOk()
        ->assertDontSee(route('app.settings'), false);
    $this->actingAs($desk)->get(route('app.settings'))->assertForbidden();
});

it('keeps the status toggle a real gated POST with an accessible switch, not a rewired control', function () {
    $s = seedScenario(1);

    // A manager gets the real form posting to the guarded toggle route, as an accessible switch.
    $html = $this->actingAs(operatorUser($s, [], 'Group Admin'))
        ->get(route('app.email-templates'))->assertOk()->getContent();
    expect($html)->toContain('action="'.route('app.email-templates.toggle', 'booking-confirmation').'"')
        ->and($html)->toContain('role="switch"')
        ->and($html)->toContain('aria-checked="true"')
        ->and($html)->toContain('aria-label="Booking confirmation — turn off"');

    // ⚠️ Front Desk sees NO toggle and NO edit — the UI never offers a write the route would 403 (#30).
    $deskHtml = $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get(route('app.email-templates'))->assertOk()->getContent();
    expect($deskHtml)->not->toContain(route('app.email-templates.toggle', 'booking-confirmation'))
        ->and($deskHtml)->not->toContain('role="switch"');
});

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P32 (design-parity) — the EDITOR. Its two actions change what real guests receive and genuinely send
// mail, so the guards are: the repositioned buttons still reach the REAL guarded routes, the variable
// palette offers only real tokens, and a test-send can only ever reach the operator's OWN address.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('reaches the real guarded save and test routes from the repositioned top actions', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, [], 'Group Admin');

    $html = $this->actingAs($manager)->get(route('app.email-templates.edit', 'review-request'))
        ->assertOk()->getContent();

    // The buttons sit outside their forms and reach them with the HTML5 `form=` attribute — so BOTH the
    // form ids and the real actions must be present. A missing id would make the top buttons inert.
    expect($html)->toContain('id="vy-ee-save"')
        ->and($html)->toContain('id="vy-ee-test"')
        ->and($html)->toContain('form="vy-ee-save"')
        ->and($html)->toContain('form="vy-ee-test"')
        ->and($html)->toContain('action="'.route('app.email-templates.save', 'review-request').'"')
        ->and($html)->toContain('action="'.route('app.email-templates.test', 'review-request').'"');
});

it('offers only merge variables the send path can actually fill (#39)', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, [], 'Group Admin');

    $html = $this->actingAs($manager)->get(route('app.email-templates.edit', 'review-request'))
        ->assertOk()->getContent();

    // Every chip comes from the catalogue's real `vars`; each maps to a field the Mailable merges.
    foreach (EmailTemplateCatalog::get('review-request')['vars'] as $var) {
        expect($html)->toContain('{'.$var.'}');
    }
    // ⚠️ And nothing invented — a token with no backing field would render but never fill.
    expect($html)->not->toContain('{loyalty_tier}')
        ->and($html)->not->toContain('{weather}')
        ->and($html)->not->toContain('{discount_code}');
});

it('keeps the section switches real, focusable checkboxes with a label (2.1.1)', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, [], 'Group Admin');

    $html = $this->actingAs($manager)->get(route('app.email-templates.edit', 'review-request'))
        ->assertOk()->getContent();

    // Styled as a switch, but still a real checkbox inside its <label> — never display:none.
    expect($html)->toContain('type="checkbox" class="vy-ee-switch"')
        ->and($html)->toContain('class="vy-ee-blockrow"')
        ->and($html)->not->toContain('.vy-ee-switch { display: none');
    expect($html)->toContain('.vy-ee-switch:focus-visible');
});

it('sends a test only to the operator\'s OWN address, never a guest\'s (#28)', function () {
    Mail::fake();
    $s = seedScenario(1);
    $s['guest']->update(['email' => 'the.guest@hidden.test']);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, [], 'Group Admin');

    $this->actingAs($manager)->post(route('app.email-templates.test', 'review-request'))->assertRedirect();

    // ⚠️ The recipient comes from $request->user()->email — there is no recipient input at all, so it
    // cannot be aimed at a guest even deliberately.
    Mail::assertSent(ReviewRequestMail::class, fn ($mail): bool => $mail->hasTo($manager->email)
        && ! $mail->hasTo('the.guest@hidden.test'));
});
