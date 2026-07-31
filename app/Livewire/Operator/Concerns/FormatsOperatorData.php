<?php

declare(strict_types=1);

namespace App\Livewire\Operator\Concerns;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\User;
use App\Support\Maintenance\MaintenanceSla;

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
        $name = $user instanceof User ? (string) $user->name : 'Operator';

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

        return mb_strtoupper($first.$last);
    }

    /** Format integer minor units for display, e.g. 1037000 EUR → "€10,370.00". */
    protected function money(int $minor, string $currency): string
    {
        $symbol = $this->currencySymbol($currency);
        $abs = number_format(abs($minor) / 100, 2, '.', ',');

        return ($minor < 0 ? '−' : '')."{$symbol}{$abs}";   // U+2212 minus, matches the design
    }

    protected function currencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'EUR' => '€',
            'GBP' => '£',
            'USD' => '$',
            default => strtoupper($currency).' ',
        };
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
            'confirmed' => ['label' => 'Confirmed', 'tone' => 'confirmed'],
            'checked_in' => ['label' => 'Checked in', 'tone' => 'in-house'],
            'checked_out' => ['label' => 'Checked out', 'tone' => 'departed'],
            'tentative' => ['label' => 'Tentative', 'tone' => 'pending'],
            'enquiry' => ['label' => 'Enquiry', 'tone' => 'pending'],
            'no_show' => ['label' => 'No-show', 'tone' => 'no-show'],
            'cancelled' => ['label' => 'Cancelled', 'tone' => 'cancelled'],
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
        return match ($source) {
            'direct' => 'Direct',
            'walk_in' => 'Walk-in',
            'corporate' => 'Corporate',
            'group' => 'Group',
            'agent' => 'Agent',
            'ota' => 'OTA',
            default => ucwords(str_replace('_', ' ', $source)),
        };
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
            'pending' => ['label' => 'Pending', 'tone' => 'pending'],
            'in_progress' => ['label' => 'In progress', 'tone' => 'in-progress'],
            'clean' => ['label' => 'Clean', 'tone' => 'clean'],
            'inspected' => ['label' => 'Inspected', 'tone' => 'inspected'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'tone' => 'pending'],
        };
    }

    /** Housekeeping task type → human label (real `type` enum: departure/stayover/deep/arrival_prep). */
    protected function housekeepingTypeLabel(string $type): string
    {
        return match ($type) {
            'departure' => 'Departure clean',
            'stayover' => 'Stayover',
            'deep' => 'Deep clean',
            'arrival_prep' => 'Arrival prep',
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
            'high' => ['label' => 'High', 'tone' => 'high'],
            'low' => ['label' => 'Low', 'tone' => 'low'],
            default => ['label' => 'Normal', 'tone' => 'normal'],
        };
    }

    /** Unit kind → human label (real `units.unit_kind` enum: room/apartment/villa/dorm_bed). */
    protected function unitKindLabel(string $kind): string
    {
        return match ($kind) {
            'room' => 'Room',
            'apartment' => 'Apartment',
            'villa' => 'Villa',
            'dorm_bed' => 'Dorm bed',
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
            'clean' => ['label' => 'Clean', 'tone' => 'clean'],
            'dirty' => ['label' => 'Dirty', 'tone' => 'dirty'],
            'inspected' => ['label' => 'Inspected', 'tone' => 'inspected'],
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
            'in_service' => ['label' => 'In service', 'tone' => 'in-service'],
            'out_of_order' => ['label' => 'Out of order', 'tone' => 'ooo'],
            'out_of_service' => ['label' => 'Out of service', 'tone' => 'oos'],
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
            'open' => ['label' => 'Open', 'tone' => 'pending'],
            'in_progress' => ['label' => 'In progress', 'tone' => 'confirmed'],
            'resolved' => ['label' => 'Resolved', 'tone' => 'departed'],
            'cancelled' => ['label' => 'Cancelled', 'tone' => 'cancelled'],
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
            'urgent' => ['label' => 'Urgent', 'tone' => 'urgent'],
            'high' => ['label' => 'High', 'tone' => 'high'],
            'normal' => ['label' => 'Normal', 'tone' => 'normal'],
            'low' => ['label' => 'Low', 'tone' => 'low'],
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
            'breached' => 'SLA breached · '.$sla['over_hours'].'h over',
            'due_soon' => 'Due in '.$sla['remaining_hours'].'h',
            'on_track' => $sla['remaining_hours'].'h to target',
            'no_target' => 'No SLA target set',
            default => '',
        };
    }

    /** Maintenance category → human label (real `category` enum). */
    protected function maintenanceCategoryLabel(string $category): string
    {
        return match ($category) {
            'plumbing' => 'Plumbing',
            'electrical' => 'Electrical',
            'appliance' => 'Appliance',
            'structural' => 'Structural',
            'other' => 'Other',
            default => ucfirst($category),
        };
    }

    /** Folio line type → human label (real `folio_items.type` enum). */
    protected function folioItemLabel(string $type): string
    {
        return match ($type) {
            'room' => 'Room',
            'tax' => 'Tax',
            'fee' => 'Fee',
            'add_on' => 'Add-on',
            'product' => 'Product',
            'discount' => 'Discount',
            'manual' => 'Charge',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    /**
     * Inbox communication channel → label + a short glyph letter + a WARM tone. Keys are EXACTLY the real
     * `message_threads.channel` enum (manual/email/whatsapp/booking_com/airbnb) — no invented channels. All
     * tones are warm tints (terracotta/amber/muted) distinguished by the glyph — never a green/blue brand
     * colour, per the design rule.
     *
     * @return array{label: string, short: string, tone: string}
     */
    protected function channelMeta(string $channel): array
    {
        return match ($channel) {
            'manual' => ['label' => 'Direct', 'short' => 'D', 'tone' => 'manual'],
            'email' => ['label' => 'Email', 'short' => 'E', 'tone' => 'email'],
            'whatsapp' => ['label' => 'WhatsApp', 'short' => 'W', 'tone' => 'whatsapp'],
            'booking_com' => ['label' => 'Booking.com', 'short' => 'B', 'tone' => 'bookingcom'],
            'airbnb' => ['label' => 'Airbnb', 'short' => 'A', 'tone' => 'airbnb'],
            default => ['label' => ucfirst($channel), 'short' => mb_strtoupper(mb_substr($channel, 0, 1)), 'tone' => 'manual'],
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
}
