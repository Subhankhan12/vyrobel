<?php

declare(strict_types=1);

namespace App\Models;

use App\Console\Commands\SetTaxRate;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A configured tax rate (Wave D · tax engine · Phase 1) — one (jurisdiction, rate_type) rate effective over a
 * date range. ⚠️ The value the engines multiply a real ledger amount by. PLATFORM-LEVEL (per country, not per
 * tenant). `is_example = true` marks a seeded EXAMPLE the founder MUST replace — the surfaces flag it and NEVER
 * present it as authoritative; a real rate ({@see SetTaxRate}) sets `is_example = false`.
 *
 * @property string $id
 * @property string $jurisdiction
 * @property string $rate_type
 * @property string $percent decimal string, e.g. "23.000"
 * @property Carbon $effective_from
 * @property Carbon|null $effective_to
 * @property bool $is_example
 * @property string|null $note
 */
class TaxRate extends Model
{
    use HasUlids;

    public const TYPE_ACCOMMODATION_VAT = 'accommodation_vat';

    public const TYPE_SERVICE_VAT = 'service_vat';

    public const TYPE_WITHHOLDING = 'withholding';

    public const TYPES = [self::TYPE_ACCOMMODATION_VAT, self::TYPE_SERVICE_VAT, self::TYPE_WITHHOLDING];

    protected $fillable = [
        'jurisdiction',
        'rate_type',
        'percent',
        'effective_from',
        'effective_to',
        'is_example',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_example' => 'boolean',
        ];
    }

    /** The rate as a float percent (e.g. 23.0). Display/compute helper — the math uses Money::percentage. */
    public function percentFloat(): float
    {
        return (float) $this->percent;
    }
}
