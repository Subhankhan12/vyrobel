<?php

declare(strict_types=1);

use App\Notifications\Operator\NewBookingNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-14 (audit of 2026-11-24, reproduced) — THE RESERVATIONS LIST AND A RESERVATION'S PAGE NEED A REASON TO READ THEM.
//
// A Housekeeping user opened the reservations list and one reservation's page and read the amount and the guest's name: the two
// read routes carried no permission check at all (every back-office role could open them).
//
// Both routes now need `reservations.view` — or `folios.view`, because the folio (charges, payments, refunds) is worked ON the
// reservation's page and the Accountant, who holds the money permissions and not `reservations.view`, must still reach it.
// Housekeeping, Maintenance and Marketing hold neither and are refused; the sidebar no longer offers them the link.
//
// ⚠️ A CROSS-CHECK KEEPS A PERMITTED ACTOR: the refusal is a 403 for the role, never a 404 that would also pass for "not found".

uses(DatabaseTransactions::class);

function vy14Reservation(array $s)
{
    $s['guest']->update(['first_name' => 'Zenobia', 'last_name' => 'Quillfeather']);

    return book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 43210]);
}

it('⚠️⚠️ VY-14 (as reproduced): Housekeeping cannot open the reservations list or a reservation — and reads no name or amount', function (string $role) {
    $s = seedScenario(1);
    $reservation = vy14Reservation($s);
    $user = operatorUser($s, [], $role);

    $list = test()->actingAs($user)->get(route('app.reservations'))->assertForbidden();
    $detail = test()->actingAs($user)->get(route('app.reservations.show', $reservation->id))->assertForbidden();

    foreach ([$list, $detail] as $response) {
        expect((string) $response->getContent())->not->toContain('Quillfeather')->not->toContain('432.10')->not->toContain($reservation->reference);
    }
})->with(['Housekeeping', 'Maintenance', 'Marketing Manager']);

it('VY-14 CONTROL: every role with a reason to read reservations still opens both screens', function (string $role) {
    $s = seedScenario(1);
    $reservation = vy14Reservation($s);
    $user = operatorUser($s, [], $role);

    test()->actingAs($user)->get(route('app.reservations'))->assertOk()->assertSee($reservation->reference);
    test()->actingAs($user)->get(route('app.reservations.show', $reservation->id))->assertOk()->assertSee($reservation->reference);
})->with(['Front Desk', 'Reservation Agent', 'Hotel Manager', 'Revenue Manager', 'Guest Support Agent', 'Property Owner',
    'Accountant',   // ⚠️ holds `folios.view`, not `reservations.view` — the folio is worked on the reservation's page
]);

it('⚠️ VY-14: the sidebar does not offer the Reservations link to a role that would be refused — and still offers it to one that would not', function () {
    $s = seedScenario(1);

    $housekeeping = (string) test()->actingAs(operatorUser($s, [], 'Housekeeping'))->get(route('app.housekeeping'))->assertOk()->getContent();
    $frontDesk = (string) test()->actingAs(operatorUser($s, [], 'Front Desk'))->get(route('app.housekeeping'))->assertOk()->getContent();

    expect($housekeeping)->not->toContain('href="'.route('app.reservations').'"')
        ->and($frontDesk)->toContain('href="'.route('app.reservations').'"');
});

it('⚠️ VY-14: the Calendar does not link a refused role to a reservation page it cannot open', function () {
    $s = seedScenario(1);
    $reservation = vy14Reservation($s);

    $housekeeping = (string) test()->actingAs(operatorUser($s, [], 'Housekeeping'))->get(route('app.calendar', ['start' => '2026-09-01']))->assertOk()->getContent();
    $frontDesk = (string) test()->actingAs(operatorUser($s, [], 'Front Desk'))->get(route('app.calendar', ['start' => '2026-09-01']))->assertOk()->getContent();

    expect($housekeeping)->not->toContain(route('app.reservations.show', $reservation->id))
        ->and($frontDesk)->toContain(route('app.reservations.show', $reservation->id));
});

it('⚠️ VY-14: a "New booking" notification does not link a refused role to the reservations list — it still does for one that can open it', function () {
    $s = seedScenario(1);
    $reservation = vy14Reservation($s);
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    $frontDesk = operatorUser($s, [], 'Front Desk');
    foreach ([$housekeeper, $frontDesk] as $user) {
        $user->notifyNow(new NewBookingNotification($reservation), ['database']);
    }
    $link = 'href="'.route('app.reservations', [], false).'"';

    // seen in a real browser on 2026-11-24: the bell offered a Housekeeper three links that all answered 403
    $hk = (string) test()->actingAs($housekeeper)->get(route('app.housekeeping'))->assertOk()->getContent();
    $fd = (string) test()->actingAs($frontDesk)->get(route('app.housekeeping'))->assertOk()->getContent();

    expect($hk)->toContain($reservation->reference)   // the notice itself is still theirs to read
        ->and($hk)->not->toContain($link)
        ->and($fd)->toContain($link);
});
