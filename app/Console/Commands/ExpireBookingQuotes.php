<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BookingEngineService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * ⚠️ VY-23 (audit of 2026-11-24) — quotes past their expiry are marked abandoned ON A SCHEDULE. {@see BookingEngineService::expireStaleQuotes()}
 * existed and nothing called it, so every quote a visitor ever made stayed "held". A quote holds no inventory (the lock at booking is the
 * guarantee), so this changes no availability — it keeps the quote's state true, and the abandoned ones are the recovery list.
 */
class ExpireBookingQuotes extends Command
{
    protected $signature = 'booking-quotes:expire';

    protected $description = 'Mark storefront quotes past their expiry as abandoned (VY-23).';

    public function handle(TenantContext $tenant, BookingEngineService $engine): int
    {
        $this->info('Quotes expired: '.$tenant->runAsSuperAdmin(fn (): int => $engine->expireStaleQuotes()));

        return self::SUCCESS;
    }
}
