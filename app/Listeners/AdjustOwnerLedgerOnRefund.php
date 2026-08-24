<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;

/**
 * Owner-statement adjustment on a guest refund (task #100 · F1). On a succeeded `payment.refunded` domain event,
 * posts an owner-scoped `adjustment` line to the owner ledger via the certified
 * {@see OwnerAttributionService::attributeRefund} — so the refund reduces the owner's net on the NEXT statement
 * close (anti-drift with the Statement/Balance the owner sees). Idempotent per refund payment; a no-op for an
 * operator-only (un-owned) reservation. Synchronous, same {@see DomainEventRecorded} bus as the guest emails.
 */
class AdjustOwnerLedgerOnRefund
{
    /** @var array<string, true> the double-fire guard (the bus can deliver an event more than once) */
    private static array $processed = [];

    public function __construct(
        private OwnerAttributionService $attribution,
        private TenantContext $tenant,
    ) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;
        if ($domainEvent->event_type !== 'payment.refunded') {
            return;
        }

        $key = (string) $domainEvent->id;
        if (isset(self::$processed[$key])) {
            return;
        }
        self::$processed[$key] = true;

        $orgId = $domainEvent->organization_id;
        if (! is_string($orgId) || $orgId === '') {
            return;
        }

        $this->tenant->forOrganization($orgId, function () use ($domainEvent): void {
            $refund = Payment::query()->find($domainEvent->subject_id);
            if (! $refund instanceof Payment || (string) $refund->status !== 'succeeded') {
                return;   // only a genuinely successful refund adjusts the owner
            }

            // ⚠️ THE OPERATOR EXPLICITLY ABSORBED THIS REFUND — post NOTHING. Not a zero row: NO row, so
            //    the owner's ledger is byte-identical to one where the refund never happened.
            //    ⚠️ NULL and 'owner' both fall through to the owner debit, unchanged from before this
            //    column existed, which is what makes the default a no-op on every existing row.
            if ($refund->isOperatorBorne()) {
                return;
            }

            $reservation = Reservation::query()->with('reservationUnits')->find($refund->reservation_id);
            if (! $reservation instanceof Reservation) {
                return;
            }

            // amount_minor is stored positive on the refund row; attribute its absolute value.
            $amount = Money::of(abs((int) $refund->amount_minor), (string) $refund->currency);
            $this->attribution->attributeRefund($reservation, $amount, (string) $refund->getKey());
        });
    }
}
