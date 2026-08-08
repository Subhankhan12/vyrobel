<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\ManagementAgreement;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The ONE shared read of an owner's PER-VILLA commercial terms (Owner Agreement exact-match #82) — so the
 * Agreement page and the agreement PDF quote the SAME figures and can never drift.
 *
 * Each row resolves the GOVERNING agreement for that unit on the given date through the certified
 * {@see ManagementAgreement::resolveFor()} (most-specific-wins: a per-unit override beats the
 * portfolio-wide one) — i.e. exactly the rate the attribution engine / statements apply, #17. `since` is
 * that agreement's effective_from. Cleaning is NOT a modelled per-unit term → honest null (never a
 * fabricated €90); the caller renders "At cost".
 */
final class OwnerAgreementTerms
{
    /**
     * @param  list<string>  $ownerIds
     * @return list<array{unit: string, commission: ?string, since: ?string, is_low: bool}>
     */
    public static function perVilla(array $ownerIds, CarbonInterface|Carbon|string|null $on = null): array
    {
        if ($ownerIds === []) {
            return [];
        }

        $date = $on !== null ? Carbon::parse((string) $on) : Carbon::today();

        // Units ARE owner-scoped (BelongsToOwner) on the portal; still constrain by the resolved ids so
        // this is correct off-portal too. Active units only — a paused unit is still under agreement, so
        // keep it (the owner's terms apply regardless of the booking pause).
        $units = Unit::query()
            ->whereIn('owner_id', $ownerIds)
            ->orderBy('code')
            ->get();

        // The portfolio-wide rate is the fallback/comparison baseline for the "below standard" cue.
        $portfolioPct = null;
        $portfolio = ManagementAgreement::query()
            ->whereIn('owner_id', $ownerIds)
            ->whereNull('unit_id')
            ->effectiveOn($date)
            ->orderByDesc('effective_from')
            ->first();
        if ($portfolio instanceof ManagementAgreement && $portfolio->commission_pct !== null) {
            $portfolioPct = (float) $portfolio->commission_pct;
        }

        $rows = [];

        foreach ($units as $unit) {
            $agreement = ManagementAgreement::resolveFor((string) $unit->owner_id, (string) $unit->id, $date);

            $pct = $agreement?->commission_pct;
            $pctFloat = $pct === null ? null : (float) $pct;

            $name = (string) $unit->name;
            $label = (string) $unit->code.($name !== '' ? ' · '.$name : '');

            $rows[] = [
                'unit' => $label,
                'commission' => $pct === null ? null : self::pct($pctFloat),
                'since' => $agreement?->effective_from?->format('Y') ?? null,
                // A per-unit rate genuinely below the portfolio baseline is highlighted (the wireframe's
                // green cue) — real comparison, never a fabricated discount.
                'is_low' => $pctFloat !== null && $portfolioPct !== null && $pctFloat < $portfolioPct,
            ];
        }

        return $rows;
    }

    /** Trim a decimal percent to its shortest honest form: 18.00 → "18%", 17.50 → "17.5%". */
    private static function pct(float $value): string
    {
        return (string) OwnerCommissionRate::display($value);
    }
}
