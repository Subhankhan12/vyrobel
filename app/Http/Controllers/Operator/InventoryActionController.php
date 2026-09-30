<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\ArrivalInfoService;
use App\Services\AvailabilityService;
use App\Services\OwnerUnitStatusService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Unit\UnitListParams;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * OPERATOR INVENTORY CREATION — room types, units and rate plans (P-6 · Gate 1).
 *
 * ⚠️⚠️ WHY THIS EXISTS. The reachability sweep proved that `RoomType`, `RatePlan` and `Channel` had NO
 * creation path anywhere in `app/`: the demo seeder built them directly, so the demo looked complete while
 * a real `/signup` tenant reached a property they could never make sellable. `BookingEngineService::search()`
 * iterates `RoomType` rows, so with none the storefront offers nothing — and `Unit` was transitively blocked,
 * because the only `Unit::create` in the codebase sat behind an OWNER's add-unit request that resolves a room
 * type the tenant could not create. ⚠️ Worse, the application INSTRUCTED the impossible: signup and the
 * portfolio both told the tenant to "add units, rates & channels to go live". A signposted missing feature is
 * worse than an absent one, because the tenant concludes they are failing.
 *
 * ⚠️ THE PATTERN IS BORROWED, NOT INVENTED (§N.8f). This is {@see PropertyController} in every respect that
 * matters: the workspace read-only guard first, `$request->validate()`, the parent resolved from the ACTOR'S
 * OWN SCOPE rather than a request id, `Model::create()` with `organization_id` auto-filled by
 * `BelongsToOrganization`, and a PRG redirect carrying a flash. There is no second pattern for the same kind
 * of work.
 *
 * ⚠️ TENANCY. Every parent is resolved through a tenant-scoped `findOrFail` from a STRING id — never a
 * route-model-bound model (#20) — so another organisation's room-type id simply 404s. The operator-realm
 * analogue of #28: the id in the request never widens what the actor can reach.
 *
 * ⚠️ AUTHORIZATION IS ON THE ROUTE (#30), not here: `can:units.manage` / `can:rates.manage`, which run after
 * the tenant middleware has set the spatie team.
 *
 * ⚠️ P-7 IS NOT DEPENDED ON. Per-property staff scoping (`property_user`) is currently GATED-UNREACHABLE —
 * its only writer requires a row only it could create — so every back-office user resolves to "all
 * properties". These actions therefore scope by ORGANISATION, which works. ⚠️ WHERE P-7 WOULD APPLY ONCE
 * FIXED: {@see resolveProperty()} — the `PropertyContext::id()` branch already honours the selected property,
 * and the single-property fallback would need to consult the actor's accessible ids rather than the org's.
 * That is the one place a later P-7 fix has to touch; it is deliberately not touched here.
 */
class InventoryActionController extends Controller
{
    /** The room-type kinds offered. A subset of the `room_types.kind` enum, matching PropertyController's TYPES idiom. */
    private const KINDS = ['room', 'apartment', 'villa', 'dorm'];

    /** One sentence for one refusal: a unit code is unique within its property (the database's own rule). */
    private const DUPLICATE_CODE = 'That unit code is already used in this property — choose another.';

    /** How far forward availability is materialised when a unit is added — the same year OwnerUnitRequestService uses. */
    private const AVAILABILITY_HORIZON_DAYS = 365;

    public function storeRoomType(Request $request): RedirectResponse
    {
        if ($guard = $this->readOnly('units', $request)) {
            return $guard;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:'.implode(',', self::KINDS)],
            // ⚠️ Occupancy feeds search(): `max_adults >= adults` decides whether the room type is offered at
            // all, so a zero here would create inventory that can never match a real party.
            'max_adults' => ['required', 'integer', 'min:1', 'max:30'],
            'max_children' => ['required', 'integer', 'min:0', 'max:30'],
            // ⚠️ NOT ASKED FOR, AND NOT A CONSTANT EITHER. `std_occupancy` is RENDERED TO GUESTS on the
            // public room page ("Standard occupancy"), so a hidden default of 2 under a room type that sleeps
            // 4 would publish a figure nobody entered and nobody could see was wrong. It is derived from
            // `max_adults` below — one fewer field to fill, and it cannot disagree with what was entered.
            'std_occupancy' => ['nullable', 'integer', 'min:1', 'max:30'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:30'],
            'property_id' => ['nullable', 'string'],
        ]);

        $property = $this->resolveProperty($request);

        $roomType = RoomType::create([
            'property_id' => $property->getKey(),
            'name' => $data['name'],
            'code' => $this->uniqueCode(RoomType::query()->where('property_id', $property->getKey()), $data['name'], 'RT'),
            'kind' => $data['kind'],
            'std_occupancy' => (int) ($data['std_occupancy'] ?? $data['max_adults']),
            'max_adults' => (int) $data['max_adults'],
            'max_children' => (int) $data['max_children'],
            'bedrooms' => isset($data['bedrooms']) ? (int) $data['bedrooms'] : null,
            'is_virtual' => false,
        ]);

        return $this->listReturn($request, redirect()->to(route('app.units')))->with('units_notice',
            $roomType->name.' added. Next: add a unit to it, then a rate plan, and it can be sold.');
    }

    public function storeUnit(Request $request, AvailabilityService $availability): RedirectResponse
    {
        if ($guard = $this->readOnly('units', $request)) {
            return $guard;
        }

        $data = $request->validate([
            'room_type_id' => ['required', 'string'],
            'code' => ['required', 'string', 'max:60'],
            'name' => ['nullable', 'string', 'max:255'],
            'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        // ⚠️ TENANT-SCOPED, FROM A STRING ID. The global organization scope makes another org's id a 404,
        // and the property is read off the ROOM TYPE rather than the request — so a unit can never be
        // attached to a property the room type does not belong to.
        $roomType = RoomType::query()->findOrFail($data['room_type_id']);

        // ⚠️ 2026-09-30 — THE DATABASE WAS THE ONLY CHECK. `units` is unique on (property, code), and this action had no
        // rule for it, so a code already used in the property reached the unique index and came back as a 500. Refused
        // here with a sentence instead, scoped to the ROOM TYPE's property (the unit is created there).
        $request->validate(
            ['code' => [Rule::unique('units', 'code')->where('property_id', $roomType->property_id)]],
            ['code.unique' => self::DUPLICATE_CODE],
        );

        // ⚠️ ONE TRANSACTION, AND THE REASON IS SPECIFIC. The unit is written and then availability is
        // recomputed; if the recompute threw in between, the tenant would own a unit that the storefront
        // can never offer — inventory that exists and is silently unsellable, which is the exact shape of
        // the defect this gate exists to remove. Mutation found the ordering: with the room-type lookup
        // broken, `Unit::create` had already run before `recompute()` raised, leaving a cross-org row
        // behind a 404 response.
        $unit = DB::transaction(function () use ($data, $roomType, $availability): Unit {
            $unit = Unit::create([
                'property_id' => $roomType->property_id,
                'room_type_id' => $roomType->getKey(),
                'code' => $data['code'],
                'name' => $data['name'] ?? $data['code'],
                'unit_kind' => (string) $roomType->kind,
                'max_occupancy' => isset($data['max_occupancy'])
                    ? (int) $data['max_occupancy']
                    : (int) ($roomType->max_adults + $roomType->max_children),
                'housekeeping_status' => 'clean',
                'condition_status' => 'in_service',
                'is_active' => true,
            ]);

            // ⚠️⚠️ WITHOUT THIS THE ROOM TYPE STAYS UNSELLABLE, AND SILENTLY SO. `search()` calls
            // `minAvailable()`, which reads `availability` rows and treats a MISSING row as zero — so a unit
            // with no materialised availability is inventory the storefront will never offer. This is the
            // same recompute `OwnerUnitRequestService::approve()` performs after creating a unit; reused,
            // not reinvented. Derived from the code, not assumed: the fourth link in the sellable chain.
            $availability->recompute(
                (string) $roomType->getKey(),
                CarbonImmutable::today()->toDateString(),
                CarbonImmutable::today()->addDays(self::AVAILABILITY_HORIZON_DAYS)->toDateString(),
            );

            return $unit;
        });

        return $this->listReturn($request, redirect()->to(route('app.units')))->with('units_notice',
            $unit->code.' added to '.$roomType->name.'. It is bookable for the next year once a rate plan has prices.');
    }

    public function storeRatePlan(Request $request): RedirectResponse
    {
        if ($guard = $this->readOnly('rates')) {
            return $guard;
        }

        $data = $request->validate([
            'room_type_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'is_refundable' => ['nullable', 'boolean'],
        ]);

        $roomType = RoomType::query()->findOrFail($data['room_type_id']);
        $property = Property::query()->findOrFail((string) $roomType->property_id);

        // ⚠️ MONEY. The currency is taken from the PROPERTY, never from the request — a rate plan in a
        // different currency from its property would price a stay in the wrong money, and `priceOver()` sums
        // `rate_calendar.price_minor` without re-checking. ⚠️ `is_active` must be true or
        // `cheapestRatePlan()` skips the plan and the room type is silently unsellable. No price is set
        // here: prices are `rate_calendar` rows, written through the existing certified
        // RateAndRestrictionService path.
        $ratePlan = RatePlan::create([
            'property_id' => $property->getKey(),
            'room_type_id' => $roomType->getKey(),
            'name' => $data['name'],
            'code' => $this->uniqueCode(RatePlan::query()->where('room_type_id', $roomType->getKey()), $data['name'], 'RP'),
            'currency' => (string) $property->currency,
            'is_refundable' => (bool) ($data['is_refundable'] ?? true),
            'is_active' => true,
        ]);

        return redirect()->to(route('app.rates'))->with('rates_notice',
            $ratePlan->name.' created for '.$roomType->name.'. Set its nightly rates below to start selling.');
    }

    /**
     * The property these actions create against.
     *
     * ⚠️ RESOLVED FROM THE ACTOR'S OWN SCOPE, in this order: the explicitly selected property context, then
     * an explicit tenant-scoped id, then — when the organisation has exactly one property — that one. A
     * signup tenant is always in the last case. ⚠️ If the org has several and none is selected, the request
     * is refused rather than guessed at: silently creating inventory on an arbitrary property is the
     * `->first()` defect Phase 6a existed to remove.
     */
    /**
     * EDIT A UNIT (F-6) — the operator realm could create a unit and never change it: no update route, no
     * delete route, no Livewire action, while `units` carries both `is_active` and `deleted_at`.
     *
     * ⚠️ WHICH FIELDS, AND WHY ONLY THESE. `code`, `name` and `max_occupancy` are editable because none of
     * them can reach a stay that has already been sold: 🔬 `reservation_units` snapshots `price_minor` at
     * booking, and a reservation references the unit by ID, so renaming or recoding it cannot reprice or
     * re-point anything. `max_occupancy` does not enter the availability count either — that counts UNITS.
     *
     * ⚠️ WHAT IS DELIBERATELY NOT EDITABLE. `room_type_id` is refused by omission: moving a unit between
     * room types would move its capacity between availability pools and change what it was sold as. That is
     * a different operation with a different blast radius, and offering it here would be the "control that
     * implies more than it does" shape this product keeps removing. `owner_id` likewise reaches the owner
     * ledger's attribution and is not this screen's to change.
     *
     * ⚠️ `code` IS UNIQUE PER PROPERTY at the database level, so the rule is scoped to this unit's property
     * and ignores itself — without that, saving a unit unchanged would fail its own uniqueness check.
     */
    public function updateUnit(Request $request, AuditEventRecorder $audit, string $unit): RedirectResponse
    {
        if ($guard = $this->readOnly('units', $request)) {
            return $guard;
        }

        $model = $this->accessibleUnit($unit);   // property-scoped → another property's id 404s

        $data = $request->validate([
            'code' => ['required', 'string', 'max:60',
                Rule::unique('units', 'code')->where('property_id', $model->property_id)->ignore($model->getKey())],
            'name' => ['nullable', 'string', 'max:255'],
            'max_occupancy' => ['nullable', 'integer', 'min:1', 'max:30'],
        ], ['code.unique' => self::DUPLICATE_CODE]);

        // ⚠️ CAPTURED BEFORE THE WRITE. A unit's previous name and code are cheap to record, unlike a rate's
        // previous price — Phase 11 found 11 of 11 rate sites passing a null `before`, and that is why an
        // undo could not be built there. There is no excuse for a null here.
        $before = [
            'code' => (string) $model->code,
            'name' => $model->name,
            'max_occupancy' => (int) $model->max_occupancy,
        ];

        $model->update([
            'code' => $data['code'],
            'name' => $data['name'] ?? null,
            'max_occupancy' => $data['max_occupancy'] ?? $model->max_occupancy,
        ]);

        $after = [
            'code' => (string) $model->code,
            'name' => $model->name,
            'max_occupancy' => (int) $model->max_occupancy,
        ];

        // ⚠️ PII-SAFE: identifiers and states only. Operators can read audit rows (four surfaces render them),
        // so this carries no guest or contact data — a unit's code and occupancy are neither.
        $audit->record('unit.updated', $model, $before, $after);

        return $this->listReturn($request, redirect()->to(route('app.units')))
            ->with('units_notice', "Unit {$model->code} updated.");
    }

    /**
     * TAKE A UNIT OUT OF SERVICE, OR PUT IT BACK (F-6).
     *
     * ⚠️ THIS REUSES THE MECHANISM THAT ALREADY EXISTED rather than writing a second one. The owner realm has
     * paused and resumed units since Wave C (#69) through {@see OwnerUnitStatusService}, which sets
     * `is_active`, RECOMPUTES the room type's availability through the certified AvailabilityService, and
     * audits the change. The operator simply had no entrance to it. A second `is_active` writer would be the
     * drift this codebase keeps removing (§N.8f) — and it would have to re-derive the recompute window.
     *
     * ⚠️ DEACTIVATE IS NOT DELETE, AND THE SCREEN SAYS SO. Capacity is removed so NEW bookings are refused;
     * existing reservations are untouched (append-only #7). Nothing is destroyed and it reverses exactly.
     *
     * ⚠️ A UNIT WITH FUTURE RESERVATIONS IS NOT REFUSED, which follows the answer the product already gave
     * for the owner's pause rather than inventing a second one. The confirmation states it plainly instead.
     * Whether an operator should be BLOCKED in that case is a founder ruling, not this pass's to take.
     */
    public function setUnitActive(Request $request, OwnerUnitStatusService $status, string $unit): RedirectResponse
    {
        if ($guard = $this->readOnly('units', $request)) {
            return $guard;
        }

        $model = $this->accessibleUnit($unit);
        $active = $request->boolean('active');
        $actorId = $request->user()?->getAuthIdentifier();

        if ($active) {
            $status->reactivateByOperator($model, $actorId !== null ? (string) $actorId : null);
        } else {
            $status->deactivateByOperator($model, $actorId !== null ? (string) $actorId : null);
        }

        return $this->listReturn($request, redirect()->to(route('app.units')))->with('units_notice', $active
            ? "Unit {$model->code} is taking bookings again."
            : "Unit {$model->code} is paused. Bookings already taken are kept; no new ones can be made.");
    }

    /**
     * PAUSE OR RESUME SEVERAL UNITS AT ONCE (2026-09-30, the Units page's select mode) — through the SAME
     * {@see OwnerUnitStatusService} the single action uses ({@see OwnerUnitStatusService::setManyByOperator()}): every unit
     * gets its own audit row, each affected room type its availability recompute ONCE, all in one transaction; nothing here
     * writes `is_active` itself. Gated `can:units.manage` on the route (#30), refused in a paused workspace, and every id
     * is resolved inside the actor's accessible properties (a unit elsewhere is never touched — not found, not merely
     * hidden). A unit already in the asked-for state is counted and left alone, and the notice says so rather than
     * claiming it.
     */
    public function bulkSetActive(Request $request, OwnerUnitStatusService $status): RedirectResponse
    {
        if ($guard = $this->readOnly('units', $request)) {
            return $guard;
        }

        $data = $request->validate([
            'unit_ids' => ['required', 'array', 'min:1', 'max:500'],
            'unit_ids.*' => ['string'],
            'active' => ['required', 'boolean'],
        ]);

        $active = $request->boolean('active');
        $actorId = $request->user()?->getAuthIdentifier();
        $readIds = app(PropertyContext::class)->readIds();
        $units = Unit::query()
            ->when($readIds !== [], fn ($q) => $q->whereIn('property_id', $readIds))
            ->whereIn('id', $data['unit_ids'])
            ->get();

        ['changed' => $done, 'already' => $already] = $status->setManyByOperator($units, $active, $actorId !== null ? (string) $actorId : null);

        $verb = $active ? 'Resumed' : 'Paused';
        $notice = $verb.' '.$done.' '.Str::plural('unit', $done);
        if ($already > 0) {
            $notice .= ' · '.$already.' already '.($active ? 'taking bookings' : 'paused');
        }

        return $this->listReturn($request, redirect()->to(route('app.units')))
            ->with('units_notice', $notice.($active ? '.' : '. Bookings already taken are kept.'));
    }

    /**
     * EDIT A RATE PLAN — F-6's sibling, recorded in the same phase and confirmed while FIX 10 closed units.
     *
     * ⚠️⚠️ A UNIT IS A ROOM; A RATE PLAN IS A PRICE. 🔬 929 `rate_calendar` rows are keyed by plan, the
     * storefront quotes a stranger from them, and seasons apply through them. So the editable set here is
     * NARROWER than a unit's, and everything excluded is excluded for a measured reason:
     *
     *   · `currency` — REFUSED. {@see storeRatePlan()} already takes it from the PROPERTY and never the
     *     request, because `priceOver()` sums `rate_calendar.price_minor` WITHOUT re-checking currency.
     *     Editing it would silently reinterpret every existing minor-unit amount on this plan.
     *   · `room_type_id` — REFUSED. It rebinds the plan to different inventory while its rate rows stay,
     *     so nights priced for one room type would be quoted for another.
     *   · `derived_from_rate_plan_id` / `adjustment` — REFUSED. These are a derived plan's pricing BASIS;
     *     changing them reprices every night the plan quotes.
     *   · `is_refundable` / `cancellation_policy` — REFUSED. A stay was SOLD under these terms. Changing
     *     them would retroactively alter what a guest agreed to, which is not an edit, it is a rewrite.
     *
     * ⚠️ SO ONLY `name` AND `code` MOVE — the two fields that identify the plan to staff and change nothing
     * about what it charges. 🔬 A sold stay is unreachable either way: `reservation_units` snapshots
     * `price_minor` at booking and references the plan by ID.
     */
    public function updateRatePlan(Request $request, AuditEventRecorder $audit, string $ratePlan): RedirectResponse
    {
        if ($guard = $this->readOnly('rates')) {
            return $guard;
        }

        $model = RatePlan::query()->findOrFail($ratePlan);   // tenant-scoped → cross-org 404

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:60',
                Rule::unique('rate_plans', 'code')->where('room_type_id', $model->room_type_id)->ignore($model->getKey())],
        ]);

        $before = ['name' => (string) $model->name, 'code' => $model->code];

        $model->update(['name' => $data['name'], 'code' => $data['code'] ?? $model->code]);

        // PII-safe: a plan's name and code are neither guest nor contact data.
        $audit->record('rate_plan.updated', $model, $before, ['name' => (string) $model->name, 'code' => $model->code]);

        return redirect()->to(route('app.rates'))->with('rates_notice', "Rate plan “{$model->name}” updated. No prices changed.");
    }

    /**
     * TAKE A RATE PLAN OFF SALE, OR PUT IT BACK.
     *
     * ⚠️ THE COLUMN ALREADY EXISTED AND WAS ALREADY READ — 🔬 `is_active` is filtered by the storefront's
     * offer loop, the booking engine, the availability read and the rates grid — but NOTHING EVER WROTE IT
     * outside creation. So this is a latent capability with no entrance, not a new concept.
     *
     * ⚠️⚠️ AND IT IS REFUSED FOR THE LAST ACTIVE PLAN, which is the one case that costs money silently.
     * {@see storeRatePlan()} records why: with no active plan `cheapestRatePlan()` finds nothing and "the
     * room type is silently unsellable". An operator taking the last plan off sale would remove a room type
     * from sale without being told. ⚠️ Refusing is the loud error; permitting is the silent one, and this
     * project's rule is that the fail-safe direction is set by which error is silent.
     *
     * ⚠️ NO PRICES ARE TOUCHED. `rate_calendar` is not written here; the plan simply stops being offered.
     */
    public function setRatePlanActive(Request $request, AuditEventRecorder $audit, string $ratePlan): RedirectResponse
    {
        if ($guard = $this->readOnly('rates')) {
            return $guard;
        }

        $model = RatePlan::query()->findOrFail($ratePlan);
        $active = $request->boolean('active');

        if (! $active) {
            $otherActive = RatePlan::query()
                ->where('room_type_id', $model->room_type_id)
                ->where('is_active', true)
                ->whereKeyNot($model->getKey())
                ->count();

            if ($otherActive === 0) {
                return redirect()->to(route('app.rates'))->with('rates_error',
                    "“{$model->name}” is the only rate plan still on sale for this room type. Taking it off sale would leave nothing to quote, so the room type could not be booked at all. Add or reactivate another plan first.");
            }
        }

        if ((bool) $model->is_active === $active) {
            return redirect()->to(route('app.rates'))->with('rates_notice', "Rate plan “{$model->name}” was already ".($active ? 'on sale' : 'off sale').'.');
        }

        $model->update(['is_active' => $active]);

        $audit->record($active ? 'rate_plan.activated' : 'rate_plan.deactivated', $model,
            ['is_active' => ! $active], ['is_active' => $active]);

        return redirect()->to(route('app.rates'))->with('rates_notice', $active
            ? "Rate plan “{$model->name}” is back on sale."
            : "Rate plan “{$model->name}” is off sale. Existing bookings keep the price they were sold at; no nightly prices were changed.");
    }

    private function resolveProperty(Request $request): Property
    {
        $selected = app(PropertyContext::class)->id();

        if (is_string($selected) && $selected !== '') {
            return Property::query()->findOrFail($selected);
        }

        $requested = $request->input('property_id');

        if (is_string($requested) && $requested !== '') {
            return Property::query()->findOrFail($requested);
        }

        $properties = Property::query()->limit(2)->get();

        abort_unless($properties->count() === 1, 422,
            'Choose which property this belongs to — your account has more than one.');

        return $properties->first();
    }

    /**
     * CLUSTER E — write a unit's ARRIVAL INFO (door code · wifi · notes), the twelfth seeder-only entity.
     *
     * ⚠️ Until this existed, every reference to these columns in the application was a READ: the guest
     * arrival screen, the pre-arrival email and the arrival PDF all displayed data only the demo seeder
     * could ever have written. Invariant #33 was gating the release of something no operator could enter.
     *
     * ⚠️ THE UNIT IS RESOLVED INSIDE THE ACTOR'S ACCESSIBLE PROPERTIES, not merely their organisation —
     * which is exactly what cluster J made meaningful. Before J nobody had a `property_user` row, so
     * "accessible" was always the whole org and this distinction would have been decorative; now a staff
     * member scoped to one property cannot set the door codes of another. A unit outside that set is not
     * found — never merely hidden.
     *
     * ⚠️ Gated `units.manage` on the route (#30). The audit lives in the SERVICE, as every other event does.
     */
    public function updateArrivalInfo(Request $request, string $unit, ArrivalInfoService $arrival): RedirectResponse
    {
        $target = $this->accessibleUnit($unit);

        $validated = $request->validate([
            'door_code' => ['nullable', 'string', 'max:64'],
            'wifi_ssid' => ['nullable', 'string', 'max:64'],
            'wifi_password' => ['nullable', 'string', 'max:128'],
            'arrival_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $changed = $arrival->update($target, $validated);

        return $this->listReturn($request, redirect()->to(route('app.units')))->with('units_notice', $changed === []
            ? 'Nothing changed — a blank field leaves the stored value alone.'
            : 'Arrival info updated for '.($target->name ?? $target->code).'.');
    }

    /**
     * CLUSTER E — reveal one unit's stored arrival secrets, on purpose and on the record.
     *
     * ⚠️ WHY A SEPARATE ACTION. The editor deliberately never renders a stored door code back into the
     * page: doing so would place a credential to a physical space into every response, browser cache and
     * screenshot of the units screen, for everyone holding `units.manage`. Reading one is instead an
     * explicit act that writes an audit row — the property that matters if a guest is later let in wrongly.
     */
    public function revealArrivalInfo(Request $request, string $unit, ArrivalInfoService $arrival): RedirectResponse
    {
        $target = $this->accessibleUnit($unit);

        return $this->listReturn($request, redirect()->to(route('app.units')))
            ->with('arrival_reveal', ['unit' => (string) $target->getKey()] + $arrival->reveal($target));
    }

    /**
     * A unit the CURRENT ACTOR may work in — tenant-scoped by the model, then narrowed to the properties
     * their `property_user` scope allows. ⚠️ Resolved from the actor's own context, never from the posted
     * id alone (the operator-realm analogue of #28).
     */
    private function accessibleUnit(string $unitId): Unit
    {
        $ids = app(PropertyContext::class)->readIds();

        return Unit::query()
            ->when($ids !== [], fn ($q) => $q->whereIn('property_id', $ids))
            ->findOrFail($unitId);
    }

    /**
     * Back to the Units page as the operator had it, when the action was started there (`return=list`) — the address is
     * REBUILT from the page's own allow-listed settings ({@see UnitListParams::returnUrl()}), never taken from the request
     * as a URL. Anything else keeps its old destination.
     */
    private function listReturn(Request $request, RedirectResponse $default): RedirectResponse
    {
        return $request->input('return') === 'list'
            ? redirect()->to(UnitListParams::returnUrl($request->input('return_query')))
            : $default;
    }

    /** A stable, human-ish code that is unique within its parent. Mirrors PropertyController::uniqueSlug. */
    private function uniqueCode(Builder $scope, string $from, string $prefix): string
    {
        $base = Str::upper(Str::substr(Str::slug($from, ''), 0, 8)) ?: $prefix;
        $code = $base;
        $n = 2;

        while ((clone $scope)->where('code', $code)->exists()) {
            $code = Str::substr($base, 0, 6).$n;
            $n++;
        }

        return $code;
    }

    /** The workspace pause guard, in PropertyController's shape. */
    private function readOnly(string $surface, ?Request $request = null): ?RedirectResponse
    {
        $workspace = app(OperatorWorkspace::class);

        if (! $workspace->isReadOnly()) {
            return null;
        }

        $route = $surface === 'rates' ? 'app.rates' : 'app.units';
        $back = $request !== null && $surface === 'units'
            ? $this->listReturn($request, redirect()->route($route))
            : redirect()->route($route);

        return $back->with($surface.'_error',
            'Workspace paused — adding inventory is read-only until '.$workspace->resumeLabel().'.');
    }
}
