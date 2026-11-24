<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Booking\UnpaidBookingRelease;
use Illuminate\Console\Command;

/**
 * ⚠️ VY-23 (audit of 2026-11-24) — release the storefront bookings whose card page closed unpaid. Runs every five minutes; Stripe's own
 * `checkout.session.expired` usually releases them first, and this is the net under a webhook that never arrived. Everything it decides
 * is in {@see UnpaidBookingRelease} (what counts as abandoned, and what is never released).
 */
class ReleaseUnpaidBookings extends Command
{
    protected $signature = 'bookings:release-unpaid';

    protected $description = 'Release storefront bookings whose card payment page closed without being paid (VY-23).';

    public function handle(UnpaidBookingRelease $release): int
    {
        $this->info('Released: '.$release->sweep());

        return self::SUCCESS;
    }
}
