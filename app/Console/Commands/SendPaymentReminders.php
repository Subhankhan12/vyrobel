<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\PaymentReminderMail;
use App\Models\Folio;
use App\Models\Organization;
use App\Models\Reservation;
use App\Notifications\Guest\GuestPaymentDueNotification;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\GuestEmailPreferences;
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

                    // ⚠️ GUEST NOTIFICATIONS · GATE 2 · G4 — the IN-APP row, dispatched only once the balance
                    // is known to be OUTSTANDING (the `continue` above already skipped settled folios), but
                    // BEFORE the email's own address/template guards — separate channels.
                    $reservation->leadGuest?->notify(new GuestPaymentDueNotification($reservation, $balance));

                    $email = $reservation->leadGuest?->email;   // encrypted cast → plaintext here
                    if (! is_string($email) || $email === '') {
                        continue;   // no address on file — nothing to send to
                    }
                    // #86 — the operator can disable this email per-org; OFF genuinely stops the send.
                    if (! EmailTemplateResolver::enabled((string) $reservation->organization_id, 'payment-reminder')) {
                        continue;
                    }
                    // ⚠️ AND THE GUEST'S OWN CHOICE — a SECOND, INDEPENDENT gate. The operator's #86 switch
                    // is about the property; this is about the person. Both must pass. The in-app row above
                    // is unaffected: switching the EMAIL off does not blind the guest inside the portal.
                    if (! GuestEmailPreferences::mayReceive($reservation->leadGuest, 'payment')) {
                        continue;
                    }

                    Mail::to($email)->locale(EmailTemplateResolver::localeFor((string) $reservation->organization_id, 'payment-reminder', $reservation->leadGuest->preferredLocale()))->send(new PaymentReminderMail($reservation, $balance));
                    $sent++;
                }
            });
        }

        $this->info("Payment reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
