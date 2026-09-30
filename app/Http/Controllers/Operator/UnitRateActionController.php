<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\PreviewChangedException;
use App\Services\UnitRateOverrideService;
use App\Support\Money\MoneyInput;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Rates\RateScope;
use App\Support\Rates\RatesListParams;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * D18-5 — BULK PER-UNIT RATE OVERRIDES: set or clear a unit-level price across many units at once.
 *
 * ⚠️⚠️ TWO POSTS, DELIBERATELY, BECAUSE THIS IS MONEY AT SCALE. `preview` writes NOTHING and hands back the
 * exact per-unit change set plus a digest; `apply` will not act without that digest and re-verifies it inside
 * the transaction. A bulk mistake is not one wrong price — it is many wrong prices, applied silently, and the
 * operator is the only one who can catch it before it lands. So they are shown it first, in figures.
 *
 * ⚠️ THE WRITE GOES THROUGH {@see UnitRateOverrideService} AND NOWHERE ELSE. That service is the ONE writer
 * for `unit_rate_overrides` (see its class note — confirm-first established that no single-set writer exists
 * yet, so this had to BE the path rather than wrap one, or the later single-set surface would have become a
 * second, disagreeing source of price — D4-1).
 *
 * ⚠️ MONEY PARSING IS THE TYPE-LEVEL PATH'S, UNCHANGED. {@see MoneyInput::toMinor()} with the same
 * MAX_PRICE_MINOR bound {@see RateActionController} uses — integer minor units by string arithmetic, never a
 * float multiply, VAT-inclusive on the flat view (§G.1). Nothing here re-prices anything.
 *
 * TENANT + PERMISSION. Both routes carry `can:rates.manage` (#30 — deliberately not Front Desk /
 * Reservation Agent) and sit inside `/app` behind EnsureBackOfficeAccess (#26). Every room type, plan and
 * unit is resolved through the `BelongsToOrganization` global scope after the tenant middleware, so a
 * cross-org id 404s rather than leaking — and the unit set is additionally narrowed to the chosen room
 * type, so a unit of another type can never be priced onto this plan.
 */
class UnitRateActionController extends Controller
{
    /** The same bound the type-level rate write uses — €1,000,000.00. */
    private const MAX_PRICE_MINOR = 100_000_000;

    /** Show what would change. ⚠️ Writes nothing. */
    public function preview(Request $request, UnitRateOverrideService $overrides): RedirectResponse
    {
        return $this->resolve($request, function (array $ctx) use ($request, $overrides): RedirectResponse {
            $preview = $overrides->preview(
                $ctx['plan'], $ctx['unit_ids'], $ctx['start'], $ctx['end_exclusive'], $ctx['mode'], $ctx['price_minor'],
            );

            if ($preview['units'] === []) {
                return $this->back($ctx['from'], $ctx['mode'] === UnitRateOverrideService::MODE_CLEAR
                    ? 'None of those units have their own price in that range — there is nothing to clear.'
                    : 'Choose at least one unit to price.');
            }

            return $this->back($ctx['from'], null, refused: false)->with('unit_rate_preview', $preview + [
                'names' => $ctx['names'],
                'room_type_id' => $ctx['room_type_id'],
                'room_type_name' => $ctx['room_type_name'],
                'start' => $ctx['start'],
                'end_inclusive' => $ctx['end_inclusive'],
                'amount' => (string) $request->input('amount', ''),
            ]);
        });
    }

    /** Apply the previewed change set — atomically, and only if it still matches. */
    public function apply(Request $request, UnitRateOverrideService $overrides): RedirectResponse
    {
        return $this->resolve($request, function (array $ctx) use ($request, $overrides): RedirectResponse {
            $digest = (string) $request->input('digest', '');
            if ($digest === '') {
                // ⚠️ NO APPROVAL, NO WRITE. A bulk apply that arrives without the previewed digest has not
                // been through the gate, so it is refused rather than trusted.
                return $this->back($ctx['from'], 'Preview the change before applying it.');
            }

            try {
                $result = $overrides->commit(
                    $ctx['plan'], $ctx['unit_ids'], $ctx['start'], $ctx['end_exclusive'], $ctx['mode'], $ctx['price_minor'], $digest,
                );
            } catch (PreviewChangedException $e) {
                return $this->back($ctx['from'], $e->getMessage());
            }

            return $this->back($ctx['from'], $this->summary($result, $ctx['names']), refused: false);
        });
    }

    /**
     * Shared validation + tenant-scoped resolution for both posts, so the two cannot drift apart in what
     * they consider a legal operation (§N.8f) — the digest would be meaningless if they could.
     */
    private function resolve(Request $request, callable $then): RedirectResponse
    {
        $from = $this->dateString($request->input('start_date'));

        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($from, 'Your workspace is read-only right now — rate changes are paused.');
        }

        $to = $this->dateString($request->input('end_date'));
        if ($from === null || $to === null) {
            return $this->back($from, 'Choose valid start and end dates.');
        }

        $start = CarbonImmutable::parse($from)->startOfDay();
        $endInclusive = CarbonImmutable::parse($to)->startOfDay();
        if ($endInclusive->lt($start)) {
            return $this->back($from, 'The end date can’t be before the start date.');
        }

        $endExclusive = $endInclusive->addDay();
        if ((int) $start->diffInDays($endExclusive) > UnitRateOverrideService::MAX_SPAN_NIGHTS) {
            return $this->back($from, 'That date range is too long — please apply at most a year at a time.');
        }

        $mode = (string) $request->input('mode');
        if (! in_array($mode, UnitRateOverrideService::MODES, true)) {
            return $this->back($from, 'Choose whether to set a price or clear the per-unit prices.');
        }

        // ⚠️ Parse the money BEFORE anything else touches the database, so an invalid figure rejects cleanly.
        $priceMinor = null;
        if ($mode === UnitRateOverrideService::MODE_SET) {
            $priceMinor = MoneyInput::toMinor((string) $request->input('amount', ''), self::MAX_PRICE_MINOR);
            if ($priceMinor === null) {
                return $this->back($from, 'Enter a valid nightly rate, e.g. 175 or 175.50.');
            }
        }

        $roomTypeId = (string) $request->input('room_type_id', '');
        if ($roomTypeId === '') {
            return $this->back($from, 'Choose a room type.');
        }

        /** @var RoomType $roomType — through the global scope, so a cross-org id 404s */
        $roomType = app(RateScope::class)->roomType($roomTypeId);   // DEC-l (2026-09-30) — inside the person's properties
        $plan = $roomType->primaryRatePlan();
        if (! $plan instanceof RatePlan) {
            return $this->back($from, 'That room type has no rate plan, so there is nowhere to store a price.');
        }

        // ⚠️ The unit set is narrowed to THIS room type as well as this tenant — a unit of another type can
        // never be given a price on this plan, whatever the form submits.
        $requested = array_values(array_filter(array_map(
            fn ($v): string => is_string($v) ? $v : '',
            (array) $request->input('unit_ids', []),
        )));

        // ⚠️ ORDER + LABEL BY `code`. Every real unit in this system carries a code ("101", "A1") and a NULL
        // `name` — labelling by name alone renders a bulk money screen where no row says which unit it is.
        // `name ?: code` is the convention the mailers already use; ordering by name would have grouped every
        // NULL together and shuffled the rows besides.
        $units = Unit::query()->where('room_type_id', $roomType->getKey())
            ->when($requested !== [], fn ($q) => $q->whereIn('id', $requested))
            ->orderBy('code')->get(['id', 'name', 'code']);

        if ($units->isEmpty()) {
            return $this->back($from, 'Choose at least one unit.');
        }
        if ($units->count() > UnitRateOverrideService::MAX_UNITS) {
            return $this->back($from, 'That is too many units for one operation.');
        }

        return $then([
            'from' => $from,
            'start' => $start->toDateString(),
            'end_inclusive' => $endInclusive->toDateString(),
            'end_exclusive' => $endExclusive->toDateString(),
            'mode' => $mode,
            'price_minor' => $priceMinor,
            'plan' => $plan,
            'room_type_id' => (string) $roomType->getKey(),
            'room_type_name' => (string) $roomType->name,
            'unit_ids' => $units->map(fn (Unit $u): string => (string) $u->getKey())->all(),
            'names' => $units->mapWithKeys(fn (Unit $u): array => [(string) $u->getKey() => (string) ($u->name ?: $u->code)])->all(),
        ]);
    }

    private function summary(array $result, array $names): string
    {
        $units = (int) $result['units'];
        $label = $units.' '.($units === 1 ? 'unit' : 'units');

        if ($result['mode'] === UnitRateOverrideService::MODE_CLEAR) {
            return 'Cleared the per-unit price for '.$label.' — '.($units === 1 ? 'it now follows' : 'they now follow').' the room-type rate again.';
        }

        // ⚠️ NIGHTS, not rows — this said "across 6 nights" for two units over three nights (2026-10-01).
        $nights = (int) $result['nights'];

        return 'Priced '.$label.' individually for '.$nights.' '.($nights === 1 ? 'night' : 'nights').'.';
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

    /**
     * Back to the Rates page — as the operator had it when the form was started there ({@see RatesListParams}), otherwise
     * the window starting at `$from`. ⚠️ A REFUSAL is an alert (`rates_error`) — until 2026-09-30 refusals and successes
     * shared `rates_notice`, so an error never announced itself.
     */
    private function back(?string $from, ?string $message, bool $refused = true): RedirectResponse
    {
        $redirect = RatesListParams::redirect(request(), $from);

        return $message === null ? $redirect : $redirect->with($refused ? 'rates_error' : 'rates_notice', $message);
    }
}
