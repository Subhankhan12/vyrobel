<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Booking\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Property;
use App\Services\AssignmentService;
use App\Services\GroupBookingService;
use App\Services\UnitAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Operator GROUP BOOKING write (Phase 1 · Sub-gate 1) — a block of rooms created ATOMICALLY through
 * {@see GroupBookingService::create()} → {@see AssignmentService::createGroupBooking()}, the
 * single inventory write path. Locked operator-mutation pattern (POST → controller → service → PRG), the same
 * shape as {@see ReservationCreateController}.
 *
 * The guards carried from New Reservation, applied per member:
 *   • GUARD 1 — HONEST AVAILABILITY + SERVER PRICE. Every chosen unit is re-derived against
 *     {@see UnitAvailabilityService} for the block dates (never a client-sent price, never a client claim a
 *     unit is free). A unit not in the free/priced set → the block is refused, nothing created. `createGroupBooking`
 *     then re-checks each under `SELECT … FOR UPDATE`, and the WHOLE block rolls back on any race — never a
 *     double book, never a phantom event.
 *   • GUARD 2 — PII. The organiser's email/phone are written to the ENCRYPTED Guest columns, never echoed.
 *   • ATOMICITY — all-or-nothing (the service's outer transaction); an unavailable member rolls the block back.
 *
 * TENANT + PERMISSION: Property/RoomType/Unit/Guest are BelongsToOrganization (cross-org id → "not available");
 * `/app` is behind EnsureBackOfficeAccess (#26) and the route carries `can:reservations.create` (#30).
 */
class GroupBookingController extends Controller
{
    private const OCCASIONS = ['wedding', 'corporate', 'family', 'sports', 'other'];

    public function store(Request $request, GroupBookingService $groups, UnitAvailabilityService $availability): RedirectResponse
    {
        $property = Property::query()->orderBy('name')->first();
        if ($property === null) {
            return $this->back($request, 'No property is configured yet.');
        }

        $checkIn = $this->dateString($request->input('check_in'));
        $checkOut = $this->dateString($request->input('check_out'));
        if ($checkIn === null || $checkOut === null || $checkOut <= $checkIn) {
            return $this->back($request, 'Choose valid stay dates for the block.');
        }

        $name = trim((string) $request->input('name'));
        if ($name === '') {
            return $this->back($request, 'Give the block a name, e.g. “Okafor–Bello Wedding”.');
        }

        $unitIds = array_values(array_filter(array_map(
            fn ($id): string => trim((string) $id),
            (array) $request->input('units', []),
        ), fn (string $id): bool => $id !== ''));
        if ($unitIds === []) {
            return $this->back($request, 'Add at least one unit to the block.');
        }

        // GUARD 1 (server-side): re-derive the authoritative price + rate plan for every offered unit. A unit
        // the client submits that is NOT in the free+priced set for these dates → refuse the whole block.
        $unitMap = $this->availableUnitMap($availability, (string) $property->getKey(), $checkIn, $checkOut);

        $members = [];
        foreach ($unitIds as $unitId) {
            if (! isset($unitMap[$unitId])) {
                return $this->back($request, 'One of the chosen units is no longer free or priced for those dates. Re-check availability.');
            }
            $members[] = $unitMap[$unitId];
        }

        $currency = $members[0]['currency'];
        if (collect($members)->pluck('currency')->unique()->count() > 1) {
            return $this->back($request, 'All units in a block must share one currency.');
        }

        $occasion = in_array($request->input('occasion'), self::OCCASIONS, true) ? (string) $request->input('occasion') : null;
        $billingMode = in_array($request->input('billing_mode'), ['individual', 'master'], true) ? (string) $request->input('billing_mode') : 'individual';
        $tentative = $request->input('hold_type') === 'tentative';
        $status = $tentative ? 'tentative' : 'confirmed';
        $pickupDeadline = $tentative ? ($this->dateString($request->input('pickup_deadline')) ?? $checkIn) : null;

        $adults = max(1, min(30, (int) $request->input('adults', 2)));
        $children = max(0, min(30, (int) $request->input('children', 0)));

        $organiser = $this->resolveOrganiser($request);
        if ($organiser instanceof RedirectResponse) {
            return $organiser;
        }

        try {
            $group = $groups->create([
                'property_id' => (string) $property->getKey(),
                'name' => $name,
                'occasion' => $occasion,
                'billing_mode' => $billingMode,
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'currency' => $currency,
                'adults' => $adults,
                'children' => $children,
                'organiser_guest_id' => (string) $organiser->getKey(),
                'pickup_deadline' => $pickupDeadline,
                'contact' => $this->organiserContact($request, $organiser),
                'members' => array_map(fn (array $m): array => [
                    'room_type_id' => $m['room_type_id'],
                    'unit_id' => $m['unit_id'],
                    'rate_plan_id' => $m['rate_plan_id'],
                    'price_minor' => $m['price_minor'],
                ], $members),
            ]);
        } catch (BookingException) {
            return $this->back($request, 'A unit in the block was just taken for those dates — nothing was created. Re-check availability.');
        }

        $count = $group->reservations->count();
        $notice = 'Group “'.$group->name.'” ('.$group->block_code.') created — '.$count.' '.($count === 1 ? 'room' : 'rooms')
            .($status === 'tentative' ? ' held tentatively.' : ' confirmed.');

        return redirect()->to(route('app.rack'))->with('group_notice', $notice);
    }

    /**
     * A map of unit_id → its authoritative booking spec, for every unit genuinely free + priced for the dates.
     *
     * @return array<string, array{unit_id: string, room_type_id: string, rate_plan_id: ?string, price_minor: int, currency: string, room_type_name: string}>
     */
    private function availableUnitMap(UnitAvailabilityService $availability, string $propertyId, string $checkIn, string $checkOut): array
    {
        $map = [];
        foreach ($availability->availableRoomTypes($propertyId, $checkIn, $checkOut) as $roomType) {
            if ($roomType['priced'] !== true) {
                continue;   // an unpriced room type is never bookable — its units aren't offered
            }
            foreach ($roomType['units'] as $unit) {
                $map[$unit['id']] = [
                    'unit_id' => (string) $unit['id'],
                    'room_type_id' => (string) $roomType['room_type_id'],
                    'rate_plan_id' => $roomType['rate_plan_id'],
                    'price_minor' => (int) $roomType['price_minor'],
                    'currency' => (string) $roomType['currency'],
                    'room_type_name' => (string) $roomType['room_type_name'],
                ];
            }
        }

        return $map;
    }

    /** The organiser guest — an existing tenant-scoped guest, or a newly-created one (encrypted contact). */
    private function resolveOrganiser(Request $request): Guest|RedirectResponse
    {
        $existingId = $request->input('existing_guest_id');
        if (is_string($existingId) && $existingId !== '') {
            $guest = Guest::query()->find($existingId);   // tenant-scoped
            if ($guest === null) {
                return $this->back($request, 'That organiser could not be found.');
            }

            return $guest;
        }

        $first = trim((string) $request->input('organiser_first_name', ''));
        $last = trim((string) $request->input('organiser_last_name', ''));
        if ($first === '' && $last === '') {
            return $this->back($request, 'Enter the organiser’s name, or pick an existing guest.');
        }

        $email = trim((string) $request->input('organiser_email', ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->back($request, 'The organiser’s email address isn’t valid.');
        }

        return Guest::create([
            'first_name' => $first !== '' ? $first : 'Organiser',
            'last_name' => $last,
            'email' => $email !== '' ? $email : null,
            'phone' => ($p = trim((string) $request->input('organiser_phone', ''))) !== '' ? $p : null,
        ]);
    }

    /**
     * The organiser block stored on `groups.contact` (json) — NON-PII only: name + company. Email/phone live
     * ENCRYPTED on the Guest row, never duplicated into this plaintext json (an encrypted column is undone the
     * moment its value is copied somewhere plaintext).
     *
     * @return array<string, string>
     */
    private function organiserContact(Request $request, Guest $organiser): array
    {
        $name = trim(((string) $organiser->first_name).' '.((string) $organiser->last_name));

        return array_filter([
            'name' => $name !== '' ? $name : 'Organiser',
            'company' => trim((string) $request->input('organiser_company', '')),
        ], fn (string $v): bool => $v !== '');
    }

    private function dateString(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }

    /** PRG back to the group screen, preserving the dates so the availability view isn't lost. */
    private function back(Request $request, string $notice): RedirectResponse
    {
        $params = array_filter([
            'check_in' => $this->dateString($request->input('check_in')),
            'check_out' => $this->dateString($request->input('check_out')),
        ], fn (?string $v): bool => $v !== null);

        return redirect()->to(route('app.group-booking', $params))->with('group_notice', $notice);
    }
}
