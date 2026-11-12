<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Guest;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\OwnerPortalService;
use App\Support\I18n\ListJoin;
use App\Support\I18n\LocalDate;
use App\Support\I18n\LocalText;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Reservations\ReservationStage;
use App\Support\StayTime;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Booking Detail (Phase 2 · Sub-gate 1 → exact-match rebuild #78) — ONE reservation on the owner's unit:
 * its facts, the gross→net earnings breakdown, "Net to you", its timeline and payout status. Matched to the
 * decoded "Owner Booking Detail" wireframe, wired to LIVE owner-scoped data. Read-only page (the only write is
 * the confirmation-PDF download, its own gated controller route).
 *
 * ⚠️ THE CRITICAL GUARD — a `{reservation}` id is a REQUEST input, and reservations are NOT owner-scoped
 * (owner A and owner B share the operator's ORG, so tenant scope does not separate them). So resolving the
 * reservation is not enough: {@see render()} REJECTS with 404 unless the reservation touches a unit THIS owner
 * actually owns — the owned-unit set comes from the owner-scoped {@see OwnerPortalService::units()}, never from
 * the request. Without this check, owner A could read owner B's booking by guessing an id (the #26 shape).
 * OwnerBookingsTest proves the rejection. The id is a plain STRING prop resolved in render() (never a
 * route-model-bound typed prop, which would resolve before the scope middleware).
 *
 * ⚠️ #28 — GUEST CONTACT IS REFUSED. The wireframe exposes the guest's email + phone + country; the entire
 * owner realm shows guest NAME only (never contact — on pages, PDFs, CSVs, the API and Messages). An owner
 * seeing a guest's email/phone is a privacy breach AND a platform-bypass/disintermediation risk. So this page
 * renders NAME + a derived owner-scoped "New/Repeat guest" tag ONLY; email/phone/country are never sent to the
 * view, and "Message {guest}" routes to the OPERATOR relay ({@see OwnerMessages}, owner↔
 * operator staff) — NEVER owner→guest. OwnerBookingsTest (“renders the guest name but never their contact details”) covers this URL
 * as well as the list, and is mutation-proven: leaking the email onto this page fails it.
 *
 * Earnings come through the ONE shared {@see OwnerLedgerAttribution::breakdown()} rule (so "Net to you" matches
 * the Bookings list, Unit Detail and the statement to the cent), and the commission rate LABEL is the REAL
 * {@see OwnerCommissionRate} (18% in the demo) — NEVER the wireframe's hardcoded 15%. The timeline is built from
 * REAL timestamps (the fabricated "pre-arrival email" event is omitted, #39). Payout status is REAL — derived
 * from the statement(s) the ledger rows landed on.
 */
#[Layout('components.layouts.owner')]
class OwnerBookingDetail extends Component
{
    use FormatsOwnerData;

    /** The reservation id straight off `{booking}` — a plain STRING, never a typed RMB prop. */
    public string $bookingId;

    public function mount(string $booking): void
    {
        $this->bookingId = $booking;
    }

    public function render(): View
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed when empty
        /** @var array<string, Unit> $unitById */
        $unitById = $units->keyBy(fn (Unit $u): string => (string) $u->id)->all();
        $ownerUnitIds = array_map('strval', $units->pluck('id')->all());

        // Reservation is tenant-scoped only — resolve it, THEN prove it touches one of the owner's own units.
        $reservation = Reservation::query()->with('leadGuest')->findOrFail($this->bookingId);

        $ownedLines = $ownerUnitIds === [] ? collect() : ReservationUnit::query()
            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: a stay that began before the owner's day is not theirs → 404
            ->where('reservation_id', $reservation->id)
            ->whereIn('unit_id', $ownerUnitIds)
            ->where('status', 'active')
            ->get(['unit_id', 'check_in', 'check_out']);

        abort_if($ownedLines->isEmpty(), 404);   // ← the owner boundary: not this owner's booking → gone

        // Owner-scoped ledger rows for this reservation → the ONE shared net + breakdown rule.
        $entries = OwnerLedgerEntry::query()
            ->where('reservation_id', $reservation->id)
            ->get();

        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);
        $nights = (int) $checkIn->diffInDays($checkOut);
        $stage = ReservationStage::for($reservation);
        $ownedUnits = $this->ownedUnits($ownedLines, $unitById);
        $currency = (string) $reservation->currency;
        $earnings = OwnerLedgerAttribution::breakdown($entries);

        // ⚠️ Read AFTER the abort_if above, so it sits INSIDE the owner boundary this page already
        //    enforces — never a second definition of "this owner's booking".
        $absorbedRefunds = $this->absorbedRefunds($reservation, $currency);
        $payout = $this->payoutStatus($entries);

        return view('livewire.owner.booking-detail', [
            'viewer' => $this->viewer(),
            'guest' => [
                'name' => $this->guestName($reservation->leadGuest),
                'initials' => $this->guestInitials($reservation->leadGuest),
                'tag' => $this->guestTag($reservation, $ownerUnitIds),
            ],
            'reservation' => [
                'reference' => (string) $reservation->reference,
                'stage' => $this->statusPill($stage),
                'head_sub' => $this->headSub($ownedUnits, $nights, (string) $reservation->source),
                'ci_day' => $checkIn->format('j'),
                'ci_sub' => $checkIn->translatedFormat(LocalDate::pattern('M Y · D')),
                // The property's REAL check-in/out times (was a hardcoded "from 3:00 PM"); null ⇒ the
                // view renders no time line at all rather than inventing an hour.
                'ci_time' => $this->stayTime($reservation->property?->check_in_time, false),
                'co_time' => $this->stayTime($reservation->property?->check_out_time, true),
                'co_day' => $checkOut->format('j'),
                'co_sub' => $checkOut->translatedFormat(LocalDate::pattern('M Y · D')),
                'nights' => $nights,
                'units' => $ownedUnits,
                'guests_label' => $this->guestsLabel($reservation),
                'nightly_rate' => $this->nightlyRate($entries, $reservation, $nights, $currency),
                'source_label' => $this->sourceLabel((string) $reservation->source),
                'source_dot' => $this->sourceDot((string) $reservation->source),
                'channel' => $this->channelLabel((string) $reservation->source),
                'booked_on' => ($reservation->booked_at ?? $reservation->created_at)?->translatedFormat(LocalDate::pattern('j M Y')),
            ],
            'earnings' => $earnings,
            'absorbedRefunds' => $absorbedRefunds,
            'attributed' => $entries->isNotEmpty(),
            'commissionRate' => OwnerCommissionRate::percent(app(OwnerContext::class)),
            'net' => $this->netHero($earnings, $currency),
            'payout' => $payout,
            'timeline' => $this->timeline($reservation, $stage, $checkIn, $checkOut, $payout),
            'canDownload' => app(OwnerContext::class)->can(OwnerTeamRole::CAP_EXPORTS),
            'pdfHref' => route('owner.bookings.confirmation', $reservation->id),
        ]);
    }

    /**
     * A property check-in/out time, prefixed ("from 3:00 PM" / "by 11:00 AM") — or NULL, so an unset time
     * renders nothing at all (the same output as StayTime::labelled(), with the prefix translated).
     */
    private function stayTime(mixed $time, bool $checkOut): ?string
    {
        $display = StayTime::display($time);
        if ($display === null) {
            return null;
        }

        return $checkOut
            ? __('php/livewire/owner/owner_booking_detail.by_time', ['time' => $display])
            : __('php/livewire/owner/owner_booking_detail.from_time', ['time' => $display]);
    }

    /**
     * The owner's OWN unit line(s) in this booking — id + label, so the view links only to units the owner can
     * actually open.
     *
     * @param  Collection<int, ReservationUnit>  $ownedLines
     * @param  array<string, Unit>  $unitById
     * @return list<array{id: string, label: string}>
     */
    private function ownedUnits(Collection $ownedLines, array $unitById): array
    {
        return $ownedLines
            ->map(fn (ReservationUnit $l): string => (string) $l->unit_id)
            ->unique()
            ->filter(fn (string $id): bool => isset($unitById[$id]))
            ->map(fn (string $id): array => [
                'id' => $id,
                'label' => (string) ($unitById[$id]->name ?? $unitById[$id]->code),
            ])
            ->values()
            ->all();
    }

    /**
     * Guest NAME only — never email / phone / contact (invariant #28). Anonymized guest → null (the reference
     * still identifies the booking).
     */
    private function guestName(?Guest $guest): ?string
    {
        if (! $guest instanceof Guest) {
            return null;
        }

        $name = trim(((string) ($guest->first_name ?? '')).' '.((string) ($guest->last_name ?? '')));

        return $name !== '' ? $name : null;
    }

    /** Two-letter initials from the guest NAME (never contact). "—" when anonymized. */
    private function guestInitials(?Guest $guest): string
    {
        $name = $this->guestName($guest);
        if ($name === null) {
            return '—';
        }

        $parts = array_values(array_filter(preg_split('/\s+/', preg_replace('/^The\s+/i', '', $name) ?? $name) ?: []));
        $letters = array_map(fn (string $p): string => LocalText::upper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '—';
    }

    /**
     * The REAL "New guest / Repeat guest" tag — OWNER-SCOPED: how many non-cancelled stays this guest has on
     * THIS owner's units (via the owned-unit set, never org-wide). Not contact PII (#28-safe). Null when the
     * guest is anonymized (no identity to count).
     *
     * @param  list<string>  $ownerUnitIds
     */
    private function guestTag(Reservation $reservation, array $ownerUnitIds): ?string
    {
        $guestId = $reservation->lead_guest_id;
        if ($guestId === null || $ownerUnitIds === []) {
            return null;
        }

        $stays = Reservation::query()
            ->where('lead_guest_id', $guestId)
            ->whereNotIn('status', ReservationStage::CANCELLED_STATUSES)
            ->whereIn('id', ReservationUnit::query()->ownerStays()->whereIn('unit_id', $ownerUnitIds)->where('status', 'active')->select('reservation_id'))
            ->count();

        return $stays > 1
            ? trans_choice('php/livewire/owner/owner_booking_detail.repeat_guest', $stays, ['count' => $stays])
            : __('php/livewire/owner/owner_booking_detail.new_guest');
    }

    /** "N guests" (adults + children), or null when unknown. */
    private function guestsLabel(Reservation $reservation): ?string
    {
        $count = max(0, (int) $reservation->adults + (int) $reservation->children);

        return $count > 0 ? trans_choice('php/livewire/owner/owner_booking_detail.guests_count', $count, ['count' => $count]) : null;
    }

    /** "{unit(s)} · N nights · {source}" — the header sub-line. */
    private function headSub(array $units, int $nights, string $source): string
    {
        $unitLabel = ListJoin::join(collect($units)->pluck('label'));

        return trim($unitLabel.' · '.trans_choice('php/livewire/owner/owner_booking_detail.nights_count', $nights, ['count' => $nights]).' · '.$this->sourceLabel($source), ' ·');
    }

    /**
     * The nightly rate as a display string — the REAL accommodation ÷ nights: the attributed `room_revenue`
     * credit when the booking is on the ledger, else the reservation's booked `total_minor`. Null when there is
     * nothing real to divide.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     */
    private function nightlyRate(Collection $entries, Reservation $reservation, int $nights, string $currency): ?string
    {
        if ($nights < 1) {
            return null;
        }

        $roomRevenue = $entries
            ->where('entry_type', 'room_revenue')
            ->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());

        $accomMinor = $roomRevenue > 0 ? (int) $roomRevenue : (int) $reservation->total_minor;
        if ($accomMinor <= 0) {
            return null;
        }

        return MoneyFormatter::display(intdiv($accomMinor, $nights), $currency);
    }

    /**
     * ⚠️ REFUNDS THE OPERATOR ABSORBED, for the note below the earnings breakdown — DISPLAY ONLY.
     *
     * ⚠️ WHY THIS IS ABSORBED-ONLY, AND WHY THAT ASYMMETRY IS DELIBERATE (§N.32 — recorded here because
     * this is where a future reader meets it, and an unexplained asymmetry gets "tidied"):
     *
     *   · An OWNER-borne refund posts an `adjustment` ledger row. It is ALREADY visible — it renders as a
     *     labelled "Adjustment" deduction in the breakdown above and reduces "Net to you". Repeating it
     *     here would show ONE deduction TWICE on a money page.
     *   · An ABSORBED refund posts NOTHING. It cannot join a gross→net list without either being a zero
     *     line — which implies it was weighed in the arithmetic when it was not — or moving a figure,
     *     which is forbidden.
     *
     * ⚠️ The two cases are represented differently because they ARE different: the breakdown answers "what
     * changed your net"; this note answers "what happened that did not". That is why it sits BELOW "Net to
     * you", outside the reconciliation rather than inside it.
     *
     * ⚠️ NO GUEST DATA (#28): a refund is a payment to a guest, but only the amount and the date are read
     * — no name, no contact, no payment reference, no gateway detail. ⚠️ AND NO REASON: the column records
     * WHO bore it, never WHY, and inferring the why from the who would be a fabrication.
     *
     * @return list<array{amount_display: string, date: string}>
     */
    private function absorbedRefunds(Reservation $reservation, string $currency): array
    {
        return Payment::query()
            ->where('reservation_id', $reservation->getKey())
            ->where('type', 'refund')
            ->where('status', 'succeeded')
            ->where('refund_borne_by', Payment::BORNE_BY_OPERATOR)
            ->orderBy('processed_at')
            ->get()
            ->map(fn (Payment $r): array => [
                // abs(): the refund row stores its amount positive; the note states an amount refunded.
                'amount_display' => MoneyFormatter::display(abs((int) $r->amount_minor), (string) ($r->currency ?? $currency)),
                'date' => $r->processed_at !== null ? Carbon::parse($r->processed_at)->translatedFormat(LocalDate::pattern('j M Y')) : '',
            ])
            ->values()
            ->all();
    }

    /**
     * The dark net-hero figure — the single-currency net (minor + display + a JS count-up target), or the raw
     * per-currency blocks when a booking somehow spans currencies (no count-up then).
     *
     * @param  list<array{currency: string, net_minor: int, net_display: string}>  $earnings
     * @return array{single: bool, minor: int, display: string, major: float, symbol: string}|null
     */
    private function netHero(array $earnings, string $currency): ?array
    {
        if (count($earnings) !== 1) {
            return null;   // 0 = not attributed; >1 = multi-currency (view renders the blocks, no count-up)
        }

        $block = $earnings[0];
        $minor = (int) $block['net_minor'];

        return [
            'single' => true,
            'minor' => $minor,
            'display' => (string) $block['net_display'],
            'major' => $minor / 100,
            'symbol' => MoneyFormatter::symbol((string) $block['currency']),
        ];
    }

    /** status → the wireframe's 4-state pill (label + bg/text/dot colours). */
    private function statusPill(string $stage): array
    {
        // ⚠️ This pill sits on the page GRADIENT, not on a card, so its tint composites over a dark, saturated
        // background — every tone measured 1.86–2.73 there. Two changes make all four states legible: the tint
        // is laid over a near-white plate (the surface the design assumes), and the text tones are darkened.
        // Measured on the plated tint: green 5.96 · amber 4.80 · red 6.08 · blue 7.19.
        $palette = match ($stage) {
            ReservationStage::IN_HOUSE, ReservationStage::UPCOMING, ReservationStage::DUE => ['bg' => 'linear-gradient(rgba(31,138,91,.13),rgba(31,138,91,.13)),rgba(255,252,248,.92)', 'co' => '#186040', 'dot' => '#1F8A5B'],
            ReservationStage::TENTATIVE => ['bg' => 'linear-gradient(rgba(224,160,48,.16),rgba(224,160,48,.16)),rgba(255,252,248,.92)', 'co' => '#8A5C08', 'dot' => '#E0A02A'],
            ReservationStage::CANCELLED => ['bg' => 'linear-gradient(rgba(190,90,80,.13),rgba(190,90,80,.13)),rgba(255,252,248,.92)', 'co' => '#8A3A30', 'dot' => '#C2564A'],
            default => ['bg' => 'linear-gradient(rgba(61,108,176,.13),rgba(61,108,176,.13)),rgba(255,252,248,.92)', 'co' => '#24487A', 'dot' => '#3D6CB0'],   // past/settled
        };

        return ['label' => ReservationStage::meta($stage)['label']] + $palette;
    }

    /** Source → the wireframe's status dot colour. */
    private function sourceDot(string $source): string
    {
        return match ($source) {
            'direct', 'walk_in' => '#1F8A5B',
            'ota' => '#3D6CB0',
            'corporate', 'group', 'agent' => '#E0A02A',
            default => '#A69A92',
        };
    }

    /** The distribution channel label — real, derived from the source (direct = the owner's own storefront). */
    private function channelLabel(string $source): string
    {
        return match ($source) {
            'direct' => __('php/livewire/owner/owner_booking_detail.channel_direct'),
            'walk_in' => __('php/livewire/owner/owner_booking_detail.channel_walk_in'),
            'ota' => __('php/livewire/owner/owner_booking_detail.channel_ota'),
            default => $this->sourceLabel($source),
        };
    }

    /**
     * The REAL booking timeline — built from the reservation's own timestamps + payment state + the payout
     * status. Events that are not tracked with a real timestamp are OMITTED, never invented (#39): the design's
     * "pre-arrival email" (no notification backend) is not rendered.
     *
     * @param  array{label: string, tone: string, note: string, paid_on: ?string}  $payout
     * @return list<array{label: string, when: string, state: string}>
     */
    private function timeline(Reservation $reservation, string $stage, Carbon $checkIn, Carbon $checkOut, array $payout): array
    {
        $booked = $reservation->booked_at ?? $reservation->created_at;
        $events = [['label' => __('php/livewire/owner/owner_booking_detail.event_booking_received'), 'when' => $booked?->translatedFormat(LocalDate::pattern('j M Y')) ?? '—', 'state' => 'done']];

        // Cancelled bookings have a short, honest timeline — no payment/stay/payout followed.
        if (in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true)) {
            $events[] = [
                'label' => __('php/livewire/owner/owner_booking_detail.event_cancelled'),
                'when' => ($reservation->cancelled_at ?? $booked)?->translatedFormat(LocalDate::pattern('j M Y')) ?? '—',
                'state' => 'cancel',
            ];

            return $events;
        }

        $events[] = match ((string) $reservation->payment_status) {
            'paid' => ['label' => __('php/livewire/owner/owner_booking_detail.event_payment_received'), 'when' => __('php/livewire/owner/owner_booking_detail.when_paid_in_full'), 'state' => 'done'],
            'partial' => ['label' => __('php/livewire/owner/owner_booking_detail.event_part_payment_received'), 'when' => __('php/livewire/owner/owner_booking_detail.when_balance_due'), 'state' => 'now'],
            default => ['label' => __('php/livewire/owner/owner_booking_detail.event_payment'), 'when' => __('php/livewire/owner/owner_booking_detail.when_not_yet_received'), 'state' => 'next'],
        };

        if ($reservation->guest_checked_in_at !== null) {
            $events[] = ['label' => __('php/livewire/owner/owner_booking_detail.event_checked_in'), 'when' => Carbon::parse($reservation->guest_checked_in_at)->translatedFormat(LocalDate::pattern('j M · g:i A')), 'state' => 'done'];
            // ⚠️ TERMINAL STATUS IS CONSULTED (wireframe-parity WAVE 3). This branch used to fire on any
            // future-dated check-in, so a booking already cancelled / no-show / checked out rendered
            // "Check-in — upcoming" directly ABOVE its own completed "Checked out" row. One demo reservation is
            // genuinely in that state today. A stay that has ended has no upcoming check-in; TERMINAL_STATUSES is
            // the certified list AssignmentService bounds re-termination with (#29).
        } elseif ($checkIn->isFuture() && ! in_array((string) $reservation->status, AssignmentService::TERMINAL_STATUSES, true)) {
            // ⚠️ Was `format('D j M · 3:00 PM')` — Carbon read the literal, where P is the UTC offset and
            // M the month, yielding "Sat 5 Sep · 3:00 +00:00Sep". And the hour itself was hardcoded while
            // the property stores a real check_in_time. Both are fixed by passing a real time through a
            // real format; an unset time simply omits the hour rather than inventing one (#39).
            $ciTime = StayTime::display($reservation->property?->check_in_time);
            $events[] = ['label' => __('php/livewire/owner/owner_booking_detail.event_check_in'), 'when' => $checkIn->translatedFormat(LocalDate::pattern('D j M')).($ciTime !== null ? ' · '.$ciTime : ''), 'state' => 'next'];
        }

        if ((string) $reservation->status === 'checked_out') {
            $events[] = ['label' => __('php/livewire/owner/owner_booking_detail.event_checked_out'), 'when' => $checkOut->translatedFormat(LocalDate::pattern('D j M Y')), 'state' => 'done'];
        }

        // Payout — REAL, from the statement the ledger rows landed on (via payoutStatus()).
        if ($payout['paid_on'] !== null) {
            $events[] = ['label' => __('php/livewire/owner/owner_booking_detail.event_payout_released'), 'when' => $payout['paid_on'], 'state' => 'done'];
        } elseif ($payout['tone'] === 'pending') {
            $events[] = ['label' => __('php/livewire/owner/owner_booking_detail.event_payout'), 'when' => __('php/livewire/owner/owner_booking_detail.when_next_payout'), 'state' => 'next'];
        }

        return $events;
    }

    /**
     * The REAL payout status of this booking's earnings — derived from the statement(s) its owner-scoped ledger
     * rows landed on. Not yet on a statement → "Scheduled"; on a finalized statement → "Payout pending"; on a
     * paid one → "Paid" (with the paid date, for the timeline). Straddled statuses → the LEAST-settled wins.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array{label: string, tone: string, note: string, paid_on: ?string}
     */
    private function payoutStatus(Collection $entries): array
    {
        if ($entries->isEmpty()) {
            return ['label' => __('php/livewire/owner/owner_booking_detail.payout_not_attributed'), 'tone' => 'draft', 'note' => __('php/livewire/owner/owner_booking_detail.payout_not_attributed_note'), 'paid_on' => null];
        }

        $statementIds = $entries
            ->map(fn (OwnerLedgerEntry $e): mixed => $e->statement_id)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();

        if ($statementIds->isEmpty() || $entries->contains(fn (OwnerLedgerEntry $e): bool => $e->statement_id === null)) {
            return ['label' => __('php/livewire/owner/owner_booking_detail.payout_scheduled'), 'tone' => 'pending', 'note' => __('php/livewire/owner/owner_booking_detail.payout_scheduled_note'), 'paid_on' => null];
        }

        $statuses = OwnerStatement::query()->whereIn('id', $statementIds->all())->pluck('status')->map(fn ($s): string => (string) $s);

        if ($statuses->contains(fn (string $s): bool => $s !== 'paid')) {
            return ['label' => __('php/livewire/owner/owner_booking_detail.payout_pending'), 'tone' => 'pending', 'note' => __('php/livewire/owner/owner_booking_detail.payout_pending_note'), 'paid_on' => null];
        }

        // The REAL payout date — from the Payout(s) linked to these statements (owner-scoped), not the statement.
        $paidOn = Payout::query()
            ->whereIn('owner_statement_id', $statementIds->all())
            ->whereNotNull('paid_at')
            ->get(['paid_at'])
            ->map(fn (Payout $p): Carbon => Carbon::parse($p->paid_at))
            ->max();

        return ['label' => __('php/livewire/owner/owner_booking_detail.payout_paid'), 'tone' => 'paid', 'note' => __('php/livewire/owner/owner_booking_detail.payout_paid_note'), 'paid_on' => $paidOn?->translatedFormat(LocalDate::pattern('j M Y'))];
    }
}
