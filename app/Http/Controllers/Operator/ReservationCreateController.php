<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Booking\BookingException;
use App\Http\Controllers\Controller;
use App\Livewire\Operator\NewReservation;
use App\Models\AddOn;
use App\Models\Fee;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Tax;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Services\GuestWalletService;
use App\Services\UnitAvailabilityService;
use App\Support\Folio\PropertyCharges;
use App\Support\Folio\StayEstimator;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Money\MoneyInput;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Reservations\ReservationExtras;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Operator NEW RESERVATION write (Wave 4 · Sub-gate 2) — a single direct booking created through the
 * CERTIFIED single write path {@see AssignmentService::createBooking()}, via the locked operator-mutation
 * pattern (POST → controller → service → PRG), exactly as {@see ReservationLifecycleController}.
 *
 * The three guards from docs/WAVE4-INVENTORY.md (the screen is "SAFE as drawn: ❌" without them):
 *   • GUARD 1 — HONEST AVAILABILITY. The unit + price are re-derived SERVER-SIDE from
 *     {@see UnitAvailabilityService} (never trust a client-sent price or a client claim that a unit is free):
 *     the chosen room type must be offered for the dates AND priced, and the chosen unit must be in its free
 *     set. createBooking then re-checks under `SELECT … FOR UPDATE`, so a unit taken between the read and the
 *     write is rejected here and surfaced — never double-booked. This screen cannot weaken that guarantee.
 *   • GUARD 2 — PII. A new guest's email/phone are written to the ENCRYPTED columns (the same real path
 *     BookingEngineService::book() uses); they are collected, never rendered back. The existing-guest picker
 *     ({@see NewReservation}) is NAME-only.
 *   • GUARD 3 — NO RAW PAN (PCI). This controller NEVER accepts a card number. An optional payment is a
 *     MANUAL folio payment ({@see FolioService::recordPayment()} over the ManualPaymentGateway — cash /
 *     bank / pay-at-desk), gated on `payments.record`. Card capture is deferred to the account-gated Stripe
 *     tokenization and stubbed "Soon" in the UI; there is no PAN field on the screen or in this code.
 *
 * TENANT + PERMISSION. `{room_type}`/`{unit}`/`{guest}` are resolved through the BelongsToOrganization global
 * scope (a cross-org id is simply "not available" — never leaked), `/app` sits behind EnsureBackOfficeAccess
 * (#26), and the route carries `can:reservations.create` (#30). The optional payment additionally checks
 * `can:payments.record` before any money is recorded — separation of duties (a Reservation Agent may create
 * a booking but not take a payment).
 */
class ReservationCreateController extends Controller
{
    /** The `reservations.source` values an operator may create here — NOT `ota` (imported) or `group` (out of scope). */
    private const SOURCES = ['direct', 'walk_in', 'corporate', 'agent'];

    public function store(
        Request $request,
        AssignmentService $assignments,
        FolioService $folios,
        UnitAvailabilityService $availability,
    ): RedirectResponse {
        // READ-ONLY MODE (Operator Dashboard/Calendar exact-match): when the workspace is paused, new bookings
        // are OFF (the wireframe's rule). Enforced at the SINGLE certified write path, so both the calendar
        // quick-panel AND the full form respect it — the UI also hides the affordance, this is the real guard.
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($request, 'Your workspace is paused (read-only) — new bookings are off until you resume. Resume from Billing.');
        }

        $checkIn = $this->dateString($request->input('check_in'));
        $checkOut = $this->dateString($request->input('check_out'));

        if ($checkIn === null || $checkOut === null || $checkOut <= $checkIn) {
            return $this->back($request, 'Choose valid check-in and check-out dates.');
        }

        $roomTypeId = trim((string) $request->input('room_type_id'));
        $unitId = trim((string) $request->input('unit_id'));

        if ($roomTypeId === '' || $unitId === '') {
            return $this->back($request, 'Choose a room and a specific unit for the stay.');
        }

        $roomType = RoomType::query()->find($roomTypeId);   // tenant-scoped → cross-org is simply "not found"
        if ($roomType === null) {
            return $this->back($request, 'That room type isn’t available.');
        }

        // Phase 6a · multi-property: the reservation lands in the ROOM TYPE's property (a booking for
        // room type X is in X's property). NewReservation only offers the current property's room types,
        // but a crafted POST could submit an in-org room type from a property the user isn't assigned to —
        // so honour the property_user allow-list on the write path too, not just in the picker.
        $context = app(PropertyContext::class);
        if ($context->active() && ! in_array((string) $roomType->property_id, $context->accessibleIds(), true)) {
            return $this->back($request, 'That room type isn’t available.');
        }

        // GUARD 1, server-side: re-derive what is genuinely bookable + the authoritative price. The client
        // never supplies a price, and a unit it claims is free is verified against the real availability read.
        $offered = collect($availability->availableRoomTypes((string) $roomType->property_id, $checkIn, $checkOut))
            ->firstWhere('room_type_id', $roomTypeId);

        if (! is_array($offered) || $offered['priced'] !== true) {
            return $this->back($request, 'That room isn’t available or priced for those dates. Re-check availability.');
        }

        if (Collection::make($offered['units'])->firstWhere('id', $unitId) === null) {
            return $this->back($request, 'That unit is no longer free for those dates. Re-check availability.');
        }

        $currency = (string) $offered['currency'];
        $roomChargeMinor = (int) $offered['price_minor'];

        $adults = max(1, min(30, (int) $request->input('adults', 1)));
        $children = max(0, min(30, (int) $request->input('children', 0)));
        $source = in_array($request->input('source'), self::SOURCES, true) ? (string) $request->input('source') : 'direct';

        // Payment intent — validated + permission-gated BEFORE any write. Card is refused (deferred), never
        // a PAN. A manual payment may not exceed the room charge at creation time.
        $method = (string) $request->input('payment_method', 'later');
        $paymentMinor = null;

        if ($method === 'card') {
            return $this->back($request, 'Card payments are coming soon — take cash or bank transfer now, or record it later from the folio.');
        }

        if (in_array($method, ['cash', 'bank'], true)) {
            if ($request->user()?->cannot('payments.record')) {
                return $this->back($request, 'You don’t have permission to record a payment.');
            }
            $paymentMinor = MoneyInput::toMinor((string) $request->input('payment_amount', ''), $roomChargeMinor);
            if ($paymentMinor === null) {
                return $this->back($request, 'Enter a valid payment amount up to the room charge, e.g. 150 or 150.00.');
            }
        }

        $guest = $this->resolveGuest($request);
        if ($guest instanceof RedirectResponse) {
            return $guest;   // a validation failure already flashed
        }

        // THE CERTIFIED WRITE. createBooking guards inventory under a row lock; a race is rejected here (the
        // whole transaction rolls back), never a double book. An expected rejection must never 500.
        try {
            $reservation = $assignments->createBooking(
                [
                    'property_id' => (string) $roomType->property_id,
                    'currency' => $currency,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'adults' => $adults,
                    'children' => $children,
                    'lead_guest_id' => $guest->getKey(),
                    'source' => $source,
                ],
                [[
                    'room_type_id' => $roomTypeId,
                    'unit_id' => $unitId,
                    'rate_plan_id' => $offered['rate_plan_id'],
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'adults' => $adults,
                    'children' => $children,
                    'price_minor' => $roomChargeMinor,
                    'currency' => $currency,
                ]],
            );
        } catch (BookingException) {
            return $this->back($request, 'That unit was just taken for those dates — nothing was created. Re-check availability.');
        }

        // Open the folio and post the room charge, so the balance the operator sees is real.
        $folio = $folios->openFolio($reservation);
        $folios->postCharge($folio, 'room', 'Room · '.$offered['room_type_name'], Money::of($roomChargeMinor, $currency));

        // #90 — EXTRAS + TRANSFER + returning-guest CREDIT + property TAXES, all REAL folio lines. The folio
        // recompute() derives the total from the append-only ledger, so room + extras + transfer − credit + tax
        // reconciles by construction (#17). Every extra is a real org-owned AddOn; taxes are the property's real
        // Tax records; the credit is the guest's REAL spendable wallet balance (never a fabricated figure).
        $nights = (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));
        $taxableExtrasMinor = $this->postExtras($request, $folios, $folio, (string) $roomType->property_id, $adults + $children, $nights, $currency);
        $creditMinor = $this->applyCreditIfRequested($request, app(GuestWalletService::class), $folio, $reservation, $guest, $currency);
        // The percentage-tax base = room (taxable) + taxable extras − credit; per-night/per-person taxes ignore it.
        $taxableBase = Money::of(max(0, $roomChargeMinor + $taxableExtrasMinor - $creditMinor), $currency);
        $this->postTaxes($folios, $folio, (string) $roomType->property_id, $taxableBase, $nights, max(1, $adults + $children), $currency);
        // #98 GATE 2 — configured non-optional fees (cleaning) now POST to the folio (applyFee was never called);
        // reconciles with the Property-Settings sample-stay via the shared StayEstimator rules.
        $this->postFees($folios, $folio, (string) $roomType->property_id, $taxableBase, $nights, max(1, $adults + $children), $currency);

        $notice = 'Reservation '.$reservation->reference.' created.';
        if ($creditMinor > 0) {
            $notice .= ' '.MoneyFormatter::display($creditMinor, $currency).' credit applied.';
        }

        // Optional MANUAL payment (no card data ever) via the real folio path.
        if ($paymentMinor !== null) {
            $folios->recordPayment($folio, Money::of($paymentMinor, $currency));
            $notice .= ' '.MoneyFormatter::display($paymentMinor, $currency).' recorded as paid.';
        }

        return redirect()->to(route('app.reservations.show', $reservation->getKey()))->with('folio_notice', $notice);
    }

    /**
     * Post the selected EXTRAS + a transfer as REAL AddOn folio lines. Server-authoritative: the AddOn (price)
     * is re-read tenant + property-scoped, and the quantity is derived from the stay via {@see ReservationExtras}
     * — a client-sent price or quantity is never trusted. An id that isn't a real active AddOn of the property is
     * silently skipped (honest — no fabricated line).
     */
    private function postExtras(Request $request, FolioService $folios, Folio $folio, string $propertyId, int $guests, int $nights, string $currency): int
    {
        $selected = $request->input('extras', []);
        if (! is_array($selected) || $selected === []) {
            return 0;
        }

        $legs = $request->input('transfer_trip') === 'return' ? 2 : 1;

        $addOns = AddOn::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->whereIn('id', array_values(array_filter($selected, 'is_string')))
            ->get();

        $taxableMinor = 0;
        foreach ($addOns as $addOn) {
            if ((string) $addOn->currency !== $currency) {
                continue;   // never mix currencies on one folio
            }
            $quantity = ReservationExtras::quantityFor($addOn, $guests, $nights, $legs);
            $folios->postAddOn($folio, $addOn, $quantity);
            if ((bool) $addOn->taxable) {
                $taxableMinor += (int) $addOn->price_minor * $quantity;
            }
        }

        return $taxableMinor;
    }

    /**
     * Apply the returning guest's REAL wallet credit if the operator asked for it — capped at the folio's current
     * balance so it can never over-credit. Returns the minor amount actually applied (0 when none/declined).
     */
    private function applyCreditIfRequested(Request $request, GuestWalletService $wallet, Folio $folio, Reservation $reservation, Guest $guest, string $currency): int
    {
        if (! $request->boolean('apply_credit')) {
            return 0;
        }

        $spendable = $wallet->spendableMinor($guest);
        if ($spendable <= 0) {
            return 0;   // no real balance — nothing applied (never a fabricated €50)
        }

        // Never credit more than what is currently owed on the folio.
        $folio->refresh();
        $balance = (int) $folio->balance_minor;
        $apply = max(0, min($spendable, $balance));
        if ($apply <= 0) {
            return 0;
        }

        $wallet->applyToBooking($guest, $reservation, Money::of($apply, $currency));

        return $apply;
    }

    /**
     * Post the property's REAL taxes (VAT / city tax) as folio lines. The percentage taxes apply to the taxable
     * base (room + taxable extras − any credit = the current folio balance before tax); the per-night / per-person
     * taxes use the stay. No property tax configured → nothing posted (honest, no fabricated VAT).
     */
    // #104 GATE 1 — the tax + fee posting rules now live in ONE shared helper ({@see PropertyCharges}) so the
    // direct booking and the group block charge them through the SAME code and cannot drift (#17 across surfaces).
    private function postTaxes(FolioService $folios, Folio $folio, string $propertyId, Money $taxableBase, int $nights, int $persons, string $currency): void
    {
        PropertyCharges::postTaxes($folios, $folio, $propertyId, $taxableBase, $nights, $persons, $currency);
    }

    private function postFees(FolioService $folios, Folio $folio, string $propertyId, Money $base, int $nights, int $persons, string $currency): void
    {
        PropertyCharges::postFees($folios, $folio, $propertyId, $base, $nights, $persons, $currency);
    }

    /**
     * The lead guest: an existing tenant-scoped guest, or a newly-created one via the real path
     * (BookingEngineService::book()'s bare Guest::create). email/phone land in the ENCRYPTED columns and are
     * never rendered back. VIP + marketing consent (#86) are captured on a NEW guest. Returns a RedirectResponse
     * (already flashed) on a validation failure.
     */
    private function resolveGuest(Request $request): Guest|RedirectResponse
    {
        $existingId = $request->input('existing_guest_id');
        if (is_string($existingId) && $existingId !== '') {
            $guest = Guest::query()->find($existingId);   // tenant-scoped
            if ($guest === null) {
                return $this->back($request, 'That guest could not be found.');
            }

            return $guest;
        }

        $first = trim((string) $request->input('guest_first_name', ''));
        $last = trim((string) $request->input('guest_last_name', ''));

        if ($first === '' && $last === '') {
            return $this->back($request, 'Choose an existing guest, or enter a name for a new one.');
        }

        $email = trim((string) $request->input('guest_email', ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->back($request, 'That guest email address isn’t valid.');
        }
        $phone = trim((string) $request->input('guest_phone', ''));

        return Guest::create([
            'first_name' => $first !== '' ? $first : 'Guest',
            'last_name' => $last,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'is_vip' => $request->boolean('guest_vip'),
            'marketing_consent' => $request->boolean('guest_marketing_consent'),
        ]);
    }

    /** A Y-m-d string from request input, or null if it is not a real calendar date. */
    private function dateString(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }

    /** PRG back to the create screen, preserving the dates so the operator doesn't lose the availability view. */
    private function back(Request $request, string $notice): RedirectResponse
    {
        $params = array_filter([
            'check_in' => $this->dateString($request->input('check_in')),
            'check_out' => $this->dateString($request->input('check_out')),
        ], fn (?string $v): bool => $v !== null);

        return redirect()->to(route('app.reservations.create', $params))->with('new_reservation_notice', $notice);
    }
}
