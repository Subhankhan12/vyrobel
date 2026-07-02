<?php

declare(strict_types=1);

namespace App\Services\Channex;

/**
 * One rate_plan's price + restrictions for a single date, as sent to the channel. Only
 * ACTIVELY-mapped rate plans are ever included (see {@see AriSnapshotBuilder}).
 */
final class AriRatePlanSnapshot
{
    public function __construct(
        public readonly string $ratePlanId,
        public readonly string $externalRatePlanId,
        public readonly ?int $priceMinor,
        public readonly ?string $currency,
        public readonly ?int $minStay,
        public readonly ?int $maxStay,
        public readonly bool $closedToArrival,
        public readonly bool $closedToDeparture,
        public readonly bool $stopSell,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rate_plan_id' => $this->ratePlanId,
            'external_rate_plan_id' => $this->externalRatePlanId,
            'price_minor' => $this->priceMinor,
            'currency' => $this->currency,
            'min_stay' => $this->minStay,
            'max_stay' => $this->maxStay,
            'closed_to_arrival' => $this->closedToArrival,
            'closed_to_departure' => $this->closedToDeparture,
            'stop_sell' => $this->stopSell,
        ];
    }
}
