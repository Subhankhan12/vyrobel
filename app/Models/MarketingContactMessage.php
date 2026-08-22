<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One message from the public contact form (front-website Gate 7).
 *
 * ⚠️ PLATFORM-LEVEL — no `organization_id` and no {@see BelongsToOrganization}. A
 * visitor has no tenant, and that trait's write guard THROWS when a tenant write runs without tenant
 * context, so adding it would break every submission. {@see PayoutRun} is the existing precedent for a row
 * that legitimately belongs to the platform rather than to a customer.
 *
 * ⚠️ THE CONTACT DETAILS ARE ENCRYPTED CASTS, matching `guests.email` / `guests.phone`. The message body is
 * encrypted too, because a prospective customer typically describes their business, their portfolio size and
 * what is going wrong with their current system in it — the same class of commercially sensitive free text
 * the project already encrypts elsewhere. ⚠️ An encrypted column is undone the moment its value is copied
 * somewhere plaintext, so nothing here is written into `audit_events` or a `domain_events` payload.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $company
 * @property string $message
 * @property string|null $units_band
 * @property string|null $topic
 * @property string|null $ip
 * @property bool $notified
 * @property Carbon|null $handled_at
 * @property Carbon $created_at
 */
class MarketingContactMessage extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'email',
        'company',
        'message',
        'units_band',
        'topic',
        'ip',
        'notified',
        'handled_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'email' => 'encrypted',
            'company' => 'encrypted',
            'message' => 'encrypted',
            'notified' => 'boolean',
            'handled_at' => 'datetime',
        ];
    }
}
