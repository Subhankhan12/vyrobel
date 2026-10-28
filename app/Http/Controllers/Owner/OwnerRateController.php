<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Exceptions\Owner\OwnerRateException;
use App\Http\Controllers\Controller;
use App\Services\OwnerRateService;
use App\Support\I18n\LocalNumber;
use App\Support\Money\MoneyInput;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner RATES write (Phase 2 · Sub-gate 3) — POST → {@see OwnerRateService} → PRG. The owner sets a nightly
 * rate (and optional minimum stay) on one of their OWN room types, DIRECTLY (no operator sign-off — the
 * conscious divergence from the design's approval model, recorded in the service + docs).
 *
 * The € is parsed to integer minor units HERE by {@see MoneyInput::toMinor()} (string arithmetic, never a
 * float) BEFORE any DB touch, so an invalid price rejects cleanly. Owner isolation is enforced in the service
 * (the room type is resolved through EXCLUSIVE ownership, never the request id); a room type the owner may not
 * edit surfaces here as a 403.
 */
class OwnerRateController extends Controller
{
    /** A rate cannot exceed this (minor units) — €1,000,000.00 — a sanity bound on a hand-typed field. */
    private const MAX_PRICE_MINOR = 100_000_000;

    /** Longest span a single apply may cover, in nights — a guard on the per-night upsert loop. */
    private const MAX_SPAN_NIGHTS = 370;

    public function apply(Request $request, OwnerRateService $rates): RedirectResponse
    {
        $from = $this->dateString($request->input('start_date'));
        $to = $this->dateString($request->input('end_date'));
        $roomTypeId = (string) $request->input('room_type_id', '');

        // The inline "Edit pricing" steppers post a redirect hint so the owner returns to the surface they edited
        // from (with the flash): `units` → My Units, `unit` (+ a `unit` id) → that Unit Detail page. Any other
        // value → the full Rates screen (the default write path).
        $redirect = $request->input('redirect');
        $unitId = (string) $request->input('unit', '');
        $back = match (true) {
            $redirect === 'units' => route('owner.units'),
            $redirect === 'unit' && $unitId !== '' => route('owner.units.show', $unitId),
            default => route('owner.rates', ['room_type' => $roomTypeId, 'from' => $from]),
        };

        if ($from === null || $to === null) {
            return redirect()->to($back)->with('rate_error', __('php/http/controllers/owner/owner_rate_controller.invalid_dates'));
        }

        $start = CarbonImmutable::parse($from)->startOfDay();
        $endInclusive = CarbonImmutable::parse($to)->startOfDay();

        if ($endInclusive->lt($start)) {
            return redirect()->to($back)->with('rate_error', __('php/http/controllers/owner/owner_rate_controller.end_before_start'));
        }

        // Certified service takes half-open [start, end); the owner's "end" is the last night to include.
        $endExclusive = $endInclusive->addDay();
        if ((int) $start->diffInDays($endExclusive) > self::MAX_SPAN_NIGHTS) {
            return redirect()->to($back)->with('rate_error', __('php/http/controllers/owner/owner_rate_controller.span_too_long'));
        }

        // Integer money — string arithmetic, before any DB touch (an invalid rate must not half-apply).
        $priceMinor = MoneyInput::toMinor((string) $request->input('rate', ''), self::MAX_PRICE_MINOR);
        if ($priceMinor === null) {
            return redirect()->to($back)->with('rate_error', __('php/http/controllers/owner/owner_rate_controller.invalid_rate'));
        }

        // Optional minimum stay: a whole number ≥ 1, or blank for none.
        $minStay = null;
        $rawMinStay = trim((string) $request->input('min_stay', ''));
        if ($rawMinStay !== '') {
            if (! ctype_digit($rawMinStay) || (int) $rawMinStay < 1 || (int) $rawMinStay > 365) {
                return redirect()->to($back)->with('rate_error', __('php/http/controllers/owner/owner_rate_controller.invalid_min_stay'));
            }
            $minStay = (int) $rawMinStay;
        }

        // Optional weekend uplift %: a number 0–200, or blank for none (0 clears it). Fri/Sat nights get
        // base × (1 + uplift%) — a REAL price change, applied by OwnerRateService (#39, never an inert store).
        $weekendUplift = null;
        $rawUplift = trim((string) $request->input('weekend_uplift', ''));
        $rawUplift = $rawUplift === '' ? '' : (LocalNumber::normalize($rawUplift, 2) ?? $rawUplift);   // D-MONEY-COMMA: "12,5"
        if ($rawUplift !== '') {
            if (! is_numeric($rawUplift) || (float) $rawUplift < 0 || (float) $rawUplift > 200) {
                return redirect()->to($back)->with('rate_error', __('php/http/controllers/owner/owner_rate_controller.invalid_weekend_uplift'));
            }
            $weekendUplift = round((float) $rawUplift, 2);
        }

        try {
            $stored = $rates->apply($roomTypeId, $start->toDateString(), $endExclusive->toDateString(), $priceMinor, $minStay, null, $weekendUplift);
        } catch (OwnerRateException) {
            abort(403);   // a room type the owner may not edit — tampering, not a normal outcome
        }

        $nights = (int) $start->diffInDays($endExclusive);
        $notice = $stored
            ? trans_choice('php/http/controllers/owner/owner_rate_controller.rate_updated', $nights, ['count' => $nights])
            : __('php/http/controllers/owner/owner_rate_controller.no_rate_plan');

        return redirect()->to($back)->with('rate_notice', $notice);
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
}
