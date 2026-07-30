<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Services\RateProposalService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-owner PRICING GUARDRAILS (Owner Rates exact-match #81) — "the limits your manager prices within".
 * Owner-scoped ({@see BelongsToOwner}) so a cross-owner guardrail is invisible + un-actionable. When a row
 * exists it GENUINELY constrains operator rate proposals in {@see RateProposalService::propose()}:
 * below `floor_minor` / above `ceiling_minor` is rejected; a change within ±`auto_approve_pct` of the current
 * rate (dynamic mode) auto-applies; anything larger stays pending for the owner. Money is integer minor units.
 *
 * @property string $pricing_mode
 * @property int $auto_approve_pct
 * @property ?int $floor_minor
 * @property ?int $ceiling_minor
 */
class RateGuardrail extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    public const MODE_DYNAMIC = 'dynamic';

    public const MODE_FIXED = 'fixed';

    protected $fillable = [
        'organization_id',
        'owner_id',
        'pricing_mode',
        'auto_approve_pct',
        'floor_minor',
        'ceiling_minor',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'auto_approve_pct' => 'integer',
            'floor_minor' => 'integer',
            'ceiling_minor' => 'integer',
        ];
    }
}
