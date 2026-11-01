<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Channel;
use App\Models\Reservation;
use App\Support\I18n\LocalDate;
use App\Support\Owner\OwnerBookingsReader;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator CHANNELS (exact-match) — the OTA channel-manager surface. Tenant-scoped; the route is Pro-gated
 * (`plan.feature:channels`).
 *
 * ⚠️ HONEST-GATED (founder decision): the whole page is OTA sync, which is ACCOUNT-GATED — there is no real
 * Channex/OTA integration (the ChannexClient throws without a partner API key; the fake client is bound). So:
 *   • The DIRECT channel is REAL — Vyrobel's own storefront bookings (`reservations.source='direct'`).
 *   • A connected OTA channel's STATUS is real (a real `channels.status` toggle), but live two-way sync +
 *     booking import are NOT wired — surfaced honestly ("sync activates with your Channex connection"),
 *     NEVER a fabricated "Synced / N imported / last sync 3 min ago".
 *   • The booking mix shows the REAL bookings by `reservations.source` (Direct real; OTA the real count,
 *     genuinely ~0 without sync) — never a fabricated OTA/commission figure (#39).
 */
#[Layout('components.layouts.operator')]
class OperatorChannels extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $orgId = app(TenantContext::class)->id();

        $monthStart = CarbonImmutable::today()->startOfMonth();
        $monthEnd = $monthStart->addMonth();
        $monthLabel = $monthStart->translatedFormat(LocalDate::pattern('M'));

        // REAL bookings by source this month (tenant-scoped) — the honest booking mix.
        $bySource = $orgId === null
            ? collect()
            : Reservation::query()
                ->whereDate('check_in', '>=', $monthStart->toDateString())
                ->whereDate('check_in', '<', $monthEnd->toDateString())
                ->selectRaw('source, COUNT(*) as c')
                ->groupBy('source')
                ->pluck('c', 'source');

        $totalBookings = (int) $bySource->sum();
        $directCount = (int) ($bySource['direct'] ?? 0);
        $otaCount = (int) ($bySource['ota'] ?? 0);
        $directShare = $totalBookings > 0 ? (int) round($directCount / $totalBookings * 100) : 0;

        // REAL OTA channel rows (tenant-scoped). Status is real; live sync is honestly gated.
        $channels = $orgId === null
            ? new Collection
            : Channel::query()->orderBy('name')->get();

        $connected = $channels->filter(fn (Channel $c): bool => (string) $c->status === 'connected')->count();
        $disconnected = $channels->count() - $connected;

        return view('livewire.operator.channels', [
            'viewer' => $this->viewer(),
            'monthLabel' => $monthLabel,
            'stats' => [
                'ota_bookings' => $otaCount,
                'total_bookings' => $totalBookings,
                'direct_share' => $directShare,
                'connected' => $connected,
                'disconnected' => $disconnected,
                'channel_count' => $channels->count(),
            ],
            // The REAL Direct channel (always live — Vyrobel's own storefront; 0% commission).
            'direct' => [
                'bookings' => $directCount,
                'share' => $directShare,
            ],
            'channels' => $this->channelRows($channels),
            'bookingMix' => $this->bookingMix($bySource, $totalBookings),
        ]);
    }

    /**
     * The REAL OTA channel rows — real name/provider/status/commission. Live sync (last-sync, imported
     * counts) is HONESTLY GATED, never fabricated: `synced` reflects a genuine `last_synced_at`, which is
     * null without the real integration → the row honestly reads "sync activates with your Channex account".
     *
     * @param  Collection<int, Channel>  $channels
     * @return list<array<string, mixed>>
     */
    private function channelRows(Collection $channels): array
    {
        return $channels->map(function (Channel $c): array {
            $status = (string) $c->status;
            $bookings = (int) $c->reservations()->count();
            $commission = $c->commission_pct !== null ? rtrim(rtrim(number_format((float) $c->commission_pct, 2), '0'), '.').'%' : null;

            return [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'initial' => strtoupper(substr((string) $c->name, 0, 1)),
                'status' => $status,
                // Translated here (render time), keyed by the status CODE; the view branches on `status`, never this.
                'status_label' => match ($status) {
                    'connected' => __('php/livewire/operator/operator_channels.status_connected'),
                    'error' => __('php/livewire/operator/operator_channels.status_error'),
                    'disabled' => __('php/livewire/operator/operator_channels.status_disabled'),
                    default => __('php/livewire/operator/operator_channels.status_disconnected'),
                },
                // A genuine sync timestamp ONLY if the real integration ever set it (it doesn't yet) — else
                // the honest gated line. Never a fabricated "last sync 3 min ago".
                'last_synced' => $c->last_synced_at !== null ? CarbonImmutable::parse($c->last_synced_at)->diffForHumans() : null,
                'last_error' => is_string($c->last_error) && $c->last_error !== '' ? $c->last_error : null,
                'bookings' => $bookings,
                'commission' => $commission,
            ];
        })->all();
    }

    /**
     * The REAL booking mix by source — Direct + OTA + the rest, with real counts + shares. Never fabricated.
     *
     * @param  Collection<array-key, int>  $bySource
     * @return list<array{label: string, count: int, share: int}>
     */
    private function bookingMix(Collection $bySource, int $total): array
    {
        return $bySource
            ->map(fn (int $count, string $source): array => [
                'label' => OwnerBookingsReader::sourceLabel($source),
                'count' => $count,
                'share' => $total > 0 ? (int) round($count / $total * 100) : 0,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
