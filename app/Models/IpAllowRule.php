<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Middleware\EnforceIpAllowlist;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * #104b GATE 1 — an org's IP-allowlist rule (operator Security). When an org has ≥1 rule, ONLY a request whose IP
 * matches a rule reaches /app ({@see EnforceIpAllowlist}); zero rules ⇒ no restriction (the
 * honest "No IP restrictions" state). Tenant-scoped ({@see BelongsToOrganization}) — it governs THIS org's admin
 * panel. `ip_address` is an exact IP or a CIDR block, matched with Symfony IpUtils.
 *
 * @property string $ip_address
 * @property string|null $label
 */
class IpAllowRule extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'ip_address',
        'label',
        'created_by',
    ];
}
