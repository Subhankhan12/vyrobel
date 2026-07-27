<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\PayoutMethod;
use App\Services\OwnerPayoutService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The ONE owner-scoped "Needs your attention" feed — REAL account-state alerts only, shared by the owner
 * My Units screen and the owner Overview so the two surfaces cannot drift on what needs the owner. Extracted
 * verbatim from the certified `OwnerMyUnits::attentionItems()` (Batch B · Q5) — the same discipline as
 * {@see OwnerOccupancy} / {@see OwnerLedgerAttribution}.
 *
 * The signals are all genuine, derived from the owner's OWN rows (resolved via {@see OwnerContext}, so a
 * non-linked user reads nothing — #28):
 *   • a GENUINE payout hold — finalized statements on file AND no tax details (the real
 *     {@see OwnerPayoutService} tax-hold);
 *   • a missing payout method — no {@see PayoutMethod} on record;
 *   • a management agreement genuinely renewing within 30 days (real `effective_to`).
 *
 * The prototype's fabricated "rate-change proposals" are NOT built (#39). No real item → an empty list → the
 * surfaces render an honest "caught up" state. A pure read: mutates nothing.
 */
final class OwnerAttention
{
    /**
     * The owner's real attention items, in a stable order (holds first, then renewals). Empty when the owner
     * context is inactive/unlinked, or when nothing needs them.
     *
     * @return list<array{tone: string, title: string, body: string, cta: array{label: string, route: string}}>
     */
    public static function items(OwnerContext $context): array
    {
        if (! $context->active() || $context->ownerIds() === []) {
            return [];
        }

        $items = [];
        $today = Carbon::today();

        foreach (Owner::query()->whereIn('id', $context->ownerIds())->get() as $owner) {
            $ownerId = (string) $owner->id;

            // A GENUINE payout hold: the owner has finalized statements but no tax details on file.
            $hasFinalized = OwnerStatement::query()->where('owner_id', $ownerId)->whereNotNull('finalized_at')->exists();
            if ($hasFinalized && ! $owner->hasTaxDetails()) {
                $items[] = [
                    'tone' => 'warn',
                    'title' => 'Add tax details to release payout',
                    'body' => 'Your payout is on hold until your tax details are on file.',
                    'cta' => ['label' => 'Add tax details', 'route' => route('owner.tax')],
                ];
            }

            // No payout method on record → we cannot send earnings.
            if (! PayoutMethod::query()->where('owner_id', $ownerId)->exists()) {
                $items[] = [
                    'tone' => 'warn',
                    'title' => 'Confirm your payout bank account',
                    'body' => 'Add a payout method so we can send your earnings.',
                    'cta' => ['label' => 'Payout method', 'route' => route('owner.payout-method')],
                ];
            }

            // A management agreement genuinely renewing within 30 days (real effective_to).
            $agreement = ManagementAgreement::query()
                ->where('owner_id', $ownerId)
                ->whereNotNull('effective_to')
                ->whereDate('effective_to', '>=', $today->toDateString())
                ->whereDate('effective_to', '<=', $today->copy()->addDays(30)->toDateString())
                ->orderBy('effective_to')
                ->first();
            if ($agreement instanceof ManagementAgreement && $agreement->effective_to !== null) {
                $days = (int) $today->diffInDays($agreement->effective_to);
                $items[] = [
                    'tone' => 'info',
                    'title' => 'Management agreement renews soon',
                    'body' => 'Your agreement ends in '.$days.' '.Str::plural('day', $days).' — review the terms.',
                    'cta' => ['label' => 'Review agreement', 'route' => route('owner.agreement')],
                ];
            }
        }

        return $items;
    }
}
