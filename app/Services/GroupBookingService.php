<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Operator\GroupBookingController;
use App\Models\Group;
use App\Support\Folio\PropertyCharges;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
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
     * @param  array{property_id: string, name: string, occasion: ?string, billing_mode: string, status: string, check_in: string, check_out: string, currency: string, adults: int, children: int, organiser_guest_id: string, pickup_deadline?: ?string, contact?: ?array<string, mixed>, discount_pct?: int, deposit_pct?: int, deposit_method?: ?string, members: list<array{room_type_id: string, unit_id: string, rate_plan_id: ?string, price_minor: int, lead_guest_id?: ?string}>}  $input
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

        // GATE 4.2 — the group discount % (from the fixed {0,5,10,15} set, re-derived by the controller — never a
        // client amount) and the deposit-to-confirm (% from {0,25,50,100} + method). Both are REAL folio movements.
        $discountPct = max(0, min(100, (int) ($input['discount_pct'] ?? 0)));
        $this->postFolios($group, $currency, $input['billing_mode'] === 'master', $discountPct);

        // The deposit is collected on a MASTER invoice only (a group deposit secures the ONE bill; an individual-pay
        // block has no single folio to hold it — the UI hides the control there). Cash/bank record NOW via the
        // certified FolioService; card is the DEFERRED Stripe seam (never faked, #41/#45) — the block still creates.
        $this->takeDeposit($group, $currency, $input['billing_mode'] === 'master',
            max(0, min(100, (int) ($input['deposit_pct'] ?? 0))), (string) ($input['deposit_method'] ?? ''));

        return $group;
    }

    /**
     * Post each member's room charge — one folio per member (individual), or all onto the first member's folio
     * (master) — plus, when a group discount is set, a REAL negative `discount` line that also LOWERS the taxable
     * base (so percentage taxes fall on room − discount, exactly the wireframe's VAT-on-afterDisc and the same
     * rule the single-reservation credit flow uses). The block total then reconciles to the discounted sum (#17).
     */
    private function postFolios(Group $group, string $currency, bool $master, int $discountPct): void
    {
        $reservations = $group->reservations()->with('reservationUnits.roomType')->orderBy('created_at')->get();

        $masterFolio = $master && $reservations->isNotEmpty()
            ? $this->folios->openFolio($reservations->first())
            : null;

        foreach ($reservations as $reservation) {
            $folio = $masterFolio ?? $this->folios->openFolio($reservation);

            $memberRoomMinor = 0;
            foreach ($reservation->reservationUnits as $line) {
                $roomName = ((string) $line->roomType->name) ?: 'Room';
                $label = $master
                    ? 'Room · '.$roomName.' · '.$reservation->reference   // one bill: name the room it's for
                    : 'Room · '.$roomName;
                $this->folios->postCharge($folio, 'room', $label, Money::of((int) $line->price_minor, $currency));
                $memberRoomMinor += (int) $line->price_minor;
            }

            if ($memberRoomMinor > 0) {
                // Group discount — a REAL negative line (folio_items 'discount' type). Per-member so it reconciles
                // whether the block is one master folio or per-member folios; the sum equals pct × block subtotal.
                $discountMinor = $discountPct > 0 ? intdiv($memberRoomMinor * $discountPct, 100) : 0;
                if ($discountMinor > 0) {
                    $this->folios->postCharge($folio, 'discount', 'Group discount ('.$discountPct.'%)', Money::of(-$discountMinor, $currency));
                }

                // #104 GATE 1 — this member's property taxes + non-optional fees, through the SAME shared
                // {@see PropertyCharges} the direct flow uses. The taxable base is room − discount (percentage
                // taxes fall on the discounted amount; per-night/per-person taxes ignore the base as before).
                $nights = max(1, (int) CarbonImmutable::parse($reservation->check_in)->diffInDays(CarbonImmutable::parse($reservation->check_out)));
                $persons = max(1, (int) $reservation->adults + (int) $reservation->children);
                $base = Money::of(max(0, $memberRoomMinor - $discountMinor), $currency);
                PropertyCharges::postTaxes($this->folios, $folio, (string) $reservation->property_id, $base, $nights, $persons, $currency);
                PropertyCharges::postFees($this->folios, $folio, (string) $reservation->property_id, $base, $nights, $persons, $currency);
            }
        }
    }

    /**
     * Take the deposit-to-confirm on the MASTER folio — a REAL payment (cash / bank transfer) for `pct` of the
     * finalised block total, capped at the real balance so it never over-collects (#17). Card is the deferred
     * Stripe seam (no payment recorded; the block still creates). No-op for individual billing / 0% / no folio.
     */
    private function takeDeposit(Group $group, string $currency, bool $master, int $depositPct, string $method): void
    {
        if (! $master || $depositPct <= 0 || ! in_array($method, ['cash', 'bank'], true)) {
            return;   // card / none / individual → nothing recorded (honest; never a faked payment)
        }

        $reservation = $group->reservations()->orderBy('created_at')->first();
        if ($reservation === null) {
            return;
        }

        $folio = $this->folios->openFolio($reservation);   // the same first-member folio the master charges landed on
        $folio->refresh();
        $depositMinor = min(max(0, (int) $folio->balance_minor), intdiv((int) $folio->total_minor * $depositPct, 100));
        if ($depositMinor > 0) {
            $this->folios->recordPayment(
                $folio,
                Money::of($depositMinor, $currency),
                metadata: ['method' => $method, 'kind' => 'group_deposit', 'deposit_pct' => $depositPct],
                idempotencyKey: 'group-deposit-'.$group->getKey(),
            );
        }
    }
}
