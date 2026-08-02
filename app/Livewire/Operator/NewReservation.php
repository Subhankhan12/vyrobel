<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\ReservationCreateController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\AddOn;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Tax;
use App\Services\AssignmentService;
use App\Services\UnitAvailabilityService;
use App\Support\QrSvg;
use App\Support\Reservations\ReservationExtras;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator NEW RESERVATION screen (Wave 4 · Sub-gate 2) — create one direct booking, on the shared operator
 * design system via <x-operator.shell>. This component READS ONLY (availability + a guest name-search);
 * the booking itself is a guarded POST → {@see ReservationCreateController}
 * → {@see AssignmentService::createBooking()} (invariant #20 — scope-changing reads ride GET
 * links, the write rides a POST form, never a wire-action that bypasses the tenant middleware).
 *
 * The three guards (docs/WAVE4-INVENTORY.md — the design is "SAFE as drawn: ❌"):
 *   • GUARD 1 — HONEST AVAILABILITY. The unit list comes from {@see UnitAvailabilityService}, which offers a
 *     unit ONLY when createBooking would accept it (room-type Level A + physical-unit Level B) AND the room
 *     type is priced. An occupied/blocked unit is never shown; an unpriced room type is shown disabled with
 *     "set a rate first". The controller re-derives this server-side, and createBooking's row lock is the
 *     actual guarantee — this screen just never offers the unbookable.
 *   • GUARD 2 — PII. The existing-guest search shows the guest NAME only. `guests.email`/`phone` are
 *     encrypted casts and are neither rendered nor queried here (encrypted text is not indexed by value — a
 *     LIKE would match nothing; the standing blind-index deferral). A NEW guest's contact details are
 *     collected into those encrypted columns by the controller, never echoed back.
 *   • GUARD 3 — NO RAW PAN (PCI). There is NO card-number field. Payment is an optional MANUAL folio payment
 *     (cash / bank), and only for a user who holds `payments.record`; card capture is an honest "Soon".
 *
 * TENANT-scoped throughout (Property/RoomType/Unit/Guest are BelongsToOrganization); `/app` is behind
 * EnsureBackOfficeAccess (#26) and the route carries `can:reservations.create` (#30). Proven by
 * OperatorNewReservationTest.
 */
#[Layout('components.layouts.operator')]
class NewReservation extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    private const MAX_QUERY = 100;

    /** Guest name-search results are bounded — a picker, not a directory dump. */
    private const GUEST_LIMIT = 8;

    public function render(UnitAvailabilityService $availability): View
    {
        $property = $this->currentProperty();

        $checkIn = $this->validDate(request()->query('check_in'));
        $checkOut = $this->validDate(request()->query('check_out'));
        $datesValid = $checkIn !== null && $checkOut !== null && $checkOut > $checkIn;

        $roomTypes = [];
        $nights = 0;
        if ($property !== null && $datesValid) {
            $nights = (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));
            $roomTypes = array_map(
                fn (array $rt): array => $this->presentRoomType($rt),
                $availability->availableRoomTypes((string) $property->getKey(), $checkIn, $checkOut),
            );
        }

        $query = $this->resolveQuery();

        return view('livewire.operator.new-reservation', [
            'viewer' => $this->viewer(),
            'subtitle' => $property !== null ? (string) $property->name : 'No property configured',
            'hasProperty' => $property !== null,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'datesValid' => $datesValid,
            'nights' => $nights,
            'roomTypes' => $roomTypes,
            'anyBookable' => collect($roomTypes)->contains(fn (array $rt): bool => $rt['priced'] && $rt['units'] !== []),
            'query' => $query,
            'guestMatches' => $query !== '' ? $this->searchGuests($query) : [],
            'canPay' => (bool) auth()->user()?->can('payments.record'),
            'sources' => [
                ['value' => 'direct', 'label' => 'Direct'],
                ['value' => 'walk_in', 'label' => 'Walk-in'],
                ['value' => 'corporate', 'label' => 'Corporate'],
                ['value' => 'agent', 'label' => 'Travel agent'],
            ],
            // #90 — REAL extras (org-owned AddOns), the property's REAL taxes, and REAL house-rules config.
            'extras' => $property !== null ? $this->extrasFor((string) $property->getKey()) : [],
            'taxes' => $property !== null ? $this->taxesFor((string) $property->getKey()) : [],
            'houseRules' => $property !== null ? $this->houseRules($property) : [],
            // GATE 1 · Part B — the in-wizard DONE screen after a successful create (PRG → ?created=<id>).
            'done' => $this->done(),
        ]);
    }

    /**
     * The post-create DONE payload (the wireframe's success card), or null during the normal wizard. Loads the
     * just-created reservation TENANT-scoped (the global org scope guarantees isolation — a foreign/bogus id
     * returns null → the normal wizard). The self-check-in QR encodes the REAL, guard-protected guest-portal
     * check-in URL: a convenience deep-link (the guest still authenticates), never a fabricated code or a
     * check-in that bypasses the guest guard.
     *
     * @return array{ref: string, guest: string, qr: string, check_in_time: string, show_url: string, create_url: string}|null
     */
    private function done(): ?array
    {
        $id = request()->query('created');
        if (! is_string($id) || $id === '') {
            return null;
        }

        $reservation = Reservation::query()->with(['property', 'leadGuest'])->find($id);
        if ($reservation === null) {
            return null;
        }

        $property = $reservation->property;
        $checkInTime = $property?->check_in_time !== null ? substr((string) $property->check_in_time, 0, 5) : '15:00';
        $guest = $reservation->leadGuest;
        $guestName = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';

        return [
            'ref' => (string) $reservation->reference,
            'guest' => $guestName !== '' ? $guestName : 'the guest',   // NAME only (#28)
            'qr' => QrSvg::of(route('guest.checkin', $reservation->getKey()), 190),
            'check_in_time' => $checkInTime,
            'show_url' => route('app.reservations.show', $reservation->getKey()),
            'create_url' => route('app.reservations.create'),
        ];
    }

    /**
     * Present one available room type for the view — a real formatted price + its free units.
     *
     * @param  array<string, mixed>  $rt
     * @return array<string, mixed>
     */
    private function presentRoomType(array $rt): array
    {
        return [
            'room_type_id' => $rt['room_type_id'],
            'name' => $rt['room_type_name'],
            'priced' => $rt['priced'],
            'price' => $rt['priced'] ? $this->money((int) $rt['price_minor'], (string) $rt['currency']) : null,
            'price_minor' => $rt['priced'] ? (int) $rt['price_minor'] : 0,
            'rate_plan_id' => (string) ($rt['rate_plan_id'] ?? ''),
            // REAL active + priced rate plans for the selector (name + server-derived price). Never fabricated.
            'plans' => array_map(fn (array $p): array => [
                'id' => (string) $p['id'],
                'name' => (string) $p['name'],
                'price' => $this->money((int) $p['price_minor'], (string) $p['currency']),
                'price_minor' => (int) $p['price_minor'],
            ], $rt['plans'] ?? []),
            'units' => $rt['units'],
            'unit_count' => count($rt['units']),
        ];
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }

    /**
     * The property's REAL active add-on extras (#90). The wizard renders these as toggles; a per-night / per-guest
     * / per-leg BASIS drives the client estimate, and the controller posts the real reconciling line.
     *
     * @return list<array{id: string, name: string, price_minor: int, currency: string, taxable: bool, basis: string, basis_label: string, is_transfer: bool}>
     */
    private function extrasFor(string $propertyId): array
    {
        return AddOn::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (AddOn $a): array => [
                'id' => (string) $a->getKey(),
                'name' => (string) $a->name,
                'price_minor' => (int) $a->price_minor,
                'currency' => (string) $a->currency,
                'taxable' => (bool) $a->taxable,
                'basis' => ReservationExtras::basisFor($a),
                'basis_label' => ReservationExtras::basisLabel($a),
                'is_transfer' => ReservationExtras::basisFor($a) === ReservationExtras::BASIS_PER_LEG,
            ])
            ->all();
    }

    /**
     * The property's REAL taxes (#90) — the percentage rate / per-night amount the client estimate mirrors and
     * the controller posts. No fabricated VAT: what the property has configured, nothing more.
     *
     * @return list<array{name: string, mode: string, rate: float, amount_minor: int}>
     */
    private function taxesFor(string $propertyId): array
    {
        return Tax::query()
            ->where('property_id', $propertyId)
            ->get()
            ->map(fn (Tax $t): array => [
                'name' => (string) $t->name,
                'mode' => (string) $t->mode,
                'rate' => (float) $t->rate,
                'amount_minor' => (int) $t->amount_minor,
            ])
            ->all();
    }

    /**
     * The property's REAL house rules (#90) — check-in/out times + the first policy's cancellation / pet /
     * smoking / max-stay. Honest defaults where the property hasn't configured a value (never invented).
     *
     * @return array<string, string>
     */
    private function houseRules(Property $property): array
    {
        $policy = $property->policies()->first();
        $cancellation = data_get($policy, 'cancellation');
        $cancellationLabel = is_array($cancellation)
            ? (string) ($cancellation['label'] ?? $cancellation['policy'] ?? 'As per booking terms')
            : 'As per booking terms';
        $pet = data_get($policy, 'pet');
        $smoking = data_get($policy, 'smoking');
        $minStay = data_get($policy, 'min_stay');

        return [
            'check_in' => $property->check_in_time !== null ? substr((string) $property->check_in_time, 0, 5) : '15:00',
            'check_out' => $property->check_out_time !== null ? substr((string) $property->check_out_time, 0, 5) : '11:00',
            'pet' => is_string($pet) && $pet !== '' ? $pet : 'Not permitted',
            'smoking' => is_string($smoking) && $smoking !== '' ? $smoking : 'Not permitted',
            'cancellation' => $cancellationLabel,
            'min_stay' => is_numeric($minStay) ? (int) $minStay.' night min' : '—',
        ];
    }

    /**
     * Existing guests matching the typed name — NAME ONLY (GUARD 2). Searches the two plaintext name columns
     * via CONCAT_WS (so "Ada", "Lovelace" and "Ada Lovelace" all match); email/phone are encrypted and are
     * neither searched nor returned. Anonymized guests (names nulled) simply never match.
     *
     * @return list<array{id: string, name: string, initials: string}>
     */
    private function searchGuests(string $query): array
    {
        $term = '%'.addcslashes($query, '%_\\').'%';

        return Guest::query()
            ->where(fn (Builder $q) => $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term]))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::GUEST_LIMIT)
            ->get()
            ->map(function (Guest $guest): array {
                $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));
                $name = $name !== '' ? $name : 'Guest';

                return ['id' => (string) $guest->getKey(), 'name' => $name, 'initials' => $this->initials($name)];
            })
            ->all();
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }
}
