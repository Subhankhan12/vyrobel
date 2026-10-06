<?php

declare(strict_types=1);

use App\Models\AddOn;
use App\Models\EmailTemplate;
use App\Models\Guest;
use App\Models\PropertyPolicy;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE NEW RESERVATION PAGE, SECOND PASS (2026-10-04, the founder again: "it is not possible to do reservation … deeply
// analyze this page … make the design more cool, advanced, minimal and operational"). 🔬 Read-only on production: his working
// property has no rooms, and his other property has NEVER had a price — the €200 "Winter" season he saved on 28 Sep was never
// applied (a season writes nothing until Apply). Each test below failed first against the page as it stood.
// Test clock: 1 Aug 2026; the stay is 2–5 Sep (3 nights); availability is published for 1–10 Sep.

const NRB_STAY = '?check_in=2026-09-02&check_out=2026-09-05';

/** A scenario, priced (every night of September) or not. */
function nrbScenario(int $units = 2, ?int $nightlyMinor = 10000): array
{
    $s = seedScenario($units);
    if ($nightlyMinor !== null) {
        app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-30', Money::of($nightlyMinor, 'EUR'));
    }

    return $s;
}

function nrbPage(User $user, string $query = NRB_STAY, array $session = []): string
{
    return (string) test()->actingAs($user)->withSession($session)->get(route('app.reservations.create').$query)->assertOk()->getContent();
}

/** A saved, NEVER applied season on the scenario's plan — what he has on production. */
function nrbWinter(array $s, string $from = '2026-09-01', string $to = '2026-09-30', int $priceMinor = 20000): RateSeason
{
    return RateSeason::create(['rate_plan_id' => $s['ratePlan']->id, 'name' => 'Winter', 'starts_on' => $from, 'ends_on' => $to,
        'price_minor' => $priceMinor, 'currency' => 'EUR']);
}

/** The JSON block the wizard's script reads. */
function nrbBoot(string $html): array
{
    preg_match('~<script type="application/json" id="vy-nr-boot">(.*?)</script>~s', $html, $m);

    return json_decode($m[1] ?? 'null', true) ?? [];
}

// ── His report: a booking that cannot be made ───────────────────────────────────────────────────

it('⚠️⚠️ offers the properties that can take a booking when the chosen one has no rooms', function () {
    $s = nrbScenario(2);
    $empty = secondProperty($s, 'Internal Test Property');

    // 🔬 before: "has no rooms yet" and "choose it at the top" — a small dropdown he did not see; on production, the whole page
    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'), NRB_STAY, ['current_property_id' => (string) $empty->id]);
    expect($html)->toContain(e($empty->name).' has no rooms yet')   // the helper adds a suffix to the name
        ->toContain('Book into another property')
        ->toContain('name="property_id" value="'.$s['property']->id.'" class="vy-nr-other"')   // the button — not the top picker's option
        ->toContain('2 rooms')
        ->not->toContain('name="property_id" value="'.$empty->id.'" class="vy-nr-other"');     // never itself
});

it('⚠️⚠️ shows a saved season that covers the stay — and lets a rates person apply it right here', function () {
    $s = nrbScenario(2, null);
    $winter = nrbWinter($s);
    $apply = route('app.seasons.apply', $winter->id);

    // 🔬 before: "No price for these nights yet" and "e.g. 120" — the €200 he had saved was never mentioned
    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'));
    expect($html)->toContain('Winter')->toContain('€200.00 a night')->toContain('saved but not applied yet')
        ->toMatch('~<form[^>]*action="'.preg_quote($apply, '~').'"[^>]*>.*?name="return" value="new-reservation".*?name="return_check_in" value="2026-09-02".*?name="return_check_out" value="2026-09-05".*?</form>~s')
        ->toContain('Apply Winter')
        // ⚠️⚠️ THROUGH the Rates page's own disclosure (the review, H2): the button opens it; only its form applies
        ->toContain('Apply this season?')->toContain('This cannot be undone.')
        ->toContain('Apply and overwrite those nights')
        ->toContain('it prices 3 of the 3 nights of the stay that have no price');

    // someone who cannot manage rates is told who can — and is never handed the button
    $desk = nrbPage(operatorUser($s, [], 'Front Desk'));
    expect($desk)->toContain('saved but not applied yet')->toContain('Someone who manages rates can apply it')
        ->not->toContain('action="'.$apply.'"');
});

it('⚠️ applies the season from the booking and comes back on the same dates, ready to book', function () {
    $s = nrbScenario(2, null);
    $winter = nrbWinter($s);
    $manager = operatorUser($s, [], 'Hotel Manager');

    test()->actingAs($manager)->post(route('app.seasons.apply', $winter->id), ['return' => 'new-reservation',
        'return_check_in' => '2026-09-02', 'return_check_out' => '2026-09-05'])
        ->assertRedirect(route('app.reservations.create', ['check_in' => '2026-09-02', 'check_out' => '2026-09-05']))
        // the Rates page's own sentence of what was written, then what the stay still lacks — counted after the write (M1)
        ->assertSessionHas('new_reservation_ok', 'Winter applied — 30 nights rewritten across 2 units. Pick a unit to continue.');

    // the Rates page's own apply ran — the nights carry the price, the season is marked applied (R-SEASON untouched)
    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->whereBetween('date', ['2026-09-02', '2026-09-04'])->pluck('price_minor')->all())
        ->toBe([20000, 20000, 20000])
        ->and($winter->fresh()->applied_at)->not->toBeNull();
    expect(nrbPage($manager))->toContain('€200.00 a night');
});

it('refuses the season from the booking while the workspace is paused — back on the booking, nothing written', function () {
    $s = nrbScenario(2, null);
    $winter = nrbWinter($s);
    pauseWorkspace($s);

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.seasons.apply', $winter->id), ['return' => 'new-reservation',
        'return_check_in' => '2026-09-02', 'return_check_out' => '2026-09-05'])
        ->assertRedirect(route('app.reservations.create', ['check_in' => '2026-09-02', 'check_out' => '2026-09-05']))
        ->assertSessionHas('new_reservation_notice', 'Your workspace is read-only right now — rate changes are paused.');

    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->count())->toBe(0)
        ->and($winter->fresh()->applied_at)->toBeNull();
});

// ── Only true sentences ─────────────────────────────────────────────────────────────────────────

it('⚠️ never invents a house rule — what the property has not set says “Not set”', function () {
    $s = nrbScenario(2);

    // 🔬 before: "Pets: Not permitted", "Smoking: Not permitted" for a property that never set either — and "these go out with
    //    the guest's confirmation", which they do not
    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'));
    expect($html)->toContain('<span>Pets</span><strong>Not set</strong>')
        ->toContain('<span>Smoking</span><strong>Not set</strong>')
        ->not->toContain('Not permitted')
        ->not->toContain('go out with the guest’s confirmation');
});

it('⚠️ shows the house rules a property did set in words — and its real free-cancellation date for the stay', function () {
    $s = nrbScenario(2);
    PropertyPolicy::create(['property_id' => $s['property']->id, 'pet' => 'on_request', 'smoking' => 'not_allowed', 'min_stay' => 2,
        'cancellation' => ['type' => 'flexible', 'free_until_days' => 2]]);

    // 🔬 before: the stored codes themselves ("on_request", "not_allowed"), and "As per booking terms" over a real policy
    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'));
    expect($html)->toContain('<span>Pets</span><strong>On request</strong>')
        ->toContain('<span>Smoking</span><strong>Not allowed</strong>')
        ->toContain('<span>Minimum stay</span><strong>2 nights</strong>')
        ->toContain('<span>Cancellation</span><strong>Free until 31 Aug</strong>')   // 2 Sep less the policy's 2 days
        ->not->toContain('>on_request<')->not->toContain('As per booking terms');
});

it('says how directions really reach the guest — never that the confirmation carries them', function () {
    $s = nrbScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 🔬 before: "The confirmation includes directions and parking" (it says they come 48 h before) and "a transfer extra in
    //    step 3" on a property with no extras at all
    $html = nrbPage($manager);
    expect($html)->not->toContain('The confirmation includes directions and parking')
        ->not->toContain('A pickup is a transfer extra in step 3')
        // the suite's mailer reaches no one — as production's does today: said, never a promise of an email
        ->toContain('Email isn’t being sent from Vyrobel yet — give the guest directions and the door code yourself.');

    // where email does reach people, the pre-arrival email's own rule is said (the review, M3)
    config(['mail.default' => 'postmark']);
    expect(nrbPage($manager))->toContain('reach the guest by email 2 days before check-in')
        ->not->toContain('The confirmation includes directions and parking');   // where the claim used to sit — NB-M11
    expect(nrbPage($manager, '?check_in=2026-08-02&check_out=2026-08-04'))
        ->toContain('This stay starts too soon for the pre-arrival email, which goes 2 days before check-in');
    EmailTemplate::create(['template_key' => 'pre-arrival', 'enabled' => false]);
    expect(nrbPage($manager))->toContain('The pre-arrival email is switched off for your properties');
});

it('⚠️ shows the self check-in code only to a guest who can sign in to use it', function () {
    $s = nrbScenario(2);
    $desk = operatorUser($s, [], 'Front Desk');
    $post = fn (array $extra) => test()->actingAs($desk)->post(route('app.reservations.create'), array_merge(['check_in' => '2026-09-02',
        'check_out' => '2026-09-05', 'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'guest_first_name' => 'Ana',
        'guest_last_name' => 'Walk', 'adults' => '2', 'children' => '0', 'source' => 'direct'], $extra));

    // 🔬 before: a QR "Ana Walk scans to open online check-in — in the guest portal (they sign in first)" for a guest with no
    //    email — the guest portal is claimed BY email, so that code led nowhere
    // each booking's id from its own redirect — the suite's clock is frozen, so two bookings share a created_at
    $created = fn ($response): string => (string) Str::after((string) $response->assertRedirect()->headers->get('Location'), 'created=');

    $noEmail = $created($post([]));
    $html = nrbPage($desk, '?created='.$noEmail);
    expect($html)->toContain('Reservation confirmed')->not->toContain('Self check-in code')
        ->toContain('can check in online once they have an email address');

    $withEmail = $created($post(['unit_id' => $s['units'][1]->id, 'guest_email' => 'ana.walk@example.org']));
    expect(Reservation::query()->whereKey($withEmail)->exists())->toBeTrue()
        ->and(nrbPage($desk, '?created='.$withEmail))->toContain('Self check-in code');
});

it('keeps the “price set” note on the first step, where the unit is picked', function () {
    $s = nrbScenario(2);

    // 🔬 before: "Price set … Pick a unit to continue." stayed above every step, all the way to payment
    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'), NRB_STAY, ['new_reservation_ok' => 'Price set — €120.00 a night for these 3 nights. Pick a unit to continue.']);
    expect($html)->toMatch('~<section class="vy-nr-card"[^>]*x-show="step === 1"[^>]*>(?:(?!</section>).)*Price set — €120\.00 a night~s')
        ->and(substr_count($html, 'Price set — €120.00 a night'))->toBe(1);   // there, and only there
});

it('does not offer to email the confirmation to a guest with no email address', function () {
    $s = nrbScenario(2);

    // 🔬 before: "Email the confirmation to the guest" ticked, with no email anywhere
    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'));
    expect($html)->toContain('x-bind:disabled="! canEmail()"')
        ->toContain('Needs the guest’s email address');
});

it('knows which returning guests can be emailed — and never puts an address on the page (#28)', function () {
    $s = nrbScenario(2);
    $withEmail = Guest::create(['first_name' => 'Lena', 'last_name' => 'Mailable', 'email' => 'lena.mailable@example.org']);
    $without = Guest::create(['first_name' => 'Lena', 'last_name' => 'Unmailable']);

    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'), NRB_STAY.'&q=Lena');
    expect(nrbBoot($html)['guestEmails'] ?? [])->toBe([(string) $withEmail->id => true, (string) $without->id => false])
        ->and($html)->not->toContain('lena.mailable@example.org');
});

it('⚠️ keeps a returning guest’s confirmation when their booking comes back refused (the review, H1)', function () {
    $s = nrbScenario(2);
    $guest = Guest::create(['first_name' => 'Lena', 'last_name' => 'Back', 'email' => 'lena.back@example.org']);

    // 🔬 before: a refusal returns without the search, so a restored returning guest read as having no email — the box went
    //    disabled, posted "0", and the confirmation was silently dropped
    $html = (string) test()->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->withSession(['_old_input' => ['room_type_id' => (string) $s['roomType']->id, 'unit_id' => (string) $s['units'][0]->id,
            'existing_guest_id' => (string) $guest->id, 'send_confirmation' => '1'], 'new_reservation_step' => 4])
        ->get(route('app.reservations.create').NRB_STAY)->assertOk()->getContent();
    $boot = nrbBoot($html);
    expect($boot['guestEmails'][(string) $guest->id] ?? null)->toBeTrue()
        ->and($boot['restore']['sendConfirmation'] ?? null)->toBeTrue()
        ->and($html)->not->toContain('lena.back@example.org');
});

it('offers only a season that would price a night still without one — and says how many', function () {
    $s = nrbScenario(2, null);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-02', '2026-09-03', Money::of(15000, 'EUR'));   // 2 Sep priced
    nrbWinter($s, '2026-09-01', '2026-09-02', 20000);   // only over the priced night — fixes nothing
    nrbWinter($s, '2026-09-04', '2026-09-30', 20000);   // over 4 Sep, one of the two without a price

    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'));
    expect(substr_count($html, 'saved but not applied yet'))->toBe(1)
        ->and($html)->toContain('It would price 1 of the 2 nights that have none.');
});

it('says what still lacks a price after a season that covers only part of the stay', function () {
    $s = nrbScenario(2, null);
    $winter = nrbWinter($s, '2026-09-04', '2026-09-30');

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.seasons.apply', $winter->id), ['return' => 'new-reservation',
        'return_check_in' => '2026-09-02', 'return_check_out' => '2026-09-05'])
        ->assertSessionHas('new_reservation_ok', 'Winter applied — 27 nights rewritten across 2 units. 2 nights of the stay still have no price.');
});

it('words a free-cancellation window that has already closed, and one that lasts until check-in', function () {
    $s = nrbScenario(2);
    $policy = PropertyPolicy::create(['property_id' => $s['property']->id, 'cancellation' => ['type' => 'flexible', 'free_until_days' => 2]]);

    // 🔬 before: "Free until 31 Jul" shown on 1 Aug for a stay arriving 2 Aug
    expect(nrbPage(operatorUser($s, [], 'Hotel Manager'), '?check_in=2026-08-02&check_out=2026-08-04'))
        ->toContain('<span>Cancellation</span><strong>Not free for this stay — it ended 31 Jul</strong>');

    $policy->update(['cancellation' => ['type' => 'flexible', 'free_until_days' => 0]]);
    expect(nrbPage(operatorUser($s, [], 'Hotel Manager'), '?check_in=2026-08-02&check_out=2026-08-04'))
        ->toContain('<span>Cancellation</span><strong>Free until check-in</strong>');
});

// ── Fewer steps, more on screen ─────────────────────────────────────────────────────────────────

it('⚠️ walks four steps — and asks about extras only where the property has some', function () {
    $s = nrbScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 🔬 before: seven steps, two of them nearly empty here (no extras; one optional arrival box)
    $html = nrbPage($manager);
    expect($html)->toContain('aria-label="Step 1: Stay"')->toContain('aria-label="Step 2: Guest"')
        ->toContain('aria-label="Step 3: Confirm"')->not->toContain(': Extras"')
        ->toContain('Step 1 of 3')->not->toContain('of 7');

    AddOn::create(['property_id' => $s['property']->id, 'name' => 'Breakfast', 'price_minor' => 1500, 'currency' => 'EUR',
        'is_active' => true, 'taxable' => false]);
    expect(nrbPage($manager))->toContain('aria-label="Step 3: Extras"')->toContain('aria-label="Step 4: Confirm"')->toContain('Step 1 of 4');
});

it('reopens a refused booking at the step that holds what was refused', function () {
    $s = nrbScenario(2);

    // a card payment is refused at the Confirm step (4), a missing guest at the Guest step (2)
    test()->actingAs(operatorUser($s, [], 'Front Desk'))->post(route('app.reservations.create'), ['check_in' => '2026-09-02',
        'check_out' => '2026-09-05', 'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'guest_first_name' => 'Ana',
        'adults' => '2', 'source' => 'direct', 'payment_method' => 'card', 'payment_amount' => '50'])
        ->assertSessionHas('new_reservation_step', 4);
    test()->actingAs(operatorUser($s, [], 'Front Desk'))->post(route('app.reservations.create'), ['check_in' => '2026-09-02',
        'check_out' => '2026-09-05', 'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'adults' => '2', 'source' => 'direct'])
        ->assertSessionHas('new_reservation_step', 2);
});

it('reopens at Confirm when a refused booking’s step is one this property does not ask', function () {
    $s = nrbScenario(2);

    // a refusal flashed at the Extras step (3) — on a property with no extras the booking reopens at Confirm, never on nothing
    $html = (string) test()->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->withSession(['_old_input' => ['room_type_id' => (string) $s['roomType']->id, 'unit_id' => (string) $s['units'][0]->id, 'guest_first_name' => 'Ana'],
            'new_reservation_step' => 3])
        ->get(route('app.reservations.create').NRB_STAY)->assertOk()->getContent();
    expect($html)->toContain('Step 3 of 3')
        ->toMatch('~<section class="vy-nr-card" x-show="step === 4"\s+aria-labelledby="vy-nr-h4">~');   // not cloaked: it is the step shown
});

it('⚠️ shows each night’s price and the rooms left on the calendar — from the same rows the booking uses', function () {
    $s = nrbScenario(2);
    RatePlan::query()->whereKey($s['ratePlan']->id)->update(['is_active' => true]);

    $boot = nrbBoot(nrbPage(operatorUser($s, [], 'Hotel Manager')));
    expect($boot['nightly']['2026-09-02'] ?? null)->toBe(['p' => 10000, 'f' => 2])
        ->and($boot['nightly'])->not->toHaveKey('2026-08-15')   // no availability published that night: nothing claimed
        ->and($boot['today'] ?? null)->toBe('2026-08-01');

    // "today" is the PROPERTY's day: 09:00 UTC on 1 Aug is still 31 Jul in Honolulu
    $s['property']->update(['timezone' => 'Pacific/Honolulu']);
    expect(nrbBoot(nrbPage(operatorUser($s, [], 'Hotel Manager')))['today'] ?? null)->toBe('2026-07-31');

    // a night with rooms and no price says so (null), rather than hiding the night
    RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->where('date', '2026-09-03')->delete();
    expect(nrbBoot(nrbPage(operatorUser($s, [], 'Hotel Manager')))['nightly']['2026-09-03'] ?? 'missing')->toBe(['p' => null, 'f' => 2]);
});

it('lists countries by name — and still sends the two-letter code the booking stores', function () {
    $s = nrbScenario(2);

    // 🔬 before: a bare box, "optional · 2 letters, e.g. PT"
    $html = nrbPage(operatorUser($s, [], 'Hotel Manager'));
    expect($html)->toContain('<select id="gc" name="guest_country"')
        // names from ICU (`ext-intl`); GitHub's image leaves it out on purpose (DEPLOYMENT §7.5.1b) — there each option is its code
        ->toMatch(extension_loaded('intl') ? '~<option value="PT"\s*>Portugal</option>~' : '~<option value="PT"\s*>PT</option>~')   // @selected leaves its space when false
        ->toMatch(extension_loaded('intl') ? '~<option value="AZ"\s*>Azerbaijan</option>~' : '~<option value="AZ"\s*>AZ</option>~')
        ->not->toContain('2 letters, e.g. PT');
});
