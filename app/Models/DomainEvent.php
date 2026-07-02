<?php

namespace App\Models;

use App\Support\Events\DomainEventRecorder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Business event outbox (the semantic layer). Written via {@see DomainEventRecorder}.
 * Not tenant-scoped at the model level (system may emit events); `organization_id` is stamped
 * by the recorder and queried explicitly by consumers.
 *
 * @property string $event_type
 * @property string|null $organization_id
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array<string, mixed>|null $payload
 */
class DomainEvent extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id',
        'event_type',
        'subject_type',
        'subject_id',
        'payload',
        'occurred_at',
        'available_at',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'available_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
