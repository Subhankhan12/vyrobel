<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\Reservation;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE INBOX, 2026-10-05 (the founder: "on ~ New message page design is not good. 2 text is repeated. Fix it. It must have better
// design"). 📖 Read first: the dialog printed its hint twice — as a second label under "Message" AND as the textarea's placeholder —
// and "Guests with a current or upcoming stay." twice when the list was empty; its textarea shared an id with the reply box behind it,
// so its label pointed at the wrong box; a Direct message read "Delivered" though Direct sends nothing anywhere. Each test failed
// first against the page as it stood. Test clock: 1 Aug 2026.

function ibxPage(array $s, string $query = '', string $role = 'Hotel Manager'): string
{
    return (string) test()->actingAs(operatorUser($s, [], $role))->get(route('app.inbox').$query)->assertOk()->getContent();
}

/** The New message dialog's own markup — from its backdrop to the end of the page's dialogs. */
function ibxCompose(string $html): string
{
    $at = strpos($html, 'x-show="composeOpen"');
    expect($at)->not->toBeFalse('the New message dialog is on the page');

    return substr($html, (int) $at, 9000);
}

// ── his report: the repeated text ──────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ says each thing once in New message — one label per field, a short placeholder, no sentence printed twice', function () {
    $s = seedScenario(1);
    book($s);
    $dialog = ibxCompose(ibxPage($s));

    // 🔬 before: "Write a message, or pick a template above to start from one — you can edit it before sending." as a label AND as
    //    the placeholder directly under it, under a second label "Message"
    expect($dialog)->not->toContain('Write a message, or pick a template above to start from one')
        ->toContain('<label class="vy-field-label" for="vy-ibx-compose-body">Message</label>')
        ->toContain('placeholder="Write your message…"')
        ->and(substr_count($dialog, '>Message<'))->toBe(1);
});

it('⚠️ says who it lists — staying now, arriving soon, or who left in the last 14 days — and never says it twice', function () {
    $s = seedScenario(1);
    book($s);

    // 🔬 before: "Guests with a current or upcoming stay." — the list also holds guests who left in the last 14 days
    expect(ibxCompose(ibxPage($s)))->toContain('Guests staying now, arriving soon, or who left in the last 14 days.')
        ->not->toContain('Guests with a current or upcoming stay');
});

it('⚠️⚠️ offers the way on when there is nobody to message — no dead form', function () {
    $s = seedScenario(1);   // no booking: nobody qualifies
    $dialog = ibxCompose(ibxPage($s));

    // 🔬 before: "No guests with a current or upcoming stay." over templates, a message box and a Start button that could never work
    expect($dialog)->toContain('No guest to message from here yet')
        ->toContain('href="'.route('app.guests').'"')
        ->not->toContain('name="body"')
        ->not->toContain('Start conversation');
});

it('⚠️⚠️ says where each guest’s stay stands by the Guests list’s own rule — never "In-house" for an arrival not checked in', function () {
    $s = seedScenario(2);   // the clock: 1 Aug 2026
    $today = book($s, ['unit_id' => $s['units'][0]->id]);
    Reservation::query()->whereKey($today->id)->update(['check_in' => '2026-08-01', 'check_out' => '2026-08-03']);   // due today, not checked in
    $inHouse = book($s, ['unit_id' => $s['units'][1]->id]);
    $other = Guest::create(['first_name' => 'Ines', 'last_name' => 'Inhouse']);
    Reservation::query()->whereKey($inHouse->id)->update(['lead_guest_id' => $other->id, 'status' => 'checked_in', 'check_in' => '2026-07-31', 'check_out' => '2026-08-04']);

    // 🖥 before (seen on the walk): "In-house · H201" for a guest arriving today, not yet checked in
    $dialog = ibxCompose(ibxPage($s));
    expect($dialog)->toContain('Arrives today')->toContain('In house')->not->toContain('In-house');
});

it('⚠️ gives the dialog’s message box its own id — its label no longer points at the reply box behind it', function () {
    $s = seedScenario(1);
    book($s);
    $guest = $s['guest'];
    $thread = app(InboxService::class)->startThread(['channel' => 'manual', 'guest_id' => (string) $guest->id]);
    $html = ibxPage($s, '?c='.$thread->id);

    // 🔬 before: id="vy-lbl-operator-inbox-body" on BOTH textareas (the reply box and the dialog's)
    preg_match_all('~\sid="([^"]+)"~', $html, $m);
    $dupes = array_keys(array_filter(array_count_values($m[1]), fn (int $n): bool => $n > 1));
    expect($dupes)->toBe([]);
});

it('lets a guest be found by name in the dialog, and keeps Start off until one is picked — without an inline style', function () {
    $s = seedScenario(1);
    book($s);
    $dialog = ibxCompose(ibxPage($s));

    expect($dialog)->toContain('placeholder="Find a guest"')
        ->toContain(':disabled="! composeGuest"')
        ->not->toContain('opacity:.5;pointer-events:none');
});

it('⚠️ says what happens to the message — kept in this inbox, read in the guest’s account if they have one, never emailed', function () {
    $s = seedScenario(1);
    book($s);
    $dialog = ibxCompose(ibxPage($s));

    // 🔬 before: "Starts a thread in this inbox · the guest replies wherever they booked" — a Direct message goes nowhere but here and
    //    the guest's own account; and the controller adds to their existing conversation rather than always starting one
    expect($dialog)->toContain('can read it in their guest account')
        ->toContain('has no guest account')
        ->toContain('It isn’t emailed.')
        ->not->toContain('the guest replies wherever they booked');
});

// ── the other dialogs, and the words under a Direct conversation ──────────────────────────────────────────────────

it('⚠️⚠️ puts every Inbox dialog on the shared solid card — and drops the page’s own see-through dialog rules', function () {
    $s = seedScenario(1);
    book($s);
    $thread = app(InboxService::class)->startThread(['channel' => 'manual', 'guest_id' => (string) $s['guest']->id]);
    $html = ibxPage($s, '?c='.$thread->id);

    // three dialogs: Assign, Link a reservation, New message (A9: the Inbox's were the see-through glass)
    expect(substr_count($html, 'class="vy-modal vy-modal-card'))->toBe(3)
        ->and(file_get_contents(resource_path('views/livewire/operator/operator-inbox.blade.php')))
        ->not->toMatch('~\n\s*\.vy-modal \{~')->not->toMatch('~\n\s*\.vy-modal-backdrop \{~');
});

it('⚠️ says the Assign dialog’s things once — and only what really happens', function () {
    $s = seedScenario(1);
    book($s);
    $thread = app(InboxService::class)->startThread(['channel' => 'manual', 'guest_id' => (string) $s['guest']->id]);
    $html = ibxPage($s, '?c='.$thread->id);

    // 🔬 before: the note's hint as a label AND its placeholder; "notified in-app" in the sub-line and again as "notified on their
    //    device" (there is no push — the notification is in Vyrobel)
    expect($html)->not->toContain('Add a note for the team (optional) — e.g. why you\'re handing this over')
        ->not->toContain('notified on their device')
        ->toContain('<label class="vy-field-label" for="vy-ibx-note">Note for the team · optional</label>');
});

it('⚠️⚠️ calls a Direct message "Saved", and says where the guest reads it — "Delivered" only where a channel delivers', function () {
    $s = seedScenario(1);
    book($s);
    $thread = app(InboxService::class)->startThread(['channel' => 'manual', 'guest_id' => (string) $s['guest']->id]);
    app(InboxService::class)->sendOutbound($thread, 'Your room is ready.', operatorUser($s));
    $html = ibxPage($s, '?c='.$thread->id);

    // 🔬 before: "Delivered" — Direct sends nothing anywhere; the guest sees it only in their own guest account
    expect($html)->toContain('<span class="vy-msg-delivery">Saved</span>')
        ->not->toContain('>Delivered<')
        ->toContain('has no guest account, so this stays here as a record — it isn’t emailed.');

    // a guest WITH an account is told the other way
    Guest::query()->whereKey($s['guest']->id)->update(['password' => bcrypt('a-long-test-password-1!')]);
    expect(ibxPage($s, '?c='.$thread->id))->toContain('reads this in their guest account — it isn’t emailed.');
});

it('keeps the conversation search on the shared search box — its icon and field', function () {
    $s = seedScenario(1);
    $html = ibxPage($s);

    // 🔬 before: a bare input with no icon
    expect($html)->toMatch('~<form method="GET" action="[^"]*" class="vy-gl-search vy-ibx-search"[^>]*>~')
        ->toContain('class="vy-gl-search-in"');
});
