<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Owner;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Payouts\Iban;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Owner PAYOUT-METHOD write path (Phase 2 · Sub-gate 5) — the owner captures + manages the bank account(s) their
 * payouts should settle to. DATA CAPTURE only; nothing here moves money (the live rail is deferred).
 *
 * ⚠️ SELF-ONLY / OWNER-SCOPED. New methods are attached to the owner resolved by `user_id` ({@see Owner()}) —
 * there is no owner id in the request, so an owner can only ever add to their OWN account. Existing methods are
 * resolved through {@see PayoutMethod}'s owner scope ({@see setDefault()}/{@see remove()} use `findOrFail`), so a
 * cross-owner id simply doesn't resolve → 404 — an owner can never touch another owner's payout method.
 *
 * The full IBAN is ENCRYPTED at rest (model cast); only its last four + country are stored in the clear, for the
 * masked display. Audit records the country/last4 (non-sensitive), never the full IBAN (invariant #28).
 *
 * ⚠️ Every change ALSO writes a `owner.payout_method_changed` DOMAIN event (convention: a meaningful state change
 * writes BOTH an audit row AND a domain-events row). That domain event is what drives the owner's ALWAYS-ON
 * "Account & security" notification (Phase 3). The payload carries only WHICH change happened, never the IBAN.
 */
class OwnerPayoutMethodService
{
    public function __construct(
        private AuditEventRecorder $audit,
        private DomainEventRecorder $domainEvents,
    ) {}

    /** The authenticated user's OWN owner record (never a request id); non-linked → 404 (fail closed). */
    public function owner(User $user): Owner
    {
        return Owner::query()->where('user_id', $user->getKey())->firstOrFail();
    }

    /**
     * The owner's payout methods, default first — owner-scoped by BelongsToOwner.
     *
     * @return Collection<int, PayoutMethod>
     */
    public function methods(): Collection
    {
        return PayoutMethod::query()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Capture a new bank account for the authenticated owner. The IBAN is normalised + stored encrypted; its
     * country + last four are derived from the string for masked display. The owner's FIRST method becomes the
     * default automatically.
     *
     * @param  array{label?: ?string, account_holder: string, iban: string, bic?: ?string, currency: string}  $data
     */
    public function add(User $user, array $data): PayoutMethod
    {
        $owner = $this->owner($user);
        $iban = Iban::normalize($data['iban']);
        $isFirst = ! PayoutMethod::query()->where('owner_id', $owner->id)->exists();

        $method = PayoutMethod::create([
            'owner_id' => $owner->id,
            'label' => $data['label'] ?? null,
            'account_holder' => $data['account_holder'],
            'iban' => $iban,
            'iban_last4' => Iban::last4($iban),
            'iban_country' => Iban::country($iban),
            'bic' => $data['bic'] ?? null,
            'currency' => strtoupper($data['currency']),
            'is_default' => $isFirst,
        ]);

        $this->audit->record('owner.payout_method_added', $owner, null, ['country' => $method->iban_country, 'last4' => $method->iban_last4]);
        $this->domainEvents->record('owner.payout_method_changed', $owner, ['change' => 'added']);

        return $method;
    }

    /**
     * Make one of the owner's methods the default (unsetting the others). Resolved owner-scoped, so a
     * cross-owner id 404s.
     */
    public function setDefault(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $method = PayoutMethod::query()->findOrFail($id);   // owner-scoped → cross-owner 404

            PayoutMethod::query()->where('owner_id', $method->owner_id)->whereKeyNot($id)->update(['is_default' => false]);
            $method->update(['is_default' => true]);

            $this->audit->record('owner.payout_method_default_changed', $method->owner, null, ['last4' => $method->iban_last4]);
            $this->domainEvents->record('owner.payout_method_changed', $method->owner, ['change' => 'default_changed']);
        });
    }

    /**
     * Remove (soft-delete) one of the owner's methods. If it was the default, the most recent remaining method
     * is promoted so an owner is never left with methods but no default. Resolved owner-scoped (cross-owner 404).
     */
    public function remove(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $method = PayoutMethod::query()->findOrFail($id);   // owner-scoped → cross-owner 404
            $wasDefault = $method->is_default;
            $ownerId = (string) $method->owner_id;
            $owner = $method->owner;

            $method->delete();

            if ($wasDefault) {
                PayoutMethod::query()->where('owner_id', $ownerId)->orderByDesc('created_at')->first()?->update(['is_default' => true]);
            }

            $this->audit->record('owner.payout_method_removed', $owner, null, ['last4' => $method->iban_last4]);
            $this->domainEvents->record('owner.payout_method_changed', $owner, ['change' => 'removed']);
        });
    }
}
