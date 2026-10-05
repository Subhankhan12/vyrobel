<?php

declare(strict_types=1);

use App\Livewire\Operator\GuestsList;
use App\Mail\OperatorGuestMessageMail;
use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE GUESTS PAGE, SECOND PASS (2026-10-05, the founder: "on this page there is design issue. Text is not visible completely.
// Serach box design is not good and its symbol is not well-aligned … the same work … as the previous pages"). 🔬 Measured locally on
// a copy of real shapes (his production workspace holds one guest): below ~1,180 px the table was wider than its card — Status cut to
// "No booki…", on a phone the Nationality column under the row menu ("Spai", "Unite Kingdom") — emails broke mid-word, "No bookings"
// and "United Kingdom" wrapped; the search box sat squeezed between the chips and the buttons with an "i" pressed against its edge.
// And read: "Message" promised "Sent by email" while production's mailer is `log`. Each test failed first against the page as it stood.
// Test clock: 1 Aug 2026.

function gp2Guest(string $first, string $last, array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => $first, 'last_name' => $last], $extra));
}

/** A booking with this lead guest on the scenario's room type — its own dates, availability published for them first. */
function gp2Stay(array $s, Guest $guest, string $in, string $out): Reservation
{
    app(AvailabilityService::class)->recompute((string) $s['roomType']->id, $in, $out);

    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $guest->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
    );
}

/** A booking of $status on its OWN unit — book() and then this fixture's dates and status, as GuestsListRedesignTest's cast does. */
function gp2At(array $s, int $unit, Guest $guest, string $status, string $in, string $out): void
{
    $r = book($s, ['unit_id' => $s['units'][$unit]->id]);
    Reservation::query()->whereKey($r->getKey())->update(['lead_guest_id' => $guest->id, 'status' => $status, 'check_in' => $in, 'check_out' => $out]);
}

/** @return array<string, ?string> each listed guest's Stay cell, by name, in the order the page lists them */
function gp2StayCells(array $s, array $params = []): array
{
    $rows = Livewire::actingAs(operatorUser($s))->withQueryParams($params)->test(GuestsList::class)->viewData('rows');

    return collect($rows->items())->mapWithKeys(fn (array $r): array => [$r['name'] => $r['last_stay']])->all();
}

function gp2Page(array $s, string $role = 'Hotel Manager', string $query = ''): string
{
    return (string) test()->actingAs(operatorUser($s, [], $role))->get(route('app.guests').$query)->assertOk()->getContent();
}

function gp2Css(): string
{
    return (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
}

// ── His report: text not visible completely ─────────────────────────────────────────────────────────────────────

it('⚠️⚠️ lets the table fit its card at every width — a narrow card stacks each guest instead of hiding columns', function () {
    $s = seedScenario(1);
    gp2Guest('Ines', 'Moreira', ['email' => 'ines@example.test', 'nationality' => 'PT']);
    $html = gp2Page($s);

    // 🔬 before: a 760 px table in a 732 px card at 1,024 px — Status cut to "No booki…"; on a phone, Nationality under the menu
    expect($html)->toContain('<td class="vy-gl-c-nat" data-label="Nationality">')
        ->toContain('<td class="is-num vy-gl-c-stays" data-label="Stays">')
        ->toContain('<td class="vy-gl-c-stay" data-label="Stay">')
        ->toContain('<td class="is-num vy-gl-c-life" data-label="Lifetime">')
        ->toContain('<td class="vy-gl-c-status" data-label="Status">');

    // the card is a size container, and a narrow one turns each row into a stacked card — no sideways scrolling, nothing hidden
    expect(gp2Css())->toMatch('~\.vy-gst \.vy-gl-card \{[^}]*container: vy-gst-list / inline-size~')
        ->toMatch('~@container vy-gst-list \(max-width: 960px\) \{[^@]*?\.vy-gst \.vy-gl-table tbody tr \{[^}]*display: flex; flex-wrap: wrap~')
        // ⚠️ the review: without this the shared 760 px floor stays, and a stacked row on a phone is 760 px wide again
        ->toContain('.vy-gst .vy-gl-table { min-width: 0; }');
});

it('⚠️ on a phone moves the status down beside the facts, and hides the Lifetime cell only when it holds nothing', function () {
    $css = gp2Css();
    expect($css)->toMatch('~@container vy-gst-list \(max-width: 520px\) \{[\s\S]*?\.vy-gst \.vy-gl-table tbody td\.vy-gl-c-status \{[^}]*order: 5~')
        // 🔬 the review: a currency with a balance due and nothing billed yet showed only "… due" — and that was hidden too
        ->toContain('.vy-gst .vy-gl-table tbody td.vy-gl-c-life:not(:has(.vy-gl-fig, .vy-gl-due)) { display: none; }');
});

it('⚠️ writes the stacked rows’ labels in the darker grey — the lighter one measured 4.48:1 on a row, under AA', function () {
    $css = gp2Css();
    expect($css)->toMatch('~\.vy-gst \.vy-gl-table thead tr::before \{[^}]*content: "Sort by";[^}]*color: var\(--vy-on-grad\)~')
        ->toMatch('~\.vy-gst \.vy-gl-table tbody td\[data-label\]::before \{[^}]*color: var\(--vy-on-grad\)~');
});

it('keeps a status, a nationality and a date on one line', function () {
    // 🔬 before: "No bookings" and "United Kingdom" broke over two lines
    expect(gp2Css())->toMatch('~\.vy-gst \.vy-gl-c-nat, \.vy-gst \.vy-gl-c-status, \.vy-gst \.vy-gl-c-stay \{[^}]*white-space: nowrap~');
});

it('⚠️ stacks before the full table can outgrow its card — and lets only a long country name wrap (the review, then measured)', function () {
    $s = seedScenario(1);
    gp2Guest('Emina', 'Hadzic', ['nationality' => 'BA']);    // "Bosnia & Herzegovina" — 20 characters
    gp2Guest('Tom', 'Hughes', ['nationality' => 'GB']);      // "United Kingdom" — 14, stays on one line
    $html = gp2Page($s);

    // 🔬 before: at 1,180–1,230 px windows (a card of 890–940 px) the full table measured 939 px, 969 with Select on — so it
    //    scrolled sideways and up to 16 cells passed the card's edge, just above where the rows stacked (880 px)
    expect(gp2Css())->toContain('@container vy-gst-list (max-width: 960px) {')
        // …and only where it must: a wrap allowed at every width was taken at 1,440 px too (🔬 the table shares its shrinking out)
        ->toMatch('~@container vy-gst-list \(max-width: 1080px\) \{\s*\.vy-gst \.vy-gl-c-nat\.is-long \{ white-space: normal; \}\s*\}~');
    expect($html)->toContain('<td class="vy-gl-c-nat is-long" data-label="Nationality">')
        ->toMatch('~<td class="vy-gl-c-nat" data-label="Nationality">(?:<!--\[if BLOCK\]><!\[endif\]-->)?\s*United Kingdom~');
});

it('⚠️ puts email and phone each on its own line — an email breaks only when it must, and no separator is left dangling', function () {
    $s = seedScenario(1);
    gp2Guest('Ines', 'Moreira', ['email' => 'demo.harbour.guest@harbourpoint-demo.test', 'phone' => '+351 21 000 0003']);
    $html = gp2Page($s);

    // 🔬 before: "demo.harbour.guest@harbourp / oint-demo.test ·" and the phone under it
    expect($html)->toMatch('~<div class="vy-gl-contact"><a class="vy-gl-mail" href="mailto:demo\.harbour\.guest@harbourpoint-demo\.test">demo\.harbour\.guest@harbourpoint-demo\.test</a><span class="vy-gl-phone">\+351 21 000 0003</span></div>~')
        ->not->toContain('vy-gl-sep');
    expect(gp2Css())->toMatch('~\.vy-gst \.vy-gl-contact \{[^}]*flex-direction: column~');
});

it('⚠️ stacks the contact lines on this page only — the Units page’s "Name · Door code set · Wi-Fi" line stays one line', function () {
    // 🔬 the review: the first version was unscoped, and stacked the Units sub-line into "Name / · Door code set / · Wi-Fi"
    expect(gp2Css())->not->toMatch('~(?:^|\n)[ \t]*\.vy-gl-contact \{[^}]*flex-direction: column~')
        ->not->toMatch('~(?:^|\n)[ \t]*\.vy-gl-mail \{~')
        ->toContain('.vy-ul-table .vy-gl-contact > span + span::before { content: "·"; margin: 0 6px; }');
});

// ── His report: the search box and its symbol ───────────────────────────────────────────────────────────────────

it('⚠️⚠️ is one clean search field — no button pressed into its edge, the browser’s own search styling switched off', function () {
    $s = seedScenario(1);
    $html = gp2Page($s);

    // 🔬 before: an "i" button 6 px from the field's right edge, and Safari's own search field (appearance) left on
    expect($html)->not->toContain('vy-gl-tip-btn')->not->toContain('id="vy-gl-tip"')
        ->toContain('placeholder="Search name, city or exact email"');
    expect(gp2Css())->toMatch('~\.vy-gl-search-in \{[^}]*-webkit-appearance: none; appearance: none~')
        ->toContain('.vy-gl-search-in::-webkit-search-decoration');
});

it('still says what search cannot find — where it matters, when nothing matched', function () {
    $s = seedScenario(1);
    gp2Guest('Ines', 'Moreira');

    expect(gp2Page($s, 'Hotel Manager', '?q=nobody-here'))->toContain('phone and partial email are encrypted at rest, so they cannot be searched');
});

it('⚠️ lays the toolbar out like the other lists — search and the buttons on top, the filters below', function () {
    $s = seedScenario(1);
    $html = gp2Page($s);

    // 🔬 before: search, chips and buttons in one row — the search squeezed, the buttons wrapping alone onto a second line
    expect($html)->toContain('<div class="vy-gl-bar vy-gs-bar vy-fade">');
    $search = strpos($html, '<form class="vy-gl-search"');
    $tools = strpos($html, '<div class="vy-gl-tools">');
    $chips = strpos($html, '<div class="vy-gl-chips"');
    expect($search)->toBeLessThan($tools)->and($tools)->toBeLessThan($chips);
});

// ── Honest words ──────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ will not say a message was sent while email is not sent from Vyrobel — the dialog says so, the server refuses', function () {
    Mail::fake();
    $s = seedScenario(1);
    $ada = gp2Guest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    // the test mailer is not a real one — as production's `log` is not (Verified 2026-10-05: mailer `log`)
    $html = gp2Page($s);
    expect($html)->toContain('Email isn’t being sent from Vyrobel yet, so a message can’t reach anyone from here.')
        ->not->toContain('Sent by email')
        ->toMatch('~<button type="submit" class="vy-btn vy-btn--accent" disabled><span>Send to~')
        // the message box and the marketing switch are off with it — nothing to type into that cannot go anywhere
        ->toMatch('~<textarea id="vy-gl-msg-body"[^>]*autofocus disabled></textarea>~')
        ->toContain('<input type="checkbox" name="marketing" value="1" x-model="marketing" disabled>');

    // 🔬 before: "1 guest messaged." — nothing left the building, and each guest's inbox recorded a message as sent
    test()->actingAs(operatorUser($s))->post(route('app.guests.bulk.message'), ['ids' => [$ada->id], 'body' => 'Your room is ready.'])
        ->assertRedirect(route('app.guests'))
        ->assertSessionHas('guest_error', 'Email isn’t being sent from Vyrobel yet — nothing was sent.');
    Mail::assertNothingSent();
    expect(MessageThread::query()->where('guest_id', $ada->id)->exists())->toBeFalse();
});

it('⚠️⚠️ sends as the property when email is sent — and says where replies go: its reply-to address (the review)', function () {
    Mail::fake();
    config(['mail.default' => 'postmark']);   // a real mailer, set after boot (boot downgrades it outside production)
    $s = seedScenario(1);
    $s['property']->forceFill(['brand_reply_to' => 'stay@seaside.test', 'brand_email_from_name' => 'Seaside Stays'])->save();
    $ada = gp2Guest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    // 🔬 before: "replies land in your inbox" — no email reply ever does (no inbound mail); a reply went to Vyrobel's own sender
    expect(gp2Page($s))->toContain('Sent by email as '.e($s['property']->name).'. Replies go to stay@seaside.test.')
        ->not->toContain('replies land in your inbox')->not->toContain('Email isn’t being sent from Vyrobel yet');

    test()->actingAs(operatorUser($s))->post(route('app.guests.bulk.message'), ['ids' => [$ada->id], 'body' => 'Your room is ready.'])
        ->assertRedirect(route('app.guests'))->assertSessionHas('guest_notice', '1 guest messaged.');
    Mail::assertSent(OperatorGuestMessageMail::class, fn (OperatorGuestMessageMail $m): bool => $m->hasTo('ada@example.test')
        && $m->hasReplyTo('stay@seaside.test') && $m->hasFrom((string) config('mail.from.address'), 'Seaside Stays'));
});

it('⚠️ says a reply will not reach the operator while the property has no reply-to address', function () {
    Mail::fake();
    config(['mail.default' => 'postmark']);
    $s = seedScenario(1);
    $ada = gp2Guest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    // 🔬 the second review: Hotel Manager, Front Desk and Reservation Agent may send but cannot open Settings (`properties.manage`) —
    //    "add a reply-to address in Settings" sent three of the five roles that can send to a page that refuses them
    expect(gp2Page($s, 'Property Owner'))->toContain('Sent by email as '.e($s['property']->name).'. Replies won’t reach you yet — add a reply-to address in Settings → Guest-facing brand.');
    expect(gp2Page($s, 'Hotel Manager'))->toContain('Replies won’t reach you yet — ask someone who manages property settings to add a reply-to address.')
        ->not->toContain('add a reply-to address in Settings');

    test()->actingAs(operatorUser($s))->post(route('app.guests.bulk.message'), ['ids' => [$ada->id], 'body' => 'Your room is ready.'])
        ->assertSessionHas('guest_notice', '1 guest messaged.');
    Mail::assertSent(OperatorGuestMessageMail::class, fn (OperatorGuestMessageMail $m): bool => $m->hasTo('ada@example.test') && $m->envelope()->replyTo === []);
});

it('⚠️ the email invites a reply only when the reply reaches the property', function () {
    $s = seedScenario(1);

    // 🔬 before: "Just reply to this email and we’ll see it" in every message — with no reply-to, the reply reached Vyrobel's sender
    expect((new OperatorGuestMessageMail('Ada', 'Your room is ready.', 'Seaside', null, null, $s['property']))->render())
        ->not->toContain('Just reply to this email');

    $s['property']->forceFill(['brand_reply_to' => 'stay@seaside.test'])->save();
    expect((new OperatorGuestMessageMail('Ada', 'Your room is ready.', 'Seaside', null, null, $s['property']->fresh()))->render())
        ->toContain('Just reply to this email and we’ll see it — Seaside.');
});

it('⚠️ counts repeat guests among guests with a booking — never "who have stayed" for a stay still ahead', function () {
    $s = seedScenario(1);
    $future = gp2Guest('Fern', 'Future');
    gp2Stay($s, $future, '2026-09-02', '2026-09-04');   // the clock is 1 Aug — this stay has not happened

    // 🔬 before: "0 of 1 guest who have stayed booked again" — about a guest who has not arrived
    $html = gp2Page($s);
    expect($html)->toMatch('~0 of 1 guest\s+with a booking\s+booked again~')->not->toContain('who have stayed');
});

it('⚠️ says why there is no repeat figure without claiming nobody ever booked (the review)', function () {
    $s = seedScenario(1);
    $gone = gp2Guest('Cara', 'Cancelled');
    gp2At($s, 0, $gone, 'cancelled', '2026-09-02', '2026-09-04');

    // 🔬 before: "nobody has booked yet" — Cara booked, then cancelled; a cancelled booking is not counted, so say that
    expect(gp2Page($s))->toContain('no bookings yet — cancellations and no-shows don’t count')->not->toContain('nobody has booked yet');
});

it('⚠️ heads the stay column "Stay" — it shows an arrival ahead as well as a stay behind', function () {
    $s = seedScenario(2);
    gp2At($s, 0, gp2Guest('Paula', 'Past'), 'checked_out', '2026-07-20', '2026-07-22');
    gp2At($s, 1, gp2Guest('Nico', 'Next'), 'confirmed', '2026-09-02', '2026-09-04');

    // 🔬 before: a column headed "Last stay" showing "Arrives 2 Sep 2026", and a past stay as a bare date
    $html = gp2Page($s);
    expect($html)->toMatch('~data-gl-key="sort-last_stay">Stay<span~')
        ->toContain('Left 22 Jul 2026')->toContain('Arrives 2 Sep 2026')->not->toContain('>Last stay<');
});

it('⚠️⚠️ says where each stay stands — never "Left" for a stay nobody has left (the review)', function () {
    $s = seedScenario(6);   // the clock: 1 Aug 2026
    gp2At($s, 0, gp2Guest('Paula', 'Past'), 'checked_out', '2026-07-20', '2026-07-22');
    gp2At($s, 1, gp2Guest('Nico', 'Next'), 'confirmed', '2026-09-02', '2026-09-04');
    gp2At($s, 2, gp2Guest('Tara', 'Today'), 'confirmed', '2026-08-01', '2026-08-03');       // due today, not checked in yet
    gp2At($s, 3, gp2Guest('Omar', 'Overdue'), 'confirmed', '2026-07-30', '2026-08-04');     // due since 30 Jul, stay still running
    gp2At($s, 4, gp2Guest('Stan', 'Stale'), 'confirmed', '2026-07-10', '2026-07-12');       // its dates passed, never checked in
    gp2At($s, 5, gp2Guest('Tina', 'Tentative'), 'tentative', '2026-09-20', '2026-09-22');   // not confirmed

    // 🔬 before (the review): Tara "Left 3 Aug 2026" and Omar "Left 4 Aug 2026" — dates still ahead — and Stan "Left 12 Jul 2026"
    //    with no check-in or check-out on record; Tina "Arrives 20 Sep 2026" for a stay nobody has confirmed
    expect(gp2StayCells($s))->toMatchArray([
        'Paula Past' => 'Left 22 Jul 2026',
        'Nico Next' => 'Arrives 2 Sep 2026',
        'Tara Today' => 'Arrives today',
        'Omar Overdue' => 'Was due 30 Jul 2026',
        'Stan Stale' => 'Was due 10 Jul 2026',
        'Tina Tentative' => 'Tentative · 20 Sep 2026',   // the badge's own word for this state (the second review)
    ]);
});

it('⚠️ sorts the stay column by the date each cell shows', function () {
    $s = seedScenario(7);
    gp2At($s, 0, gp2Guest('Paula', 'Past'), 'checked_out', '2026-07-20', '2026-07-22');
    gp2At($s, 1, gp2Guest('Nico', 'Next'), 'confirmed', '2026-09-02', '2026-09-04');
    gp2At($s, 2, gp2Guest('Tara', 'Today'), 'confirmed', '2026-08-01', '2026-08-03');
    gp2At($s, 3, gp2Guest('Omar', 'Overdue'), 'confirmed', '2026-07-30', '2026-08-04');
    gp2At($s, 4, gp2Guest('Stan', 'Stale'), 'confirmed', '2026-07-10', '2026-07-12');
    gp2At($s, 5, gp2Guest('Tina', 'Tentative'), 'tentative', '2026-09-20', '2026-09-22');
    gp2At($s, 6, gp2Guest('Lena', 'Long'), 'checked_out', '2026-07-01', '2026-07-25');   // arrived first, left last: "Left 25 Jul"

    // Tara (shown 1 Aug) above Omar (shown 30 Jul) — sorting by the departures it no longer shows put Omar (4 Aug) first;
    // Lena (shown 25 Jul) above Paula (22 Jul) — sorting every booking by its arrival would put Lena (1 Jul) last
    expect(array_keys(gp2StayCells($s, ['sort' => 'last_stay'])))
        ->toBe(['Tina Tentative', 'Nico Next', 'Tara Today', 'Omar Overdue', 'Lena Long', 'Paula Past', 'Stan Stale', 'G One'])
        ->and(array_keys(gp2StayCells($s, ['sort' => 'last_stay', 'dir' => 'asc'])))
        ->toBe(['Stan Stale', 'Paula Past', 'Lena Long', 'Omar Overdue', 'Tara Today', 'Nico Next', 'Tina Tentative', 'G One']);
});

// ── Nationality, from the list of countries ─────────────────────────────────────────────────────────────────────

it('⚠️ offers every country by name for nationality — in Add guest and in Edit', function () {
    $s = seedScenario(1);
    $html = gp2Page($s);

    // 🔬 before: a two-letter box with a pattern ("· 2 letters", placeholder "IE")
    expect($html)->toContain('<select class="vy-input" id="a_nat" name="nationality">')
        ->toContain('<select class="vy-input" id="vy-gl-e-nat" name="nationality" x-model="edit.nationality" x-ref="nat">')
        ->toContain('<option value="JP">Japan</option>')
        ->not->toContain('pattern="[A-Za-z]{2}"')->not->toContain('· 2 letters');

    test()->actingAs(operatorUser($s))->post(route('app.guests.store'), ['last_name' => 'Watanabe', 'first_name' => 'Kenji', 'nationality' => 'JP'])
        ->assertRedirect();
    expect((string) Guest::query()->where('last_name', 'Watanabe')->value('nationality'))->toBe('JP');
});

it('opens Edit on the guest’s country, whatever case it was saved in', function () {
    $s = seedScenario(1);
    gp2Guest('Ines', 'Moreira', ['nationality' => 'pt']);

    // the select's options are upper case — a code saved in lower case would have opened Edit on "Not given"
    expect(gp2Page($s))->toMatch('~(?:&quot;|\\\\u0022)nationality(?:&quot;|\\\\u0022):(?:&quot;|\\\\u0022)PT(?:&quot;|\\\\u0022)~');
});

it('⚠️⚠️ keeps a code the country list does not have — carried apart from the choice, and set again once its option exists', function () {
    $s = seedScenario(1);
    gp2Guest('Arta', 'Krasniqi', ['nationality' => 'XK']);   // Kosovo's code, which ISO-3166 does not list
    $html = gp2Page($s);

    // the saved code and its name travel apart from the live choice, so picking France never adds a second "FR (as saved)"
    expect($html)->toMatch('~nationality_unlisted(?:&quot;|\\\\u0022):(?:&quot;|\\\\u0022)XK(?:&quot;|\\\\u0022)~')
        ->toMatch('~nationality_unlisted_label(?:&quot;|\\\\u0022):(?:&quot;|\\\\u0022)Kosovo \(as saved\)~')
        ->toContain('<template x-if="edit.nationality_unlisted"><option :value="edit.nationality_unlisted" x-text="edit.nationality_unlisted_label"></option></template>');
    // 🖥 before (the review, then measured in the browser): x-model chose before x-if had added the option — Edit opened on
    //    "Not given" with the select's value "", and Save would have erased XK. The select is set again once the option is there.
    expect($html)->toContain('x-ref="nat"')
        ->toContain('this.$nextTick(() => { if (this.$refs.nat) this.$refs.nat.value = this.edit.nationality; });')
        // …and it is CALLED where Edit opens (the second review: the body alone proved nothing — delete the call and it is back)
        ->toMatch('~startEdit\(\) \{[\s\S]*?this\.edit = Object\.assign\(this\.blank\(\), row\.edit\);\s*this\.syncNat\(\);~');
    // 🖥 the behaviour itself was walked: Edit opened on "Kosovo (as saved)", France added no "FR (as saved)", Save kept XK
});

it('⚠️ keeps that code when an edit the server refused reopens — and sets the select again there too', function () {
    $s = seedScenario(1);
    $arta = gp2Guest('Arta', 'Krasniqi', ['nationality' => 'XK']);
    $manager = operatorUser($s);

    // (no assertSessionHasErrors — it would start the session store and lose the flashed input; see GuestsListRedesignTest)
    test()->actingAs($manager)->from('/app/guests')->post(route('app.guests.update', $arta->id), [
        'first_name' => 'Arta', 'last_name' => 'Krasniqi', 'email' => 'not-an-email', 'nationality' => 'XK',
        'keep_preferences' => '1', 'return' => 'list', '_guest' => $arta->id,
    ])->assertRedirect('/app/guests');

    // 🔬 before: the reopened dialog had no "(as saved)" option at all
    $html = (string) test()->actingAs($manager)->get('/app/guests')->assertOk()->getContent();
    expect($html)->toContain('not-an-email')
        ->toMatch('~retry(?:&quot;|\\\\u0022):\{[^}]*nationality_unlisted(?:&quot;|\\\\u0022):(?:&quot;|\\\\u0022)XK~')
        ->toMatch('~this\.edit = Object\.assign\(this\.blank\(\), boot\.retry\);\s*this\.syncNat\(\);~');
});

// ── The top bar, on this page and every other ──────────────────────────────────────────────────────────────────

it('keeps the top bar’s search on one line — a shorter label where the bar is narrow, the same name for a screen reader', function () {
    $s = seedScenario(1);
    $html = gp2Page($s);

    // 🔬 before: "Search reservations, / guests…" on two lines at 1,024 px
    // its spoken name holds the words it shows, in order (WCAG 2.5.3) — "and" in it broke that (the review)
    expect($html)->toContain('aria-label="Search reservations, guests"')
        ->toContain('<span class="vy-search-long">Search reservations, guests…</span><span class="vy-search-short" aria-hidden="true">Search…</span>');
    // the BASE rule (desktop widths) — the phone rule further down has its own nowrap and must not stand in for this one (GS-M19)
    expect(gp2Css())->toMatch('~\.vy-search-pill \{ display: inline-flex;[^}]*white-space: nowrap~');
});
