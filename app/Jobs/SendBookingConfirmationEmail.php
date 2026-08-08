<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Sync\RetryableSyncJob;
use App\Mail\BookingConfirmationMail;
use App\Models\Reservation;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The DELAYED guest booking-confirmation send (audit 2026-08-07 · H2 / F-2b-01 / P5-04).
 *
 * WHY A JOB AND NOT `Mail::later(new BookingConfirmationMail($reservation))`:
 * that queued a Mailable holding a Reservation via {@see SerializesModels}. On the worker the payload
 * restores fine — but RENDERING the mail touches `$reservation->leadGuest` and the property, both
 * tenant-scoped, and the worker has no ambient {@see TenantContext}. Every confirmation therefore threw
 * `TenantContextMissingException` and failed permanently (36 dead jobs in the live dev DB), with horizon
 * `tries => 1` guaranteeing no retry. The suite never saw it: tests used `Mail::fake()` + `assertQueued`,
 * which asserts a job was ENQUEUED and never renders it.
 *
 * ⚠️ Verified precisely, because the distinction changes the fix: the audit reported this as a failure on
 * DESERIALIZATION; it is actually a failure on RENDER (which is why the production exception named
 * [App\Models\Guest], not [App\Models\Reservation]). Wrapping only the deserialize would not have fixed it —
 * the whole send has to run inside the tenant context, which is what handle() below does.
 * tests/Feature/ConfirmationEmailDeliveryTest.php pins both halves.
 *
 * THE FIX mirrors {@see RetryableSyncJob}: the payload carries only SCALARS (an
 * organization id + a reservation id), and the body runs inside `TenantContext::forOrganization()`,
 * which is where the model is loaded. No tenant-scoped model ever crosses the queue boundary.
 *
 * ⚠️ NO PII IN THE PAYLOAD. The recipient address is deliberately NOT a constructor argument — it is
 * resolved from the guest INSIDE the tenant context at send time. A queued payload is stored in plaintext
 * in `jobs`/`failed_jobs`, so passing the address would have copied an encrypted column into a plaintext
 * store and put it out of reach of `Guest::anonymize()` (audit F-2b-04). Resolving it here also means a
 * guest erased between booking and send is not emailed.
 *
 * THE DELAY IS LOAD-BEARING (unchanged): `reservation.confirmed` is recorded INSIDE createBooking, BEFORE
 * the caller posts the folio. Sending inline would render `total_minor = 0` with no folio items (a "€0.00"
 * email). The job re-reads the reservation once the request has committed the folio, so the Total and the
 * breakdown reconcile (#17).
 */
class SendBookingConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Transient failures (SMTP 4xx, a provider blip, a DB deadlock) deserve a retry; 1 discarded them. */
    public int $tries = 3;

    /** @var list<int> seconds between attempts */
    public array $backoff = [30, 120];

    public function __construct(
        public readonly string $organizationId,
        public readonly string $reservationId,
    ) {}

    public function handle(TenantContext $tenant): void
    {
        $tenant->forOrganization($this->organizationId, function (): void {
            $reservation = Reservation::query()->with('leadGuest')->find($this->reservationId);

            if (! $reservation instanceof Reservation) {
                return;   // cancelled + hard-removed between booking and send — nothing to confirm.
            }

            $email = $reservation->leadGuest?->email;   // encrypted cast → plaintext only here, in memory

            if (! is_string($email) || $email === '') {
                return;   // no address (or an anonymised guest) → no send, never a throw.
            }

            Mail::to($email)->send(new BookingConfirmationMail($reservation));
        });
    }

    /**
     * A send that exhausts its retries must not be silent — but must never log the address.
     */
    public function failed(Throwable $e): void
    {
        Log::warning('Guest booking-confirmation email failed after retries', [
            'reservation_id' => $this->reservationId,
            'error' => $e->getMessage(),
        ]);
    }
}
