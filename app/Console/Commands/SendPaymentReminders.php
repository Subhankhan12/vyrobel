<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\PaymentReminderMail;
use App\Models\Folio;
use App\Models\Organization;
use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * The REAL trigger for the guest payment-reminder email (Phase 3 · Sub-gate 4): find confirmed reservations
 * arriving within the window that still carry an outstanding folio balance, and email each lead guest a reminder.
 * Runnable now (`php artisan emails:send-payment-reminders`) and scheduled daily in routes/console.php.
 *
 * Tenancy: iterates organizations and runs each org's query inside that org's context, so the tenant scope is
 * always satisfied (never an unscoped read). The balance is the SHARED {@see FolioTotals} figure — the same one
 * the folio, the balance-due notification and the PDFs use — never an invented number.
 */
class SendPaymentReminders extends Command
{
    protected $signature = 'emails:send-payment-reminders {--days=3 : Remind guests arriving within this many days}';

    protected $description = 'Email guests with an outstanding balance who are arriving within the given window.';

    public function handle(TenantContext $tenant): int
    {
        $days = max(1, (int) $this->option('days'));
        $today = Carbon::today();
        $windowEnd = $today->copy()->addDays($days);
        $sent = 0;

        foreach (Organization::all() as $organization) {
            $tenant->forOrganization((string) $organization->getKey(), function () use ($today, $windowEnd, &$sent): void {
                $reservations = Reservation::query()
                    ->with(['leadGuest', 'property'])
                    ->where('status', 'confirmed')
                    ->whereDate('check_in', '>=', $today->toDateString())
                    ->whereDate('check_in', '<=', $windowEnd->toDateString())
                    ->get();

                foreach ($reservations as $reservation) {
                    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
                    if ($folio === null) {
                        continue;
                    }

                    $balance = FolioTotals::for($folio)['balance_minor'];
                    if ($balance <= 0) {
                        continue;   // settled — no reminder
                    }

                    $email = $reservation->leadGuest?->email;   // encrypted cast → plaintext here
                    if (! is_string($email) || $email === '') {
                        continue;   // no address on file — nothing to send to
                    }

                    Mail::to($email)->send(new PaymentReminderMail($reservation, $balance));
                    $sent++;
                }
            });
        }

        $this->info("Payment reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
