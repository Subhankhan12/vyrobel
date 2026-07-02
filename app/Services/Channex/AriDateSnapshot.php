<?php

declare(strict_types=1);

namespace App\Services\Channex;

/**
 * One night's current state: sellable count + the mapped rate plans' price/restrictions.
 */
final class AriDateSnapshot
{
    /**
     * @param  list<AriRatePlanSnapshot>  $ratePlans
     */
    public function __construct(
        public readonly string $date,
        public readonly int $available,
        public readonly array $ratePlans,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'available' => $this->available,
            'rate_plans' => array_map(fn (AriRatePlanSnapshot $rp): array => $rp->toArray(), $this->ratePlans),
        ];
    }
}
