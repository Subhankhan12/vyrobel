<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\GuestActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Note;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Tag;
use App\Services\GuestService;
use App\Support\Guest\GuestHistory;
use App\Support\Guest\GuestSpend;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator GUEST PROFILE — the CRM detail (Wave 2 · Sub-gate 5). One guest: who they are, how to reach
 * them, what they have been billed, every stay they have taken, and the operator's own notes and tags.
 *
 * THE READS LIVE HERE; THE WRITES LIVE IN {@see GuestActionController} → {@see GuestService}. The design's
 * "Save changes" button fired a toast and persisted NOTHING (docs/WAVE2-INVENTORY.md: "No persistence, no
 * validation, no request… A design-level toast is a mock, not a backend"). Every control on this page is a
 * real POST that survives a refresh, or it is not here.
 *
 * ⚠️ PII SURFACE — consistent with the Guests list, and still the only pair (locked decision 4). Email and
 * phone are `encrypted` casts, decrypted for display and EDITABLE here. Assigning them writes ciphertext
 * because the cast says so; no manual encrypt call exists anywhere in this path (one would double-encrypt).
 * The audit trail deliberately records WHICH fields changed, never their values — `audit_events.before`/
 * `after` are plaintext columns that `Guest::anonymize()` does not clear, so logging the old email there
 * would re-retain the very PII an erase destroys (see GuestService's docblock).
 *
 * RMB RULE (invariant #20): `{guest}` is a plain STRING prop that Livewire fills from the route param and
 * that render() resolves via a TENANT-SCOPED findOrFail — AFTER SetTenantForWebRequest. Never a
 * route-model-bound typed prop (RMB resolves during SubstituteBindings, i.e. BEFORE the scope middleware →
 * leak). A cross-org id 404s. ⚠️ The `Id` SUFFIX IS LOAD-BEARING, not styling: Livewire injects public props
 * into the view scope, so a prop named `$guest` would SHADOW the `'guest' => [...]` array render() passes and
 * the view's `$guest['name']` would fatal on a string offset. Same reason OperatorOwnerDetail /
 * ReservationDetail / MaintenanceTicketDetail all use `$xxxId`.
 *
 * ⚠️ AND THE PROP IS A SECOND REASON HERE: a public prop is serialized into the page payload and round-trips
 * over /livewire/update. Holding an id — not the guest — keeps every decrypted contact detail server-side.
 *
 * NOTHING IS DERIVED TWICE. Three shared definitions do the work and this file re-implements none:
 *   • {@see GuestSpend}   — "Total billed" + the open balance beside it (locked decision 1). The LIST reads
 *                           the same helper, so the two screens cannot disagree; it reconciles to the
 *                           append-only ledger by construction (GuestSpendTest pins it against FolioTotals).
 *   • {@see GuestHistory} — stays · nights · "guest since" (a cancelled booking is not a stay).
 *   • {@see ReservationStage} — the header badge and every stay row's badge.
 *
 * Honesty triage (invariant #19; standing order = OMIT AGGRESSIVELY):
 *   • REAL     — name; `email`/`phone`; `nationality`; `is_vip`, `risk_flag`, `marketing_consent` (all three
 *                real boolean columns); tags (the shared `taggables` pivot); notes (the shared polymorphic
 *                `notes` table + its real author + timestamp); every stay via `Guest::reservations()`.
 *   • DERIVED  — "Total billed" + open balance; stays · nights · avg/night; "guest since"; the header stage
 *                badge; the repeat-guest pill; the source mix.
 *   • OMITTED (no backend — absent, not greyed out):
 *       – The ENTIRE "LATEST REVIEW" PANEL — ~15% of the design's visual weight. There is no reviews table,
 *         model or migration anywhere; its 5.0, its five hard-coded stars and its quoted paragraph are all
 *         literals. Reviews are a Tier-2 module, not a panel to fake.
 *       – The PAUSED-BILLING BANNER. Omitted by name in Wave 1 (OperatorOwnerDetail:101-102) and again in
 *         Sub-gates 1 and 4; not reopened here.
 *       – The localStorage REFUND NETTING (`vyrobel:refund:last`) behind the design's lifetime figure and
 *         its "€978 open folio". Both branches of that pill are hard-coded and 978 only reconciles if the
 *         refund is exactly €277 — an arbitrary localStorage number still prints €978. Rejected by
 *         precedent (OperatorOwnerDetail:111) and by arithmetic.
 *       – The "DUBLIN, IRELAND" location row and the modal's City/country input. `guests` has NO city or
 *         residence column — `nationality` is char(2), a NATIONALITY. Rendering a residence we do not store
 *         (or relabelling a nationality as one) is a data-modelling error, not a display choice.
 *       – "PREFERS EMAIL" and the modal's "Prefers email contact" toggle. No `preferred_channel` column;
 *         `guests.language` is a locale, not a contact preference. And no routing honours such a thing.
 *       – The 8 HARD-CODED PREFERENCE CHIPS (Sea view / Crib / …). A designer's guess living in the design's
 *         own script, not a tenant catalog. The real `tags` catalog replaces them — see below.
 *       – The "SHOWN TO HOUSEKEEPING & FRONT DESK" hint under the preferences, and the VIP toggle's "Flags
 *         arrivals & the front desk for extra care". Both VERIFIED FALSE: neither HousekeepingBoard nor
 *         FrontDesk reads tags or `is_vip`. The toggles are real; the promised downstream effect does not
 *         exist, so the promise is not printed.
 *       – The nav badges (hard-coded 2 / 2) and the design's own nav grouping — the built shell is the one
 *         with real routes.
 *   • SOON     — nothing on this page. Every control here writes.
 *
 * DECISIONS TAKEN HERE (each was ambiguous or wrong in the design, so it is stated rather than guessed):
 *   1. TWO NAME INPUTS, not one. The design has a single "Guest name" field; the schema has `first_name` +
 *      `last_name`. Splitting on the last space is lossy for real names ("van Dijk", "de la Cruz"), so the
 *      form asks for both.
 *   2. TAGS ARE CREATED ON FIRST USE, not picked from a list. `tags` has been real since Phase 1 and
 *      NOTHING had ever written one — a pick-list would be empty in every tenant, forever. `tags` carries
 *      `unique(organization_id, name)`, which makes a name the identity within a tenant and create-on-use
 *      the intended shape. The catalog grows from what operators actually type; the list's tag chips then
 *      mean something.
 *   3. MARKETING CONSENT IS SHOWN AS A RECORD, NOT A PROMISE. The column is real and this page is its first
 *      reader anywhere. There is no sending mechanism, so the design's "Seasonal offers & the occasional
 *      newsletter" is not printed — a consent record is worth keeping accurately on its own terms, and the
 *      caption says exactly that.
 *   4. THE VOCABULARY IS THE APP'S. The design's stay rows say "Completed", which is NOT in the status enum
 *      (enquiry/tentative/confirmed/checked_in/checked_out/cancelled/no_show) — it means `checked_out`. Its
 *      "Booking.com" is not modelled either: `source` is direct/walk_in/ota/corporate/group/agent/manual
 *      with no brand column, so an OTA booking reads "OTA". Both come from the shared helpers.
 *
 * Scope-dependent reads take no parameters and there are no wire-actions: every mutation is a real POST that
 * re-runs the route middleware that establishes the tenant (invariant #20).
 */
#[Layout('components.layouts.operator')]
class GuestProfile extends Component
{
    use FormatsOperatorData;

    /** The `{guest}` route param — a plain STRING id. See the class docblock: the `Id` suffix is load-bearing. */
    public string $guestId = '';

    public function mount(string $guest): void
    {
        $this->guestId = $guest;
    }

    public function render(): View
    {
        // Tenant-scoped → a cross-org id 404s. Resolved HERE, after the scope middleware — never via RMB.
        $guest = Guest::query()->with(['tags'])->findOrFail($this->guestId);

        $today = CarbonImmutable::today();

        // Read the rows through their OWN tenant-scoped model rather than the relation: a relation without
        // generics degrades to Collection<int, Model> under static analysis and every real column read
        // becomes an undefined-property error. Same table, same global scope, same rows — a type PHPStan can
        // check. (The ReservationDetail precedent.)
        $reservations = Reservation::query()
            ->where('lead_guest_id', $guest->getKey())
            ->with(['reservationUnits' => fn ($lines) => $lines->where('status', 'active'), 'reservationUnits.unit'])
            ->orderByDesc('check_in')
            ->orderByDesc('id')
            ->get();

        $history = GuestHistory::for($reservations);
        $spend = GuestSpend::for($guest);
        $refunds = $this->refundsByReservation($reservations->map(fn (Reservation $r): string => (string) $r->getKey())->all());

        return view('livewire.operator.guest-profile', [
            'viewer' => $this->viewer(),
            'guest' => $this->guestCard($guest, $history, $today, $reservations),
            'spend' => $this->spendCard($spend, $history),
            'stays' => $reservations->map(fn (Reservation $reservation): array => $this->stayRow($reservation, $today, $refunds))->all(),
            'history' => $history,
            'notes' => $this->notes($guest),
            'review' => $this->latestReview($guest),
            'prefCatalog' => Guest::PREFERENCE_CATALOG,
            'threadId' => $this->latestThreadId($guest),
        ]);
    }

    /**
     * The guest's latest review (#76) — real, tied to a real completed stay. Any review (published/pending),
     * because the OPERATOR sees their own guest's feedback regardless of public moderation state. Null hides
     * the panel (the honest "no reviews yet" is the design's empty state, never a fabricated 5.0).
     *
     * @return array<string, mixed>|null
     */
    private function latestReview(Guest $guest): ?array
    {
        $review = Review::query()
            ->where('guest_id', $guest->getKey())
            ->with(['unit', 'roomType'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($review === null) {
            return null;
        }

        $rating = max(1, min(5, (int) $review->rating));
        $unit = $review->unit;
        $roomType = $review->roomType;
        $unitName = $unit !== null ? (string) $unit->name : ($roomType !== null ? (string) $roomType->name : null);

        return [
            'rating' => $rating,
            'stars' => str_repeat('★', $rating).str_repeat('☆', 5 - $rating),
            'body' => (string) ($review->body ?? ''),
            'unit' => $unitName,
            'when' => $review->created_at !== null ? CarbonImmutable::parse($review->created_at)->format('F Y') : null,
            'status' => (string) $review->status,
            'response' => $review->operator_response !== null ? (string) $review->operator_response : null,
        ];
    }

    /**
     * Total succeeded refunds per reservation (#87 — the stay-history "− refund" line). One query over the
     * guest's stays; refunds are `payments.type='refund' AND status='succeeded'`, summed as a positive figure.
     *
     * @param  list<string>  $reservationIds
     * @return array<string, int>
     */
    private function refundsByReservation(array $reservationIds): array
    {
        if ($reservationIds === []) {
            return [];
        }

        return Payment::query()
            ->whereIn('reservation_id', $reservationIds)
            ->where('type', 'refund')
            ->where('status', 'succeeded')
            ->selectRaw('reservation_id, SUM(ABS(amount_minor)) as refunded')
            ->groupBy('reservation_id')
            ->pluck('refunded', 'reservation_id')
            ->map(fn ($v): int => (int) $v)
            ->all();
    }

    // ── The guest ────────────────────────────────────────────────────────────────────────────────

    /**
     * @param  array{stays: int, nights: int, since_year: ?string, stat: ?string}  $history
     * @param  Collection<int, Reservation>  $reservations
     * @return array<string, mixed>
     */
    private function guestCard(Guest $guest, array $history, CarbonImmutable $today, $reservations): array
    {
        $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));
        $anonymized = $guest->isAnonymized();

        // A real state, not missing data: anonymize() nulls the PII and KEEPS the row so the financial
        // history referencing it stays intact.
        $label = match (true) {
            $name !== '' => $name,
            $anonymized => 'Anonymized guest',
            default => 'Unnamed guest',
        };

        $stage = ReservationStage::mostSignificant($reservations, $today);

        return [
            'id' => (string) $guest->getKey(),
            'name' => $label,
            'first_name' => (string) $guest->first_name,
            'last_name' => (string) $guest->last_name,
            'initials' => $name !== '' ? $this->initials($name) : '—',
            'anonymized' => $anonymized,
            // ⚠️ PII — decrypted for display AND for the edit form's current values.
            'email' => $this->contact($guest->email),
            'phone' => $this->contact($guest->phone),
            // #87 — a real residence (the Contact card's city). Distinct from `nationality` (char2).
            'city' => $this->contact($guest->city),
            // A nationality, not a residence (decision · see the docblock).
            'nationality' => $this->countryName($guest->nationality),
            'nationality_code' => $guest->nationality !== null ? (string) $guest->nationality : '',
            'is_vip' => (bool) $guest->is_vip,
            'risk_flag' => (bool) $guest->risk_flag,
            'prefers_email' => (bool) $guest->prefers_email,
            'marketing_consent' => (bool) $guest->marketing_consent,
            // #87 — the ACTIVE stay-preference keys (a subset of Guest::PREFERENCE_CATALOG). Shown on the
            // profile AND flowed to the front desk + housekeeping.
            'preferences' => is_array($guest->preferences) ? array_values($guest->preferences) : [],
            'tags' => $guest->tags->map(fn (Tag $tag): array => [
                'id' => (string) $tag->getKey(),
                'name' => (string) $tag->name,
            ])->all(),
            'stage' => $stage,
            'stage_meta' => $stage !== null ? ReservationStage::meta($stage) : null,
            // DERIVED, not a column — a guest with more than one real stay has come back.
            'repeat' => $history['stays'] > 1,
            'since_year' => $history['since_year'],
            // The in-house stay, if any — the design's "Current stay" CTA. Null hides it rather than
            // rendering a dead link.
            'current_stay' => $this->currentStayId($reservations, $today),
            'source_mix' => $this->sourceMix($reservations),
        ];
    }

    /** The decrypted value, or null when there genuinely is none (nullable columns; anonymize() nulls both). */
    private function contact(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    /**
     * The id of the stay the guest is in RIGHT NOW, via the shared resolver — or null.
     *
     * @param  Collection<int, Reservation>  $reservations
     */
    private function currentStayId($reservations, CarbonImmutable $today): ?string
    {
        $current = $reservations->first(
            fn (Reservation $reservation): bool => ReservationStage::for($reservation, $today) === ReservationStage::IN_HOUSE
        );

        return $current !== null ? (string) $current->getKey() : null;
    }

    /**
     * "2 direct · 1 OTA" — the real `source` enum through the shared label map.
     *
     * The design says "2 direct · 1 Booking.com". THE OTA BRAND IS NOT MODELLED: `source` is exactly
     * direct/walk_in/ota/corporate/group/agent/manual and no brand column exists on any table, so an OTA
     * booking is "OTA" and nothing more specific can be claimed. (`message_threads.channel` DOES carry
     * booking_com/airbnb — that is the messaging medium, a different enum for a different concept.)
     *
     * Cancelled bookings are excluded, consistently with the stay count: the mix describes stays taken.
     *
     * @param  Collection<int, Reservation>  $reservations
     */
    private function sourceMix($reservations): ?string
    {
        $counts = [];

        foreach ($reservations as $reservation) {
            if (in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true)) {
                continue;
            }

            $label = $this->sourceLabel((string) $reservation->source);
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        if ($counts === []) {
            return null;
        }

        arsort($counts);

        return implode(' · ', array_map(
            fn (string $label, int $count): string => $count.' '.$label,
            array_keys($counts),
            array_values($counts),
        ));
    }

    // ── Money — the SHARED definition, identical to the list's ────────────────────────────────────

    /**
     * "Total billed" + the open balance BESIDE it (locked decision 1), per currency, never summed across.
     *
     * Plus avg/night — the design's tile. Its FORMULA is sound; its inputs were fabricated. It is only
     * computed for the primary currency and only when there are nights to divide by: a cross-currency
     * average would invent an FX rate, and dividing by zero nights would print an infinity.
     *
     * @param  array<string, array{billed_minor: int, balance_minor: int, stays: int}>  $spend
     * @param  array{stays: int, nights: int, since_year: ?string, stat: ?string}  $history
     * @return array<string, mixed>
     */
    private function spendCard(array $spend, array $history): array
    {
        $primary = GuestSpend::primary($spend);

        $billed = [];
        $balance = [];

        foreach ($spend as $currency => $figures) {
            if ($figures['billed_minor'] !== 0) {
                $billed[] = $this->money($figures['billed_minor'], $currency);
            }
            if ($figures['balance_minor'] !== 0) {
                $balance[] = $this->money($figures['balance_minor'], $currency);
            }
        }

        return [
            // Null → the view renders "—". A guest with no folio has no currency to render a zero IN.
            'primary' => $primary !== null ? $this->money($primary['billed_minor'], $primary['currency']) : null,
            'billed' => $billed,
            'balance' => $balance,
            'multi_currency' => count($billed) > 1,
            'avg_per_night' => $primary !== null && $history['nights'] > 0
                ? $this->money((int) round($primary['billed_minor'] / $history['nights']), $primary['currency'])
                : null,
        ];
    }

    // ── Stays — every one, each linking to the real reservation detail ────────────────────────────

    /**
     * One stay row. The design linked only the FIRST row (rows 2-3 were `href: '#'`); every row here links,
     * because `route('app.reservations.show', $id)` is real for all of them.
     *
     * @param  array<string, int>  $refunds  reservation id → total succeeded refund (minor)
     * @return array<string, mixed>
     */
    private function stayRow(Reservation $reservation, CarbonImmutable $today, array $refunds = []): array
    {
        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();

        $lines = $reservation->reservationUnits;
        $units = $lines
            ->filter(fn ($line): bool => $line->unit_id !== null && $line->unit !== null)
            ->map(fn ($line): string => (string) ($line->unit->name ?? $line->unit->code))
            ->unique()->values();

        $stage = ReservationStage::for($reservation, $today);
        $currency = (string) ($reservation->currency ?? 'EUR');

        return [
            'id' => (string) $reservation->getKey(),
            'reference' => (string) $reservation->reference,
            'check_in' => $checkIn->format('j M Y'),
            'check_out' => $checkOut->format('j M Y'),
            // Half-open [check_in, check_out) — the same arithmetic as the folio's room nights.
            'nights' => (int) $checkIn->diffInDays($checkOut),
            // `reservation_units.unit_id` is nullable by design (a fresh OTA import lands unassigned), so
            // this reports a real state.
            'unit' => $units->isNotEmpty() ? $units->implode(', ') : null,
            'source' => $this->sourceLabel((string) $reservation->source),
            'stage' => $stage,
            'stage_meta' => ReservationStage::meta($stage),
            'in_house' => $stage === ReservationStage::IN_HOUSE,
            // total_minor is kept in sync with the append-only ledger by FolioService::recompute(), so this
            // reconciles to the folio for free.
            'total' => $this->money((int) $reservation->total_minor, $currency),
            // #87 — the real refund on this stay (succeeded refund payments), shown as a "− refund" line. Zero
            // → null → no line, never a fabricated €0.00.
            'refund' => ($refunds[(string) $reservation->getKey()] ?? 0) > 0
                ? $this->money($refunds[(string) $reservation->getKey()], $currency)
                : null,
        ];
    }

    // ── Notes — the shared polymorphic table, this page its first reader ──────────────────────────

    /**
     * The guest's notes, newest first. REAL rows: `notes.body`, the real `author_id` → user name, and the
     * real `created_at`.
     *
     * An anonymized guest's note bodies are redacted to '' by `Guest::anonymize()` (added with this gate —
     * a note about an identified person is their PII). Those rows render as an explicit "Redacted" rather
     * than a blank card that reads like a bug.
     *
     * @return list<array<string, mixed>>
     */
    private function notes(Guest $guest): array
    {
        // Through Note's own tenant-scoped model, not the relation — see render() for why (PHPStan generics).
        $notes = Note::query()
            ->where('notable_type', $guest->getMorphClass())
            ->where('notable_id', $guest->getKey())
            ->with('author')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $notes->map(function (Note $note): array {
            $body = (string) $note->body;

            return [
                'id' => (string) $note->getKey(),
                'body' => $body,
                'redacted' => $body === '',
                // `author_id` is nullable BY DESIGN (nullOnDelete) — a note outlives the staff member who
                // wrote it. Say so rather than inventing an author.
                'author' => $note->author?->name !== null ? (string) $note->author->name : 'Former staff member',
                'created' => $note->created_at !== null
                    ? CarbonImmutable::parse($note->created_at)->format('j M Y')
                    : '—',
            ];
        })->all();
    }

    /**
     * The guest's most recent inbox thread, if one exists — the design's "Message" button.
     *
     * Link-if-exists only: STARTING a conversation is the inbox's job, and every external channel is
     * account-gated (ManualChannel is the only one that sends). A button that opened a composer we cannot
     * send from would be the design's toast in a new costume. Null hides it.
     *
     * This also lights up a link that was already computed and never rendered: InboxThread::guestCard()
     * returns `guest_id` and its blade ignores it — the two screens now point at each other.
     */
    private function latestThreadId(Guest $guest): ?string
    {
        $thread = MessageThread::query()
            ->where('guest_id', $guest->getKey())
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        return $thread !== null ? (string) $thread->getKey() : null;
    }
}
