<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Support\Export\CsvSafe;
use App\Support\Exports\ExportAudit;
use App\Support\Owner\OwnerBookingsReader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner Bookings — CSV export (GATE 1). A REAL, owner-scoped download of the SAME rows the page shows: it reads
 * through the ONE shared {@see OwnerBookingsReader}, so the file can never disagree with the screen (net,
 * source, status, guest). Owner boundary is applied THROUGH the units by the reader; a non-linked owner's
 * export is an empty (headers-only) file — fail-closed, never all owners.
 *
 * GATED `exports` (co-owner + accountant) — the same capability as the other owner downloads (units/statement
 * PDFs). A view-only sub-user can see the page but not export (the button is @ownercan-hidden and the route
 * 403s). HONESTY: guest NAME only — no email/phone/contact ever leaves in the CSV (#28); an unattributed
 * booking's net is blank ("Not yet attributed"), never a fabricated €0.
 */
class OwnerBookingsExportController extends Controller
{
    public function download(Request $request, OwnerBookingsReader $reader): StreamedResponse
    {
        $rows = $reader->rows();

        // Sort by check-in (most recent first) so the file reads like the table's "All" view.
        usort($rows, fn (array $a, array $b): int => strcmp($b['check_in_sort'], $a['check_in_sort']));

        $filename = 'my-bookings-'.Carbon::now()->format('Y-m-d').'.csv';

        // Wave B · GATE 5 — record + watermark the download (who / when); the SAME identity in the log + the file's comment row.
        $watermark = app(ExportAudit::class)->stamp($request, 'bookings_csv', 'Bookings export');

        return response()->streamDownload(function () use ($rows, $watermark): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, CsvSafe::row(['# '.$watermark]));
            fputcsv($out, CsvSafe::row(['Guest', 'Reference', 'Unit', 'Check-in', 'Check-out', 'Nights', 'Source', 'Status', 'Net to you']));

            foreach ($rows as $row) {
                fputcsv($out, CsvSafe::row([
                    $row['guest'] ?? '',                          // NAME only — never contact PII (#28)
                    $row['reference'],
                    $row['unit_label'],
                    $row['check_in_sort'],
                    $row['check_out_sort'],
                    $row['nights'],
                    $row['source_label'],                         // real source enum, never an OTA brand
                    $row['status_badge']['label'],
                    // Per-currency net displays joined; empty ⇒ honestly "Not yet attributed", never €0.
                    $row['net_displays'] === [] ? 'Not yet attributed' : implode(' / ', $row['net_displays']),
                ]));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
