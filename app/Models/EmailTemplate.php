<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\Email\EmailTemplateCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-org customization of one guest email (Operator Email Templates exact-match #86) — the store the real
 * #83 Mailables read at send. A null column falls back to the code default ({@see EmailTemplateCatalog});
 * `blocks` toggles content blocks. Tenant-scoped ({@see BelongsToOrganization}); audit subject (morph alias).
 *
 * @property array<string, bool>|null $blocks
 * @property bool $enabled
 */
class EmailTemplate extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'template_key',
        'enabled',
        'subject',
        'heading',
        'message',
        'button_label',
        'blocks',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'blocks' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
