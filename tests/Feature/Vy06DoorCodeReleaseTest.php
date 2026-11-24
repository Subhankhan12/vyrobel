<?php

declare(strict_types=1);

use App\Mail\PreArrivalMail;
use App\Models\CompanyAccount;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Guest\ArrivalPack;
use App\Support\Money\Money;
use App\Support\Pdf\ArrivalPackDocument;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// ⚠️⚠️ VY-06 (audit of 2026-11-24, reproduced) — THE DOOR CODE IS RELEASED ON CONDITIONS, NOT ON A DATE ALONE.
//
// A stay with 200.00 owing and no online check-in showed its door code two days before arrival: the only rule was the date, fixed at
// two days for every property, and the pre-arrival email carried the same code. The code now needs, in this order:
//   1. a live booking (never a cancelled one);
//   2. the arrival window — a number of days EACH PROPERTY sets (2 when it has set none);
//   3. the balance settled — unless the property switches that off (pay-on-arrival houses with a front desk);
//   4. online check-in done — only if the property asks for it.
// Until then the guest is told the ONE thing that is missing ("Pay your balance of … to see your door code"), on the Arrival page, the
// reservation page, the printable guide and the pre-arrival email — all four read the same ArrivalPack, so they cannot disagree.

uses(DatabaseTransactions::class);

const VY06_CODE = '4471#';

/** A claimed guest with a 2026-09-05 → 09-08 stay on a unit that carries a door code and Wi-Fi; `$charged` posts a 200.00 room charge. */
function vy06Stay(bool $charged = true, array $policy = []): array
{
    $s = seedScenario(1);
    $s['units'][0]->update(['door_code' => VY06_CODE, 'wifi_ssid' => 'Seaside-Guest', 'wifi_password' => 'salt-and-pepper', 'arrival_notes' => 'Blue door, second floor.']);

    $guest = $s['guest'];
    $guest->update(['email' => 'ada.vy06@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 20000, 'currency' => 'EUR']],
    );
    if ($charged) {
        postRoom($reservation, 20000);   // 200.00 owing, nothing paid
    }
    if ($policy !== []) {
        PropertyPolicy::create(array_merge(['property_id' => $s['property']->id], $policy));
    }

    return $s + ['reservation' => $reservation, 'guestUser' => $guest->fresh()];
}

function vy06Pack(Reservation $reservation, string $today): array
{
    return ArrivalPack::for(
        Reservation::query()->with(['property', 'reservationUnits.unit'])->findOrFail($reservation->getKey()),
        CarbonImmutable::parse($today),
    );
}

it('⚠️⚠️ VY-06 (as reproduced): 200.00 owing, two days before arrival — the code is WITHHELD, and the guest is told to pay', function () {
    $c = vy06Stay();
    $this->travelTo('2026-09-03 10:00:00');   // two days before check-in: inside the window

    $pack = vy06Pack($c['reservation'], '2026-09-03');
    expect($pack['released'])->toBeFalse()
        ->and($pack['withheld'])->toBe('balance')
        ->and($pack['door_code'])->toBeNull()
        ->and($pack['wifi_ssid'])->toBeNull()
        ->and($pack['wifi_password'])->toBeNull()
        ->and($pack['arrival_notes'])->toBeNull()
        ->and($pack['withheld_message'])->toContain('200.00')
        ->and($pack['has_code'])->toBeTrue();

    // the Arrival page, the reservation page and the printable guide: no code anywhere, and the one thing that is missing
    foreach ([route('guest.arrival', $c['reservation']->id), route('guest.reservation', $c['reservation']->id)] as $url) {
        $page = $this->actingAs($c['guestUser'], 'guest')->get($url)->assertOk();
        $page->assertDontSee(VY06_CODE)->assertDontSee('salt-and-pepper')->assertDontSee('Seaside-Guest')
            ->assertSee('Pay your balance of')
            ->assertSee('200.00');
        assertNoRawBlade($page);
    }
    $pdf = ArrivalPackDocument::data(Reservation::query()->with(['property', 'reservationUnits.unit', 'leadGuest'])->findOrFail($c['reservation']->id), CarbonImmutable::parse('2026-09-03'));
    expect($pdf['pack']['door_code'])->toBeNull();
    expect(view('pdfs.arrival', $pdf)->render())->not->toContain(VY06_CODE)->toContain('Pay your balance of');
});

it('⚠️ VY-06: once the balance is paid the same guest sees the code — the control that the gate is the balance', function () {
    $c = vy06Stay();
    collectFolio($c['reservation']);
    $this->travelTo('2026-09-03 10:00:00');

    $pack = vy06Pack($c['reservation'], '2026-09-03');
    expect($pack['released'])->toBeTrue()
        ->and($pack['withheld'])->toBeNull()
        ->and($pack['withheld_message'])->toBeNull()
        ->and($pack['door_code'])->toBe(VY06_CODE)
        ->and($pack['wifi_password'])->toBe('salt-and-pepper');

    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.arrival', $c['reservation']->id))->assertOk()
        ->assertSee(VY06_CODE)->assertDontSee('Pay your balance of');
});

it('VY-06: a part payment is not "settled" — one cent owing still withholds the code', function () {
    $c = vy06Stay();
    $folio = Folio::query()->where('reservation_id', $c['reservation']->id)->firstOrFail();
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(19999, 'EUR'));
    app(FolioService::class)->settlePayment($payment, 'succeeded');

    $pack = vy06Pack($c['reservation'], '2026-09-04');
    expect($pack['withheld'])->toBe('balance')->and($pack['door_code'])->toBeNull()->and($pack['withheld_message'])->toContain('0.01');
});

it('VY-06: a property that takes payment on arrival can switch the balance rule off — its guests see the code while owing', function () {
    $c = vy06Stay(policy: ['arrival_requires_paid' => false]);

    $pack = vy06Pack($c['reservation'], '2026-09-03');
    expect($pack['released'])->toBeTrue()->and($pack['door_code'])->toBe(VY06_CODE);
});

it('VY-06: a stay billed to a company account is not the guest\'s to pay — the code is released', function () {
    $c = vy06Stay();
    $account = CompanyAccount::create(['name' => 'Acme Travel '.random_int(1000, 9999)]);
    Folio::query()->where('reservation_id', $c['reservation']->id)->update(['billed_to_account_id' => $account->id]);

    expect(vy06Pack($c['reservation'], '2026-09-03')['door_code'])->toBe(VY06_CODE);
});

it('⚠️ VY-06: a property may ALSO ask for online check-in — then a paid guest still waits for it, and is told so', function () {
    $c = vy06Stay(policy: ['arrival_requires_checkin' => true]);
    collectFolio($c['reservation']);
    $this->travelTo('2026-09-03 10:00:00');

    $pack = vy06Pack($c['reservation'], '2026-09-03');
    expect($pack['withheld'])->toBe('checkin')->and($pack['door_code'])->toBeNull();
    $this->actingAs($c['guestUser'], 'guest')->get(route('guest.arrival', $c['reservation']->id))->assertOk()
        ->assertDontSee(VY06_CODE)->assertSee('Complete online check-in');

    // the guest checks in online → released
    $this->actingAs($c['guestUser'], 'guest')->post(route('guest.checkin.submit', $c['reservation']->id))->assertRedirect();
    expect(vy06Pack($c['reservation'], '2026-09-03')['door_code'])->toBe(VY06_CODE);

    // ⚠️ the balance comes FIRST: unpaid AND not checked in says "pay", not "check in"
    $d = vy06Stay(policy: ['arrival_requires_checkin' => true]);
    expect(vy06Pack($d['reservation'], '2026-09-03')['withheld'])->toBe('balance');
});

it('⚠️ VY-06: the lead time is the PROPERTY\'S — 2 days when it set none, its own number when it did', function () {
    // none set → 2 days, as before
    $c = vy06Stay(charged: false);
    expect(ArrivalPack::leadDays($c['property']))->toBe(2)
        ->and(vy06Pack($c['reservation'], '2026-09-02')['withheld'])->toBe('window')
        ->and(vy06Pack($c['reservation'], '2026-09-03')['released'])->toBeTrue();

    // five days
    $d = vy06Stay(charged: false, policy: ['arrival_lead_days' => 5]);
    expect(ArrivalPack::leadDays($d['property']->fresh()))->toBe(5)
        ->and(vy06Pack($d['reservation'], '2026-08-30')['withheld'])->toBe('window')
        ->and(vy06Pack($d['reservation'], '2026-08-31')['door_code'])->toBe(VY06_CODE);

    // zero: only on the day itself
    $e = vy06Stay(charged: false, policy: ['arrival_lead_days' => 0]);
    expect(vy06Pack($e['reservation'], '2026-09-04')['withheld'])->toBe('window')
        ->and(vy06Pack($e['reservation'], '2026-09-05')['door_code'])->toBe(VY06_CODE);
});

it('VY-06: a cancelled stay never shows the code — paid or not', function () {
    $c = vy06Stay();
    collectFolio($c['reservation']);
    app(AssignmentService::class)->cancelReservation($c['reservation']->fresh(), 'changed plans');

    $pack = vy06Pack($c['reservation'], '2026-09-04');
    expect($pack['withheld'])->toBe('cancelled')->and($pack['door_code'])->toBeNull();
});

it('⚠️⚠️ VY-06: the pre-arrival EMAIL carries no code while the balance is owing — and says what is missing', function () {
    $c = vy06Stay();
    $reservation = Reservation::query()->with(['leadGuest', 'property', 'reservationUnits.unit'])->findOrFail($c['reservation']->id);

    $html = (new PreArrivalMail($reservation))->render();
    expect($html)->not->toContain(VY06_CODE)
        ->not->toContain('salt-and-pepper')
        ->not->toContain('your door code is inside')
        ->toContain('Pay your balance of');

    // paid → the code is in the email
    collectFolio($c['reservation']);
    $paid = (new PreArrivalMail(Reservation::query()->with(['leadGuest', 'property', 'reservationUnits.unit'])->findOrFail($c['reservation']->id)))->render();
    expect($paid)->toContain(VY06_CODE)->not->toContain('Pay your balance of');

    // a property whose window opens only on the day itself: the email it sends THAT day carries the code
    // (last in this test: making a second scenario moves the tenant context to its organisation)
    $day = vy06Stay(charged: false, policy: ['arrival_lead_days' => 0]);
    $onTheDay = (new PreArrivalMail(Reservation::query()->with(['leadGuest', 'property', 'reservationUnits.unit'])->findOrFail($day['reservation']->id)))->render();
    expect($onTheDay)->toContain(VY06_CODE);
});

it('VY-06: the pre-arrival email goes out the day EACH property\'s window opens', function () {
    Mail::fake();
    $two = vy06Stay(charged: false);                                       // default: 2 days → sent on 3 Sep
    $five = vy06Stay(charged: false, policy: ['arrival_lead_days' => 5]);   // 5 days → sent on 31 Aug
    $five['guestUser']->update(['email' => 'grace.vy06@example.test']);

    $sentTo = function (): array {
        $to = [];
        foreach (Mail::sent(PreArrivalMail::class) as $mail) {
            $to[] = $mail->to[0]['address'];
        }
        sort($to);

        return $to;
    };

    $this->travelTo('2026-08-31 09:00:00');
    $this->artisan('emails:send-pre-arrival')->assertExitCode(0);
    expect($sentTo())->toBe(['grace.vy06@example.test']);

    $this->travelTo('2026-09-03 09:00:00');
    $this->artisan('emails:send-pre-arrival')->assertExitCode(0);
    expect($sentTo())->toBe(['ada.vy06@example.test', 'grace.vy06@example.test']);
});

it('⚠️ VY-06 (settings): the three rules are set per property from Settings → Policies, within bounds', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Property Owner');
    $base = ['pet' => 'allowed', 'min_stay' => 1];

    $this->actingAs($user)->post(route('app.settings.policies'), $base + ['arrival_lead_days' => '5', 'arrival_requires_paid' => '0', 'arrival_requires_checkin' => '1'])->assertRedirect();
    $row = DB::table('property_policies')->where('property_id', $s['property']->id)->first();
    expect((int) $row->arrival_lead_days)->toBe(5)
        ->and((bool) $row->arrival_requires_paid)->toBeFalse()
        ->and((bool) $row->arrival_requires_checkin)->toBeTrue();

    // out of bounds: refused, nothing changes
    $this->actingAs($user)->from(route('app.settings'))->post(route('app.settings.policies'), $base + ['arrival_lead_days' => '31'])->assertSessionHasErrors('arrival_lead_days');
    $this->actingAs($user)->from(route('app.settings'))->post(route('app.settings.policies'), $base + ['arrival_lead_days' => '-1'])->assertSessionHasErrors('arrival_lead_days');
    expect((int) DB::table('property_policies')->where('property_id', $s['property']->id)->value('arrival_lead_days'))->toBe(5);

    // ⚠️ a form that does NOT carry the fields (an older tab, another client) leaves all three as they are — never reset by omission
    $this->actingAs($user)->post(route('app.settings.policies'), $base)->assertRedirect();
    $row = DB::table('property_policies')->where('property_id', $s['property']->id)->first();
    expect((int) $row->arrival_lead_days)->toBe(5)
        ->and((bool) $row->arrival_requires_paid)->toBeFalse()
        ->and((bool) $row->arrival_requires_checkin)->toBeTrue();

    // the form shows them back
    $this->actingAs($user)->get(route('app.settings'))->assertOk()
        ->assertSee('name="arrival_lead_days"', false)
        ->assertSee('Only after the balance is paid');
});

it('VY-06: a NEW property (no policy row at all) withholds the code from an unpaid guest — the safe default', function () {
    $c = vy06Stay();
    expect(PropertyPolicy::query()->where('property_id', $c['property']->id)->exists())->toBeFalse()
        ->and(vy06Pack($c['reservation'], '2026-09-04')['withheld'])->toBe('balance');

    // …and a policy row made for another reason keeps the same default (the column defaults to "paid first")
    PropertyPolicy::create(['property_id' => $c['property']->id, 'pet' => 'allowed']);
    expect(vy06Pack($c['reservation'], '2026-09-04')['withheld'])->toBe('balance');
});
