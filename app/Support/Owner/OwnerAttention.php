<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerAttentionState;
use App\Models\OwnerStatement;
use App\Models\PayoutMethod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The ONE owner-scoped "Needs your attention" feed — REAL account-state alerts only, shared by the owner
 * My Units screen and the owner Overview so the two surfaces cannot drift on what needs the owner.
 *
 * Wave A · GATE 1 added REAL per-signal STATE (snooze / dismiss / read) via {@see OwnerAttentionState}: each
 * signal carries a stable `key`, so the owner can snooze it to a date or dismiss it — and BOTH surfaces respect
 * it (one store, two surfaces — anti-drift). {@see items()} returns the VISIBLE signals (dismissed + currently-
 * snoozed hidden); {@see snoozed()} returns the snoozed ones (for the Snoozed panel); {@see signalFor()} lets a
 * controller resolve a posted key back to a REAL current signal (so you can only ever act on a genuine one).
 *
 * The signals are all genuine, derived from the owner's OWN rows (resolved via {@see OwnerContext}, so a
 * non-linked user reads nothing — #28): a GENUINE payout tax-hold; a missing {@see PayoutMethod}; an agreement
 * renewing within 30 days. The prototype's fabricated "rate-change proposals" are NOT built (#39). No visible
 * item → an empty list → an honest "caught up" state. items()/snoozed()/signalFor() mutate nothing.
 */
final class OwnerAttention
{
    /**
     * The VISIBLE attention items — real signals the owner has not dismissed or snoozed past. Each carries its
     * stable `key` + a `read` flag; ordered holds first, then renewals.
     *
     * @return list<array{key: string, tone: string, title: string, body: string, read: bool, cta: array{label: string, route: string}}>
     */
    public static function items(OwnerContext $context): array
    {
        $states = self::states($context);

        $out = [];
        foreach (self::rawSignals($context) as $s) {
            $state = $states[$s['owner_id'].'|'.$s['key']] ?? null;
            if ($state !== null && $state->isHidden()) {
                continue;   // dismissed, or snoozed to a future moment
            }
            $out[] = [
                'key' => $s['key'],
                'tone' => $s['tone'],
                'title' => $s['title'],
                'body' => $s['body'],
                'read' => $state?->read_at !== null,
                'cta' => $s['cta'],
            ];
        }

        return $out;
    }

    /**
     * The currently-SNOOZED items (real signals hidden until a future date) — for the Snoozed panel + Undo.
     *
     * @return list<array{key: string, title: string, body: string, until: string, cta: array{label: string, route: string}}>
     */
    public static function snoozed(OwnerContext $context): array
    {
        $states = self::states($context);

        $out = [];
        foreach (self::rawSignals($context) as $s) {
            $state = $states[$s['owner_id'].'|'.$s['key']] ?? null;
            if ($state === null || $state->dismissed_at !== null || $state->snoozed_until === null || ! $state->snoozed_until->isFuture()) {
                continue;
            }
            $out[] = [
                'key' => $s['key'],
                'title' => $s['title'],
                'body' => $s['body'],
                'until' => $state->snoozed_until->isToday() ? 'today' : $state->snoozed_until->diffForHumans(),
                'cta' => $s['cta'],
            ];
        }

        return $out;
    }

    /**
     * Resolve a posted key back to a REAL current signal (so a controller can only act on a genuine one, and
     * gets the owner_id to scope the state row). Returns the signal or null when the key is not currently raised.
     *
     * @return array{owner_id: string, key: string}|null
     */
    public static function signalFor(OwnerContext $context, string $key): ?array
    {
        foreach (self::rawSignals($context) as $s) {
            if ($s['key'] === $key) {
                return ['owner_id' => $s['owner_id'], 'key' => $s['key']];
            }
        }

        return null;
    }

    /**
     * ALL currently-raised signals as {owner_id, key} pairs — for BULK actions (mark-all-read / snooze-all).
     * The controller applies a state to each, so a bulk action can only ever touch genuine current signals.
     *
     * @return list<array{owner_id: string, key: string}>
     */
    public static function currentSignals(OwnerContext $context): array
    {
        return array_map(
            fn (array $s): array => ['owner_id' => $s['owner_id'], 'key' => $s['key']],
            self::rawSignals($context),
        );
    }

    /**
     * The raw, unfiltered signals — the genuine account-state alerts, each with its owner_id + stable key.
     * The ONE place the signal set is defined (items/snoozed/signalFor all read it).
     *
     * @return list<array{owner_id: string, key: string, tone: string, title: string, body: string, cta: array{label: string, route: string}}>
     */
    private static function rawSignals(OwnerContext $context): array
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
                    'owner_id' => $ownerId, 'key' => 'tax_hold', 'tone' => 'warn',
                    'title' => 'Add tax details to release payout',
                    'body' => 'Your payout is on hold until your tax details are on file.',
                    'cta' => ['label' => 'Add tax details', 'route' => route('owner.tax')],
                ];
            }

            // No payout method on record → we cannot send earnings.
            if (! PayoutMethod::query()->where('owner_id', $ownerId)->exists()) {
                $items[] = [
                    'owner_id' => $ownerId, 'key' => 'payout_method', 'tone' => 'warn',
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
                    'owner_id' => $ownerId, 'key' => 'agreement_renewal:'.$agreement->id, 'tone' => 'info',
                    'title' => 'Management agreement renews soon',
                    'body' => 'Your agreement ends in '.$days.' '.Str::plural('day', $days).' — review the terms.',
                    'cta' => ['label' => 'Review agreement', 'route' => route('owner.agreement')],
                ];
            }
        }

        return $items;
    }

    /**
     * The owner's attention-state rows, keyed by "ownerId|attention_key" for O(1) lookup.
     *
     * @return array<string, OwnerAttentionState>
     */
    private static function states(OwnerContext $context): array
    {
        $ownerIds = $context->ownerIds();
        if ($ownerIds === []) {
            return [];
        }

        $out = [];
        foreach (OwnerAttentionState::query()->whereIn('owner_id', $ownerIds)->get() as $state) {
            $out[$state->owner_id.'|'.$state->attention_key] = $state;
        }

        return $out;
    }
}
