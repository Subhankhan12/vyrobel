<?php

declare(strict_types=1);

namespace App\Livewire\Owner\Concerns;

use App\Models\User;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerBookingsReader;
use App\Support\Reservations\ReservationStage;
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
        return MoneyFormatter::display($minor, $currency, $signed);
    }

    protected function currencySymbol(string $currency): string
    {
        return MoneyFormatter::symbol($currency);
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
     * Payout status → badge label + tone, for the OWNER realm's 3-tone vocabulary (paid/pending/draft).
     *
     * ⚠️ `processing` AND `held` USED TO FALL THROUGH TO "Payout pending", AND THAT WAS WRONG ABOUT MONEY.
     * Both are real, live states — `OwnerPayoutService::pay()` sets `processing` when a Connect transfer has
     * been INITIATED and is awaiting its webhook, and `hold()` sets `held` when the tax-hold pauses a payout.
     * An owner reading "Payout pending" against either one is told their money has not started moving when it
     * has, or that it is merely queued when it is actually BLOCKED on something they must act on. The label
     * was the only wrong part; when the states are set is untouched.
     *
     * ⚠️ THE LABELS ARE NOT NEW — they are the ones `OwnerBalance::rowState()` has always used for these two
     * statuses ("In transit" / "On hold", both `pending`-toned). The clearing model got this right from the
     * start; this map is what lagged, so the fix CONVERGES on the existing definition rather than inventing a
     * third vocabulary for one concept (§N.8f).
     *
     * ⚠️ AND `default` NOW MEANS WHAT IT SAYS. It is reached only by `pending` and by a genuinely unknown
     * value — not by two states the enum has carried since the payout engine landed. A silent `default` that
     * swallows real enum members is how a status map goes wrong without anyone seeing it: the demo has no
     * `processing`/`held` payout, so neither branch has ever rendered on a demo page.
     *
     * @return array{label: string, tone: string}
     */
    protected function payoutStatusMeta(string $status): array
    {
        return match ($status) {
            'paid' => ['label' => 'Paid', 'tone' => 'paid'],
            'processing' => ['label' => 'In transit', 'tone' => 'pending'],
            'held' => ['label' => 'On hold', 'tone' => 'pending'],
            'failed' => ['label' => 'Payout failed', 'tone' => 'pending'],
            'reversed' => ['label' => 'Reversed', 'tone' => 'draft'],
            default => ['label' => 'Payout pending', 'tone' => 'pending'],
        };
    }

    /**
     * reservations.source enum → an owner-facing label. Delegates to the ONE owner source-of-truth
     * {@see OwnerBookingsReader::sourceLabel()} so the Bookings page, its CSV export and every
     * other owner surface can never disagree. ⚠️ There is NO 'booking_com'/'airbnb': the OTA BRAND IS NOT
     * MODELLED — an OTA booking is "OTA", nothing more specific.
     */
    protected function sourceLabel(string $source): string
    {
        return OwnerBookingsReader::sourceLabel($source);
    }

    /**
     * A reservation's LIFECYCLE STAGE → owner badge (label + owner-portal tone). The LABEL comes from the ONE
     * shared {@see ReservationStage} vocabulary (In-house / Upcoming / Past / Arrival due / Tentative /
     * Cancelled), so the owner Bookings surfaces can never disagree with the operator's on what stage a stay is
     * in. The TONE maps onto the owner design system's badge tones.
     *
     * @return array{label: string, tone: string}
     */
    protected function stageBadge(string $stage): array
    {
        return [
            'label' => ReservationStage::meta($stage)['label'],
            'tone' => match ($stage) {
                ReservationStage::IN_HOUSE => 'paid',
                ReservationStage::UPCOMING, ReservationStage::DUE, ReservationStage::TENTATIVE => 'pending',
                default => 'draft',   // past, cancelled — settled/closed
            },
        ];
    }
}
