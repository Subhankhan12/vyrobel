<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Livewire\Operator\RatesGrid;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Operator RATES + RESTRICTIONS write — the FIRST-EVER production caller of
 * {@see RateAndRestrictionService} (Phase 1 shipped the service with the schema, but only the ARI-push
 * wiring referenced it in a comment; nothing drove it). Driven from the styled {@see RatesGrid} via the
 * locked operator-mutation pattern: POST → controller → service → redirect (PRG), exactly as
 * {@see ReservationLifecycleController} and {@see MaintenanceActionController}.
 *
 * ⚠️ PER-ROOM-TYPE, NOT PER-UNIT (docs/WAVE4-INVENTORY.md STRUCT-1). The design draws a per-physical-unit
 * grid, but `rate_calendar` is keyed on `rate_plan_id` and a rate plan belongs to a `room_type` — a room
 * type's three units share one plan, so a "per-unit" edit would silently move all three. This screen edits
 * a room type's PRIMARY rate plan (its `default_rate_plan_id`, or its first active plan), which is what the
 * service actually persists.
 *
 * ⚠️ INTEGER MONEY (convention #3 / invariant #17). The € the operator types is parsed to integer minor
 * units by {@see parseMinorUnits()} using STRING arithmetic — never a float multiply, which would drift
 * (17556.9999… on 175.57). The Money value object then carries it; the service stores `price_minor`. No
 * pricing math happens client-side.
 *
 * TENANT + PERMISSION. A normal POST re-runs the web middleware (SetTenantForWebRequest sets context), so
 * `{room_type}` is resolved through the `BelongsToOrganization` global scope AFTER the middleware (a
 * cross-org id 404s), and `/app` sits behind EnsureBackOfficeAccess (#26). The route additionally carries
 * `can:rates.manage` (#30) — held by Property Owner / Group Admin / Hotel Manager / Revenue Manager, and
 * deliberately NOT Front Desk or Reservation Agent (who hold at most `rates.view`). {@see RatesGrid} gates
 * the editor controls off the SAME permission so the UI never offers a write the route will 403.
 */
class RateActionController extends Controller
{
    /** A rate cannot exceed this (minor units) — €1,000,000.00 — a sanity bound on a hand-typed field. */
    private const MAX_PRICE_MINOR = 100_000_000;

    /** Longest span a single apply may cover, in nights — a guard on the per-night upsert loop. */
    private const MAX_SPAN_NIGHTS = 370;

    /**
     * Apply a rate and/or restriction change over a date range, to ONE room type or to ALL of the tenant's
     * room types. The single-cell editor is just start == end (one night); the bulk panel is a wider range.
     */
    public function apply(Request $request, RateAndRestrictionService $rates): RedirectResponse
    {
        $fromParam = $this->dateString($request->input('start_date'));
        $toParam = $this->dateString($request->input('end_date'));

        if ($fromParam === null || $toParam === null) {
            return $this->back($fromParam, 'Choose valid start and end dates.');
        }

        $start = CarbonImmutable::parse($fromParam)->startOfDay();
        $endInclusive = CarbonImmutable::parse($toParam)->startOfDay();

        if ($endInclusive->lt($start)) {
            return $this->back($fromParam, 'The end date can’t be before the start date.');
        }

        // The service takes a half-open [start, end) range; the operator's "end" is the last night they mean
        // to include, so the exclusive bound is one day later.
        $endExclusive = $endInclusive->addDay();
        $nights = (int) $start->diffInDays($endExclusive);

        if ($nights > self::MAX_SPAN_NIGHTS) {
            return $this->back($fromParam, 'That date range is too long — please apply at most a year at a time.');
        }

        $setRate = $request->boolean('set_rate');
        $setMinStay = $request->boolean('set_min_stay');
        $setStopSell = $request->boolean('set_stop_sell');

        if (! $setRate && ! $setMinStay && ! $setStopSell) {
            return $this->back($fromParam, 'Choose at least one thing to set — a nightly rate, a minimum stay, or open/close.');
        }

        // Parse money BEFORE touching the gateway of the DB — an invalid price must reject cleanly, never
        // half-apply a restriction and then fail on the rate.
        $priceMinor = null;
        if ($setRate) {
            $priceMinor = $this->parseMinorUnits((string) $request->input('rate', ''));
            if ($priceMinor === null) {
                return $this->back($fromParam, 'Enter a valid nightly rate, e.g. 175 or 175.50.');
            }
        }

        // min_stay: an explicit value ≥ 1, or blank meaning "no minimum" (null clears the column).
        $minStay = null;
        if ($setMinStay) {
            $raw = trim((string) $request->input('min_stay', ''));
            if ($raw !== '') {
                if (! ctype_digit($raw) || (int) $raw < 1 || (int) $raw > 365) {
                    return $this->back($fromParam, 'A minimum stay must be a whole number of nights (1–365), or blank for none.');
                }
                $minStay = (int) $raw;
            }
        }

        $stopSell = $setStopSell && $request->boolean('stop_sell');

        $roomTypes = $this->resolveRoomTypes($request->input('room_type_id'));
        if ($roomTypes === []) {
            return $this->back($fromParam, 'Choose a room type to update.');
        }

        $applied = 0;
        $missingPlan = 0;

        foreach ($roomTypes as $roomType) {
            if ($setRate) {
                $plan = $this->primaryRatePlan($roomType);
                if ($plan === null) {
                    // A room type with no rate plan has nowhere to store a price — report it rather than
                    // silently skipping, so the operator knows the rate did not land.
                    $missingPlan++;
                } else {
                    $rates->setRate($plan, $start, $endExclusive, Money::of($priceMinor, (string) $plan->currency));
                }
            }

            if ($setMinStay || $setStopSell) {
                $attributes = ['room_type_id' => (string) $roomType->getKey()];
                if ($setMinStay) {
                    $attributes['min_stay'] = $minStay;   // null clears it
                }
                if ($setStopSell) {
                    $attributes['stop_sell'] = $stopSell;
                }
                $rates->setRestriction($attributes, $start, $endExclusive);
            }

            $applied++;
        }

        return $this->back($fromParam, $this->summary($applied, $nights, $missingPlan, $setRate, $setMinStay, $setStopSell));
    }

    /**
     * The room types to apply to: a single tenant-scoped id, or every room type in the tenant when the
     * form submits `all`. `findOrFail` runs through the BelongsToOrganization global scope, so a cross-org
     * id 404s rather than leaking.
     *
     * @return list<RoomType>
     */
    private function resolveRoomTypes(mixed $roomTypeId): array
    {
        if ($roomTypeId === 'all') {
            return RoomType::query()->orderBy('name')->get()->all();
        }

        if (! is_string($roomTypeId) || $roomTypeId === '') {
            return [];
        }

        return [RoomType::query()->findOrFail($roomTypeId)];
    }

    /**
     * A room type's primary rate plan: its declared default, else the oldest active plan. Rates are stored
     * per rate plan, so the grid edits exactly one plan per room-type row (STRUCT-1).
     */
    private function primaryRatePlan(RoomType $roomType): ?RatePlan
    {
        if ($roomType->default_rate_plan_id !== null) {
            $default = $roomType->defaultRatePlan()->first();
            if ($default !== null) {
                return $default;
            }
        }

        return $roomType->ratePlans()->where('is_active', true)->orderBy('created_at')->first();
    }

    /**
     * Parse a hand-typed money amount to integer minor units WITHOUT a float multiply. Accepts an optional
     * currency glyph, thousands commas and up to two decimal places; rejects negatives and anything else.
     * Returns null on any invalid or non-positive input.
     */
    private function parseMinorUnits(string $input): ?int
    {
        $clean = str_replace([' ', ','], '', trim($input));
        $clean = ltrim($clean, '€£$');

        if ($clean === '' || preg_match('/^\d+(\.\d{1,2})?$/', $clean) !== 1) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $clean), 2, '');
        $fraction = str_pad($fraction, 2, '0');           // "5" → "50", "" → "00"
        $minor = ((int) $whole) * 100 + (int) $fraction;  // integer arithmetic only

        if ($minor <= 0 || $minor > self::MAX_PRICE_MINOR) {
            return null;
        }

        return $minor;
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

    private function summary(int $applied, int $nights, int $missingPlan, bool $rate, bool $minStay, bool $stopSell): string
    {
        $what = array_filter([
            $rate ? 'rate' : null,
            $minStay ? 'minimum stay' : null,
            $stopSell ? 'availability' : null,
        ]);

        $roomTypes = $applied.' '.($applied === 1 ? 'room type' : 'room types');
        $nightsLabel = $nights.' '.($nights === 1 ? 'night' : 'nights');
        $message = 'Updated '.implode(' + ', $what).' for '.$roomTypes.' across '.$nightsLabel.'.';

        if ($missingPlan > 0) {
            $message .= ' '.$missingPlan.' had no rate plan and were skipped for the rate.';
        }

        return $message;
    }

    /** PRG back to the rates grid, preserving the window, using the ONLY flash key that view renders. */
    private function back(?string $from, string $notice): RedirectResponse
    {
        $params = $from !== null ? ['from' => $from] : [];

        return redirect()->to(route('app.rates', $params))->with('rates_notice', $notice);
    }
}
