<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\Scopes\OwnerScope;
use App\Payouts\PayoutEngineMode;
use App\Payouts\PayoutSchedule;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerNextPayout;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Owner\PayoutRecovery;
use App\Support\Payouts\Iban;
use App\Support\Payouts\RailLabel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Payouts (design pass · Screen 3) — a NEW owner-scoped page for the certified Payout backend
 * (there was no dedicated route/component before). Styled from the catalogued "Owner Payouts" design,
 * wired to LIVE data via the read-only {@see OwnerPortalService} (called, never modified). Presentation
 * only: NO change to Payout logic, schema, or guarantees. Owner isolation
 * ({@see OwnerScope}) makes every read the owner's own rows; a non-linked user fails
 * closed. No route-model-bound props — all reads happen in render() under the active owner+tenant scope.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — payout history (amount / date / rail=manual→"Bank transfer" / status / linked statement),
 *                paid-this-year total, next/pending amounts, payout currency.
 *   • DERIVED  — paid-this-year = Σ REAL paid payouts (by paid_at year); next-payout breakdown = the
 *                source statement's REAL lines. No fabricated projections.
 *   • LIFECYCLE— rendered with the REAL states ONLY (statement Finalized → payout Pending → Paid, plus
 *                Failed/Reversed). ⚠️ STALE CLAIM CORRECTED (design pass · Owner-3): this said
 *                "Payout.status ∈ pending/paid/failed/reversed". It is now
 *                **pending | held | processing | paid | failed | reversed** — `processing` is written by
 *                the certified {@see OwnerPayoutService::pay()} on the live-transfer path, and `held` is
 *                the payout engine's persisted tax-hold (#71). The stepper below already handles both;
 *                only this comment lagged. ⚠️ The SAME omission is a real defect in {@see self::metrics()}
 *                — see the note there. The design's "Scheduled" state still does not exist and is never
 *                invented.
 *   • REAL     — the per-payout REMITTANCE ADVICE PDF (Wave 1 · Sub-gate 2): each row links to
 *                `owner.payouts.remittance`, an owner-scoped download off the real payout + its source
 *                statement's real lines. (Was "Receipt · Soon" — no longer deferred.)
 *   • REAL (rebuild + payout engine #71) — the tax-HOLD banner (the real tax-hold), the masked IBAN/payout-account
 *                card (CAP_PAYOUT #28), the real next-scheduled-payout DATE (PayoutSchedule::nextRunDate), the
 *                Export CSV, and the full payout-preference editor (frequency/day/minimum) — all real, never faked.
 *                TEST-MODE only (PayoutEngineMode) — no real money moves in the build. Micro-deposit bank
 *                VERIFICATION / live SEPA trace stay honestly account-gated (never a fake "Verified").
 */
#[Layout('components.layouts.owner')]
class OwnerPayouts extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $portal = app(OwnerPortalService::class);
        $payouts = $portal->payouts();          // owner-scoped, most recent first
        $statements = $portal->statements();     // for statement links + next-payout breakdown
        $currency = $this->portfolioCurrency($statements, $payouts);

        /** @var Collection<string, OwnerStatement> $byId */
        $byId = $statements->keyBy('id');
        $focus = $payouts->first(fn (Payout $p): bool => $p->status === 'pending') ?? $payouts->first();

        // ⚠️ WAVE 3 — the YEAR filter. `distinctYears()` already built a real list of the owner's payout
        // years and NOTHING rendered or used it, so the wireframe's filter did not exist. Rides a GET link
        // like every other owner filter (a wire-action would bypass the owner middleware, #20).
        $requestedYear = request()->query('year');
        $activeYear = is_string($requestedYear) && preg_match('/^\d{4}$/', $requestedYear) === 1
            ? (int) $requestedYear
            : null;

        $ctx = app(OwnerContext::class);
        $rail = OwnerNextPayout::rail($ctx);   // {iban_masked (CAP_PAYOUT only), paused (the real tax-hold)}

        return view('livewire.owner.payouts', [
            'viewer' => $this->viewer(),
            'currency' => $currency,
            'currencySymbol' => $this->currencySymbol($currency),
            // Only the HISTORY narrows: the KPI band and the next-payout card describe the owner's whole
            // position, and filtering them by a browsed year would make their labels false.
            'payouts' => $this->historyRows($this->applyYear($payouts, $activeYear), $byId),
            'activeYear' => $activeYear,
            'metrics' => $this->metrics($payouts, $currency),
            'nextPayout' => $this->nextPayoutCard($focus, $byId, $currency),
            'lifecycle' => $this->lifecycle($focus),
            'statuses' => $payouts->map(fn (Payout $p): string => (string) $p->status)->unique()->sort()->values()->all(),
            'years' => $this->distinctYears($payouts),
            // Exact-match additions — all REAL / honest (never a fabricated hold, IBAN, or schedule).
            'hold' => $this->holdBanner($rail, $focus),
            // DO3-3 — the failed-payout alert + its recovery route. Driven by the REAL `failed` status and
            // the owner's REAL current account state; no ETA is produced (the wireframe's "est. {time}" has
            // no source and stays refused, #39).
            'recovery' => $this->recoveryBanner($payouts, $ctx),
            'payoutAccount' => $this->payoutAccountCard($ctx, $rail),
            'preferences' => $this->preferences($ctx, $currency),
            'canManagePayout' => $ctx->can(OwnerTeamRole::CAP_PAYOUT),
            // The REAL automated schedule (the engine's next run date from the owner's own preferences) + the
            // TEST-MODE boundary — the "Upcoming schedule" placeholder becomes real, honestly labelled.
            'schedule' => $this->scheduleCard(),
            'testMode' => PayoutEngineMode::isTest(),
        ]);
    }

    /**
     * The REAL "next scheduled payout" — the payout engine's next run date for THIS owner, derived from their
     * own preferences via the shared, deterministic {@see PayoutSchedule} (never a fabricated date, #39). Also
     * reports whether the automated engine is switched on, so the copy is honest either way.
     *
     * @return array{engine_enabled: bool, frequency_label: string, next_date: string, paused: bool}
     */
    private function scheduleCard(): array
    {
        $prefs = OwnerPayoutPreference::resolve();   // owner-scoped (active on the /owner route)
        $next = PayoutSchedule::nextRunDate($prefs, CarbonImmutable::today());

        return [
            'engine_enabled' => (bool) config('services.payouts.engine_enabled', false),
            'frequency_label' => OwnerPayoutPreference::frequencyLabel((string) $prefs['frequency']),
            'next_date' => $next->format('j M Y'),
            'paused' => (bool) $prefs['paused'],
        ];
    }

    /**
     * The on-hold banner reflects the ONLY real hold: a PENDING payout held by the tax-hold (no tax details on
     * file). The wireframe's "verification pending" bank-verification hold is account-gated + NOT modelled, so it
     * is never faked — the banner shows only the real reason, or nothing. Null → no banner (no real hold).
     *
     * @param  array{iban_masked: string|null, paused: bool}  $rail
     * @return array<string, string>|null
     */
    private function holdBanner(array $rail, ?Payout $focus): ?array
    {
        if (! $focus instanceof Payout) {
            return null;
        }

        // The real tax-hold takes two shapes: the engine's PERSISTED `held` status, or (the manual path) a
        // `pending` payout the tax-hold pauses. Either → the same honest "held until tax details" banner.
        $held = $focus->status === 'held' || ($focus->status === 'pending' && $rail['paused']);
        if (! $held) {
            return null;
        }

        return [
            'amount_display' => $this->money((int) $focus->amount_minor, (string) $focus->currency),
            'reason' => 'We’re holding this payout until your tax details are on file.',
            'cta_label' => 'Add tax details',
            'cta_route' => route('owner.tax'),
        ];
    }

    /**
     * DO3-3 — the failed-payout alert. Shown ONLY for a genuinely `failed` payout; every other state
     * (pending / held / processing / paid / reversed) renders nothing here, because a recovery prompt on a
     * payout that is fine is worse than no prompt at all.
     *
     * ⚠️ WHAT THE OWNER IS OFFERED IS A RE-QUEUE, NOT A SEND. The Retry control posts to a controller route
     * that calls the certified {@see OwnerPayoutService::retry()}, which writes a new `pending` payout; the
     * disbursement itself stays operator-permissioned over the config-selected rail. The copy says so.
     *
     * ⚠️ THREE HONEST SUPPRESSIONS, each reading real state through the shared {@see PayoutRecovery}:
     *   • a live replacement already exists (queued / in flight / since paid) → say so, offer NO retry — a
     *     second live payout for one statement is a double payment;
     *   • the account would fail the same way again → show what to FIX, and route there, instead of a
     *     doomed re-send (fix-then-retry);
     *   • the viewer lacks CAP_PAYOUT → the alert still renders (they should know), the control does not.
     *
     * The stored `failure_reason` is rendered verbatim when present — it is real text written by the rail,
     * never a rewritten or invented explanation. No ETA, ever.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return array<string, mixed>|null
     */
    private function recoveryBanner(Collection $payouts, OwnerContext $ctx): ?array
    {
        $failed = $payouts->first(fn (Payout $p): bool => (string) $p->status === 'failed');

        if (! $failed instanceof Payout) {
            return null;
        }

        $owner = $failed->owner;
        $replacement = PayoutRecovery::liveReplacement($failed);
        $blockers = $owner instanceof Owner ? PayoutRecovery::blockers($owner) : [];
        $statement = $failed->statement;

        return [
            'amount_display' => $this->money((int) $failed->amount_minor, (string) $failed->currency),
            'period_label' => $statement instanceof OwnerStatement ? $statement->period_start->format('F Y') : null,
            // The same parse shape {@see self::payoutDate()} uses — `failed_at` reaches static analysis as
            // string|null (the cast is invisible to it), so an instanceof check here is always-false.
            'failed_on' => filled($failed->failed_at) ? Carbon::parse($failed->failed_at)->format('j M Y') : null,
            'reason' => filled($failed->failure_reason) ? (string) $failed->failure_reason : null,
            'already_queued' => $replacement !== null,
            'blockers' => $blockers,
            'can_act' => $ctx->can(OwnerTeamRole::CAP_PAYOUT),
            'can_retry' => $owner instanceof Owner && PayoutRecovery::isRetryable($failed, $owner),
            // DEC-n — a pause stops every payout, a retry included; the banner says so instead of offering a refused button
            'paused' => $owner instanceof Owner && OwnerPayoutPreference::isPaused((string) $owner->getKey()),
            'retry_route' => route('owner.payouts.retry', ['payout' => $failed->id]),
        ];
    }

    /**
     * The payout-account card — the owner's REAL default bank account. The IBAN is CO-OWNER ONLY (#28): a
     * non-payout role (accountant / view-only) sees a generic "Bank transfer" with NO account number. No method
     * yet → an honest "add an account" state (never a fabricated account).
     *
     * @param  array{iban_masked: string|null, paused: bool}  $rail
     * @return array<string, mixed>
     */
    private function payoutAccountCard(OwnerContext $ctx, array $rail): array
    {
        $canManage = $ctx->can(OwnerTeamRole::CAP_PAYOUT);
        $method = $canManage ? PayoutMethod::query()->orderByDesc('is_default')->first() : null;

        return [
            'can_manage' => $canManage,
            'has_method' => $method instanceof PayoutMethod,
            'label' => $method instanceof PayoutMethod
                ? (((string) $method->label) !== '' ? (string) $method->label : 'Bank account')
                : null,
            'iban_masked' => $rail['iban_masked'],   // masked or null, by capability (#28)
            'country' => $method instanceof PayoutMethod ? Iban::nameForCode((string) $method->iban_country) : null,
        ];
    }

    /**
     * The owner's REAL payout preferences (or the honest defaults when none is set yet — no row is written until
     * the owner saves). A PREFERENCE, not a schedule: the view labels it so and never promises a date (#39).
     *
     * @return array<string, mixed>
     */
    private function preferences(OwnerContext $ctx, string $currency): array
    {
        $data = OwnerPayoutPreference::resolve();   // owner-scoped; null → defaults (the ONE shared resolve)

        return [
            'can_manage' => $ctx->can(OwnerTeamRole::CAP_PAYOUT),
            'frequency' => $data['frequency'],
            'frequency_label' => OwnerPayoutPreference::frequencyLabel($data['frequency']),
            'payout_day' => $data['payout_day'],
            'minimum_minor' => $data['minimum_minor'],
            'minimum_display' => $data['minimum_minor'] > 0 ? $this->money($data['minimum_minor'], $currency) : 'None',
            'paused' => $data['paused'],
        ];
    }

    /**
     * @param  Collection<int, Payout>  $payouts
     * @param  Collection<string, OwnerStatement>  $byId
     * @return list<array<string, mixed>>
     */
    private function historyRows(Collection $payouts, Collection $byId): array
    {
        return $payouts->map(function (Payout $p) use ($byId): array {
            $statement = $byId->get((string) $p->owner_statement_id);
            $date = $this->payoutDate($p);

            return [
                'id' => $p->id,
                'amount_display' => $this->money((int) $p->amount_minor, (string) $p->currency),
                'rail_label' => $this->railLabel((string) $p->rail),
                'status' => (string) $p->status,
                'status_meta' => $this->payoutStatusMeta((string) $p->status),
                'date_display' => $date['display'],
                'month_label' => $statement instanceof OwnerStatement ? $statement->period_start->format('M Y') : '—',
                'year' => $date['year'],
                'statement_id' => $statement instanceof OwnerStatement ? $statement->id : null,
            ];
        })->values()->all();
    }

    /**
     * Paid-this-year (Σ real paid payouts in the most recent paid year) + next/pending totals. All real.
     *
     * ⚠️ SURFACED, NOT FIXED HERE (design pass · Owner-3 — this is a computation, and that pass is
     * design-only): these tiles count ONLY `paid` and `pending`. A payout in **`processing`** — the state
     * {@see OwnerPayoutService::pay()} writes the moment a real transfer is in flight — appears in
     * NEITHER, so once Stripe is live the owner's own money can be invisible on their payouts page while
     * it is actually moving. `held` is likewise absent from the tiles (it at least drives the on-hold
     * banner). This is the owner-side recurrence of the operator finding **D16-7**; it is logged in
     * `docs/design/MISSING-BACKEND-BY-PAGE.md` §O3 for the backend phase. Do not "fix" it by adding a
     * fourth tile in Blade — the buckets belong in one place, beside these two.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return array<string, mixed>
     */
    private function metrics(Collection $payouts, string $currency): array
    {
        $paid = $payouts->filter(fn (Payout $p): bool => $p->status === 'paid');
        $paidYear = $paid
            ->map(fn (Payout $p): ?int => $this->payoutDate($p)['year'])
            ->filter(fn (?int $y): bool => $y !== null)
            ->max();

        $paidThisYear = $paidYear === null ? 0 : (int) $paid
            ->filter(fn (Payout $p): bool => $this->payoutDate($p)['year'] === $paidYear)
            ->sum(fn (Payout $p): int => (int) $p->amount_minor);

        $pending = $payouts->filter(fn (Payout $p): bool => $p->status === 'pending');
        $nextPayout = $pending->first();

        return [
            'paid_year' => $paidYear,
            'paid_this_year_display' => $this->money($paidThisYear, $currency),
            'paid_count' => $paid->count(),
            'next_payout_display' => $this->money($nextPayout instanceof Payout ? (int) $nextPayout->amount_minor : 0, $currency),
            'pending_total_display' => $this->money((int) $pending->sum(fn (Payout $p): int => (int) $p->amount_minor), $currency),
            'pending_count' => $pending->count(),
        ];
    }

    /**
     * The focus payout (the pending one, else the most recent) + its source-statement breakdown of REAL
     * lines. No IBAN / estimated date — those have no backing field.
     *
     * @param  Collection<string, OwnerStatement>  $byId
     * @return array<string, mixed>|null
     */
    private function nextPayoutCard(?Payout $payout, Collection $byId, string $currency): ?array
    {
        if (! $payout instanceof Payout) {
            return null;
        }

        $statement = $byId->get((string) $payout->owner_statement_id);

        return [
            'amount_minor' => (int) $payout->amount_minor,
            'amount_display' => $this->money((int) $payout->amount_minor, (string) $payout->currency),
            'currency_symbol' => $this->currencySymbol((string) $payout->currency),
            'status_meta' => $this->payoutStatusMeta((string) $payout->status),
            'rail_label' => $this->railLabel((string) $payout->rail),
            'statement_id' => $statement instanceof OwnerStatement ? $statement->id : null,
            'period_label' => $statement instanceof OwnerStatement ? $statement->period_start->format('F Y') : null,
            'breakdown' => $statement instanceof OwnerStatement ? $this->breakdown($statement) : [],
        ];
    }

    /**
     * The source statement's REAL component lines (same discipline as the statement screen — renders the real
     * "Payment & ops fees" (#70) + "Withholding tax" (#73) lines when present, €0 → omitted, never faked #39).
     *
     * @return list<array{label: string, amount_display: string, is_negative: bool, is_net: bool}>
     */
    private function breakdown(OwnerStatement $statement): array
    {
        return OwnerStatementLine::query()
            ->where('owner_statement_id', $statement->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OwnerStatementLine $l): array => [
                'label' => (string) $l->description,
                'amount_display' => $this->money((int) $l->amount_minor, (string) $l->currency, signed: $l->line_type !== 'net'),
                'is_negative' => (int) $l->amount_minor < 0,
                'is_net' => $l->line_type === 'net',
            ])->values()->all();
    }

    /**
     * The payout lifecycle rendered with REAL states ONLY: a statement is Finalized, its payout is
     * Pending, then Paid (with Failed / Reversed terminal states). Payout.status has no "Scheduled" or
     * "On hold" — those are never invented here.
     *
     * @return array{steps: list<array{label: string, state: string}>, note: string|null}
     */
    private function lifecycle(?Payout $payout): array
    {
        $status = $payout instanceof Payout ? (string) $payout->status : null;

        $stateOf = function (string $step) use ($status): string {
            if ($status === null) {
                return $step === 'Finalized' ? 'active' : 'todo';
            }

            return match ($status) {
                'paid' => 'done',
                // A Stripe Connect transfer is initiated → awaiting the webhook confirmation.
                'processing' => $step === 'Paid' ? 'active' : 'done',
                // `held` (the engine tax-hold) reads as in-progress at Pending here; Phase 4 gives it a
                // distinct "On hold" visual. Grouped with pending so the stepper stays honest, never invented.
                'pending', 'held' => match ($step) {
                    'Finalized' => 'done', 'Pending' => 'active', default => 'todo'
                },
                'failed' => match ($step) {
                    'Finalized' => 'done', 'Pending' => 'failed', default => 'todo'
                },
                'reversed' => match ($step) {
                    'Paid' => 'reversed', default => 'done'
                },
                default => 'todo',   // total over string (status is cast to string) — never an UnhandledMatchError
            };
        };

        $steps = array_map(
            fn (string $label): array => ['label' => $label, 'state' => $stateOf($label)],
            ['Finalized', 'Pending', 'Paid'],
        );

        $note = match ($status) {
            // ⚠️ §N.8d — this read "it WILL be retried as a new payout", which nothing in the product did:
            // no scheduled job retries a failed payout, and until DO3-3 there was no control that could.
            // The sentence now states what is true — a retry is available and creates a new payout — and
            // promises no automatic one. Found beside the flagged item (§N.13.1), not by a failing test.
            'failed' => 'This payout wasn’t sent. Retrying queues a new payout; the failed attempt stays in your history.',
            'reversed' => 'This payout was reversed — the balance carries to your next statement.',
            default => null,
        };

        return ['steps' => $steps, 'note' => $note];
    }

    /**
     * Delegates to the SHARED rail map so this screen and the Remittance PDF that mirrors it can never
     * disagree about how a payout was sent (same discipline as MoneyFormatter — Wave 1 · Sub-gate 2).
     */
    private function railLabel(string $rail): string
    {
        return RailLabel::for($rail);
    }

    /**
     * The display date for a payout: paid_at (paid), failed_at (failed), else created_at; pending shows
     * "Pending".
     *
     * @return array{display: string, year: int|null}
     */
    private function payoutDate(Payout $p): array
    {
        $when = $p->paid_at ?? $p->failed_at ?? ($p->status === 'pending' ? null : $p->created_at);

        if ($when === null) {
            return ['display' => 'Pending', 'year' => null];
        }

        $c = Carbon::parse($when);

        return ['display' => $c->format('j M Y'), 'year' => (int) $c->format('Y')];
    }

    /**
     * @param  Collection<int, Payout>  $payouts
     * @return list<int>
     */
    /**
     * Narrow the HISTORY to one year. Null — or a malformed `?year=` — yields every payout, so a junk query
     * string widens rather than silently emptying the screen. A valid year the owner has no payouts in
     * legitimately shows none: that is a true empty, not a bug.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return Collection<int, Payout>
     */
    private function applyYear(Collection $payouts, ?int $year): Collection
    {
        if ($year === null) {
            return $payouts;
        }

        return $payouts->filter(fn (Payout $p): bool => $this->payoutDate($p)['year'] === $year)->values();
    }

    private function distinctYears(Collection $payouts): array
    {
        $years = [];
        foreach ($payouts as $p) {
            $year = $this->payoutDate($p)['year'];
            if ($year !== null) {
                $years[$year] = true;
            }
        }
        $keys = array_keys($years);
        rsort($keys);

        return $keys;
    }
}
