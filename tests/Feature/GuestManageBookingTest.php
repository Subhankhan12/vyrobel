<?php

use App\Models\AddOn;
use App\Models\Guest;
use App\Models\GuestChangeRequest;
use App\Models\Reservation;
use App\Models\Tax;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Services\GuestChangeRequestService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GUEST MANAGE BOOKING (Phase 4b · Sub-gate 3) — the careful one. A guest changing dates / adding extras /
// cancelling touches the operator's inventory/folio/money, so EVERYTHING is a pending REQUEST the operator
// confirms. Proven: a guest creates only a pending request (owned booking, foreign → 404); the guest NEVER does
// the real write (the booking is unchanged); the guest CAN'T self-approve (can't reach the operator confirm);
// only the operator confirms (status only — the execute stays the operator's certified tools).

if (! function_exists('mgrGuest')) {
    function mgrGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

if (! function_exists('mgrBook')) {
    function mgrBook(array $s, Guest $guest): Reservation
    {
        return app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 10000, 'currency' => 'EUR']],
        );
    }
}

function guestChangeRequestFor(Reservation $reservation): ?GuestChangeRequest
{
    return GuestChangeRequest::withoutOrganizationScope()->where('reservation_id', $reservation->getKey())->latest()->first();
}

// ── Every change is a pending REQUEST; the booking is NEVER written by the guest ──────────────────
it('records a date-change / add-on / cancellation as pending requests, leaving the booking untouched', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);

    // Real, active AddOns of THIS property — the request stores their ids (a guest can't invent a name/price).
    $breakfast = AddOn::create(['property_id' => $s['property']->id, 'name' => 'Breakfast', 'price' => Money::of(1800, 'EUR'), 'taxable' => true, 'is_active' => true]);
    $late = AddOn::create(['property_id' => $s['property']->id, 'name' => 'Late checkout', 'price' => Money::of(4000, 'EUR'), 'taxable' => true, 'is_active' => true]);

    $this->actingAs($a, 'guest')->post(route('guest.manage.date-change', $reservation->getKey()), ['new_check_in' => '2026-10-01', 'new_check_out' => '2026-10-04'])->assertRedirect(route('guest.manage', $reservation->getKey()));
    $this->actingAs($a, 'guest')->post(route('guest.manage.add-ons', $reservation->getKey()), ['add_ons' => [$breakfast->id, $late->id]])->assertRedirect();
    $this->actingAs($a, 'guest')->post(route('guest.manage.cancel', $reservation->getKey()), ['reason' => 'Change of plans'])->assertRedirect();

    // Three pending requests — and nothing on the booking moved.
    $requests = GuestChangeRequest::withoutOrganizationScope()->where('reservation_id', $reservation->getKey())->get();
    expect($requests)->toHaveCount(3);
    expect($requests->pluck('status')->unique()->all())->toBe(['pending']);
    $fresh = $reservation->fresh();
    expect($fresh->check_in->toDateString())->toBe('2026-09-01');   // dates NOT moved
    expect($fresh->status)->toBe('confirmed');                       // NOT cancelled
});

// ── The guest does NOT run the real cancel-and-refund (mutation-proven) ───────────────────────────
it('does not cancel the booking when the guest requests cancellation', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);

    $this->actingAs($a, 'guest')->post(route('guest.manage.cancel', $reservation->getKey()), ['reason' => 'Too expensive'])->assertRedirect();

    expect(guestChangeRequestFor($reservation)?->status)->toBe('pending');
    expect($reservation->fresh()->status)->toBe('confirmed');   // the certified cancel did NOT run — only a request
});

// ── ISOLATION (mutation-proven): a guest can't manage another guest's booking ─────────────────────
it('404s when a guest manages another guest’s booking', function () {
    $s = seedScenario(2);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $aRes = mgrBook($s, $a);
    $bRes = mgrBook($s, $b);

    $this->actingAs($a, 'guest')->get(route('guest.manage', $aRes->getKey()))->assertOk();               // own → ok
    $this->actingAs($a, 'guest')->get(route('guest.manage', $bRes->getKey()))->assertNotFound();          // foreign → 404
    $this->actingAs($a, 'guest')->post(route('guest.manage.cancel', $bRes->getKey()), ['reason' => 'Other'])->assertNotFound();
});

// ── The guest CAN'T self-approve its own request (only the operator confirms) ─────────────────────
it('never lets a guest confirm its own request', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);
    $this->actingAs($a, 'guest')->post(route('guest.manage.cancel', $reservation->getKey()), ['reason' => 'Change of plans']);
    $request = guestChangeRequestFor($reservation);

    // A guest can't reach the operator confirm route (it's /app — the guest is refused, not authorised).
    $this->actingAs($a, 'guest')->post(route('app.guest-requests.confirm', $request->getKey()));
    expect($request->fresh()->status)->toBe('pending');   // still pending — the guest could not self-approve
});

// ── Only the OPERATOR confirms — and confirm is status-only (the booking isn't auto-written) ──────
it('lets the operator confirm a request without executing the real change', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);
    $request = app(GuestChangeRequestService::class)->requestCancellation($reservation, $a, 'Change of plans');
    $operator = operatorUser($s, [], 'Hotel Manager');

    // Clear the ambient tenant context so the request runs the REAL middleware path (SetTenantForWebRequest must
    // set it) — otherwise a leftover singleton hides that a route-model-bound {guestRequest} resolves with NO
    // context and 500s (the browser found exactly that). The route param must be resolved INSIDE the action.
    app(TenantContext::class)->forget();

    $this->actingAs($operator)->post(route('app.guest-requests.confirm', $request->getKey()))->assertRedirect();

    expect($request->fresh()->status)->toBe('confirmed');
    expect($reservation->fresh()->status)->toBe('confirmed');   // confirm is status-only — no auto inventory/refund write
});

// ── Cross-org isolation: an operator can't confirm ANOTHER org's guest request (tenant scope) ─────
it('404s when an operator confirms another org’s guest request', function () {
    $orgA = seedScenario(1);
    $guestA = mgrGuest($orgA['guest'], 'ada@example.test');
    $requestA = app(GuestChangeRequestService::class)->requestCancellation(mgrBook($orgA, $guestA), $guestA, 'Change of plans');

    $orgB = seedScenario(1);                                   // a different org…
    $operatorB = operatorUser($orgB, [], 'Hotel Manager');     // …and its operator
    app(TenantContext::class)->forget();

    $this->actingAs($operatorB)->post(route('app.guest-requests.confirm', $requestA->getKey()))->assertNotFound();
    expect($requestA->fresh()->status)->toBe('pending');       // untouched by the foreign operator
});

// ── The date-change quote is REAL + read-only (a GET preview writes nothing) ──────────────────────
it('previews a read-only date-change quote without creating any request', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);

    $this->actingAs($a, 'guest')->get(route('guest.manage', $reservation->getKey()).'?new_check_in=2026-10-01&new_check_out=2026-10-04')
        ->assertOk()
        ->assertSee('Request date change');   // the quote panel + request button rendered

    expect(GuestChangeRequest::withoutOrganizationScope()->where('reservation_id', $reservation->getKey())->count())->toBe(0);   // read-only — nothing written
});

// ── D4 + #17: confirming a guest add-on request POSTS the real folio lines (breakfast + its VAT) ─────
it('posts the confirmed add-ons to the folio, reconciling to the cent (#17)', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);

    // A real folio with the room charge already on it.
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(10000, 'EUR'));
    $before = (int) FolioTotals::for($folio->refresh())['charges_minor'];

    // A flat €18 taxable extra + a real 23% VAT on the property.
    $extra = AddOn::create(['property_id' => $s['property']->id, 'name' => 'Spa voucher', 'price' => Money::of(1800, 'EUR'), 'taxable' => true, 'is_active' => true]);
    Tax::create(['property_id' => $s['property']->id, 'name' => 'VAT', 'mode' => 'percentage', 'rate' => 23, 'inclusive' => false, 'currency' => 'EUR']);

    // Guest REQUESTS it → pending; the folio is NOT touched (a guest never writes the operator's folio).
    $request = app(GuestChangeRequestService::class)->requestAddOns($reservation, $a, [$extra->id]);
    expect((int) FolioTotals::for($folio->refresh())['charges_minor'])->toBe($before);

    // Operator CONFIRMS → the real folio lines post: €18 net + €4.14 VAT (23%) = €22.14, reconciling (#17).
    app(GuestChangeRequestService::class)->confirm($request);
    $after = (int) FolioTotals::for($folio->refresh())['charges_minor'];
    expect($after - $before)->toBe(2214);
    expect($request->fresh()->status)->toBe('confirmed');

    // Idempotent: a second confirm never double-posts.
    app(GuestChangeRequestService::class)->confirm($request->fresh());
    expect((int) FolioTotals::for($folio->refresh())['charges_minor'])->toBe($after);
});

// ── The extras page renders the REAL AddOn catalogue (never the wireframe's invented items) ─────────
it('renders the real add-on catalogue on the extras page, VAT-inclusive', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);
    AddOn::create(['property_id' => $s['property']->id, 'name' => 'Spa voucher', 'price' => Money::of(1800, 'EUR'), 'taxable' => true, 'is_active' => true]);
    Tax::create(['property_id' => $s['property']->id, 'name' => 'VAT', 'mode' => 'percentage', 'rate' => 23, 'inclusive' => false, 'currency' => 'EUR']);

    $this->actingAs($a, 'guest')->get(route('guest.extras', $reservation->getKey()))
        ->assertOk()
        ->assertSee('Enhance your stay')
        ->assertSee('Spa voucher')
        ->assertSee('€22.14')          // 18 net + 23% VAT = VAT-inclusive display (#17)
        ->assertSee('Prices include VAT');
});

// ── G2 pixel-diff HONESTY: the wireframe's retention upsell is refused; the real cancellation policy shows ──
it('shows the real cancellation policy and refuses the fabricated "convert to travel credit" upsell (#39)', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $res = mgrBook($s, $a);

    $page = $this->actingAs($a, 'guest')->get(route('guest.manage', $res->getKey()))->assertOk();

    $page->assertSee('Manage your booking')
        ->assertSee('Cancellation policy')
        ->assertSee('Request cancellation')
        // #39: no "Convert to travel credit" / "Instead of losing your deposit" — there is no such backend.
        ->assertDontSee('Convert to travel credit')
        ->assertDontSee('Instead of losing your deposit');
});

// ── §N.8d: a PENDING request renders as pending — in its TEXT and in its VISUAL signal ────────────
// The guest asks, the operator decides. A pending cancellation must never read (or LOOK) like a done one:
// §N.8d's rule is that the visual signal outlives the text, so a green tick or a struck-through booking would
// mislead even beside honest words. Both directions matter — an in-flight request that renders as "nothing
// requested" issues a false instruction and the guest requests again.
it('renders an in-flight request as Pending, never as done (#N.8d, text and visual)', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);
    app(GuestChangeRequestService::class)->requestCancellation($reservation, $a, 'Change of plans');

    $html = $this->actingAs($a, 'guest')->get(route('guest.manage', $reservation->getKey()))
        ->assertOk()
        ->assertSee('Cancellation requested')   // the in-flight request IS surfaced (understating direction)
        ->assertSee('Pending')                  // …as pending
        ->getContent();

    // …and never as an accomplished fact, in text OR in the visual language of one.
    // ⚠️ Assert the APPLIED class, not the stylesheet: `.vy-badge--confirmed` is DEFINED in the shared shell's
    // <style> on every guest page, so its mere presence in the HTML proves nothing.
    expect($html)->toContain('vy-badge vy-badge--pending')   // the amber pending plate IS the one applied
        ->and($html)->not->toContain('vy-badge vy-badge--confirmed')   // …and no green confirmed plate is
        ->and($html)->not->toContain('line-through')         // no struck-through booking
        ->and($html)->not->toContain('Cancelled');           // the word, capitalised as a status
    expect($reservation->fresh()->status)->toBe('confirmed');   // the booking itself is untouched
});

// ── §N.4k: the destructive request needs a deliberate confirmation ────────────────────────────────
// Measured in the browser: with a reason chosen, Enter on the reason <select> fired the cancellation request
// immediately — the guest means "confirm my choice" and gets "send the request", with no chance to reconsider
// on the most consequential action of the page. The prototype puts a review step in front of it.
it('guards the cancellation request behind an explicit confirmation', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);

    $html = $this->actingAs($a, 'guest')->get(route('guest.manage', $reservation->getKey()))->assertOk()->getContent();

    expect($html)->toContain('onsubmit="return confirm(')
        ->and($html)->toContain('data-guard-submit');   // and the shared double-submit guard
});

// ── #39: the extras page must not promise a cancellation right the app cannot honour ──────────────
// "Free to cancel up to 24h before arrival" shipped verbatim from the prototype. Nothing implements it —
// no cancellation window on AddOn / ReservationExtras / config, and no removal path at all (the only extras
// route is a GET; an add-on can only be REQUESTED). Same family as the "48h" literal fixed at G2, except that
// one at least mirrored a real constant.
it('never promises an add-on cancellation window the app does not implement (#39)', function () {
    $s = seedScenario(1);
    $a = mgrGuest($s['guest'], 'ada@example.test');
    $reservation = mgrBook($s, $a);
    AddOn::factory()->create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id,
        'name' => 'Breakfast', 'price_minor' => 1800, 'currency' => 'EUR', 'is_active' => true,
    ]);

    $html = $this->actingAs($a, 'guest')->get(route('guest.extras', $reservation->getKey()))->assertOk()
        ->assertSee('Breakfast')                       // the real catalogue renders (never vacuous)
        ->assertSee('Your host confirms')              // the honest request framing survives
        ->getContent();

    expect($html)->not->toContain('Free to cancel')
        ->and($html)->not->toContain('24h before arrival');
});
