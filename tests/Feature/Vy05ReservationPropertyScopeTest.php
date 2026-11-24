<?php

declare(strict_types=1);

use App\Livewire\Operator\ReservationRefund;
use App\Livewire\ReservationDetail;
use App\Models\Folio;
use App\Models\GuestChangeRequest;
use App\Models\Payment;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\FolioService;
use App\Support\Property\PropertyContext;
use App\Support\Property\PropertyScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

// ⚠️⚠️ VY-05 (audit of 2026-11-24, reproduced) — STAFF LIMITED TO ONE PROPERTY CANNOT REACH ANOTHER PROPERTY'S RESERVATIONS.
//
// A Front Desk user assigned only to property A opened property B's reservation, downloaded its folio PDF and checked its guest in.
// Lists, units, work orders and housekeeping already respected the allow-list (#38, DEC-i/j/k/l/q); every reservation, folio and
// front-desk route found its row through the TENANT scope only. Each now resolves through `PropertyScope::reservation()`. A
// reservation on a property outside the person's allow-list is NOT FOUND — a 404, never a 403 that would confirm it exists.
//
// ⚠️ EVERY refusal here stands beside a PERMITTED actor (CLAUDE.md #30): without one a 404 could be a masked 403 or a broken fixture.

uses(DatabaseTransactions::class);

/** A person scoped (by the allow-list) to ONE property of an organisation that has two. */
function vy05ScopedTo(array $s, string $propertyId, string $role): User
{
    $user = operatorUser($s, [], $role);
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $user->id,
        'property_id' => $propertyId, 'created_at' => now(), 'updated_at' => now()]);

    return $user;
}

/** A confirmed, charged and paid booking on the organisation's SECOND property. */
function vy05Far(array $s): array
{
    $far = secondProperty($s, 'Far House');
    $rt = RoomType::create(['property_id' => $far->id, 'name' => 'Far Std', 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'code' => 'F'.random_int(100, 999)]);
    $other = Unit::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'code' => 'G'.random_int(100, 999)]);
    $plan = RatePlan::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    app(AvailabilityService::class)->recompute($rt->id, '2026-09-01', '2026-09-10');

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $far->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $rt->id, 'rate_plan_id' => $plan->id, 'unit_id' => $unit->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 20000, 'currency' => 'EUR']],
    );
    postRoom($reservation, 20000);
    collectFolio($reservation);

    return compact('far', 'rt', 'unit', 'other', 'plan', 'reservation');
}

/** A confirmed, charged and paid booking on the scenario's OWN (first) property. */
function vy05Near(array $s): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);
    postRoom($reservation, 20000);
    collectFolio($reservation);

    return $reservation;
}

function vy05Status(Reservation $reservation): string
{
    return (string) DB::table('reservations')->where('id', $reservation->id)->value('status');
}

it('⚠️⚠️ VY-05 (as reproduced): Front Desk limited to property A cannot open, print or check in property B\'s reservation', function () {
    $s = seedScenario(1);
    $far = vy05Far($s);
    $near = vy05Near($s);
    $desk = vy05ScopedTo($s, (string) $s['property']->id, 'Front Desk');

    // ⚠️ ORDER: the reservation PAGE is a Livewire component, and once one has rendered inside a test process Livewire's own
    //    redirector stays bound for the rest of it — so the controller routes are driven first and the pages last.

    // refused: checking in and printing the folio of the far booking — and the booking is untouched
    $this->actingAs($desk)->post(route('app.front-desk.check-in', $far['reservation']->id), ['confirm_early' => 1])->assertNotFound();
    $this->actingAs($desk)->get(route('app.reservations.folio-pdf', $far['reservation']->id))->assertNotFound();
    expect(vy05Status($far['reservation']))->toBe('confirmed');

    // PERMITTED CONTROL: the same person does both on their OWN property's booking (the suite's clock is before the stay → confirm_early)
    $this->actingAs($desk)->get(route('app.reservations.folio-pdf', $near->id))->assertOk();
    $this->actingAs($desk)->post(route('app.front-desk.check-in', $near->id), ['confirm_early' => 1])->assertRedirect();
    expect(vy05Status($near))->toBe('checked_in');

    // …and Front Desk with NO allow-list (the whole organisation) still checks the far guest in
    $everywhere = operatorUser($s, [], 'Front Desk');
    $this->actingAs($everywhere)->post(route('app.front-desk.check-in', $far['reservation']->id), ['confirm_early' => 1])->assertRedirect();
    expect(vy05Status($far['reservation']))->toBe('checked_in');

    // the reservation page: refused for the far booking, open for their own, open to the organisation-wide colleague
    $this->actingAs($desk)->get(route('app.reservations.show', $far['reservation']->id))->assertNotFound();
    $this->actingAs($desk)->get(route('app.reservations.show', $near->id))->assertOk();
    $this->actingAs($everywhere)->get(route('app.reservations.show', $far['reservation']->id))->assertOk();
})->group('security');

it('⚠️⚠️ VY-05: EVERY reservation, folio and front-desk route is a 404 outside the allow-list — and writes nothing', function () {
    $s = seedScenario(1);
    $far = vy05Far($s);
    $r = $far['reservation'];
    // ⚠️ Holds EVERY permission, so a refusal below is the property scope and never a missing permission (a masked 403).
    $scoped = vy05ScopedTo($s, (string) $s['property']->id, 'Property Owner');

    $payment = Payment::query()->where('reservation_id', $r->id)->where('type', 'payment')->firstOrFail();
    $hold = app(FolioService::class)->authorizeDeposit(Folio::query()->where('reservation_id', $r->id)->firstOrFail(), moneyEur(5000), 'pm_card_visa');
    $before = [
        'reservation' => (array) DB::table('reservations')->where('id', $r->id)->first(),
        'folio_items' => DB::table('folio_items')->whereIn('folio_id', Folio::query()->where('reservation_id', $r->id)->select('id'))->count(),
        'payments' => DB::table('payments')->where('reservation_id', $r->id)->count(),
        'lines' => DB::table('reservation_units')->where('reservation_id', $r->id)->get()->map(fn ($l) => (array) $l)->all(),
    ];

    // ⚠️ ORDER: controller routes first, the two Livewire pages last (see the first test).
    foreach (['app.reservations.folio-pdf', 'app.front-desk.reverse-checkin', 'app.front-desk.reverse-checkout'] as $name) {
        $this->actingAs($scoped)->get(route($name, $r->id))->assertNotFound();
    }

    $posts = [
        'app.front-desk.check-in' => ['confirm_early' => 1],
        'app.front-desk.check-out' => [],
        'app.front-desk.reverse-checkin.store' => ['reason' => 'wrong guest'],
        'app.front-desk.reverse-checkout.store' => ['reason' => 'wrong guest'],
        'app.reservations.move' => ['unit_id' => $far['other']->id],
        'app.reservations.reschedule' => ['check_in' => '2026-09-04', 'check_out' => '2026-09-06'],
        'app.reservations.cancel' => ['reason' => 'from afar'],
        'app.reservations.confirm' => [],
        'app.reservations.no-show' => [],
        'app.reservations.charge' => ['type' => 'manual', 'description' => 'Minibar', 'amount' => '10.00', 'idempotency_key' => 'vy05-refused'],
        'app.reservations.payment' => ['amount' => '10.00', 'method' => 'cash'],
        'app.reservations.refund' => ['payment' => $payment->id, 'amount' => '10.00', 'reason' => 'other'],
        'app.reservations.bill-to-account' => ['company_account_id' => 'x'],
        'app.reservations.unbill-from-account' => [],
    ];
    foreach ($posts as $name => $body) {
        $this->actingAs($scoped)->post(route($name, $r->id), $body)->assertNotFound();
    }
    // ⚠️ a REAL hold on the far booking: with an id that does not exist the route would be a 404 whatever the property scope did
    $this->actingAs($scoped)->post(route('app.reservations.hold-release', [$r->id, $hold->id]))->assertNotFound();
    expect((string) DB::table('payment_authorizations')->where('id', $hold->id)->value('status'))->toBe('authorized');

    // NOTHING moved on the far booking
    $unchanged = function () use ($r, $before): void {
        expect((array) DB::table('reservations')->where('id', $r->id)->first())->toBe($before['reservation'])
            ->and(DB::table('folio_items')->whereIn('folio_id', Folio::query()->where('reservation_id', $r->id)->select('id'))->count())->toBe($before['folio_items'])
            ->and(DB::table('payments')->where('reservation_id', $r->id)->count())->toBe($before['payments'])
            ->and(DB::table('reservation_units')->where('reservation_id', $r->id)->get()->map(fn ($l) => (array) $l)->all())->toBe($before['lines']);
    };
    $unchanged();

    // PERMITTED CONTROL — an organisation-wide Property Owner drives the same routes on the same booking, so each 404 above was the scope
    $all = operatorUser($s, [], 'Property Owner');
    $this->actingAs($all)->get(route('app.reservations.folio-pdf', $r->id))->assertOk();
    $this->actingAs($all)->post(route('app.reservations.charge', $r->id), ['type' => 'manual', 'description' => 'Minibar', 'amount' => '10.00', 'idempotency_key' => 'vy05-permitted'])->assertRedirect();
    expect(DB::table('folio_items')->whereIn('folio_id', Folio::query()->where('reservation_id', $r->id)->select('id'))->count())->toBe($before['folio_items'] + 1);
    $this->actingAs($all)->post(route('app.reservations.cancel', $r->id), ['reason' => 'permitted'])->assertRedirect();
    expect(vy05Status($r))->toBe('cancelled');

    // the two pages: a 404 for the scoped person, open to the organisation-wide one
    foreach (['app.reservations.show', 'app.reservations.refund-screen'] as $name) {
        $this->actingAs($scoped)->get(route($name, $r->id))->assertNotFound();
        $this->actingAs($all)->get(route($name, $r->id))->assertOk();
    }
})->group('security');

it('⚠️ VY-05: a guest change request on another property\'s booking cannot be confirmed or declined', function () {
    $s = seedScenario(1);
    $far = vy05Far($s);
    $scoped = vy05ScopedTo($s, (string) $s['property']->id, 'Property Owner');

    $request = GuestChangeRequest::create([
        'reservation_id' => $far['reservation']->id, 'guest_id' => $s['guest']->id, 'type' => 'cancellation', 'status' => 'pending', 'payload' => [],
    ]);

    $this->actingAs($scoped)->post(route('app.guest-requests.confirm', $request->id))->assertNotFound();
    $this->actingAs($scoped)->post(route('app.guest-requests.decline', $request->id))->assertNotFound();
    expect((string) DB::table('guest_change_requests')->where('id', $request->id)->value('status'))->toBe('pending')
        ->and(vy05Status($far['reservation']))->toBe('confirmed');

    // PERMITTED CONTROL: the organisation-wide owner declines it
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.guest-requests.decline', $request->id))->assertRedirect();
    expect((string) DB::table('guest_change_requests')->where('id', $request->id)->value('status'))->toBe('declined');
})->group('security');

it('VY-05: the scope itself — a reservation outside the allow-list is not found, one inside is; no property context finds nothing', function () {
    $s = seedScenario(1);
    $far = vy05Far($s);
    $near = vy05Near($s);
    $context = app(PropertyContext::class);

    $context->forProperty([(string) $s['property']->id], null, function () use ($far, $near): void {
        expect(app(PropertyScope::class)->reservation((string) $near->id)->is($near))->toBeTrue()
            ->and(fn () => app(PropertyScope::class)->reservation((string) $far['reservation']->id))->toThrow(ModelNotFoundException::class);
    });

    // FAIL CLOSED: with no context there is no allow-list, so nothing is found — never "everything"
    $context->deactivate();
    expect(fn () => app(PropertyScope::class)->reservation((string) $near->id))->toThrow(ModelNotFoundException::class);
})->group('security');

it('⚠️ VY-05: the reservation pages hold their id LOCKED — a wire update cannot swap in another property\'s booking', function () {
    $s = seedScenario(1);
    $far = vy05Far($s);
    $near = vy05Near($s);
    $scoped = vy05ScopedTo($s, (string) $s['property']->id, 'Property Owner');

    app(PropertyContext::class)->activate([(string) $s['property']->id], null);

    foreach ([ReservationDetail::class, ReservationRefund::class] as $component) {
        // mounting the far booking is a 404 for this person
        // ⚠️ 2026-11-24 (VY-11, Livewire 4.3.3 → 4.4.9): the test harness used to let the mount's ModelNotFoundException escape; it now
        //    answers as the router does — the 404 page. The subject is unchanged and is asserted from BOTH sides: the status is 404
        //    AND the far booking's reference is nowhere in what was rendered.
        Livewire::actingAs($scoped)->test($component, ['reservation' => (string) $far['reservation']->id])
            ->assertStatus(404)
            ->assertDontSee((string) $far['reservation']->reference);

        // …and a page mounted on their own booking refuses to be pointed at the far one
        expect(fn () => Livewire::actingAs($scoped)->test($component, ['reservation' => (string) $near->id])
            ->set('reservationId', (string) $far['reservation']->id))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    }
})->group('security');
