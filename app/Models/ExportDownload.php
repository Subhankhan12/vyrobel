<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An EXPORT DOWNLOAD record (Wave B · GATE 5) — one financial export a user downloaded. Append-only accountability;
 * tenant-scoped by {@see BelongsToOrganization} + carries the owner_id so the primary owner sees their own log.
 */
class ExportDownload extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'user_id',
        'downloader_name',
        'kind',
        'label',
        'subject_id',
        'ip',
    ];
}
