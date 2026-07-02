<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DomainEvent;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired synchronously by {@see DomainEventRecorder} right after a
 * domain_events outbox row is written. Lets in-process listeners (e.g. availability recompute)
 * react without a running queue worker — the Phase-1 defense-in-depth path.
 */
class DomainEventRecorded
{
    use Dispatchable;

    public function __construct(
        public readonly DomainEvent $domainEvent,
    ) {}
}
