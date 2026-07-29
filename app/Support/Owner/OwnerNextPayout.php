<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\Owner;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\Scopes\OwnerScope;
use App\Services\OwnerPayoutService;
use App\Support\Payouts\Iban;

/**
 * THE ONE owner "next payout" rail derivation — the masked payout IBAN + the tax-hold PAUSED state — shared by the
 * owner Overview (the dark next-payout card) and the owner Statement page (the dark rail), so the two surfaces cannot
 * disagree on the single "where does my money go / why is it held" answer. Same anti-drift discipline as
 * {@see OwnerLedgerAttribution} / {@see OwnerOccupancy}.
 *
 * 🔒 Two guards live here, not in the callers:
 *   • the masked IBAN is returned ONLY to a payout-capable role (CAP_PAYOUT = co-owner) — an accountant / view-only
 *     sub-user gets null (the account number is the most sensitive owner field, #28);
 *   • "paused" is the ONE real hold: a PENDING payout AND an owner with no tax details on file
 *     (the {@see OwnerPayoutService} tax-hold). There is NO estimated-payout-DATE field — never faked (#39).
 *
 * Pure read: the {@see PayoutMethod} / {@see Payout} / {@see Owner} queries are owner-scoped by the active
 * {@see OwnerScope} on the `/owner` routes; a non-linked user fails closed (empty scope → null/false).
 */
final class OwnerNextPayout
{
    /**
     * @return array{iban_masked: string|null, paused: bool}
     */
    public static function rail(OwnerContext $context): array
    {
        // 🔒 P1-1: the masked IBAN is the most sensitive owner field — return it ONLY for a payout method whose
        // OWNER the acting user has CAP_PAYOUT on (per-owner). A co-owner of A + view-only of B never sees B's
        // IBAN even though the union role once said "co_owner". An empty capable set → no method → null.
        $payoutOwnerIds = $context->ownerIdsWithCapability(OwnerTeamRole::CAP_PAYOUT);
        $method = $payoutOwnerIds === []
            ? null
            : PayoutMethod::query()->whereIn('owner_id', $payoutOwnerIds)->orderByDesc('is_default')->first();
        // Guard the last4 (a method persisted without it would otherwise mask to an empty "···· ").
        $ibanMasked = ($method instanceof PayoutMethod && filled($method->iban_last4))
            ? Iban::masked((string) $method->iban_last4)
            : null;

        $paused = false;
        if ($context->active() && $context->ownerIds() !== []) {
            $hasPending = Payout::query()->where('status', 'pending')->exists();
            $missingTax = Owner::query()->whereIn('id', $context->ownerIds())->get()
                ->contains(fn (Owner $o): bool => ! $o->hasTaxDetails());
            $paused = $hasPending && $missingTax;
        }

        return ['iban_masked' => $ibanMasked, 'paused' => $paused];
    }
}
