<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An owner's PAYOUT PREFERENCES (Owner Payouts exact-match) — owner-scoped via {@see BelongsToOwner} (a portal
 * read only ever sees the authenticated owner's own row). A PREFERENCE, not a schedule engine: it records what
 * the owner WANTS (frequency · business-day · minimum · pause); the actual cadence + execution are confirmed with
 * the operator + the live payout rail (never a fabricated schedule, #39). Money is integer minor units (#17).
 *
 * @property string $frequency
 * @property int $payout_day
 * @property int $minimum_minor
 * @property bool $paused
 */
class OwnerPayoutPreference extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    /** The valid frequencies (the enum lives here — the UI + the validator both read it). */
    public const FREQUENCIES = ['monthly', 'twice_monthly', 'weekly'];

    protected $fillable = [
        'organization_id',
        'owner_id',
        'frequency',
        'payout_day',
        'minimum_minor',
        'paused',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payout_day' => 'integer',
            'minimum_minor' => 'integer',
            'paused' => 'boolean',
        ];
    }

    /**
     * The honest defaults for an owner who has never set a preference (so the page renders sensible values
     * without writing a row until the owner actually saves).
     *
     * @return array{frequency: string, payout_day: int, minimum_minor: int, paused: bool}
     */
    public static function defaults(): array
    {
        return ['frequency' => 'monthly', 'payout_day' => 3, 'minimum_minor' => 0, 'paused' => false];
    }

    /**
     * The current owner's preference (owner-scoped by the global scope) as a plain array, or the defaults when
     * none is set — the ONE resolve read by BOTH the Payouts page and the Payout Method schedule (anti-drift).
     *
     * @return array{frequency: string, payout_day: int, minimum_minor: int, paused: bool}
     */
    public static function resolve(): array
    {
        $pref = static::query()->first();

        return $pref instanceof self
            ? ['frequency' => $pref->frequency, 'payout_day' => $pref->payout_day, 'minimum_minor' => $pref->minimum_minor, 'paused' => $pref->paused]
            : static::defaults();
    }

    /** A human label for a frequency value. */
    public static function frequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'twice_monthly' => 'Twice monthly',
            'weekly' => 'Weekly',
            default => 'Monthly',
        };
    }
}
