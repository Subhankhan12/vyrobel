<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\ManagementAgreement;
use App\Models\OwnerLedgerEntry;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerNextPayout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Balance (exact-match pass, invariant #59) — the owner's append-only `owner_ledger` presented as
 * the decoded "Owner Balance" wireframe's CLEARING view, wired to LIVE data. Presentation only: NO change
 * to the ledger/statement/payout services, schema, or guarantees. Owner isolation ({@see OwnerScope}) makes
 * every read the owner's own rows; a non-linked user fails closed. No route-model-bound props — all reads
 * happen in render() under the active owner+tenant scope.
 *
 * ⚠️ REAL MONEY. Honesty triage:
 *   • REAL clearing model — the reconciling identity `Available + On hold + In transit + Paid == Net
 *                 position`, per currency, to the cent (#17), derived entirely from the REAL ledger +
 *                 payouts + the REAL tax-hold:
 *                   – Available  = Σ signedMinor() over UNSTAMPED rows (statement_id === null) — cleared
 *                                  this period, flowing into the next payout.
 *                   – On hold    = Σ pending payouts WHEN the owner tax-hold is on (no tax ID on file).
 *                   – In transit = Σ pending payouts when NOT held (released) — €0 while held.
 *                   – Paid       = Σ payouts.status='paid'. ('pending'/'paid' are real Payout enum values.)
 *                 The per-row state chip takes the same real states (Available / Paid out / On hold /
 *                 In transit / Cleared). No fabricated "cleared" state — a row is Available until it is
 *                 stamped onto a finalised statement, then it takes its statement's real payout status.
 *   • DERIVED   — "Net position" = Σ signedMinor() over ALL rows, and the per-row RUNNING BALANCE = the
 *                 same cumulative sum taken chronologically. Both reconcile to the cent. Reversal rows are
 *                 ORDINARY rows in the sum (a reversed pair nets to 0) — never filtered out (#16).
 *   • CURRENCY  — owner_ledger carries the BOOKING currency, payouts the PAYOUT currency. Every total and
 *                 the running balance accumulate PER CURRENCY; nothing is summed or converted across
 *                 currencies (#17 — no silent FX). The masked IBAN is CAP_PAYOUT-only (#28).
 *   • REAL HOLD — the "Why is X held?" card + "Add tax details" render ONLY on a genuine tax-hold; the
 *                 auto-payout cadence is the owner's CURRENT ManagementAgreement.payout_schedule.
 *   • ACCOUNT-GATED — "Withdraw early" is instant money-movement; there is NO owner-initiated payout rail
 *                 (StripeConnectPayoutRail::send() throws; ManualPayoutRail is operator-run), so the button
 *                 is honestly DISABLED with a note that automated month-end payout is how funds move. NEVER
 *                 a fake withdrawal / transfer / "in transit" (#39). Export (CSV) is REAL, CAP_EXPORTS-gated.
 *
 * Honest compromises (documented rather than papered over):
 *   • owner_ledger.note holds an INTERNAL token ('ota_unverified', 'no_agreement') — not owner-facing copy
 *     — so it is not rendered; `description` already carries the human sentence and the Unverified badge
 *     carries the signal.
 *   • The cadence card renders only when the owner's current agreements agree on ONE payout_schedule. None
 *     current, or a genuinely ambiguous set, renders nothing rather than picking a value.
 */
#[Layout('components.layouts.owner')]
class OwnerBalance extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        // Chronological ASC — the running balance MUST accumulate oldest→newest; the table renders the
        // reverse (newest first). ULIDs are monotonic, so id is a stable same-instant tiebreaker.
        $ascending = OwnerLedgerEntry::query()
            ->orderBy('service_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $descending = $ascending->reverse()->values();
        $payouts = Payout::query()->orderByDesc('created_at')->get();

        $currency = $this->portfolioCurrency($descending, $payouts);

        $ctx = app(OwnerContext::class);
        $rail = OwnerNextPayout::rail($ctx);   // iban_masked (CAP_PAYOUT), paused (the REAL tax-hold)

        $net = $this->netPosition($descending, $currency);
        $paid = $this->payoutTotal($payouts->filter(fn (Payout $p): bool => $p->status === 'paid'), $currency);

        // The reconciling CLEARING model (exact-match). REAL states, derived from the real ledger + payouts + the
        // real tax-hold. Identity: Available + On hold + In transit + Paid == Net position, per currency, to the
        // cent (#17). NOTHING here is a money-movement — the withdraw stays account-gated (never faked, #39).
        //   • Available  = unstamped ledger net (cleared this period, not yet in a finalised statement).
        //   • On hold    = pending payouts held by the tax-hold (owner has no tax ID on file).
        //   • In transit = pending payouts NOT held (released, on their way per the schedule) — €0 while held.
        $pending = $payouts->filter(fn (Payout $p): bool => $p->status === 'pending');
        $unstamped = $descending->filter(fn (OwnerLedgerEntry $e): bool => $e->statement_id === null)->values();
        $available = $this->netPosition($unstamped, $currency);
        $onHold = $this->payoutTotal($rail['paused'] ? $pending : collect(), $currency);
        $inTransit = $this->payoutTotal($rail['paused'] ? collect() : $pending, $currency);

        // statement_id → its payout status, for the activity feed's per-row state chip. Only real pending/paid
        // payouts drive a chip (createFromStatement holds one such per statement); a statement whose only payout
        // failed falls through to "Cleared" (finalised, not yet successfully paid) — never a fabricated state.
        $payoutStatusByStatement = $payouts
            ->whereIn('status', ['pending', 'paid'])
            ->mapWithKeys(fn (Payout $p): array => [(string) $p->owner_statement_id => (string) $p->status])
            ->all();

        return view('livewire.owner.balance', [
            'viewer' => $this->viewer(),
            'available' => $available,
            'onHold' => $onHold,
            'inTransit' => $inTransit,
            'paid' => $paid,
            'net' => $net,
            // The held explainer + the masked IBAN (CAP_PAYOUT only, #28) — shown only when there is a REAL hold.
            'held' => $onHold['minor'] !== 0 ? ['display' => $onHold['display']] : null,
            'ibanMasked' => $rail['iban_masked'],
            'byCurrency' => $this->byCurrency($net['currencies'], $inTransit['currencies'], $paid['currencies']),
            'entries' => $this->activityRows($ascending, (bool) $rail['paused'], $payoutStatusByStatement),
            'schedule' => $this->payoutSchedule(),
        ]);
    }

    /**
     * The per-row balance state (the wireframe's chip), derived from the REAL entry: an unstamped row is still
     * Available (accruing to the next payout); a stamped row takes its statement's payout status — Paid out /
     * On hold (tax-held) / In transit (released) / Cleared (finalised, no payout yet). No fabricated state.
     *
     * @param  array<string, string>  $payoutStatusByStatement
     * @return array{label: string, tone: string}
     */
    private function rowState(OwnerLedgerEntry $entry, bool $paused, array $payoutStatusByStatement): array
    {
        if ($entry->statement_id === null) {
            return ['label' => 'Available', 'tone' => 'paid'];
        }

        $status = $payoutStatusByStatement[(string) $entry->statement_id] ?? null;

        return match ($status) {
            'paid' => ['label' => 'Paid out', 'tone' => 'paid'],
            'pending' => $paused ? ['label' => 'On hold', 'tone' => 'pending'] : ['label' => 'In transit', 'tone' => 'pending'],
            default => ['label' => 'Cleared', 'tone' => 'draft'],
        };
    }

    /**
     * Net position — Σ signedMinor() over EVERY one of the owner's ledger rows, per currency. There is
     * no balance column: this IS the balance, derived from the real rows and reconciling to the cent.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries  newest-first (fixes the primary currency)
     * @return array{currency: string, minor: int, display: string, count: int, multi: bool, currencies: array<string, int>}
     */
    private function netPosition(Collection $entries, string $fallbackCurrency): array
    {
        $byCurrency = [];

        foreach ($entries as $entry) {
            $entryCurrency = (string) $entry->currency;
            $byCurrency[$entryCurrency] = ($byCurrency[$entryCurrency] ?? 0) + $entry->signedMinor();
        }

        return $this->headline($byCurrency, $entries->count(), $fallbackCurrency);
    }

    /**
     * Σ amount_minor over a REAL payout set (already filtered to one real status), per currency.
     *
     * @param  Collection<int, Payout>  $payouts  newest-first (fixes the primary currency)
     * @return array{currency: string, minor: int, display: string, count: int, multi: bool, currencies: array<string, int>}
     */
    private function payoutTotal(Collection $payouts, string $fallbackCurrency): array
    {
        $byCurrency = [];

        foreach ($payouts as $payout) {
            $payoutCurrency = (string) $payout->currency;
            $byCurrency[$payoutCurrency] = ($byCurrency[$payoutCurrency] ?? 0) + (int) $payout->amount_minor;
        }

        return $this->headline($byCurrency, $payouts->count(), $fallbackCurrency);
    }

    /**
     * A headline figure = the PRIMARY currency's total (the newest row's currency, deterministic).
     * `currencies` carries every currency's own total so the view can render each separately — the
     * headline is never a cross-currency sum.
     *
     * @param  array<string, int>  $byCurrency
     * @return array{currency: string, minor: int, display: string, count: int, multi: bool, currencies: array<string, int>}
     */
    private function headline(array $byCurrency, int $count, string $fallbackCurrency): array
    {
        $currency = array_key_first($byCurrency) ?? $fallbackCurrency;
        $minor = $byCurrency[$currency] ?? 0;

        return [
            'currency' => $currency,
            'minor' => $minor,
            'display' => $this->money($minor, $currency),
            'count' => $count,
            'multi' => count($byCurrency) > 1,
            'currencies' => $byCurrency,
        ];
    }

    /**
     * Per-currency totals, rendered ONLY when the owner's rows span more than one currency (otherwise
     * the headline trio already says everything). A currency absent from a metric totals 0 — the sum
     * of an empty set, not a fabrication. Nothing is converted (invariant #17).
     *
     * @param  array<string, int>  $net
     * @param  array<string, int>  $transit
     * @param  array<string, int>  $paid
     * @return list<array{currency: string, net_display: string, transit_display: string, paid_display: string}>
     */
    private function byCurrency(array $net, array $transit, array $paid): array
    {
        /** @var list<string> $currencies */
        $currencies = array_keys($net + $transit + $paid);

        if (count($currencies) < 2) {
            return [];
        }

        sort($currencies);

        $rows = [];

        foreach ($currencies as $currency) {
            $rows[] = [
                'currency' => $currency,
                'net_display' => $this->money($net[$currency] ?? 0, $currency),
                'transit_display' => $this->money($transit[$currency] ?? 0, $currency),
                'paid_display' => $this->money($paid[$currency] ?? 0, $currency),
            ];
        }

        return $rows;
    }

    /**
     * The ledger rows + their RUNNING BALANCE. Built chronologically (so the cumulative sum is real),
     * then reversed for newest-first display. The running total is kept PER CURRENCY — a single
     * cross-currency running balance would be a silent FX sum.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $ascending  oldest-first
     * @param  array<string, string>  $payoutStatusByStatement
     * @return list<array<string, mixed>>
     */
    private function activityRows(Collection $ascending, bool $paused, array $payoutStatusByStatement): array
    {
        /** @var array<string, int> $running */
        $running = [];
        $rows = [];

        foreach ($ascending as $entry) {
            $currency = (string) $entry->currency;
            $signed = $entry->signedMinor();
            $running[$currency] = ($running[$currency] ?? 0) + $signed;

            $rows[] = [
                'id' => (string) $entry->id,
                'date' => $entry->service_date->format('j M Y'),
                'description' => (string) $entry->description,
                'type_label' => $this->entryTypeLabel((string) $entry->entry_type),
                'amount_display' => $this->money($signed, $currency, signed: true),
                'is_negative' => $signed < 0,
                'running_display' => $this->money($running[$currency], $currency),
                'currency' => $currency,
                'statement_id' => $entry->statement_id !== null ? (string) $entry->statement_id : null,
                'state' => $this->rowState($entry, $paused, $payoutStatusByStatement),
                'prior_period' => (bool) $entry->prior_period_adjustment,
                'unverified' => (bool) $entry->unverified,
            ];
        }

        return array_reverse($rows);
    }

    /**
     * The owner's CURRENT payout cadence, from the certified ManagementAgreement (reusing its
     * `effectiveOn` scope rather than re-deriving the effective-dating rule).
     *
     * SECURITY: ManagementAgreement does NOT carry BelongsToOwner, so there is no OwnerScope to lean
     * on — the owner boundary is applied EXPLICITLY here. Inactive context or an empty owner set →
     * render NOTHING (fail closed), never every owner's terms.
     *
     * @return array{label: string}|null
     */
    private function payoutSchedule(): ?array
    {
        $context = app(OwnerContext::class);

        if (! $context->active()) {
            return null; // no owner boundary in force → do not read owner terms at all
        }

        $ownerIds = $context->ownerIds();

        if ($ownerIds === []) {
            return null; // fail closed — a non-linked user sees nothing
        }

        $schedules = ManagementAgreement::query()
            ->whereIn('owner_id', $ownerIds)
            ->effectiveOn(now()->toDateString())
            ->pluck('payout_schedule')
            ->unique()
            ->values();

        if ($schedules->count() !== 1) {
            return null; // no current agreement, or an ambiguous set — never invent a schedule
        }

        return ['label' => $this->scheduleLabel((string) $schedules->first())];
    }

    /** payout_schedule enum ∈ monthly|quarterly. */
    private function scheduleLabel(string $schedule): string
    {
        return match ($schedule) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            default => ucfirst($schedule),
        };
    }

    /** entry_type enum → an owner-facing label. The 8 arms ARE the enum; nothing else is invented. */
    private function entryTypeLabel(string $type): string
    {
        return match ($type) {
            'room_revenue' => 'Room revenue',
            'commission' => 'Commission',
            'management_fee' => 'Management fee',
            'ota_cost' => 'OTA cost',
            'cleaning' => 'Cleaning',
            'addon' => 'Add-on',
            'expense' => 'Expense',
            'adjustment' => 'Adjustment',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
