<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Support\Money\MoneyFormatter;
use App\Support\Property\PropertyContext;
use App\Support\Reports\OperatorRevenueBreakdown;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * #104 GATE 2 — the operator Reports CSV export (the wireframe's "Export" was a dead "Soon" pill; it is not
 * infra-blocked, so it was a build gap, not an honest deferral). Streams the REAL by-channel + by-month revenue
 * breakdown for the current property scope + range, through the SAME {@see OperatorRevenueBreakdown} the screen
 * renders — so the CSV and the page can never disagree (#17). Gated `can:reports.view` at the route (the same
 * gate as the screen); scope resolves through {@see PropertyContext} (never a request-supplied property id).
 */
class OperatorReportsExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $ctx = app(PropertyContext::class);
        $ids = $ctx->readIds();
        $range = $this->resolveRange((string) $request->query('range', 'this-year'));
        $currency = $this->currency($ctx);

        $reservationIds = $ids === []
            ? []
            : Reservation::query()
                ->whereIn('property_id', $ids)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->whereDate('check_in', '>=', $range['start']->toDateString())
                ->whereDate('check_in', '<', $range['end']->toDateString())
                ->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $channels = OperatorRevenueBreakdown::byChannel($reservationIds, $currency);
        $months = OperatorRevenueBreakdown::byMonth($reservationIds, $currency, $range['start'], $range['end']);

        $filename = 'reports-'.$range['key'].'.csv';

        return response()->streamDownload(function () use ($channels, $months, $currency, $range): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, [$this->csvSafe('Vyrobel report'), $this->csvSafe($range['label'])]);
            fputcsv($out, []);

            fputcsv($out, [$this->csvSafe('Revenue by channel')]);
            fputcsv($out, ['Channel', 'Bookings', 'Revenue', 'Share %']);
            foreach ($channels as $c) {
                fputcsv($out, [
                    $this->csvSafe($this->sourceLabel($c['source'])),
                    $c['bookings'],
                    MoneyFormatter::display($c['revenue_minor'], $currency),
                    $c['share'],
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, [$this->csvSafe('Revenue by month')]);
            fputcsv($out, ['Month', 'Revenue']);
            foreach ($months as $m) {
                fputcsv($out, [$this->csvSafe((string) $m['label']), MoneyFormatter::display($m['revenue_minor'], $currency)]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable} */
    private function resolveRange(string $key): array
    {
        $today = CarbonImmutable::today();

        return match ($key) {
            'ytd' => ['key' => 'ytd', 'label' => 'Year to date', 'start' => $today->startOfYear(), 'end' => $today->addDay()],
            '6m' => ['key' => '6m', 'label' => 'Last 6 months', 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay()],
            default => ['key' => 'this-year', 'label' => $today->format('Y'), 'start' => $today->startOfYear(), 'end' => $today->startOfYear()->addYear()],
        };
    }

    private function currency(PropertyContext $ctx): string
    {
        $id = $ctx->id();
        if ($id !== null) {
            return (string) (Property::query()->where('id', $id)->value('currency') ?? 'EUR');
        }

        return (string) (Property::query()->orderBy('name')->value('currency') ?? 'EUR');
    }

    private function sourceLabel(string $source): string
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

    /** Neutralise CSV formula injection: a field beginning with = + - @ (or a control char) is quote-prefixed. */
    private function csvSafe(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);

        return preg_match('/^[=+\-@\t]/', $value) === 1 ? "'".$value : $value;
    }
}
