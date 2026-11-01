<?php

declare(strict_types=1);

namespace App\Support\Exports;

use App\Models\ExportDownload;
use App\Support\I18n\LocalDate;
use App\Support\Owner\OwnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * EXPORT AUDIT + WATERMARK (Wave B · GATE 5) — the ONE place a financial export download is recorded AND the
 * downloader watermark line is produced, so the audit-log and the stamp on the file can never disagree.
 *
 * A single {@see stamp()} call from an export controller both writes the {@see ExportDownload} row (who / what /
 * when) and returns the watermark string to embed in the PDF footer or the CSV header. Owner-scoped via the active
 * {@see OwnerContext}. This makes the Team security note's claim TRUE — exports really are watermarked with, and
 * logged against, who downloaded them.
 */
class ExportAudit
{
    /**
     * Record the download + return the watermark line to embed. Call it once per export, before streaming.
     */
    public function stamp(Request $request, string $kind, string $label, ?string $subjectId = null): string
    {
        // Every export route sits behind `auth`, so the user is present; guard defensively all the same.
        $user = $request->user();
        $name = $user !== null && filled($user->name) ? (string) $user->name : 'Unknown user';
        $email = $user !== null ? (string) $user->email : '';
        $ownerId = app(OwnerContext::class)->ownerIds()[0] ?? null;

        ExportDownload::create([
            'owner_id' => $ownerId,
            'user_id' => $user?->getKey(),
            'downloader_name' => $name,
            'kind' => $kind,
            'label' => $label,
            'subject_id' => $subjectId,
            'ip' => $request->ip(),
        ]);

        // The audit row above keeps the stored English fallback; the watermark is read by the downloader, so it is
        // written in the downloader's language (I18N-01).
        $shownName = $user !== null && filled($user->name) ? (string) $user->name : (string) __('php/support/exports/export_audit.unknown_user');
        $when = Carbon::now('UTC')->translatedFormat(LocalDate::pattern('j M Y, H:i'));

        return (string) ($email !== ''
            ? __('php/support/exports/export_audit.watermark_with_email', ['name' => $shownName, 'email' => $email, 'when' => $when])
            : __('php/support/exports/export_audit.watermark', ['name' => $shownName, 'when' => $when]));
    }
}
