<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\Review;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER FIX 1 — the two honesty flags + the completable-tax-form claim, from the owner deep audit
// (docs/audits/owner-portal-DEEP-parity-2026-08-17.md).
//
// ⚠️⚠️ THE AUDIT'S HIGHEST-IMPACT FINDING WAS WRONG, AND TEST 1 IS WHY IT IS RECORDED HERE RATHER THAN
// "FIXED". The audit reported that the 17-country residency select has no catch-all, that `Tax ID` is
// required, and therefore that an owner resident elsewhere "cannot complete the form — and their payout
// hold can never be released." The code says otherwise: `tax_country` is `nullable`, the select has
// always carried a blank option, and `Owner::hasTaxDetails()` — the ONE thing the payout hold consults —
// reads `tax_id` ALONE. So the form was always completable. What was actually wrong was the LABEL: a bare
// em-dash reads as "unset", not as "a choice you may leave". That is what changed.
//
// ⚠️ AND NO "Other" OPTION WAS ADDED, DELIBERATELY. `tax_country` is an ISO-code lookup key
// (TaxRateService::rateFor) and Countries::toCode() passes unrecognised values THROUGH — so "Other" would
// persist as a junk code that matches no configured rate: identical in behaviour to blank, but LOOKING
// like a recorded residency. Test 2 pins that absence so a future "helpful" addition reds.

const OHF_PW = 'password';

/** A real owner on a real scenario — resolved the way the app does, by `user_id`, never by a request id. */
function ohfOwner(): array
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $owner = Owner::query()->where('user_id', $user->getKey())->firstOrFail();

    return ['s' => $s, 'user' => $user, 'owner' => $owner];
}

// ── 1. ⚠️⚠️ THE FORM IS COMPLETABLE WITH NO RESIDENCY — and the hold releases on the tax id ALONE ──────
it('accepts a saved tax id with no residency selected, and that alone satisfies the payout-hold check', function () {
    $f = ohfOwner();

    // ⚠️ THE PREMISE, PROVEN NOT ASSUMED: with no tax id on file the hold genuinely applies.
    $f['owner']->forceFill(['tax_id' => null])->save();
    expect($f['owner']->fresh()->hasTaxDetails())->toBeFalse();

    // The exact payload an owner outside the 17 countries can produce: residency left blank.
    test()->actingAs($f['user'])
        ->post(route('owner.tax.save'), ['current_password' => 'password',
            'type' => 'individual',
            'tax_country' => '',          // ⚠️ not listed → left blank
            'tax_id' => 'XX-9988776',
            'legal_name' => 'Demo Owner',
        ])
        ->assertRedirect(route('owner.tax'));

    $owner = $f['owner']->fresh();

    // ⚠️ RAW: the id is stored, the residency is genuinely null, and the ONE predicate the payout hold
    // consults is now true — so the release is unblocked by a form with no residency in it.
    expect($owner->tax_id)->toBe('XX-9988776')
        ->and($owner->tax_country)->toBeNull()
        ->and($owner->hasTaxDetails())->toBeTrue();
});

// ── 2. ⚠️ THE SELECT SAYS BLANK IS A CHOICE — and offers NO junk catch-all code ────────────────────────
it('labels the blank residency option as a real choice and offers no Other catch-all', function () {
    $f = ohfOwner();

    $html = test()->actingAs($f['user'])->get(route('owner.tax'))->assertOk()->getContent();

    // The blank option is present AND explains itself — the em-dash alone was the whole defect.
    expect($html)->toContain('<option value="">Not listed — leave blank</option>');

    // ⚠️ A junk catch-all would silently match no configured tax rate while looking like a real residency.
    expect($html)->not->toContain('value="Other"')
        ->and($html)->not->toContain('value="OTHER"');

    // …and the real list is genuinely still offered, so this is not passing on an empty select.
    expect($html)->toContain('value="PT"')
        ->and($html)->toContain('Portugal');
});

// ── 3. ⚠️⚠️ REVIEWS — no fabricated causation, and the derived label survives ──────────────────────────
it('states the softest score without claiming rate proposals exist or factor reviews in', function () {
    $f = ohfOwner();
    $unit = $f['s']['units'][0];
    app(AvailabilityService::class)->recompute($f['s']['roomType']->id, '2026-06-01', '2026-06-30');

    // ⚠️ REAL reviews with REAL category ratings — `softest_label` is DERIVED, so without them the whole
    // block is correctly absent and every assertion below would pass for the wrong reason.
    foreach ([['2026-06-02', '2026-06-04', 5], ['2026-06-06', '2026-06-08', 4]] as [$in, $out, $rating]) {
        $res = app(AssignmentService::class)->createBooking(
            ['property_id' => $f['s']['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out,
                'lead_guest_id' => $f['s']['guest']->getKey(), 'source' => 'direct'],
            [['room_type_id' => $f['s']['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $f['s']['ratePlan']->id,
                'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
        );
        Review::create([
            'guest_id' => $f['s']['guest']->getKey(), 'reservation_id' => $res->getKey(),
            'unit_id' => $unit->getKey(), 'room_type_id' => $unit->room_type_id,
            'rating' => $rating, 'tags' => [], 'body' => 'A real stay.',
            // ⚠️ Value is deliberately the LOWEST, so there is a genuine softest category to name.
            'rating_cleanliness' => 5, 'rating_communication' => 5, 'rating_location' => 5, 'rating_value' => 3,
            'visibility' => 'public', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    $html = test()->actingAs($f['user'])->get(route('owner.reviews'))->assertOk()->getContent();

    // ⚠️ THE PREMISE: the derived part really renders, so the assertions below are not vacuous.
    expect($html)->toContain('is your softest score');

    // ⚠️ The two claims the system cannot back — that proposals EXIST, and that they factor reviews in.
    expect($html)->not->toContain('the proposed')
        ->and($html)->not->toContain('factor this in');

    // The Rates link stays as an ACTION, never as a claim about what is already happening.
    expect($html)->toContain(route('owner.rates'));
});

// ── 4. ⚠️⚠️ HELP — no live-health assertion, and no status board smuggled back in ──────────────────────
it('makes no operational-health claim on the help page', function () {
    $f = ohfOwner();

    $html = test()->actingAs($f['user'])->get(route('owner.help'))->assertOk()->getContent();

    // ⚠️ The residual sentence — a present-tense claim with nothing measuring it.
    expect($html)->not->toContain('are running normally');

    // ⚠️ …nor any of the board's furniture, which the audit credited as correctly refused.
    expect($html)->not->toContain('All systems operational')
        ->and($html)->not->toContain('Checked moments ago');

    // The card itself still renders — the fix reworded it, it did not delete the section.
    expect($html)->toContain('How support works');
});

// ── 5. ⚠️ MESSAGES — a client-side Copy text control, and no new server surface ────────────────────────
it('offers a copy-text control on a message without adding any route', function () {
    $f = ohfOwner();

    // ⚠️ A REAL thread with a REAL body — the control is per-message, so an empty inbox renders no controls
    // at all and the assertions below would be vacuous.
    $thread = MessageThread::create([
        'organization_id' => $f['s']['org']->id,
        'property_id' => $f['s']['property']->id,
        // ⚠️ AN OWNER THREAD IS `owner_id` SET / `guest_id` NULL — the two kinds are discriminated by that
        // XOR, and an owner genuinely cannot see a guest thread. My first fixture set `guest_id` and the
        // page rendered no messages at all: the scope was right and the test was wrong.
        'owner_id' => $f['owner']->getKey(),
        'channel' => 'manual',
        'subject' => 'A question for my operator',
        'status' => 'open',
        'last_message_at' => now(),
    ]);
    Message::create([
        'organization_id' => $f['s']['org']->id,
        'thread_id' => $thread->getKey(),
        'direction' => 'inbound',
        'body' => 'When does September settle?',
        'sent_at' => now(),
    ]);

    $html = test()->actingAs($f['user'])->get(route('owner.messages').'?c='.$thread->getKey())->assertOk()->getContent();

    // ⚠️ THE PREMISE: a real thread rendered, so an absent control would be a real absence.
    expect($html)->toContain('aria-label="Reply"');

    expect($html)->toContain('Copy text')
        // ⚠️ CLIENT-SIDE ONLY — it reads the clipboard API directly and posts nowhere.
        ->and($html)->toContain('navigator.clipboard.writeText');
});
