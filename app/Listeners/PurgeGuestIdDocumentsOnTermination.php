<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Console\Commands\PurgeExpiredGuestDocuments;
use App\Events\DomainEventRecorded;
use App\Services\Guest\GuestIdDocumentService;
use App\Support\Tenancy\TenantContext;
use Throwable;

/**
 * VY-18 (audit of 2026-11-24) — A BOOKING THAT WILL NOT HAPPEN KEEPS NO ID IMAGE.
 *
 * The only delete was "30 days after checkout", counted from the booking's original dates: a booking for next March, cancelled
 * today, kept the guest's passport photo until 30 days after a stay that never took place. On `reservation.cancelled` and
 * `reservation.no_show` the booking's ID documents are purged at once — the encrypted file and its three rows, through the single
 * teardown ({@see GuestIdDocumentService::purge()}).
 *
 * A strict one-directional CONSUMER of an already-committed domain event (the discipline of the other listeners): it writes only
 * in the guest-document domain, never touches the reservation or inventory, and runs synchronously.
 *
 * ⚠️ A FAILURE HERE NEVER UNDOES THE CANCELLATION — it is reported, and {@see PurgeExpiredGuestDocuments} (nightly) purges the image
 *    of any cancelled / no-show booking it finds, so a missed purge is caught within a day rather than never.
 * ⚠️ Both terminal states are FINAL in this product (a cancelled booking cannot be revived — ruling R1), so nothing can later need
 *    the image back.
 */
class PurgeGuestIdDocumentsOnTermination
{
    /** The reservation events after which the stay will not happen. */
    private const TRIGGERS = ['reservation.cancelled', 'reservation.no_show'];

    public function __construct(
        private TenantContext $tenant,
        private GuestIdDocumentService $ids,
    ) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;

        if (! in_array((string) $domainEvent->event_type, self::TRIGGERS, true)) {
            return;
        }

        $reservationId = (string) $domainEvent->subject_id;
        if ($reservationId === '') {
            return;
        }

        try {
            $this->tenant->forOrganization(
                (string) $domainEvent->organization_id,
                fn (): int => $this->ids->purgeForReservation($reservationId),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
