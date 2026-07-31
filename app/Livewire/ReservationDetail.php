<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\AuditEvent;
use App\Models\Folio;
use App\Models\InventoryBlock;
use App\Models\Payment;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Models\User;
use App\Services\FolioService;
use App\Support\Folio\FolioTotals;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Reservation Detail — one reservation + its folio (design pass · Operator Screen 4). Styled
 * from the catalogued "Operator Reservation Detail" design onto the SAME operator design system as
 * Screens 1–3 (operator layout, <x-operator.shell>, FormatsOperatorData — reused, never redefined).
 * Presentation + the real folio money actions; NO change to FolioService, AssignmentService, the
 * append-only ledger, schema, or any guarantee — this component only READS.
 *
 * RMB rule: `{reservation}` is a plain string prop resolved in render() via a TENANT-SCOPED
 * findOrFail (after SetTenantForWebRequest) — a cross-org id 404s. Never a route-model-bound prop.
 *
 * THE FOLIO IS RENDERED AS THE REAL APPEND-ONLY LEDGER IT IS: every folio_item and every payment is
 * its own line, chronological — charges positive, payments negative, refunds their OWN offsetting
 * lines (a prior line is NEVER edited or struck; correct-by-reversal). Totals are derived from the
 * SAME rows and reconcile to the folio's cached figures to the cent. No line the ledger doesn't
 * contain is ever rendered (the owner-statement fake-fee precedent).
 *
 * TERMINAL STATES (Wave 2 · sub-gate 2). "Cancelled" and "No-show" are STATE VARIANTS of this screen,
 * not screens of their own — the design showed them as separate pages; they are one component in a
 * different real state. When `status` is terminal the screen adds a provenance panel built ONLY from
 * columns `terminate()` genuinely writes: `cancelled_at` (labelled per state — a no-show sets the SAME
 * shared column, so it reads "Marked no-show at", never "Cancelled at") and `cancellation_reason` (a
 * no-show HAS none — `markNoShow()` passes null — so the row is OMITTED, never filled with a dash or an
 * invented "guest never arrived"). The released-inventory claim is real and automatic: `terminate()` →
 * `releaseLine()` frees `sold`. Nothing else about the terminal state is asserted.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — guest name, stay summary, units, reservation status (real enum), and the folio ledger.
 *                Record payment + full refund are REAL and persist (append-only, via FolioService).
 *                CANCEL + MARK-NO-SHOW are REAL and persist through AssignmentService — the single
 *                inventory write path — via ReservationLifecycleController (POST → service → PRG).
 *                The status TIMELINE is the real `reservation_status_history` audit trail (actor
 *                included; "System" where `changed_by` is genuinely null, e.g. the OTA webhook).
 *                The cancellation POLICY is the real `property_policies.cancellation` JSON — this screen
 *                is its FIRST reader in the app. Rendered as CONFIGURED CONTEXT, never as a computed fee.
 *   • DERIVED  — charges / paid / balance totals, computed from the real ledger rows only. The lifecycle
 *                stage badge (ReservationStage — the shared resolver, so this screen can never disagree
 *                with the reservations list about the same booking).
 *   • DEFERRED — add charge, partial refund, void, capture-hold, modify / move room: shown real-looking
 *                but flagged "not yet available" — never a faked money move (move-room goes through the
 *                guarded AssignmentService; deferred like the rack drag). The cancellation FEE and the
 *                no-show FEE/WAIVER are disabled "Soon": no fee engine exists (locked decision 5 — it is
 *                a policy subsystem), so NO fee figure is ever rendered, computed or implied.
 *   • NO BACKEND — export / print: disabled "Soon". A deposit/pre-auth line shows ONLY when a real
 *                deposit_hold authorization exists — never a fabricated pre-auth.
 *   • Guest PII — name only; encrypted email/phone never rendered.
 *
 * NOT DONE, deliberately: the folio rails are NOT hidden on a terminal reservation, though the design
 * drops them behind a "Folio closed · read-only" lock. There is no folio close/void lifecycle — every folio
 * is `'open'` (Phase 0.2 REMOVED the never-written `'closed'`/`'void'` enum values rather than leave a lock
 * that could never engage). A cancelled booking can still genuinely need a refund settled. Revisit if
 * `FolioService::closeFolio()` is ever built — it would re-add `'closed'` WITH the guard that enforces it.
 */
#[Layout('components.layouts.operator')]
class ReservationDetail extends Component
{
    use FormatsOperatorData;

    public string $reservationId;

    public function mount(string $reservation): void
    {
        $this->reservationId = $reservation;
    }

    public function render(): View
    {
        $reservation = Reservation::query()
            ->with(['leadGuest', 'reservationUnits.roomType', 'reservationUnits.unit'])
            ->findOrFail($this->reservationId);   // tenant-scoped → cross-org 404

        $guest = $reservation->leadGuest;
        $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
        $status = (string) $reservation->status;
        $meta = $this->reservationStatusMeta($status);
        $currency = (string) ($reservation->currency ?? 'EUR');

        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();

        $folioModel = Folio::query()
            ->where('reservation_id', $reservation->id)
            ->with(['items', 'payments'])
            ->first();

        $folio = $folioModel !== null ? $this->buildFolio($folioModel, $currency) : null;

        // The transition allow-lists are the WRITE PATH's own (public constants on the controller that
        // performs the mutation), never a second copy: a button this screen offers is exactly a transition
        // ReservationLifecycleController will accept, and it cannot drift out of step with it.
        //
        // …AND the actor's PERMISSION (2026-07-17). Both routes are now gated on `can:reservations.cancel`,
        // which e.g. Housekeeping does not hold. A button is offered only when BOTH are true — the state
        // permits the transition AND this user is allowed to make it. Either alone would render a control
        // that 403s or gets refused, which is the "UI must not invite a rejection" failure (#27's principle,
        // applied to authorization rather than to money).
        $mayTerminate = auth()->user()?->can('reservations.cancel') ?? false;
        $canCancel = $mayTerminate && in_array($status, ReservationLifecycleController::CANCELLABLE, true);
        $canNoShow = $mayTerminate && in_array($status, ReservationLifecycleController::NO_SHOWABLE, true);

        // Batch C · GATE 2 — a room MOVE is offered only on a non-terminal reservation (moving a cancelled /
        // no-show / checked-out stay is nonsensical). The write goes through AssignmentService::assignUnit,
        // which re-checks availability under a lock — so the free-unit list here is a UX nicety, never the
        // authority. #104 GATE 0 — the move route is now gated `reservations.assign`; gate the CONTROL on the
        // SAME permission so the UI never invites a 403 (the reservation detail is reachable by roles without it).
        $canMove = ! in_array($status, ['cancelled', 'no_show', 'checked_out'], true)
            && (bool) auth()->user()?->can('reservations.assign');

        return view('livewire.reservation-detail', [
            'viewer' => $this->viewer(),
            'terminal' => $this->terminalState($reservation, $status, $meta),
            'timeline' => $this->timeline($reservation, $folioModel),
            // Cancellation policy is CONTEXT for a CANCELLATION — so it shows exactly where it is
            // meaningful: on a cancelled booking, or on one that can still be cancelled. Not on a
            // checked-out stay (noise), and deliberately NOT on a no-show: `property_policies.cancellation`
            // is the cancellation policy specifically, there is no no-show key in it, and captioning it at
            // a no-show would imply a no-show policy the property has not configured.
            'policy' => $canCancel || $status === 'cancelled' ? $this->cancellationPolicy($reservation) : null,
            'can_cancel' => $canCancel,
            'can_no_show' => $canNoShow,
            'reservation' => [
                'id' => $reservation->id,
                'reference' => (string) $reservation->reference,
                'source' => $this->sourceLabel((string) $reservation->source),
                'status' => $status,
                'status_label' => $meta['label'],
                'tone' => $meta['tone'],
                'check_in' => $checkIn->format('j M Y'),
                'check_out' => $checkOut->format('j M Y'),
                'nights' => (int) $checkIn->diffInDays($checkOut),
                'guests' => (int) $reservation->adults + (int) $reservation->children,
                'currency' => $currency,
                'guest' => $name !== '' ? $name : null,
                // #104 GATE 1c — the honest form of the wireframe's guest card on this operational surface (#28
                // keeps it name-only): a cross-link to the Guests CRM, where the full profile + contact live.
                'guest_id' => $reservation->lead_guest_id !== null ? (string) $reservation->lead_guest_id : null,
            ],
            'units' => $reservation->reservationUnits->map(fn (ReservationUnit $line): array => [
                'id' => (string) $line->id,
                'room_type' => (string) $line->roomType->name,
                'unit' => $line->unit?->code,
                'check_in' => CarbonImmutable::parse($line->check_in)->format('j M'),
                'check_out' => CarbonImmutable::parse($line->check_out)->format('j M'),
                'status' => (string) $line->status,
                'price' => $this->money((int) $line->price_minor, $currency),
                // Batch C — the SAME-room-type units free for this line's dates (the move picker). Only an
                // ACTIVE line on a non-terminal reservation can move.
                'move_targets' => ($canMove && $line->status === 'active') ? $this->freeSameTypeUnits($line) : [],
            ])->all(),
            'can_move' => $canMove,
            'charge_types' => [
                ['value' => 'add_on', 'label' => 'Service'],
                ['value' => 'product', 'label' => 'Product'],
                ['value' => 'fee', 'label' => 'Fee'],
                ['value' => 'manual', 'label' => 'Other'],
            ],
            // Batch C · GATE 3 — a per-render nonce for the payment form's idempotency: a double-click of the
            // SAME rendered form submits the SAME key → recorded ONCE; a genuine second payment is a new
            // render → a new key → recorded again. Deterministic per request (Str::uuid), never fabricated data.
            'payment_nonce' => (string) Str::uuid(),
            // The SAME per-render nonce discipline for the add-charge form (its own key so a payment + a charge from
            // the same page don't collide) — a duplicate add-charge submission records ONCE, a fresh render a new line.
            'charge_nonce' => (string) Str::uuid(),
            'folio' => $folio,
        ]);
    }

    /**
     * The SAME-room-type units free for this line's stay dates — the move picker's options (Batch C · GATE 2,
     * Q5: same-type only). A unit is offered when it is active, not the current unit, and has NO overlapping
     * active reservation_unit and NO overlapping inventory block over [check_in, check_out). This is only the
     * PICKER; {@see AssignmentService::assignUnit} re-checks under a `FOR UPDATE` lock on the write, so a race
     * can never let a move land on an occupied unit — the list is a convenience, the service is the guard.
     *
     * @return list<array{id: string, code: string}>
     */
    private function freeSameTypeUnits(ReservationUnit $line): array
    {
        $checkIn = CarbonImmutable::parse($line->check_in)->toDateString();
        $checkOut = CarbonImmutable::parse($line->check_out)->toDateString();

        return Unit::query()
            ->where('room_type_id', $line->room_type_id)
            ->where('id', '!=', $line->unit_id)
            ->where('is_active', '!=', false)
            ->orderBy('code')
            ->get()
            ->filter(function (Unit $unit) use ($line, $checkIn, $checkOut): bool {
                $occupied = ReservationUnit::query()
                    ->where('unit_id', $unit->id)
                    ->where('status', 'active')
                    ->where('id', '!=', $line->id)
                    ->whereDate('check_in', '<', $checkOut)
                    ->whereDate('check_out', '>', $checkIn)
                    ->exists();

                $blocked = InventoryBlock::query()
                    ->where('unit_id', $unit->id)
                    ->whereDate('start_date', '<', $checkOut)
                    ->whereDate('end_date', '>', $checkIn)
                    ->exists();

                return ! $occupied && ! $blocked;
            })
            ->map(fn (Unit $unit): array => ['id' => (string) $unit->id, 'code' => (string) $unit->code])
            ->values()
            ->all();
    }

    /**
     * The terminal-state provenance panel — ONLY for a genuinely terminal reservation, and built only
     * from what `AssignmentService::terminate()` actually writes.
     *
     * `cancelled_at` is a SHARED column: a no-show stamps it too. So it is labelled per state rather than
     * always "Cancelled at", which would misreport a no-show. `cancellation_reason` is rendered only when
     * genuinely set — `markNoShow()` stores none, so on a no-show the row is omitted entirely rather than
     * shown as "—" (a dash implies an empty field; the truth is there is no field).
     *
     * @param  array{label: string, tone: string}  $meta  the real status meta, already resolved by render()
     * @return array<string, mixed>|null
     */
    private function terminalState(Reservation $reservation, string $status, array $meta): ?array
    {
        if (! in_array($status, ['cancelled', 'no_show'], true)) {
            return null;
        }

        $isNoShow = $status === 'no_show';
        $reason = trim((string) ($reservation->cancellation_reason ?? ''));

        // Both the status enum and the shared stage resolver land on CANCELLED here — consistent by
        // design, and taken from the shared resolver so this screen can never disagree with the list.
        $stageMeta = ReservationStage::meta(ReservationStage::for($reservation));

        return [
            'label' => $meta['label'],
            'tone' => $meta['tone'],
            'stage_label' => $stageMeta['label'],
            'stage_tone' => $stageMeta['tone'],
            'at_label' => $isNoShow ? 'Marked no-show at' : 'Cancelled at',
            'at' => $reservation->cancelled_at !== null
                ? CarbonImmutable::parse($reservation->cancelled_at)->format('j M Y · H:i')
                : null,
            'reason' => $reason !== '' ? $reason : null,
            // The ONE consequence that is real and automatic — terminate() → releaseLine() → sold−−.
            'released' => $isNoShow
                ? 'Every held night was released when this booking was marked a no-show — the unit is back on the calendar and can be resold.'
                : 'Every held night was released when this booking was cancelled — the unit is back on the calendar and can be resold.',
            // DEFERRED (locked decision 5): no fee engine exists. A LABEL only — never a figure.
            'fee_label' => $isNoShow ? 'No-show fee · waiver' : 'Cancellation fee',
        ];
    }

    /**
     * The REAL transition trail from `reservation_status_history` — written by AssignmentService on
     * booking + terminate, and by the CheckIn/CheckOut actions. Every label runs through
     * `reservationStatusMeta()` for BOTH sides, so the vocabulary is the real enum end-to-end.
     *
     * A null `from_status` is the first row (the booking) — rendered as the destination alone, with no
     * arrow: there is no "New"/"Created" status in the enum and this screen will not invent one. A null
     * `changed_by` is genuinely system-written (the OTA webhook cancels with no authenticated user) —
     * "System" reports that truthfully.
     *
     * @return list<array<string, mixed>>
     */
    private function timeline(Reservation $reservation, ?Folio $folioModel): array
    {
        // #104 GATE 1c — the trail was STATUS-ONLY; the money actions were already in `audit_events` (subject =
        // the Payment) but this screen never read them. Now it merges BOTH into one real who-did-what-when trail:
        // lifecycle transitions (reservation_status_history) + the reservation's payment events (recorded /
        // settled / refunded). No PII — actions, actors, and times only (#28); amounts stay in the folio ledger.
        $entries = [];

        // 1) Lifecycle transitions — through their OWN tenant-scoped model (the statusHistory relation carries no
        //    generics, so rows reached through it degrade to bare Model). ULID breaks a same-second tie.
        $rows = ReservationStatusHistory::query()
            ->where('reservation_id', $reservation->id)
            ->with('changedBy')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        foreach ($rows as $row) {
            $to = $this->reservationStatusMeta((string) $row->to_status)['label'];
            $from = $row->from_status !== null ? $this->reservationStatusMeta((string) $row->from_status)['label'] : null;
            $actor = $row->changedBy;
            $entries[] = [
                'ts' => CarbonImmutable::parse($row->created_at),
                'sort' => (string) $row->id,
                'label' => $from !== null ? $from.' → '.$to : $to,
                'actor' => $actor instanceof User ? (string) $actor->name : 'System',
                'reason' => trim((string) ($row->reason ?? '')) ?: null,
                'is_money' => false,
            ];
        }

        // 2) Money actions on THIS reservation's payments — the real audit_events the status-only trail missed.
        $paymentIds = $folioModel?->payments->pluck('id')->all() ?? [];
        if ($paymentIds !== []) {
            $events = AuditEvent::query()
                ->where('subject_type', (new Payment)->getMorphClass())
                ->whereIn('subject_id', $paymentIds)
                ->whereIn('action', ['payment.recorded', 'payment.settled', 'payment.refunded'])
                ->orderBy('created_at')
                ->get();
            /** @var array<string, string> $names */
            $names = User::query()->whereIn('id', $events->pluck('actor_id')->filter()->all())->pluck('name', 'id')->all();
            foreach ($events as $event) {
                $label = match ((string) $event->action) {
                    'payment.recorded' => 'Payment recorded',
                    'payment.settled' => 'Payment settled',
                    'payment.refunded' => 'Refund issued',
                    default => ucfirst(str_replace('payment.', '', (string) $event->action)),
                };
                $actorId = $event->actor_id !== null ? (string) $event->actor_id : null;
                $entries[] = [
                    'ts' => CarbonImmutable::parse($event->created_at),
                    'sort' => (string) $event->getKey(),
                    'label' => $label,
                    'actor' => $actorId !== null ? ($names[$actorId] ?? 'A team member') : 'System',
                    'reason' => null,
                    'is_money' => true,
                ];
            }
        }

        // Merge oldest-first; a same-timestamp tie falls back to the monotonic sort key.
        usort($entries, fn (array $a, array $b): int => $a['ts']->eq($b['ts']) ? ($a['sort'] <=> $b['sort']) : ($a['ts']->lt($b['ts']) ? -1 : 1));

        return array_map(fn (array $e): array => [
            'label' => $e['label'],
            'at' => $e['ts']->format('j M Y · H:i'),
            'actor' => $e['actor'],
            'reason' => $e['reason'],
            'is_money' => $e['is_money'],
        ], $entries);
    }

    /**
     * The property's CONFIGURED cancellation policy — the real `property_policies.cancellation` JSON.
     * This screen is its first reader in the app (nothing else has ever read the column).
     *
     * ⚠️ THIS IS CONTEXT, NOT A FEE. No fee engine exists (locked decision 5), so nothing here is
     * computed against this stay: it reports what is on file, and the copy says so. The JSON has no
     * defined contract — only DemoSeeder writes it, as `{"type": "flexible", "free_until_days": 2}` — so
     * ONLY those two genuinely-present keys are rendered; an unknown key is not guessed at, and a missing
     * or empty policy returns null (the panel disappears rather than showing an empty shell).
     *
     * @return array<string, string>|null
     */
    private function cancellationPolicy(Reservation $reservation): ?array
    {
        // Read through PropertyPolicy's own tenant-scoped model, keyed by the reservation's real
        // `property_id` — same reason as timeline(): `Property::policies()` carries no generics, so
        // reaching the rows through the relation degrades them to bare Models.
        $policies = PropertyPolicy::query()
            ->where('property_id', $reservation->property_id)
            ->get();

        foreach ($policies as $row) {
            // Read through the Eloquent accessor: `cancellation` is a json column that PropertyPolicy
            // casts to `array`, but the static schema scanner sees only the raw json (string|null) and
            // does not apply the cast. getAttribute() applies the real cast at runtime and returns mixed,
            // so the guard below is a genuine check rather than a fight with the type. Same idiom as
            // FormatsOwnerData::portfolioCurrency() / OwnerExpenses::sourceType().
            $policy = $row->getAttribute('cancellation');

            if (! is_array($policy) || $policy === []) {
                continue;
            }

            $parts = [];

            $type = $policy['type'] ?? null;
            if (is_string($type) && trim($type) !== '') {
                // No policy-type vocabulary exists — title-case the stored value, never map it to an
                // invented enum. Same posture as sourceLabel()'s default arm.
                $parts[] = ucfirst(str_replace('_', ' ', trim($type)));
            }

            // array_key_exists, not isset/truthiness: `free_until_days: 0` is a real, meaningful value.
            if (array_key_exists('free_until_days', $policy) && is_numeric($policy['free_until_days'])) {
                $days = (int) $policy['free_until_days'];
                $parts[] = $days > 0
                    ? 'free until '.$days.' '.Str::plural('day', $days).' before arrival'
                    : 'free until the arrival day';
            }

            if ($parts === []) {
                continue;
            }

            return ['summary' => implode(' — ', $parts)];
        }

        return null;
    }

    /**
     * Assemble the append-only ledger + reconciled totals from the real folio rows.
     *
     * @return array<string, mixed>
     */
    private function buildFolio(Folio $folioModel, string $currency): array
    {
        /** @var list<array{ts: int, entry: array<string, mixed>}> $entries */
        $entries = [];

        foreach ($folioModel->items as $item) {
            $amount = (int) $item->amount_minor;
            $entries[] = [
                'ts' => CarbonImmutable::parse($item->posted_at ?? $item->created_at)->getTimestamp(),
                'entry' => [
                    'label' => $this->folioItemLabel((string) $item->type),
                    'desc' => (string) $item->description,
                    'date' => CarbonImmutable::parse($item->service_date ?? $item->posted_at ?? $item->created_at)->format('j M'),
                    'amount' => ($amount < 0 ? '' : '+').$this->money($amount, $currency),
                    'tone' => $amount < 0 ? 'refund' : 'charge',
                    'flag' => null,
                    'refundable' => false,
                    'payment_id' => null,
                ],
            ];
        }

        foreach ($folioModel->payments as $payment) {
            $entries[] = ['ts' => CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->getTimestamp(), 'entry' => $this->paymentEntry($payment, $currency)];
        }

        usort($entries, fn (array $a, array $b): int => $a['ts'] <=> $b['ts']);

        // Reconciliation figures come from the SHARED FolioTotals formula (same one the folio PDF uses).
        $totals = FolioTotals::for($folioModel);
        $balanceMinor = $totals['balance_minor'];

        return [
            'ledger' => array_map(fn (array $e): array => $e['entry'], $entries),
            'charges' => $this->money($totals['charges_minor'], $currency),
            'paid' => $this->money($totals['paid_minor'], $currency),
            'balance' => $this->money($balanceMinor, $currency),
            'balance_minor' => $balanceMinor,
            'has_balance' => $balanceMinor > 0,
            // Cross-check against the folio's own cached figures — the presentation reconciles to the
            // cent with FolioService's ledger recompute (they are computed from the same rows).
            'reconciles' => $balanceMinor === (int) $folioModel->balance_minor,
        ];
    }

    /**
     * One payment → a ledger line. Payments negative; refunds their own positive offsetting line;
     * holds/pending/failed shown honestly with no balance effect.
     *
     * @return array<string, mixed>
     */
    private function paymentEntry(Payment $payment, string $currency): array
    {
        $amount = (int) $payment->amount_minor;
        $type = (string) $payment->type;
        $status = (string) $payment->status;
        $date = CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->format('j M');

        // Settled inbound money — reduces the balance. Refundable ONLY while un-refunded headroom remains:
        // `refundable => true` for any settled payment is what kept the button on screen after a full refund
        // and made the cumulative over-refund reachable (docs/DECISIONS.md 2026-07-16). The service is still
        // the authority — this just stops the UI inviting a rejection.
        if (in_array($type, ['payment', 'capture'], true) && in_array($status, ['succeeded', 'captured'], true)) {
            $remaining = app(FolioService::class)->remainingRefundableMinor($payment);

            return [
                'label' => $type === 'capture' ? 'Capture' : 'Payment',
                'desc' => 'Received',
                'date' => $date,
                'amount' => '−'.$this->money($amount, $currency),
                'tone' => 'paid',
                'flag' => $remaining > 0 && $remaining < $amount ? 'Partly refunded' : null,
                'refundable' => $remaining > 0,
                'remaining_refundable_minor' => $remaining,
                'remaining_refundable' => $this->money($remaining, $currency),
                'payment_id' => $payment->id,
            ];
        }

        // Refund — its OWN offsetting line; the original payment line is untouched (correct-by-reversal).
        if ($type === 'refund' && $status === 'succeeded') {
            return ['label' => 'Refund', 'desc' => 'Reverses a payment', 'date' => $date, 'amount' => '+'.$this->money($amount, $currency), 'tone' => 'refund', 'flag' => null, 'refundable' => false, 'payment_id' => null];
        }

        // A real deposit/pre-authorization hold (shown only because the row genuinely exists).
        if ($type === 'deposit_hold' && $status === 'authorized') {
            return ['label' => 'Deposit hold', 'desc' => 'Pre-authorized', 'date' => $date, 'amount' => $this->money($amount, $currency), 'tone' => 'hold', 'flag' => 'Hold', 'refundable' => false, 'payment_id' => null];
        }

        // Pending (3DS/processing) or failed/voided — real rows, no balance effect, flagged honestly.
        $flag = in_array($status, ['pending'], true) ? 'Pending' : ucfirst($status);

        return ['label' => ucfirst($type === 'deposit_hold' ? 'Deposit hold' : $type), 'desc' => 'Not counted in the balance', 'date' => $date, 'amount' => $this->money($amount, $currency), 'tone' => 'muted', 'flag' => $flag, 'refundable' => false, 'payment_id' => null];
    }
}
