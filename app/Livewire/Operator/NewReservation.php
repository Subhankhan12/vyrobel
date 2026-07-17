<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\ReservationCreateController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Guest;
use App\Models\Property;
use App\Services\AssignmentService;
use App\Services\UnitAvailabilityService;
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

    private const MAX_QUERY = 100;

    /** Guest name-search results are bounded — a picker, not a directory dump. */
    private const GUEST_LIMIT = 8;

    public function render(UnitAvailabilityService $availability): View
    {
        $property = Property::query()->orderBy('name')->first();

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
        ]);
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
