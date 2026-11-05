<?php

declare(strict_types=1);

namespace App\Livewire\Operator\Concerns;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Payout;
use App\Models\User;
use App\Support\I18n\LocalText;
use App\Support\Maintenance\MaintenanceSla;
use App\Support\Money\MoneyFormatter;
use App\Support\Reports\OperatorRevenueBreakdown;

/**
 * Shared presentation formatting for the operator back-office Livewire screens (design pass). The
 * operator mirror of {@see FormatsOwnerData}. Formatting ONLY — no data
 * access, no business logic; the numbers come from the tenant-scoped services/models untouched.
 *
 * Operator screens are TENANT-scoped (BelongsToOrganization), never owner-scoped.
 */
trait FormatsOperatorData
{
    /**
     * The authenticated staff user, for the topbar chip.
     *
     * @return array{name: string, initials: string}
     */
    protected function viewer(): array
    {
        $user = auth()->user();
        $name = $user instanceof User ? (string) $user->name : (string) __('php/livewire/operator/concerns/formats_operator_data.viewer_fallback');

        return ['name' => $name, 'initials' => $this->initials($name)];
    }

    protected function initials(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));

        if ($parts === []) {
            return 'O';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return LocalText::upper($first.$last);
    }

    /** Format integer minor units for display, e.g. 1037000 EUR → "€10,370.00". */
    protected function money(int $minor, string $currency): string
    {
        $symbol = $this->currencySymbol($currency);
        $abs = number_format(abs($minor) / 100, 2, '.', ',');

        return ($minor < 0 ? '−' : '')."{$symbol}{$abs}";   // U+2212 minus, matches the design
    }

    /** A signed integer for a delta, e.g. 5 → "+5", -3 → "−3", 0 → "0". */
    protected function signed(int $n): string
    {
        return ($n > 0 ? '+' : ($n < 0 ? '−' : '')).abs($n);
    }

    /** A signed money delta, e.g. +€12.00 / −€3.40 (U+2212 minus). */
    protected function signedMoney(int $minor, string $currency): string
    {
        return ($minor >= 0 ? '+' : '−').$this->money(abs($minor), $currency);
    }

    /** A delta direction for styling: 'up' / 'down' / 'flat', or null when there is no baseline. */
    protected function dir(?int $n): ?string
    {
        return $n === null ? null : ($n > 0 ? 'up' : ($n < 0 ? 'down' : 'flat'));
    }

    /** The ONE symbol map lives in {@see MoneyFormatter::symbol()} — never a second copy (§N.8f). */
    protected function currencySymbol(string $currency): string
    {
        return MoneyFormatter::symbol($currency);
    }

    /**
     * Reservation status → a rack booking-bar label + tone. Tones are WARM only (terracotta/amber
     * tints — no green/red), matching the design. The keys are exactly the real reservation `status`
     * enum values (enquiry/tentative/confirmed/checked_in/checked_out/cancelled/no_show); nothing is
     * invented. `default` guards forward-compat, never fabricates a status a booking doesn't have.
     *
     * @return array{label: string, tone: string}
     */
    protected function reservationStatusMeta(string $status): array
    {
        return match ($status) {
            'confirmed' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.reservation_confirmed'), 'tone' => 'confirmed'],
            'checked_in' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.reservation_checked_in'), 'tone' => 'in-house'],
            'checked_out' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.reservation_checked_out'), 'tone' => 'departed'],
            'tentative' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.reservation_tentative'), 'tone' => 'pending'],
            'enquiry' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.reservation_enquiry'), 'tone' => 'pending'],
            'no_show' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.reservation_no_show'), 'tone' => 'no-show'],
            'cancelled' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.reservation_cancelled'), 'tone' => 'cancelled'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'tone' => 'pending'],
        };
    }

    /**
     * ISO-3166 alpha-2 → a country name for display ("IE" → "Ireland"), or null when there is genuinely no
     * value. Shared by the Guests CRM list and the guest profile (Wave 2).
     *
     * ⚠️ THE CODE IS THE FALLBACK, NOT AN ERROR. `ext-intl` is NOT in composer.json's require, so it cannot
     * be assumed present — without it (or for a code ICU does not know) this returns the real alpha-2 code.
     * Both outputs are TRUE; one is merely prettier. Declaring a new platform requirement to gild a display
     * label is not a trade worth making, and inlining a 250-row country table would be worse.
     *
     * The CALLER decides what the value means: on `guests.nationality` this is a NATIONALITY, not a country
     * of residence (no residence column exists) — label it accordingly.
     */
    protected function countryName(mixed $iso2): ?string
    {
        $code = is_string($iso2) ? mb_strtoupper(trim($iso2)) : '';

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }

        if (extension_loaded('intl')) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');

            // ICU echoes the input back for a region it does not know — that is not a name, so fall through.
            if ($name !== '' && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }

    /**
     * Booking source → a short human label (direct/OTA/walk-in/…). Reads the real `source` string;
     * unknown values are title-cased rather than dropped.
     */
    protected function sourceLabel(string $source): string
    {
        // Delegates to the ONE operator source-label map (shared with the CSV + PDF exports — anti-drift).
        return OperatorRevenueBreakdown::sourceLabel($source);
    }

    /**
     * Housekeeping task status → label + WARM tone. Keys are EXACTLY the real `housekeeping_tasks.status`
     * enum (pending/in_progress/clean/inspected) — no invented columns or states.
     *
     * @return array{label: string, tone: string}
     */
    protected function housekeepingStatusMeta(string $status): array
    {
        return match ($status) {
            'pending' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.hk_status_pending'), 'tone' => 'pending'],
            'in_progress' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.hk_status_in_progress'), 'tone' => 'in-progress'],
            'clean' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.hk_status_clean'), 'tone' => 'clean'],
            'inspected' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.hk_status_inspected'), 'tone' => 'inspected'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'tone' => 'pending'],
        };
    }

    /** Housekeeping task type → human label (real `type` enum: departure/stayover/deep/arrival_prep). */
    protected function housekeepingTypeLabel(string $type): string
    {
        return match ($type) {
            'departure' => __('php/livewire/operator/concerns/formats_operator_data.hk_type_departure'),
            'stayover' => __('php/livewire/operator/concerns/formats_operator_data.hk_type_stayover'),
            'deep' => __('php/livewire/operator/concerns/formats_operator_data.hk_type_deep'),
            'arrival_prep' => __('php/livewire/operator/concerns/formats_operator_data.hk_type_arrival_prep'),
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    /**
     * Task priority → label + tone (real `priority` enum: low/normal/high). Warm only.
     *
     * @return array{label: string, tone: string}
     */
    protected function priorityMeta(string $priority): array
    {
        return match ($priority) {
            'high' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.priority_high'), 'tone' => 'high'],
            'low' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.priority_low'), 'tone' => 'low'],
            default => ['label' => __('php/livewire/operator/concerns/formats_operator_data.priority_normal'), 'tone' => 'normal'],
        };
    }

    /** Unit kind → human label (real `units.unit_kind` enum: room/apartment/villa/dorm_bed). */
    protected function unitKindLabel(string $kind): string
    {
        return match ($kind) {
            'room' => __('php/livewire/operator/concerns/formats_operator_data.unit_kind_room'),
            'apartment' => __('php/livewire/operator/concerns/formats_operator_data.unit_kind_apartment'),
            'villa' => __('php/livewire/operator/concerns/formats_operator_data.unit_kind_villa'),
            'dorm_bed' => __('php/livewire/operator/concerns/formats_operator_data.unit_kind_dorm_bed'),
            default => ucwords(str_replace('_', ' ', $kind)),
        };
    }

    /**
     * Unit cleanliness → label + WARM tone. Keys are exactly the real `units.housekeeping_status`
     * enum (clean/dirty/inspected) — nothing invented.
     *
     * @return array{label: string, tone: string}
     */
    protected function unitHousekeepingMeta(string $status): array
    {
        return match ($status) {
            'clean' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.unit_hk_clean'), 'tone' => 'clean'],
            'dirty' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.unit_hk_dirty'), 'tone' => 'dirty'],
            'inspected' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.unit_hk_inspected'), 'tone' => 'inspected'],
            default => ['label' => ucfirst($status), 'tone' => 'dirty'],
        };
    }

    /**
     * Unit condition → label + WARM tone. Real `units.condition_status` enum
     * (in_service/out_of_order/out_of_service).
     *
     * @return array{label: string, tone: string}
     */
    protected function unitConditionMeta(string $status): array
    {
        return match ($status) {
            'in_service' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.condition_in_service'), 'tone' => 'in-service'],
            'out_of_order' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.condition_out_of_order'), 'tone' => 'ooo'],
            'out_of_service' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.condition_out_of_service'), 'tone' => 'oos'],
            default => ['label' => ucwords(str_replace('_', ' ', $status)), 'tone' => 'oos'],
        };
    }

    /**
     * Maintenance ticket status → label + WARM tone. Keys are EXACTLY the real `maintenance_tickets.status`
     * enum (open/in_progress/resolved/cancelled) — no invented statuses.
     *
     * @return array{label: string, tone: string}
     */
    protected function maintenanceStatusMeta(string $status): array
    {
        return match ($status) {
            'open' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_open'), 'tone' => 'pending'],
            'in_progress' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_in_progress'), 'tone' => 'confirmed'],
            'resolved' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_resolved'), 'tone' => 'departed'],
            'cancelled' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_cancelled'), 'tone' => 'cancelled'],
            default => ['label' => ucwords(str_replace('_', ' ', $status)), 'tone' => 'pending'],
        };
    }

    /**
     * Maintenance priority → label + WARM tone (real `priority` enum: low/normal/high/urgent). Urgent is a
     * muted warm-amber; higher severity is terracotta.
     *
     * @return array{label: string, tone: string}
     */
    protected function maintenancePriorityMeta(string $priority): array
    {
        return match ($priority) {
            'urgent' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_priority_urgent'), 'tone' => 'urgent'],
            'high' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_priority_high'), 'tone' => 'high'],
            'normal' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_priority_normal'), 'tone' => 'normal'],
            'low' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.maintenance_priority_low'), 'tone' => 'low'],
            default => ['label' => ucfirst($priority), 'tone' => 'normal'],
        };
    }

    /**
     * The SLA state → one human label. Shared (#104 GATE 1d) so the maintenance BOARD and the ticket DETAIL
     * describe the same clock identically — they read the same {@see MaintenanceSla}.
     *
     * @param  array{state: string, over_hours: int, remaining_hours: int}  $sla
     */
    protected function slaLabel(array $sla): string
    {
        return match ($sla['state']) {
            'breached' => (string) __('php/livewire/operator/concerns/formats_operator_data.sla_breached', ['hours' => $sla['over_hours']]),
            'due_soon' => (string) __('php/livewire/operator/concerns/formats_operator_data.sla_due_soon', ['hours' => $sla['remaining_hours']]),
            'on_track' => (string) __('php/livewire/operator/concerns/formats_operator_data.sla_on_track', ['hours' => $sla['remaining_hours']]),
            'no_target' => (string) __('php/livewire/operator/concerns/formats_operator_data.sla_no_target'),
            default => '',
        };
    }

    /** Maintenance category → human label (real `category` enum). */
    protected function maintenanceCategoryLabel(string $category): string
    {
        return match ($category) {
            'plumbing' => __('php/livewire/operator/concerns/formats_operator_data.category_plumbing'),
            'electrical' => __('php/livewire/operator/concerns/formats_operator_data.category_electrical'),
            'appliance' => __('php/livewire/operator/concerns/formats_operator_data.category_appliance'),
            'structural' => __('php/livewire/operator/concerns/formats_operator_data.category_structural'),
            'other' => __('php/livewire/operator/concerns/formats_operator_data.category_other'),
            default => ucfirst($category),
        };
    }

    /** Folio line type → human label (real `folio_items.type` enum). */
    protected function folioItemLabel(string $type): string
    {
        return match ($type) {
            'room' => __('php/livewire/operator/concerns/formats_operator_data.folio_room'),
            'tax' => __('php/livewire/operator/concerns/formats_operator_data.folio_tax'),
            'fee' => __('php/livewire/operator/concerns/formats_operator_data.folio_fee'),
            'add_on' => __('php/livewire/operator/concerns/formats_operator_data.folio_add_on'),
            'product' => __('php/livewire/operator/concerns/formats_operator_data.folio_product'),
            'discount' => __('php/livewire/operator/concerns/formats_operator_data.folio_discount'),
            'manual' => __('php/livewire/operator/concerns/formats_operator_data.folio_manual'),
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    /**
     * Inbox communication channel → label + a short glyph letter + a WARM tone. Keys are EXACTLY the real
     * `message_threads.channel` enum (manual/email/whatsapp/booking_com/airbnb) — no invented channels. All
     * tones are warm tints (terracotta/amber/muted) distinguished by the glyph — never a green/blue brand
     * colour, per the design rule.
     *
     * `filter` is the channel's CODE in the inbox's `?channel=` filter. It is the English word the URL has always
     * carried ("Direct", "Booking.com", …) and is never translated — the inbox filters and compares on it, while
     * `label` is the word a person reads, in their language (I18N-01).
     *
     * @return array{label: string, short: string, tone: string, filter: string}
     */
    protected function channelMeta(string $channel): array
    {
        return match ($channel) {
            'manual' => ['label' => (string) __('php/livewire/operator/concerns/formats_operator_data.channel_direct'), 'short' => 'D', 'tone' => 'manual', 'filter' => 'Direct'],
            'email' => ['label' => (string) __('php/livewire/operator/concerns/formats_operator_data.channel_email'), 'short' => 'E', 'tone' => 'email', 'filter' => 'Email'],
            'whatsapp' => ['label' => 'WhatsApp', 'short' => 'W', 'tone' => 'whatsapp', 'filter' => 'WhatsApp'],
            'booking_com' => ['label' => 'Booking.com', 'short' => 'B', 'tone' => 'bookingcom', 'filter' => 'Booking.com'],
            'airbnb' => ['label' => 'Airbnb', 'short' => 'A', 'tone' => 'airbnb', 'filter' => 'Airbnb'],
            default => ['label' => ucfirst($channel), 'short' => LocalText::upper(mb_substr($channel, 0, 1)), 'tone' => 'manual', 'filter' => ucfirst($channel)],
        };
    }

    /**
     * An inbox `?channel=` filter CODE → the label a person reads for it (the same word {@see channelMeta()} gives
     * that channel). Used by the inbox's channel tabs and active-filter chip.
     */
    protected function channelFilterLabel(string $filter): string
    {
        return match ($filter) {
            'Direct' => $this->channelMeta('manual')['label'],
            'Email' => $this->channelMeta('email')['label'],
            'WhatsApp' => $this->channelMeta('whatsapp')['label'],
            'Booking.com' => $this->channelMeta('booking_com')['label'],
            'Airbnb' => $this->channelMeta('airbnb')['label'],
            default => $filter,
        };
    }

    /**
     * Whether a channel can actually SEND now: the internal `manual` channel always can; a real external
     * channel only when its adapter is configured (`services.inbox.channels.{channel}.enabled`). Deferred
     * skeletons are NOT connected — the reply box shows an honest disabled state (invariant #24), never a
     * fake send.
     */
    protected function channelIsConnected(string $channel): bool
    {
        return $channel === 'manual'
            || (bool) config('services.inbox.channels.'.$channel.'.enabled', false);
    }

    /**
     * `payouts.status` → an OPERATOR-shell badge. The declared enum is
     * pending|processing|paid|failed|reversed|held ({@see Payout}); an unexpected value is
     * echoed through `ucfirst`, never re-badged as something friendlier. The design's "Scheduled" /
     * "In review" / "Sent" are not states this column can hold and are never rendered.
     *
     * ⚠️ ONE DEFINITION, WAS TWO. `OperatorOwnerDetail` and `PayoutRun` each carried a private, byte-identical
     * copy of this map, and both documented the duplication rather than closing it. Two readers of one payout
     * concept drift at the edges, and at money scale a drift is a payout badged wrongly on one screen but not
     * the other — which is exactly what happened in the owner realm, where its copy of this concept badged
     * `processing` and `held` as "Payout pending" (money already sent reading as not started) until it was
     * corrected. Neither operator screen owns the map now, so they cannot disagree.
     *
     * ⚠️ DELIBERATELY *NOT* SHARED WITH THE OWNER REALM, and that separation is load-bearing rather than
     * laziness: the two shells' badge vocabularies do not overlap. The owner shell defines exactly three
     * tones (paid/pending/draft); this shell defines fifteen `tone-*` classes and defines neither `paid` nor
     * `draft`. Collapsing the two maps would emit tone strings the receiving shell has no CSS for, so the
     * badge would lose its colour — a worse outcome than the duplication. The realms also speak differently
     * on purpose: an owner reads "In transit" and "On hold" about their own money, where an operator reads
     * the raw state.
     *
     * ⚠️ `processing` and `held` reach the `default` arm and echo accurately ("Processing" / "Held"). That is
     * the behaviour both copies already had, preserved here to the character — this change unified the
     * source, not the output.
     *
     * @return array{label: string, tone: string}
     */
    protected function payoutStatusMeta(string $status): array
    {
        return match ($status) {
            'pending' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.payout_pending'), 'tone' => 'pending'],
            'paid' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.payout_paid'), 'tone' => 'confirmed'],
            'failed' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.payout_failed'), 'tone' => 'no-show'],
            'reversed' => ['label' => __('php/livewire/operator/concerns/formats_operator_data.payout_reversed'), 'tone' => 'cancelled'],
            default => ['label' => ucfirst($status), 'tone' => 'departed'],
        };
    }
}
