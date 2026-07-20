<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Guest;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Reservations\ReservationStage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Booking Detail (Phase 2 · Sub-gate 1) — ONE reservation on the owner's unit: its facts, the gross→net
 * earnings breakdown, "Net to you", and its payout status. Styled from the catalogued "Owner Booking Detail"
 * design, wired to LIVE owner-scoped data. Read-only.
 *
 * ⚠️ THE CRITICAL GUARD — a `{reservation}` id is a REQUEST input, and reservations are NOT owner-scoped
 * (owner A and owner B share the operator's ORG, so tenant scope does not separate them). So resolving the
 * reservation is not enough: {@see render()} REJECTS with 404 unless the reservation touches a unit THIS owner
 * actually owns — the owned-unit set comes from the owner-scoped {@see OwnerPortalService::units()}, never from
 * the request. Without this check, owner A could read owner B's booking by guessing an id (the #26 shape).
 * OwnerBookingsTest proves the rejection (and that removing the guard breaks it). The id is a plain STRING prop
 * resolved in render() (never a route-model-bound typed prop, which would resolve before the scope middleware).
 *
 * Honesty: guest NAME + guest COUNT only — NO email / phone / country / "message guest" (invariant #28; and
 * owner↔guest messaging does not exist — the owner inbox is owner↔staff). The design's pre-arrival-email
 * timeline is fabricated (no notification backend) and is not rendered. The earnings breakdown comes through
 * the ONE shared {@see OwnerLedgerAttribution} rule, so "Net to you" matches the Bookings list, Unit Detail and
 * the statement to the cent. Payout status is REAL — derived from the statement(s) the ledger rows landed on.
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
        $stage = ReservationStage::for($reservation);

        return view('livewire.owner.booking-detail', [
            'viewer' => $this->viewer(),
            'booking' => [
                'reference' => (string) $reservation->reference,
                'guest' => $this->guestName($reservation->leadGuest),
                'guest_count' => max(0, (int) $reservation->adults + (int) $reservation->children),
                'units' => $this->ownedUnits($ownedLines, $unitById),
                'check_in' => $checkIn->format('D j M Y'),
                'check_out' => $checkOut->format('D j M Y'),
                'nights' => (int) $checkIn->diffInDays($checkOut),
                'source_label' => $this->sourceLabel((string) $reservation->source),
                'stage_badge' => $this->stageBadge($stage),
                'booked_on' => $reservation->created_at?->format('j M Y'),
            ],
            'earnings' => OwnerLedgerAttribution::breakdown($entries),
            'attributed' => $entries->isNotEmpty(),
            'payout' => $this->payoutStatus($entries),
        ]);
    }

    /**
     * The owner's OWN unit line(s) in this booking — id + label, so the view links only to units the owner can
     * actually open (Unit Detail is itself owner-scoped, but never offer a link to a unit outside the set).
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

    /**
     * The REAL payout status of this booking's earnings — derived from the statement(s) its owner-scoped ledger
     * rows landed on (`owner_statements` is itself owner-scoped). Not yet on a statement → "Scheduled"; on a
     * finalized statement → "Payout pending"; on a paid one → "Paid". If a booking's rows straddle statuses the
     * LEAST-settled wins (the money isn't fully out until every line is).
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return array{label: string, tone: string, note: string}
     */
    private function payoutStatus(Collection $entries): array
    {
        if ($entries->isEmpty()) {
            return ['label' => 'Not yet attributed', 'tone' => 'draft', 'note' => 'This booking has not been attributed to your ledger yet.'];
        }

        $statementIds = $entries
            ->map(fn (OwnerLedgerEntry $e): mixed => $e->statement_id)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();

        // Any row not yet on a statement → the earnings are still scheduled for a future statement.
        if ($statementIds->count() < 1 || $entries->contains(fn (OwnerLedgerEntry $e): bool => $e->statement_id === null)) {
            return ['label' => 'Scheduled', 'tone' => 'pending', 'note' => 'Included in your next statement, then released with that payout.'];
        }

        $statuses = OwnerStatement::query()
            ->whereIn('id', $statementIds->all())
            ->pluck('status')
            ->map(fn ($s): string => (string) $s);

        if ($statuses->contains(fn (string $s): bool => $s !== 'paid')) {
            return ['label' => 'Payout pending', 'tone' => 'pending', 'note' => 'On a finalized statement — releases with that payout.'];
        }

        return ['label' => 'Paid', 'tone' => 'paid', 'note' => 'Released with a completed payout.'];
    }
}
