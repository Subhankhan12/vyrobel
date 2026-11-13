<?php

declare(strict_types=1);

namespace App\Support\Privacy;

use App\Models\PrivacyRequest;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;

/**
 * DEC-6 (his ruling, 2026-11-13: "C is fine right now") — a staff member or an owner can REQUEST that their own account
 * and data be deleted. ⚠️ A REQUEST, NOT A DELETE: it files a row on the Privacy desk's register — due in one month
 * (GDPR Art. 12(3), {@see PrivacyRequest::DUE_MONTHS}) — and a person answers it. Nothing is erased here, ever; an
 * instant self-delete waits for the lawyer's answer on how long financial records must be kept (`OPEN.md` F6), because
 * an owner's or a manager's name sits on money history that cannot simply vanish.
 *
 * ⚠️ NO FREE TEXT, NO COPIED CONTACT DETAILS: the register holds a user id and an organisation id, as it always has. The
 * audit row records the due date only.
 *
 * ONE definition, read by the staff Security page, the owner Security page and their two POST routes, so the screens
 * and the writes cannot disagree about whether a request is already open.
 */
final class AccountDeletionRequests
{
    /** The channel code for a request filed from inside the product (the others are email · letter · phone · hotel). */
    public const CHANNEL = 'in_app';

    /** The person's OPEN deletion request (received or in progress), if any — the newest. */
    public static function openFor(User $user): ?PrivacyRequest
    {
        return PrivacyRequest::query()
            ->where('subject_user_id', $user->getKey())
            ->where('kind', 'delete')
            ->whereIn('status', ['received', 'in_progress'])
            ->latest('received_at')
            ->first();
    }

    /**
     * File the request for THIS user — idempotent: a second ask while one is open returns the open one and files
     * nothing. Returns [the request, whether a new one was filed].
     *
     * @return array{0: PrivacyRequest, 1: bool}
     */
    public function file(User $user): array
    {
        $open = self::openFor($user);
        if ($open !== null) {
            return [$open, false];
        }

        $received = Carbon::now();
        $request = PrivacyRequest::query()->create([
            'kind' => 'delete',
            'subject_kind' => 'account_holder',
            'subject_user_id' => (string) $user->getKey(),
            'related_organization_id' => app(TenantContext::class)->id(),
            'channel' => self::CHANNEL,
            'status' => 'received',
            'received_at' => $received,
            'due_at' => $received->copy()->addMonthsNoOverflow(PrivacyRequest::DUE_MONTHS),
            'recorded_by' => (string) $user->getKey(),   // the person themself — not a platform admin
        ]);

        app(AuditEventRecorder::class)->record(
            'user.deletion_requested',
            $user,
            null,
            ['due' => $request->due_at->toDateString()],
            actorType: 'user',
            actorId: (string) $user->getKey(),
        );

        return [$request, true];
    }
}
