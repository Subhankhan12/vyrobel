<?php

use App\Models\Property;
use App\Support\Guest\PropertySupport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * THE GUEST SUPPORT BACKEND (2026-08-12) — the guards for the other half of DO-G13-1.
 *
 * The feature is three nullable per-property columns, an operator form that sets them, and a guest block that
 * displays them. What these tests defend is not the layout, it is the three properties that make it honest:
 *
 *   1. HONEST ABSENCE. A field that is not set draws NOTHING — not a blank, not "not provided", not a
 *      placeholder. This is the whole reason the founder ruled "build the backend first": the alternative is a
 *      page advertising a phone nobody answers. Every field is proven to disappear when nulled.
 *   2. THE NORM IS A NORM (§N.8d / DO-EMAIL-9). The guest reads "usually …", never a guarantee, and the
 *      operator cannot submit one — the stored value is a key into a fixed catalogue, so the sentence is
 *      written in code and a hand-posted guarantee is rejected by validation.
 *   3. THE TENANT PERIMETER IS UNCHANGED. An operator writes their OWN current property; a guest sees the
 *      support details only of properties they have actually booked.
 *
 * ⚠️ Assertions on absence read VISIBLE TEXT, not the raw response — CSS comments ship, and the sibling suite
 * already false-positived on the word "PHONE" inside a breakpoint heading.
 */

/** The visible text of a response: style/script stripped, tags removed, whitespace collapsed. */
function visibleText(string $html): string
{
    return trim((string) preg_replace('/\s+/', ' ',
        html_entity_decode(strip_tags((string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html)))
    ));
}

/** A claimable guest with a real booking at the scenario property. */
function guestWithStay(): array
{
    $scenario = seedScenario(1);
    $reservation = book($scenario);

    $guest = $scenario['guest'];
    $guest->update(['email' => 'support@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    return ['guest' => $guest->fresh(), 'property' => $scenario['property'], 'reservation' => $reservation];
}

it('shows ONLY the relay when the property has set no support details — the honest-absence case', function () {
    ['guest' => $guest, 'property' => $property] = guestWithStay();

    // The columns start NULL. Nothing seeds them — a default here would be a promise nobody made.
    expect($property->fresh()->support_phone)->toBeNull()
        ->and($property->fresh()->support_reply_norm)->toBeNull()
        ->and($property->fresh()->support_hours)->toBeNull();

    $response = $this->actingAs($guest, 'guest')->get(route('guest.help'));
    $response->assertOk();

    $html = (string) $response->getContent();
    $visible = visibleText($html);

    // The relay is unconditional and still there.
    expect($visible)->toContain('Still need help?');

    // ⚠️ And NOTHING else is claimed. No blank row, no "not provided", no dial link anywhere in the markup.
    // (An em-dash is NOT on this list: the FAQ prose is full of them, and a token that common tests the page's
    // punctuation rather than its honesty — the first cut of this guard fired on ordinary copy.)
    foreach (['Phone', 'Hours', 'usually', 'We usually', 'not provided', 'Not set'] as $forbidden) {
        expect(stripos($visible, $forbidden))->toBeFalse("an unset support field still rendered: “{$forbidden}”");
    }
    expect(stripos($html, 'tel:'))->toBeFalse()
        ->and(stripos($html, 'callto:'))->toBeFalse();

    // ⚠️ AND NO CARD AT ALL — not an empty one. The row-level guards above pass even when the card renders with
    // every row suppressed, which would still put a property-named panel on the page saying nothing: a
    // placeholder by any other name. This assertion is what makes PropertySupport::for()'s null return testable
    // (O32 — the first cut of this file mutated that return away and stayed GREEN, which is redundancy, not
    // proof). The stylesheet defines .vy-sup-*, so strip <style> before looking for the markup.
    $markup = (string) preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html);
    expect(stripos($markup, 'vy-sup'))->toBeFalse('an empty support card rendered for a property with nothing set');
});

it('renders each populated field — and ONLY the populated ones', function () {
    ['guest' => $guest, 'property' => $property] = guestWithStay();

    // Only hours. The other two stay null, so they must not appear.
    $property->forceFill(['support_hours' => 'Mon-Sat, 8am-8pm'])->save();

    $visible = visibleText((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent());

    expect($visible)->toContain('Mon-Sat, 8am-8pm')
        ->and($visible)->toContain('Hours')
        // ⚠️ The partially-configured state is the one a real operator lands in first, and it must not leak the
        // other two rows.
        ->and(stripos($visible, 'Phone'))->toBeFalse()
        ->and(stripos($visible, 'usually'))->toBeFalse();
});

it('⚠️ a field that is cleared DISAPPEARS from the guest page (the mutation the whole feature rests on)', function () {
    ['guest' => $guest, 'property' => $property] = guestWithStay();

    $property->forceFill([
        'support_phone' => '+351 21 000 0000',
        'support_reply_norm' => 'few_hours',
        'support_hours' => 'Mon-Sat, 8am-8pm',
    ])->save();

    $before = (string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent();
    $beforeVisible = visibleText($before);
    expect($beforeVisible)->toContain('+351 21 000 0000')
        ->and($beforeVisible)->toContain('We usually reply within a few hours.')
        ->and($beforeVisible)->toContain('Mon-Sat, 8am-8pm')
        ->and(stripos($before, 'tel:+351210000000'))->not->toBeFalse();

    // Null each one in turn; each affordance must vanish on its own.
    $property->forceFill(['support_phone' => null])->save();
    $noPhone = (string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent();
    expect(stripos(visibleText($noPhone), '+351 21 000 0000'))->toBeFalse()
        ->and(stripos($noPhone, 'tel:'))->toBeFalse()
        // …while its siblings are untouched, so this is the phone row and not the whole card.
        ->and(visibleText($noPhone))->toContain('Mon-Sat, 8am-8pm');

    $property->forceFill(['support_hours' => null])->save();
    expect(stripos(visibleText((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent()), 'Mon-Sat'))->toBeFalse();

    $property->forceFill(['support_reply_norm' => null])->save();
    $bare = visibleText((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent());
    expect(stripos($bare, 'usually'))->toBeFalse()
        // Back to the page as it shipped: the relay, and nothing claimed about it.
        ->and($bare)->toContain('Still need help?');

    // A whitespace-only value is as unset as NULL — otherwise an operator's stray space draws an empty row.
    $property->forceFill(['support_phone' => '   '])->save();
    expect(stripos((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent(), 'tel:'))->toBeFalse();
});

it('⚠️ renders the reply time as a NORM and never as a guarantee (§N.8d)', function () {
    ['guest' => $guest, 'property' => $property] = guestWithStay();

    // EVERY option in the catalogue, not a sample: an option that stopped saying "usually" would be the defect.
    foreach (PropertySupport::REPLY_NORMS as $key => $phrase) {
        $property->forceFill(['support_reply_norm' => $key])->save();

        $visible = visibleText((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent());

        // ⚠️ The WHOLE composed sentence, not the prefix. Asserting "We usually" passed while the page actually
        // read "We usually within a few hours." — a broken sentence the browser caught and this test did not.
        expect($visible)->toContain('We '.$phrase.'.');
        foreach (['guarantee', 'guaranteed', 'promise', 'always within', 'no later than', 'SLA'] as $forbidden) {
            expect(stripos($visible, $forbidden))->toBeFalse("the reply norm reads as a commitment: “{$forbidden}”");
        }
    }

    // The catalogue itself carries the rule, so a future option cannot smuggle a promise past the view.
    foreach (PropertySupport::REPLY_NORMS as $phrase) {
        expect($phrase)->toStartWith('usually');
    }

    // A stored key the catalogue does not define renders NOTHING — never the raw database token.
    $property->forceFill(['support_reply_norm' => 'within_one_hour_guaranteed'])->save();
    $visible = visibleText((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent());
    expect(stripos($visible, 'within_one_hour_guaranteed'))->toBeFalse()
        ->and(stripos($visible, 'usually'))->toBeFalse();
});

it('a guest sees support details ONLY for properties they have booked', function () {
    ['guest' => $guest] = guestWithStay();

    // A second property in the same org, fully configured, that this guest has never stayed at.
    $other = Property::create([
        'name' => 'Someone Else House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC',
        'support_phone' => '+44 20 7946 0000', 'support_reply_norm' => 'one_day', 'support_hours' => 'Always closed Tuesdays',
    ]);

    $visible = visibleText((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent());

    expect(stripos($visible, '+44 20 7946 0000'))->toBeFalse()
        ->and(stripos($visible, 'Someone Else House'))->toBeFalse()
        ->and(stripos($visible, 'Always closed Tuesdays'))->toBeFalse()
        ->and($other->fresh()->support_phone)->toBe('+44 20 7946 0000');   // …and it really was configured.
});

it('names the property on its card, so two desks are never merged into one number', function () {
    ['guest' => $guest, 'property' => $property] = guestWithStay();

    $property->forceFill(['support_phone' => '+351 21 000 0000'])->save();

    $visible = visibleText((string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent());

    // ⚠️ Scoped to the support card, not the page: the property name appears in plenty of guest chrome.
    $html = (string) $this->actingAs($guest, 'guest')->get(route('guest.help'))->getContent();
    $card = mb_substr($html, (int) mb_strpos($html, 'vy-sup-row'));
    expect($visible)->toContain((string) $property->name)
        ->and($card)->toContain('+351 21 000 0000');
});

it('the OPERATOR can set the support details on their own current property', function () {
    $scenario = seedScenario(1);
    $property = $scenario['property'];

    $manager = operatorUser($scenario, [], 'Property Owner');

    $response = $this->actingAs($manager)
        ->withSession(['current_property_id' => (string) $property->getKey()])
        ->post(route('app.settings.support'), [
            'support_phone' => '+351 21 000 0000',
            'support_reply_norm' => 'same_day',
            'support_hours' => 'Mon-Fri, 9am-6pm',
        ]);

    $response->assertRedirect(route('app.settings'));

    $fresh = $property->fresh();
    expect($fresh->support_phone)->toBe('+351 21 000 0000')
        ->and($fresh->support_reply_norm)->toBe('same_day')
        ->and($fresh->support_hours)->toBe('Mon-Fri, 9am-6pm');

    // ⚠️ AND CLEARING REALLY CLEARS. An emptied box withdraws the channel; if a blank were merely skipped the
    // guest page would keep advertising a number the operator just took down.
    $this->actingAs($manager)
        ->withSession(['current_property_id' => (string) $property->getKey()])
        ->post(route('app.settings.support'), ['support_phone' => '', 'support_reply_norm' => '', 'support_hours' => '']);

    $cleared = $property->fresh();
    expect($cleared->support_phone)->toBeNull()
        ->and($cleared->support_reply_norm)->toBeNull()
        ->and($cleared->support_hours)->toBeNull();
});

it('⚠️ REFUSES a reply time that is not one of the soft norms — a guarantee cannot be hand-posted', function () {
    $scenario = seedScenario(1);
    $property = $scenario['property'];
    $manager = operatorUser($scenario, [], 'Property Owner');

    $this->actingAs($manager)
        ->withSession(['current_property_id' => (string) $property->getKey()])
        ->post(route('app.settings.support'), ['support_reply_norm' => 'guaranteed within 1 hour'])
        ->assertSessionHasErrors('support_reply_norm');

    expect($property->fresh()->support_reply_norm)->toBeNull();

    // A sentence where a dialable number belongs is refused too — the view builds a tel: link from this column.
    $this->actingAs($manager)
        ->withSession(['current_property_id' => (string) $property->getKey()])
        ->post(route('app.settings.support'), ['support_phone' => 'call the front desk'])
        ->assertSessionHasErrors('support_phone');

    expect($property->fresh()->support_phone)->toBeNull();
});

it('⚠️ an operator can never write ANOTHER organisation’s property', function () {
    $theirs = seedScenario(1);          // a whole second org, seeded properly — NOT a bare fixture (non-vacuity)
    $foreignProperty = $theirs['property'];
    $foreignProperty->forceFill(['support_phone' => '+44 20 7946 0000'])->save();

    $mine = seedScenario(1);
    $manager = operatorUser($mine, [], 'Property Owner');

    // ⚠️ Naming the FOREIGN property in the session is the attack. The writer never accepts a request-supplied
    // id: it resolves the actor's CURRENT context, and PropertyContext::activate() normalises a selection that
    // is not in the actor's accessible set back to one that IS. So the write lands on the operator's OWN
    // property and the foreign row is untouched.
    $this->actingAs($manager)
        ->withSession(['current_property_id' => (string) $foreignProperty->getKey()])
        ->post(route('app.settings.support'), ['support_phone' => '+351 21 000 0000'])
        ->assertRedirect(route('app.settings'));

    expect($foreignProperty->fresh()->support_phone)->toBe('+44 20 7946 0000')   // unchanged
        ->and($mine['property']->fresh()->support_phone)->toBe('+351 21 000 0000');   // …and it did land somewhere
});

it('a user without properties.manage cannot reach the writer at all (#30)', function () {
    $scenario = seedScenario(1);
    $property = $scenario['property'];

    // Front Desk is a real back-office role that genuinely lacks properties.manage — the route gate is the check.
    $frontDesk = operatorUser($scenario, [], 'Front Desk');

    $this->actingAs($frontDesk)
        ->withSession(['current_property_id' => (string) $property->getKey()])
        ->post(route('app.settings.support'), ['support_phone' => '+351 21 000 0000'])
        ->assertForbidden();

    expect($property->fresh()->support_phone)->toBeNull();
});
