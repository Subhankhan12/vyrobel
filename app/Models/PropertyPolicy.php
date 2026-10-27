<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<string, mixed>|null $cancellation
 * @property array<string, mixed>|null $child_policy
 * @property array<string, mixed>|null $deposit
 * @property array<string, mixed>|null $damage
 * @property array<string, mixed>|null $long_stay_discounts
 * @property string|null $pet
 * @property string|null $smoking
 * @property bool $self_check_in
 * @property int|null $min_stay
 * @property int|null $max_stay
 */
class PropertyPolicy extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    /**
     * The words for a stored pet or smoking rule — the Settings form's own three options (its policies write,
     * `SettingsActionController::updatePolicies()`). ⚠️ 2026-10-04: the New reservation
     * page printed the stored code itself ("on_request", "not_allowed") on a property that had set its rules.
     */
    public const RULE_LABELS = ['allowed' => 'Allowed', 'on_request' => 'On request', 'not_allowed' => 'Not allowed'];

    /**
     * A stored rule in words, or NULL when none is set — never a default the property did not choose. A value that is not one
     * of the three codes (an older free-text row) is shown as written rather than dropped.
     */
    public static function ruleLabel(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return match (trim($value)) {
            'allowed' => (string) __('php/models/property_policy.rule_allowed'),
            'on_request' => (string) __('php/models/property_policy.rule_on_request'),
            'not_allowed' => (string) __('php/models/property_policy.rule_not_allowed'),
            default => trim($value),
        };
    }

    protected $fillable = [
        'organization_id',
        'property_id',
        'cancellation',
        'child_policy',
        'pet',
        'smoking',
        'self_check_in',
        'deposit',
        'damage',
        'min_stay',
        'max_stay',
        'long_stay_discounts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancellation' => 'array',
            'child_policy' => 'array',
            'deposit' => 'array',
            'damage' => 'array',
            'long_stay_discounts' => 'array',
            'min_stay' => 'integer',
            'max_stay' => 'integer',
            'self_check_in' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
