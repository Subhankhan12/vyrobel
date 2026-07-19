<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Operator\GroupBookingController;
use App\Models\Group;
use App\Support\Money\Money;
use Illuminate\Support\Str;

/**
 * Application service for the operator GROUP BOOKING screen (Phase 1 · Sub-gate 1). Orchestrates a block
 * booking over EXISTING structures — never a parallel one:
 *   • the atomic INVENTORY write ({@see AssignmentService::createGroupBooking()} — the single write path,
 *     all-or-nothing, no phantom events) creates the `Group` + one member reservation per unit;
 *   • the FOLIO roll-up here decides how the block is billed:
 *       – `individual` — a folio per member reservation, each carrying its own room charge (each guest pays
 *         their unit);
 *       – `master`     — ONE folio on the first member (the organiser's reservation), carrying EVERY member's
 *         room charge (the organiser settles one bill). The other members hold no charge.
 *
 * All money is integer minor units end to end (each member's charge is the authoritative stay price the
 * controller re-derived server-side from {@see UnitAvailabilityService}). The caller ({@see GroupBookingController})
 * has already validated availability + prices; this service trusts validated input and does not re-open the guard.
 */
class GroupBookingService
{
    public function __construct(
        private AssignmentService $assignments,
        private FolioService $folios,
    ) {}

    /**
     * @param  array{property_id: string, name: string, occasion: ?string, billing_mode: string, status: string, check_in: string, check_out: string, currency: string, adults: int, children: int, organiser_guest_id: string, pickup_deadline?: ?string, contact?: ?array<string, mixed>, members: list<array{room_type_id: string, unit_id: string, rate_plan_id: ?string, price_minor: int}>}  $input
     */
    public function create(array $input): Group
    {
        $currency = $input['currency'];

        $groupAttributes = [
            'property_id' => $input['property_id'],
            'name' => $input['name'],
            'occasion' => $input['occasion'],
            'billing_mode' => $input['billing_mode'],
            'block_code' => 'GRP-'.Str::upper(Str::random(6)),
            'pickup_deadline' => $input['pickup_deadline'] ?? null,
            'contact' => $input['contact'] ?? null,
        ];

        $members = array_map(fn (array $member): array => [
            'reservation' => [
                'property_id' => $input['property_id'],
                'currency' => $currency,
                'check_in' => $input['check_in'],
                'check_out' => $input['check_out'],
                'adults' => $input['adults'],
                'children' => $input['children'],
                'lead_guest_id' => $input['organiser_guest_id'],
                'status' => $input['status'],
            ],
            'line' => [
                'room_type_id' => $member['room_type_id'],
                'unit_id' => $member['unit_id'],
                'rate_plan_id' => $member['rate_plan_id'],
                'check_in' => $input['check_in'],
                'check_out' => $input['check_out'],
                'adults' => $input['adults'],
                'children' => $input['children'],
                'price_minor' => $member['price_minor'],
                'currency' => $currency,
            ],
        ], $input['members']);

        $group = $this->assignments->createGroupBooking($groupAttributes, $members);

        $this->postFolios($group, $currency, $input['billing_mode'] === 'master');

        return $group;
    }

    /**
     * Post each member's room charge — one folio per member (individual), or all onto the first member's
     * folio (master). Re-queries the group's reservations as an Eloquent collection so the relations load.
     */
    private function postFolios(Group $group, string $currency, bool $master): void
    {
        $reservations = $group->reservations()->with('reservationUnits.roomType')->orderBy('created_at')->get();

        $masterFolio = $master && $reservations->isNotEmpty()
            ? $this->folios->openFolio($reservations->first())
            : null;

        foreach ($reservations as $reservation) {
            $folio = $masterFolio ?? $this->folios->openFolio($reservation);

            foreach ($reservation->reservationUnits as $line) {
                $roomName = ((string) $line->roomType->name) ?: 'Room';
                $label = $master
                    ? 'Room · '.$roomName.' · '.$reservation->reference   // one bill: name the room it's for
                    : 'Room · '.$roomName;
                $this->folios->postCharge($folio, 'room', $label, Money::of((int) $line->price_minor, $currency));
            }
        }
    }
}
