<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The commercial terms between operator and owner (Phase 3 · Module 1). EFFECTIVE-DATED: attribution
 * for a given night uses the agreement in effect on that night, so terms can change over time without
 * rewriting history. `unit_id` null = applies to all the owner's units; set = per-unit override
 * (most-specific-wins). `attribution_rules` json holds OVERRIDES ONLY — a missing key falls back to
 * the documented default in the attribution engine (Step 2).
 *
 * @property array<string, mixed>|null $attribution_rules
 */
class ManagementAgreement extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'unit_id',
        'commission_pct',
        'management_fee_minor',
        'management_fee_currency',
        'management_fee_interval',
        'payout_currency',
        'payout_schedule',
        'attribution_rules',
        'effective_from',
        'effective_to',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_pct' => 'decimal:2',
            'management_fee' => MoneyCast::class.':management_fee_minor,management_fee_currency',
            'attribution_rules' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Constrain to agreements that are ACTIVE and in effect on the given date
     * (effective_from <= date <= effective_to, treating a null effective_to as open-ended).
     *
     * @param  Builder<ManagementAgreement>  $query
     * @return Builder<ManagementAgreement>
     */
    public function scopeEffectiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        $date = Carbon::parse($date)->toDateString();

        return $query
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $q) use ($date): void {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            });
    }

    /**
     * Resolve the single governing agreement for an owner (optionally a specific unit) on a date.
     * Most specific wins: a per-unit agreement in effect that day takes precedence over the
     * owner-wide one. Returns null if no active agreement covers the date.
     */
    public static function resolveFor(string $ownerId, ?string $unitId, CarbonInterface|string $date): ?self
    {
        return static::query()
            ->where('owner_id', $ownerId)
            ->effectiveOn($date)
            ->where(function (Builder $q) use ($unitId): void {
                $q->whereNull('unit_id');

                if ($unitId !== null) {
                    $q->orWhere('unit_id', $unitId);
                }
            })
            // unit-specific (unit_id NOT NULL) sorts before owner-wide (unit_id IS NULL).
            ->orderByRaw('unit_id IS NULL')
            ->first();
    }

    /**
     * Read a single attribution-rule override; returns $default (the documented default, supplied by
     * the attribution engine) when the agreement carries no override for that key.
     */
    public function rule(string $key, mixed $default = null): mixed
    {
        return $this->attribution_rules[$key] ?? $default;
    }
}
