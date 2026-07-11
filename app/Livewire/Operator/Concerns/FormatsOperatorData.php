<?php

declare(strict_types=1);

namespace App\Livewire\Operator\Concerns;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\User;

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
}
