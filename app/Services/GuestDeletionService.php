<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AccountDeletionScheduledMail;
use App\Models\Guest;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Guest-side "delete my account" as a REVERSIBLE, SCHEDULED erase (Guest Portal · GATE 3, founder decision D2) —
 * the SAME schedule/cancel/processDue shape as {@see PropertyRemovalService} (#98). Confirming the delete stamps
 * `scheduled_deletion_at` 14 days out and emails the guest; the account stays fully usable and the guest can UNDO
 * (clear it) within the window. A daily command runs {@see processDue()} on/after the date: it cancels the guest's
 * upcoming reservations per policy (inventory/status via the certified {@see AssignmentService}; any refund stays
 * the operator's separate step — the policy is display-only) and then calls the certified {@see Guest::anonymize()}
 * GDPR erase (PII + documents + inbox + auth). NEVER immediate/destructive; NEVER a fabricated undo timer.
 *
 * PII-safe events (invariant #28): audit records WHICH field changed (`['fields' => [...]]`), never the values.
 */
class GuestDeletionService
{
    /** Days the guest has to change their mind before the erase runs. */
    public const GRACE_DAYS = 14;

    public function __construct(
        private readonly AuditEventRecorder $audit,
        private readonly AssignmentService $assignment,
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

    /** Cancel the guest's still-live reservations per policy, then run the certified GDPR erase. */
    private function eraseNow(Guest $guest): void
    {
        $live = $guest->reservations()
            ->whereNotIn('status', AssignmentService::TERMINAL_STATUSES)
            ->get();

        foreach ($live as $reservation) {
            try {
                $this->assignment->cancelReservation($reservation, 'Guest account deletion');
            } catch (Throwable) {
                // Already terminal / a race — never block the erase on one reservation.
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
            Mail::to($email)->send(new AccountDeletionScheduledMail($guest));
        } catch (Throwable $e) {
            Log::warning('Account-deletion email failed', ['error' => $e->getMessage()]);   // never the address (no PII)
        }
    }
}
