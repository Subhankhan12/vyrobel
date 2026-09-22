<?php

use App\Models\Guest;
use App\Models\GuestChangeRequest;
use App\Models\Reservation;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ APPLY 03 #4 — GATE THE GUEST'S ACTIONS, NOT HIS PAGE.
 *
 * ⚠️ A guest has an entirely legitimate reason to open a stay that has ENDED: their dates, their folio, their
 * receipt. **The page stays.** What makes no sense on a finished booking is asking to change it.
 *
 * ⚠️⚠️ AND THIS ONE WAS REAL, UNLIKE ITS PREDECESSOR. The previous pass measured the cancellation COPY and
 * found it honest ("the property reviews it"), so that item shrank. 🔬 This measurement went the other way:
 * `GuestChangeRequestService` has no status check, so all three requests went STRAIGHT THROUGH on a
 * cancelled, checked-out or no-show booking — the guest was told "we'll be in touch" and an operator
 * received a pending request they could not action.
 */
function gmsGuest(string $email = 'gate@example.test'): Guest
{
    $g = Guest::create(['email' => $email, 'first_name' => 'Gate', 'last_name' => 'Test']);
    $g->password = 'gate-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

it('⚠️ keeps the PAGE open on a finished stay — the dates and reference still render', function (string $status) {
    $s = seedScenario(1);
    $guest = gmsGuest();
    $r = book($s, ['unit_id' => $s['units'][0]->id, 'lead_guest_id' => $guest->getKey()]);
    Reservation::query()->whereKey($r->getKey())->update(['status' => $status, 'lead_guest_id' => $guest->getKey()]);

    $html = $this->actingAs($guest, 'guest')->get('/guest/reservation/'.$r->getKey().'/manage')
        ->assertOk()->getContent();

    // ⚠️ THE PAGE IS NOT GATED — asserted positively, so a blanket 403 could never pass this file.
    expect($html)->toContain((string) $r->reference);
})->with(['cancelled', 'checked_out', 'no_show']);

it('⚠️ hides the three change ACTIONS on a finished stay, and says why', function (string $status) {
    $s = seedScenario(1);
    $guest = gmsGuest('actions-'.$status.'@example.test');
    $r = book($s, ['unit_id' => $s['units'][0]->id, 'lead_guest_id' => $guest->getKey()]);
    Reservation::query()->whereKey($r->getKey())->update(['status' => $status, 'lead_guest_id' => $guest->getKey()]);

    $html = $this->actingAs($guest, 'guest')->get('/guest/reservation/'.$r->getKey().'/manage')
        ->assertOk()->getContent();

    expect($html)->not->toContain(route('guest.manage.cancel', $r->getKey()))
        ->and($html)->not->toContain(route('guest.manage.date-change', $r->getKey()))
        ->and($html)->not->toContain(route('guest.extras', $r->getKey()))
        // ⚠️ A page that simply stops offering things reads as broken. It must say why.
        ->and($html)->toContain('This booking has closed');
})->with(['cancelled', 'checked_out', 'no_show']);

it('⚠️⚠️ REFUSES the write on a finished stay — hiding a form is the UI half, never the gate', function (string $status) {
    $s = seedScenario(1);
    $guest = gmsGuest('write-'.$status.'@example.test');
    $r = book($s, ['unit_id' => $s['units'][0]->id, 'lead_guest_id' => $guest->getKey()]);
    Reservation::query()->whereKey($r->getKey())->update(['status' => $status, 'lead_guest_id' => $guest->getKey()]);

    $before = GuestChangeRequest::query()->withoutGlobalScopes()->count();

    // A DIRECT POST — exactly what a hidden form does not prevent.
    $this->actingAs($guest, 'guest')
        ->post('/guest/reservation/'.$r->getKey().'/manage/cancel', ['reason' => 'Change of plans'])
        ->assertRedirect();

    // ⚠️ THE ROW IS THE EVIDENCE. A redirect proves nothing about whether a request was created.
    expect(GuestChangeRequest::query()->withoutGlobalScopes()->count())->toBe($before);
})->with(['cancelled', 'checked_out', 'no_show']);

it('⚠️ leaves a LIVE stay completely alone — including checked_in', function (string $status) {
    // ⚠️ THE CONTROL, AND IT CARRIES THE RULING. `checked_in` is deliberately NOT gated: a guest in the room
    //    may still add extras, ask to extend, or ask to cancel — and since R9 an operator can actually carry
    //    that last one out, in two deliberate steps. Gating it would remove a request the product can now fulfil.
    $s = seedScenario(1);
    $guest = gmsGuest('live-'.$status.'@example.test');
    $r = book($s, ['unit_id' => $s['units'][0]->id, 'lead_guest_id' => $guest->getKey()]);
    Reservation::query()->whereKey($r->getKey())->update(['status' => $status, 'lead_guest_id' => $guest->getKey()]);

    $html = $this->actingAs($guest, 'guest')->get('/guest/reservation/'.$r->getKey().'/manage')
        ->assertOk()->getContent();

    expect($html)->toContain(route('guest.manage.cancel', $r->getKey()))
        ->and($html)->not->toContain('This booking has closed');
})->with(['confirmed', 'checked_in']);

it('⚠️ the gate reads the SHARED terminal definition, never a second list', function () {
    // ⚠️ #29's constant is the one definition of "finished". A private copy here would drift the day the
    //    ladder changes — which is exactly how the controller and the OTA path once disagreed.
    expect(AssignmentService::TERMINAL_STATUSES)->toBe(['cancelled', 'no_show', 'checked_out']);
    expect(file_get_contents(base_path('app/Http/Controllers/Guest/GuestManageController.php')))
        ->toContain('AssignmentService::TERMINAL_STATUSES');
});
