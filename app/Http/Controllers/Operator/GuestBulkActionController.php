<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Mail\OperatorGuestMessageMail;
use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Property;
use App\Services\GuestService;
use App\Services\InboxService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Export\CsvSafe;
use App\Support\Guest\GuestMarketing;
use App\Support\Guest\GuestName;
use App\Support\Guest\MarketingUnsubscribe;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Guests-list BULK actions (#87) — Message · Delete (GDPR erasure) · Export. The locked operator-mutation
 * pattern (POST → controller → service → PRG); every id is re-resolved through the tenant-scoped global scope
 * (a foreign-org id simply isn't found), so a tampered id list can only ever touch this org's guests.
 *
 * ⚠️ DELETE = ANONYMIZE, NEVER DESTROY. The bulk delete loops the CERTIFIED {@see Guest::anonymize()} — PII
 * erased, ID-doc bytes purged, messages/notes redacted, but the row KEPT so reservations/folios/ledger stay
 * intact (anonymized). `can:guests.erase` on the route (#30). Typed "ERASE" is checked SERVER-side.
 *
 * Read-only (#84): Message + Delete are writes → refused while the workspace is paused. Export is a read
 * (download of contact already on screen) → allowed, but AUDITED (who exported, how many — never the values).
 */
class GuestBulkActionController extends Controller
{
    /** Bulk email selected guests + record a manual inbox thread each. Marketing mode skips non-consented. */
    public function message(Request $request, InboxService $inbox): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->paused();
        }

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
            'body' => ['required', 'string', 'max:2000'],
            'marketing' => ['sometimes', 'boolean'],
        ]);

        $marketing = $request->boolean('marketing');
        $property = $this->currentPropertyName();
        $guests = Guest::query()->whereIn('id', $data['ids'])->get();

        $sent = 0;
        $skippedConsent = 0;
        $skippedNoEmail = 0;

        foreach ($guests as $guest) {
            // A MARKETING blast reaches only opted-in guests (#87 consent gate). A non-marketing (service)
            // message goes to everyone reachable.
            if ($marketing && ! GuestMarketing::canReceive($guest)) {
                $skippedConsent++;

                continue;
            }

            $email = is_string($guest->email) ? trim($guest->email) : '';
            if ($email === '' || $guest->isAnonymized()) {
                $skippedNoEmail++;

                continue;
            }

            // The real send (MailSafetyGuard → log/Mailpit outside production).
            //
            // ⚠️ A MARKETING send carries a working opt-out — the footer link plus the RFC 8058 headers — so the
            // consent gate above is something the guest can actually reach. A SERVICE message carries neither:
            // it is not marketing, and offering to unsubscribe from it would be a promise we don't honour.
            Mail::to($email)->send(new OperatorGuestMessageMail(
                GuestName::display($guest),
                $data['body'],
                $property,
                $marketing ? MarketingUnsubscribe::url($guest) : null,
                $marketing ? MarketingUnsubscribe::oneClickUrl($guest) : null,
            ));

            // Record it in the inbox so the operator keeps a thread and a future inbound reply attaches.
            $thread = $this->manualThreadFor($guest, $inbox, $request);
            $inbox->sendOutbound($thread, $data['body'], $request->user());

            $sent++;
        }

        $parts = [$sent.' '.($sent === 1 ? 'guest' : 'guests').' messaged'];
        if ($skippedConsent > 0) {
            $parts[] = $skippedConsent.' skipped (no marketing consent)';
        }
        if ($skippedNoEmail > 0) {
            $parts[] = $skippedNoEmail.' skipped (no email)';
        }

        return $this->back(implode(' · ', $parts).'.');
    }

    /** Bulk-tag selected guests — reuses the certified per-guest {@see GuestService::attachTag} (audited). */
    public function tag(Request $request, GuestService $guests): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->paused();
        }

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $selected = Guest::query()->whereIn('id', $data['ids'])->get();
        foreach ($selected as $guest) {
            if (! $guest->isAnonymized()) {
                $guests->attachTag($guest, $data['name'], $request->user());
            }
        }

        return $this->back($selected->count().' '.($selected->count() === 1 ? 'guest' : 'guests').' tagged “'.trim($data['name']).'”.');
    }

    /**
     * ⚠️ GDPR bulk erasure. `can:guests.erase` on the route. Typed "ERASE" checked here (a client dialog is
     * bypassable). Each guest → the certified {@see Guest::anonymize()} (PII gone, financial history kept).
     */
    public function anonymize(Request $request): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->paused();
        }

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
            'confirm' => ['required', 'string', 'in:ERASE'],
        ], [
            'confirm.in' => 'Type ERASE exactly to confirm this permanent erasure.',
            'confirm.required' => 'Type ERASE to confirm this permanent erasure.',
        ]);

        $guests = Guest::query()->whereIn('id', (array) $request->input('ids'))->get();

        $erased = 0;
        foreach ($guests as $guest) {
            if ($guest->isAnonymized()) {
                continue;   // idempotent — nothing left to erase
            }
            $guest->anonymize();   // the certified path: PII erased, reservations retained (anonymized), audited
            $erased++;
        }

        return $this->back($erased.' '.($erased === 1 ? 'guest' : 'guests').' erased. Their personal data is gone; the financial history remains.');
    }

    /**
     * Export selected guests to CSV. A READ (contact already on screen), so allowed while paused — but AUDITED:
     * the event records WHO exported and HOW MANY, never the contact values (an audit row is plaintext).
     */
    public function export(Request $request, AuditEventRecorder $audit): StreamedResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
        ]);

        $guests = Guest::query()->whereIn('id', $data['ids'])->orderBy('last_name')->orderBy('first_name')->get();

        $audit->record('guest.exported', null, null, ['count' => $guests->count()]);

        $filename = 'Vyrobel-guests-'.$guests->count().'.csv';

        return response()->streamDownload(function () use ($guests): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, CsvSafe::row(['Name', 'Email', 'Phone', 'City', 'Nationality', 'Stays', 'VIP', 'Marketing consent']));
            foreach ($guests as $guest) {
                $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name)) ?: ($guest->isAnonymized() ? 'Anonymized guest' : 'Unnamed guest');
                fputcsv($out, CsvSafe::row([
                    $name,
                    (string) $guest->email,
                    (string) $guest->phone,
                    (string) $guest->city,
                    (string) $guest->nationality,
                    (string) $guest->reservations()->whereNotIn('status', ['cancelled', 'no_show'])->count(),
                    $guest->is_vip ? 'Yes' : 'No',
                    $guest->marketing_consent ? 'Yes' : 'No',
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Reuse the guest's latest MANUAL thread (or open one) so a bulk send doesn't spawn a thread per message. */
    private function manualThreadFor(Guest $guest, InboxService $inbox, Request $request): MessageThread
    {
        $thread = MessageThread::query()
            ->where('guest_id', $guest->getKey())
            ->where('channel', 'manual')
            ->orderByDesc('created_at')
            ->first();

        return $thread ?? $inbox->startThread([
            'channel' => 'manual',
            'guest_id' => (string) $guest->getKey(),
            'subject' => 'Message from your host',
            'assigned_to' => $request->user()?->getKey(),
        ]);
    }

    private function currentPropertyName(): ?string
    {
        $id = app(PropertyContext::class)->id();
        $property = $id !== null ? Property::query()->find($id) : null;

        return $property !== null ? (string) $property->name : null;
    }

    private function paused(): RedirectResponse
    {
        return $this->back('Your workspace is paused (read-only) — bulk actions are off until you resume. Resume from Billing.', 'guest_error');
    }

    private function back(string $message, string $key = 'guest_notice'): RedirectResponse
    {
        return redirect()->route('app.guests')->with($key, $message);
    }
}
