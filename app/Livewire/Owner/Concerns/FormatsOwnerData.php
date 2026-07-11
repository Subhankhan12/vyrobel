<?php

declare(strict_types=1);

namespace App\Livewire\Owner\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Shared presentation formatting for the owner-portal Livewire screens (design pass). Formatting ONLY —
 * no data access, no business logic; the numbers come from the owner-scoped read services untouched.
 */
trait FormatsOwnerData
{
    /**
     * The portfolio display currency — the first available row's currency (statements preferred, then
     * payouts), else EUR. Reads only owner-scoped collections already fetched by the caller.
     *
     * @param  Collection<int, mixed>  $primary
     * @param  Collection<int, mixed>  $fallback
     */
    protected function portfolioCurrency(Collection $primary, Collection $fallback): string
    {
        foreach ([$primary, $fallback] as $set) {
            $first = $set->first();
            if ($first instanceof Model) {
                $currency = $first->getAttribute('currency');
                if (is_string($currency)) {
                    return $currency;
                }
            }
        }

        return 'EUR';
    }

    /** Format integer minor units for display, e.g. 1037000 EUR → "€10,370.00" (or "+…" / "−…"). */
    protected function money(int $minor, string $currency, bool $signed = false): string
    {
        $symbol = $this->currencySymbol($currency);
        $abs = number_format(abs($minor) / 100, 2, '.', ',');

        if ($minor < 0) {
            return "−{$symbol}{$abs}";           // U+2212 minus, matches the design
        }

        return ($signed ? '+' : '')."{$symbol}{$abs}";
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
     * The authenticated user, for the topbar chip.
     *
     * @return array{name: string, initials: string}
     */
    protected function viewer(): array
    {
        $user = auth()->user();
        $name = $user instanceof User ? (string) $user->name : 'Owner';

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
     * Statement status → badge label + tone.
     *
     * @return array{label: string, tone: string}
     */
    protected function statusMeta(string $status): array
    {
        return match ($status) {
            'paid' => ['label' => 'Paid', 'tone' => 'paid'],
            'finalized' => ['label' => 'Payout pending', 'tone' => 'pending'],
            default => ['label' => 'Draft', 'tone' => 'draft'],
        };
    }

    /**
     * Payout status → badge label + tone.
     *
     * @return array{label: string, tone: string}
     */
    protected function payoutStatusMeta(string $status): array
    {
        return match ($status) {
            'paid' => ['label' => 'Paid', 'tone' => 'paid'],
            'failed' => ['label' => 'Payout failed', 'tone' => 'pending'],
            'reversed' => ['label' => 'Reversed', 'tone' => 'draft'],
            default => ['label' => 'Payout pending', 'tone' => 'pending'],
        };
    }
}
