<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\PreArrivalMail;
use App\Models\Organization;
use App\Models\Reservation;
use App\Support\Guest\ArrivalPack;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * The REAL trigger for the guest pre-arrival email (transactional-emails exact-match) — the piece deferred in
 * Phase 3. Find confirmed reservations checking in `--days` out (default {@see ArrivalPack::LEAD_DAYS} = 48h,
 * exactly when the access secrets unlock) and email each lead guest their self check-in.
 *
 * Mirrors {@see SendPaymentReminders}: iterate organizations, run each org's query inside that org's context (the
 * tenant scope is always satisfied), send only to the lead guest's OWN email. The door code + wifi come from the
 * shared {@see ArrivalPack} inside the Mailable — real, guest-only, and withheld (never fabricated) for a stay
 * with no code. Runnable now (`php artisan emails:send-pre-arrival`); scheduled daily in routes/console.php.
 */
class SendPreArrivalEmails extends Command
{
    protected $signature = 'emails:send-pre-arrival {--days= : Check-ins this many days out (default: the arrival-window lead)}';

    protected $description = 'Email guests their self check-in (door code, directions, wifi) the day the arrival window opens.';

    public function handle(TenantContext $tenant): int
    {
        $days = $this->option('days') !== null ? max(0, (int) $this->option('days')) : ArrivalPack::LEAD_DAYS;
        $target = Carbon::today()->addDays($days)->toDateString();
        $sent = 0;

        foreach (Organization::all() as $organization) {
            $tenant->forOrganization((string) $organization->getKey(), function () use ($target, &$sent): void {
                $reservations = Reservation::query()
                    ->with(['leadGuest', 'property', 'reservationUnits.unit'])
                    ->where('status', 'confirmed')
                    ->whereDate('check_in', $target)
                    ->get();

                foreach ($reservations as $reservation) {
                    $email = $reservation->leadGuest?->email;   // encrypted cast → plaintext here; guest-only
                    if (! is_string($email) || $email === '') {
                        continue;   // no address on file — nothing to send to
                    }

                    Mail::to($email)->send(new PreArrivalMail($reservation));
                    $sent++;
                }
            });
        }

        $this->info("Pre-arrival emails sent: {$sent}");

        return self::SUCCESS;
    }
}
