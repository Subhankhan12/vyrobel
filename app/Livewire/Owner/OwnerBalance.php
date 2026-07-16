<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\ManagementAgreement;
use App\Models\OwnerLedgerEntry;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Support\Owner\OwnerContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Balance (Wave 1 · Sub-gate 1) — the owner's append-only `owner_ledger` as a running-balance
 * view, styled from the catalogued "Owner Balance" design and wired to LIVE data. Presentation only:
 * NO change to the ledger/statement/payout services, schema, or guarantees. Owner isolation
 * ({@see OwnerScope}) makes every read the owner's own rows; a non-linked user fails closed. No
 * route-model-bound props — all reads happen in render() under the active owner+tenant scope.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL      — the ledger rows themselves (service_date, description, entry_type, direction +
 *                 amount_minor, currency, statement_id link, prior_period_adjustment, unverified);
 *                 "In transit" = Σ payouts.status='pending'; "Paid to date" = Σ payouts.status='paid'
 *                 ('pending'/'paid' are real Payout enum values); the auto-payout cadence, read from
 *                 the owner's CURRENT ManagementAgreement.payout_schedule.
 *   • DERIVED   — "Net position" = Σ OwnerLedgerEntry::signedMinor() over ALL the owner's rows, and
 *                 the per-row RUNNING BALANCE = the same cumulative sum taken in chronological order.
 *                 Both reconcile to the cent against the source rows. Reversal rows are ORDINARY rows
 *                 in the sum (a reversed pair nets to 0) — never filtered out, per invariant #16.
 *   • CURRENCY  — owner_ledger carries the BOOKING currency, payouts the PAYOUT currency. Totals and
 *                 the running balance accumulate PER CURRENCY and are rendered per currency; nothing
 *                 is ever summed or converted across currencies (invariant #17 — no silent FX).
 *   • NO BACKEND— "Withdraw early": there is NO owner-initiated payout rail (StripeConnectPayoutRail
 *                 ::send() throws; ManualPayoutRail is operator-run), so the control is a disabled
 *                 "Soon" + an honest note. NO IBAN, NO ETA, NO estimated arrival date — those have no
 *                 backing field. Export (CSV) is the standing deferral → disabled "Soon".
 *   • OMITTED   — the design's "Available / On hold / In transit" CLEARING state machine, the tax-ID
 *                 hold explainer, "Add tax details" and "How money clears": no such concept exists in
 *                 the backend (a ledger row is posted, not "cleared"), so rendering them would imply
 *                 state that does not exist. They are dropped, not stubbed.
 *
 * Honest compromises (documented rather than papered over):
 *   • owner_ledger.note holds an INTERNAL token ('ota_unverified', 'no_agreement') — not owner-facing
 *     copy — so it is not rendered; `description` already carries the human sentence and the
 *     Unverified badge carries the signal.
 *   • The cadence card renders only when the owner's current agreements agree on ONE payout_schedule.
 *     None current, or a genuinely ambiguous set, renders nothing rather than picking a value.
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

        $net = $this->netPosition($descending, $currency);
        $inTransit = $this->payoutTotal($payouts->filter(fn (Payout $p): bool => $p->status === 'pending'), $currency);
        $paid = $this->payoutTotal($payouts->filter(fn (Payout $p): bool => $p->status === 'paid'), $currency);

        return view('livewire.owner.balance', [
            'viewer' => $this->viewer(),
            'net' => $net,
            'inTransit' => $inTransit,
            'paid' => $paid,
            'byCurrency' => $this->byCurrency($net['currencies'], $inTransit['currencies'], $paid['currencies']),
            'entries' => $this->activityRows($ascending),
            'schedule' => $this->payoutSchedule(),
        ]);
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
     * @return list<array<string, mixed>>
     */
    private function activityRows(Collection $ascending): array
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
