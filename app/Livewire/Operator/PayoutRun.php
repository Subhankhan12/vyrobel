<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\PayoutRunActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\PayoutRun as PayoutRunModel;
use App\Models\Scopes\OwnerScope;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\PayoutEngineMode;
use App\Payouts\Rails\ManualPayoutRail;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\OwnerPayoutService;
use App\Support\Payouts\Iban;
use App\Support\Payouts\RailLabel;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Payout Run (Wave 1 · Sub-gate 4) — the operator's TENANT-scoped disbursement desk: which
 * finalized statements are genuinely payable, which transfers are in flight, and what has actually
 * settled. This component RENDERS ONLY; the two real money writes go POST → {@see PayoutRunActionController}
 * → the CERTIFIED {@see OwnerPayoutService} → PRG (invariant #20 — a scope-dependent write must never ride
 * a wire-action, which re-renders over /livewire/update and bypasses the route middleware).
 *
 * ⚠️ THE CATALOGUED DESIGN WAS NOT USABLE AS A SOURCE OF TRUTH and none of it was ported. It has zero
 * onClick handlers and zero checkboxes (every control is inert markup), and its numbers do not reconcile:
 * six rows summing to 66,000 plus "+6 more · €24,000" implies 90,000 while its header claims 89,990, and
 * it simultaneously offers "Send 11 transfers", "12 statements" and 6+6 rows. Its lifecycle vocabulary
 * ("Scheduled" / "In review" / "Sent" / "On hold") does not exist in either real enum. So EVERY figure and
 * EVERY state on this page is derived from real rows via the real services; only the visual language (stat
 * strip, transfers list, run summary, a right-hand rail) is shared with the rest of the operator system.
 *
 * ⚠️ TENANT-scoped, NOT owner-scoped — the inverse of the owner portal, and the isolation-critical fact.
 * `/app` does not run SetOwnerScopeForPortal, so {@see OwnerScope} is INACTIVE here. That is CORRECT for a
 * payout run: the operator pays EVERY owner they manage in one pass. So, unlike
 * {@see OperatorOwnerDetail}, this screen deliberately adds NO `->where('owner_id', …)`. What keeps it
 * safe is that OwnerStatement / Payout / Owner are all BelongsToOrganization: the global tenant scope plus
 * SetTenantForWebRequest means a foreign org's statement is not in the collection at all, and the
 * controller's scoped `findOrFail` 404s a foreign id posted directly at the write.
 *
 * READY-TO-PAY is DERIVED, and it mirrors {@see OwnerPayoutService::createFromStatement()} EXACTLY rather
 * than inventing a looser rule — a button that cannot do what it says is a lie:
 *   • `status='finalized'` AND `locked=true` — the service THROWS on a draft ("only from a finalized
 *     (locked) statement"), so a draft is never offered.
 *   • `net_payable_minor > 0` — the service returns NULL for a zero net (nothing owed) and for a negative
 *     one (carried forward), so neither is offered a Create button; the negative case gets the honest
 *     "Not payable" rail instead.
 *   • no pending|paid payout yet — the service is idempotent and hands back the existing row. Note a
 *     FAILED or REVERSED payout does NOT claim its statement: the service genuinely would create a new
 *     payout, so that statement correctly returns to the ready list (and its real failed/reversed row
 *     stays visible below with its real reason — nothing is quietly dropped).
 *
 * Honesty triage (invariant #19; standing order = OMIT AGGRESSIVELY):
 *   • REAL     — owner names; statement sequence_no, period, net_payable_minor; payout amount, rail via the
 *                SHARED {@see RailLabel} map, the real status enum, paid_at/failed_at, failure_reason,
 *                external_ref; the bound rail's own name(), read from the container (what the write will
 *                ACTUALLY use — not a config guess).
 *   • DERIVED  — the ready set (above); the stat strip (Σ ready / Σ pending / Σ paid + counts), computed
 *                PER CURRENCY and NEVER summed across currencies (invariant #17). An empty bucket renders
 *                "—", not a zero in a currency no row on this page justifies.
 *   • DEFERRED — Stripe Connect disbursement. {@see StripeConnectPayoutRail::send()} THROWS by design; it
 *                is stated as not connected and is never callable from here. If it were ever the bound
 *                rail the Pay controls disable themselves rather than offer a button that can only fail.
 *                {@see ManualPayoutRail} is the real, working rail — and it is described truthfully: it
 *                does not MOVE money, it RECORDS a bank transfer the operator already made offline.
 *   • OMITTED (no backend — absent, not greyed out):
 *       – "Export SEPA file" / "SEPA batch · 09:00" — no export exists and no rail makes a scheme claim.
 *         Omitted entirely rather than shown as a disabled "Soon": naming SEPA would itself be the lie.
 *       – IBAN / bank columns / "no verified IBAN" / "Request IBAN" — NO bank-detail column exists on any
 *         table. The design's whole "On hold · no verified IBAN" rail is unbacked; the honest rail in its
 *         place is "Not payable", which reports the REAL reason (a non-positive net).
 *       – "Carry forward" as a BUTTON — carry-forward is not an operator action. It happens INSIDE
 *         createFromStatement() for a negative net. It is explained as the policy it is, never offered.
 *       – "Select statements" checkboxes + "Send 11 transfers" — the design's constants are fiction; the
 *         batch controls here are honest all-or-nothing forms over the REAL ready/pending sets.
 *       – Every demo constant (STMT-2025-09-NN, 89,990, "11 transfers", "12 statements").
 *   • NOT WIRED — retry() / reverse() exist on the certified service but have no route in this gate, so no
 *                control claims them. A failed payout is reported, not silently retried.
 *
 * No route param → no public props → the Sub-gate 3 prop/view-data collision (a `$payout` prop shadowing a
 * `'payout' => [...]` view array) cannot arise here, and there is no wire-action to leak scope through.
 */
#[Layout('components.layouts.operator')]
class PayoutRun extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        // TENANT-scoped by BelongsToOrganization's global scope (see the class docblock): a foreign org's
        // rows are not in these collections at all. NO ->where('owner_id') — a payout run is every owner.
        /** @var Collection<int, OwnerStatement> $finalized */
        $finalized = OwnerStatement::query()
            ->with('owner')
            ->where('status', 'finalized')
            ->where('locked', true)
            ->orderBy('period_start')
            ->get();

        /** @var Collection<int, Payout> $payouts */
        $payouts = Payout::query()
            ->with(['owner', 'statement'])
            ->orderByDesc('created_at')
            ->get();

        // The service's OWN idempotency predicate, mirrored exactly — pending|paid claims a statement;
        // failed|reversed does NOT (createFromStatement would genuinely create a new payout).
        $claimed = $payouts
            ->whereIn('status', ['pending', 'paid'])
            ->pluck('owner_statement_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $unclaimed = $finalized->reject(
            fn (OwnerStatement $s): bool => in_array((string) $s->getKey(), $claimed, true)
        );

        $ready = $unclaimed->filter(fn (OwnerStatement $s): bool => (int) $s->net_payable_minor > 0)->values();
        $notPayable = $unclaimed->filter(fn (OwnerStatement $s): bool => (int) $s->net_payable_minor <= 0)->values();

        $pending = $payouts->where('status', 'pending')->values();
        $paid = $payouts->where('status', 'paid')->values();
        // paid|failed|reversed are ALL real enum values — a settled row is never hidden, so a failure or a
        // reversal is always visible with its real reason next to the statement it belongs to.
        $recent = $payouts->whereIn('status', ['paid', 'failed', 'reversed'])->values();
        $held = $payouts->where('status', 'held')->values();

        // #93 — masked IBAN per owner (CAP_PAYOUT only, last-4, never the full number). One batched read.
        $canPayout = (bool) auth()->user()?->can('payouts.manage');
        $ibanByOwner = [];
        if ($canPayout) {
            foreach (PayoutMethod::query()->orderByDesc('is_default')->get() as $m) {
                $oid = (string) $m->owner_id;
                if (! isset($ibanByOwner[$oid]) && (string) $m->iban_last4 !== '') {
                    $ibanByOwner[$oid] = Iban::masked((string) $m->iban_last4);
                }
            }
        }

        // The last persisted RUN (the real header/lock/status) + the certified engine MODE (test/live).
        $latestRun = PayoutRunModel::query()->latest('created_at')->first();

        // ON HOLD (honest): the certified tax-held payouts + owners with a positive-net ready statement but NO
        // payout method on file (can't be sent). "Request IBAN" is a real notification, not a fabricated verify.
        $ownerHasMethod = PayoutMethod::query()->pluck('owner_id')->unique()->flip();
        $noMethod = $ready->filter(fn (OwnerStatement $s): bool => $s->owner !== null && ! $ownerHasMethod->has($s->owner->getKey()))->values();

        return view('livewire.operator.payout-run', [
            'viewer' => $this->viewer(),
            'subtitle' => $this->subtitle($ready->count(), $pending->count()),
            'rail' => $this->rail(),
            'mode' => PayoutEngineMode::current(),
            'canPayout' => $canPayout,
            'latestRun' => $latestRun !== null ? [
                'status' => (string) $latestRun->status,
                'mode' => (string) $latestRun->mode,
                'created' => (int) $latestRun->payouts_created,
                'paid' => (int) $latestRun->payouts_paid,
                'held' => (int) $latestRun->payouts_held,
                'when' => $latestRun->created_at !== null ? CarbonImmutable::parse($latestRun->created_at)->diffForHumans() : '',
            ] : null,
            'heldRows' => $this->heldRows($held, $ibanByOwner),
            'noMethodRows' => $this->noMethodRows($noMethod),
            'ibanByOwner' => $ibanByOwner,
            'kpis' => [
                'ready' => [
                    'lines' => $this->moneyLines($this->statementAmounts($ready)),
                    'count' => $ready->count(),
                ],
                'pending' => [
                    'lines' => $this->moneyLines($this->payoutAmounts($pending)),
                    'count' => $pending->count(),
                ],
                'paid' => [
                    'lines' => $this->moneyLines($this->payoutAmounts($paid)),
                    'count' => $paid->count(),
                ],
            ],
            'ready' => $this->readyRows($ready),
            'pending' => $this->pendingRows($pending),
            'recent' => $this->recentRows($recent),
            'notPayable' => $this->notPayableRows($notPayable),
        ]);
    }

    // ── The bound rail — read from the CONTAINER, so the page describes what the write will really use ──

    /**
     * The rail the certified service is actually constructed with (`services.payouts.rail`, default
     * `manual`). `can_send` is the honest capability: {@see StripeConnectPayoutRail::send()} throws by
     * design, so when it is bound the Pay controls disable rather than offer a button that can only fail.
     *
     * @return array{name: string, label: string, can_send: bool}
     */
    private function rail(): array
    {
        $rail = app(PayoutRail::class);
        $name = $rail->name();

        return [
            'name' => $name,
            'label' => RailLabel::for($name),   // the SHARED map — this page and the remittance PDF agree
            'can_send' => $name !== 'stripe_connect',
        ];
    }

    /** DERIVED from the real counts — never a claimed total. */
    private function subtitle(int $ready, int $pending): string
    {
        return $ready.' '.Str::plural('statement', $ready).' ready · '
            .$pending.' '.Str::plural('transfer', $pending).' pending';
    }

    // ── Stat strip — DERIVED, PER CURRENCY (invariant #17) ───────────────────────────────────────

    /**
     * Σ a set of amounts PER CURRENCY, formatted. Currencies are NEVER added together (invariant #17):
     * each gets its own line. An EMPTY set returns an EMPTY list — the view renders "—", because a zero
     * has to be a zero of SOME currency and this page (unlike an owner's, which has a real
     * `payout_currency`) spans every owner in the tenant and has no row to justify a choice.
     *
     * @param  list<array{currency: string, minor: int}>  $amounts
     * @return list<string>
     */
    private function moneyLines(array $amounts): array
    {
        $totals = [];

        foreach ($amounts as $amount) {
            $totals[$amount['currency']] = ($totals[$amount['currency']] ?? 0) + $amount['minor'];
        }

        ksort($totals);

        $lines = [];

        foreach ($totals as $currency => $minor) {
            $lines[] = $this->money($minor, (string) $currency);
        }

        return $lines;
    }

    /**
     * `net_payable_minor` is denominated in the statement's payout `currency` (NOT `source_currency`,
     * which the breakdown figures use) — pairing the wrong one would mislabel real money.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array{currency: string, minor: int}>
     */
    private function statementAmounts(Collection $statements): array
    {
        return $statements->map(fn (OwnerStatement $s): array => [
            'currency' => (string) $s->currency,
            'minor' => (int) $s->net_payable_minor,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Payout>  $payouts
     * @return list<array{currency: string, minor: int}>
     */
    private function payoutAmounts(Collection $payouts): array
    {
        return $payouts->map(fn (Payout $p): array => [
            'currency' => (string) $p->currency,
            'minor' => (int) $p->amount_minor,
        ])->values()->all();
    }

    // ── Rows — REAL columns only ─────────────────────────────────────────────────────────────────

    /**
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function readyRows(Collection $statements): array
    {
        return $statements->map(fn (OwnerStatement $s): array => [
            'statement_id' => (string) $s->getKey(),
            'owner_id' => $this->ownerId($s->owner),
            'owner_name' => $this->ownerName($s->owner),
            'sequence_no' => $s->sequence_no !== null ? (int) $s->sequence_no : null,
            'period' => $this->period($s),
            'net' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'finalized' => $this->dateDisplay($s->finalized_at),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Payout>  $payouts
     * @return list<array<string, mixed>>
     */
    private function pendingRows(Collection $payouts): array
    {
        return $payouts->map(fn (Payout $p): array => [
            'payout_id' => (string) $p->getKey(),
            'owner_id' => $this->ownerId($p->owner),
            'owner_name' => $this->ownerName($p->owner),
            'amount' => $this->money((int) $p->amount_minor, (string) $p->currency),
            'method' => RailLabel::for((string) $p->rail),
            'sequence_no' => $this->statementNumber($p),
            'status_meta' => $this->payoutStatusMeta((string) $p->status),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Payout>  $payouts
     * @return list<array<string, mixed>>
     */
    private function recentRows(Collection $payouts): array
    {
        return $payouts->map(fn (Payout $p): array => [
            'owner_id' => $this->ownerId($p->owner),
            'owner_name' => $this->ownerName($p->owner),
            'amount' => $this->money((int) $p->amount_minor, (string) $p->currency),
            'method' => RailLabel::for((string) $p->rail),
            'sequence_no' => $this->statementNumber($p),
            'status_meta' => $this->payoutStatusMeta((string) $p->status),
            'paid_at' => $this->dateDisplay($p->paid_at),
            'failed_at' => $this->dateDisplay($p->failed_at),
            'failure_reason' => $this->nullableString($p->failure_reason),
            'external_ref' => $this->nullableString($p->external_ref),
        ])->values()->all();
    }

    /**
     * Finalized statements with a NON-POSITIVE net. This is the honest replacement for the design's "On
     * hold · no verified IBAN" rail: there is no bank-detail column anywhere in the schema, so nothing is
     * ever "on hold" awaiting one. What IS real is that a zero net owes nothing and a negative net is
     * never charged back to the owner — {@see OwnerPayoutService} carries it forward to offset their next
     * statement. That is reported as the policy it is; it is not a button.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function notPayableRows(Collection $statements): array
    {
        return $statements->map(fn (OwnerStatement $s): array => [
            'owner_id' => $this->ownerId($s->owner),
            'owner_name' => $this->ownerName($s->owner),
            'sequence_no' => $s->sequence_no !== null ? (int) $s->sequence_no : null,
            'period' => $this->period($s),
            'net' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
            'negative' => (int) $s->net_payable_minor < 0,
        ])->values()->all();
    }

    /**
     * ON HOLD — the certified `held` payouts (the real tax-hold). Masked IBAN if on file (CAP_PAYOUT), never full.
     *
     * @param  Collection<int, Payout>  $payouts
     * @param  array<string, string>  $ibanByOwner
     * @return list<array<string, mixed>>
     */
    private function heldRows(Collection $payouts, array $ibanByOwner): array
    {
        return $payouts->map(fn (Payout $p): array => [
            'owner_id' => $this->ownerId($p->owner),
            'owner_name' => $this->ownerName($p->owner),
            'amount' => $this->money((int) $p->amount_minor, (string) $p->currency),
            'iban' => $ibanByOwner[$this->ownerId($p->owner) ?? ''] ?? null,
            'reason' => 'Tax details pending — carried until cleared',
        ])->values()->all();
    }

    /**
     * READY statements whose owner has NO payout method on file — the honest "can't send yet" hold (the real
     * gate in place of the wireframe's unbacked "no verified IBAN"). "Request IBAN" prompts the owner.
     *
     * @param  Collection<int, OwnerStatement>  $statements
     * @return list<array<string, mixed>>
     */
    private function noMethodRows(Collection $statements): array
    {
        return $statements->map(fn (OwnerStatement $s): array => [
            'owner_id' => $this->ownerId($s->owner),
            'owner_name' => $this->ownerName($s->owner),
            'amount' => $this->money((int) $s->net_payable_minor, (string) $s->currency),
        ])->values()->all();
    }

    // ── Real enums / real columns → display ──────────────────────────────────────────────────────

    /**
     * `payouts.status` — pending|processing|paid|failed|reversed|held. The design's "Scheduled" /
     * "In review" / "Sent" are not states this column can hold and are never rendered. An unexpected value
     * is echoed, never re-badged as something friendlier.
     *
     * ⚠️ THIS COMMENT WAS STALE IN A WAY WORTH RECORDING. It said the enum was "EXACTLY
     * pending|paid|failed|reversed" and listed **"On hold"** among the design terms the column *cannot*
     * hold. The payout engine then added `held` — so the design's word was right all along and the code
     * caught up without the comment doing so. `held`/`processing` reach the `default` arm and echo
     * accurately ("Held" / "Processing"), so the behaviour here was never wrong; the owner realm's copy
     * badged both as "Payout pending", which was (fixed in `FormatsOwnerData::payoutStatusMeta()`, where
     * the owner-facing labels are "On hold" and "In transit" — the terms Balance already used).
     *
     * ⚠️ Not unified with the owner trait: the two realms' tone vocabularies do not overlap (see the same
     * note on `OperatorOwnerDetail::payoutStatusMeta()`). This copy and that one ARE identical to each
     * other — a real, narrower de-dup, logged rather than done in a correctness-only pass.
     *
     * @return array{label: string, tone: string}
     */
    private function payoutStatusMeta(string $status): array
    {
        return match ($status) {
            'pending' => ['label' => 'Pending', 'tone' => 'pending'],
            'paid' => ['label' => 'Paid', 'tone' => 'confirmed'],
            'failed' => ['label' => 'Failed', 'tone' => 'no-show'],
            'reversed' => ['label' => 'Reversed', 'tone' => 'cancelled'],
            default => ['label' => ucfirst($status), 'tone' => 'departed'],
        };
    }

    private function period(OwnerStatement $s): string
    {
        return $s->period_start->format('j M Y').' – '.$s->period_end->format('j M Y');
    }

    /** The payout's source statement number. Null `sequence_no` renders "—", never a made-up ref. */
    private function statementNumber(Payout $p): ?int
    {
        $statement = $p->statement;

        return $statement instanceof OwnerStatement && $statement->sequence_no !== null
            ? (int) $statement->sequence_no
            : null;
    }

    /** The owner's REAL name. A missing relation renders "—" rather than an invented placeholder. */
    private function ownerName(?Owner $owner): string
    {
        $name = $owner instanceof Owner ? trim((string) $owner->name) : '';

        return $name !== '' ? $name : '—';
    }

    /** Null → the row renders the name as plain text: a link that cannot resolve is worse than no link. */
    private function ownerId(?Owner $owner): ?string
    {
        return $owner instanceof Owner ? (string) $owner->getKey() : null;
    }

    private function dateDisplay(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? Carbon::instance($value)->format('j M Y') : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
