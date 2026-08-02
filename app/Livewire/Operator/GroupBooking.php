<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\GroupBookingController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Folio;
use App\Models\Group;
use App\Models\Payment;
use App\Services\AssignmentService;
use App\Services\UnitAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator GROUP BOOKING screen (Phase 1 · Sub-gate 1) — build a block of rooms (a wedding/corporate/family
 * block) over the block dates. READ-ONLY component (availability); the block is a guarded POST →
 * {@see GroupBookingController} → {@see GroupBookingService::create()} →
 * {@see AssignmentService::createGroupBooking()} (atomic, single write path).
 *
 * Invariant #20 (the scope-leak trap): the availability read is scope-dependent, so the DATES ride a GET (the
 * `?check_in/check_out` query, re-run through the tenant middleware) — never a Livewire wire-action that would
 * bypass `SetTenantForWebRequest`. The rest of the wizard (unit selection, organiser, terms, review) is
 * client-side Alpine over the units this render already scoped + loaded; the final create rides a POST form.
 * Same proven shape as {@see NewReservation}.
 *
 * TENANT-scoped (Property/RoomType/Unit are BelongsToOrganization); `/app` behind #26; the write route carries
 * `can:reservations.create` (#30). Only free + priced units are ever offered (GUARD 1 — honest availability),
 * and the controller re-derives every price server-side, so a client cannot inject a price or a taken unit.
 */
#[Layout('components.layouts.operator')]
class GroupBooking extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    public function render(UnitAvailabilityService $availability): View
    {
        $property = $this->currentProperty();

        $checkIn = $this->validDate(request()->query('check_in'));
        $checkOut = $this->validDate(request()->query('check_out'));
        $datesValid = $checkIn !== null && $checkOut !== null && $checkOut > $checkIn;

        $roomTypes = [];
        $nights = 0;
        $freeUnitCount = 0;
        $currency = null;

        if ($property !== null && $datesValid) {
            $nights = (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));
            foreach ($availability->availableRoomTypes((string) $property->getKey(), $checkIn, $checkOut) as $rt) {
                if ($rt['priced'] !== true || $rt['units'] === []) {
                    continue;   // only offer what createGroupBooking would accept AND price
                }
                $presented = $this->presentRoomType($rt);
                $roomTypes[] = $presented;
                $freeUnitCount += $presented['unit_count'];
                $currency ??= (string) $rt['currency'];
            }
        }

        return view('livewire.operator.group-booking', [
            'viewer' => $this->viewer(),
            'subtitle' => $property !== null ? (string) $property->name : 'No property configured',
            'hasProperty' => $property !== null,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'datesValid' => $datesValid,
            'nights' => $nights,
            'roomTypes' => $roomTypes,
            'freeUnitCount' => $freeUnitCount,
            'currency' => $currency ?? 'EUR',
            'occasions' => [
                ['value' => 'wedding', 'label' => 'Wedding'],
                ['value' => 'corporate', 'label' => 'Corporate'],
                ['value' => 'family', 'label' => 'Family'],
                ['value' => 'sports', 'label' => 'Sports'],
                ['value' => 'other', 'label' => 'Other'],
            ],
            'done' => $this->done(),
        ]);
    }

    /**
     * The post-create DONE payload (the wireframe's success card), or null during the normal wizard. Loads the
     * just-created group TENANT-scoped (the global org scope isolates — a foreign/bogus id returns null → the
     * wizard). Every figure is REAL (block total = Σ member folios; deposit = Σ recorded payments); NO guest
     * contact (#28 — organiser NAME only, from the non-PII contact json).
     *
     * @return array{reference: string, title: string, sub: string, organiser: string, units: int, dates: string, total: string, deposit: string|null, rack_url: string, new_url: string}|null
     */
    private function done(): ?array
    {
        $id = request()->query('created');
        if (! is_string($id) || $id === '') {
            return null;
        }

        $group = Group::query()->with('reservations')->find($id);
        if ($group === null || $group->reservations->isEmpty()) {
            return null;
        }

        $first = $group->reservations->first();
        $tentative = (string) $first->status === 'tentative';
        $folios = Folio::query()->whereIn('reservation_id', $group->reservations->pluck('id'))->get();
        $currency = ($c = (string) $first->currency) !== '' ? $c : 'EUR';   // the member reservation's currency is always set
        $totalMinor = (int) $folios->sum('total_minor');
        $depositMinor = (int) Payment::query()->whereIn('folio_id', $folios->pluck('id'))->where('type', 'payment')->sum('amount_minor');

        return [
            'reference' => (string) $group->block_code,
            'title' => $tentative ? 'Block held' : 'Block confirmed',
            'sub' => $tentative ? 'option on the rack' : 'it’s on the rack now',
            'organiser' => (string) (data_get($group->contact, 'name') ?: 'the organiser'),   // NAME only (#28)
            'units' => $group->reservations->count(),
            'dates' => CarbonImmutable::parse($first->check_in)->format('j M').' – '.CarbonImmutable::parse($first->check_out)->format('j M Y'),
            'total' => $this->money($totalMinor, $currency),
            'deposit' => $depositMinor > 0 ? $this->money($depositMinor, $currency) : null,
            'rack_url' => route('app.rack'),
            'new_url' => route('app.group-booking'),
        ];
    }

    /**
     * @param  array<string, mixed>  $rt
     * @return array{room_type_id: string, name: string, price: string, price_minor: int, currency: string, units: list<array{id: string, code: string, name: string, sleeps: int}>, unit_count: int}
     */
    private function presentRoomType(array $rt): array
    {
        return [
            'room_type_id' => (string) $rt['room_type_id'],
            'name' => (string) $rt['room_type_name'],
            'price' => $this->money((int) $rt['price_minor'], (string) $rt['currency']),
            'price_minor' => (int) $rt['price_minor'],   // integer minor units — the client sums these for the DISPLAY-only block preview; the folio charge is re-derived + posted server-side
            'currency' => (string) $rt['currency'],
            'units' => $rt['units'],
            'unit_count' => count($rt['units']),
        ];
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
