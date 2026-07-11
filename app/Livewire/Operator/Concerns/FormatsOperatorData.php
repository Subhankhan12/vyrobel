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
}
