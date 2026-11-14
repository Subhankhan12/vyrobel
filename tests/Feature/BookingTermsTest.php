<?php

use App\Mail\BookingConfirmationMail;
use App\Models\AuditEvent;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Services\BookingEngineService;
use App\Support\Booking\BookingTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

// BOOKING TERMS (his ruling 2026-11-14, option B — OPEN D-BOOK-NO-TERMS). A booking is a contract and the checkout collects personal data, so
// before a guest pays they must SEE the terms and accept them, and what they accepted must be provable. Proven here: the terms are built from the
// property's REAL settings (never invented), the checkout refuses without acceptance and never stores a lie when the terms changed under the guest,
// the proof is written with the reservation and holds no guest data, the hotel's own text is escaped, the machine API is not a way round it, and the
// public pages are read-only and property-isolated.

/** A property with real policies the terms can be built from. */
function termsProperty(array $policy = [], array $property = []): array
{
    $s = publicPropertyScenario(units: 1);
    $s['property']->update(array_merge(['check_in_time' => '15:00:00', 'check_out_time' => '11:00:00'], $property));
    PropertyPolicy::create(array_merge([
        'property_id' => $s['property']->id,
        'cancellation' => ['type' => 'flexible', 'free_until_days' => 7],
        'pet' => 'allowed',
        'smoking' => 'not_allowed',
        'min_stay' => 2,
        'deposit' => ['required' => false],
        'booking_terms_extra' => 'After the free-cancellation date we charge the first night.',
    ], $policy));

    return $s;
}

/** The web checkout POST body, with the terms accepted exactly as the page offered them. */
function termsCheckoutBody(array $s, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Zephyrina',
        'last_name' => 'Quibblewick',
        'email' => 'zephyrina.quibblewick@guestmail.dev',
        'payment_option' => 'on_arrival',
        'terms_accepted' => '1',
        'terms_version' => BookingTerms::for(Property::query()->findOrFail($s['property']->id), '2026-09-01')['version'],
    ], $overrides);
}

// ── 1. The terms are built from what the property REALLY set ──────────────────────────────────────────────
it('builds the terms from the property\'s real settings and invents nothing', function () {
    $s = termsProperty();

    $terms = BookingTerms::for($s['property'], '2026-09-01');
    $lines = collect($terms['sections'])->flatMap(fn ($sec) => $sec['lines'])->all();

    expect($lines)->toContain('Free cancellation until 25 Aug 2026.')   // 1 Sep minus the policy's 7 days
        ->toContain('Check-in from 15:00.')
        ->toContain('Check-out by 11:00.')
        ->toContain('Minimum stay: 2 nights.')
        ->toContain('Pets: Allowed.')
        ->toContain('Smoking: Not allowed.')
        ->and($terms['extra'])->toBe('After the free-cancellation date we charge the first night.')
        ->and($terms['version'])->toMatch('/^[0-9a-f]{16}$/');
    // The policy stores ONLY a free-cancel deadline — so nothing is said about a refund percentage or a penalty (#39).
    expect(implode(' ', $lines))->not->toMatch('/%|refund of|penalty/i');
});

it('says nothing about a rule the property never set', function () {
    $s = publicPropertyScenario(units: 1);   // no policy row at all

    $terms = BookingTerms::for($s['property'], '2026-09-01');
    $lines = collect($terms['sections'])->flatMap(fn ($sec) => $sec['lines'])->implode(' ');

    expect($lines)->toContain('has not set a free-cancellation rule')
        ->not->toContain('Pets')->not->toContain('Smoking')->not->toContain('Minimum stay')->not->toContain('deposit')
        ->and($terms['extra'])->toBeNull();
});

it('keeps the version stable for the same rules and changes it when a rule or the hotel\'s words change', function () {
    $s = termsProperty();
    $v1 = BookingTerms::for($s['property'], '2026-09-01')['version'];

    expect(BookingTerms::for($s['property'], '2026-12-24')['version'])->toBe($v1);   // another stay's dates: same rules, same version

    PropertyPolicy::query()->where('property_id', $s['property']->id)->update(['booking_terms_extra' => 'Changed words.']);
    $v2 = BookingTerms::for($s['property']->fresh(), '2026-09-01')['version'];
    expect($v2)->not->toBe($v1);

    PropertyPolicy::query()->where('property_id', $s['property']->id)->update(['cancellation' => json_encode(['type' => 'flexible', 'free_until_days' => 14])]);
    expect(BookingTerms::for($s['property']->fresh(), '2026-09-01')['version'])->not->toBe($v2);
});

// ── 2. The checkout page shows them, with the box ─────────────────────────────────────────────────────────
it('shows the terms, the version and an unticked required box on the checkout', function () {
    $s = termsProperty();
    $version = BookingTerms::for($s['property'], '2026-09-01')['version'];

    $page = test()->get("/book/{$s['property']->slug}/checkout?".http_build_query([
        'room_type_id' => $s['roomType']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'adults' => 2, 'children' => 0,
    ]))->assertOk()
        ->assertSee('Before you book')
        ->assertSee('Free cancellation until 25 Aug 2026.')
        ->assertSee('Pets: Allowed.')
        ->assertSee('After the free-cancellation date we charge the first night.')
        ->assertSee('name="terms_accepted"', false)
        ->assertSee('name="terms_version" value="'.$version.'"', false)
        ->assertSee("/book/{$s['property']->slug}/terms", false)
        ->assertSee("/book/{$s['property']->slug}/privacy", false);

    // The box is REQUIRED and not pre-ticked (a pre-ticked box is not consent).
    $html = $page->getContent();
    expect($html)->toMatch('/<input type="checkbox" name="terms_accepted" value="1" required/');
    expect($html)->not->toMatch('/name="terms_accepted"[^>]*checked/');
    assertNoRawBlade($page);
});

// ── 3. Refusals: nothing is booked, nothing is stored ────────────────────────────────────────────────────
it('refuses a booking without the box and books nothing', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);

    test()->from("/book/{$s['property']->slug}/checkout")
        ->post("/book/{$s['property']->slug}/checkout", termsCheckoutBody($s, ['terms_accepted' => null]))
        ->assertSessionHasErrors('terms_accepted');

    expect(Reservation::query()->count())->toBe(0)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
});

it('refuses a booking from a page that carried no terms version (a stale tab)', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);

    test()->from("/book/{$s['property']->slug}/checkout")
        ->post("/book/{$s['property']->slug}/checkout", array_diff_key(termsCheckoutBody($s), ['terms_version' => 1]))
        ->assertSessionHasErrors('terms_accepted');

    expect(Reservation::query()->count())->toBe(0);
});

it('refuses, and stores no lie, when the property changed its terms after the guest loaded the page', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);
    $body = termsCheckoutBody($s);   // the version the guest SAW

    PropertyPolicy::query()->where('property_id', $s['property']->id)->update(['booking_terms_extra' => 'Rules changed while you were reading.']);

    test()->from("/book/{$s['property']->slug}/checkout")
        ->post("/book/{$s['property']->slug}/checkout", $body)
        ->assertSessionHasErrors('terms_accepted')
        ->assertSessionHasInput('first_name', 'Zephyrina');   // the guest does not retype their details

    expect(Reservation::query()->count())->toBe(0);

    // With the NEW version (they read it again) the same booking goes through and stores THAT version.
    $fresh = termsCheckoutBody($s);
    test()->post("/book/{$s['property']->slug}/checkout", $fresh)->assertRedirect();
    expect(Reservation::query()->firstOrFail()->terms_version)->toBe($fresh['terms_version']);
});

// ── 4. The proof is written with the reservation ─────────────────────────────────────────────────────────
it('stores the acceptance — time, version and the terms as shown — on the reservation', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);
    $body = termsCheckoutBody($s);

    test()->post("/book/{$s['property']->slug}/checkout", $body)->assertRedirect();

    $r = Reservation::query()->firstOrFail();
    expect($r->terms_accepted_at)->not->toBeNull()
        ->and($r->terms_accepted_at->toDateString())->toBe(now()->toDateString())
        ->and($r->terms_version)->toBe($body['terms_version'])
        ->and($r->terms_snapshot['version'])->toBe($body['terms_version'])
        ->and($r->terms_snapshot['extra'])->toBe('After the free-cancellation date we charge the first night.')
        ->and(collect($r->terms_snapshot['sections'])->flatMap(fn ($sec) => $sec['lines'])->all())->toContain('Free cancellation until 25 Aug 2026.');
});

it('holds NO guest data in the stored proof or in the audit row', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);
    test()->post("/book/{$s['property']->slug}/checkout", termsCheckoutBody($s))->assertRedirect();

    $r = Reservation::query()->firstOrFail();
    $blob = json_encode($r->terms_snapshot);
    expect($blob)->not->toContain('Zephyrina')->not->toContain('Quibblewick')->not->toContain('guestmail.dev');

    $audit = AuditEvent::query()->where('action', 'reservation.created')->where('subject_id', $r->id)->firstOrFail();
    expect($audit->after['terms_version'])->toBe($r->terms_version)
        ->and(json_encode($audit->after))->not->toContain('Free cancellation')->not->toContain('Zephyrina')->not->toContain('guestmail.dev');
});

it('never rewrites what a past guest accepted when the property edits its rules later', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);
    test()->post("/book/{$s['property']->slug}/checkout", termsCheckoutBody($s))->assertRedirect();
    $before = Reservation::query()->firstOrFail()->terms_snapshot;

    PropertyPolicy::query()->where('property_id', $s['property']->id)->update(['booking_terms_extra' => 'Completely different rules now.', 'pet' => 'not_allowed']);

    $after = Reservation::query()->firstOrFail()->terms_snapshot;
    expect($after)->toBe($before)
        ->and(BookingTerms::for($s['property']->fresh(), '2026-09-01')['version'])->not->toBe($before['version']);
});

it('records no acceptance for a booking made without terms (an operator, OTA or older booking) — never a guess', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    $engine = app(BookingEngineService::class);
    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'x@guestmail.dev');

    $engine->book($quote, 'offline', ['email' => 'x@guestmail.dev', 'first_name' => 'X', 'last_name' => 'Y']);

    $r = Reservation::query()->firstOrFail();
    expect($r->terms_accepted_at)->toBeNull()->and($r->terms_version)->toBeNull()->and($r->terms_snapshot)->toBeNull();
});

// ── 5. The hotel's own words are PLAIN TEXT ──────────────────────────────────────────────────────────────
it('escapes the hotel\'s own text everywhere it is shown (no stored XSS)', function () {
    $s = termsProperty(['booking_terms_extra' => "<script>alert('x')</script> & <b>bold</b>\nSecond line"]);

    foreach (["/book/{$s['property']->slug}/terms", "/book/{$s['property']->slug}/checkout?".http_build_query([
        'room_type_id' => $s['roomType']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'adults' => 2,
    ])] as $url) {
        $html = test()->get($url)->assertOk()->getContent();
        expect($html)->not->toContain("<script>alert('x')</script>")
            ->and($html)->toContain('&lt;script&gt;')
            ->and($html)->not->toContain('<b>bold</b>')
            ->and($html)->toContain('Second line');
    }
});

it('cleans the stored text: trims, normalises line endings, drops control characters, caps the length', function () {
    expect(BookingTerms::clean("  a\r\nb\x00\x07c  "))->toBe("a\nbc")
        ->and(BookingTerms::clean("   \n\t "))->toBeNull()
        ->and(BookingTerms::clean(null))->toBeNull()
        ->and(mb_strlen((string) BookingTerms::clean(str_repeat('é', 5000))))->toBe(BookingTerms::MAX_EXTRA);
});

// ── 6. The machine API is not a way round it ──────────────────────────────────────────────────────────────
it('refuses a JSON booking without terms_accepted, and a wrong terms_version, and books nothing', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);
    $quote = app(BookingEngineService::class)->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'api@guestmail.dev');
    $base = ['quote_id' => $quote->id, 'payment_token' => 'offline', 'email' => 'api@guestmail.dev', 'first_name' => 'Api', 'last_name' => 'Client'];

    test()->postJson("/book/{$s['property']->slug}/book", $base)->assertStatus(422)->assertJsonValidationErrors('terms_accepted');
    test()->postJson("/book/{$s['property']->slug}/book", $base + ['terms_accepted' => true, 'terms_version' => 'deadbeefdeadbeef'])
        ->assertStatus(422)->assertJsonValidationErrors('terms_version');

    expect(Reservation::query()->count())->toBe(0);
});

it('books over the JSON API with acceptance and stores the same proof', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);
    $quote = app(BookingEngineService::class)->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'api@guestmail.dev');
    $version = BookingTerms::for($s['property'], '2026-09-01')['version'];

    test()->postJson("/book/{$s['property']->slug}/book", [
        'quote_id' => $quote->id, 'payment_token' => 'offline', 'email' => 'api@guestmail.dev', 'first_name' => 'Api', 'last_name' => 'Client',
        'terms_accepted' => true, 'terms_version' => $version,
    ])->assertStatus(201);

    $r = Reservation::query()->firstOrFail();
    expect($r->terms_version)->toBe($version)->and($r->terms_accepted_at)->not->toBeNull();
});

// ── 7. The public pages ───────────────────────────────────────────────────────────────────────────────────
it('serves the property\'s terms and the guest privacy notice, read-only and noindex', function () {
    $s = termsProperty([], ['brand_reply_to' => 'stay@hotel-example.test', 'support_phone' => '+351 210 000 000']);

    $terms = test()->get("/book/{$s['property']->slug}/terms")->assertOk()
        ->assertSee('Booking terms')->assertSee('Public Hotel is the seller of your stay')
        ->assertSee('stay@hotel-example.test')->assertSee('Version '.BookingTerms::for($s['property'])['version'])
        ->assertSee('noindex', false);
    assertNoRawBlade($terms);

    $privacy = test()->get("/book/{$s['property']->slug}/privacy")->assertOk()
        ->assertSee('Privacy notice for your booking')->assertSee('Stripe')->assertSee('stay@hotel-example.test')
        ->assertSee('booking does not sign you up for marketing emails');
    assertNoRawBlade($privacy);
    // ⚠️ The notice must NOT state a retention period — the law fixes them and they are not decided here (OPEN F6).
    expect(strip_tags($privacy->getContent()))->not->toMatch('/\b\d+\s+(years?|months?|days?)\b/i');
});

it('shows a property only its own terms (isolation)', function () {
    $a = termsProperty(['booking_terms_extra' => 'ALPHA-ONLY-HOUSE-RULE']);
    $b = termsProperty(['booking_terms_extra' => 'BRAVO-ONLY-HOUSE-RULE']);

    test()->get("/book/{$a['property']->slug}/terms")->assertOk()->assertSee('ALPHA-ONLY-HOUSE-RULE')->assertDontSee('BRAVO-ONLY-HOUSE-RULE');
    test()->get("/book/{$b['property']->slug}/terms")->assertOk()->assertSee('BRAVO-ONLY-HOUSE-RULE')->assertDontSee('ALPHA-ONLY-HOUSE-RULE');
});

it('answers 404 for a property that does not exist', function () {
    test()->get('/book/no-such-property-xyz/terms')->assertNotFound();
    test()->get('/book/no-such-property-xyz/privacy')->assertNotFound();
});

// ── 8. After booking: the guest, the email and the operator see what was accepted ──────────────────────────
it('repeats what the guest accepted on the public confirmation page and in the confirmation email', function () {
    $s = termsProperty();
    config(['services.payments.gateway' => 'manual']);
    $resp = test()->post("/book/{$s['property']->slug}/checkout", termsCheckoutBody($s))->assertRedirect();

    test()->get($resp->headers->get('Location'))->assertOk()
        ->assertSee('Booking terms accepted on')->assertSee('Free cancellation until 25 Aug 2026.')
        ->assertSee('After the free-cancellation date we charge the first night.');

    $reservation = Reservation::query()->firstOrFail();
    $html = (new BookingConfirmationMail($reservation))->render();
    expect($html)->toContain('You accepted the booking terms on')->toContain("/book/{$s['property']->slug}/terms");

    // A booking with no acceptance on record says nothing of the kind.
    $reservation->update(['terms_accepted_at' => null, 'terms_version' => null, 'terms_snapshot' => null]);
    expect((new BookingConfirmationMail($reservation->fresh()))->render())->not->toContain('You accepted the booking terms');
});

// ── 9. Settings: the hotel's own-terms box ───────────────────────────────────────────────────────────────
it('lets the manager save the hotel\'s own terms, audits the FIELD and never the text, and a partial save keeps them', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');   // holds properties.manage

    $this->actingAs($manager)->post('/app/settings/policies', [
        'pet' => 'allowed', 'smoking' => 'not_allowed', 'min_stay' => '1',
        'booking_terms_extra' => "  Quiet hours 22:00–07:00.\r\nNo parties.  ",
    ])->assertRedirect()->assertSessionHas('settings_notice');

    $policy = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail();
    expect($policy->booking_terms_extra)->toBe("Quiet hours 22:00–07:00.\nNo parties.");

    $audit = AuditEvent::query()->where('action', 'property.policies_updated')->latest('created_at')->firstOrFail();
    expect($audit->after['fields'])->toContain('booking_terms_extra')->and(json_encode($audit->after))->not->toContain('Quiet hours');

    // A save from a client that does NOT send the field must not wipe the hotel's words.
    $this->actingAs($manager)->post('/app/settings/policies', ['pet' => 'on_request', 'smoking' => 'not_allowed', 'min_stay' => '1'])->assertRedirect();
    expect($policy->fresh()->booking_terms_extra)->toBe("Quiet hours 22:00–07:00.\nNo parties.");

    // Sending it empty clears it.
    $this->actingAs($manager)->post('/app/settings/policies', ['pet' => 'on_request', 'smoking' => 'not_allowed', 'min_stay' => '1', 'booking_terms_extra' => '   '])->assertRedirect();
    expect($policy->fresh()->booking_terms_extra)->toBeNull();
});

it('rejects a hotel text longer than the limit', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->from('/app/settings')
        ->post('/app/settings/policies', ['pet' => 'allowed', 'smoking' => 'allowed', 'min_stay' => '1', 'booking_terms_extra' => str_repeat('x', BookingTerms::MAX_EXTRA + 1)])
        ->assertSessionHasErrors('booking_terms_extra');

    expect(PropertyPolicy::query()->where('property_id', $s['property']->id)->exists())->toBeFalse();
});

it('keeps the front desk from editing the hotel\'s own terms', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post('/app/settings/policies', ['pet' => 'allowed', 'smoking' => 'allowed', 'min_stay' => '1', 'booking_terms_extra' => 'Sneaky'])
        ->assertForbidden();

    expect(PropertyPolicy::query()->where('property_id', $s['property']->id)->exists())->toBeFalse();
});

it('shows the settings box with a link to the real public page', function () {
    $s = seedScenario(1);
    $s['property']->update(['slug' => 'seed-hotel-terms']);
    PropertyPolicy::create(['property_id' => $s['property']->id, 'pet' => 'allowed', 'smoking' => 'allowed', 'min_stay' => 1, 'booking_terms_extra' => 'Existing words.']);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/settings')->assertOk()
        ->assertSee('Your own booking terms')->assertSee('Existing words.')
        ->assertSee("/book/{$s['property']->slug}/terms", false)->assertSee('Preview exactly what guests see');
});

it('still opens Settings, with no preview link, for a property that has no public address yet (it must never 500)', function () {
    $s = seedScenario(1);
    $s['property']->update(['slug' => null]);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/settings')->assertOk()
        ->assertSee('Your own booking terms')->assertDontSee('Preview exactly what guests see');
});

/** Every field the rendered Policies form would submit, exactly as a browser sends it (hidden, text, number, ticked boxes/radios, selects, textareas). */
function renderedPoliciesFormFields(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($dom);
    $form = $xpath->query('//form[substring(@action, string-length(@action) - string-length("/app/settings/policies") + 1) = "/app/settings/policies"]')->item(0);
    expect($form)->not->toBeNull();

    $fields = [];
    foreach ($xpath->query('.//input[@name] | .//select[@name] | .//textarea[@name]', $form) as $el) {
        $name = $el->getAttribute('name');
        if ($el->nodeName === 'textarea') {
            $fields[$name] = $el->textContent;
        } elseif ($el->nodeName === 'select') {
            $chosen = $xpath->query('.//option[@selected]', $el)->item(0) ?? $xpath->query('.//option', $el)->item(0);
            $fields[$name] = $chosen?->getAttribute('value') ?? '';
        } elseif (in_array($el->getAttribute('type'), ['checkbox', 'radio'], true)) {
            if ($el->hasAttribute('checked')) {
                $fields[$name] = $el->getAttribute('value');
            }
        } else {
            $fields[$name] = $el->getAttribute('value');
        }
    }

    return $fields;
}

it('saves from the REAL Settings form exactly as the page renders it (the form once refused every save in silence)', function () {
    $s = seedScenario(1);
    $s['property']->update(['slug' => 'seed-hotel-real-form']);
    $manager = operatorUser($s, [], 'Property Owner');

    $fields = renderedPoliciesFormFields($this->actingAs($manager)->get('/app/settings')->getContent());
    // The page really has NO smoking field (the redesign dropped it) — the save must work without one.
    expect($fields)->not->toHaveKey('smoking')->toHaveKey('booking_terms_extra');

    $this->actingAs($manager)->post('/app/settings/policies', array_merge($fields, [
        'free_until_days' => '7', 'pet' => 'allowed', 'min_stay' => '2', 'booking_terms_extra' => 'Typed into the real form.',
    ]))->assertSessionHasNoErrors()->assertSessionHas('settings_notice');

    $policy = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail();
    expect($policy->booking_terms_extra)->toBe('Typed into the real form.')
        ->and((int) ($policy->cancellation['free_until_days'] ?? -1))->toBe(7)
        ->and($policy->pet)->toBe('allowed')
        ->and((int) $policy->min_stay)->toBe(2);
});

it('shows the reason when a Policies save is refused (never a silent failure)', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');

    $this->actingAs($manager)->followingRedirects()->from('/app/settings')
        ->post('/app/settings/policies', ['pet' => 'allowed', 'min_stay' => '0', 'booking_terms_extra' => 'x'])
        ->assertOk()->assertSee('role="alert"', false)->assertSee('min stay');
});

// ── 10. The guest's own page and the operator's reservation repeat the proof — only when there is one ────────
/** Book the scenario's room through the public checkout, with the terms accepted, and return the reservation. */
function acceptedBookingFor(array $s, string $email): Reservation
{
    config(['services.payments.gateway' => 'manual']);
    test()->post("/book/{$s['property']->slug}/checkout", termsCheckoutBody($s, ['email' => $email]))->assertRedirect();

    return Reservation::query()->latest('id')->firstOrFail();
}

it('shows a guest, on their own reservation page, the terms they accepted — and nothing on a booking with none on record', function () {
    $s = termsProperty();
    $reservation = acceptedBookingFor($s, 'ada.terms@guestmail.dev');

    // The booking's lead guest signs in (a claimed, verified guest account), exactly like the other guest-portal tests.
    $guest = $reservation->leadGuest;
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    $page = $this->actingAs($guest->fresh(), 'guest')->get(route('guest.reservation', $reservation->getKey()))->assertOk()
        ->assertSee('Booking terms accepted on')->assertSee('Free cancellation until 25 Aug 2026.')
        ->assertSee('After the free-cancellation date we charge the first night.');
    assertNoRawBlade($page);

    // Without an acceptance on record (an operator, OTA or older booking) the card is simply not there.
    $reservation->update(['terms_accepted_at' => null, 'terms_version' => null, 'terms_snapshot' => null]);
    $this->actingAs($guest->fresh(), 'guest')->get(route('guest.reservation', $reservation->getKey()))->assertOk()
        ->assertDontSee('Booking terms accepted on');
});

it('shows the operator, on the reservation, what the guest accepted — and nothing when none is on record', function () {
    $s = termsProperty();
    $reservation = acceptedBookingFor($s, 'ops.terms@guestmail.dev');
    $manager = operatorUser($s, [], 'Property Owner');

    $page = $this->actingAs($manager)->get(route('app.reservations.show', $reservation->getKey()))->assertOk()
        ->assertSee('Booking terms accepted on')->assertSee('Free cancellation until 25 Aug 2026.')
        ->assertSee($reservation->terms_version);
    assertNoRawBlade($page);

    $reservation->update(['terms_accepted_at' => null, 'terms_version' => null, 'terms_snapshot' => null]);
    $this->actingAs($manager)->get(route('app.reservations.show', $reservation->getKey()))->assertOk()
        ->assertDontSee('Booking terms accepted on');
});

it('renders nothing by itself for a booking with no acceptance on record, or an acceptance without its stored terms', function () {
    $s = termsProperty();
    $reservation = acceptedBookingFor($s, 'component.terms@guestmail.dev');

    // The component has its OWN guard (the screens that use it check too — defence in depth, so each layer is proven on its own).
    $render = fn (Reservation $r): string => trim(Blade::render('<x-booking.accepted-terms :reservation="$r" />', ['r' => $r]));

    expect($render($reservation->fresh()))->toContain('Booking terms accepted on');

    $reservation->update(['terms_accepted_at' => null, 'terms_version' => null, 'terms_snapshot' => null]);
    expect($render($reservation->fresh()))->toBe('');

    // Inconsistent state (a time but no stored terms) is shown as nothing — never a half-card or a crash.
    $reservation->update(['terms_accepted_at' => now(), 'terms_snapshot' => null]);
    expect($render($reservation->fresh()))->toBe('');
});
