<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AccountDeletionScheduledMail;
use App\Models\Guest;
use App\Models\Reservation;
use App\Notifications\Guest\GuestAccountSecurityNotification;
use App\Support\Events\AuditEventRecorder;
use App\Support\Log\LogSafe;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Guest-side "delete my account" as a REVERSIBLE, SCHEDULED erase (Guest Portal · GATE 3, founder decision D2) —
 * the SAME schedule/cancel/processDue shape as {@see PropertyRemovalService} (#98). Confirming the delete stamps
 * `scheduled_deletion_at` 14 days out and emails the guest; the account stays fully usable and the guest can UNDO
 * (clear it) within the window. A daily command runs {@see processDue()} on/after the date, and then calls the
 * certified {@see Guest::anonymize()} GDPR erase (PII + documents + inbox + auth).
 *
 * ⚠️⚠️ THE ERASE CANCELS BOOKINGS AND CAN MOVE MONEY — say so, because a reader who believes otherwise will
 * under-warn the guest. {@see eraseNow()} puts every row from {@see reservationsToCancel()} through
 * {@see ReservationCancellationService::cancelWithPolicyRefund()}, which cancels through the certified
 * {@see AssignmentService} AND refunds the full remaining-refundable of each settled payment when
 * {@see CancellationPolicy::isRefundable()} is true on the day the sweep runs (bounded #27) — refunding nothing
 * when it is false. A refund there posts its own owner-ledger adjustment. NEVER immediate/destructive; NEVER a
 * fabricated undo timer.
 *
 * PII-safe events (invariant #28): audit records WHICH field changed (`['fields' => [...]]`), never the values.
 */
class GuestDeletionService
{
    /** Days the guest has to change their mind before the erase runs. */
    public const GRACE_DAYS = 14;

    public function __construct(
        private readonly AuditEventRecorder $audit,
        private readonly ReservationCancellationService $cancellations,
        private readonly TenantContext $tenant,
    ) {}

    /** Schedule the erase 14 days out (idempotent — an already-scheduled account keeps its original date). */
    public function schedule(Guest $guest, ?CarbonInterface $now = null): CarbonInterface
    {
        $effective = ($now ? CarbonImmutable::parse($now) : CarbonImmutable::now())->addDays(self::GRACE_DAYS);

        if ($guest->scheduled_deletion_at === null) {
            $guest->forceFill(['scheduled_deletion_at' => $effective])->save();
            $this->audit->record('guest.deletion_scheduled', $guest, null, ['fields' => ['scheduled_deletion_at']]);
            $this->email($guest);
            // ⚠️ GUEST NOTIFICATIONS · GATE 2 · G8 — the IN-APP row, ADDITIVE to the email above (which is
            // untouched). Always-on: a pending erasure of your own account is not a preference. The row
            // carries NO date — the account page reads the authoritative one, which can change if the request
            // is cancelled and re-made.
            $guest->notify(new GuestAccountSecurityNotification('scheduled'));
        }

        return $guest->scheduled_deletion_at instanceof CarbonInterface ? $guest->scheduled_deletion_at : $effective;
    }

    /** Undo — the guest keeps their account (only meaningful before the sweep runs). */
    public function cancel(Guest $guest): void
    {
        if ($guest->scheduled_deletion_at === null) {
            return;
        }
        $guest->forceFill(['scheduled_deletion_at' => null])->save();
        $this->audit->record('guest.deletion_cancelled', $guest, null, ['fields' => ['scheduled_deletion_at']]);
        $guest->notify(new GuestAccountSecurityNotification('cancelled'));
    }

    /**
     * Run the erase for every guest whose scheduled date has arrived. Super-admin selects the due guests across
     * orgs (id + org only), then each is processed INSIDE its own org context so scopes + events stay correct.
     */
    public function processDue(?CarbonInterface $now = null): int
    {
        $cutoff = $now ? CarbonImmutable::parse($now) : CarbonImmutable::now();

        $due = $this->tenant->runAsSuperAdmin(fn () => Guest::query()
            ->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', $cutoff)
            ->whereNull('anonymized_at')
            ->get(['id', 'organization_id']));

        $count = 0;
        foreach ($due as $ref) {
            $this->tenant->forOrganization((string) $ref->organization_id, function () use ($ref, &$count): void {
                $guest = Guest::query()->find($ref->getKey());
                if ($guest instanceof Guest && ! $guest->isAnonymized()) {
                    $this->eraseNow($guest);
                    $count++;
                }
            });
        }

        return $count;
    }

    /**
     * The reservations a scheduled erase WOULD cancel — anything not already cancelled / no-show / checked-out,
     * so a stay IN PROGRESS is included.
     *
     * ⚠️ THE ONE DEFINITION, on purpose. {@see eraseNow()} cancels exactly this set, and the account screen quotes
     * its count to warn the guest BEFORE they confirm. A second copy of this predicate would let the warning and
     * the erase disagree — the drift this codebase removes wherever it finds it.
     *
     * @return EloquentCollection<int, Reservation>
     */
    public function reservationsToCancel(Guest $guest): EloquentCollection
    {
        return $guest->reservations()
            ->whereNotIn('status', AssignmentService::TERMINAL_STATUSES)
            ->get();
    }

    /** Cancel the guest's still-live reservations per policy, then run the certified GDPR erase. */
    private function eraseNow(Guest $guest): void
    {
        $live = $this->reservationsToCancel($guest);

        foreach ($live as $reservation) {
            try {
                // Cancel per policy (+ a full in-window refund, bounded #27) — never block the erase on one.
                $this->cancellations->cancelWithPolicyRefund($reservation, 'Guest account deletion');
            } catch (Throwable) {
                // Already terminal / a race — skip.
            }
        }

        // The certified erase — PII + documents + inbox + auth; records its own PII-safe events (#28).
        $guest->anonymize();
    }

    /** The "deletion scheduled" confirmation email (a side effect — never breaks the schedule). */
    private function email(Guest $guest): void
    {
        $email = $guest->email;
        if (! is_string($email) || $email === '') {
            return;
        }
        try {
            Mail::to($email)->send(new AccountDeletionScheduledMail($guest, $this->reservationsToCancel($guest)->count()));
        } catch (Throwable $e) {
            Log::warning('Account-deletion email failed', ['error' => LogSafe::message($e)]);   // never the address (no PII)
        }
    }
}
