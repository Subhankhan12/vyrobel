<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Billing\BillableUnits;
use App\Support\Billing\PlanTerms;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Global product package catalog (not tenant-scoped). `price` is a Money value object over
 * the `price_minor` + `currency` columns.
 *
 * @property string $code
 * @property string $name
 * @property int $price_minor
 * @property string $currency
 * @property string $pricing_type flat | per_unit
 * @property ?int $unit_rate_minor
 * @property ?int $vat_pct
 * @property ?string $stripe_price_id
 * @property string $interval
 * @property bool $is_active
 */
class Plan extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'price_minor',
        'pricing_type',
        'unit_rate_minor',
        'vat_pct',
        'currency',
        'stripe_price_id',
        'interval',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class.':price_minor,currency',
            'is_active' => 'boolean',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** A plan charges one price regardless of how many units the org runs. */
    public const PRICING_FLAT = 'flat';

    /** A plan charges `unit_rate_minor` × the org's REAL active-unit count, plus VAT. */
    public const PRICING_PER_UNIT = 'per_unit';

    public function isPerUnit(): bool
    {
        return $this->pricing_type === self::PRICING_PER_UNIT;
    }

    /**
     * The quantity to charge for this plan.
     *
     * ⚠️ FLAT is always 1 — a flat plan's price does not move with the rack, so sending the unit count would
     * multiply the bill by it. PER_UNIT is the org's REAL active-unit count, derived at call time from the ONE
     * source {@see BillableUnits} rather than read from a stored copy: a stored quantity is correct only until
     * the next unit is added, and a charged quantity that disagrees with the org's units is §N.8d with money
     * attached.
     */
    public function quantityFor(): int
    {
        return $this->isPerUnit() ? BillableUnits::count() : 1;
    }

    /**
     * What an org owes on this plan this period, in integer minor units (#17).
     *
     * FLAT     → the plan's own `price_minor`, VAT-inclusive as configured today (no VAT line is added, because
     *            a flat tier price is quoted as the price).
     * PER_UNIT → rate × the real unit count, + the plan's own VAT percentage.
     *
     * @return array{units: int, net_minor: int, vat_minor: int, gross_minor: int, tax_known: bool, pricing_type: string}
     */
    public function chargeFor(): array
    {
        // ⚠️ Delegated to the ONE calculation (the plan-panel pass). A subscription can now bill from CAPTURED
        // terms rather than this live row, and two sources with two arithmetics is exactly how
        // `SubscriptionPricing` drifted from this method (§N.17.4). The sources differ; the maths does not.
        return PlanTerms::fromPlan($this)->charge();
    }
}
